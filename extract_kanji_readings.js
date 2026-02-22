/**
 * KANJIDIC2から配当漢字の読み仮名を抽出するスクリプト (Node.js版)
 *
 * 使用方法:
 * 1. http://www.edrdg.org/kanjidic/kanjidic2.xml.gz をダウンロード
 * 2. 解凍して temp/kanjidic2.xml として配置
 * 3. node extract_kanji_readings.js を実行
 *
 * 出力: temp/kanji_readings.csv
 */

const fs = require('fs');
const path = require('path');

const INPUT_XML = path.join(__dirname, 'temp', 'kanjidic2.xml');
const OUTPUT_CSV = path.join(__dirname, 'temp', 'kanji_readings.csv');
const TARGET_GRADES = [1, 2, 3, 4, 5, 6];

/**
 * カタカナをひらがなに変換
 */
function katakanaToHiragana(str) {
    return str.replace(/[\u30A1-\u30F6]/g, (match) => {
        return String.fromCharCode(match.charCodeAt(0) - 0x60);
    });
}

/**
 * 訓読みからオクリガナを分離
 * 例: "た.べる" → {reading: "た", okurigana: "べる"}
 */
function parseKunyomi(reading) {
    // 特殊記号を除去
    const cleaned = reading.replace(/[-()]/g, '');

    if (cleaned.includes('.')) {
        const [base, okurigana] = cleaned.split('.', 2);
        return { reading: base, okurigana: okurigana || '' };
    }

    return { reading: cleaned, okurigana: '' };
}

/**
 * ひらがなのみで構成されているか確認
 */
function isHiragana(str) {
    return /^[ぁ-んー]+$/.test(str);
}

/**
 * XMLをパースしてデータを抽出
 */
function parseXML(xmlContent) {
    const results = [];
    const gradeStats = {};
    for (let i = 1; i <= 6; i++) {
        gradeStats[i] = { kanji: 0, onyomi: 0, kunyomi: 0 };
    }

    let kanjiCount = 0;
    let readingCount = 0;
    let skippedReadings = 0;

    // 各<character>要素を抽出
    const characterRegex = /<character>([\s\S]*?)<\/character>/g;
    let match;

    while ((match = characterRegex.exec(xmlContent)) !== null) {
        const charBlock = match[1];

        // 学年を取得
        const gradeMatch = charBlock.match(/<grade>(\d+)<\/grade>/);
        if (!gradeMatch) continue;

        const grade = parseInt(gradeMatch[1], 10);
        if (!TARGET_GRADES.includes(grade)) continue;

        // 漢字を取得
        const literalMatch = charBlock.match(/<literal>(.)<\/literal>/);
        if (!literalMatch) continue;

        const kanji = literalMatch[1];
        kanjiCount++;
        gradeStats[grade].kanji++;

        // reading_meaning/rmgroup内の読みを抽出
        const rmgroupRegex = /<rmgroup>([\s\S]*?)<\/rmgroup>/g;
        let rmMatch;

        while ((rmMatch = rmgroupRegex.exec(charBlock)) !== null) {
            const rmBlock = rmMatch[1];

            // 全ての<reading>要素を抽出
            const readingRegex = /<reading r_type="(ja_on|ja_kun)"(?: r_status="([^"]*)")?[^>]*>([^<]+)<\/reading>/g;
            let readMatch;

            while ((readMatch = readingRegex.exec(rmBlock)) !== null) {
                const type = readMatch[1];
                const status = readMatch[2] || '';
                const value = readMatch[3];

                // 一般的な読みのみを抽出（稀な読みは除外）
                if (status !== '' && status !== 'jy') {
                    skippedReadings++;
                    continue;
                }

                if (type === 'ja_on') {
                    // 音読み（カタカナ→ひらがな変換）
                    const hiragana = katakanaToHiragana(value);

                    if (!isHiragana(hiragana)) {
                        skippedReadings++;
                        continue;
                    }

                    results.push({
                        kanji,
                        is_onyomi: 1,
                        reading: hiragana,
                        okurigana: ''
                    });
                    readingCount++;
                    gradeStats[grade].onyomi++;

                } else if (type === 'ja_kun') {
                    // 訓読み（オクリガナ分離）
                    const parsed = parseKunyomi(value);

                    if (!isHiragana(parsed.reading)) {
                        skippedReadings++;
                        continue;
                    }
                    if (parsed.okurigana !== '' && !isHiragana(parsed.okurigana)) {
                        skippedReadings++;
                        continue;
                    }

                    results.push({
                        kanji,
                        is_onyomi: 0,
                        reading: parsed.reading,
                        okurigana: parsed.okurigana
                    });
                    readingCount++;
                    gradeStats[grade].kunyomi++;
                }
            }
        }
    }

    return { results, kanjiCount, readingCount, skippedReadings, gradeStats };
}

/**
 * メイン処理
 */
function main() {
    // 入力ファイル確認
    if (!fs.existsSync(INPUT_XML)) {
        console.log('エラー: KANJIDIC2ファイルが見つかりません。\n');
        console.log('ダウンロード手順:');
        console.log('1. http://www.edrdg.org/kanjidic/kanjidic2.xml.gz をダウンロード');
        console.log('2. gzファイルを解凍');
        console.log('3. 解凍したXMLを以下に配置:', INPUT_XML);
        process.exit(1);
    }

    console.log('KANJIDIC2を読み込み中...');

    // XMLを読み込み
    const xmlContent = fs.readFileSync(INPUT_XML, 'utf-8');

    console.log('読みデータを抽出中...');
    const { results, kanjiCount, readingCount, skippedReadings, gradeStats } = parseXML(xmlContent);

    // CSVファイル作成
    const csvLines = ['\uFEFFkanji,is_onyomi,reading,okurigana']; // UTF-8 BOM

    for (const row of results) {
        const line = [
            row.kanji,
            row.is_onyomi,
            row.reading,
            row.okurigana
        ].map(v => {
            // CSVエスケープ
            const str = String(v);
            if (str.includes(',') || str.includes('"') || str.includes('\n')) {
                return '"' + str.replace(/"/g, '""') + '"';
            }
            return str;
        }).join(',');
        csvLines.push(line);
    }

    fs.writeFileSync(OUTPUT_CSV, csvLines.join('\n'), 'utf-8');

    // 結果表示
    console.log('\n抽出完了!');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('  対象漢字:', kanjiCount + '字');
    console.log('  抽出した読み:', readingCount + '件');
    console.log('  スキップした読み:', skippedReadings + '件（稀な読み等）');
    console.log('  出力ファイル:', OUTPUT_CSV);
    console.log('\n学年別統計:');

    for (let g = 1; g <= 6; g++) {
        const stats = gradeStats[g];
        if (stats.kanji > 0) {
            const total = stats.onyomi + stats.kunyomi;
            console.log(`  ${g}年生: ${stats.kanji}字 / 音読み${stats.onyomi}件, 訓読み${stats.kunyomi}件 (計${total}件)`);
        }
    }

    console.log('\n次のステップ:');
    console.log('  node import_kanji_readings.js');
}

main();
