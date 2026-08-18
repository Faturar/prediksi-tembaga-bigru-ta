<?php
$statusClass = ($recursive['status'] ?? '') === 'ok' ? 'ok' : (($recursive['status'] ?? '') === 'warning' ? 'warn' : '');
$formatPrice = fn ($value) => $value !== null ? number_format((float) $value, 6, ',', '') : '-';
$formatDate = function (?string $date): string {
    if (!$date) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y', $timestamp) : $date;
};
$formatTimestamp = function (?string $date): string {
    if (!$date) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y H:i:s', $timestamp) : $date;
};
$displayValue = function (array $row) use ($formatDate, $formatTimestamp): string {
    if (in_array($row['info'], ['Input awal', 'Input akhir'], true)) {
        return $formatDate($row['value']);
    }
    if ($row['info'] === 'Waktu proses') {
        return $formatTimestamp($row['value']);
    }
    return (string) $row['value'];
};
?>

<div class="thesis-doc-shell research-doc-shell recursive-prediction-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/evaluasi-model">Tabel 4.9</a>
        <a class="button-secondary" href="/predictions">Riwayat Prediksi</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/prediksi-recursive?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyRecursivePredictionTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel recursive-prediction-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.10</p>
                <h2>Prediksi Recursive Multi-Periode</h2>
                <p class="card-note no-screenshot">Data berasal dari riwayat prediksi operasional model aktif, bukan test set prediction.</p>
            </div>
            <?php if (!empty($recursive['version'])): ?>
                <span class="thesis-model-chip">Model Version: <?= e($recursive['version']) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($recursive['message'] ?? 'Model aktif belum tersedia.') ?></strong>
            <small>Validasi memeriksa model aktif, artifact model/scaler, window input, horizon, jumlah output, dan source recursive forecasting.</small>
        </div>

        <?php if (!empty($recursive['available'])): ?>
            <div class="research-table-wrap recursive-prediction-table-wrap">
                <table id="recursivePredictionTable" class="research-table recursive-prediction-table">
                    <thead>
                        <tr>
                            <th>Informasi</th>
                            <th>Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recursive['rows'] as $row): ?>
                        <tr>
                            <td data-label="Informasi"><strong><?= e($row['info']) ?></strong></td>
                            <td data-label="Nilai"><?= nl2br(e($displayValue($row))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="thesis-caption">Sumber: Hasil prediksi sistem final</p>

            <section class="recursive-prediction-detail no-screenshot">
                <h3>Detail Hasil Prediksi</h3>
                <div class="research-table-wrap">
                    <table class="research-table recursive-output-table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Prediksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recursive['outputs'] as $output): ?>
                            <tr>
                                <td data-label="Periode"><strong><?= e($output['period']) ?></strong></td>
                                <td data-label="Prediksi"><?= e($formatPrice($output['predicted_close'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h3>Informasi Validasi</h3>
                <div class="model-detail-grid">
                    <div><span>Prediction ID</span><strong><?= e((string) $recursive['prediction_id']) ?></strong></div>
                    <div><span>Model Version</span><strong><?= e($recursive['version']) ?></strong></div>
                    <div><span>Model Run ID</span><strong><?= e((string) $recursive['model_run_id']) ?></strong></div>
                    <div><span>Input Start</span><strong><?= e($formatDate($recursive['input_start_date'])) ?></strong></div>
                    <div><span>Input End</span><strong><?= e($formatDate($recursive['input_end_date'])) ?></strong></div>
                    <div><span>Window Size</span><strong><?= e((string) $recursive['window_size']) ?></strong></div>
                    <div><span>Feature</span><strong><?= e($recursive['feature']) ?></strong></div>
                    <div><span>Horizon</span><strong><?= e((string) $recursive['horizon']) ?></strong></div>
                    <div><span>Prediction Count</span><strong><?= e((string) $recursive['prediction_count']) ?></strong></div>
                    <div><span>Strategy</span><strong><?= e($recursive['strategy']) ?></strong></div>
                    <div><span>P+1</span><strong><?= e($formatPrice($recursive['p1'])) ?></strong></div>
                    <div><span>P+N</span><strong><?= e($formatPrice($recursive['pn'])) ?></strong></div>
                    <div><span>Prediction Timestamp</span><strong><?= e($formatTimestamp($recursive['prediction_timestamp'])) ?></strong></div>
                    <div><span>Artifact Model</span><strong><?= e($recursive['artifact_model_status']) ?></strong></div>
                    <div><span>Artifact Scaler</span><strong><?= e($recursive['artifact_scaler_status']) ?></strong></div>
                    <div><span>Status</span><strong><?= e($recursive['overall_status']) ?></strong></div>
                    <div><span>Model Final</span><strong><?= e($recursive['model_sync']) ?></strong></div>
                    <div><span>Recursive Validation</span><strong><?= e($recursive['recursive_validation']) ?></strong></div>
                    <div><span>Window Validation</span><strong><?= e($recursive['window_validation']) ?></strong></div>
                    <div><span>Prediction Count Validation</span><strong><?= e($recursive['prediction_count_validation']) ?></strong></div>
                </div>
            </section>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong><?= e($recursive['message'] ?? 'Model aktif belum tersedia.') ?></strong><small>Jalankan prediksi operasional melalui menu Prediksi agar Tabel 4.10 dapat dibuat dari riwayat aktual.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const recursivePredictionCopyButton = document.getElementById('copyRecursivePredictionTable');
if (recursivePredictionCopyButton) {
    recursivePredictionCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#recursivePredictionTable tr'));
        const text = ['Tabel 4.10', 'Format Hasil Prediksi Recursive Multi-Periode', '']
            .concat(rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')))
            .join('\n');
        await navigator.clipboard.writeText(text);
        recursivePredictionCopyButton.textContent = 'Tersalin';
        setTimeout(() => recursivePredictionCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
