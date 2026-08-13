<?php if (!empty($models)): ?>
<?php
$selectedModel = $selectedModel ?? ($activeModel ?: $models[0]);
$latestPrediction = $predictions[0] ?? null;
?>
<section class="panel prediction-panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Prediksi</p>
            <h2>Periode Perdagangan Berikutnya</h2>
            <p class="card-note">Gunakan model terlatih untuk memprediksi satu periode berikutnya.</p>
        </div>
        <?php if ($activeModel): ?>
            <span class="model-active-chip">Aktif: <?= e($activeModel['version']) ?></span>
        <?php endif; ?>
    </div>

    <form method="post" action="/predictions" class="prediction-form">
        <?= csrf_field() ?>

        <div class="model-selector-row">
            <label class="model-select-field">
                <span>Pilih Model</span>
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
                            <?= e($model['version']) ?><?= $model['is_active'] ? ' • aktif' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="button-primary prediction-submit">Jalankan Prediksi</button>
        </div>

        <div class="selected-model-summary">
            <div>
                <span>Model terpilih</span>
                <strong data-selected-model="version"><?= e($selectedModel['version']) ?></strong>
            </div>
            <span class="selected-status-pill" data-selected-model="status"><?= $selectedModel['is_active'] ? 'Aktif' : 'Tersedia' ?></span>
        </div>

        <div class="model-detail-grid" aria-live="polite">
            <div><span>Model</span><strong data-model-detail="version"><?= e($selectedModel['version']) ?></strong></div>
            <div><span>Window</span><strong data-model-detail="window"><?= e($selectedModel['window_size']) ?> observasi</strong></div>
            <div><span>Units</span><strong data-model-detail="units"><?= e($selectedModel['units']) ?></strong></div>
            <div><span>Dropout</span><strong data-model-detail="dropout"><?= e($selectedModel['dropout']) ?></strong></div>
            <div><span>Batch</span><strong data-model-detail="batch"><?= e($selectedModel['batch_size']) ?></strong></div>
            <div><span>Epoch</span><strong data-model-detail="epochs"><?= e($selectedModel['actual_epochs'] ?? $selectedModel['configured_epochs']) ?></strong></div>
            <div><span>Learning Rate</span><strong data-model-detail="learning"><?= e($selectedModel['learning_rate']) ?></strong></div>
            <div><span>Trained</span><strong data-model-detail="trained"><?= e(format_indonesian_date($selectedModel['trained_at'] ?? null, true)) ?></strong></div>
        </div>

        <div class="data-detail-grid" aria-live="polite">
            <div class="info-box">
                <span>Data historis terakhir</span>
                <strong data-model-detail="latest"><?= e(format_indonesian_date($targetContext['latest_date'] ?? null)) ?></strong>
            </div>
            <div class="info-box">
                <span>Rentang masukan</span>
                <strong><span data-model-detail="inputStart"><?= e(format_indonesian_date($targetContext['input_start_date'] ?? null)) ?></span> s.d. <span data-model-detail="inputEnd"><?= e(format_indonesian_date($targetContext['input_end_date'] ?? null)) ?></span></strong>
            </div>
            <div class="info-box">
                <span>Observasi tersedia</span>
                <strong data-model-detail="available"><?= e((int)($modelContexts[(int)($selectedModel['id'] ?? 0)]['available_records'] ?? 0)) ?></strong>
            </div>
        </div>

        <?php if (!($targetContext['has_enough_data'] ?? false)): ?>
            <p class="alert">Data historis belum mencukupi. Model membutuhkan minimal <?= e($selectedModel['window_size']) ?> observasi harga penutupan untuk melakukan prediksi.</p>
        <?php endif; ?>
    </form>
</section>

