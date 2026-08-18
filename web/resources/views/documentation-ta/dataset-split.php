<?php
$modelName = $finalModel['model_name'] ?? null;
$modelVersion = $finalModel['version'] ?? null;
$syncClass = ($split['sync_status'] ?? '') === 'ok' ? 'ok' : (($split['sync_status'] ?? '') === 'warning' ? 'warn' : '');
?>

<div class="thesis-doc-shell research-doc-shell dataset-split-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian">Tabel 4.2</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/normalisasi">Tabel 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/sliding-window">Tabel 4.5</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/pembagian-dataset?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyDatasetSplitTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel dataset-split-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.3</p>
                <h2>Pembagian Dataset Final</h2>
                <p class="card-note no-screenshot">Jumlah observasi dihitung dari pembagian data mentah/final, bukan dari jumlah sequence sliding window.</p>
            </div>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip no-screenshot">Model final: <?= e($modelName ?: $modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($syncClass) ?> no-screenshot">
            <strong><?= e($split['sync_message'] ?? 'Model final belum tersedia.') ?></strong>
            <small>Metode pembagian mengikuti preprocessing Python: data kronologis, 80% awal untuk latih, 20% akhir untuk uji, tanpa pengacakan.</small>
        </div>

        <?php if (!empty($split['available'])): ?>
            <div class="research-table-wrap">
                <table id="datasetSplitTable" class="research-table dataset-split-table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Persentase</th>
                            <th>Jumlah Observasi</th>
                            <th>Periode</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($split['rows'] as $row): ?>
                        <tr>
                            <td data-label="Jenis"><strong><?= e($row['type']) ?></strong></td>
                            <td data-label="Persentase"><?= e($row['percentage']) ?></td>
                            <td data-label="Jumlah Observasi"><?= e(number_format((int) $row['observations'], 0, ',', '.')) ?></td>
                            <td data-label="Periode"><?= e($row['period']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="dataset-split-summary">
                <div>
                    <h3>Pembagian Data Mentah</h3>
                    <div class="model-detail-grid">
                        <div><span>Total Records</span><strong><?= e(number_format((int) $split['total_records'], 0, ',', '.')) ?></strong></div>
                        <div><span>Train Observations</span><strong><?= e(number_format((int) $split['train_observations'], 0, ',', '.')) ?></strong></div>
                        <div><span>Test Observations</span><strong><?= e(number_format((int) $split['test_observations'], 0, ',', '.')) ?></strong></div>
                        <div><span>Persentase Aktual</span><strong><?= e(number_format((float) $split['train_actual_percentage'], 2, ',', '.')) ?>% / <?= e(number_format((float) $split['test_actual_percentage'], 2, ',', '.')) ?>%</strong></div>
                    </div>
                </div>
                <div>
                    <h3>Setelah Sliding Window</h3>
                    <div class="model-detail-grid">
                        <div><span>Window Size</span><strong><?= e((string) $split['window_size']) ?></strong></div>
                        <div><span>Train Samples</span><strong><?= e(number_format((int) $split['train_samples'], 0, ',', '.')) ?></strong></div>
                        <div><span>Test Samples</span><strong><?= e(number_format((int) $split['test_samples'], 0, ',', '.')) ?></strong></div>
                        <div><span>Rasio Pembagian</span><strong>80% : 20%</strong></div>
                    </div>
                </div>
            </div>

            <div class="research-notes dataset-split-source">
                <strong>Sumber</strong>
                <p>Hasil pembagian dataset final (2026). Total records dan periode model dibaca dari metadata/model run final. Jumlah observasi latih/uji dihitung mengikuti `train_end_index = int(len(prices) * 0.8)` pada preprocessing Python. `train_samples` dan `test_samples` ditampilkan terpisah karena merupakan sequence setelah sliding window.</p>
            </div>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Model final belum tersedia.</strong><small>Jalankan training model terlebih dahulu untuk menghasilkan metadata pembagian dataset.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const splitCopyButton = document.getElementById('copyDatasetSplitTable');
if (splitCopyButton) {
    splitCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#datasetSplitTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        splitCopyButton.textContent = 'Tersalin';
        setTimeout(() => splitCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
