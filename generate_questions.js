/**
 * 漢字クイズ問題自動生成スクリプト
 * kanji_readingsテーブルの各レコードに対して1問ずつ問題を作成
 * 問題文は学年に応じた漢字使用制御を行う
 */

const Database = require('better-sqlite3');
const path = require('path');

const DB_PATH = path.join(__dirname, 'db', 'kanji.db');

// 学年別の単元名
const UNIT_NAMES = {
  1: 'かんじのよみ',
  2: 'かん字の読み',
  3: '漢字の読み',
  4: '漢字の読み',
  5: '漢字の読み',
  6: '漢字の読み'
};

// 問題文テンプレート（学年レベル別）
// 漢字は使わず、変換が必要な部分は最小限に
const TEMPLATES = {
  // 訓読み・送り仮名なし（名詞系）
  noun: {
    1: [
      { pre: '', post: 'がみえる。' },
      { pre: '', post: 'をみた。' },
      { pre: '', post: 'がある。' },
      { pre: 'きょうは', post: 'にいく。' },
      { pre: 'あの', post: 'はおおきい。' }
    ],
    2: [
      { pre: '', post: 'が見える。' },
      { pre: '大きな', post: 'がある。' },
      { pre: '今日は', post: 'に行く。' },
      { pre: 'あの', post: 'は大きい。' },
      { pre: '', post: 'の中に入る。' }
    ],
    3: [
      { pre: '遠くに', post: 'が見えた。' },
      { pre: '大きな', post: 'の上に立つ。' },
      { pre: '今日は友だちと', post: 'に行った。' },
      { pre: 'あの', post: 'はとても高い。' },
      { pre: '', post: 'の中で遊んだ。' }
    ],
    4: [
      { pre: '遠くの', post: 'がかすんで見える。' },
      { pre: '大きな', post: 'の前で写真をとった。' },
      { pre: '今日は家族と', post: 'に出かけた。' },
      { pre: 'あの', post: 'は昔から有名だ。' },
      { pre: '', post: 'の近くに住んでいる。' }
    ],
    5: [
      { pre: '遠くの', post: 'がかすんで見える。' },
      { pre: '立派な', post: 'の前に立った。' },
      { pre: '今日は家族と', post: 'をおとずれた。' },
      { pre: 'あの', post: 'は古くから有名だ。' },
      { pre: '', post: 'のまわりは自然がゆたかだ。' }
    ],
    6: [
      { pre: '遠くの', post: 'がかすんで見える。' },
      { pre: 'れきしある', post: 'の前に立った。' },
      { pre: '今日は家族で', post: 'をおとずれた。' },
      { pre: 'あの', post: 'は古くから有名である。' },
      { pre: '', post: 'のまわりは自然がゆたかだ。' }
    ]
  },
  // 訓読み・送り仮名あり（動詞系）
  verb: {
    1: [
      { pre: '', post: '。' },
      { pre: 'きょう', post: '。' },
      { pre: 'いま', post: '。' }
    ],
    2: [
      { pre: '今日', post: '。' },
      { pre: 'あした', post: '。' },
      { pre: 'ゆっくり', post: '。' }
    ],
    3: [
      { pre: '今日は早く', post: 'ことにした。' },
      { pre: '明日も', post: 'つもりだ。' },
      { pre: 'みんなで', post: 'ことになった。' }
    ],
    4: [
      { pre: '今日は早めに', post: 'ことにした。' },
      { pre: '明日も同じように', post: 'よていだ。' },
      { pre: 'みんなで力を合わせて', post: 'ことになった。' }
    ],
    5: [
      { pre: '今日はよていより早く', post: 'ことにした。' },
      { pre: '明日も同じように', post: 'よていである。' },
      { pre: 'ぜんいんできょうりょくして', post: 'ことが決まった。' }
    ],
    6: [
      { pre: '本日はよていより早く', post: 'ことに決定した。' },
      { pre: '明日も同じように', post: 'よていである。' },
      { pre: 'ぜんいんできょうりょくして', post: 'ことが決まった。' }
    ]
  },
  // 音読み
  onyomi: {
    1: [
      { pre: '', post: 'のじかんだ。' },
      { pre: '', post: 'がある。' },
      { pre: '', post: 'をならう。' }
    ],
    2: [
      { pre: '', post: 'のじかんだ。' },
      { pre: '', post: 'がある。' },
      { pre: '', post: 'をまなぶ。' }
    ],
    3: [
      { pre: '学校で', post: 'を習った。' },
      { pre: '今日の', post: 'は楽しかった。' },
      { pre: '', post: 'について話し合う。' }
    ],
    4: [
      { pre: '学校で', post: 'を学んだ。' },
      { pre: '今日の', post: 'の時間は楽しかった。' },
      { pre: '', post: 'について話し合いをする。' }
    ],
    5: [
      { pre: '学校で', post: 'について学んだ。' },
      { pre: '今日の', post: 'のじゅぎょうはおもしろかった。' },
      { pre: '', post: 'についてぎろんする。' }
    ],
    6: [
      { pre: '学校で', post: 'について学んだ。' },
      { pre: '今日の', post: 'のじゅぎょうはおもしろかった。' },
      { pre: '', post: 'についてぎろんを行う。' }
    ]
  }
};

