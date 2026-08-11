<section class="login-shell">
    <div class="login-visual">
        <div class="login-visual-content">
            <h1>Prediksi harga tembaga berbasis data historis.</h1>
            <p>Kelola dataset, training BiGRU, evaluasi model, dan laporan prediksi dalam satu panel admin.</p>
        </div>
    </div>
    <div class="login-panel">
        <div class="brand login-brand">
            <span>
                <strong><?= e(config('app')['name']) ?></strong>
                <small>Masuk ke dashboard</small>
            </span>
        </div>
        <h2>Login Admin</h2>
        <p class="login-helper">Gunakan akun pengelola untuk mengatur data harga, model, prediksi, dan laporan.</p>
        <?php if (!empty($error)): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
        <form method="post" action="/login" class="stack">
            <?= csrf_field() ?>
            <label>Email <input type="email" name="email" autocomplete="email" required></label>
            <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
            <button type="submit">Masuk</button>
        </form>
    </div>
</section>
