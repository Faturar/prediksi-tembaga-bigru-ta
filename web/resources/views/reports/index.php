<?php
$formatTanggalIndonesia = function (?string $date, bool $withTime = false): string {
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);
    if (!$timestamp) {
        return $date;
    }

    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $formatted = $days[(int) date('w', $timestamp)] . ', ' . date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

    return $withTime ? $formatted . ' ' . date('H:i', $timestamp) : $formatted;
};

$filterQuery = http_build_query(array_filter([
    'start_date' => $filters['start_date'] ?? null,
    'end_date' => $filters['end_date'] ?? null,
]));
$filterSuffix = $filterQuery ? '&' . $filterQuery : '';

$renderReportPagination = function (array $pagination, string $activeType) use ($filterSuffix): void {
    $pageWindowStart = max(1, ($pagination['page'] ?? 1) - 2);
    $pageWindowEnd = min($pagination['total_pages'] ?? 1, ($pagination['page'] ?? 1) + 2);
    $perPage = $pagination['per_page'] ?? 20;
?>
    <div class="server-pagination">
        <span><?= e($pagination['start'] ?? 0) ?>-<?= e($pagination['end'] ?? 0) ?> dari <?= e($pagination['total_rows'] ?? 0) ?> data</span>
        <form method="get">
            <input type="hidden" name="type" value="<?= e($activeType) ?>">
            <input type="hidden" name="page" value="1">
            <?php if (!empty($_GET['start_date'])): ?><input type="hidden" name="start_date" value="<?= e($_GET['start_date']) ?>"><?php endif; ?>
            <?php if (!empty($_GET['end_date'])): ?><input type="hidden" name="end_date" value="<?= e($_GET['end_date']) ?>"><?php endif; ?>
            <label>Rows
                <select name="per_page" onchange="this.form.submit()">
                    <?php foreach (($pagination['allowed_per_page'] ?? [20, 50, 100]) as $size): ?>
                        <option value="<?= e($size) ?>" <?= (int) $perPage === (int) $size ? 'selected' : '' ?>><?= e($size) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
        <nav class="table-pagination-nav" aria-label="Pagination laporan">
            <a class="table-page-button <?= ($pagination['page'] ?? 1) <= 1 ? 'disabled' : '' ?>" href="/reports?type=<?= e($activeType) ?>&page=<?= e(max(1, ($pagination['page'] ?? 1) - 1)) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>">Prev</a>
            <?php if ($pageWindowStart > 1): ?>
                <a class="table-page-button" href="/reports?type=<?= e($activeType) ?>&page=1&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>">1</a>
                <?php if ($pageWindowStart > 2): ?><span class="table-page-dots">...</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($pageNumber = $pageWindowStart; $pageNumber <= $pageWindowEnd; $pageNumber++): ?>
                    <a class="table-page-button <?= (int) ($pagination['page'] ?? 1) === $pageNumber ? 'active' : '' ?>" href="/reports?type=<?= e($activeType) ?>&page=<?= e($pageNumber) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>"><?= e($pageNumber) ?></a>
            <?php endfor; ?>
            <?php if ($pageWindowEnd < ($pagination['total_pages'] ?? 1)): ?>
                <?php if ($pageWindowEnd < ($pagination['total_pages'] ?? 1) - 1): ?><span class="table-page-dots">...</span><?php endif; ?>
                <a class="table-page-button" href="/reports?type=<?= e($activeType) ?>&page=<?= e($pagination['total_pages'] ?? 1) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>"><?= e($pagination['total_pages'] ?? 1) ?></a>
            <?php endif; ?>
            <a class="table-page-button <?= ($pagination['page'] ?? 1) >= ($pagination['total_pages'] ?? 1) ? 'disabled' : '' ?>" href="/reports?type=<?= e($activeType) ?>&page=<?= e(min($pagination['total_pages'] ?? 1, ($pagination['page'] ?? 1) + 1)) ?>&per_page=<?= e($perPage) ?><?= e($filterSuffix) ?>">Next</a>
        </nav>
    </div>
<?php
};
?>

<?php if ($printMode): ?>
<div class="print-mode-bar">
    <a class="button-secondary" href="/reports?type=<?= e($activeType) ?>">Kembali</a>
    <button class="full-width-button button-primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>
