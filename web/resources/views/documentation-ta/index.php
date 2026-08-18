<section class="panel thesis-doc-panel">
    <div class="section-head">
        <div>
            <p class="eyebrow">Dokumentasi BAB IV</p>
            <h2>Halaman Screenshot Tugas Akhir</h2>
            <p class="card-note">Setiap gambar dibuka pada halaman terpisah agar siap diambil sebagai screenshot.</p>
        </div>
    </div>

    <div class="thesis-doc-toolbar">
        <a class="button-secondary" href="/admin/dokumentasi-penelitian">Ringkasan Prapemrosesan Data</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/pembagian-dataset">Pembagian Dataset Final</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/normalisasi">Contoh Normalisasi</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/sliding-window">Sliding Window</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/parameter-model">Parameter Model</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-training">Hasil Training</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/training-loss">Grafik Loss</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/manajemen-model">Manajemen Model</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/log-training">Log Training</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/hasil-test">Hasil Test</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/evaluasi-model">Evaluasi Model</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian/prediksi-recursive">Prediksi Recursive</a>
        <a class="button-secondary" href="/admin/dokumentasi-penelitian?screenshot=1">Mode Screenshot Tabel 4.2</a>
    </div>

    <div class="thesis-figure-grid">
        <?php foreach ($figures as $number => [$label, $path]): ?>
            <a class="thesis-figure-link" href="<?= e($path) ?>">
                <span>Gambar <?= e($number) ?></span>
                <strong><?= e($label) ?></strong>
                <small>Mode screenshot tersedia melalui parameter <code>?screenshot=1</code>.</small>
            </a>
        <?php endforeach; ?>
    </div>
</section>
