<form method="post" action="/prices/update" class="grid-form price-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
    <label class="field-date">Tanggal <input type="date" name="date" value="<?= e($row['date']) ?>" required></label>
    <label class="field-open">Open <input type="number" step="0.0001" name="open" value="<?= e($row['open']) ?>"></label>
    <label class="field-high">High <input type="number" step="0.0001" name="high" value="<?= e($row['high']) ?>"></label>
    <label class="field-low">Low <input type="number" step="0.0001" name="low" value="<?= e($row['low']) ?>"></label>
    <label class="field-close">Close <input type="number" step="0.0001" name="close" value="<?= e($row['close']) ?>" required></label>
    <label class="field-volume">Volume <input type="number" name="volume" value="<?= e($row['volume']) ?>"></label>
    <label class="field-change">Change % <input type="number" step="0.0001" name="change_percent" value="<?= e($row['change_percent']) ?>"></label>
    <button type="submit">Update Data</button>
    <a class="button-secondary" href="/prices">Batal</a>
</form>
