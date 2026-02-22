# Kanji Quiz - 漢字クイズアプリ

小学校1〜6年生向けの漢字読み練習Webアプリケーション。単元ごとに出題される漢字の読みを穴埋め形式で学習できる。

## 技術スタック

- **バックエンド**: PHP (PDO), SQLite3 (`db/kanji.db`)
- **フロントエンド**: HTML5, CSS3, Vanilla JavaScript (ES6) — フレームワーク不使用
- **フォント**: Zen Maru Gothic (Google Fonts)
- **データ処理スクリプト**: Node.js + better-sqlite3

## 開発サーバー起動

```bash
php -S localhost:8000
# http://localhost:8000/index.php (生徒用)
# http://localhost:8000/admin.php (管理画面)
```

## プロジェクト構成

```
index.php          # 生徒用クイズ画面
admin.php          # 管理画面（単元管理・問題管理・CSV入出力）
api.php            # 全APIエンドポイント（actionパラメータで振り分け）
init_db.php        # DB初期化・スキーマ定義
js/app.js          # フロントエンドJS（KanjiQuizApp / AdminApp クラス）
css/style.css      # 全スタイル定義（CSS変数によるデザインシステム）
db/kanji.db        # SQLiteデータベース
generate_questions.js      # 漢字読みから問題を自動生成
import_kanji_readings.js   # CSV→DB読みデータ取り込み
import_haitou_kanji.php    # 配当漢字データ取り込み
temp/              # データ取り込み用の元ファイル（CSV, XML等）
```

## DBスキーマ（5テーブル）

- **grades** — 学年マスタ（1〜6年生）
- **units** — 単元（grade_id, unit_number, unit_name）
- **questions** — 問題（unit_id, pre_text, kanji_text, kanji_reading, post_text, reading_id）
- **haitou_kanji** — 配当漢字（grade, kanji, unit_id）
- **kanji_readings** — 漢字の読み（kanji_id, is_onyomi, reading, okurigana）

## API設計

`api.php?action=<アクション名>` の単一エントリポイント方式。主なアクション：

- `get_grades`, `get_units`, `get_questions`, `get_question_count`
- `add_unit`, `update_unit`, `delete_unit`
- `add_question`, `update_question`, `delete_question`
- `import_csv`, `export_csv`
- `get_haitou_kanji`, `get_kanji_readings`, `search_kanji`

レスポンスはすべてJSON。SQLインジェクション対策としてPDOプリペアドステートメントを使用。

## コーディング規約・注意点

- フロントエンドJSはクラスベース（`KanjiQuizApp`, `AdminApp`）でグローバル変数 `app` に格納
- XSS対策として `div.textContent` によるHTMLエスケープを使用
- 問題表示は2モード: マス目（個別文字ボックス）と大かっこ（送り仮名付き）
- 認証機能なし（学校内LAN等での利用を想定）
- CSSカラーはCSS変数で管理（`--primary: #4a90d9`, `--accent: #ff7b54` 等）
