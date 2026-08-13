<section class="panel training-log-panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Training Log</p>
            <h2><?= e($model['version']) ?></h2>
            <p class="card-note">Log proses training BiGRU diperbarui dari ML service.</p>
        </div>
        <div class="training-log-actions">
            <span class="status-pill <?= $model['status'] === 'success' ? 'ok' : ($model['status'] === 'running' ? 'warn' : '') ?>">
                <?= e($model['status']) ?>
            </span>
            <a class="button-secondary" href="/models">Kembali</a>
        </div>
    </div>

    <div class="model-detail-grid training-log-summary">
        <div><span>Window</span><strong><?= e($model['window_size']) ?></strong></div>
        <div><span>Units</span><strong><?= e($model['units']) ?></strong></div>
        <div><span>Batch</span><strong><?= e($model['batch_size']) ?></strong></div>
        <div><span>Epoch</span><strong><?= e($model['actual_epochs'] ?? $model['configured_epochs']) ?></strong></div>
    </div>

    <?php if ($model['status'] === 'running'): ?>
    <div class="training-loading" aria-live="polite">
        <span></span>
        <strong>Training sedang berjalan...</strong>
        <small>Halaman ini refresh otomatis tiap 5 detik.</small>
    </div>
    <?php endif; ?>

    <pre class="training-log-box" id="trainingLogBox"><?= e($logContent !== '' ? $logContent : 'Menunggu log training dari ML service...') ?></pre>

    <?php if ($model['status'] === 'failed' && !empty($model['error_message'])): ?>
    <p class="alert">Training gagal: <?= e($model['error_message']) ?></p>
    <?php endif; ?>
</section>

<?php if ($model['status'] === 'running'): ?>
<script>
setTimeout(() => window.location.reload(), 5000);
</script>
<?php endif; ?>
<script>
const trainingLogBox = document.getElementById('trainingLogBox');
if (trainingLogBox) {
    trainingLogBox.scrollTop = trainingLogBox.scrollHeight;
}
</script>