/**
 * メイン処理
 */
function main() {
  const db = new Database(DB_PATH);

  try {
    console.log('=== 漢字クイズ問題生成 ===\n');

    // 1. 学年ごとの使用可能漢字マップを構築
    console.log('1. 学年ごとの使用可能漢字マップを構築中...');
    const kanjiByGrade = buildKanjiByGrade(db);
    const allowedKanjiByGrade = buildAllowedKanjiMap(kanjiByGrade);

    // 漢字→読みのマップ（変換用）- 熟語対応版
    const kanjiToReading = buildKanjiToReadingMap(db);

    // 2. 既存の問題を削除
    console.log('2. 既存の問題を削除中...');
    const deletedCount = db.prepare('DELETE FROM questions').run().changes;
    console.log(`   ${deletedCount}件削除しました。`);

    // 3. 各学年に「漢字の読み」単元を作成
    console.log('3. 単元を作成中...');
    const unitIds = createUnits(db);

    // 4. kanji_readingsから問題を生成
    console.log('4. 問題を生成中...');
    const readings = db.prepare(`
      SELECT r.reading_id, r.kanji_id, r.is_onyomi, r.reading, r.okurigana,
             h.kanji, h.grade
      FROM kanji_readings r
      JOIN haitou_kanji h ON r.kanji_id = h.kanji_id
      ORDER BY h.grade, h.kanji_id, r.is_onyomi DESC
    `).all();

    const insertStmt = db.prepare(`
      INSERT INTO questions (unit_id, pre_text, kanji_text, kanji_reading, post_text, reading_id)
      VALUES (?, ?, ?, ?, ?, ?)
    `);

    let insertedCount = 0;
    const stats = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 };
    let templateIndex = 0;

    const insertAll = db.transaction(() => {
      for (const r of readings) {
        const grade = r.grade;
        const unitId = unitIds[grade];
        const allowedKanji = allowedKanjiByGrade[grade];

        // 問題文を生成
        const question = generateQuestion(r, grade, allowedKanji, kanjiToReading, templateIndex);
        templateIndex++;

        insertStmt.run(
          unitId,
          question.preText,
          question.kanjiText,
          question.kanjiReading,
          question.postText,
          r.reading_id
        );

        insertedCount++;
        stats[grade]++;
      }
    });

    insertAll();

    // 5. 結果を表示
    console.log('\n=== 生成完了 ===');
    console.log(`総問題数: ${insertedCount}件\n`);
    console.log('学年別問題数:');
    for (let g = 1; g <= 6; g++) {
      console.log(`  ${g}年生: ${stats[g]}件`);
    }

    // サンプル表示
    console.log('\n=== サンプル問題 ===');
    for (let g = 1; g <= 6; g++) {
      console.log(`\n【${g}年生】`);
      const samples = db.prepare(`
        SELECT q.pre_text, q.kanji_text, q.kanji_reading, q.post_text
        FROM questions q
        JOIN units u ON q.unit_id = u.unit_id
        WHERE u.grade_id = ?
        LIMIT 5
      `).all(g);
      samples.forEach((s, i) => {
        const fullText = s.pre_text + '【' + s.kanji_text + '】' + s.post_text;
        console.log(`  ${i + 1}. ${fullText} → ${s.kanji_reading}`);
      });
    }

  } finally {
    db.close();
  }
}

/**
 * 学年ごとの漢字リストを構築
 */
function buildKanjiByGrade(db) {
  const result = { 1: [], 2: [], 3: [], 4: [], 5: [], 6: [] };
  const rows = db.prepare('SELECT kanji, grade FROM haitou_kanji ORDER BY grade').all();
  for (const row of rows) {
    result[row.grade].push(row.kanji);
  }
  return result;
}

