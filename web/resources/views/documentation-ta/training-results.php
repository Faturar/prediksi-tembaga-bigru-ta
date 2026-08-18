<?php
$modelVersion = $results['version'] ?? ($finalModel['version'] ?? null);
$statusClass = ($results['status'] ?? '') === 'ok' ? 'ok' : (($results['status'] ?? '') === 'warning' ? 'warn' : '');
?>

<div class="thesis-doc-shell research-doc-shell training-results-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian">Tabel 4.2</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/pembagian-dataset">Tabel 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/normalisasi">Tabel 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/sliding-window">Tabel 4.5</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/parameter-model">Tabel 4.6</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/training-loss">Gambar 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/manajemen-model">Gambar 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-test">Tabel 4.8 / Gambar 4.6</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyTrainingResultsTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel training-results-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.7</p>
                <h2>Ringkasan Hasil Pelatihan Model</h2>
                <p class="card-note no-screenshot">Nilai berasal dari model run final, metadata training, metrik, dan artifact model/scaler.</p>
            </div>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip">Model Version: <?= e($modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($results['message'] ?? 'Model final belum tersedia.') ?></strong>
            <small>Jumlah data latih adalah observasi sebelum sliding window; jumlah sequence train adalah hasil windowing.</small>
        </div>

        <?php if (!empty($results['available'])): ?>
            <div class="research-table-wrap">
                <table id="trainingResultsTable" class="research-table training-results-table">
                    <thead>
                        <tr>
                            <th>Informasi</th>
                            <th>Hasil Final</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results['rows'] as $row): ?>
                        <tr>
                            <td data-label="Informasi"><strong><?= e($row['info']) ?></strong></td>
                            <td data-label="Hasil Final"><?= e($row['value']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="training-results-meta no-screenshot">
                <div>
                    <h3>Informasi Tambahan</h3>
                    <div class="model-detail-grid">
                        <div><span>Model Run ID</span><strong><?= e((string) $results['model_run_id']) ?></strong></div>
                        <div><span>Status</span><strong><?= e($results['model_status']) ?></strong></div>
                        <div><span>Active</span><strong><?= $results['is_active'] ? 'Ya' : 'Tidak' ?></strong></div>
                        <div><span>Dataset Period</span><strong><?= e($results['dataset_period']) ?></strong></div>
                        <div><span>Train Period</span><strong><?= e($results['train_period']) ?></strong></div>
                        <div><span>Test Period</span><strong><?= e($results['test_period']) ?></strong></div>
                        <div><span>Total Records</span><strong><?= e(number_format((int) ($results['total_records'] ?? 0), 0, ',', '.')) ?></strong></div>
                        <div><span>Train Observations</span><strong><?= e(number_format((int) ($results['train_observations'] ?? 0), 0, ',', '.')) ?></strong></div>
                        <div><span>Test Observations</span><strong><?= e(number_format((int) ($results['test_observations'] ?? 0), 0, ',', '.')) ?></strong></div>
                        <div><span>Train Samples</span><strong><?= e(number_format((int) ($results['train_samples'] ?? 0), 0, ',', '.')) ?></strong></div>
                        <div><span>Test Samples</span><strong><?= e(number_format((int) ($results['test_samples'] ?? 0), 0, ',', '.')) ?></strong></div>
                        <div><span>Window Size</span><strong><?= e((string) $results['window_size']) ?></strong></div>
                        <div><span>Configured Epoch</span><strong><?= e((string) $results['configured_epochs']) ?></strong></div>
                        <div><span>Actual Epoch</span><strong><?= e((string) $results['actual_epochs']) ?></strong></div>
                        <div><span>Final Training Loss</span><strong><?= e($results['final_training_loss'] !== null ? number_format((float) $results['final_training_loss'], 8, '.', '') : '-') ?></strong></div>
                        <div><span>Training Duration</span><strong><?= e($results['formatted_duration']) ?></strong></div>
                    </div>
                </div>
                <div>
                    <h3>Validasi Artifact dan Sinkronisasi</h3>
                    <div class="model-detail-grid">
                        <div><span>Model Artifact</span><strong><?= e($results['model_artifact_status']) ?></strong></div>
                        <div><span>Scaler Artifact</span><strong><?= e($results['scaler_artifact_status']) ?></strong></div>
                        <div><span>Metadata Artifact</span><strong><?= e($results['metadata_artifact_status']) ?></strong></div>
                        <div><span>Artifact Consistency</span><strong><?= e($results['artifact_consistency']) ?></strong></div>
                        <div><span>Tabel 4.3</span><strong><?= e($results['table43_sync']) ?></strong></div>
                        <div><span>Tabel 4.5</span><strong><?= e($results['table45_sync']) ?></strong></div>
                        <div><span>Tabel 4.6</span><strong><?= e($results['table46_sync']) ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="research-notes training-results-source">
                <strong>Sumber</strong>
                <p>Hasil pelatihan model BiGRU final (2026). Versi model dari `model_runs.version`/metadata, jumlah data latih dari split index aktual, sequence train dari `train_samples`, epoch dari `actual_epochs`, loss dari `final_training_loss`, durasi dari `training_duration_seconds`, dan artifact dari `model_path` serta `scaler_path`.</p>
            </div>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Model final belum tersedia.</strong><small>Jalankan training model hingga sukses untuk menghasilkan ringkasan pelatihan final.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const trainingResultsCopyButton = document.getElementById('copyTrainingResultsTable');
if (trainingResultsCopyButton) {
    trainingResultsCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#trainingResultsTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        trainingResultsCopyButton.textContent = 'Tersalin';
        setTimeout(() => trainingResultsCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
