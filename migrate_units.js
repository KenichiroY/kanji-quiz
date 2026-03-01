/**
 * 単元マイグレーション: 教科書単元 → 20字ずつの「第N回」に再編成
 * 使い方: node migrate_units.js
 */

const Database = require('better-sqlite3');
const path = require('path');

const db = new Database(path.join(__dirname, 'db', 'kanji.db'));
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = OFF'); // 一時的にFK制約を無効化

const GROUP_SIZE = 20;
const MIN_LAST_GROUP = 5;

const migrate = db.transaction(() => {
    // === Step 1: 既存の全単元を削除 ===
    db.prepare("DELETE FROM units").run();
    db.prepare("DELETE FROM sqlite_sequence WHERE name='units'").run();
    console.log("既存単元を削除しました。");

    // === Step 2: 学年ごとに新単元を作成 ===
    const grades = db.prepare("SELECT grade_id FROM grades ORDER BY grade_id").all();

    const insertUnit = db.prepare("INSERT INTO units (grade_id, unit_number, unit_name) VALUES (?, ?, ?)");
    const updateKanji = db.prepare("UPDATE haitou_kanji SET unit_id = ? WHERE kanji_id = ?");

    for (const { grade_id } of grades) {
        const allKanji = db.prepare("SELECT kanji_id, kanji FROM haitou_kanji WHERE grade = ? ORDER BY kanji_id").all(grade_id);
        const total = allKanji.length;

        if (total === 0) {
            console.log(`${grade_id}年生: 配当漢字なし、スキップ`);
            continue;
        }

        // グループ分け
        const groups = [];
        for (let i = 0; i < allKanji.length; i += GROUP_SIZE) {
            groups.push(allKanji.slice(i, i + GROUP_SIZE));
        }

        // 最終グループが小さすぎる場合、前のグループに統合
        if (groups.length > 1 && groups[groups.length - 1].length <= MIN_LAST_GROUP) {
            const lastGroup = groups.pop();
            groups[groups.length - 1] = groups[groups.length - 1].concat(lastGroup);
        }

        console.log(`${grade_id}年生: ${total}字 → ${groups.length}単元`);

        for (let i = 0; i < groups.length; i++) {
            const unitNumber = i + 1;
            const unitName = `第${unitNumber}回`;
            const group = groups[i];

            // 単元を作成
            const result = insertUnit.run(grade_id, unitNumber, unitName);
            const unitId = result.lastInsertRowid;

            // 配当漢字にunit_idを設定
            for (const kanji of group) {
                updateKanji.run(unitId, kanji.kanji_id);
            }

            const kanjiStr = group.map(k => k.kanji).join('');
            console.log(`  第${unitNumber}回 (unit_id=${unitId}): ${group.length}字 [${kanjiStr}]`);
        }
    }

    // === Step 3: 問題のunit_idを再設定 ===
    // reading_idがある問題
    const updated = db.prepare(`
        UPDATE questions SET unit_id = (
            SELECT hk.unit_id
            FROM kanji_readings kr
            JOIN haitou_kanji hk ON kr.kanji_id = hk.kanji_id
            WHERE kr.reading_id = questions.reading_id
        )
        WHERE reading_id IS NOT NULL
    `).run();
    console.log(`\nreading_id経由で ${updated.changes} 問のunit_idを更新しました。`);

    // reading_idがNULLの問題
    const nullReadings = db.prepare("SELECT question_id, kanji_text FROM questions WHERE reading_id IS NULL").all();

    if (nullReadings.length > 0) {
        console.log(`\nreading_idがNULLの問題を処理中...`);
        const findUnit = db.prepare("SELECT unit_id FROM haitou_kanji WHERE kanji = ? LIMIT 1");
        const updateQ = db.prepare("UPDATE questions SET unit_id = ? WHERE question_id = ?");

        for (const q of nullReadings) {
            const chars = [...q.kanji_text];
            let matched = false;
            for (const char of chars) {
                const result = findUnit.get(char);
                if (result && result.unit_id) {
                    updateQ.run(result.unit_id, q.question_id);
                    console.log(`  question_id=${q.question_id} (kanji_text='${q.kanji_text}') → unit_id=${result.unit_id}`);
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                console.log(`  [警告] question_id=${q.question_id} (kanji_text='${q.kanji_text}') の単元を特定できませんでした。`);
            }
        }
    }

    // === Step 4: 検証 ===
    console.log("\n=== 検証 ===");

    const verify = db.prepare(`
        SELECT g.grade_name, u.unit_name, COUNT(q.question_id) as q_count
        FROM units u
        JOIN grades g ON u.grade_id = g.grade_id
        LEFT JOIN questions q ON q.unit_id = u.unit_id
        GROUP BY u.unit_id
        ORDER BY g.grade_id, u.unit_number
    `).all();

    let currentGrade = '';
    for (const row of verify) {
        if (row.grade_name !== currentGrade) {
            currentGrade = row.grade_name;
            console.log(`\n${currentGrade}:`);
        }
        console.log(`  ${row.unit_name}: ${row.q_count}問`);
    }

    const orphans = db.prepare("SELECT COUNT(*) as cnt FROM questions WHERE unit_id NOT IN (SELECT unit_id FROM units)").get();
    console.log(`\n孤立問題（存在しない単元を参照）: ${orphans.cnt}件`);
});

try {
    migrate();
    console.log("\nマイグレーション完了！");
} catch (e) {
    console.error("エラー:", e.message);
    process.exit(1);
} finally {
    db.close();
}
