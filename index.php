<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>漢字れんしゅう</title>
    <link rel="stylesheet" href="css/style.css?v=20">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="title">🎓 漢字れんしゅう</h1>
            <a href="admin.php" class="admin-link">管理画面</a>
        </header>

        <!-- ステップ1: 学年選択 -->
        <section id="step-grade" class="step active">
            <h2 class="step-title">
                <span class="step-number">1</span>
                学年をえらぼう
            </h2>
            <div id="grade-list" class="radio-grid">
                <!-- 動的に生成 -->
            </div>
            <button id="btn-next-grade" class="btn btn-primary" disabled>つぎへ →</button>
        </section>

        <!-- ステップ2: 単元選択 -->
        <section id="step-unit" class="step">
            <h2 class="step-title">
                <span class="step-number">2</span>
                単元をえらぼう
            </h2>
            <div id="unit-list" class="checkbox-grid">
                <!-- 動的に生成 -->
            </div>
            <div class="btn-group">
                <button id="btn-back-grade" class="btn btn-secondary">← もどる</button>
                <button id="btn-next-unit" class="btn btn-primary" disabled>つぎへ →</button>
            </div>
        </section>

        <!-- ステップ3: 出題数選択 -->
        <section id="step-count" class="step">
            <h2 class="step-title">
                <span class="step-number">3</span>
                問題数をえらぼう
            </h2>
            <p id="available-count" class="info-text">登録問題数: --問</p>
            <div id="count-options" class="radio-grid">
                <label class="radio-card">
                    <input type="radio" name="count" value="5">
                    <span class="radio-label">5問</span>
                </label>
                <label class="radio-card">
                    <input type="radio" name="count" value="10" checked>
                    <span class="radio-label">10問</span>
                </label>
                <label class="radio-card">
                    <input type="radio" name="count" value="0">
                    <span class="radio-label">ぜんぶ</span>
                </label>
            </div>
            <div class="btn-group">
                <button id="btn-back-unit" class="btn btn-secondary">← もどる</button>
                <button id="btn-start" class="btn btn-accent">🚀 スタート！</button>
            </div>
        </section>

        <!-- ステップ4: 問題表示 -->
        <section id="step-quiz" class="step">
            <div class="quiz-header">
                <span id="quiz-progress" class="quiz-progress">1 / 10</span>
                <span class="tap-hint-header">👆 タップで答えを見る</span>
                <button id="btn-finish" class="btn btn-small btn-secondary">おわる</button>
            </div>

            <div id="question-list" class="question-list">
                <!-- 動的に生成 -->
            </div>
            
            <div class="quiz-footer">
                <button id="btn-show-all" class="btn btn-primary">すべての答えを見る</button>
                <button id="btn-retry" class="btn btn-accent" style="display:none;">もう一度チャレンジ</button>
                <button id="btn-new-quiz" class="btn btn-secondary" style="display:none;">あたらしい問題</button>
            </div>
        </section>
    </div>

    <script src="js/app.js?v=5"></script>
</body>
</html>
