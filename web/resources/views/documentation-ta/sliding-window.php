<?php
$modelName = $finalModel['model_name'] ?? null;
$modelVersion = $finalModel['version'] ?? null;
$statusClass = ($window['status'] ?? '') === 'ok' ? 'ok' : (($window['status'] ?? '') === 'warning' ? 'warn' : '');
$primaryExample = $window['examples'][0] ?? null;
$formatShortDate = function (?string $date): string {
    if (!$date) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d-m-Y', $timestamp) : $date;
};
?>

<div class="thesis-doc-shell research-doc-shell sliding-window-doc-shell">
    <div class="thesis-doc-toolbar no-screenshot">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian">Tabel 4.2</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/pembagian-dataset">Tabel 4.3</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/normalisasi">Tabel 4.4</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/parameter-model">Tabel 4.6</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/sliding-window?screenshot=1">Mode Screenshot</a>
        <button type="button" id="copySlidingWindowTable">Salin Tabel</button>
    </div>

    <section class="panel thesis-capture-panel research-capture-panel sliding-window-panel">
        <div class="thesis-title-block">
            <div>
                <p class="eyebrow">Tabel 4.5</p>
                <h2>Struktur Sliding Window 30 Observasi</h2>
                <p class="card-note no-screenshot">Setiap input terdiri atas 30 observasi Close Price sebelumnya untuk memprediksi satu nilai Close Price berikutnya.</p>
            </div>
            <?php if ($modelVersion): ?>
                <span class="thesis-model-chip no-screenshot">Model final: <?= e($modelName ?: $modelVersion) ?></span>
            <?php endif; ?>
        </div>

        <div class="research-sync-banner <?= e($statusClass) ?> no-screenshot">
            <strong><?= e($window['message'] ?? 'Model final belum tersedia.') ?></strong>
            <small>Sliding window dibentuk dari data kronologis tanpa shuffle, sesuai fungsi `make_sequences()` pada preprocessing ML service.</small>
        </div>

        <?php if (!empty($window['available'])): ?>
            <div class="sliding-window-diagram no-screenshot">
                <div class="timeline-row">
                    <span>t-30</span><span>t-29</span><span>...</span><span>t-2</span><span>t-1</span><span>t</span><span>t+1</span><span>t+2</span>
                </div>
                <div class="window-row"><strong>Window 1</strong><span>[t-30 ... t-1]</span><b>-></b><span>target t</span></div>
                <div class="window-row"><strong>Window 2</strong><span>[t-29 ... t]</span><b>-></b><span>target t+1</span></div>
                <div class="window-row"><strong>Window 3</strong><span>[t-28 ... t+1]</span><b>-></b><span>target t+2</span></div>
            </div>

            <div class="research-table-wrap sliding-pattern-table-wrap">
                <table id="slidingWindowPatternTable" class="research-table sliding-pattern-table">
                    <thead>
                        <tr>
                            <th>Pasangan</th>
                            <th>Input</th>
                            <th>Target</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($window['rows'] as $row): ?>
                        <tr>
                            <td data-label="Pasangan"><strong><?= e($row['pair']) ?></strong></td>
                            <td data-label="Input"><?= e($row['input']) ?></td>
                            <td data-label="Target"><?= e($row['target']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="research-table-wrap sliding-observation-table-wrap">
                <table id="slidingWindowTable" class="research-table sliding-window-table">
                    <thead>
                        <tr>
                            <th>Observasi Ke</th>
                            <th>Tanggal Input</th>
                            <th>Close Input</th>
                            <th>Target</th>
                            <th>Close Target</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($primaryExample['input_values'] ?? []) as $index => $input): ?>
                        <tr>
                            <td data-label="Observasi Ke"><strong><?= e((string) ($index + 1)) ?></strong></td>
                            <td data-label="Tanggal Input"><?= e($formatShortDate($input['date'] ?? null)) ?></td>
                            <td data-label="Close Input"><?= e(number_format((float) ($input['close'] ?? 0), 6, ',', '')) ?></td>
                            <td data-label="Target"><?= e($formatShortDate($primaryExample['target_date'] ?? null)) ?></td>
                            <td data-label="Close Target"><?= e(number_format((float) ($primaryExample['target_close'] ?? 0), 6, ',', '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="sliding-window-note">Tabel menampilkan <?= e((string) count($primaryExample['input_values'] ?? [])) ?> observasi input pada pasangan pertama. Target yang diprediksi adalah Close Price periode berikutnya.</p>

            <div class="sliding-example-grid no-screenshot">
                <?php foreach ($window['examples'] as $example): ?>
                    <section class="sliding-example-card">
                        <h3>Pasangan <?= e((string) $example['pair']) ?></h3>
                        <div class="model-detail-grid">
                            <div><span>Input Periode</span><strong><?= e(format_indonesian_date($example['input_start_date']) . ' - ' . format_indonesian_date($example['input_end_date'])) ?></strong></div>
                            <div><span>Target Tanggal</span><strong><?= e(format_indonesian_date($example['target_date'])) ?></strong></div>
                            <div><span>Close Target</span><strong><?= e(number_format((float) $example['target_close'], 6, '.', '')) ?></strong></div>
                            <div><span>Split</span><strong>Data Latih</strong></div>
                        </div>
                        <details>
                            <summary>Lihat 30 nilai input Close</summary>
                            <ol>
                                <?php foreach ($example['input_values'] as $input): ?>
                                    <li><?= e(format_indonesian_date($input['date'])) ?>: <?= e(number_format((float) $input['close'], 6, '.', '')) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </details>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="sliding-window-info no-screenshot">
                <div>
                    <h3>Informasi Aktual Model Final</h3>
                    <div class="model-detail-grid">
                        <div><span>Model Version</span><strong><?= e($modelVersion ?? '-') ?></strong></div>
                        <div><span>Window Size</span><strong><?= e((string) $window['window_size']) ?></strong></div>
                        <div><span>Input Shape</span><strong><?= e($window['input_shape']) ?></strong></div>
                        <div><span>Feature</span><strong><?= e($window['feature']) ?></strong></div>
                        <div><span>Target Shape</span><strong><?= e($window['target_shape']) ?></strong></div>
                        <div><span>Forecast Type</span><strong><?= e($window['forecast_type']) ?></strong></div>
                        <div><span>Input Shape Train</span><strong><?= e($window['train_input_shape']) ?></strong></div>
                        <div><span>Input Shape Test</span><strong><?= e($window['test_input_shape']) ?></strong></div>
                    </div>
                </div>
                <div>
                    <h3>Jumlah Sample</h3>
                    <div class="model-detail-grid">
                        <div><span>Train Observations</span><strong><?= e(number_format((int) $window['train_observations'], 0, ',', '.')) ?></strong></div>
                        <div><span>Train Samples</span><strong><?= e(number_format((int) $window['train_samples'], 0, ',', '.')) ?></strong></div>
                        <div><span>Test Observations</span><strong><?= e(number_format((int) $window['test_observations'], 0, ',', '.')) ?></strong></div>
                        <div><span>Test Samples</span><strong><?= e(number_format((int) $window['test_samples'], 0, ',', '.')) ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="research-notes sliding-window-source">
                <strong>Sumber</strong>
                <p>Rancangan berdasarkan BAB III final (2026). Sliding window aktual dibentuk oleh `make_sequences(values, window_size, start_target, end_target)` pada `ml-service/app/ml/preprocessing.py` dengan input shape per sample <?= e($window['input_shape']) ?> dan target satu nilai Close.</p>
            </div>
        <?php else: ?>
            <div class="empty-chart historical-empty"><strong><?= e($window['message'] ?? 'Model final belum tersedia.') ?></strong><small>Pastikan model final dan dataset final tersedia.</small></div>
        <?php endif; ?>
    </section>
</div>

<script>
const slidingCopyButton = document.getElementById('copySlidingWindowTable');
if (slidingCopyButton) {
    slidingCopyButton.addEventListener('click', async () => {
        const rows = Array.from(document.querySelectorAll('#slidingWindowTable tr'));
        const text = rows.map(row => Array.from(row.children).map(cell => cell.innerText.trim()).join('\t')).join('\n');
        await navigator.clipboard.writeText(text);
        slidingCopyButton.textContent = 'Tersalin';
        setTimeout(() => slidingCopyButton.textContent = 'Salin Tabel', 1400);
    });
}
</script>
