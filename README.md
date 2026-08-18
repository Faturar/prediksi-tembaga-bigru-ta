# Prediksi Harga Tembaga BiGRU

Native PHP + FastAPI monorepo untuk tugas akhir: "Prediksi Harga Komoditas Tembaga di Indonesia Menggunakan Metode Bidirectional Gated Recurrent Unit (BiGRU)".

## Arsitektur

- `web/`: native PHP, PDO, server-rendered templates, Chart.js, dan MySQL persistence.
- `ml-service/`: FastAPI, Pydantic, scikit-learn preprocessing, TensorFlow/Keras BiGRU.
- `MySQL`: data aplikasi dimiliki PHP.
- Integrasi REST: PHP cURL client memanggil endpoint Python `/api/train`, `/api/predict`, dan `/api/models/{version}`.

Python bertanggung jawab atas preprocessing, normalisasi MinMaxScaler, sliding window, training BiGRU, prediksi, denormalisasi, metrik, dan artifact model/scaler/metadata.

## Modul Aplikasi

- Dashboard admin untuk ringkasan dataset, status ML service, model aktif, dan grafik harga historis.
- Manajemen data harga tembaga (`copper_prices`) dengan input manual, edit, hapus, dan import CSV.
- Manajemen training model BiGRU dengan konfigurasi window, units, dropout, batch size, epoch, learning rate, serta nama model custom.
- Nama model dapat dikosongkan saat training; sistem otomatis membuat nama berbasis konfigurasi dan waktu training. `version` tetap dibuat otomatis untuk traceability artifact, log, dan metadata.
- Riwayat model memakai `model_runs` dan `model_metrics`, termasuk status aktif, metrik MAE/RMSE/MAPE, periode dataset, train/test samples, dan artifact metadata.
- Prediksi memakai model aktif dan menyimpan input/output prediksi untuk menjaga traceability.
- Evaluasi menampilkan metrik dan perbandingan aktual vs prediksi dari test series model.
- Laporan dan halaman publik untuk data historis serta prediksi.
- Modul Dokumentasi TA untuk screenshot BAB IV pada route `/admin/dokumentasi-ta`.

## Metodologi Final

- Input model hanya `Close Price` dengan urutan tanggal ascending.
- Split data 80% training dan 20% testing secara kronologis tanpa shuffle.
- `MinMaxScaler(feature_range=(0, 1))` fit hanya pada data training.
- Default `window_size = 30`, input shape `(30, 1)`.
- Arsitektur model: satu `Bidirectional(GRU(units=64, return_sequences=False))`, `Dropout(0.2)`, `Dense(1)`.
- Optimizer `Adam`, loss `mse`, default `batch_size = 32`, `epochs = 50`, `learning_rate = 0.001`.
- `model.fit(..., shuffle=False)` tanpa `validation_split`.
- Evaluasi memakai MAE, RMSE, dan MAPE setelah inverse transform ke skala harga asli.
- Prediksi merepresentasikan periode/observasi perdagangan berikutnya, bukan tanggal kalender yang dibuat otomatis.
- Metadata training menyimpan `test_series` one-step-ahead. Training baru juga menyimpan `training_history.loss`; model lama dapat tetap membaca loss per epoch dari log training jika tersedia.

TensorFlow/Keras wajib tersedia untuk training BiGRU. Gunakan Python 3.11 untuk ML service di Windows karena TensorFlow belum tersedia untuk semua versi Python terbaru. Jika TensorFlow tidak tersedia, training gagal dan tidak ada fallback Linear Regression.

## Dokumentasi Screenshot TA

Halaman dokumentasi khusus admin tersedia untuk mengambil screenshot BAB IV:

```text
Gambar 4.1:
http://localhost:8000/admin/dokumentasi-ta/gambar-4-1?screenshot=1

Gambar 4.2:
http://localhost:8000/admin/dokumentasi-ta/gambar-4-2?screenshot=1

Gambar 4.3:
http://localhost:8000/admin/dokumentasi-ta/gambar-4-3?screenshot=1

Gambar 4.4:
http://localhost:8000/admin/dokumentasi-ta/gambar-4-4?screenshot=1

Gambar 4.5:
http://localhost:8000/admin/dokumentasi-ta/gambar-4-5?screenshot=1
```

Parameter `?screenshot=1` mengaktifkan mode screenshot: sidebar, navbar, tombol aksi, dan elemen non-esensial disembunyikan agar tampilan bersih untuk resolusi desktop.

## Setup

1. Import schema:

   ```powershell
   Get-Content web/database/schema.sql | mysql -u root
   ```

2. Copy env files:

   ```powershell
   Copy-Item web/.env.example web/.env
   Copy-Item ml-service/.env.example ml-service/.env
   ```

3. Start PHP:

   ```powershell
   cd web
   php -S localhost:8000 -t public
   ```

4. Start ML service:

   ```powershell
   cd ml-service
   py -3.11 -m venv .venv311
   .\.venv311\Scripts\Activate.ps1
   pip install -r requirements.txt
   uvicorn app.main:app --reload --port 8001
   ```

Seed admin lokal development: `admin@gmail.com` / `admin123`. Ganti kredensial ini untuk konfigurasi selain development lokal.

## Verification

```powershell
cd web
php tests/run.php

cd ..\ml-service
.\.venv311\Scripts\python.exe -m compileall app tests
```

After Python dependencies are installed, run:

```powershell
cd ml-service
.\.venv311\Scripts\python.exe -m pytest
```
