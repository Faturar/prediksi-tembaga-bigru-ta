# Prediksi Harga Tembaga

Native PHP + FastAPI monorepo for copper futures close-price forecasting.

## Stack

- `web/`: native PHP, PDO, server-rendered templates, Chart.js-ready layout.
- `ml-service/`: FastAPI, Pydantic, scikit-learn preprocessing, TensorFlow/Keras BiGRU when installed.
- `MySQL`: application data owned by PHP.
- REST integration: PHP cURL client calls the Python ML API.

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

Default admin seed: `admin@example.com` / `password`.

TensorFlow is optional for lightweight bootstrapping. If TensorFlow is not installed, the training service stores a linear-regression fallback artifact so integration can still be demonstrated. Final thesis training should install TensorFlow and use the BiGRU path.

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
