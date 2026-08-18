<?php
$statusClass = ($evaluation['status'] ?? '') === 'ok' ? 'ok' : (($evaluation['status'] ?? '') === 'warning' ? 'warn' : '');
$formatMetricId = fn ($value, int $decimals) => number_format((float) $value, $decimals, ',', '');
$formatMetricDot = fn ($value, int $decimals) => number_format((float) $value, $decimals, '.', '');
$formatDate = function (?string $date): string {
    if (!$date) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y', $timestamp) : $date;
};
?>

<div class="thesis-doc-shell research-doc-shell model-evaluation-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training">Tabel 4.7</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-test">Tabel 4.8 / Gambar 4.6</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/prediksi-recursive">Tabel 4.10</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/evaluasi-model?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyModelEvaluationTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel model-evaluation-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.9</p>
                <h2>Hasil Evaluasi Model BiGRU</h2>
                <p class="card-note no-screenshot">Nilai evaluasi dibaca dari metadata model final dan divalidasi ulang memakai seluruh test_series.</p>
            </div>
            <?php if (!empty($evaluation['version'])): ?>
                <span class="thesis-model-chip">Model Version: <?= e($evaluation['version']) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($evaluation['message'] ?? 'Metrik evaluasi model final belum tersedia.') ?></strong>
            <small>Evaluation Dataset: <?= e($evaluation['evaluation_dataset'] ?? 'Test Set') ?>; Prediction Scale: <?= e($evaluation['scale'] ?? 'Original Close Price') ?>.</small>
        </div>

        <?php if (!empty($evaluation['available'])): ?>
            <div class="research-table-wrap model-evaluation-table-wrap">
                <table id="modelEvaluationTable" class="research-table model-evaluation-table">
                    <thead>
                        <tr>
                            <th>Metrik</th>
                            <th>Nilai Final</th>
                            <th>Interpretasi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($evaluation['rows'] as $row): ?>
                        <tr>
                            <td data-label="Metrik"><strong><?= e($row['metric']) ?></strong></td>
                            <td data-label="Nilai Final"><?= e($formatMetricId($row['value'], (int) $row['decimals']) . $row['suffix']) ?></td>
                            <td data-label="Interpretasi"><?= e($row['interpretation']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="thesis-caption">Sumber: Hasil pengujian model final</p>

            <section class="model-evaluation-detail no-screenshot">
                <h3>Informasi Tambahan</h3>
                <div class="model-detail-grid">
                    <div><span>Model Version</span><strong><?= e($evaluation['version']) ?></strong></div>
                    <div><span>Model Run ID</span><strong><?= e((string) $evaluation['model_run_id']) ?></strong></div>
                    <div><span>Status</span><strong><?= e(ucfirst($evaluation['model_status'])) ?></strong></div>
                    <div><span>Active</span><strong><?= $evaluation['is_active'] ? 'Ya' : 'Tidak' ?></strong></div>
                    <div><span>Test Start Date</span><strong><?= e($formatDate($evaluation['test_start_date'])) ?></strong></div>
                    <div><span>Test End Date</span><strong><?= e($formatDate($evaluation['test_end_date'])) ?></strong></div>
                    <div><span>Test Samples</span><strong><?= e((string) $evaluation['test_samples']) ?></strong></div>
                    <div><span>Test Series Records</span><strong><?= e((string) $evaluation['test_series_records']) ?></strong></div>
                    <div><span>MAE</span><strong><?= e($formatMetricDot($evaluation['mae'], 6)) ?></strong></div>
                    <div><span>RMSE</span><strong><?= e($formatMetricDot($evaluation['rmse'], 6)) ?></strong></div>
                    <div><span>MAPE</span><strong><?= e($formatMetricDot($evaluation['mape'], 4)) ?>%</strong></div>
                    <div><span>MAE Validation</span><strong><?= e($formatMetricDot($evaluation['mae_validation'], 6)) ?></strong></div>
                    <div><span>RMSE Validation</span><strong><?= e($formatMetricDot($evaluation['rmse_validation'], 6)) ?></strong></div>
                    <div><span>MAPE Validation</span><strong><?= e($formatMetricDot($evaluation['mape_validation'], 4)) ?>%</strong></div>
                    <div><span>Scale</span><strong><?= e($evaluation['scale']) ?></strong></div>
                    <div><span>Evaluation Source</span><strong><?= e($evaluation['evaluation_source']) ?></strong></div>
                    <div><span>Validation Source</span><strong><?= e($evaluation['validation_source']) ?></strong></div>
                    <div><span>MAPE Zero Division</span><strong><?= e($evaluation['zero_division_status']) ?></strong></div>
                    <div><span>Status Sinkronisasi</span><strong><?= e($evaluation['metadata_sync']) ?></strong></div>
                    <div><span>Tabel 4.8</span><strong><?= e($evaluation['table48_sync']) ?></strong></div>
                    <div><span>Gambar 4.6</span><strong><?= e($evaluation['figure46_sync']) ?></strong></div>
                    <div><span>Overall Status</span><strong><?= e($evaluation['overall_status']) ?></strong></div>
                </div>
            </section>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Metrik evaluasi model final belum tersedia.</strong><small>Pastikan model final memiliki metadata metrics.mae, metrics.rmse, metrics.mape, dan test_series.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const modelEvaluationCopyButton = document.getElementById('copyModelEvaluationTable');
if (modelEvaluationCopyButton) {
    modelEvaluationCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#modelEvaluationTable tr'));
        const text = ['Tabel 4.9', 'Hasil Evaluasi Model BiGRU', '']
            .concat(rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')))
            .join('\n');
        await navigator.clipboard.writeText(text);
        modelEvaluationCopyButton.textContent = 'Tersalin';
        setTimeout(() => modelEvaluationCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
