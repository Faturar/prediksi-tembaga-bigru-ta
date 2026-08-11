<?php if (!empty($error)): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
<?php if (!empty($stats)): ?>
<div class="stats">
    <?php foreach ($stats as $key => $value): ?>
    <div><span><?= e(str_replace('_', ' ', $key)) ?></span><strong><?= e($value) ?></strong></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<section class="panel">
    <form method="post" action="/import" enctype="multipart/form-data" class="import-form">
        <?= csrf_field() ?>
        <label>File CSV <input type="file" name="csv" accept=".csv,text/csv" required></label>
        <button type="submit">Import</button>
    </form>
</section>

<section class="panel">
    <h2>Riwayat Import</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>File</th>
                    <th>User</th>
                    <th>Total</th>
                    <th>Valid</th>
                    <th>Inserted</th>
                    <th>Updated</th>
                    <th>Invalid</th>
                    <th>Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($histories ?? []) as $row): ?>
                <tr>
                    <td data-label="File"><?= e($row['original_filename']) ?></td>
                    <td data-label="User"><?= e($row['user_name']) ?></td>
                    <td data-label="Total"><?= e($row['total_rows']) ?></td>
                    <td data-label="Valid"><?= e($row['valid_rows']) ?></td>
                    <td data-label="Inserted"><?= e($row['imported_rows']) ?></td>
                    <td data-label="Updated"><?= e($row['updated_rows']) ?></td>
                    <td data-label="Invalid"><?= e($row['invalid_rows']) ?></td>
                    <td data-label="Status"><?= e($row['status']) ?></td>
                    <td data-label="Waktu"><?= e($row['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>