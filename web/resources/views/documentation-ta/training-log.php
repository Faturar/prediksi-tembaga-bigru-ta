<?php
$statusClass = ($log['status'] ?? '') === 'ok' ? 'ok' : (($log['status'] ?? '') === 'warning' ? 'warn' : '');
$formatMetric = fn ($value, int $decimals) => $value !== null ? number_format((float) $value, $decimals, '.', '') : '-';
$lineClass = function (string $line): string {
    foreach (['START', 'DATA', 'FIT', 'METRICS', 'DONE', 'PREPARE', 'MODEL', 'SAVE_MODEL', 'SAVE_SCALER', 'SAVE_METADATA'] as $marker) {
        if (str_contains($line, "[TRAIN][{$marker}]")) {
            return 'training-log-line is-marker';
        }
    }
    if (str_contains($line, '[TRAIN][EPOCH]')) {
        return 'training-log-line is-epoch';
    }
    return 'training-log-line';
};
?>

<div class="thesis-doc-shell research-doc-shell training-log-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/training-loss">Gambar 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/manajemen-model">Gambar 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-test">Tabel 4.8 / Gambar 4.6</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training">Tabel 4.7</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/log-training?screenshot=1">Mode Screenshot</a>
        <?php if (!empty($log['model_run_id'])): ?>
            <a class="button-secondary" href="/models/log?id=<?= e((string) $log['model_run_id']) ?>">Lihat Log Lengkap</a>
        <?php endif; ?>
        <button type="button" id="copyTrainingLog">Salin Log</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel training-log-doc-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Gambar 4.5</p>
                <h2>Log Proses Pelatihan Model BiGRU</h2>
                <p class="card-note no-screenshot">Log dibaca dari file training final yang dihasilkan ML service dan worker PHP.</p>
            </div>
            <?php if (!empty($log['version'])): ?>
                <span class="thesis-model-chip">Model Version: <?= e($log['version']) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($log['message'] ?? 'Log training model final tidak tersedia.') ?></strong>
            <small>Validasi mencocokkan version, jumlah epoch, final training loss, MAE, RMSE, dan MAPE dengan metadata model final.</small>
        </div>

        <?php if (!empty($log['available'])): ?>
            <div class="thesis-meta-grid training-log-meta-grid">
                <div><span>Model Run ID</span><strong><?= e((string) $log['model_run_id']) ?></strong></div>
                <div><span>Status</span><strong><?= e(ucfirst($log['model_status'])) ?></strong></div>
                <div><span>Active</span><strong><?= $log['is_active'] ? 'Ya' : 'Tidak' ?></strong></div>
                <div><span>Epoch Aktual</span><strong><?= e((string) $log['actual_epochs']) ?></strong></div>
                <div><span>Final Training Loss</span><strong><?= e($formatMetric($log['final_training_loss_metadata'], 8)) ?></strong></div>
                <div><span>MAE</span><strong><?= e($formatMetric($log['mae'], 6)) ?></strong></div>
                <div><span>RMSE</span><strong><?= e($formatMetric($log['rmse'], 6)) ?></strong></div>
                <div><span>MAPE</span><strong><?= e($formatMetric($log['mape'], 4)) ?>%</strong></div>
                <div><span>Waktu Pelatihan</span><strong><?= e($log['formatted_duration']) ?></strong></div>
                <div><span>Status Sinkronisasi</span><strong><?= ($log['metadata_sync'] ?? 'Tidak') === 'Ya' ? 'Valid' : 'Tidak Valid' ?></strong></div>
            </div>

            <pre class="training-log-doc-box" id="trainingLogDocBox"><?php foreach ($log['lines'] as $line): ?><span class="<?= e($lineClass((string) $line)) ?>"><?= e((string) $line) ?></span>
<?php endforeach; ?></pre>

            <div class="training-log-validation no-screenshot">
                <h3>Output Data Dokumentasi</h3>
                <div class="model-detail-grid">
                    <div><span>Model Version</span><strong><?= e($log['version']) ?></strong></div>
                    <div><span>Model Run ID</span><strong><?= e((string) $log['model_run_id']) ?></strong></div>
                    <div><span>Log File</span><strong><?= e($log['log_file']) ?></strong></div>
                    <div><span>Log Exists</span><strong><?= $log['log_exists'] ? 'Ya' : 'Tidak' ?></strong></div>
                    <div><span>Actual Epoch</span><strong><?= e((string) $log['actual_epochs']) ?></strong></div>
                    <div><span>Epoch Lines Found</span><strong><?= e((string) $log['epoch_lines_found']) ?></strong></div>
                    <div><span>First Epoch Loss</span><strong><?= e($formatMetric($log['first_loss'], 8)) ?></strong></div>
                    <div><span>Final Epoch Loss</span><strong><?= e($formatMetric($log['final_loss'], 8)) ?></strong></div>
                    <div><span>Final Training Loss Metadata</span><strong><?= e($formatMetric($log['final_training_loss_metadata'], 8)) ?></strong></div>
                    <div><span>MAE</span><strong><?= e($formatMetric($log['mae'], 6)) ?></strong></div>
                    <div><span>RMSE</span><strong><?= e($formatMetric($log['rmse'], 6)) ?></strong></div>
                    <div><span>MAPE</span><strong><?= e($formatMetric($log['mape'], 4)) ?>%</strong></div>
                    <div><span>Training Duration</span><strong><?= e($log['formatted_duration']) ?></strong></div>
                    <div><span>Sinkron dengan Metadata</span><strong><?= e($log['metadata_sync']) ?></strong></div>
                    <div><span>Baris Log Lengkap</span><strong><?= e((string) $log['full_line_count']) ?></strong></div>
                    <div><span>Baris Screenshot</span><strong><?= e((string) $log['focused_line_count']) ?></strong></div>
                </div>
            </div>

            <p class="thesis-caption">Gambar 4.5 Log Proses Pelatihan Model BiGRU. Sumber: Dokumen Pribadi (2026)</p>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Log training model final tidak tersedia.</strong><small>File log untuk model final belum ditemukan pada direktori log ML service.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const copyTrainingLogButton = document.getElementById('copyTrainingLog');
if (copyTrainingLogButton) {
    copyTrainingLogButton.addEventListener('click', async () => {
        const logBox = document.getElementById('trainingLogDocBox');
        if (!logBox) return;
        await navigator.clipboard.writeText(logBox.innerText.trim());
        copyTrainingLogButton.textContent = 'Tersalin';
        setTimeout(() => copyTrainingLogButton.textContent = 'Salin Log', 1400);
    });
}
</script>
