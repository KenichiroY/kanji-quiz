/**
 * 読み仮名インポートスクリプト (Node.js版)
 * temp/kanji_readings.csv から読み仮名データをDBにインポートします
 *
 * 前提条件:
 * - init_db.php が実行済み
 * - import_haitou_kanji.php が実行済み（haitou_kanjiテーブルにデータあり）
 * - extract_kanji_readings.js が実行済み（CSVファイルあり）
 */

const fs = require('fs');
const path = require('path');
const Database = require('better-sqlite3');

const DB_PATH = path.join(__dirname, 'db', 'kanji.db');
const CSV_PATH = path.join(__dirname, 'temp', 'kanji_readings.csv');

/**
 * CSVをパース
 */
function parseCSV(content) {
    const lines = content.split('\n').filter(line => line.trim());
    const results = [];

    // BOMを除去
    if (lines[0].charCodeAt(0) === 0xFEFF) {
        lines[0] = lines[0].substring(1);
    }

    // ヘッダーをスキップ
    for (let i = 1; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) continue;

        // 簡易CSVパース
        const parts = line.split(',');
        if (parts.length >= 3) {
            results.push({
                kanji: parts[0],
                is_onyomi: parseInt(parts[1], 10),
                reading: parts[2],
                okurigana: parts[3] || null,
                lineNum: i + 1
            });
        }
    }

    return results;
}

/**
 * ひらがなチェック
 */
function isHiragana(str) {
    return /^[ぁ-んー]+$/.test(str);
}

/**
 * メイン処理
 */
function main() {
    // 前提条件チェック
    if (!fs.existsSync(DB_PATH)) {
        console.log('エラー: データベースが存在しません。');
        console.log('先に init_db.php を実行してください。');
        process.exit(1);
    }

    if (!fs.existsSync(CSV_PATH)) {
        console.log('エラー: CSVファイルが存在しません:', CSV_PATH);
        console.log('先に node extract_kanji_readings.js を実行してください。');
        process.exit(1);
    }

    const db = new Database(DB_PATH);

    try {
        // 配当漢字の存在確認
        const haitouCount = db.prepare('SELECT COUNT(*) as cnt FROM haitou_kanji').get().cnt;
        if (haitouCount === 0) {
            console.log('エラー: 配当漢字データがありません。');
            console.log('先に import_haitou_kanji.php を実行してください。');
            process.exit(1);
        }
        console.log('配当漢字:', haitouCount + '字を確認しました。');

        // 既存の読み仮名データを確認
        const existingCount = db.prepare('SELECT COUNT(*) as cnt FROM kanji_readings').get().cnt;
        if (existingCount > 0) {
            console.log('既に', existingCount, '件の読み仮名データが登録されています。');
            console.log('再インポートする場合は、先にデータを削除してください。');
            console.log('削除コマンド: DELETE FROM kanji_readings;');
            process.exit(0);
        }

        // 漢字→kanji_id のマッピングを作成
        const kanjiMap = {};
        const gradeMap = {};
        const kanjiRows = db.prepare('SELECT kanji_id, kanji, grade FROM haitou_kanji').all();
        for (const row of kanjiRows) {
            kanjiMap[row.kanji] = row.kanji_id;
            gradeMap[row.kanji_id] = row.grade;
        }

        // CSVファイル読み込み
        console.log('CSVを読み込み中...');
        const csvContent = fs.readFileSync(CSV_PATH, 'utf-8');
        const rows = parseCSV(csvContent);
        console.log('CSVから', rows.length, '行を読み込みました。');

        // データ挿入準備
        const insertStmt = db.prepare(`
            INSERT INTO kanji_readings (kanji_id, is_onyomi, reading, okurigana)
            VALUES (?, ?, ?, ?)
        `);

        let importedCount = 0;
        let skippedCount = 0;
        const errors = [];
        const gradeStats = {};
        for (let i = 1; i <= 6; i++) {
            gradeStats[i] = { onyomi: 0, kunyomi: 0 };
        }

        // トランザクション開始
        const insertMany = db.transaction((rows) => {
            for (const row of rows) {
                const kanji = row.kanji.trim();
                const isOnyomi = row.is_onyomi;
                const reading = row.reading.trim();
                let okurigana = row.okurigana ? row.okurigana.trim() : null;
                if (okurigana === '') okurigana = null;

                // バリデーション
                if (!(kanji in kanjiMap)) {
                    errors.push(`${row.lineNum}行目: 未登録の漢字「${kanji}」`);
                    skippedCount++;
                    continue;
                }

                if (![0, 1].includes(isOnyomi)) {
                    errors.push(`${row.lineNum}行目: is_onyomiが不正(${isOnyomi})`);
                    skippedCount++;
                    continue;
                }

                if (!reading) {
                    errors.push(`${row.lineNum}行目: 読み仮名が空`);
                    skippedCount++;
                    continue;
                }

                if (!isHiragana(reading)) {
                    errors.push(`${row.lineNum}行目: 読み仮名がひらがなではありません「${reading}」`);
                    skippedCount++;
                    continue;
                }

                if (okurigana !== null && !isHiragana(okurigana)) {
                    errors.push(`${row.lineNum}行目: オクリガナがひらがなではありません「${okurigana}」`);
                    skippedCount++;
                    continue;
                }

                const kanjiId = kanjiMap[kanji];
                insertStmt.run(kanjiId, isOnyomi, reading, okurigana);
                importedCount++;

                // 学年別統計
                const grade = gradeMap[kanjiId];
                if (isOnyomi === 1) {
                    gradeStats[grade].onyomi++;
                } else {
                    gradeStats[grade].kunyomi++;
                }
            }
        });

        insertMany(rows);

        // 結果表示
        console.log('\nインポート完了!');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('  成功:', importedCount + '件');
        console.log('  スキップ:', skippedCount + '件');

        console.log('\n学年別読み仮名数:');
        for (let grade = 1; grade <= 6; grade++) {
            const stats = gradeStats[grade];
            const total = stats.onyomi + stats.kunyomi;
            if (total > 0) {
                console.log(`  ${grade}年生: 音読み${stats.onyomi}件, 訓読み${stats.kunyomi}件 (計${total}件)`);
            }
        }

        // 読みのない漢字を確認
        const noReadings = db.prepare(`
            SELECT h.kanji, h.grade
            FROM haitou_kanji h
            LEFT JOIN kanji_readings r ON h.kanji_id = r.kanji_id
            WHERE r.reading_id IS NULL
            ORDER BY h.grade, h.kanji_id
        `).all();

        if (noReadings.length > 0) {
            console.log('\n警告: 読みのない漢字が', noReadings.length, '字あります:');
            const byGrade = {};
            for (const row of noReadings) {
                if (!(row.grade in byGrade)) byGrade[row.grade] = [];
                byGrade[row.grade].push(row.kanji);
            }
            for (const grade in byGrade) {
                console.log(`  ${grade}年生:`, byGrade[grade].join(''));
            }
        }

        // エラー詳細（最初の10件のみ表示）
        if (errors.length > 0) {
            console.log('\nエラー詳細（最初の10件）:');
            for (const error of errors.slice(0, 10)) {
                console.log('  -', error);
            }
            if (errors.length > 10) {
                console.log('  ... 他', errors.length - 10, '件');
            }
        }

    } finally {
        db.close();
    }
}

main();
