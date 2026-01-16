<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - 漢字れんしゅう</title>
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="admin-header">
            <h1 class="admin-title">⚙️ 管理画面</h1>
            <nav class="admin-nav">
                <button class="tab-btn active" data-tab="units">単元管理</button>
                <button class="tab-btn" data-tab="questions">問題管理</button>
                <button class="tab-btn" data-tab="import">CSV入出力</button>
                <a href="index.php" class="btn btn-secondary btn-small">← れんしゅうへ</a>
            </nav>
        </header>

        <div id="alert-container"></div>

        <!-- 単元管理 -->
        <section id="tab-units" class="admin-section active">
            <h2 class="section-title">単元一覧</h2>
            <button class="btn btn-primary" onclick="admin.showUnitModal()">＋ 単元を追加</button>
            <table class="data-table" style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>学年</th>
                        <th>単元番号</th>
                        <th>単元名</th>
                        <th>問題数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="units-table-body">
                    <!-- 動的に生成 -->
                </tbody>
            </table>
        </section>

        <!-- 問題管理 -->
        <section id="tab-questions" class="admin-section">
            <h2 class="section-title">問題一覧</h2>
            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label">学年</label>
                    <select id="filter-grade" class="form-select">
                        <option value="">選択してください</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">単元</label>
                    <select id="filter-unit" class="form-select" disabled>
                        <option value="">学年を選択してください</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary" onclick="admin.showQuestionModal()" id="btn-add-question" disabled>＋ 問題を追加</button>
            <table class="data-table" style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>問題文</th>
                        <th>漢字</th>
                        <th>ふりがな</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="questions-table-body">
                    <tr><td colspan="5" style="text-align: center; color: #666;">単元を選択してください</td></tr>
                </tbody>
            </table>
        </section>

        <!-- CSV入出力 -->
        <section id="tab-import" class="admin-section">
            <h2 class="section-title">CSV入出力</h2>
            
            <div class="csv-section">
                <h4>📥 CSVインポート</h4>
                <div class="form-group">
                    <label class="form-label">インポート種別</label>
                    <select id="import-type" class="form-select" style="max-width: 300px;">
                        <option value="questions">問題データ</option>
                        <option value="units">単元データ</option>
                    </select>
                </div>
                <div class="csv-format" id="csv-format-hint">
                    <strong>CSVフォーマット（1行目はヘッダー）:</strong><br>
                    unit_id, pre_text, kanji_text, kanji_reading, post_text<br>
                    <br>
                    <strong>例:</strong><br>
                    1,,山,やま,にのぼる。<br>
                    1,本を,読,よ,む。
                </div>
                <div class="form-group">
                    <label class="form-label">CSVファイル</label>
                    <input type="file" id="csv-file" accept=".csv" class="form-input" style="max-width: 400px;">
                    <p class="form-hint">UTF-8（BOMあり/なし）対応</p>
                </div>
                <button class="btn btn-primary" onclick="admin.importCsv()">インポート実行</button>
            </div>
            
            <div class="csv-section">
                <h4>📤 CSVエクスポート</h4>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="admin.exportCsv('questions')">問題データをダウンロード</button>
                    <button class="btn btn-secondary" onclick="admin.exportCsv('units')">単元データをダウンロード</button>
                </div>
            </div>
        </section>
    </div>

    <!-- 単元モーダル -->
    <div id="unit-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="unit-modal-title">単元を追加</h3>
                <button class="modal-close" onclick="admin.closeModal('unit-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="unit-id">
                <div class="form-group">
                    <label class="form-label">学年</label>
                    <select id="unit-grade" class="form-select">
                        <!-- 動的に生成 -->
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">単元番号</label>
                    <input type="number" id="unit-number" class="form-input" min="1" value="1">
                </div>
                <div class="form-group">
                    <label class="form-label">単元名</label>
                    <input type="text" id="unit-name" class="form-input" placeholder="例: かん字のはなし">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="admin.closeModal('unit-modal')">キャンセル</button>
                <button class="btn btn-primary" onclick="admin.saveUnit()">保存</button>
            </div>
        </div>
    </div>

    <!-- 問題モーダル -->
    <div id="question-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="question-modal-title">問題を追加</h3>
                <button class="modal-close" onclick="admin.closeModal('question-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="question-id">
                <input type="hidden" id="question-unit-id">
                <div class="form-group">
                    <label class="form-label">問題前文</label>
                    <input type="text" id="question-pre" class="form-input" placeholder="例: 本を（空欄の前の文）">
                    <p class="form-hint">空欄の前にある文。なければ空欄</p>
                </div>
                <div class="form-group">
                    <label class="form-label">問われる漢字 <span style="color: #ff7b54;">*</span></label>
                    <input type="text" id="question-kanji" class="form-input" placeholder="例: 読">
                    <p class="form-hint">ひらがなを含む場合（読み、持ち上げ など）は大かっこ表示になります</p>
                </div>
                <div class="form-group">
                    <label class="form-label">ふりがな <span style="color: #ff7b54;">*</span></label>
                    <input type="text" id="question-reading" class="form-input" placeholder="例: よ">
                </div>
                <div class="form-group">
                    <label class="form-label">問題後文</label>
                    <input type="text" id="question-post" class="form-input" placeholder="例: む。（空欄の後の文）">
                    <p class="form-hint">空欄の後にある文。なければ空欄</p>
                </div>
                <div class="form-group">
                    <label class="form-label">プレビュー</label>
                    <div id="question-preview" style="padding: 1rem; background: #f5f5f5; border-radius: 8px; font-size: 1.25rem;">
                        <!-- 動的に生成 -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="admin.closeModal('question-modal')">キャンセル</button>
                <button class="btn btn-primary" onclick="admin.saveQuestion()">保存</button>
            </div>
        </div>
    </div>

    <script>
    /**
     * 管理画面 JavaScript
     */
    class AdminApp {
        constructor() {
            this.grades = [];
            this.currentUnitId = null;
        }

        async init() {
            this.bindEvents();
            await this.loadGrades();
            await this.loadUnits();
        }

        bindEvents() {
            // タブ切り替え
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabId = btn.dataset.tab;
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
                    btn.classList.add('active');
                    document.getElementById(`tab-${tabId}`).classList.add('active');
                });
            });

            // 学年フィルター
            document.getElementById('filter-grade').addEventListener('change', async (e) => {
                const gradeId = e.target.value;
                const unitSelect = document.getElementById('filter-unit');
                
                if (!gradeId) {
                    unitSelect.innerHTML = '<option value="">学年を選択してください</option>';
                    unitSelect.disabled = true;
                    document.getElementById('btn-add-question').disabled = true;
                    document.getElementById('questions-table-body').innerHTML = 
                        '<tr><td colspan="5" style="text-align: center; color: #666;">単元を選択してください</td></tr>';
                    return;
                }

                const units = await this.fetchUnits(gradeId);
                unitSelect.innerHTML = '<option value="">選択してください</option>' +
                    units.map(u => `<option value="${u.unit_id}">${u.unit_name}</option>`).join('');
                unitSelect.disabled = false;
            });

            // 単元フィルター
            document.getElementById('filter-unit').addEventListener('change', async (e) => {
                const unitId = e.target.value;
                this.currentUnitId = unitId ? parseInt(unitId) : null;
                document.getElementById('btn-add-question').disabled = !unitId;
                
                if (unitId) {
                    await this.loadQuestions(unitId);
                } else {
                    document.getElementById('questions-table-body').innerHTML = 
                        '<tr><td colspan="5" style="text-align: center; color: #666;">単元を選択してください</td></tr>';
                }
            });

            // インポート種別変更
            document.getElementById('import-type').addEventListener('change', (e) => {
                const hint = document.getElementById('csv-format-hint');
                if (e.target.value === 'units') {
                    hint.innerHTML = `
                        <strong>CSVフォーマット（1行目はヘッダー）:</strong><br>
                        grade_id, unit_number, unit_name<br>
                        <br>
                        <strong>例:</strong><br>
                        1,1,かん字のはなし<br>
                        1,2,かたかなをかこう
                    `;
                } else {
                    hint.innerHTML = `
                        <strong>CSVフォーマット（1行目はヘッダー）:</strong><br>
                        unit_id, pre_text, kanji_text, kanji_reading, post_text<br>
                        <br>
                        <strong>例:</strong><br>
                        1,,山,やま,にのぼる。<br>
                        1,本を,読,よ,む。
                    `;
                }
            });

            // 問題プレビュー
            ['question-pre', 'question-kanji', 'question-reading', 'question-post'].forEach(id => {
                document.getElementById(id).addEventListener('input', () => this.updatePreview());
            });
        }

        showAlert(message, type = 'success') {
            const container = document.getElementById('alert-container');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }

        // ========================================
        // 学年関連
        // ========================================

        async loadGrades() {
            try {
                const response = await fetch('api.php?action=get_grades');
                this.grades = await response.json();

                // フィルター用セレクト
                document.getElementById('filter-grade').innerHTML = 
                    '<option value="">選択してください</option>' +
                    this.grades.map(g => `<option value="${g.grade_id}">${g.grade_name}</option>`).join('');

                // モーダル用セレクト
                document.getElementById('unit-grade').innerHTML = 
                    this.grades.map(g => `<option value="${g.grade_id}">${g.grade_name}</option>`).join('');
            } catch (error) {
                console.error('学年読み込みエラー:', error);
            }
        }

        // ========================================
        // 単元関連
        // ========================================

        async loadUnits() {
            try {
                const response = await fetch('api.php?action=get_all_units');
                const units = await response.json();

                const tbody = document.getElementById('units-table-body');
                if (units.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #666;">単元がありません</td></tr>';
                    return;
                }

                tbody.innerHTML = units.map(u => `
                    <tr>
                        <td>${u.grade_name}</td>
                        <td>第${u.unit_number}単元</td>
                        <td>${this.escapeHtml(u.unit_name)}</td>
                        <td>${u.question_count}問</td>
                        <td class="actions">
                            <button class="btn btn-secondary btn-icon" onclick="admin.editUnit(${u.unit_id}, ${u.grade_id}, ${u.unit_number}, '${this.escapeHtml(u.unit_name)}')">編集</button>
                            <button class="btn btn-secondary btn-icon" onclick="admin.deleteUnit(${u.unit_id}, '${this.escapeHtml(u.unit_name)}')">削除</button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('単元読み込みエラー:', error);
            }
        }

        async fetchUnits(gradeId) {
            const response = await fetch(`api.php?action=get_units&grade_ids=${gradeId}`);
            return await response.json();
        }

        showUnitModal(unitId = null, gradeId = null, unitNumber = null, unitName = null) {
            document.getElementById('unit-modal-title').textContent = unitId ? '単元を編集' : '単元を追加';
            document.getElementById('unit-id').value = unitId || '';
            document.getElementById('unit-grade').value = gradeId || this.grades[0]?.grade_id || '';
            document.getElementById('unit-number').value = unitNumber || 1;
            document.getElementById('unit-name').value = unitName || '';
            document.getElementById('unit-modal').classList.add('active');
        }

        editUnit(unitId, gradeId, unitNumber, unitName) {
            this.showUnitModal(unitId, gradeId, unitNumber, unitName);
        }

        async saveUnit() {
            const unitId = document.getElementById('unit-id').value;
            const gradeId = document.getElementById('unit-grade').value;
            const unitNumber = document.getElementById('unit-number').value;
            const unitName = document.getElementById('unit-name').value.trim();

            if (!unitName) {
                alert('単元名を入力してください');
                return;
            }

            const formData = new FormData();
            formData.append('action', unitId ? 'update_unit' : 'add_unit');
            if (unitId) formData.append('unit_id', unitId);
            formData.append('grade_id', gradeId);
            formData.append('unit_number', unitNumber);
            formData.append('unit_name', unitName);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    this.closeModal('unit-modal');
                    this.showAlert(unitId ? '単元を更新しました' : '単元を追加しました');
                    await this.loadUnits();
                } else {
                    alert(result.error || '保存に失敗しました');
                }
            } catch (error) {
                console.error('保存エラー:', error);
                alert('保存に失敗しました');
            }
        }

        async deleteUnit(unitId, unitName) {
            if (!confirm(`「${unitName}」を削除しますか？\n※関連する問題もすべて削除されます`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_unit');
            formData.append('unit_id', unitId);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    this.showAlert('単元を削除しました');
                    await this.loadUnits();
                } else {
                    alert(result.error || '削除に失敗しました');
                }
            } catch (error) {
                console.error('削除エラー:', error);
                alert('削除に失敗しました');
            }
        }

        // ========================================
        // 問題関連
        // ========================================

        async loadQuestions(unitId) {
            try {
                const response = await fetch(`api.php?action=get_questions_by_unit&unit_id=${unitId}`);
                const questions = await response.json();

                const tbody = document.getElementById('questions-table-body');
                if (questions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #666;">問題がありません</td></tr>';
                    return;
                }

                tbody.innerHTML = questions.map(q => {
                    const fullText = `${q.pre_text}【${q.kanji_text}】${q.post_text}`;
                    return `
                        <tr>
                            <td>${q.question_id}</td>
                            <td>${this.escapeHtml(fullText)}</td>
                            <td>${this.escapeHtml(q.kanji_text)}</td>
                            <td>${this.escapeHtml(q.kanji_reading)}</td>
                            <td class="actions">
                                <button class="btn btn-secondary btn-icon" onclick='admin.editQuestion(${JSON.stringify(q)})'>編集</button>
                                <button class="btn btn-secondary btn-icon" onclick="admin.deleteQuestion(${q.question_id})">削除</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('問題読み込みエラー:', error);
            }
        }

        showQuestionModal(question = null) {
            document.getElementById('question-modal-title').textContent = question ? '問題を編集' : '問題を追加';
            document.getElementById('question-id').value = question?.question_id || '';
            document.getElementById('question-unit-id').value = question?.unit_id || this.currentUnitId;
            document.getElementById('question-pre').value = question?.pre_text || '';
            document.getElementById('question-kanji').value = question?.kanji_text || '';
            document.getElementById('question-reading').value = question?.kanji_reading || '';
            document.getElementById('question-post').value = question?.post_text || '';
            this.updatePreview();
            document.getElementById('question-modal').classList.add('active');
        }

        editQuestion(question) {
            this.showQuestionModal(question);
        }

        updatePreview() {
            const pre = document.getElementById('question-pre').value;
            const kanji = document.getElementById('question-kanji').value;
            const reading = document.getElementById('question-reading').value;
            const post = document.getElementById('question-post').value;

            if (!kanji) {
                document.getElementById('question-preview').innerHTML = '<span style="color: #999;">漢字を入力してください</span>';
                return;
            }

            const hasHiragana = /[ぁ-ん]/.test(kanji);
            let blankHtml;

            if (hasHiragana) {
                blankHtml = `<span style="color: #4a90d9;">【</span><span style="border-bottom: 2px solid #4a90d9; display: inline-block; min-width: 2em;">&nbsp;</span><span style="color: #4a90d9;">】</span>`;
            } else {
                const boxes = Array(kanji.length).fill('<span style="display: inline-block; width: 1.5em; height: 1.5em; border: 2px solid #4a90d9; margin: 0 1px; vertical-align: middle;"></span>').join('');
                blankHtml = boxes;
            }

            document.getElementById('question-preview').innerHTML = 
                `${this.escapeHtml(pre)}${blankHtml}<span style="font-size: 0.75em; color: #ff7b54; vertical-align: super;">（${this.escapeHtml(reading)}）</span>${this.escapeHtml(post)}`;
        }

        async saveQuestion() {
            const questionId = document.getElementById('question-id').value;
            const unitId = document.getElementById('question-unit-id').value;
            const preText = document.getElementById('question-pre').value;
            const kanjiText = document.getElementById('question-kanji').value.trim();
            const kanjiReading = document.getElementById('question-reading').value.trim();
            const postText = document.getElementById('question-post').value;

            if (!kanjiText || !kanjiReading) {
                alert('問われる漢字とふりがなは必須です');
                return;
            }

            const formData = new FormData();
            formData.append('action', questionId ? 'update_question' : 'add_question');
            if (questionId) formData.append('question_id', questionId);
            formData.append('unit_id', unitId);
            formData.append('pre_text', preText);
            formData.append('kanji_text', kanjiText);
            formData.append('kanji_reading', kanjiReading);
            formData.append('post_text', postText);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    this.closeModal('question-modal');
                    this.showAlert(questionId ? '問題を更新しました' : '問題を追加しました');
                    await this.loadQuestions(unitId);
                } else {
                    alert(result.error || '保存に失敗しました');
                }
            } catch (error) {
                console.error('保存エラー:', error);
                alert('保存に失敗しました');
            }
        }

        async deleteQuestion(questionId) {
            if (!confirm('この問題を削除しますか？')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_question');
            formData.append('question_id', questionId);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    this.showAlert('問題を削除しました');
                    await this.loadQuestions(this.currentUnitId);
                } else {
                    alert(result.error || '削除に失敗しました');
                }
            } catch (error) {
                console.error('削除エラー:', error);
                alert('削除に失敗しました');
            }
        }

        // ========================================
        // CSV入出力
        // ========================================

        async importCsv() {
            const fileInput = document.getElementById('csv-file');
            const importType = document.getElementById('import-type').value;

            if (!fileInput.files.length) {
                alert('CSVファイルを選択してください');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'import_csv');
            formData.append('import_type', importType);
            formData.append('csv_file', fileInput.files[0]);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    let message = `${result.imported}件をインポートしました`;
                    if (result.errors && result.errors.length > 0) {
                        message += `\n\nエラー:\n${result.errors.join('\n')}`;
                    }
                    this.showAlert(message);
                    await this.loadUnits();
                    fileInput.value = '';
                } else {
                    alert(result.error || 'インポートに失敗しました');
                }
            } catch (error) {
                console.error('インポートエラー:', error);
                alert('インポートに失敗しました');
            }
        }

        exportCsv(type) {
            window.location.href = `api.php?action=export_csv&type=${type}`;
        }

        // ========================================
        // ユーティリティ
        // ========================================

        closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    }

    const admin = new AdminApp();
    document.addEventListener('DOMContentLoaded', () => admin.init());
    </script>
</body>
</html>
