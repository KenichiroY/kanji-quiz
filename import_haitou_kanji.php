<?php
/**
 * 配当漢字インポートスクリプト
 * temp/haitoukanji.csv から配当漢字データをDBにインポートします
 */

$dbPath = __DIR__ . '/db/kanji.db';
$csvPath = __DIR__ . '/temp/haitoukanji.csv';

if (!file_exists($dbPath)) {
    die("エラー: データベースが存在しません。先に init_db.php を実行してください。\n");
}

if (!file_exists($csvPath)) {
    die("エラー: CSVファイルが存在しません: {$csvPath}\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 既存データ数を確認
    $existingCount = $pdo->query("SELECT COUNT(*) FROM haitou_kanji")->fetchColumn();
    if ($existingCount > 0) {
        echo "既に {$existingCount} 件の配当漢字データが登録されています。\n";
        echo "再インポートする場合は、先にデータを削除してください。\n";
        echo "削除コマンド: DELETE FROM haitou_kanji;\n";
        exit(0);
    }

    // CSVファイルを読み込み
    $handle = fopen($csvPath, 'r');
    if ($handle === false) {
        die("エラー: CSVファイルを開けません。\n");
    }

    // BOMをスキップ
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // ヘッダー行をスキップ
    $header = fgetcsv($handle);

    // データ挿入
    $stmt = $pdo->prepare("INSERT INTO haitou_kanji (grade, kanji) VALUES (?, ?)");
    $pdo->beginTransaction();

    $count = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 3) continue;

        // CSVの列: 主キー(使わない), 学年, 漢字
        $grade = (int)$row[1];
        $kanji = trim($row[2]);

        if ($grade >= 1 && $grade <= 6 && mb_strlen($kanji) === 1) {
            $stmt->execute([$grade, $kanji]);
            $count++;
        }
    }

    $pdo->commit();
    fclose($handle);

    echo "インポート完了: {$count} 件の配当漢字を登録しました。\n";

    // 学年別集計を表示
    echo "\n学年別内訳:\n";
    $result = $pdo->query("SELECT grade, COUNT(*) as cnt FROM haitou_kanji GROUP BY grade ORDER BY grade");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['grade']}年生: {$row['cnt']}字\n";
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("データベースエラー: " . $e->getMessage() . "\n");
}
