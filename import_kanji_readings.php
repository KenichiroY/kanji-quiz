<?php
/**
 * 読み仮名インポートスクリプト
 * temp/kanji_readings.csv から読み仮名データをDBにインポートします
 *
 * 前提条件:
 * - init_db.php が実行済み
 * - import_haitou_kanji.php が実行済み（haitou_kanjiテーブルにデータあり）
 * - extract_kanji_readings.php が実行済み（CSVファイルあり）
 */

declare(strict_types=1);

$dbPath = __DIR__ . '/db/kanji.db';
$csvPath = __DIR__ . '/temp/kanji_readings.csv';

// 前提条件チェック
if (!file_exists($dbPath)) {
    echo "エラー: データベースが存在しません。\n";
    echo "先に init_db.php を実行してください。\n";
    exit(1);
}

if (!file_exists($csvPath)) {
    echo "エラー: CSVファイルが存在しません: {$csvPath}\n";
    echo "先に extract_kanji_readings.php を実行してください。\n";
    exit(1);
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 配当漢字の存在確認
    $haitouCount = (int)$pdo->query("SELECT COUNT(*) FROM haitou_kanji")->fetchColumn();
    if ($haitouCount === 0) {
        echo "エラー: 配当漢字データがありません。\n";
        echo "先に import_haitou_kanji.php を実行してください。\n";
        exit(1);
    }
    echo "配当漢字: {$haitouCount}字を確認しました。\n";

    // 既存の読み仮名データを確認
    $existingCount = (int)$pdo->query("SELECT COUNT(*) FROM kanji_readings")->fetchColumn();
    if ($existingCount > 0) {
        echo "既に {$existingCount} 件の読み仮名データが登録されています。\n";
        echo "再インポートする場合は、先にデータを削除してください。\n";
        echo "削除コマンド: DELETE FROM kanji_readings;\n";
        exit(0);
    }

    // 漢字→kanji_id のマッピングを作成
    $kanjiMap = [];
    $stmt = $pdo->query("SELECT kanji_id, kanji FROM haitou_kanji");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $kanjiMap[$row['kanji']] = (int)$row['kanji_id'];
    }

    // CSVファイル読み込み
    $handle = fopen($csvPath, 'r');
    if ($handle === false) {
        echo "エラー: CSVファイルを開けません。\n";
        exit(1);
    }

    // BOMスキップ
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // ヘッダー行をスキップ
    fgetcsv($handle);

    // データ挿入準備
    $insertStmt = $pdo->prepare("
        INSERT INTO kanji_readings (kanji_id, is_onyomi, reading, okurigana)
        VALUES (?, ?, ?, ?)
    ");

    $pdo->beginTransaction();

    $importedCount = 0;
    $skippedCount = 0;
    $errors = [];
    $lineNum = 1;
    $gradeStats = array_fill(1, 6, ['onyomi' => 0, 'kunyomi' => 0]);

    // 学年マッピング取得
    $gradeMap = [];
    $stmt = $pdo->query("SELECT kanji_id, grade FROM haitou_kanji");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gradeMap[(int)$row['kanji_id']] = (int)$row['grade'];
    }

    while (($row = fgetcsv($handle)) !== false) {
        $lineNum++;

        if (count($row) < 3) {
            $errors[] = "{$lineNum}行目: 列数が不足";
            $skippedCount++;
            continue;
        }

        $kanji = trim($row[0]);
        $isOnyomi = (int)$row[1];
        $reading = trim($row[2]);
        $okurigana = isset($row[3]) ? trim($row[3]) : null;
        $okurigana = $okurigana === '' ? null : $okurigana;

        // バリデーション
        if (!isset($kanjiMap[$kanji])) {
            $errors[] = "{$lineNum}行目: 未登録の漢字「{$kanji}」";
            $skippedCount++;
            continue;
        }

        if (!in_array($isOnyomi, [0, 1], true)) {
            $errors[] = "{$lineNum}行目: is_onyomiが不正({$isOnyomi})";
            $skippedCount++;
            continue;
        }

        if (empty($reading)) {
            $errors[] = "{$lineNum}行目: 読み仮名が空";
            $skippedCount++;
            continue;
        }

        // ひらがなチェック
        if (!preg_match('/^[ぁ-んー]+$/u', $reading)) {
            $errors[] = "{$lineNum}行目: 読み仮名がひらがなではありません「{$reading}」";
            $skippedCount++;
            continue;
        }

        if ($okurigana !== null && !preg_match('/^[ぁ-んー]+$/u', $okurigana)) {
            $errors[] = "{$lineNum}行目: オクリガナがひらがなではありません「{$okurigana}」";
            $skippedCount++;
            continue;
        }

        $kanjiId = $kanjiMap[$kanji];
        $insertStmt->execute([$kanjiId, $isOnyomi, $reading, $okurigana]);
        $importedCount++;

        // 学年別統計
        $grade = $gradeMap[$kanjiId];
        if ($isOnyomi === 1) {
            $gradeStats[$grade]['onyomi']++;
        } else {
            $gradeStats[$grade]['kunyomi']++;
        }
    }

    $pdo->commit();
    fclose($handle);

    // 結果表示
    echo "\nインポート完了!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  成功: {$importedCount}件\n";
    echo "  スキップ: {$skippedCount}件\n";

    echo "\n学年別読み仮名数:\n";
    foreach ($gradeStats as $grade => $stats) {
        $total = $stats['onyomi'] + $stats['kunyomi'];
        if ($total > 0) {
            echo "  {$grade}年生: 音読み{$stats['onyomi']}件, 訓読み{$stats['kunyomi']}件 (計{$total}件)\n";
        }
    }

    // 読みのない漢字を確認
    $noReadings = $pdo->query("
        SELECT h.kanji, h.grade
        FROM haitou_kanji h
        LEFT JOIN kanji_readings r ON h.kanji_id = r.kanji_id
        WHERE r.reading_id IS NULL
        ORDER BY h.grade, h.kanji_id
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (count($noReadings) > 0) {
        echo "\n警告: 読みのない漢字が " . count($noReadings) . " 字あります:\n";
        $byGrade = [];
        foreach ($noReadings as $row) {
            $byGrade[$row['grade']][] = $row['kanji'];
        }
        foreach ($byGrade as $grade => $chars) {
            echo "  {$grade}年生: " . implode('', $chars) . "\n";
        }
    }

    // エラー詳細（最初の10件のみ表示）
    if (!empty($errors)) {
        echo "\nエラー詳細（最初の10件）:\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "  - {$error}\n";
        }
        if (count($errors) > 10) {
            echo "  ... 他 " . (count($errors) - 10) . " 件\n";
        }
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "データベースエラー: " . $e->getMessage() . "\n";
    exit(1);
}