<script>
window.addEventListener('load', () => {
    window.setTimeout(() => window.print(), 400);
});
</script>
<?php else: ?>
<div class="report-toolbar">
    <div class="report-switcher" aria-label="Jenis laporan">
        <?php foreach ($reportTypes as $type => $label): ?>
            <a class="<?= $activeType === $type ? 'active' : '' ?>" href="/reports?type=<?= e($type) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <a class="full-width-button print-trigger" href="/reports?type=<?= e($activeType) ?><?= $filterSuffix ?>&print=1">Print Laporan</a>
</div>
<?php endif; ?>

<?php if (!$printMode && in_array($activeType, ['dataset', 'prediction'], true)): ?>
<?php
    $displayStart = !empty($filters['start_date']) ? date('d/m/Y', strtotime($filters['start_date'])) : '';
    $displayEnd = !empty($filters['end_date']) ? date('d/m/Y', strtotime($filters['end_date'])) : '';
?>
<div class="report-header-filter">
    <form method="get" class="report-filter-form" id="report-filter-form">
        <input type="hidden" name="type" value="<?= e($activeType) ?>">
        <input type="hidden" name="start_date" id="start_date_iso" value="<?= e($filters['start_date'] ?? '') ?>">
        <input type="hidden" name="end_date" id="end_date_iso" value="<?= e($filters['end_date'] ?? '') ?>">

        <div class="filter-grid">
            <label>
                <span>Mulai</span>
                <input type="text" id="start_date_display" name="start_date_display" placeholder="dd/mm/yyyy" value="<?= e($displayStart) ?>">
            </label>
            <label>
                <span>Sampai</span>
                <input type="text" id="end_date_display" name="end_date_display" placeholder="dd/mm/yyyy" value="<?= e($displayEnd) ?>">
            </label>
        </div>

        <div class="filter-actions">
            <button type="submit">Filter</button>
            <a class="button-secondary" href="/reports?type=<?= e($activeType) ?>">Reset</a>
        </div>
    </form>
</div>

<script>
(() => {
    const form = document.getElementById('report-filter-form');
    const startDisplay = document.getElementById('start_date_display');
    const endDisplay = document.getElementById('end_date_display');
    const startIso = document.getElementById('start_date_iso');
    const endIso = document.getElementById('end_date_iso');

    const toIso = (ddmmyy) => {
        if (!ddmmyy || !ddmmyy.trim()) return '';
        const parts = ddmmyy.trim().split('/');
        if (parts.length !== 3) return null;

        const day = parts[0].padStart(2, '0');
        const month = parts[1].padStart(2, '0');
        const year = parts[2];

        if (!/^\d{4}$/.test(year)) return null;
        const d = Number(day);
        const m = Number(month);
        if (m < 1 || m > 12 || d < 1 || d > 31) return null;

        return `${year}-${month}-${day}`;
    };

    form.addEventListener('submit', (event) => {
        const startValue = toIso(startDisplay.value);
        const endValue = toIso(endDisplay.value);

        if (startDisplay.value && !startValue) {
            event.preventDefault();
            alert('Format Mulai harus dd/mm/yyyy');
            return;
        }

        if (endDisplay.value && !endValue) {
            event.preventDefault();
            alert('Format Sampai harus dd/mm/yyyy');
            return;
        }

        startIso.value = startValue || '';
        endIso.value = endValue || '';
    });
})();
</script>
<?php endif; ?>

