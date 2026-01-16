/**
 * 漢字れんしゅう - メインアプリケーション
 */

class KanjiQuizApp {
    constructor() {
        this.selectedGrade = null; // 単一選択に変更
        this.selectedUnits = [];
        this.selectedCount = 10;
        this.questions = [];
        this.availableCount = 0;
    }

    init() {
        this.bindEvents();
        this.loadGrades();
    }

    bindEvents() {
        // ステップ1: 学年選択
        document.getElementById('btn-next-grade').addEventListener('click', () => this.goToUnitStep());

        // ステップ2: 単元選択
        document.getElementById('btn-back-grade').addEventListener('click', () => this.showStep('step-grade'));
        document.getElementById('btn-next-unit').addEventListener('click', () => this.goToCountStep());

        // ステップ3: 出題数選択
        document.getElementById('btn-back-unit').addEventListener('click', () => this.showStep('step-unit'));
        document.getElementById('btn-start').addEventListener('click', () => this.startQuiz());

        // 出題数ラジオボタン
        document.querySelectorAll('input[name="count"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.selectedCount = parseInt(e.target.value);
            });
        });

        // ステップ4: 問題表示
        document.getElementById('btn-finish').addEventListener('click', () => this.finishQuiz());
        document.getElementById('btn-show-all').addEventListener('click', () => this.showAllAnswers());
        document.getElementById('btn-retry').addEventListener('click', () => this.retryQuiz());
        document.getElementById('btn-new-quiz').addEventListener('click', () => this.newQuiz());
    }

    showStep(stepId) {
        document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
        document.getElementById(stepId).classList.add('active');
    }

    // ========================================
    // ステップ1: 学年選択
    // ========================================

    async loadGrades() {
        try {
            const response = await fetch('api.php?action=get_grades');
            const grades = await response.json();

            const container = document.getElementById('grade-list');
            container.innerHTML = grades.map(grade => `
                <label class="radio-card">
                    <input type="radio" name="grade" value="${grade.grade_id}" data-name="${grade.grade_name}">
                    <span class="radio-label">${grade.grade_name}</span>
                </label>
            `).join('');

            // ラジオボタンのイベント（単一選択）
            container.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', () => this.updateGradeSelection());
            });
        } catch (error) {
            console.error('学年の読み込みに失敗:', error);
        }
    }

    updateGradeSelection() {
        const selected = document.querySelector('#grade-list input[type="radio"]:checked');
        if (selected) {
            this.selectedGrade = {
                id: parseInt(selected.value),
                name: selected.dataset.name
            };
        } else {
            this.selectedGrade = null;
        }

        document.getElementById('btn-next-grade').disabled = !this.selectedGrade;
    }

    // ========================================
    // ステップ2: 単元選択
    // ========================================

    async goToUnitStep() {
        // 単元選択状態をリセット
        this.selectedUnits = [];
        const container = document.getElementById('unit-list');

        if (!this.selectedGrade) {
            return;
        }

        try {
            const gradeIds = this.selectedGrade.id;
            console.log('Fetching units for grade:', gradeIds);

            const response = await fetch(`api.php?action=get_units&grade_ids=${gradeIds}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const units = await response.json();

            // 単元が空またはnullの場合
            if (!units || !Array.isArray(units) || units.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">この学年には単元が登録されていません。<br>管理画面から単元を追加してください。</p>';
                document.getElementById('btn-next-unit').disabled = true;
                this.showStep('step-unit');
                return;
            }

            container.innerHTML = units.map(unit => `
                <label class="checkbox-card">
                    <input type="checkbox" value="${unit.unit_id}" data-name="${this.escapeHtml(unit.unit_name)}">
                    <span class="checkbox-label">
                        <span class="unit-name">${this.escapeHtml(unit.unit_name)}</span>
                        <span class="grade-badge">第${unit.unit_number}単元</span>
                    </span>
                </label>
            `).join('');

            // チェックボックスのイベント
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', () => this.updateUnitSelection());
            });

            // ボタンの状態をリセット
            document.getElementById('btn-next-unit').disabled = true;

            // データ読み込み完了後に画面を表示
            this.showStep('step-unit');

        } catch (error) {
            console.error('単元の読み込みに失敗:', error);
            container.innerHTML = `<p style="text-align: center; color: #c62828; padding: 2rem;">単元の読み込みに失敗しました。<br>${this.escapeHtml(error.message)}<br>ページを再読み込みしてください。</p>`;
            document.getElementById('btn-next-unit').disabled = true;
            this.showStep('step-unit');
        }
    }

    updateUnitSelection() {
        const checkboxes = document.querySelectorAll('#unit-list input[type="checkbox"]:checked');
        this.selectedUnits = Array.from(checkboxes).map(cb => ({
            id: parseInt(cb.value),
            name: cb.dataset.name
        }));

        document.getElementById('btn-next-unit').disabled = this.selectedUnits.length === 0;
    }

    // ========================================
    // ステップ3: 出題数選択
    // ========================================

    async goToCountStep() {
        // 単元選択状態を最新に更新
        this.updateUnitSelection();

        if (this.selectedUnits.length === 0) {
            alert('単元を選択してください。');
            return;
        }

        await this.loadQuestionCount();
        this.showStep('step-count');
    }

    async loadQuestionCount() {
        try {
            const unitIds = this.selectedUnits.map(u => u.id).join(',');
            console.log('Fetching question count for units:', unitIds);

            if (!unitIds) {
                document.getElementById('available-count').textContent = `登録問題数: 0問`;
                return;
            }

            const response = await fetch(`api.php?action=get_question_count&unit_ids=${unitIds}`);
            const result = await response.json();
            console.log('Question count result:', result);

            this.availableCount = result.count || 0;
            document.getElementById('available-count').textContent = `登録問題数: ${this.availableCount}問`;

            // 出題数オプションの調整
            const allRadio = document.querySelector('input[name="count"][value="0"]');
            if (allRadio) {
                allRadio.closest('.radio-card').querySelector('.radio-label').textContent =
                    `ぜんぶ（${this.availableCount}問）`;
            }
        } catch (error) {
            console.error('問題数の取得に失敗:', error);
            document.getElementById('available-count').textContent = `登録問題数: --問`;
        }
    }

    // ========================================
    // ステップ4: 問題表示
    // ========================================

    async startQuiz() {
        try {
            const unitIds = this.selectedUnits.map(u => u.id).join(',');
            const limit = this.selectedCount;
            const response = await fetch(`api.php?action=get_questions&unit_ids=${unitIds}&limit=${limit}`);
            this.questions = await response.json();

            if (this.questions.length === 0) {
                // 問題がない場合は単元選択に戻る
                this.showStep('step-unit');
                return;
            }

            this.renderQuestions();
            this.showStep('step-quiz');
        } catch (error) {
            console.error('問題の読み込みに失敗:', error);
        }
    }

    renderQuestions() {
        const container = document.getElementById('question-list');
        container.innerHTML = this.questions.map((q, index) => {
            const blankHtml = this.createBlankHtml(q);
            return `
                <div class="question-card" data-index="${index}">
                    <div class="question-header">
                        <span class="question-number">第${index + 1}問</span>
                        <span class="question-meta">${q.grade_name} ${q.unit_name}</span>
                    </div>
                    <div class="question-content" onclick="app.toggleAnswer(${index})">
                        ${this.escapeHtml(q.pre_text)}${blankHtml}${this.escapeHtml(q.post_text)}
                    </div>
                </div>
            `;
        }).join('');

        document.getElementById('quiz-progress').textContent = `全${this.questions.length}問`;

        // ボタンの表示状態をリセット
        document.getElementById('btn-show-all').style.display = 'inline-flex';
        document.getElementById('btn-retry').style.display = 'none';
        document.getElementById('btn-new-quiz').style.display = 'none';
    }

    createBlankHtml(question) {
        const reading = this.escapeHtml(question.kanji_reading);
        const answerText = this.escapeHtml(question.kanji_text);

        if (question.display_type === 'box') {
            // マス目表示（熟語など）
            // 各文字ごとにマス目を作成し、答えを内包
            let boxes = '';
            for (let i = 0; i < question.kanji_text.length; i++) {
                const char = this.escapeHtml(question.kanji_text[i]);
                boxes += `<span class="box"><span class="answer-char">${char}</span></span>`;
            }
            return `<span class="blank-box"><span class="reading">${reading}</span><span class="boxes">${boxes}</span></span>`;
        } else {
            // かっこ表示（送り仮名つき）
            return `<span class="blank-bracket"><span class="reading">${reading}</span><span class="bracket-content">[<span class="answer-text">${answerText}</span>]</span></span>`;
        }
    }

    toggleAnswer(index) {
        const card = document.querySelector(`.question-card[data-index="${index}"]`);
        card.classList.toggle('revealed');
    }

    showAllAnswers() {
        document.querySelectorAll('.question-card').forEach(card => {
            card.classList.add('revealed');
        });

        document.getElementById('btn-show-all').style.display = 'none';
        document.getElementById('btn-retry').style.display = 'inline-flex';
        document.getElementById('btn-new-quiz').style.display = 'inline-flex';
    }

    retryQuiz() {
        // 同じ問題でもう一度（答えを隠す）
        document.querySelectorAll('.question-card').forEach(card => {
            card.classList.remove('revealed');
        });

        document.getElementById('btn-show-all').style.display = 'inline-flex';
        document.getElementById('btn-retry').style.display = 'none';
        document.getElementById('btn-new-quiz').style.display = 'none';

        // 先頭にスクロール
        document.getElementById('step-quiz').scrollIntoView({ behavior: 'smooth' });
    }

    newQuiz() {
        // 新しい問題でやり直し
        this.startQuiz();
    }

    finishQuiz() {
        this.showStep('step-grade');
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// グローバルにアクセス可能にする（onclick用）
let app;
document.addEventListener('DOMContentLoaded', () => {
    if (!app) {
        app = new KanjiQuizApp();
        app.init();
    }
});
