<?php
$modelVersion = $loss['version'] ?? ($finalModel['version'] ?? null);
$statusClass = ($loss['status'] ?? '') === 'ok' ? 'ok' : (($loss['status'] ?? '') === 'warning' ? 'warn' : '');
?>

<div class="thesis-doc-shell research-doc-shell training-loss-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training">Tabel 4.7</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/manajemen-model">Gambar 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/training-loss?screenshot=1">Mode Screenshot</a>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel training-loss-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Gambar 4.3</p>
                <h2>Grafik Loss Pelatihan Model BiGRU</h2>
                <p class="card-note no-screenshot">Plot nilai training loss terhadap epoch dari model run final.</p>
            </div>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip">Model Version: <?= e($modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($loss['available'])): ?>
            <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
                <strong><?= e($loss['message']) ?></strong>
                <small>Validation loss tidak digunakan karena training final tidak memakai validation split.</small>
            </div>

            <div class="thesis-meta-grid training-loss-meta-grid">
                <div><span>Epoch</span><strong><?= e((string) $loss['actual_epochs']) ?></strong></div>
                <div><span>Optimizer</span><strong><?= e($loss['optimizer']) ?></strong></div>
                <div><span>Loss Function</span><strong><?= e($loss['loss_function']) ?></strong></div>
                <div><span>Batch Size</span><strong><?= e((string) $loss['batch_size']) ?></strong></div>
                <div><span>Learning Rate</span><strong><?= e(number_format((float) $loss['learning_rate'], 3, '.', '')) ?></strong></div>
                <div><span>Final Training Loss</span><strong><?= e(number_format((float) $loss['final_loss'], 8, '.', '')) ?></strong></div>
            </div>

            <div class="thesis-chart-box training-loss-chart-box">
                <canvas id="trainingLossChart"></canvas>
            </div>

            <p class="thesis-caption">Gambar 4.3 Grafik Loss Pelatihan Model BiGRU<br>Sumber: Dokumen Pribadi (2026)</p>

            <div class="training-loss-debug no-screenshot">
                <h3>Validasi Data Loss</h3>
                <div class="model-detail-grid">
                    <div><span>Model Run ID</span><strong><?= e((string) $loss['model_run_id']) ?></strong></div>
                    <div><span>Status</span><strong><?= e($loss['model_status']) ?></strong></div>
                    <div><span>Configured Epoch</span><strong><?= e((string) $loss['configured_epochs']) ?></strong></div>
                    <div><span>Actual Epoch</span><strong><?= e((string) $loss['actual_epochs']) ?></strong></div>
                    <div><span>Jumlah Loss Point</span><strong><?= e((string) $loss['loss_points']) ?></strong></div>
                    <div><span>Loss Epoch Pertama</span><strong><?= e(number_format((float) $loss['first_loss'], 8, '.', '')) ?></strong></div>
                    <div><span>Loss Epoch Terakhir</span><strong><?= e(number_format((float) $loss['final_loss'], 8, '.', '')) ?></strong></div>
                    <div><span>Final Loss Metadata</span><strong><?= e(number_format((float) $loss['final_loss_metadata'], 8, '.', '')) ?></strong></div>
                    <div><span>Sumber Loss</span><strong><?= e($loss['source']) ?></strong></div>
                    <div><span>Status Epoch</span><strong><?= e($loss['loss_point_status']) ?></strong></div>
                    <div><span>Status Final Loss</span><strong><?= e($loss['final_loss_status']) ?></strong></div>
                    <div><span>Status Log</span><strong><?= e($loss['log_status']) ?></strong></div>
                </div>
            </div>

            <div class="research-table-wrap no-screenshot">
                <table class="research-table training-loss-table">
                    <thead>
                        <tr>
                            <th>Epoch</th>
                            <th>Training Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($loss['history'] as $point): ?>
                        <tr>
                            <td><?= e((string) $point['epoch']) ?></td>
                            <td><?= e(number_format((float) $point['loss'], 8, '.', '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong>Data loss training final belum tersedia.</strong><small>Pastikan metadata atau training log model final memiliki loss per epoch.</small></div>
        <?php endif; ?>
    </section>
</div>

<?php if (!empty($loss['available'])): ?>
<script>
const trainingLossData = <?= json_encode($loss['history'], JSON_THROW_ON_ERROR) ?>;
new Chart(document.getElementById('trainingLossChart'), {
    type: 'line',
    data: {
        labels: trainingLossData.map(item => `Epoch ${item.epoch}`),
        datasets: [{
            label: 'Training Loss',
            data: trainingLossData.map(item => Number(item.loss)),
            borderColor: '#1f2937',
            backgroundColor: 'rgba(31, 41, 55, 0.08)',
            borderWidth: 2,
            pointRadius: trainingLossData.length > 60 ? 0 : 2,
            tension: 0,
            fill: false,
        }]
    },
    options: {
        maintainAspectRatio: false,
        animation: false,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    title(items) {
                        return items[0]?.label || '';
                    },
                    label(context) {
                        return `Training Loss: ${Number(context.parsed.y).toFixed(8)}`;
                    }
                }
            }
        },
        scales: {
            x: {
                title: { display: true, text: 'Epoch' },
                ticks: { autoSkip: true, maxTicksLimit: 10, maxRotation: 0 },
                grid: { display: false }
            },
            y: {
                title: { display: true, text: 'Loss (MSE)' },
                grid: { color: 'rgba(17, 24, 39, 0.10)' }
            }
        }
    }
});
</script>
<?php endif; ?>
