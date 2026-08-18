<?php
$screenshot = (($_GET['screenshot'] ?? '') === '1');
$statusClass = ($management['status'] ?? '') === 'ok' ? 'ok' : (($management['status'] ?? '') === 'warning' ? 'warn' : '');
$final = $management['final'] ?? null;
$formatMetric = fn ($value, int $decimals) => $value !== null ? number_format((float) $value, $decimals, '.', '') : '-';
$formatComma = fn ($value, int $decimals) => number_format((float) $value, $decimals, ',', '');
?>

<div class="thesis-doc-shell research-doc-shell model-management-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/parameter-model">Tabel 4.6</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training">Tabel 4.7</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/training-loss">Gambar 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/log-training">Gambar 4.5</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/manajemen-model?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyModelManagementTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel model-management-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Gambar 4.4</p>
                <h2>Ringkasan Manajemen dan Riwayat Training Model</h2>
                <p class="card-note no-screenshot">Data diambil dari riwayat training, metadata model, metrik evaluasi, dan artifact model aktif.</p>
            </div>
            <?php if ($final): ?>
                <span class="thesis-model-chip">Model aktif saat ini: <?= e($final['version']) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($management['message'] ?? 'Model final belum tersedia.') ?></strong>
            <small>Jumlah training run: <?= e((string) ($management['total_runs'] ?? 0)) ?>; training berhasil: <?= e((string) ($management['success_runs'] ?? 0)) ?>.</small>
        </div>

        <?php if (!empty($management['available']) && $final): ?>
            <section class="model-final-summary">
                <div class="model-final-heading">
                    <div>
                        <p class="eyebrow">Model Final / Model Aktif</p>
                        <h3><?= e($final['name']) ?></h3>
                    </div>
                    <span class="model-active-chip doc-active-chip">AKTIF</span>
                </div>
                <div class="model-detail-grid model-final-grid">
                    <div><span>Versi</span><strong><?= e($final['version']) ?></strong></div>
                    <div><span>Status</span><strong><?= e($final['status_label']) ?></strong></div>
                    <div><span>Model</span><strong>BiGRU</strong></div>
                    <div><span>Window Size</span><strong><?= e((string) $final['window_size']) ?></strong></div>
                    <div><span>GRU Units</span><strong><?= e((string) $final['units']) ?></strong></div>
                    <div><span>Dropout</span><strong><?= e($formatComma($final['dropout'], 1)) ?></strong></div>
                    <div><span>Batch Size</span><strong><?= e((string) $final['batch_size']) ?></strong></div>
                    <div><span>Epoch Aktual</span><strong><?= e((string) $final['actual_epochs']) ?></strong></div>
                    <div><span>Learning Rate</span><strong><?= e($formatComma($final['learning_rate'], 3)) ?></strong></div>
                    <div><span>MAE</span><strong><?= e($formatMetric($final['mae'], 6)) ?></strong></div>
                    <div><span>RMSE</span><strong><?= e($formatMetric($final['rmse'], 6)) ?></strong></div>
                    <div><span>MAPE</span><strong><?= e($formatMetric($final['mape'], 4)) ?>%</strong></div>
                    <div><span>Waktu Training</span><strong><?= e($final['formatted_duration']) ?></strong></div>
                    <div><span>Status Aktif</span><strong>AKTIF</strong></div>
                </div>
            </section>

            <div class="research-table-wrap model-management-table-wrap">
                <table id="modelManagementTable" class="research-table model-management-table">
                    <thead>
                        <tr>
                            <th>Versi</th>
                            <th>Status</th>
                            <th>Window</th>
                            <th>Units</th>
                            <th>Dropout</th>
                            <th>Batch</th>
                            <th>Epoch</th>
                            <th>LR</th>
                            <th>MAE</th>
                            <th>RMSE</th>
                            <th>MAPE</th>
                            <th>Waktu</th>
                            <th>Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($management['rows'] as $row): ?>
                        <tr class="<?= $row['is_active'] ? 'active-model-row' : '' ?>">
                            <td data-label="Versi"><strong><?= e($row['version']) ?></strong></td>
                            <td data-label="Status"><span class="status-pill <?= $row['status'] === 'success' ? 'ok' : ($row['status'] === 'running' ? 'warn' : '') ?>"><?= e($row['status_label']) ?></span></td>
                            <td data-label="Window"><?= e((string) $row['window_size']) ?></td>
                            <td data-label="Units"><?= e((string) $row['units']) ?></td>
                            <td data-label="Dropout"><?= e($formatComma($row['dropout'], 1)) ?></td>
                            <td data-label="Batch"><?= e((string) $row['batch_size']) ?></td>
                            <td data-label="Epoch"><?= e((string) $row['actual_epochs']) ?></td>
                            <td data-label="LR"><?= e($formatComma($row['learning_rate'], 3)) ?></td>
                            <td data-label="MAE"><?= e($formatMetric($row['mae'], 6)) ?></td>
                            <td data-label="RMSE"><?= e($formatMetric($row['rmse'], 6)) ?></td>
                            <td data-label="MAPE"><?= e($formatMetric($row['mape'], 4)) ?>%</td>
                            <td data-label="Waktu"><?= e($row['formatted_duration']) ?></td>
                            <td data-label="Aktif"><?= $row['is_active'] ? 'Aktif' : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="model-management-validation no-screenshot">
                <div>
                    <h3>Output Data Dokumentasi</h3>
                    <div class="model-detail-grid">
                        <div><span>Model Final</span><strong><?= e($final['version']) ?></strong></div>
                        <div><span>Model Run ID</span><strong><?= e((string) $final['id']) ?></strong></div>
                        <div><span>Status</span><strong><?= e($final['status_label']) ?></strong></div>
                        <div><span>Active</span><strong><?= $final['is_active'] ? 'Ya' : 'Tidak' ?></strong></div>
                        <div><span>Window Size</span><strong><?= e((string) $final['window_size']) ?></strong></div>
                        <div><span>GRU Units</span><strong><?= e((string) $final['units']) ?></strong></div>
                        <div><span>Dropout</span><strong><?= e($formatComma($final['dropout'], 1)) ?></strong></div>
                        <div><span>Batch Size</span><strong><?= e((string) $final['batch_size']) ?></strong></div>
                        <div><span>Configured Epoch</span><strong><?= e((string) $final['configured_epochs']) ?></strong></div>
                        <div><span>Actual Epoch</span><strong><?= e((string) $final['actual_epochs']) ?></strong></div>
                        <div><span>Learning Rate</span><strong><?= e($formatComma($final['learning_rate'], 3)) ?></strong></div>
                        <div><span>Training Duration</span><strong><?= e($final['formatted_duration']) ?></strong></div>
                    </div>
                </div>
                <div>
                    <h3>Validasi</h3>
                    <div class="model-detail-grid">
                        <div><span>MAE</span><strong><?= e($formatMetric($final['mae'], 6)) ?></strong></div>
                        <div><span>RMSE</span><strong><?= e($formatMetric($final['rmse'], 6)) ?></strong></div>
                        <div><span>MAPE</span><strong><?= e($formatMetric($final['mape'], 4)) ?>%</strong></div>
                        <div><span>Artifact Model</span><strong><?= e($management['model_artifact']) ?></strong></div>
                        <div><span>Artifact Scaler</span><strong><?= e($management['scaler_artifact']) ?></strong></div>
                        <div><span>Metadata</span><strong><?= e($management['metadata_artifact']) ?></strong></div>
                        <div><span>Tabel 4.6</span><strong><?= e($management['table46_sync']) ?></strong></div>
                        <div><span>Tabel 4.7</span><strong><?= e($management['table47_sync']) ?></strong></div>
                        <div><span>Metrik Evaluasi</span><strong><?= e($management['metrics_sync']) ?></strong></div>
                        <div><span>Run Ditampilkan</span><strong><?= e((string) $management['displayed_count']) ?></strong></div>
                    </div>
                </div>
            </div>

            <p class="thesis-caption">Gambar 4.4 Ringkasan Manajemen dan Riwayat Training Model. Sumber: Dokumen Pribadi (2026)</p>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Model final belum tersedia.</strong><small>Jalankan training model hingga sukses untuk menampilkan ringkasan manajemen model.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const modelManagementCopyButton = document.getElementById('copyModelManagementTable');
if (modelManagementCopyButton) {
    modelManagementCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#modelManagementTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        modelManagementCopyButton.textContent = 'Tersalin';
        setTimeout(() => modelManagementCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