<style>
.prediction-form { display:flex; flex-direction:column; gap:18px; }
.model-selector-row { display:flex; align-items:flex-end; gap:12px; }
.model-select-field { display:flex; flex-direction:column; gap:6px; flex:1; }
.model-select-field span { color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase; }
.model-select-field select {
    width:100%; border:1px solid var(--line); border-radius:10px; background:#fff; padding:10px 12px; font-size:14px; font-weight:600; color:var(--ink);
}
.prediction-submit { white-space:nowrap; }
.selected-model-summary {
    display:flex; justify-content:space-between; align-items:center; gap:12px; border:1px solid var(--line); border-radius:12px; background:var(--panel-soft); padding:12px 14px;
}
.selected-model-summary > div { display:flex; flex-direction:column; gap:4px; }
.selected-model-summary span { color:var(--muted); font-size:12px; font-weight:700; text-transform:uppercase; }
.selected-model-summary strong { font-size:18px; }
.selected-status-pill {
    display:inline-flex; align-items:center; justify-content:center; height:30px; padding:0 10px; border-radius:999px; border:1px solid rgba(13,148,136,.25); background:rgba(13,148,136,.08); color:var(--primary); font-weight:800; font-size:12px;
}
.model-detail-grid, .data-detail-grid { display:grid; gap:12px; grid-template-columns: repeat(4, minmax(0,1fr)); }
.model-detail-grid > div, .data-detail-grid > div {
    border:1px solid var(--line); border-radius:12px; background:var(--panel-soft); padding:12px 14px;
}
.model-detail-grid span, .data-detail-grid span { display:block; color:var(--muted); font-size:11px; font-weight:800; text-transform:uppercase; margin-bottom:6px; }
.model-detail-grid strong, .data-detail-grid strong { display:block; color:var(--ink); font-size:14px; line-height:1.5; }
.info-box strong { line-height:1.5; }
@media (max-width: 900px){ .model-detail-grid, .data-detail-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
@media (max-width: 640px){ .model-selector-row { flex-direction:column; align-items:stretch; } .model-detail-grid, .data-detail-grid { grid-template-columns:1fr; } .selected-model-summary { flex-direction:column; align-items:flex-start; } }
</style>
<script>
const modelSelect = document.getElementById('prediction-model-select');
if (modelSelect) {
    const fields = document.querySelectorAll('[data-model-detail]');
    const selectedFields = document.querySelectorAll('[data-selected-model]');
    const updateModelDetails = () => {
        const option = modelSelect.selectedOptions[0];
        if (!option) return;

        fields.forEach((field) => {
            const key = field.dataset.modelDetail;
            field.textContent = option.dataset[key] || '-';
        });

        selectedFields.forEach((field) => {
            const key = field.dataset.selectedModel;
            field.textContent = option.dataset[key] || '-';
        });
    };
    modelSelect.addEventListener('change', updateModelDetails);
    updateModelDetails();
}
</script>
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
        <div><span>Input Start Date</span><strong><?= e(format_indonesian_date($latestPrediction['input_start_date'] ?? null)) ?></strong></div>
        <div><span>Input End Date</span><strong><?= e(format_indonesian_date($latestPrediction['input_end_date'] ?? null)) ?></strong></div>
        <div><span>Prediction Created At</span><strong><?= e(format_indonesian_date($latestPrediction['created_at'] ?? null, true)) ?></strong></div>
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
                    <td data-label="Input Period"><?= e(format_indonesian_date($row['input_start_date'] ?? null)) ?> - <?= e(format_indonesian_date($row['input_end_date'] ?? null)) ?></td>
                    <td data-label="Target">Periode Berikutnya</td>
                    <td data-label="Predicted Close"><?= e($row['predicted_close']) ?></td>
                    <td data-label="Created At"><?= e(format_indonesian_date($row['created_at'] ?? null, true)) ?></td>
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
            const key = field.dataset.modelDetail;
            field.textContent = option.dataset[key] || '-';
        });
        selectedFields.forEach((field) => {
            const key = field.dataset.selectedModel;
            field.textContent = option.dataset[key] || '-';
        });
    };
    modelSelect.addEventListener('change', updateModelDetails);
}
</script>