/**
 * 各学年で使用可能な漢字マップを構築
 * 例: 3年生 → 1年生 + 2年生の漢字が使用可能
 */
function buildAllowedKanjiMap(kanjiByGrade) {
  const result = {};
  for (let grade = 1; grade <= 6; grade++) {
    const allowed = new Set();
    // その学年より前の学年の漢字を追加
    for (let g = 1; g < grade; g++) {
      for (const k of kanjiByGrade[g]) {
        allowed.add(k);
      }
    }
    result[grade] = allowed;
  }
  return result;
}

/**
 * 漢字→読み（ひらがな変換用）のマップを構築
 * 訓読みを優先、なければ音読み
 */
function buildKanjiToReadingMap(db) {
  const result = {};
  const rows = db.prepare(`
    SELECT h.kanji, r.reading, r.is_onyomi, r.okurigana
    FROM haitou_kanji h
    JOIN kanji_readings r ON h.kanji_id = r.kanji_id
    ORDER BY h.kanji, r.is_onyomi ASC
  `).all();

  for (const row of rows) {
    // 訓読み優先（is_onyomi=0が先に来る）
    if (!result[row.kanji]) {
      // 送り仮名部分の括弧を除去した読みを使用
      let reading = row.reading.replace(/\(.+\)$/, '');
      result[row.kanji] = reading;
    }
  }
  return result;
}

/**
 * 単元を作成（なければ）
 */
function createUnits(db) {
  const unitIds = {};

  for (let grade = 1; grade <= 6; grade++) {
    const unitName = UNIT_NAMES[grade];

    // 既存チェック
    let unit = db.prepare(`
      SELECT unit_id FROM units WHERE grade_id = ? AND unit_number = 99
    `).get(grade);

    if (!unit) {
      db.prepare(`
        INSERT INTO units (grade_id, unit_number, unit_name)
        VALUES (?, 99, ?)
      `).run(grade, unitName);

      unit = db.prepare(`
        SELECT unit_id FROM units WHERE grade_id = ? AND unit_number = 99
      `).get(grade);

      console.log(`   ${grade}年生: 「${unitName}」を作成しました。`);
    } else {
      console.log(`   ${grade}年生: 「${unitName}」は既に存在します。`);
    }

    unitIds[grade] = unit.unit_id;
  }

  return unitIds;
}

/**
 * 問題文を生成
 */
function generateQuestion(reading, grade, allowedKanji, kanjiToReading, index) {
  const kanji = reading.kanji;
  const isOnyomi = reading.is_onyomi === 1;
  const hasOkurigana = reading.okurigana && reading.okurigana.length > 0;

  // テンプレートタイプを決定
  let templateType;
  if (isOnyomi) {
    templateType = 'onyomi';
  } else if (hasOkurigana) {
    templateType = 'verb';
  } else {
    templateType = 'noun';
  }

  // テンプレートを選択
  const templates = TEMPLATES[templateType][grade];
  const template = templates[index % templates.length];

  // 読み仮名を構築（送り仮名の括弧を除去）
  let kanjiReading = reading.reading.replace(/\((.+)\)$/, '');

  // kanji_textとpost_textを構築
  let kanjiText = kanji;
  let postText = template.post;

  if (hasOkurigana) {
    // 送り仮名がある場合、送り仮名をpostTextの先頭に追加
    postText = reading.okurigana + template.post;
  }

  // pre_textとpost_textの漢字を変換
  let preText = convertText(template.pre, allowedKanji, kanjiToReading);
  postText = convertText(postText, allowedKanji, kanjiToReading);

  return {
    preText,
    kanjiText,
    kanjiReading,
    postText
  };
}

/**
 * テキスト内の漢字を変換
 * 許可された漢字以外はひらがなに変換
 */
function convertText(text, allowedKanji, kanjiToReading) {
  let result = '';
  for (const char of text) {
    // 漢字かどうかチェック
    if (/[\u4e00-\u9faf]/.test(char)) {
      if (allowedKanji.has(char)) {
        // 許可された漢字はそのまま
        result += char;
      } else {
        // 許可されていない漢字はひらがなに変換
        const reading = kanjiToReading[char];
        if (reading) {
          result += reading;
        } else {
          // 読みが見つからない場合はそのまま（稀なケース）
          result += char;
        }
      }
    } else {
      result += char;
    }
  }
  return result;
}

main();
