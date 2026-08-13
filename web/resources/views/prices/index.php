<form method="post" action="/prices" class="grid-form price-form">
    <?= csrf_field() ?>
    <label class="field-date">Tanggal <input type="date" name="date" required></label>
    <label class="field-open">Open <input type="number" step="0.0001" name="open"></label>
    <label class="field-high">High <input type="number" step="0.0001" name="high"></label>
    <label class="field-low">Low <input type="number" step="0.0001" name="low"></label>
    <label class="field-close">Close <input type="number" step="0.0001" name="close" required></label>
    <label class="field-volume">Volume <input type="number" name="volume"></label>
    <label class="field-change">Change % <input type="number" step="0.0001" name="change_percent"></label>
    <button type="submit">Tambah Data</button>
</form>

<section class="panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Dataset</p>
            <h2>Data Historis Terbaru</h2>
        </div>
    </div>
    <div class="table-wrap">
        <?php
        $pageWindowStart = max(1, ($pagination['page'] ?? 1) - 2);
        $pageWindowEnd = min($pagination['total_pages'] ?? 1, ($pagination['page'] ?? 1) + 2);
        ?>
        <div class="server-pagination">
            <span><?= e($pagination['start'] ?? 0) ?>-<?= e($pagination['end'] ?? 0) ?> dari <?= e($pagination['total_rows'] ?? 0) ?> data</span>
            <form method="get">
                <label>Rows
                    <select name="per_page" onchange="this.form.submit()">
                        <?php foreach (($pagination['allowed_per_page'] ?? [20, 50, 100]) as $size): ?>
                            <option value="<?= e($size) ?>" <?= (int) ($pagination['per_page'] ?? 20) === (int) $size ? 'selected' : '' ?>><?= e($size) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <input type="hidden" name="page" value="1">
            </form>
            <nav class="table-pagination-nav" aria-label="Pagination data harga">
                <a class="table-page-button <?= ($pagination['page'] ?? 1) <= 1 ? 'disabled' : '' ?>" href="/prices?page=<?= e(max(1, ($pagination['page'] ?? 1) - 1)) ?>&per_page=<?= e($pagination['per_page'] ?? 20) ?>">Prev</a>
                <?php if ($pageWindowStart > 1): ?>
                    <a class="table-page-button" href="/prices?page=1&per_page=<?= e($pagination['per_page'] ?? 20) ?>">1</a>
                    <?php if ($pageWindowStart > 2): ?><span class="table-page-dots">...</span><?php endif; ?>
                <?php endif; ?>
                <?php for ($pageNumber = $pageWindowStart; $pageNumber <= $pageWindowEnd; $pageNumber++): ?>
                    <a class="table-page-button <?= (int) ($pagination['page'] ?? 1) === $pageNumber ? 'active' : '' ?>" href="/prices?page=<?= e($pageNumber) ?>&per_page=<?= e($pagination['per_page'] ?? 20) ?>"><?= e($pageNumber) ?></a>
                <?php endfor; ?>
                <?php if ($pageWindowEnd < ($pagination['total_pages'] ?? 1)): ?>
                    <?php if ($pageWindowEnd < ($pagination['total_pages'] ?? 1) - 1): ?><span class="table-page-dots">...</span><?php endif; ?>
                    <a class="table-page-button" href="/prices?page=<?= e($pagination['total_pages'] ?? 1) ?>&per_page=<?= e($pagination['per_page'] ?? 20) ?>"><?= e($pagination['total_pages'] ?? 1) ?></a>
                <?php endif; ?>
                <a class="table-page-button <?= ($pagination['page'] ?? 1) >= ($pagination['total_pages'] ?? 1) ? 'disabled' : '' ?>" href="/prices?page=<?= e(min($pagination['total_pages'] ?? 1, ($pagination['page'] ?? 1) + 1)) ?>&per_page=<?= e($pagination['per_page'] ?? 20) ?>">Next</a>
            </nav>
        </div>
        <table data-no-client-pagination="true">
            <thead><tr><th>Tanggal</th><th>Open</th><th>High</th><th>Low</th><th>Close</th><th>Volume</th><th>Change %</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td data-label="Tanggal"><?= e(format_indonesian_date($row['date'] ?? null)) ?></td>
                    <td data-label="Open"><?= e($row['open']) ?></td>
                    <td data-label="High"><?= e($row['high']) ?></td>
                    <td data-label="Low"><?= e($row['low']) ?></td>
                    <td data-label="Close"><?= e($row['close']) ?></td>
                    <td data-label="Volume"><?= e($row['volume']) ?></td>
                    <td data-label="Change %"><?= e($row['change_percent']) ?></td>
                    <td data-label="Aksi">
                        <a class="button-secondary" href="/prices/edit?id=<?= e($row['id']) ?>">Edit</a>
                        <form method="post" action="/prices/delete" class="inline-form" onsubmit="return confirm('Hapus data harga tanggal <?= e($row['date']) ?>?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                            <button type="submit" class="danger-button">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
