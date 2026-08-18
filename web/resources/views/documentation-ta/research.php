<?php
$screenshot = (($_GET['screenshot'] ?? '') === '1');
$datasetPeriod = $summary['start_date'] && $summary['end_date']
    ? format_indonesian_date($summary['start_date']) . ' - ' . format_indonesian_date($summary['end_date'])
    : '-';
$modelName = $finalModel['model_name'] ?? null;
$modelVersion = $finalModel['version'] ?? null;
$syncClass = $sync['status'] === 'ok' ? 'ok' : ($sync['status'] === 'warning' ? 'warn' : '');
?>

<div class="thesis-doc-shell research-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-ta">Dokumentasi Gambar</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/pembagian-dataset">Tabel 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/normalisasi">Tabel 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyResearchTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.2</p>
                <h2>Ringkasan Prapemrosesan Data</h2>
                <p class="card-note">Nilai dihitung dari database final, metadata training, dan source preprocessing yang digunakan sistem.</p>
            </div>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip">Model final: <?= e($modelName ?: $modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($syncClass) ?>">
            <strong><?= e($sync['message']) ?></strong>
            <?php if (!$finalModel): ?>
                <small>Jalankan training melalui menu Model untuk menghasilkan metadata final.</small>
            <?php else: ?>
                <small>Perbandingan memakai total_records, dataset_start_date, dan dataset_end_date model final.</small>
            <?php endif; ?>
        </div>

        <div class="thesis-meta-grid research-meta-grid">
            <div><span>Jumlah Observasi Final</span><strong><?= e((string) $summary['total_records']) ?></strong></div>
            <div><span>Periode Dataset</span><strong><?= e($datasetPeriod) ?></strong></div>
            <div><span>Duplikat Tanggal</span><strong><?= e((string) $summary['duplicate_date_count']) ?></strong></div>
            <div><span>Missing Close</span><strong><?= e((string) $summary['missing_close']) ?></strong></div>
            <div><span>Missing Volume</span><strong><?= e((string) $summary['missing_volume']) ?></strong></div>
        </div>

        <div class="research-table-wrap">
            <table id="researchPreprocessingTable" class="research-table">
                <thead>
                    <tr>
                        <th>Pemeriksaan</th>
                        <th>Kondisi Sampel</th>
                        <th>Hasil Final</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td data-label="Pemeriksaan"><strong><?= e($row['check']) ?></strong></td>
                        <td data-label="Kondisi Sampel"><?= e($row['sample']) ?></td>
                        <td data-label="Hasil Final"><?= e($row['final']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <section class="research-model-section">
            <h3>Validasi Terhadap Training Final</h3>
            <?php if ($finalModel): ?>
                <div class="model-detail-grid">
                    <div><span>Nama Model</span><strong><?= e($modelName ?? '-') ?></strong></div>
                    <div><span>Model Version</span><strong><?= e($modelVersion ?? '-') ?></strong></div>
                    <div><span>Dataset Start</span><strong><?= e(format_indonesian_date($sync['metadata_dataset_start_date'] ?? null)) ?></strong></div>
                    <div><span>Dataset End</span><strong><?= e(format_indonesian_date($sync['metadata_dataset_end_date'] ?? null)) ?></strong></div>
                    <div><span>Total Records</span><strong><?= e((string) ($sync['metadata_total_records'] ?? '-')) ?></strong></div>
                    <div><span>Train Samples</span><strong><?= e((string) ($metadata['train_samples'] ?? $finalModel['train_samples'] ?? '-')) ?></strong></div>
                    <div><span>Test Samples</span><strong><?= e((string) ($metadata['test_samples'] ?? $finalModel['test_samples'] ?? '-')) ?></strong></div>
                    <div><span>Window Size</span><strong><?= e((string) ($metadata['window_size'] ?? $finalModel['window_size'] ?? '-')) ?></strong></div>
                </div>
            <?php else: ?>
                <div class="empty-chart historical-empty"><strong>Model final belum tersedia.</strong><small>Data validasi model akan muncul setelah training sukses.</small></div>
            <?php endif; ?>
        </section>

        <div class="research-notes">
            <strong>Catatan</strong>
            <p>Kondisi sampel sebelum import tidak disimpan oleh sistem setelah import, sehingga nilai kondisi sampel yang tidak dapat dihitung dinyatakan secara eksplisit. Proses model memakai data final dari `copper_prices` yang dibaca ascending melalui repository dan disortir kembali pada preprocessing ML service.</p>
        </div>
    </section>
</div>

<script>
const copyButton = document.getElementById('copyResearchTable');
if (copyButton) {
    copyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#researchPreprocessingTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        copyButton.textContent = 'Tersalin';
        setTimeout(() => copyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
