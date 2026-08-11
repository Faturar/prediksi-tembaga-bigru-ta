<?php if (!empty($models)): ?>
<?php
$selectedModel = $activeModel ?: $models[0];
?>
<section class="panel prediction-panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Forecast</p>
            <h2>Prediksi Periode Berikutnya</h2>
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
        <button type="submit" class="full-width-button">Jalankan Prediksi Periode Berikutnya</button>
    </form>
</section>
<?php else: ?>
    <p class="alert">Belum ada model training yang sukses.</p>
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
            <thead><tr><th>Model</th><th>Input Awal</th><th>Input Akhir</th><th>Periode</th><th>Prediksi Close</th><th>Dibuat</th></tr></thead>
            <tbody>
            <?php foreach ($predictions as $row): ?>
                <tr>
                    <td data-label="Model"><?= e($row['version']) ?></td>
                    <td data-label="Input Awal"><?= e($row['input_start_date']) ?></td>
                    <td data-label="Input Akhir"><?= e($row['input_end_date']) ?></td>
                    <td data-label="Periode">Periode berikutnya</td>
                    <td data-label="Prediksi Close"><?= e($row['predicted_close']) ?></td>
                    <td data-label="Dibuat"><?= e($row['created_at']) ?></td>
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
