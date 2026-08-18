<?php
$modelName = $finalModel['model_name'] ?? null;
$modelVersion = $finalModel['version'] ?? null;
$statusClass = ($normalization['status'] ?? '') === 'ok' ? 'ok' : (($normalization['status'] ?? '') === 'warning' ? 'warn' : '');
$hasOutsideRange = !empty(array_filter($normalization['rows'] ?? [], fn ($row) => !empty($row['outside_train_range'])));
?>

<div class="thesis-doc-shell research-doc-shell normalization-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian">Tabel 4.2</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/pembagian-dataset">Tabel 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/sliding-window">Tabel 4.5</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/normalisasi?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyNormalizationTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel normalization-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.4</p>
                <h2>Contoh Format Hasil Normalisasi</h2>
                <p class="card-note no-screenshot">Contoh dipilih deterministik dari train pertama, train terakhir, dan test pertama.</p>
            </div>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip no-screenshot">Model final: <?= e($modelName ?: $modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($normalization['message'] ?? 'Scaler model final belum tersedia.') ?></strong>
            <small>Normalisasi mengikuti MinMaxScaler yang di-fit hanya pada data latih dan dipakai untuk transform data latih serta data uji.</small>
        </div>

        <?php if (!empty($normalization['available'])): ?>
            <div class="research-table-wrap">
                <table id="normalizationTable" class="research-table normalization-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Close Asli</th>
                            <th>Close Normalisasi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($normalization['rows'] as $row): ?>
                        <tr>
                            <td data-label="Tanggal"><?= e(format_indonesian_date($row['date'])) ?></td>
                            <td data-label="Close Asli"><?= e(number_format((float) $row['close'], 6, '.', '')) ?></td>
                            <td data-label="Close Normalisasi"><?= e(number_format((float) $row['normalized'], 6, '.', '')) ?></td>
                            <td data-label="Keterangan"><?= e($row['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="normalization-info-grid">
                <div>
                    <h3>Parameter Scaler</h3>
                    <div class="model-detail-grid">
                        <div><span>Scaler</span><strong><?= e($normalization['scaler_name']) ?></strong></div>
                        <div><span>Feature Range</span><strong><?= e($normalization['feature_range']) ?></strong></div>
                        <div><span>Fit Scaler</span><strong><?= e($normalization['fit_scope']) ?></strong></div>
                        <div><span>Min Close Train</span><strong><?= e(number_format((float) $normalization['min_train'], 6, '.', '')) ?></strong></div>
                        <div><span>Max Close Train</span><strong><?= e(number_format((float) $normalization['max_train'], 6, '.', '')) ?></strong></div>
                        <div><span>Scaler Artifact</span><strong><?= !empty($normalization['scaler_available']) ? 'Tersedia' : 'Tidak tersedia' ?></strong></div>
                    </div>
                </div>
                <div class="no-screenshot">
                    <h3>Validasi Model Final</h3>
                    <div class="model-detail-grid">
                        <div><span>Model Version</span><strong><?= e($modelVersion ?? '-') ?></strong></div>
                        <div><span>Dataset Period</span><strong><?= e($normalization['dataset_start_date'] && $normalization['dataset_end_date'] ? format_indonesian_date($normalization['dataset_start_date']) . ' - ' . format_indonesian_date($normalization['dataset_end_date']) : '-') ?></strong></div>
                        <div><span>Train Period</span><strong><?= e($normalization['train_start_date'] && $normalization['train_end_date'] ? format_indonesian_date($normalization['train_start_date']) . ' - ' . format_indonesian_date($normalization['train_end_date']) : '-') ?></strong></div>
                        <div><span>Test Period</span><strong><?= e($normalization['test_start_date'] && $normalization['test_end_date'] ? format_indonesian_date($normalization['test_start_date']) . ' - ' . format_indonesian_date($normalization['test_end_date']) : '-') ?></strong></div>
                        <div><span>Window Size</span><strong><?= e((string) $normalization['window_size']) ?></strong></div>
                        <div><span>Status</span><strong><?= e($normalization['message']) ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="normalization-formula">
                <strong>Formula</strong>
                <code>X_normalized = (X - X_min_train) / (X_max_train - X_min_train)</code>
            </div>

            <div class="research-table-wrap no-screenshot">
                <table class="research-table normalization-validation-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Normalisasi Manual</th>
                            <th>Selisih</th>
                            <th>Status Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($normalization['rows'] as $row): ?>
                        <tr>
                            <td><?= e(format_indonesian_date($row['date'])) ?></td>
                            <td><?= e(number_format((float) $row['manual'], 6, '.', '')) ?></td>
                            <td><?= e(number_format((float) $row['difference'], 8, '.', '')) ?></td>
                            <td><?= $row['is_valid'] ? 'Valid - sesuai scaler training' : 'Warning - tidak sesuai scaler training' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($hasOutsideRange): ?>
                <div class="research-notes normalization-range-note">
                    <strong>Catatan Data Uji</strong>
                    <p>Nilai berada di luar rentang data latih, tetapi tetap valid karena scaler dibentuk hanya dari data latih.</p>
                </div>
            <?php endif; ?>

            <div class="research-notes normalization-source">
                <strong>Sumber</strong>
                <p>Hasil normalisasi dataset final. Dataset diurutkan kronologis, split 80/20 dilakukan tanpa shuffle, MinMaxScaler di-fit pada data latih saja, lalu scaler yang sama digunakan untuk transform data latih dan data uji.</p>
            </div>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong><?= e($normalization['message'] ?? 'Scaler model final belum tersedia.') ?></strong><small>Pastikan model final sukses training dan scaler artifact tersedia.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const normalizationCopyButton = document.getElementById('copyNormalizationTable');
if (normalizationCopyButton) {
    normalizationCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#normalizationTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        normalizationCopyButton.textContent = 'Tersalin';
        setTimeout(() => normalizationCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
