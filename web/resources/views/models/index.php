<form method="post" action="/models/train" class="grid-form model-form">
    <?= csrf_field() ?>
    <label class="field-window">Window <input type="number" name="window_size" value="30" min="2" required></label>
    <label class="field-units">Units <input type="number" name="units" value="64" min="1" required></label>
    <label class="field-dropout">Dropout <input type="number" step="0.01" name="dropout" value="0.2" min="0" max="0.9"></label>
    <label class="field-batch">Batch <input type="number" name="batch_size" value="32" min="1"></label>
    <label class="field-epochs">Epochs <input type="number" name="epochs" value="50" min="1"></label>
    <label class="field-learning">Learning Rate <input type="number" step="0.0001" name="learning_rate" value="0.001"></label>
    <button type="submit" class="full-width-button">Train Model</button>
</form>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Riwayat training</p>
            <h2>Daftar Model</h2>
        </div>
        <?php if (!empty($models)): ?>
            <form method="post" action="/models/reset" class="inline-form" onsubmit="return confirm('Reset semua data model, metrik, prediksi terkait, dan file artifact?')">
                <?= csrf_field() ?>
                <button type="submit" class="danger-button">Reset Model</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Version</th><th>Status</th><th>Aktif</th><th>Window</th><th>Units</th><th>Dropout</th><th>Batch</th><th>Epoch</th><th>LR</th><th>Dataset</th><th>MAE</th><th>RMSE</th><th>MAPE</th><th>Trained</th><th>Keterangan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr>
                    <td data-label="Version"><?= e($model['version']) ?></td>
                    <td data-label="Status"><?= e($model['status']) ?></td>
                    <td data-label="Aktif"><?= $model['is_active'] ? 'Ya' : '-' ?></td>
                    <td data-label="Window"><?= e($model['window_size']) ?></td>
                    <td data-label="Units"><?= e($model['units']) ?></td>
                    <td data-label="Dropout"><?= e($model['dropout']) ?></td>
                    <td data-label="Batch"><?= e($model['batch_size']) ?></td>
                    <td data-label="Epoch"><?= e($model['actual_epochs'] ?? $model['configured_epochs']) ?></td>
                    <td data-label="LR"><?= e($model['learning_rate']) ?></td>
                    <td data-label="Dataset"><?= $model['dataset_start_date'] && $model['dataset_end_date'] ? e($model['dataset_start_date'] . ' - ' . $model['dataset_end_date']) : '-' ?></td>
                    <td data-label="MAE"><?= e($model['mae'] ?? '-') ?></td>
                    <td data-label="RMSE"><?= e($model['rmse'] ?? '-') ?></td>
                    <td data-label="MAPE"><?= isset($model['mape']) ? e($model['mape']) . '%' : '-' ?></td>
                    <td data-label="Trained"><?= e($model['trained_at']) ?></td>
                    <td data-label="Keterangan"><?= $model['status'] === 'failed' ? e($model['error_message'] ?? '-') : '-' ?></td>
                    <td data-label="Aksi">
                        <?php if ($model['status'] === 'success' && !$model['is_active']): ?>
                            <form method="post" action="/models/activate" class="inline-form">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($model['id']) ?>">
                                <button type="submit">Aktifkan</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
