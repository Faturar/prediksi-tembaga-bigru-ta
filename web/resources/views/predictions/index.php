<?php if (!empty($models)): ?>
<?php
$selectedModel = $selectedModel ?? ($activeModel ?: $models[0]);
$latestPrediction = $predictions[0] ?? null;
?>
<section class="panel prediction-panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Forecast</p>
            <h2>Prediksi Periode Perdagangan Berikutnya</h2>
            <p>Model menggunakan <span data-model-detail="window"><?= e($selectedModel['window_size']) ?></span> observasi harga penutupan terakhir untuk memprediksi satu observasi perdagangan berikutnya.</p>
        </div>
        <?php if ($activeModel): ?>
            <span class="model-active-chip">Aktif: <?= e($activeModel['version']) ?></span>
        <?php endif; ?>
    </div>
    <form method="post" action="/predictions" class="prediction-form">
        <?= csrf_field() ?>
        <label class="model-select-field">Model Prediksi
            <select name="model_run_id" id="prediction-model-select" required>
                <?php foreach ($models as $model): ?>
                    <option
                        value="<?= e($model['id']) ?>"
                        data-version="<?= e($model['version']) ?>"
                        data-status="<?= $model['is_active'] ? 'Aktif' : 'Tersedia' ?>"
                        data-window="<?= e($model['window_size']) ?>"
                        data-units="<?= e($model['units']) ?>"
                        data-dropout="<?= e($model['dropout']) ?>"
                        data-batch="<?= e($model['batch_size']) ?>"
                        data-epochs="<?= e($model['actual_epochs'] ?? $model['configured_epochs']) ?>"
                        data-learning="<?= e($model['learning_rate']) ?>"
                        data-trained="<?= e($model['trained_at'] ?? '-') ?>"
                        data-latest="<?= e($modelContexts[(int) $model['id']]['latest_date'] ?? '-') ?>"
                        data-input-start="<?= e($modelContexts[(int) $model['id']]['input_start_date'] ?? '-') ?>"
                        data-input-end="<?= e($modelContexts[(int) $model['id']]['input_end_date'] ?? '-') ?>"
                        data-available="<?= e($modelContexts[(int) $model['id']]['available_records'] ?? 0) ?>"
                        <?= (int) $selectedModel['id'] === (int) $model['id'] ? 'selected' : '' ?>
                    >
                        <?= e($model['version']) ?><?= $model['is_active'] ? ' - aktif' : '' ?> | window <?= e($model['window_size']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="selected-model-summary">
            <div>
                <span>Model Terpilih</span>
                <strong data-selected-model="version"><?= e($selectedModel['version']) ?></strong>
            </div>
            <span class="selected-status-pill" data-selected-model="status"><?= $selectedModel['is_active'] ? 'Aktif' : 'Tersedia' ?></span>
        </div>
        <div class="model-detail-grid" aria-live="polite">
            <div><span>Window</span><strong data-model-detail="window"><?= e($selectedModel['window_size']) ?></strong></div>
            <div><span>Units</span><strong data-model-detail="units"><?= e($selectedModel['units']) ?></strong></div>
            <div><span>Dropout</span><strong data-model-detail="dropout"><?= e($selectedModel['dropout']) ?></strong></div>
            <div><span>Batch</span><strong data-model-detail="batch"><?= e($selectedModel['batch_size']) ?></strong></div>
            <div><span>Epoch</span><strong data-model-detail="epochs"><?= e($selectedModel['actual_epochs'] ?? $selectedModel['configured_epochs']) ?></strong></div>
            <div><span>Learning Rate</span><strong data-model-detail="learning"><?= e($selectedModel['learning_rate']) ?></strong></div>
            <div><span>Trained</span><strong data-model-detail="trained"><?= e($selectedModel['trained_at'] ?? '-') ?></strong></div>
        </div>
        <div class="model-detail-grid prediction-target-grid" aria-live="polite">
            <div><span>Prediksi untuk</span><strong>Periode Perdagangan Berikutnya</strong></div>
            <div><span>Fitur</span><strong>Close Price</strong></div>
            <div><span>Model</span><strong data-selected-model="version"><?= e($selectedModel['version']) ?></strong></div>
            <div><span>Window</span><strong><span data-model-detail="window"><?= e($selectedModel['window_size']) ?></span> observasi</strong></div>
            <div><span>Data historis terakhir</span><strong data-model-detail="latest"><?= e($targetContext['latest_date'] ?? '-') ?></strong></div>
            <div><span>Rentang Data Masukan</span><strong><span data-model-detail="inputStart"><?= e($targetContext['input_start_date'] ?? '-') ?></span> s.d. <span data-model-detail="inputEnd"><?= e($targetContext['input_end_date'] ?? '-') ?></span></strong></div>
        </div>
        <?php if (!($targetContext['has_enough_data'] ?? false)): ?>
            <p class="alert">Data historis belum mencukupi. Model membutuhkan minimal <?= e($selectedModel['window_size']) ?> observasi harga penutupan untuk melakukan prediksi.</p>
        <?php endif; ?>
        <button type="submit" class="full-width-button">Jalankan Prediksi Periode Berikutnya</button>
    </form>
</section>
<?php else: ?>
    <p class="alert">Belum ada model training yang sukses.</p>
<?php endif; ?>

<?php if (!empty($latestPrediction)): ?>
<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Hasil Terbaru</p>
            <h2>Hasil Prediksi Periode Perdagangan Berikutnya</h2>
        </div>
    </div>
    <div class="model-detail-grid">
        <div><span>Target</span><strong>Periode perdagangan berikutnya</strong></div>
        <div><span>Model Version</span><strong><?= e($latestPrediction['version']) ?></strong></div>
        <div><span>Nilai Prediksi Close</span><strong><?= e($latestPrediction['predicted_close']) ?></strong></div>
        <div><span>Window Size</span><strong><?= e($latestPrediction['window_size'] ?? '-') ?> observasi</strong></div>
        <div><span>Input Start Date</span><strong><?= e($latestPrediction['input_start_date']) ?></strong></div>
        <div><span>Input End Date</span><strong><?= e($latestPrediction['input_end_date']) ?></strong></div>
        <div><span>Prediction Created At</span><strong><?= e($latestPrediction['created_at']) ?></strong></div>
    </div>
</section>
<?php endif; ?>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">History</p>
            <h2>Riwayat Prediksi</h2>
        </div>
        <?php if (!empty($predictions)): ?>
            <form method="post" action="/predictions/reset" class="inline-form" onsubmit="return confirm('Reset semua data riwayat prediksi?')">
                <?= csrf_field() ?>
                <button type="submit" class="danger-button">Reset Prediksi</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Model Version</th><th>Input Period</th><th>Target</th><th>Predicted Close</th><th>Created At</th></tr></thead>
            <tbody>
            <?php foreach ($predictions as $row): ?>
                <tr>
                    <td data-label="ID"><?= e($row['id']) ?></td>
                    <td data-label="Model Version"><?= e($row['version']) ?></td>
                    <td data-label="Input Period"><?= e($row['input_start_date']) ?> - <?= e($row['input_end_date']) ?></td>
                    <td data-label="Target">Periode Berikutnya</td>
                    <td data-label="Predicted Close"><?= e($row['predicted_close']) ?></td>
                    <td data-label="Created At"><?= e($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
const modelSelect = document.getElementById('prediction-model-select');
if (modelSelect) {
    const fields = document.querySelectorAll('[data-model-detail]');
    const selectedFields = document.querySelectorAll('[data-selected-model]');
    const updateModelDetails = () => {
        const option = modelSelect.selectedOptions[0];
        fields.forEach((field) => {
            field.textContent = option.dataset[field.dataset.modelDetail] || '-';
        });
        selectedFields.forEach((field) => {
            field.textContent = option.dataset[field.dataset.selectedModel] || '-';
        });
    };
    modelSelect.addEventListener('change', updateModelDetails);
}
</script>
