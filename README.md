# Prediksi Harga Tembaga BiGRU

Native PHP + FastAPI monorepo untuk tugas akhir: "Prediksi Harga Komoditas Tembaga di Indonesia Menggunakan Metode Bidirectional Gated Recurrent Unit (BiGRU)".

## Arsitektur

- `web/`: native PHP, PDO, server-rendered templates, Chart.js, dan MySQL persistence.
- `ml-service/`: FastAPI, Pydantic, scikit-learn preprocessing, TensorFlow/Keras BiGRU.
- `MySQL`: data aplikasi dimiliki PHP.
- Integrasi REST: PHP cURL client memanggil endpoint Python `/api/train`, `/api/predict`, dan `/api/models/{version}`.

Python bertanggung jawab atas preprocessing, normalisasi MinMaxScaler, sliding window, training BiGRU, prediksi, denormalisasi, metrik, dan artifact model/scaler/metadata.

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

TensorFlow/Keras wajib tersedia untuk training BiGRU. Jika TensorFlow tidak tersedia, training gagal dan tidak ada fallback Linear Regression.

## Setup

1. Import schema:

   ```powershell
   mysql -u root < web/database/schema.sql
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
   py -m venv .venv
   .\.venv\Scripts\Activate.ps1
   pip install -r requirements.txt
   uvicorn app.main:app --reload --port 8001
   ```

Seed admin lokal development: `admin@example.com` / `password`. Ganti kredensial ini untuk konfigurasi selain development lokal.

## Verification

```powershell
cd web
php tests/run.php

cd ..\ml-service
py -m compileall app tests
```

After Python dependencies are installed, run:

```powershell
cd ml-service
.\.venv\Scripts\python.exe -m pytest
```
