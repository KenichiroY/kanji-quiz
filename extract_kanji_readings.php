<?php
/**
 * KANJIDIC2から配当漢字の読み仮名を抽出するスクリプト
 *
 * 使用方法:
 * 1. http://www.edrdg.org/kanjidic/kanjidic2.xml.gz をダウンロード
 * 2. 解凍して temp/kanjidic2.xml として配置
 * 3. php extract_kanji_readings.php を実行
 *
 * 出力: temp/kanji_readings.csv
 */

declare(strict_types=1);

const INPUT_XML = __DIR__ . '/temp/kanjidic2.xml';
const OUTPUT_CSV = __DIR__ . '/temp/kanji_readings.csv';
const TARGET_GRADES = [1, 2, 3, 4, 5, 6];

/**
 * カタカナをひらがなに変換
 */
function katakanaToHiragana(string $str): string
{
    return mb_convert_kana($str, 'c', 'UTF-8');
}

/**
 * 訓読みからオクリガナを分離
 * 例: "た.べる" → ["reading" => "た", "okurigana" => "べる"]
 * 例: "いち" → ["reading" => "いち", "okurigana" => ""]
 */
function parseKunyomi(string $reading): array
{
    // 特殊記号を除去（例: "-" や "(" など）
    $cleaned = preg_replace('/[-\(\)]/', '', $reading);

    if (strpos($cleaned, '.') !== false) {
        [$base, $okurigana] = explode('.', $cleaned, 2);
        return ['reading' => $base, 'okurigana' => $okurigana];
    }

    return ['reading' => $cleaned, 'okurigana' => ''];
}

/**
 * ひらがなのみで構成されているか確認
 */
function isHiragana(string $str): bool
{
    return preg_match('/^[ぁ-んー]+$/u', $str) === 1;
}

/**
 * メイン処理
 */
function main(): void
{
    // 入力ファイル確認
    if (!file_exists(INPUT_XML)) {
        echo "エラー: KANJIDIC2ファイルが見つかりません。\n\n";
        echo "ダウンロード手順:\n";
        echo "1. http://www.edrdg.org/kanjidic/kanjidic2.xml.gz をダウンロード\n";
        echo "2. gzファイルを解凍\n";
        echo "3. 解凍したXMLを以下に配置: " . INPUT_XML . "\n";
        exit(1);
    }

    echo "KANJIDIC2を読み込み中...\n";

    // XMLを読み込み
    $xml = simplexml_load_file(INPUT_XML);
    if ($xml === false) {
        echo "エラー: XMLの解析に失敗しました。\n";
        exit(1);
    }

    // CSVファイル作成
    $csvFile = fopen(OUTPUT_CSV, 'w');
    if ($csvFile === false) {
        echo "エラー: CSVファイルを作成できません。\n";
        exit(1);
    }

    // UTF-8 BOMを書き込み
    fwrite($csvFile, "\xEF\xBB\xBF");
    fputcsv($csvFile, ['kanji', 'is_onyomi', 'reading', 'okurigana']);

    $kanjiCount = 0;
    $readingCount = 0;
    $skippedReadings = 0;
    $gradeStats = array_fill(1, 6, ['kanji' => 0, 'onyomi' => 0, 'kunyomi' => 0]);

    foreach ($xml->character as $character) {
        // 学年チェック
        $grade = null;
        if (isset($character->misc->grade)) {
            foreach ($character->misc->grade as $g) {
                $gradeVal = (int)$g;
                if (in_array($gradeVal, TARGET_GRADES, true)) {
                    $grade = $gradeVal;
                    break;
                }
            }
        }
        if ($grade === null) {
            continue;
        }

        $kanji = (string)$character->literal;
        $kanjiCount++;
        $gradeStats[$grade]['kanji']++;

        // 読み仮名を抽出
        if (!isset($character->reading_meaning->rmgroup)) {
            continue;
        }

        foreach ($character->reading_meaning->rmgroup as $rmgroup) {
            if (!isset($rmgroup->reading)) {
                continue;
            }

            foreach ($rmgroup->reading as $reading) {
                $type = (string)($reading['r_type'] ?? '');
                $value = (string)$reading;
                $status = (string)($reading['r_status'] ?? '');

                // 一般的な読みのみを抽出（稀な読みは除外）
                // 空のステータスまたは'jy'（常用漢字）のみを含める
                if ($status !== '' && $status !== 'jy') {
                    $skippedReadings++;
                    continue;
                }

                if ($type === 'ja_on') {
                    // 音読み（カタカナ→ひらがな変換）
                    $hiragana = katakanaToHiragana($value);

                    if (!isHiragana($hiragana)) {
                        $skippedReadings++;
                        continue;
                    }

                    fputcsv($csvFile, [$kanji, 1, $hiragana, '']);
                    $readingCount++;
                    $gradeStats[$grade]['onyomi']++;

                } elseif ($type === 'ja_kun') {
                    // 訓読み（オクリガナ分離）
                    $parsed = parseKunyomi($value);

                    if (!isHiragana($parsed['reading'])) {
                        $skippedReadings++;
                        continue;
                    }
                    if ($parsed['okurigana'] !== '' && !isHiragana($parsed['okurigana'])) {
                        $skippedReadings++;
                        continue;
                    }

                    fputcsv($csvFile, [
                        $kanji,
                        0,
                        $parsed['reading'],
                        $parsed['okurigana']
                    ]);
                    $readingCount++;
                    $gradeStats[$grade]['kunyomi']++;
                }
            }
        }
    }

    fclose($csvFile);

    // 結果表示
    echo "\n抽出完了!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  対象漢字: {$kanjiCount}字\n";
    echo "  抽出した読み: {$readingCount}件\n";
    echo "  スキップした読み: {$skippedReadings}件（稀な読み等）\n";
    echo "  出力ファイル: " . OUTPUT_CSV . "\n";
    echo "\n学年別統計:\n";

    foreach ($gradeStats as $g => $stats) {
        if ($stats['kanji'] > 0) {
            $total = $stats['onyomi'] + $stats['kunyomi'];
            echo "  {$g}年生: {$stats['kanji']}字 / 音読み{$stats['onyomi']}件, 訓読み{$stats['kunyomi']}件 (計{$total}件)\n";
        }
    }

    echo "\n次のステップ:\n";
    echo "  php import_kanji_readings.php\n";
}

main();
