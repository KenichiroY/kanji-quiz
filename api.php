<?php
/**
 * API エンドポイント
 * 学年・単元・問題の取得、管理機能を提供
 */

header('Content-Type: application/json; charset=utf-8');

$dbPath = __DIR__ . '/db/kanji.db';

// データベースが存在しない場合は初期化
if (!file_exists($dbPath)) {
    require_once __DIR__ . '/init_db.php';
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'データベース接続エラー']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // ========== 取得系 ==========
    
    case 'get_grades':
        // 全学年を取得
        $stmt = $pdo->query("SELECT * FROM grades ORDER BY grade_id");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'get_units':
        // 指定学年の単元を取得
        $gradeIds = $_GET['grade_ids'] ?? '';
        if (empty($gradeIds)) {
            echo json_encode([]);
            break;
        }
        $ids = array_map('intval', explode(',', $gradeIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT u.*, g.grade_name 
            FROM units u 
            JOIN grades g ON u.grade_id = g.grade_id 
            WHERE u.grade_id IN ($placeholders) 
            ORDER BY u.grade_id, u.unit_number
        ");
        $stmt->execute($ids);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'get_questions':
        // 問題を取得（ランダム・非復元抽出）
        $unitIds = $_GET['unit_ids'] ?? '';
        $limit = intval($_GET['limit'] ?? 10);
        
        if (empty($unitIds)) {
            echo json_encode([]);
            break;
        }
        
        $ids = array_map('intval', explode(',', $unitIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // limit=0 は「全部」を意味する
        $limitClause = $limit > 0 ? "LIMIT $limit" : "";
        
        $stmt = $pdo->prepare("
            SELECT q.*, u.unit_name, u.unit_number, g.grade_name, g.grade_id,
                   r.is_onyomi, r.okurigana, h.kanji as haitou_kanji, h.grade as haitou_grade
            FROM questions q
            JOIN units u ON q.unit_id = u.unit_id
            JOIN grades g ON u.grade_id = g.grade_id
            LEFT JOIN kanji_readings r ON q.reading_id = r.reading_id
            LEFT JOIN haitou_kanji h ON r.kanji_id = h.kanji_id
            WHERE q.unit_id IN ($placeholders)
            ORDER BY RANDOM()
            $limitClause
        ");
        $stmt->execute($ids);
        $questions = $stmt->fetchAll();
        
        // 表示形式を判定（ひらがなが含まれていれば大かっこ）
        foreach ($questions as &$q) {
            $q['display_type'] = preg_match('/[ぁ-ん]/u', $q['kanji_text']) ? 'bracket' : 'box';
        }
        
        echo json_encode($questions);
        break;
        
    case 'get_question_count':
        // 指定単元の問題数を取得
        $unitIds = $_GET['unit_ids'] ?? '';
        if (empty($unitIds)) {
            echo json_encode(['count' => 0]);
            break;
        }
        $ids = array_map('intval', explode(',', $unitIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE unit_id IN ($placeholders)");
        $stmt->execute($ids);
        echo json_encode($stmt->fetch());
        break;
    
    // ========== 管理系 ==========
    
    case 'get_all_units':
        // 全単元を取得（管理画面用）
        $stmt = $pdo->query("
            SELECT u.*, g.grade_name,
                   (SELECT COUNT(*) FROM questions q WHERE q.unit_id = u.unit_id) as question_count
            FROM units u 
            JOIN grades g ON u.grade_id = g.grade_id 
            ORDER BY u.grade_id, u.unit_number
        ");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'get_questions_by_unit':
        // 単元ごとの問題一覧（管理画面用）
        $unitId = intval($_GET['unit_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT * FROM questions WHERE unit_id = ? ORDER BY question_id
        ");
        $stmt->execute([$unitId]);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'add_unit':
        // 単元追加
        $gradeId = intval($_POST['grade_id'] ?? 0);
        $unitNumber = intval($_POST['unit_number'] ?? 0);
        $unitName = trim($_POST['unit_name'] ?? '');
        
        if ($gradeId < 1 || $unitNumber < 1 || empty($unitName)) {
            http_response_code(400);
            echo json_encode(['error' => '入力内容を確認してください']);
            break;
        }
        
        $stmt = $pdo->prepare("INSERT INTO units (grade_id, unit_number, unit_name) VALUES (?, ?, ?)");
        $stmt->execute([$gradeId, $unitNumber, $unitName]);
        echo json_encode(['success' => true, 'unit_id' => $pdo->lastInsertId()]);
        break;
        
    case 'update_unit':
        // 単元更新
        $unitId = intval($_POST['unit_id'] ?? 0);
        $gradeId = intval($_POST['grade_id'] ?? 0);
        $unitNumber = intval($_POST['unit_number'] ?? 0);
        $unitName = trim($_POST['unit_name'] ?? '');
        
        if ($unitId < 1 || $gradeId < 1 || $unitNumber < 1 || empty($unitName)) {
            http_response_code(400);
            echo json_encode(['error' => '入力内容を確認してください']);
            break;
        }
        
        $stmt = $pdo->prepare("UPDATE units SET grade_id = ?, unit_number = ?, unit_name = ? WHERE unit_id = ?");
        $stmt->execute([$gradeId, $unitNumber, $unitName, $unitId]);
        echo json_encode(['success' => true]);
        break;
        
    case 'delete_unit':
        // 単元削除（関連する問題も削除）
        $unitId = intval($_POST['unit_id'] ?? 0);
        if ($unitId < 1) {
            http_response_code(400);
            echo json_encode(['error' => '単元IDが不正です']);
            break;
        }
        
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM questions WHERE unit_id = ?")->execute([$unitId]);
            $pdo->prepare("DELETE FROM units WHERE unit_id = ?")->execute([$unitId]);
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => '削除に失敗しました']);
        }
        break;
        
    case 'add_question':
        // 問題追加
        $unitId = intval($_POST['unit_id'] ?? 0);
        $preText = $_POST['pre_text'] ?? '';
        $kanjiText = trim($_POST['kanji_text'] ?? '');
        $kanjiReading = trim($_POST['kanji_reading'] ?? '');
        $postText = $_POST['post_text'] ?? '';
        $readingId = isset($_POST['reading_id']) ? (intval($_POST['reading_id']) ?: null) : null;

        if ($unitId < 1 || empty($kanjiText) || empty($kanjiReading)) {
            http_response_code(400);
            echo json_encode(['error' => '問題漢字とふりがなは必須です']);
            break;
        }

        $stmt = $pdo->prepare("
            INSERT INTO questions (unit_id, pre_text, kanji_text, kanji_reading, post_text, reading_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$unitId, $preText, $kanjiText, $kanjiReading, $postText, $readingId]);
        echo json_encode(['success' => true, 'question_id' => $pdo->lastInsertId()]);
        break;
        
    case 'update_question':
        // 問題更新
        $questionId = intval($_POST['question_id'] ?? 0);
        $unitId = intval($_POST['unit_id'] ?? 0);
        $preText = $_POST['pre_text'] ?? '';
        $kanjiText = trim($_POST['kanji_text'] ?? '');
        $kanjiReading = trim($_POST['kanji_reading'] ?? '');
        $postText = $_POST['post_text'] ?? '';
        $readingId = isset($_POST['reading_id']) ? (intval($_POST['reading_id']) ?: null) : null;

        if ($questionId < 1 || $unitId < 1 || empty($kanjiText) || empty($kanjiReading)) {
            http_response_code(400);
            echo json_encode(['error' => '入力内容を確認してください']);
            break;
        }

        $stmt = $pdo->prepare("
            UPDATE questions
            SET unit_id = ?, pre_text = ?, kanji_text = ?, kanji_reading = ?, post_text = ?, reading_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE question_id = ?
        ");
        $stmt->execute([$unitId, $preText, $kanjiText, $kanjiReading, $postText, $readingId, $questionId]);
        echo json_encode(['success' => true]);
        break;
        
    case 'delete_question':
        // 問題削除
        $questionId = intval($_POST['question_id'] ?? 0);
        if ($questionId < 1) {
            http_response_code(400);
            echo json_encode(['error' => '問題IDが不正です']);
            break;
        }
        
        $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
        $stmt->execute([$questionId]);
        echo json_encode(['success' => true]);
        break;
        
    case 'import_csv':
        // CSVインポート
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'ファイルのアップロードに失敗しました']);
            break;
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $importType = $_POST['import_type'] ?? 'questions';
        
        $handle = fopen($file, 'r');
        if (!$handle) {
            http_response_code(500);
            echo json_encode(['error' => 'ファイルを開けませんでした']);
            break;
        }
        
        // BOMスキップ
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        $header = fgetcsv($handle);
        $imported = 0;
        $errors = [];
        
        $pdo->beginTransaction();
        try {
            if ($importType === 'units') {
                // 単元インポート: grade_id, unit_number, unit_name
                $stmt = $pdo->prepare("INSERT INTO units (grade_id, unit_number, unit_name) VALUES (?, ?, ?)");
                $lineNum = 1;
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNum++;
                    if (count($row) < 3) {
                        $errors[] = "{$lineNum}行目: 列数が不足しています";
                        continue;
                    }
                    $stmt->execute([intval($row[0]), intval($row[1]), trim($row[2])]);
                    $imported++;
                }
            } else {
                // 問題インポート: unit_id, pre_text, kanji_text, kanji_reading, post_text
                $stmt = $pdo->prepare("
                    INSERT INTO questions (unit_id, pre_text, kanji_text, kanji_reading, post_text) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $lineNum = 1;
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNum++;
                    if (count($row) < 5) {
                        $errors[] = "{$lineNum}行目: 列数が不足しています";
                        continue;
                    }
                    if (empty(trim($row[2])) || empty(trim($row[3]))) {
                        $errors[] = "{$lineNum}行目: 問題漢字またはふりがなが空です";
                        continue;
                    }
                    $stmt->execute([intval($row[0]), $row[1], trim($row[2]), trim($row[3]), $row[4]]);
                    $imported++;
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'インポート中にエラーが発生しました: ' . $e->getMessage()]);
            fclose($handle);
            break;
        }
        
        fclose($handle);
        echo json_encode([
            'success' => true, 
            'imported' => $imported,
            'errors' => $errors
        ]);
        break;
        
    // ========== 配当漢字・読み仮名系 ==========

    case 'get_haitou_kanji':
        // 配当漢字一覧を取得（学年または単元指定可）
        $grade = isset($_GET['grade']) ? intval($_GET['grade']) : 0;
        $unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
        if ($unitId > 0) {
            $stmt = $pdo->prepare("
                SELECT h.*, u.unit_name, u.unit_number
                FROM haitou_kanji h
                LEFT JOIN units u ON h.unit_id = u.unit_id
                WHERE h.unit_id = ?
                ORDER BY h.kanji_id
            ");
            $stmt->execute([$unitId]);
        } elseif ($grade > 0) {
            $stmt = $pdo->prepare("
                SELECT h.*, u.unit_name, u.unit_number
                FROM haitou_kanji h
                LEFT JOIN units u ON h.unit_id = u.unit_id
                WHERE h.grade = ?
                ORDER BY h.kanji_id
            ");
            $stmt->execute([$grade]);
        } else {
            $stmt = $pdo->query("
                SELECT h.*, u.unit_name, u.unit_number
                FROM haitou_kanji h
                LEFT JOIN units u ON h.unit_id = u.unit_id
                ORDER BY h.grade, h.kanji_id
            ");
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'update_kanji_unit':
        // 漢字の初出単元を設定
        $kanjiId = intval($_POST['kanji_id'] ?? 0);
        $unitId = isset($_POST['unit_id']) ? (intval($_POST['unit_id']) ?: null) : null;

        if ($kanjiId < 1) {
            http_response_code(400);
            echo json_encode(['error' => '漢字IDが不正です']);
            break;
        }

        $stmt = $pdo->prepare("UPDATE haitou_kanji SET unit_id = ? WHERE kanji_id = ?");
        $stmt->execute([$unitId, $kanjiId]);
        echo json_encode(['success' => true]);
        break;

    case 'bulk_update_kanji_unit':
        // 複数漢字の初出単元を一括設定
        $kanjiIds = $_POST['kanji_ids'] ?? '';
        $unitId = isset($_POST['unit_id']) ? (intval($_POST['unit_id']) ?: null) : null;

        if (empty($kanjiIds)) {
            http_response_code(400);
            echo json_encode(['error' => '漢字IDが指定されていません']);
            break;
        }

        $ids = array_map('intval', explode(',', $kanjiIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE haitou_kanji SET unit_id = ? WHERE kanji_id IN ($placeholders)");
        $stmt->execute(array_merge([$unitId], $ids));
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
        break;

    case 'get_kanji_readings':
        // 指定漢字の読み仮名を取得
        $kanjiId = intval($_GET['kanji_id'] ?? 0);
        if ($kanjiId < 1) {
            echo json_encode([]);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT r.*, h.kanji, h.grade
            FROM kanji_readings r
            JOIN haitou_kanji h ON r.kanji_id = h.kanji_id
            WHERE r.kanji_id = ?
            ORDER BY r.is_onyomi DESC, r.reading
        ");
        $stmt->execute([$kanjiId]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'search_kanji':
        // 漢字で検索
        $kanji = trim($_GET['kanji'] ?? '');
        if (empty($kanji)) {
            echo json_encode(null);
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM haitou_kanji WHERE kanji = ?");
        $stmt->execute([$kanji]);
        $result = $stmt->fetch();
        if ($result) {
            // 読み仮名も取得
            $stmt2 = $pdo->prepare("SELECT * FROM kanji_readings WHERE kanji_id = ? ORDER BY is_onyomi DESC, reading");
            $stmt2->execute([$result['kanji_id']]);
            $result['readings'] = $stmt2->fetchAll();
        }
        echo json_encode($result);
        break;

    case 'add_reading':
        // 読み仮名追加
        $kanjiId = intval($_POST['kanji_id'] ?? 0);
        $isOnyomi = intval($_POST['is_onyomi'] ?? 0);
        $reading = trim($_POST['reading'] ?? '');
        $okurigana = trim($_POST['okurigana'] ?? '') ?: null;

        if ($kanjiId < 1 || empty($reading)) {
            http_response_code(400);
            echo json_encode(['error' => '漢字IDと読み仮名は必須です']);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO kanji_readings (kanji_id, is_onyomi, reading, okurigana) VALUES (?, ?, ?, ?)");
        $stmt->execute([$kanjiId, $isOnyomi, $reading, $okurigana]);
        echo json_encode(['success' => true, 'reading_id' => $pdo->lastInsertId()]);
        break;

    case 'update_reading':
        // 読み仮名更新
        $readingId = intval($_POST['reading_id'] ?? 0);
        $isOnyomi = intval($_POST['is_onyomi'] ?? 0);
        $reading = trim($_POST['reading'] ?? '');
        $okurigana = trim($_POST['okurigana'] ?? '') ?: null;

        if ($readingId < 1 || empty($reading)) {
            http_response_code(400);
            echo json_encode(['error' => '読み仮名IDと読み仮名は必須です']);
            break;
        }

        $stmt = $pdo->prepare("UPDATE kanji_readings SET is_onyomi = ?, reading = ?, okurigana = ? WHERE reading_id = ?");
        $stmt->execute([$isOnyomi, $reading, $okurigana, $readingId]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_reading':
        // 読み仮名削除
        $readingId = intval($_POST['reading_id'] ?? 0);
        if ($readingId < 1) {
            http_response_code(400);
            echo json_encode(['error' => '読み仮名IDが不正です']);
            break;
        }

        $stmt = $pdo->prepare("DELETE FROM kanji_readings WHERE reading_id = ?");
        $stmt->execute([$readingId]);
        echo json_encode(['success' => true]);
        break;

    case 'get_haitou_kanji_stats':
        // 配当漢字の統計情報
        $stmt = $pdo->query("
            SELECT
                h.grade,
                COUNT(DISTINCT h.kanji_id) as kanji_count,
                COUNT(r.reading_id) as reading_count
            FROM haitou_kanji h
            LEFT JOIN kanji_readings r ON h.kanji_id = r.kanji_id
            GROUP BY h.grade
            ORDER BY h.grade
        ");
        echo json_encode($stmt->fetchAll());
        break;

    case 'export_csv':
        // CSVエクスポート
        $exportType = $_GET['type'] ?? 'questions';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $exportType . '_' . date('Ymd') . '.csv"');
        
        // BOM出力（Excel対応）
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        if ($exportType === 'units') {
            fputcsv($output, ['grade_id', 'unit_number', 'unit_name']);
            $stmt = $pdo->query("SELECT grade_id, unit_number, unit_name FROM units ORDER BY grade_id, unit_number");
            while ($row = $stmt->fetch()) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['unit_id', 'pre_text', 'kanji_text', 'kanji_reading', 'post_text']);
            $stmt = $pdo->query("SELECT unit_id, pre_text, kanji_text, kanji_reading, post_text FROM questions ORDER BY unit_id, question_id");
            while ($row = $stmt->fetch()) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => '不明なアクションです']);
}
