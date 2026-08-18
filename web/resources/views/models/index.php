<?php $hasRunningModel = !empty(array_filter($models, fn ($model) => ($model['status'] ?? '') === 'running')); ?>

<form method="post" action="/models/train" class="grid-form model-form">
    <?= csrf_field() ?>
    <label class="field-model-name">Nama Model <input type="text" name="model_name" maxlength="100" placeholder="Kosongkan untuk nama otomatis"></label>
    <label class="field-window">Window <input type="number" name="window_size" value="30" min="2" required></label>
    <label class="field-units">Units <input type="number" name="units" value="64" min="1" required></label>
    <label class="field-dropout">Dropout <input type="number" step="0.01" name="dropout" value="0.2" min="0" max="0.9"></label>
    <label class="field-batch">Batch <input type="number" name="batch_size" value="32" min="1"></label>
    <label class="field-epochs">Epochs <input type="number" name="epochs" value="50" min="1"></label>
    <label class="field-learning">Learning Rate <input type="number" step="0.0001" name="learning_rate" value="0.001"></label>
    <button type="submit" class="full-width-button">Train Model</button>
</form>

<?php if ($hasRunningModel): ?>
<section class="panel training-running-panel">
    <strong>Training sedang berjalan.</strong>
    <p>Proses training berjalan di background. Buka tombol Lihat Log pada model berstatus running untuk memantau tahap training.</p>
</section>
<?php endif; ?>

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
            <thead><tr><th>Nama Model</th><th>Version</th><th>Status</th><th>Aktif</th><th>MAPE</th><th>Trained</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr>
                    <td data-label="Nama Model"><strong><?= e($model['model_name'] ?? 'BiGRU') ?></strong></td>
                    <td data-label="Version"><?= e($model['version']) ?></td>
                    <td data-label="Status"><span class="status-pill <?= $model['status'] === 'success' ? 'ok' : ($model['status'] === 'running' ? 'warn' : '') ?>"><?= e($model['status']) ?></span></td>
                    <td data-label="Aktif"><?= $model['is_active'] ? 'Ya' : '-' ?></td>
                    <td data-label="MAPE"><?= isset($model['mape']) ? e($model['mape']) . '%' : '-' ?></td>
                    <td data-label="Trained"><?= e(format_indonesian_date($model['trained_at'] ?? null, true)) ?></td>
                    <td data-label="Aksi">
                        <a class="button-secondary table-action-button" href="/models/detail?id=<?= e($model['id']) ?>">Detail</a>
                        <?php if ($model['has_log']): ?>
                            <a class="button-secondary table-action-button" href="/models/log?id=<?= e($model['id']) ?>">Lihat Log</a>
                        <?php endif; ?>
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

<?php if ($hasRunningModel): ?>
<script>
setTimeout(() => window.location.reload(), 15000);
</script>
<?php endif; ?>
