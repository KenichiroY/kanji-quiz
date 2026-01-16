<?php
/**
 * データベース初期化スクリプト
 * 初回実行時にテーブルを作成し、サンプルデータを挿入します
 */

$dbPath = __DIR__ . '/db/kanji.db';
$isNewDb = !file_exists($dbPath);

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($isNewDb) {
        // テーブル作成
        $pdo->exec("
            -- 学年マスタ
            CREATE TABLE IF NOT EXISTS grades (
                grade_id INTEGER PRIMARY KEY,
                grade_name TEXT NOT NULL
            );
            
            -- 単元マスタ
            CREATE TABLE IF NOT EXISTS units (
                unit_id INTEGER PRIMARY KEY AUTOINCREMENT,
                grade_id INTEGER NOT NULL,
                unit_number INTEGER NOT NULL,
                unit_name TEXT NOT NULL,
                FOREIGN KEY (grade_id) REFERENCES grades(grade_id)
            );
            
            -- 問題テーブル
            CREATE TABLE IF NOT EXISTS questions (
                question_id INTEGER PRIMARY KEY AUTOINCREMENT,
                unit_id INTEGER NOT NULL,
                pre_text TEXT DEFAULT '',
                kanji_text TEXT NOT NULL,
                kanji_reading TEXT NOT NULL,
                post_text TEXT DEFAULT '',
                reading_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (unit_id) REFERENCES units(unit_id),
                FOREIGN KEY (reading_id) REFERENCES kanji_readings(reading_id)
            );
            
            -- インデックス
            CREATE INDEX IF NOT EXISTS idx_questions_unit ON questions(unit_id);
            CREATE INDEX IF NOT EXISTS idx_units_grade ON units(grade_id);

            -- 配当漢字テーブル
            CREATE TABLE IF NOT EXISTS haitou_kanji (
                kanji_id INTEGER PRIMARY KEY AUTOINCREMENT,
                grade INTEGER NOT NULL,
                kanji TEXT NOT NULL UNIQUE,
                unit_id INTEGER,
                FOREIGN KEY (unit_id) REFERENCES units(unit_id)
            );

            -- 読み仮名テーブル
            CREATE TABLE IF NOT EXISTS kanji_readings (
                reading_id INTEGER PRIMARY KEY AUTOINCREMENT,
                kanji_id INTEGER NOT NULL,
                is_onyomi INTEGER NOT NULL,
                reading TEXT NOT NULL,
                okurigana TEXT,
                FOREIGN KEY (kanji_id) REFERENCES haitou_kanji(kanji_id)
            );

            -- 配当漢字用インデックス
            CREATE INDEX IF NOT EXISTS idx_haitou_kanji_grade ON haitou_kanji(grade);
            CREATE INDEX IF NOT EXISTS idx_kanji_readings_kanji ON kanji_readings(kanji_id);
        ");
        
        // 学年マスタデータ挿入
        $grades = [
            [1, '1年生'], [2, '2年生'], [3, '3年生'],
            [4, '4年生'], [5, '5年生'], [6, '6年生']
        ];
        $stmt = $pdo->prepare("INSERT INTO grades (grade_id, grade_name) VALUES (?, ?)");
        foreach ($grades as $grade) {
            $stmt->execute($grade);
        }
        
        // サンプル単元データ挿入
        $units = [
            [1, 1, 'かん字のはなし'],
            [1, 2, 'かたかなをかこう'],
            [2, 1, 'じゅんばんにならぼう'],
            [2, 2, 'たんぽぽのちえ'],
            [3, 1, 'きつつきの商売'],
            [3, 2, '国語辞典を使おう'],
        ];
        $stmt = $pdo->prepare("INSERT INTO units (grade_id, unit_number, unit_name) VALUES (?, ?, ?)");
        foreach ($units as $unit) {
            $stmt->execute($unit);
        }
        
        // サンプル問題データ挿入
        $questions = [
            // 1年生 単元1
            [1, '', '山', 'やま', 'にのぼる。'],
            [1, '', '川', 'かわ', 'であそぶ。'],
            [1, 'あの', '木', 'き', 'はたかい。'],
            [1, '', '日', 'ひ', 'がのぼる。'],
            [1, '', '月', 'つき', 'がきれいだ。'],
            // 1年生 単元2
            [2, '', '犬', 'いぬ', 'とさんぽする。'],
            [2, '', '虫', 'むし', 'をみつけた。'],
            // 2年生 単元1
            [3, '', '読', 'よ', 'む本をえらぶ。'],
            [3, '計算を', '書', 'か', 'く。'],
            [3, '', '話し合', 'はなしあ', 'う。'],
            // 2年生 単元2
            [4, '', '春', 'はる', 'がきた。'],
            [4, '', '夏休', 'なつやす', 'みがたのしみだ。'],
            // 3年生 単元1
            [5, '', '商売', 'しょうばい', 'をする。'],
            [5, '本を', '持', 'も', 'ってくる。'],
            [5, '', '開', 'ひら', 'く。'],
            // 3年生 単元2
            [6, '', '国語', 'こくご', 'の時間。'],
            [6, '', '辞典', 'じてん', 'で調べる。'],
        ];
        $stmt = $pdo->prepare("INSERT INTO questions (unit_id, pre_text, kanji_text, kanji_reading, post_text) VALUES (?, ?, ?, ?, ?)");
        foreach ($questions as $q) {
            $stmt->execute($q);
        }
        
        echo "データベースを初期化しました。\n";
    } else {
        // 既存DBに新テーブルを追加（存在しない場合のみ）
        $pdo->exec("
            -- 配当漢字テーブル
            CREATE TABLE IF NOT EXISTS haitou_kanji (
                kanji_id INTEGER PRIMARY KEY AUTOINCREMENT,
                grade INTEGER NOT NULL,
                kanji TEXT NOT NULL UNIQUE,
                unit_id INTEGER,
                FOREIGN KEY (unit_id) REFERENCES units(unit_id)
            );

            -- 読み仮名テーブル
            CREATE TABLE IF NOT EXISTS kanji_readings (
                reading_id INTEGER PRIMARY KEY AUTOINCREMENT,
                kanji_id INTEGER NOT NULL,
                is_onyomi INTEGER NOT NULL,
                reading TEXT NOT NULL,
                okurigana TEXT,
                FOREIGN KEY (kanji_id) REFERENCES haitou_kanji(kanji_id)
            );

            -- 配当漢字用インデックス
            CREATE INDEX IF NOT EXISTS idx_haitou_kanji_grade ON haitou_kanji(grade);
            CREATE INDEX IF NOT EXISTS idx_kanji_readings_kanji ON kanji_readings(kanji_id);
        ");
        echo "データベースは既に存在します。新テーブルを確認しました。\n";
    }
    
} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}
