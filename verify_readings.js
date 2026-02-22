/**
 * kanji_readings テーブルの検証スクリプト
 */

const Database = require('better-sqlite3');
const path = require('path');

const DB_PATH = path.join(__dirname, 'db', 'kanji.db');
const db = new Database(DB_PATH);

console.log('=== kanji_readings テーブル検証 ===\n');

// 1. 総件数確認
const totalCount = db.prepare('SELECT COUNT(*) as cnt FROM kanji_readings').get().cnt;
console.log('1. 総読み数:', totalCount, '件');

// 2. 読み種別の内訳
const byType = db.prepare(`
    SELECT
        CASE WHEN is_onyomi = 1 THEN '音読み' ELSE '訓読み' END as type,
        COUNT(*) as cnt
    FROM kanji_readings
    GROUP BY is_onyomi
`).all();
console.log('\n2. 読み種別:');
for (const row of byType) {
    console.log('   ', row.type + ':', row.cnt, '件');
}

// 3. 学年別統計
const byGrade = db.prepare(`
    SELECT
        h.grade,
        COUNT(CASE WHEN r.is_onyomi = 1 THEN 1 END) as onyomi,
        COUNT(CASE WHEN r.is_onyomi = 0 THEN 1 END) as kunyomi,
        COUNT(*) as total
    FROM haitou_kanji h
    JOIN kanji_readings r ON h.kanji_id = r.kanji_id
    GROUP BY h.grade
    ORDER BY h.grade
`).all();
console.log('\n3. 学年別統計:');
for (const row of byGrade) {
    console.log(`    ${row.grade}年生: 音読み${row.onyomi}件, 訓読み${row.kunyomi}件 (計${row.total}件)`);
}

// 4. 読みのない漢字を確認
const noReadings = db.prepare(`
    SELECT h.kanji, h.grade
    FROM haitou_kanji h
    LEFT JOIN kanji_readings r ON h.kanji_id = r.kanji_id
    WHERE r.reading_id IS NULL
    ORDER BY h.grade, h.kanji_id
`).all();
console.log('\n4. 読みのない漢字:', noReadings.length, '字');
if (noReadings.length > 0 && noReadings.length <= 20) {
    console.log('   ', noReadings.map(r => r.kanji).join(''));
}

// 5. 読みが最も多い漢字TOP10
const topKanji = db.prepare(`
    SELECT h.kanji, h.grade, COUNT(r.reading_id) as reading_count
    FROM haitou_kanji h
    JOIN kanji_readings r ON h.kanji_id = r.kanji_id
    GROUP BY h.kanji_id
    ORDER BY reading_count DESC
    LIMIT 10
`).all();
console.log('\n5. 読みが最も多い漢字TOP10:');
for (const row of topKanji) {
    console.log(`    ${row.kanji} (${row.grade}年生): ${row.reading_count}読み`);
}

// 6. サンプル確認
const samples = ['食', '行', '生', '上', '下'];
console.log('\n6. サンプル漢字の読み:');
for (const kanji of samples) {
    const readings = db.prepare(`
        SELECT
            CASE WHEN r.is_onyomi = 1 THEN '音' ELSE '訓' END as type,
            r.reading,
            r.okurigana
        FROM haitou_kanji h
        JOIN kanji_readings r ON h.kanji_id = r.kanji_id
        WHERE h.kanji = ?
        ORDER BY r.is_onyomi DESC, r.reading
    `).all(kanji);

    console.log(`\n   「${kanji}」:`);
    for (const r of readings) {
        const oku = r.okurigana ? `(${r.okurigana})` : '';
        console.log(`      ${r.type}: ${r.reading}${oku}`);
    }
}

db.close();
console.log('\n=== 検証完了 ===');
