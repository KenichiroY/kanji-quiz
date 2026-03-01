<?php
/**
 * 単元マイグレーション: 教科書単元 → 20字ずつの「第N回」に再編成
 *
 * 使い方: php migrate_units.php
 */

$db = new PDO('sqlite:' . __DIR__ . '/db/kanji.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$GROUP_SIZE = 20;
$MIN_LAST_GROUP = 5; // 最終グループがこれ以下なら前のグループに統合

$db->beginTransaction();

try {
    // === Step 1: 既存の全単元を削除 ===
    $db->exec("DELETE FROM units");
    // AUTO_INCREMENTをリセット
    $db->exec("DELETE FROM sqlite_sequence WHERE name='units'");
    echo "既存単元を削除しました。\n";

    // === Step 2: 学年ごとに新単元を作成 ===
    $grades = $db->query("SELECT grade_id FROM grades ORDER BY grade_id")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($grades as $gradeId) {
        // この学年の配当漢字をkanji_id順で取得
        $kanjiList = $db->prepare("SELECT kanji_id, kanji FROM haitou_kanji WHERE grade = ? ORDER BY kanji_id");
        $kanjiList->execute([$gradeId]);
        $allKanji = $kanjiList->fetchAll(PDO::FETCH_ASSOC);
        $total = count($allKanji);

        if ($total === 0) {
            echo "{$gradeId}年生: 配当漢字なし、スキップ\n";
            continue;
        }

        // グループ分け
        $groups = array_chunk($allKanji, $GROUP_SIZE);

        // 最終グループが小さすぎる場合、前のグループに統合
        if (count($groups) > 1 && count(end($groups)) <= $MIN_LAST_GROUP) {
            $lastGroup = array_pop($groups);
            $groups[count($groups) - 1] = array_merge($groups[count($groups) - 1], $lastGroup);
        }

        $unitCount = count($groups);
        echo "{$gradeId}年生: {$total}字 → {$unitCount}単元\n";

        $insertUnit = $db->prepare("INSERT INTO units (grade_id, unit_number, unit_name) VALUES (?, ?, ?)");
        $updateKanji = $db->prepare("UPDATE haitou_kanji SET unit_id = ? WHERE kanji_id = ?");

        foreach ($groups as $i => $group) {
            $unitNumber = $i + 1;
            $unitName = "第{$unitNumber}回";

            // 単元を作成
            $insertUnit->execute([$gradeId, $unitNumber, $unitName]);
            $unitId = $db->lastInsertId();

            // 配当漢字にunit_idを設定
            foreach ($group as $kanji) {
                $updateKanji->execute([$unitId, $kanji['kanji_id']]);
            }

            $kanjiStr = implode('', array_column($group, 'kanji'));
            echo "  第{$unitNumber}回 (unit_id={$unitId}): " . count($group) . "字 [{$kanjiStr}]\n";
        }
    }

    // === Step 3: 問題のunit_idを再設定 ===
    // reading_idがある問題: reading_id → kanji_readings → haitou_kanji → unit_id
    $updated = $db->exec("
        UPDATE questions SET unit_id = (
            SELECT hk.unit_id
            FROM kanji_readings kr
            JOIN haitou_kanji hk ON kr.kanji_id = hk.kanji_id
            WHERE kr.reading_id = questions.reading_id
        )
        WHERE reading_id IS NOT NULL
    ");
    echo "\nreading_id経由で {$updated} 問のunit_idを更新しました。\n";

    // reading_idがNULLの問題: kanji_textで漢字を特定
    $nullReadings = $db->query("SELECT question_id, kanji_text, unit_id FROM questions WHERE reading_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);

    if (count($nullReadings) > 0) {
        echo "\nreading_idがNULLの問題を処理中...\n";
        $findUnit = $db->prepare("SELECT unit_id FROM haitou_kanji WHERE kanji = ? LIMIT 1");
        $updateQ = $db->prepare("UPDATE questions SET unit_id = ? WHERE question_id = ?");

        foreach ($nullReadings as $q) {
            // kanji_textから漢字1文字を抽出
            $chars = preg_split('//u', $q['kanji_text'], -1, PREG_SPLIT_NO_EMPTY);
            $matched = false;
            foreach ($chars as $char) {
                $findUnit->execute([$char]);
                $result = $findUnit->fetch(PDO::FETCH_ASSOC);
                if ($result && $result['unit_id']) {
                    $updateQ->execute([$result['unit_id'], $q['question_id']]);
                    echo "  question_id={$q['question_id']} (kanji_text='{$q['kanji_text']}') → unit_id={$result['unit_id']}\n";
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                echo "  [警告] question_id={$q['question_id']} (kanji_text='{$q['kanji_text']}') の単元を特定できませんでした。\n";
            }
        }
    }

    // === Step 4: 検証 ===
    echo "\n=== 検証 ===\n";

    // 単元ごとの問題数
    $verify = $db->query("
        SELECT g.grade_name, u.unit_name, COUNT(q.question_id) as q_count
        FROM units u
        JOIN grades g ON u.grade_id = g.grade_id
        LEFT JOIN questions q ON q.unit_id = u.unit_id
        GROUP BY u.unit_id
        ORDER BY g.grade_id, u.unit_number
    ")->fetchAll(PDO::FETCH_ASSOC);

    $currentGrade = '';
    foreach ($verify as $row) {
        if ($row['grade_name'] !== $currentGrade) {
            $currentGrade = $row['grade_name'];
            echo "\n{$currentGrade}:\n";
        }
        echo "  {$row['unit_name']}: {$row['q_count']}問\n";
    }

    // unit_idがNULLの問題チェック
    $orphans = $db->query("SELECT COUNT(*) FROM questions WHERE unit_id NOT IN (SELECT unit_id FROM units)")->fetchColumn();
    echo "\n孤立問題（存在しない単元を参照）: {$orphans}件\n";

    $db->commit();
    echo "\nマイグレーション完了！\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "エラー: " . $e->getMessage() . "\n";
    echo "ロールバックしました。\n";
    exit(1);
}
