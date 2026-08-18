<?php
$modelName = $finalModel['model_name'] ?? null;
$modelVersion = $finalModel['version'] ?? null;
$statusClass = ($parameters['status'] ?? '') === 'ok' ? 'ok' : (($parameters['status'] ?? '') === 'warning' ? 'warn' : '');
?>

<div class="thesis-doc-shell research-doc-shell model-parameters-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian">Tabel 4.2</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/pembagian-dataset">Tabel 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/normalisasi">Tabel 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/sliding-window">Tabel 4.5</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training">Tabel 4.7</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/manajemen-model">Gambar 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/parameter-model?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copyModelParametersTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel model-parameters-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.6</p>
                <h2>Parameter Model BiGRU</h2>
                <p class="card-note no-screenshot">Parameter diambil dari model run final, metadata training, dan source code pembentukan model.</p>
            </div>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip">Model Version: <?= e($modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($parameters['message'] ?? 'Model final belum tersedia.') ?></strong>
            <small>Sinkronisasi dicek terhadap arsitektur Gambar 4.2: Input (30,1), Bidirectional GRU (64), Dropout (0,2), Dense(1), dan prediksi Close Price.</small>
        </div>

        <?php if (!empty($parameters['available'])): ?>
            <div class="research-table-wrap">
                <table id="modelParametersTable" class="research-table model-parameters-table">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Konfigurasi / Hasil Final</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($parameters['rows'] as $row): ?>
                        <tr>
                            <td data-label="Parameter"><strong><?= e($row['parameter']) ?></strong></td>
                            <td data-label="Konfigurasi / Hasil Final"><?= e($row['value']) ?></td>
                            <td data-label="Keterangan"><?= e($row['note']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="model-parameter-meta no-screenshot">
                <div>
                    <h3>Model Final</h3>
                    <div class="model-detail-grid">
                        <div><span>Nama Model</span><strong><?= e($modelName ?? '-') ?></strong></div>
                        <div><span>Model Version</span><strong><?= e($modelVersion ?? '-') ?></strong></div>
                        <div><span>Status Model</span><strong><?= e($finalModel['status'] ?? '-') ?></strong></div>
                        <div><span>Model Type</span><strong><?= e($metadata['model_type'] ?? $finalModel['model_name'] ?? 'BiGRU') ?></strong></div>
                        <div><span>Configured Epoch</span><strong><?= e((string) $parameters['configured_epochs']) ?></strong></div>
                        <div><span>Actual Epoch</span><strong><?= e((string) $parameters['actual_epochs']) ?></strong></div>
                    </div>
                </div>
                <div>
                    <h3>Sumber Nilai Internal</h3>
                    <div class="model-source-list">
                        <span>Window Size: metadata / preprocessing</span>
                        <span>Input Feature: preprocessing</span>
                        <span>BiGRU Layer: build_bigru()</span>
                        <span>GRU Units: model configuration / metadata</span>
                        <span>Dropout: model run / build_bigru()</span>
                        <span>Batch Size: model run</span>
                        <span>Epoch: configured_epochs / actual_epochs</span>
                        <span>Learning Rate: model run</span>
                        <span>Shuffle: model.fit()</span>
                        <span>Loss & Optimizer: model.compile()</span>
                        <span>Output Model: Dense layer</span>
                        <span>Horizon: PredictionService / request validation</span>
                    </div>
                </div>
            </div>

            <div class="research-notes model-parameters-source">
                <strong>Sumber</strong>
                <p>Parameter model final dibaca dari `model_runs`, metadata training final, `ml-service/app/ml/model.py`, `ml-service/app/services/training.py`, `ml-service/app/services/prediction.py`, dan `ml-service/app/schemas/ml.py`.</p>
            </div>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Model final belum tersedia.</strong><small>Jalankan training model terlebih dahulu untuk menghasilkan parameter final.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const modelParametersCopyButton = document.getElementById('copyModelParametersTable');
if (modelParametersCopyButton) {
    modelParametersCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#modelParametersTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        modelParametersCopyButton.textContent = 'Tersalin';
        setTimeout(() => modelParametersCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