<?php if ($printMode && $activeType === 'training'): ?>
<style>@page{size:A4 landscape;margin:12mm 10mm;}</style>
<?php endif; ?>
<section class="panel report-document<?= ($printMode && $activeType === 'training') ? ' print-landscape' : '' ?>">
    <?php if ($printMode): ?>
    <div class="print-doc-header">
        <div class="print-doc-brand">
            <strong><?= e($appName) ?></strong>
            <span><?= e($activeTitle) ?></span>
        </div>
        <div class="report-print-meta">
            <span>Dicetak</span>
            <strong><?= e($formatTanggalIndonesia(date('Y-m-d H:i:s'), true)) ?></strong>
            <span>Oleh</span>
            <strong><?= e($user['name'] ?? '-') ?></strong>
            <?php if (!empty($filters['start_date']) || !empty($filters['end_date'])): ?>
                <span>Periode</span>
                <strong><?= e($formatTanggalIndonesia($filters['start_date'] ?? null)) ?> s/d <?= e($formatTanggalIndonesia($filters['end_date'] ?? null)) ?></strong>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="report-title">
        <div>
            <h2><?= e($activeTitle) ?></h2>
            <?php if ($activeType === 'dataset'): ?>
                <p>Periode data: <?= e($formatTanggalIndonesia($priceSummary['start_date'] ?? null)) ?> sampai <?= e($formatTanggalIndonesia($priceSummary['end_date'] ?? null)) ?></p>
            <?php elseif ($activeType === 'training'): ?>
                <p>Riwayat sesi training model BiGRU</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($activeType === 'dataset'): ?>
        <div class="report-summary-grid">
            <div><span>Total Data</span><strong><?= e(number_format((float) ($priceSummary['total_rows'] ?? 0), 0, ',', '.')) ?></strong><small>baris harga historis</small></div>
            <div><span>Periode Awal</span><strong><?= e($formatTanggalIndonesia($priceSummary['start_date'] ?? null)) ?></strong><small>tanggal data pertama</small></div>
            <div><span>Periode Akhir</span><strong><?= e($formatTanggalIndonesia($priceSummary['end_date'] ?? null)) ?></strong><small>tanggal data terakhir</small></div>
            <div><span>Min Close</span><strong><?= e(number_format((float) ($priceSummary['min_close'] ?? 0), 4)) ?></strong><small>nilai close terendah</small></div>
            <div><span>Max Close</span><strong><?= e(number_format((float) ($priceSummary['max_close'] ?? 0), 4)) ?></strong><small>nilai close tertinggi</small></div>
            <div><span>Rata-rata Close</span><strong><?= e(number_format((float) ($priceSummary['avg_close'] ?? 0), 4)) ?></strong><small>rata-rata seluruh data</small></div>
        </div>

        <h3>Riwayat Import</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>File</th><th>User</th><th>Total</th><th>Valid</th><th>Inserted</th><th>Updated</th><th>Invalid</th><th>Waktu</th></tr></thead>
                <tbody>
                <?php foreach ($imports as $row): ?>
                    <tr>
                        <td data-label="File"><?= e($row['original_filename']) ?></td>
                        <td data-label="User"><?= e($row['user_name'] ?? '-') ?></td>
                        <td data-label="Total"><?= e($row['total_rows']) ?></td>
                        <td data-label="Valid"><?= e($row['valid_rows']) ?></td>
                        <td data-label="Inserted"><?= e($row['imported_rows']) ?></td>
                        <td data-label="Updated"><?= e($row['updated_rows']) ?></td>
                        <td data-label="Invalid"><?= e($row['invalid_rows']) ?></td>
                        <td data-label="Waktu"><?= e($formatTanggalIndonesia($row['created_at'] ?? null, true)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3>Data Harga Terbaru</h3>
        <div class="table-wrap">
            <?php if (!$printMode): $renderReportPagination($reportPagination, $activeType); endif; ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Tanggal</th><th>Open</th><th>High</th><th>Low</th><th>Close</th><th>Volume</th><th>Change %</th></tr></thead>
                <tbody>
                <?php foreach ($prices as $row): ?>
                    <tr>
                        <td data-label="Tanggal"><?= e($formatTanggalIndonesia($row['date'] ?? null)) ?></td>
                        <td data-label="Open"><?= e($row['open']) ?></td>
                        <td data-label="High"><?= e($row['high']) ?></td>
                        <td data-label="Low"><?= e($row['low']) ?></td>
                        <td data-label="Close"><?= e($row['close']) ?></td>
                        <td data-label="Volume"><?= e($row['volume']) ?></td>
                        <td data-label="Change %"><?= e($row['change_percent']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($activeType === 'training'): ?>
        <div class="report-summary-grid">
            <div><span>Total Training</span><strong><?= e(number_format((float) ($trainingSummary['total_runs'] ?? 0), 0, ',', '.')) ?></strong><small>total sesi training</small></div>
            <div><span>Model Aktif</span><strong><?= e($trainingSummary['active_version'] ?? '-') ?></strong><small>model terpilih</small></div>
            <div><span>Berhasil</span><strong><?= e((int) ($trainingSummary['success_runs'] ?? 0)) ?></strong><small>status success</small></div>
            <div><span>Gagal</span><strong><?= e((int) ($trainingSummary['failed_runs'] ?? 0)) ?></strong><small>status failed</small></div>
            <div><span>Sedang Proses</span><strong><?= e((int) ($trainingSummary['running_runs'] ?? 0)) ?></strong><small>status running</small></div>
            <div><span>Training Terakhir</span><strong><?= e($formatTanggalIndonesia($trainingSummary['last_trained_at'] ?? null, true)) ?></strong><small>waktu training selesai</small></div>
        </div>

        <div class="table-wrap">
            <?php if (!$printMode): $renderReportPagination($reportPagination, $activeType); endif; ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Version</th><th>Status</th><th>Aktif</th><th>Window</th><th>Units</th><th>Dropout</th><th>Batch</th><th>Epoch</th><th>Learning Rate</th><th>Train</th><th>Test</th><th>Loss</th><th>MAE</th><th>RMSE</th><th>MAPE</th><th>Durasi</th><th>Trained</th></tr></thead>
                <tbody>
                <?php foreach ($models as $row): ?>
                    <tr>
                        <td data-label="Version"><?= e($row['version']) ?></td>
                        <td data-label="Status"><?= e($row['status']) ?></td>
                        <td data-label="Aktif"><?= $row['is_active'] ? 'Ya' : '-' ?></td>
                        <td data-label="Window"><?= e($row['window_size']) ?></td>
                        <td data-label="Units"><?= e($row['units']) ?></td>
                        <td data-label="Dropout"><?= e($row['dropout']) ?></td>
                        <td data-label="Batch"><?= e($row['batch_size']) ?></td>
                        <td data-label="Epoch"><?= e($row['actual_epochs'] ?? $row['configured_epochs']) ?></td>
                        <td data-label="Learning Rate"><?= e($row['learning_rate']) ?></td>
                        <td data-label="Train"><?= e($row['train_samples'] ?? '-') ?></td>
                        <td data-label="Test"><?= e($row['test_samples'] ?? '-') ?></td>
                        <td data-label="Loss"><?= $row['final_training_loss'] !== null ? e(number_format((float) $row['final_training_loss'], 4)) : '-' ?></td>
                        <td data-label="MAE"><?= e($row['mae']) ?></td>
                        <td data-label="RMSE"><?= e($row['rmse']) ?></td>
                        <td data-label="MAPE"><?= e($row['mape']) ?></td>
                        <td data-label="Durasi"><?= $row['training_duration_seconds'] !== null ? e(number_format((float) $row['training_duration_seconds'], 0, ',', '.')) . ' detik' : '-' ?></td>
                        <td data-label="Trained"><?= e($formatTanggalIndonesia($row['trained_at'] ?? null, true)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($activeType === 'evaluation'): ?>
        <div class="table-wrap">
            <?php if (!$printMode): $renderReportPagination($reportPagination, $activeType); endif; ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Version</th><th>Train</th><th>Test</th><th>Loss</th><th>MAE</th><th>RMSE</th><th>MAPE</th><th>Durasi</th></tr></thead>
                <tbody>
                <?php foreach ($metrics as $row): ?>
                    <tr>
                        <td data-label="Version"><?= e($row['version']) ?></td>
                        <td data-label="Train"><?= e($row['train_samples']) ?></td>
                        <td data-label="Test"><?= e($row['test_samples']) ?></td>
                        <td data-label="Loss"><?= e($row['final_training_loss']) ?></td>
                        <td data-label="MAE"><?= e($row['mae']) ?></td>
                        <td data-label="RMSE"><?= e($row['rmse']) ?></td>
                        <td data-label="MAPE"><?= e($row['mape']) ?></td>
                        <td data-label="Durasi"><?= e($row['training_duration_seconds']) ?> detik</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <?php if (!$printMode): $renderReportPagination($reportPagination, $activeType); endif; ?>
            <table data-no-client-pagination="true">
                <thead><tr><th>Model</th><th>Window</th><th>Input Awal</th><th>Input Akhir</th><th>Periode Prediksi</th><th>Prediksi Close</th><th>Dibuat</th></tr></thead>
                <tbody>
                <?php foreach ($predictions as $row): ?>
                    <tr>
                        <td data-label="Model"><?= e($row['version']) ?></td>
                        <td data-label="Window"><?= e($row['window_size']) ?></td>
                        <td data-label="Input Awal"><?= e($formatTanggalIndonesia($row['input_start_date'] ?? null)) ?></td>
                        <td data-label="Input Akhir"><?= e($formatTanggalIndonesia($row['input_end_date'] ?? null)) ?></td>
                        <td data-label="Periode Prediksi">Periode berikutnya</td>
                        <td data-label="Prediksi Close"><?= e($row['predicted_close']) ?></td>
                        <td data-label="Dibuat"><?= e($formatTanggalIndonesia($row['created_at'] ?? null, true)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
