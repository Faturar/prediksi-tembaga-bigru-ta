# MASTER IMPLEMENTATION PLAN

## 1. Executive Summary

Sistem yang direkomendasikan adalah monorepo lokal berisi:

- `web/`: PHP native sebagai aplikasi web untuk autentikasi, dashboard, CRUD data harga tembaga, import CSV, tampilan training/prediksi/evaluasi, laporan, dan penyimpanan data bisnis ke MySQL.
- `ml-service/`: FastAPI sebagai layanan Python ML untuk preprocessing, normalisasi, sliding window, training BiGRU dengan TensorFlow/Keras, evaluasi, prediksi, dan manajemen artifact model.
- `MySQL`: penyimpanan permanen untuk user, data harga tembaga, riwayat import, model run, metric evaluasi, dan prediksi.
- Integrasi PHP ke Python menggunakan HTTP REST API, bukan `shell_exec()`.

Fokus metodologi adalah univariate time-series forecasting menggunakan `close price` historis Copper Futures. Data diproses secara kronologis, dibagi 80:20 tanpa shuffle, scaler hanya di-fit pada data training, dan semua evaluasi akhir dihitung kembali pada skala harga asli.

## 2. Final Architecture Decision

- PHP web: **Native PHP**
  - Dipilih karena requirement kampus mewajibkan PHP native. Struktur tetap dibuat rapi dengan pola MVC sederhana, routing manual, session auth, CSRF token, validation helper, PDO, dan template PHP.
- Framework PHP seperti Laravel/CodeIgniter tidak digunakan karena aturan kampus mewajibkan PHP native.
- Python API: **FastAPI**
  - Dipilih karena request validation kuat via Pydantic, dokumentasi API otomatis, performa baik, dan cocok untuk service ML.
- Flask: ditolak sebagai pilihan utama karena validasi dan OpenAPI perlu lebih banyak konfigurasi manual.
- Database: **MySQL 8.x**
  - Cocok dengan PHP native, Laragon/XAMPP, dan kebutuhan lokal skripsi.
- UI: **Server-rendered PHP templates**
  - Lebih sederhana daripada SPA, cukup untuk dashboard, form, tabel, chart, dan laporan.
- Chart library: **Chart.js**
  - Ringan, mudah dipakai di template PHP, cukup untuk historical chart dan actual vs predicted chart.
- Integration style: **Native PHP cURL HTTP client -> FastAPI REST API**
  - Lebih bersih, testable, dan mudah dijelaskan daripada menjalankan Python lewat shell.
- Report strategy: **HTML printable reports + CSS print**
  - Tidak butuh library PDF berbayar. Browser print dapat digunakan untuk simpan PDF.

## 3. Architecture Alternatives Considered

| Area        | Chosen                                  | Rejected                 | Reason                                                |
| ----------- | --------------------------------------- | ------------------------ | ----------------------------------------------------- |
| PHP Web     | Native PHP                              | Laravel/CodeIgniter      | Requirement kampus mewajibkan PHP native              |
| Python API  | FastAPI                                 | Flask                    | FastAPI validasi dan OpenAPI lebih kuat               |
| Integration | REST API                                | `shell_exec()`           | REST lebih terpisah, aman, observable                 |
| UI          | PHP templates                           | Vue/React SPA            | Template PHP cukup dan mudah dijelaskan untuk skripsi |
| Reports     | Print HTML                              | Paid PDF library         | Gratis, lokal, cukup untuk demo                       |
| DB access   | PHP owns MySQL, Python receives payload | Python direct DB         | Coupling lebih rendah dan business logic tetap di PHP |

## 4. System Architecture

```text
+-------------------+
| Browser           |
| Admin User        |
+---------+---------+
          |
          | HTTP
          v
+----------------------+      REST/JSON       +----------------------+
| Native PHP Web App   +--------------------->| FastAPI ML Service   |
| PHP + templates      |<---------------------+ Python + TensorFlow  |
+----------+-----------+                      +----------+-----------+
          |                                               |
          | SQL                                           | File I/O
          v                                               v
+-------------------+                          +----------------------+
| MySQL Database    |                          | ML Artifacts Storage |
| Business Data     |                          | models/scalers/meta  |
+-------------------+                          +----------------------+
```

Responsibilities:

- Browser: user interaction.
- Native PHP app: auth, UI, CRUD, CSV import, persistence, report rendering, API client.
- MySQL: relational data.
- FastAPI: ML computation, artifact creation/loading, evaluation, prediction.
- Artifact storage: versioned `.keras`, `.joblib`, `.json`.

Request/data flow:

1. Admin uploads CSV di aplikasi PHP native.
2. Aplikasi PHP native memvalidasi, mem-parse, dan menyimpan baris bersih ke MySQL.
3. Admin triggers training.
4. Aplikasi PHP native membaca data close price terurut dari MySQL dan mengirim payload dataset ke FastAPI.
5. FastAPI validates, preprocesses, trains, evaluates, stores artifacts, returns metadata and metrics.
6. Aplikasi PHP native menyimpan metadata/metrik model di MySQL dan menandai model sukses terbaru sebagai aktif.
7. Admin runs prediction.
8. Aplikasi PHP native mengirim window data terbaru dan versi model aktif ke FastAPI.
9. FastAPI returns predicted close.
10. Aplikasi PHP native menyimpan dan menampilkan prediksi.

## 5. Data Ownership and Integration Decision

Final decision:

- **MySQL is owned by Native PHP.**
- **Python does not directly access MySQL.**
- **Native PHP sends training data payload to Python.**
- **Native PHP sends latest prediction window to Python.**
- **Native PHP persists model metadata and prediction records returned by Python.**
- **Python persists ML artifacts to disk.**

Why:

- Around 10 years of daily trading data is small, roughly 2,000-3,000 rows. JSON payload is acceptable.
- Business validation and dataset ownership remain centralized in the native PHP application.
- Python service remains reusable and easier to test with pure JSON input.
- Thesis explanation is simpler: PHP owns application data; Python owns ML computation.

Rejected alternative:

- Python direct MySQL access would reduce payload transfer but introduces duplicated database credentials, tighter coupling, and harder debugging.

## 6. Repository Structure

```text
copper-price-bigru/
|-- README.md
|-- .gitignore
|-- docs/
|   |-- architecture/
|   |   |-- system-architecture.md
|   |   |-- database-schema.md
|   |   `-- api-contract.md
|   |-- uml/
|   |   |-- use-case.md
|   |   |-- activity.md
|   |   |-- sequence.md
|   |   `-- class.md
|   |-- thesis-evidence/
|   |   |-- screenshots-checklist.md
|   |   |-- bab-iv-evidence-log.md
|   |   `-- final-training-summary-template.md
|   `-- api/
|       `-- openapi-notes.md
|-- web/
|   |-- app/
|   |   |-- Controllers/
|   |   |-- Core/
|   |   |-- Helpers/
|   |   |-- Models/
|   |   |-- Repositories/
|   |   `-- Services/
|   |-- config/
|   |-- database/
|   |   `-- schema.sql
|   |-- public/
|   |   |-- index.php
|   |   `-- assets/
|   |-- resources/
|   |   `-- views/
|   |-- routes/
|   |   `-- web.php
|   |-- tests/
|   |   `-- run.php
|   |-- storage/
|   `-- .env.example
|-- ml-service/
|   |-- app/
|   |   |-- api/
|   |   |-- core/
|   |   |-- schemas/
|   |   |-- services/
|   |   |-- ml/
|   |   `-- utils/
|   |-- artifacts/
|   |   |-- models/
|   |   |-- scalers/
|   |   `-- metadata/
|   |-- logs/
|   |-- tests/
|   |-- .env.example
|   `-- requirements.txt
|-- data/
|   |-- sample/
|   |   `-- sample_copper_prices.csv
|   `-- README.md
`-- scripts/
    |-- check-env.ps1
    `-- run-local.ps1
```

Purpose:

- `docs/`: architecture, UML, API, and thesis evidence.
- `web/`: Native PHP application.
- `ml-service/`: FastAPI ML service.
- `data/sample/`: small non-final CSV sample only.
- `scripts/`: local helper scripts, not required for production.
- Final private/proprietary dataset should not be committed unless explicitly permitted.

## 7. Local Development Prerequisites

Recommended:

| Software   |                                Version Range | Verification                                                 |
| ---------- | -------------------------------------------: | ------------------------------------------------------------ |
| Git        |                                          2.x | `git --version`                                              |
| PHP        |                                      8.2-8.4 | `php -v`                                                     |
| MySQL      |                                          8.x | `mysql --version`                                            |
| Python     |                                    3.10-3.13 | `py --version`                                               |
| TensorFlow | Current stable compatible with chosen Python | `python -c "import tensorflow as tf; print(tf.__version__)"` |
| Node.js    |                             20.x or 22.x LTS | `node -v`                                                    |
| VS Code    |                                      Current | manual                                                       |
| Laragon    |                               Current stable | manual                                                       |

TensorFlow note: official TensorFlow install documentation currently indicates TensorFlow 2.21 supports Python 3.10-3.13. Verify again before setup because ML package compatibility changes over time: https://www.tensorflow.org/install/pip

Primary local recommendation:

- Use Python 3.11 or 3.12 for lower dependency risk.
- Use CPU TensorFlow for thesis demo unless GPU setup is already stable.

## 8. Environment Configuration

PHP `web/.env`:

```env
APP_NAME="Copper Price BiGRU"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=copper_bigru
DB_USERNAME=root
DB_PASSWORD=

ML_API_URL=http://127.0.0.1:8001
ML_API_KEY=change-me-local
ML_API_TIMEOUT=300
```

Python `ml-service/.env`:

```env
APP_ENV=local
HOST=127.0.0.1
PORT=8001
API_KEY=change-me-local

MODEL_DIR=artifacts/models
SCALER_DIR=artifacts/scalers
METADATA_DIR=artifacts/metadata
LOG_DIR=logs

DEFAULT_WINDOW_SIZE=30
DEFAULT_UNITS=64
DEFAULT_DROPOUT=0.2
DEFAULT_BATCH_SIZE=32
DEFAULT_EPOCHS=50
DEFAULT_LEARNING_RATE=0.001
```

`.gitignore` rules:

```gitignore
.env
.env.*
!.env.example
/node_modules/
/web/storage/logs/*
/ml-service/.venv/
/ml-service/artifacts/models/*
/ml-service/artifacts/scalers/*
/ml-service/artifacts/metadata/*
/ml-service/logs/*
/data/final/
/data/private/
__pycache__/
.pytest_cache/
*.pyc
```

## 9. Database Design

### `users`

| Column         | Type            | Null | Default | Index  | Description            |
| -------------- | --------------- | ---: | ------- | ------ | ---------------------- |
| id             | BIGINT UNSIGNED |   No | auto    | PK     | User ID                |
| name           | VARCHAR(100)    |   No | -       | -      | Display name           |
| email          | VARCHAR(150)    |   No | -       | UNIQUE | Login email            |
| password       | VARCHAR(255)    |   No | -       | -      | Hashed password        |
| remember_token | VARCHAR(100)    |  Yes | NULL    | -      | Remember-me token |
| created_at     | TIMESTAMP       |  Yes | NULL    | -      | Created time           |
| updated_at     | TIMESTAMP       |  Yes | NULL    | -      | Updated time           |

### `copper_prices`

Use `DECIMAL`, not `FLOAT`, because price values are monetary/financial-like and should avoid binary floating precision issues.

| Column         | Type            | Null | Default | Index  | Description          |
| -------------- | --------------- | ---: | ------- | ------ | -------------------- |
| id             | BIGINT UNSIGNED |   No | auto    | PK     | Row ID               |
| date           | DATE            |   No | -       | UNIQUE | Trading date         |
| open           | DECIMAL(12,4)   |  Yes | NULL    | -      | Open price           |
| high           | DECIMAL(12,4)   |  Yes | NULL    | -      | High price           |
| low            | DECIMAL(12,4)   |  Yes | NULL    | -      | Low price            |
| close          | DECIMAL(12,4)   |   No | -       | INDEX  | Closing price target |
| volume         | BIGINT UNSIGNED |  Yes | NULL    | -      | Parsed volume        |
| change_percent | DECIMAL(8,4)    |  Yes | NULL    | -      | Percent change       |
| created_at     | TIMESTAMP       |  Yes | NULL    | -      | Created time         |
| updated_at     | TIMESTAMP       |  Yes | NULL    | -      | Updated time         |

### `import_histories`

| Column            | Type            | Null | Default | Index | Description            |
| ----------------- | --------------- | ---: | ------- | ----- | ---------------------- |
| id                | BIGINT UNSIGNED |   No | auto    | PK    | Import ID              |
| user_id           | BIGINT UNSIGNED |   No | -       | FK    | Admin who imported     |
| original_filename | VARCHAR(255)    |   No | -       | -     | Uploaded filename      |
| total_rows        | INT UNSIGNED    |   No | 0       | -     | Rows read              |
| valid_rows        | INT UNSIGNED    |   No | 0       | -     | Valid rows             |
| imported_rows     | INT UNSIGNED    |   No | 0       | -     | New rows               |
| updated_rows      | INT UNSIGNED    |   No | 0       | -     | Updated rows           |
| skipped_rows      | INT UNSIGNED    |   No | 0       | -     | Skipped rows           |
| duplicate_rows    | INT UNSIGNED    |   No | 0       | -     | Duplicate rows         |
| invalid_rows      | INT UNSIGNED    |   No | 0       | -     | Invalid rows           |
| status            | VARCHAR(30)     |   No | pending | INDEX | pending/success/failed |
| error_summary     | TEXT            |  Yes | NULL    | -     | Summary                |
| created_at        | TIMESTAMP       |  Yes | NULL    | -     | Import time            |
| updated_at        | TIMESTAMP       |  Yes | NULL    | -     | Updated time           |

### `model_runs`

| Column             | Type            | Null | Default    | Index  | Description                    |
| ------------------ | --------------- | ---: | ---------- | ------ | ------------------------------ |
| id                 | BIGINT UNSIGNED |   No | auto       | PK     | Model run ID                   |
| version            | VARCHAR(30)     |   No | -          | UNIQUE | e.g. bigru_v001                |
| model_name         | VARCHAR(100)    |   No | BiGRU      | -      | Model name                     |
| status             | VARCHAR(30)     |   No | pending    | INDEX  | pending/running/success/failed |
| is_active          | BOOLEAN         |   No | false      | INDEX  | Active model flag              |
| dataset_hash       | VARCHAR(64)     |  Yes | NULL       | INDEX  | Snapshot hash                  |
| total_records      | INT UNSIGNED    |   No | 0          | -      | Dataset size                   |
| dataset_start_date | DATE            |  Yes | NULL       | -      | First data date                |
| dataset_end_date   | DATE            |  Yes | NULL       | -      | Last data date                 |
| train_start_date   | DATE            |  Yes | NULL       | -      | Train start                    |
| train_end_date     | DATE            |  Yes | NULL       | -      | Train end                      |
| test_start_date    | DATE            |  Yes | NULL       | -      | Test start                     |
| test_end_date      | DATE            |  Yes | NULL       | -      | Test end                       |
| window_size        | INT UNSIGNED    |   No | 30         | -      | Lookback window                |
| units              | INT UNSIGNED    |   No | 64         | -      | GRU units                      |
| dropout            | DECIMAL(4,3)    |   No | 0.200      | -      | Dropout                        |
| batch_size         | INT UNSIGNED    |   No | 32         | -      | Batch size                     |
| configured_epochs  | INT UNSIGNED    |   No | 50         | -      | Max epochs                     |
| actual_epochs      | INT UNSIGNED    |  Yes | NULL       | -      | Actual epochs                  |
| best_epoch         | INT UNSIGNED    |  Yes | NULL       | -      | Best epoch                     |
| learning_rate      | DECIMAL(10,8)   |   No | 0.00100000 | -      | Adam LR                        |
| optimizer          | VARCHAR(50)     |   No | Adam       | -      | Optimizer                      |
| loss               | VARCHAR(50)     |   No | MSE        | -      | Loss                           |
| model_path         | VARCHAR(500)    |  Yes | NULL       | -      | Artifact path                  |
| scaler_path        | VARCHAR(500)    |  Yes | NULL       | -      | Scaler path                    |
| metadata_path      | VARCHAR(500)    |  Yes | NULL       | -      | Metadata path                  |
| error_message      | TEXT            |  Yes | NULL       | -      | Failure reason                 |
| requested_by       | BIGINT UNSIGNED |  Yes | NULL       | FK     | User                           |
| trained_at         | TIMESTAMP       |  Yes | NULL       | INDEX  | Training finish time           |
| created_at         | TIMESTAMP       |  Yes | NULL       | -      | Created                        |
| updated_at         | TIMESTAMP       |  Yes | NULL       | -      | Updated                        |

### `model_metrics`

| Column                    | Type            | Null | Default | Index      | Description         |
| ------------------------- | --------------- | ---: | ------- | ---------- | ------------------- |
| id                        | BIGINT UNSIGNED |   No | auto    | PK         | Metric ID           |
| model_run_id              | BIGINT UNSIGNED |   No | -       | FK, UNIQUE | Model run           |
| train_samples             | INT UNSIGNED    |   No | 0       | -          | Train sequences     |
| test_samples              | INT UNSIGNED    |   No | 0       | -          | Test sequences      |
| final_training_loss       | DECIMAL(18,8)   |  Yes | NULL    | -          | Final MSE loss      |
| final_validation_loss     | DECIMAL(18,8)   |  Yes | NULL    | -          | Validation loss     |
| mae                       | DECIMAL(18,6)   |   No | -       | INDEX      | Original scale MAE  |
| rmse                      | DECIMAL(18,6)   |   No | -       | INDEX      | Original scale RMSE |
| mape                      | DECIMAL(10,4)   |   No | -       | INDEX      | MAPE percent        |
| training_duration_seconds | DECIMAL(12,3)   |  Yes | NULL    | -          | Duration            |
| created_at                | TIMESTAMP       |  Yes | NULL    | -          | Created             |
| updated_at                | TIMESTAMP       |  Yes | NULL    | -          | Updated             |

### `predictions`

| Column                 | Type            | Null | Default           | Index     | Description                       |
| ---------------------- | --------------- | ---: | ----------------- | --------- | --------------------------------- |
| id                     | BIGINT UNSIGNED |   No | auto              | PK        | Prediction ID                     |
| model_run_id           | BIGINT UNSIGNED |   No | -                 | FK, INDEX | Model version                     |
| prediction_date        | DATE            |  Yes | NULL              | INDEX     | Target date if known              |
| input_start_date       | DATE            |   No | -                 | -         | Window start                      |
| input_end_date         | DATE            |   No | -                 | INDEX     | Latest observed date              |
| latest_observed_close  | DECIMAL(12,4)   |   No | -                 | -         | Last actual close                 |
| predicted_close        | DECIMAL(12,4)   |   No | -                 | INDEX     | Predicted close                   |
| actual_close           | DECIMAL(12,4)   |  Yes | NULL              | -         | Filled later if actual exists     |
| difference_from_latest | DECIMAL(12,4)   |  Yes | NULL              | -         | Predicted - latest close          |
| error_abs              | DECIMAL(12,4)   |  Yes | NULL              | -         | Absolute error after actual known |
| error_percent          | DECIMAL(10,4)   |  Yes | NULL              | -         | Percent error                     |
| generated_by           | BIGINT UNSIGNED |  Yes | NULL              | FK        | User                              |
| generated_at           | TIMESTAMP       |   No | CURRENT_TIMESTAMP | INDEX     | Generated time                    |
| created_at             | TIMESTAMP       |  Yes | NULL              | -         | Created                           |
| updated_at             | TIMESTAMP       |  Yes | NULL              | -         | Updated                           |

### `prediction_inputs`

Justified because it preserves evidence of exact input window without relying on later edited data.

| Column         | Type            | Null | Default | Index     | Description          |
| -------------- | --------------- | ---: | ------- | --------- | -------------------- |
| id             | BIGINT UNSIGNED |   No | auto    | PK        | Input row ID         |
| prediction_id  | BIGINT UNSIGNED |   No | -       | FK, INDEX | Prediction           |
| sequence_order | INT UNSIGNED    |   No | -       | -         | 1..window_size       |
| date           | DATE            |   No | -       | -         | Input date           |
| close          | DECIMAL(12,4)   |   No | -       | -         | Input close snapshot |

Relationships:

- `users` has many `import_histories`, `model_runs`, `predictions`.
- `model_runs` has one `model_metrics`.
- `model_runs` has many `predictions`.
- `predictions` has many `prediction_inputs`.

ERD summary:

```text
users 1---* import_histories
users 1---* model_runs
users 1---* predictions
model_runs 1---1 model_metrics
model_runs 1---* predictions
predictions 1---* prediction_inputs
copper_prices independent time-series table keyed by date
```

## 10. PHP Application Design

### 10.1 Authentication

- Routes:
  - `GET /login`
  - `POST /login`
  - `POST /logout`
  - `GET /profile`
  - `PUT /profile`
  - `PUT /profile/password`
- Controller:
  - `AuthController`
  - `ProfileController`
- Validation:
  - email required, valid email.
  - password required.
  - new password min 8, confirmed.
- Middleware:
  - `auth` for protected pages.
  - `guest` for login page.
- Service:
  - PHP native session auth with password_hash() and password_verify().
- Model:
  - `User`
- View:
  - `auth/login.php`
  - `profile/show.php`
- DB:
  - `users`
- Behavior:
  - invalid login redirects back with generic message.
  - logout invalidates session and regenerates CSRF token.
  - logged-in user visiting login redirects to dashboard.
  - session lifetime from PHP config, recommended 120 minutes local.

### 10.2 Dashboard

- Route: `GET /dashboard`
- Controller: `DashboardController@index`
- Service: `DashboardService`
- View: `dashboard/index.php`
- DB:
  - `copper_prices`
  - `model_runs`
  - `model_metrics`
  - `predictions`
- Cards:
  - Always available if data exists: total observations, earliest date, latest date, latest close, historical chart.
  - Show "No model available": latest model version, trained date, MAE, RMSE, MAPE, actual vs predicted chart.
  - Show "No prediction available": latest prediction.
- API:
  - none required for dashboard read.

### 10.3 Copper Price Management

- Routes:
  - `GET /prices`
  - `GET /prices/create`
  - `POST /prices`
  - `GET /prices/{price}`
  - `GET /prices/{price}/edit`
  - `PUT /prices/{price}`
  - `DELETE /prices/{price}`
- Controller: `CopperPriceController`
- Request validation:
  - date required, valid date, unique.
  - close required, numeric, `> 0`.
  - open/high/low nullable, numeric, `> 0`.
  - hard error: `high < low`.
  - warning: high lower than open/close or low higher than open/close.
  - volume nullable integer `>= 0`.
  - change_percent nullable numeric.
- Service:
  - `CopperPriceService`
- Model:
  - `CopperPrice`
- View:
  - `prices/index`, `show`, `create`, `edit`.
- DB:
  - `copper_prices`
- API:
  - none.

### 10.4 CSV Import

- Routes:
  - `GET /imports`
  - `GET /imports/create`
  - `POST /imports/preview`
  - `POST /imports/confirm`
  - `GET /imports/{history}`
- Controller:
  - `CsvImportController`
- Validation:
  - extension: `.csv`.
  - MIME: `text/csv`, `text/plain`, accepted browser CSV MIME variants.
  - max size: 5 MB.
- Service:
  - `CsvParserService`
  - `CopperPriceImportService`
- Duplicate policy:
  - **Update existing rows by date after preview confirmation.**
  - Rationale: Investing.com corrected data or re-import should refresh existing rows safely.
- DB:
  - `copper_prices`
  - `import_histories`
- Transaction:
  - confirm step runs in transaction.
- Output:
  - total, valid, imported, updated, skipped, duplicate, invalid.

### 10.5 Model Management

- Routes:
  - `GET /models`
  - `GET /models/{modelRun}`
  - `POST /models/train`
  - `POST /models/{modelRun}/activate`
- Controller:
  - `ModelController`
- Service:
  - `ModelRunService`
  - `MlApiClient`
- DB:
  - `model_runs`
  - `model_metrics`
- API:
  - `POST /api/v1/train`
  - `GET /api/v1/models/{version}`
- Active model policy:
  - newest successful training becomes active automatically.
  - previous model remains stored.
  - manual activation supports rollback.

### 10.6 Prediction

- Routes:
  - `GET /predictions`
  - `GET /predictions/create`
  - `POST /predictions`
  - `GET /predictions/{prediction}`
- Controller:
  - `PredictionController`
- Service:
  - `PredictionService`
  - `MlApiClient`
- DB:
  - `model_runs`
  - `model_metrics`
  - `predictions`
  - `prediction_inputs`
  - `copper_prices`
- API:
  - `POST /api/v1/predict`
- Output:
  - generated time, predicted close, model version, latest close, difference, metric summary, input date range.

### 10.7 Evaluation

- Route:
  - `GET /evaluation`
  - `GET /evaluation/{modelRun}`
- Controller:
  - `EvaluationController`
- Service:
  - `EvaluationService`
- DB:
  - `model_runs`
  - `model_metrics`
- API:
  - optional `GET /api/v1/models/{version}`
- View:
  - metrics cards, actual vs predicted chart from returned/stored summary.

### 10.8 Reports

Minimum reports:

1. Historical Copper Price Report.
2. Prediction History Report.
3. Model Evaluation Report.
4. Summary Report.

- Routes:
  - `GET /reports`
  - `GET /reports/historical`
  - `GET /reports/predictions`
  - `GET /reports/evaluation`
  - `GET /reports/summary`
- Controller:
  - `ReportController`
- View:
  - printable PHP template pages.
- Strategy:
  - browser print with print CSS hides nav/buttons.

### 10.9 Profile

- Routes:
  - `GET /profile`
  - `PUT /profile`
  - `PUT /profile/password`
- Controller:
  - `ProfileController`
- Validation:
  - name required.
  - email unique except current user.
  - current password required for password change.
- DB:
  - `users`

## 11. Python ML Service Design

Modules:

| Module                       | Purpose                            | Inputs            | Outputs           | Dependencies           |
| ---------------------------- | ---------------------------------- | ----------------- | ----------------- | ---------------------- |
| `app/main.py`                | FastAPI app bootstrap              | env               | app               | FastAPI                |
| `app/core/config.py`         | env settings                       | `.env`            | settings object   | pydantic-settings      |
| `app/core/security.py`       | API key auth                       | header            | pass/fail         | FastAPI                |
| `app/core/logging.py`        | logging setup                      | env               | logger            | logging                |
| `app/schemas/dataset.py`     | request schemas                    | JSON              | typed models      | Pydantic               |
| `app/schemas/model.py`       | model responses                    | JSON              | typed responses   | Pydantic               |
| `app/ml/preprocessing.py`    | clean, sort, split, scale, windows | close series      | arrays/scaler     | pandas, numpy, sklearn |
| `app/ml/model_builder.py`    | build BiGRU                        | config            | Keras model       | TensorFlow             |
| `app/ml/training.py`         | train/evaluate                     | dataset/config    | artifacts/metrics | TensorFlow             |
| `app/ml/prediction.py`       | load model/scaler/predict          | window/version    | predicted close   | TensorFlow, joblib     |
| `app/ml/metrics.py`          | MAE/RMSE/MAPE                      | y_true/y_pred     | metrics           | numpy                  |
| `app/services/artifacts.py`  | save/load paths                    | version/artifacts | files             | joblib, json           |
| `app/services/versioning.py` | next version                       | metadata dir      | version           | pathlib                |
| `app/api/routes.py`          | endpoints                          | HTTP              | JSON              | FastAPI                |

## 12. API Contract

Auth:

- Header: `X-API-Key: <key>`
- Invalid/missing key: `401`.

### `GET /api/v1/health`

- Auth: optional or required; recommend required except `/health` can be public local.
- Response:

```json
{
  "status": "ok",
  "service": "ml-service",
  "version": "1.0.0"
}
```

### `POST /api/v1/train`

- Auth: required.
- Request:

```json
{
  "dataset": [{ "date": "2016-01-01", "close": 2.1234 }],
  "config": {
    "window_size": 30,
    "units": 64,
    "dropout": 0.2,
    "batch_size": 32,
    "epochs": 50,
    "learning_rate": 0.001
  }
}
```

- Validation:
  - dates parseable.
  - sorted after service sorting.
  - no duplicate dates.
  - close positive.
  - enough records.
  - window size less than training size.
- Response `200`:

```json
{
  "status": "success",
  "version": "bigru_v001",
  "metrics": {
    "mae": 0.123456,
    "rmse": 0.234567,
    "mape": 3.21
  },
  "training": {
    "actual_epochs": 28,
    "best_epoch": 22,
    "final_training_loss": 0.0012,
    "final_validation_loss": 0.0015,
    "duration_seconds": 42.5
  },
  "dataset": {
    "total_records": 2500,
    "train_samples": 1970,
    "test_samples": 500,
    "train_start_date": "2016-01-01",
    "train_end_date": "2024-01-01",
    "test_start_date": "2024-01-02",
    "test_end_date": "2026-01-01"
  },
  "artifacts": {
    "model_path": "artifacts/models/model_bigru_v001.keras",
    "scaler_path": "artifacts/scalers/scaler_bigru_v001.joblib",
    "metadata_path": "artifacts/metadata/metadata_bigru_v001.json"
  }
}
```

- Errors:
  - `400` validation.
  - `422` insufficient data.
  - `500` training/artifact failure.

### `POST /api/v1/predict`

- Auth: required.
- Request:

```json
{
  "model_version": "bigru_v001",
  "window": [{ "date": "2026-07-01", "close": 4.1234 }]
}
```

- Validation:
  - exactly model window size rows.
  - positive close.
  - ascending dates.
  - model/scaler exists.
- Response:

```json
{
  "status": "success",
  "model_version": "bigru_v001",
  "input_start_date": "2026-07-01",
  "input_end_date": "2026-08-10",
  "latest_observed_close": 4.573,
  "predicted_close": 4.6123,
  "generated_at": "2026-08-11T10:00:00+07:00"
}
```

### `GET /api/v1/models/{version}`

- Auth: required.
- Response:
  - metadata JSON.
- Errors:
  - `404` unknown model.

### `GET /api/v1/models/{version}/metrics`

- Auth: required.
- Response:
  - MAE, RMSE, MAPE, sample counts, date ranges.

## 13. Machine Learning Pipeline

Steps:

1. Receive dataset from native PHP app.
2. Convert to DataFrame.
3. Parse `date`.
4. Sort ascending by `date`.
5. Reject duplicate dates.
6. Validate `close > 0`, no NaN, no Inf.
7. Extract `close` as shape `(n, 1)`.
8. Chronological split by observation:
   - `split_index = floor(n * 0.8)`
   - training observations: `[0 : split_index]`
   - testing observations: `[split_index : n]`
9. Fit `MinMaxScaler(feature_range=(0, 1))` on training closes only.
10. Transform entire close series using train-fitted scaler.
11. Create leakage-safe sequences for train and test.
12. Build BiGRU.
13. Train with `shuffle=False`.
14. Predict on test sequences.
15. Inverse transform predictions and labels.
16. Compute MAE, RMSE, MAPE in original price scale.
17. Save artifacts.
18. Return metrics and metadata.

## 14. Leakage Prevention Design

### 14.1 Split Before Or After Sliding Window?

Recommended:

- Determine chronological train/test boundary first.
- Fit scaler using only training observations.
- Create train and test windows with explicit target indexing.

Reason:

- This makes train/test boundary visible and auditable.
- It prevents accidental fitting scaler on test values.
- It allows first test prediction to use prior training observations as context.

### 14.2 Scaler Fit

Only:

```python
scaler.fit(train_close_values)
```

Never:

```python
scaler.fit(all_close_values)
scaler.fit(test_close_values)
```

Then:

```python
scaled_all = scaler.transform(all_close_values)
```

This is acceptable because transform uses min/max learned only from training.

### 14.3 Index Rule

For target at index `t`:

```text
X_t = [
  close_(t-window),
  close_(t-window+1),
  ...
  close_(t-1)
]

y_t = close_t
```

Never include:

```text
close_t
close_(t+1)
close_(t+2)
```

### 14.4 Example

If `window_size = 30` and target index is `80`:

```text
X_80 = close_50 ... close_79
y_80 = close_80
```

### 14.5 First Test Prediction

If:

```text
n = 100
split_index = 80
train = index 0..79
test = index 80..99
window = 30
```

First test target:

```text
target t = 80
X_80 = index 50..79
y_80 = index 80
```

Yes, the 30 most recent training observations may be used to predict the first test observation because they are historically prior to the test target. That is not leakage because those observations would be known before `date_80`.

### 14.6 Later Test Windows

For target `t = 81`:

```text
X_81 = index 51..80
y_81 = index 81
```

This includes `close_80`, which is an earlier actual test observation. For offline evaluation this is acceptable if simulating one-step-ahead forecasting where actual previous day data is known before predicting the next day.

If strict multi-step future forecasting is desired later, use recursive prediction. Not recommended for primary thesis unless methodology changes.

### 14.7 Do Test Labels Enter Input Windows?

A test label may enter a later input only after its timestamp is earlier than the later target. It must never enter its own input.

Allowed:

```text
close_80 used in X_81
```

Forbidden:

```text
close_81 used in X_81
close_82 used in X_81
```

### 14.8 Pseudocode

```python
def make_sequences(scaled_values, dates, start_target_idx, end_target_idx, window):
    X, y, target_dates = [], [], []

    for t in range(start_target_idx, end_target_idx):
        input_start = t - window
        input_end_exclusive = t

        if input_start < 0:
            continue

        x_t = scaled_values[input_start:input_end_exclusive]
        y_t = scaled_values[t]

        assert len(x_t) == window
        assert input_end_exclusive - 1 < t

        X.append(x_t)
        y.append(y_t)
        target_dates.append(dates[t])

    return np.array(X), np.array(y), target_dates
```

Train sequences:

```python
train_start_target = window_size
train_end_target = split_index
```

Test sequences:

```python
test_start_target = split_index
test_end_target = n
```

## 15. BiGRU Model Design

Input:

```text
(batch_size, window_size, features)
(batch_size, 30, 1)
```

Baseline architecture:

```python
Sequential([
    Input(shape=(30, 1)),
    Bidirectional(GRU(64, return_sequences=False)),
    Dropout(0.2),
    Dense(1)
])
```

Dense(32, relu):

- Optional but not recommended for baseline.
- The simpler architecture is easier to explain and less likely to overfit small daily data.

Recommended initial configuration:

| Parameter     | Value | Note                        |
| ------------- | ----: | --------------------------- |
| window_size   |    30 | 30 trading observations     |
| features      |     1 | close only                  |
| GRU units     |    64 | initial research config     |
| dropout       |   0.2 | regularization              |
| batch_size    |    32 | common baseline             |
| epochs        |    50 | max, not guaranteed optimum |
| learning_rate | 0.001 | Adam default-like           |
| optimizer     |  Adam | required                    |
| loss          |   MSE | required                    |
| shuffle       | False | time-series safety          |

Callbacks:

- `EarlyStopping(monitor="val_loss", patience=10, restore_best_weights=True)`
- `ModelCheckpoint(save_best_only=True)`
- `ReduceLROnPlateau(monitor="val_loss", patience=5, factor=0.5)`

Validation approach:

- Keep thesis 80:20 train/test split.
- Inside the 80% training observations, reserve the tail 10% of training sequences as chronological validation.
- Test data is never used for EarlyStopping.

## 16. Training Workflow

1. Admin clicks train.
2. Native PHP app checks:
   - enough data.
   - active Python service reachable.
3. Native PHP app creates pending `model_runs`.
4. Native PHP app sends dataset/config to FastAPI.
5. FastAPI validates and trains.
6. FastAPI saves temporary artifacts.
7. FastAPI verifies model/scaler/metadata can be reloaded.
8. FastAPI renames/moves temp artifacts to final version paths.
9. FastAPI returns success metadata.
10. Native PHP app stores metrics and artifact paths.
11. Native PHP app marks previous active model inactive.
12. Native PHP app marks new successful model active.
13. Dashboard and model page update.

Failure:

- Native PHP app marks run failed.
- Active model remains unchanged.
- Error is logged and shown safely.

## 17. Prediction Workflow

1. Admin opens prediction page.
2. Native PHP app loads active model.
3. Native PHP app checks at least `window_size` observations exist.
4. Native PHP app gets latest `window_size` closes ordered ascending.
5. Native PHP app sends window and model version to FastAPI.
6. FastAPI loads model/scaler/metadata.
7. FastAPI validates shape `(1, window_size, 1)`.
8. FastAPI scales using saved scaler.
9. FastAPI predicts normalized value.
10. FastAPI inverse transforms to price scale.
11. Native PHP app stores prediction and input window snapshot.
12. UI shows result.

Actual value handling:

- A future prediction is not labeled actual.
- When a later `copper_prices.date` matches `prediction_date`, Native PHP app may attach `actual_close` and compute errors.

## 18. Evaluation Workflow

Evaluation occurs after training:

1. Test sequences are predicted.
2. Predictions and true labels are inverse transformed.
3. Metrics are computed on original price scale:
   - MAE.
   - RMSE.
   - MAPE.
4. Actual vs predicted chart data is produced.
5. Summary metrics stored in MySQL.
6. Detailed arrays may be stored in metadata JSON or returned for display.

MAPE handling:

- If any `actual == 0`, exclude from MAPE or raise validation.
- Because close price must be `> 0`, zero should not occur.

## 19. Model Versioning and Artifact Management

Path format:

```text
ml-service/artifacts/models/model_bigru_v001.keras
ml-service/artifacts/scalers/scaler_bigru_v001.joblib
ml-service/artifacts/metadata/metadata_bigru_v001.json
```

Metadata records:

- version.
- model name.
- created_at.
- dataset hash.
- total records.
- dataset start/end.
- train/test ranges.
- window size.
- features.
- scaler type.
- units.
- dropout.
- batch size.
- configured/actual epochs.
- best epoch.
- learning rate.
- optimizer.
- loss.
- MAE/RMSE/MAPE.
- TensorFlow version.
- Python version.
- paths.
- status.

Rollback:

- Admin can activate a previous successful model.
- Failed run never replaces active model.
- Temporary artifacts removed if integrity check fails.

## 20. CSV Import Workflow

Accepted:

- `.csv`
- max 5 MB.
- UTF-8 preferred.

Header mapping:

| Raw              | DB             |
| ---------------- | -------------- |
| Date             | date           |
| Price, Close     | close          |
| Open             | open           |
| High             | high           |
| Low              | low            |
| Vol., Volume     | volume         |
| Change %, Change | change_percent |

Parsing:

- Date examples:
  - `08/10/2026`
  - parse explicitly as Investing.com format, confirm locale during implementation.
- Price:
  - remove commas.
  - parse decimal.
- Volume:
  - `45.20K` -> `45200`.
  - `1.30M` -> `1300000`.
  - `-` -> `NULL`.
- Change:
  - `1.25%` -> `1.2500`.
  - `-0.35%` -> `-0.3500`.
  - `-` -> `NULL`.

Workflow:

1. Upload.
2. Validate file.
3. Parse rows.
4. Show dry-run preview.
5. Mark row errors/warnings.
6. Confirm import.
7. Transaction upserts by unique `date`.
8. Store import summary.
9. Show result.

Hard errors:

- missing Date.
- missing Close.
- invalid date.
- invalid close.
- close <= 0.
- high < low.
- malformed numeric.

Warnings:

- high lower than open/close.
- low higher than open/close.
- missing volume/change.

## 21. Authentication Workflow

Login:

1. Guest opens `/login`.
2. Submit email/password with CSRF token.
3. Native PHP app checks credentials.
4. On success:
   - regenerate session.
   - redirect `/dashboard`.
5. On failure:
   - log email/IP attempt.
   - show generic invalid credentials.

Logout:

1. POST `/logout`.
2. invalidate session.
3. regenerate token.
4. redirect `/login`.

Protected pages:

- use `auth` middleware.
- unauthenticated users redirect to login.

Password change:

- current password required.
- new password min 8 and confirmed.
- store hash only.

## 22. CRUD Workflow

Create:

1. Open form.
2. Validate.
3. Save.
4. Redirect list with success.

Update:

1. Load existing row.
2. Validate unique date except current row.
3. Save.
4. Redirect show/list.

Delete:

1. Confirm.
2. Delete.
3. Redirect list.

Filtering:

- date from/to.
- search date/close.
- pagination 25 or 50 rows.

## 23. Reporting Workflow

Reports:

1. Historical report:
   - filters date range.
   - table and chart.
2. Prediction report:
   - prediction history.
   - model version.
3. Evaluation report:
   - model metrics and actual vs predicted chart.
4. Summary report:
   - dataset range, latest model, latest metrics, latest prediction.

Print behavior:

- `@media print`
- hide sidebar/nav/buttons.
- show report title, timestamp, filter criteria.

## 24. UI Information Architecture

Menu:

```text
Dashboard
Historical Data
CSV Import
Model Training
Models
Prediction
Prediction History
Evaluation
Reports
Profile
Logout
```

Page states:

- No data:
  - Dashboard cards show empty state.
  - Training disabled.
- Data exists, no model:
  - Prediction disabled.
  - Evaluation shows no model.
- Model exists:
  - Prediction enabled.
  - Evaluation available.
- Python offline:
  - Training/prediction buttons show service unavailable.

## 25. UML Plan

Use Case:

- Login.
- Manage copper prices.
- Import CSV.
- Train model.
- View evaluation.
- Run prediction.
- Print reports.
- Manage profile.

Activity:

- CSV import.
- Training workflow.
- Prediction workflow.
- Login workflow.

Sequence:

- Native PHP to FastAPI training.
- Native PHP to FastAPI prediction.
- CSV import save.

Class:

- User.
- CopperPrice.
- ImportHistory.
- ModelRun.
- ModelMetric.
- Prediction.
- PredictionInput.
- Services/controllers.

Architecture Diagram:

- Browser, Native PHP, MySQL, FastAPI, TensorFlow, artifacts.

## 26. Security Plan

- Password hashing via password_hash().
- CSRF on all forms.
- Auth middleware on protected pages.
- API key for Native PHP -> FastAPI.
- Do not expose Python service publicly in local demo.
- Validate all request payloads.
- Escape template output with htmlspecialchars() by default.
- File upload:
  - CSV only.
  - size limit.
  - no direct public execution.
- Logs:
  - no passwords.
  - no DB passwords.
  - no API keys.
  - no session IDs.

## 27. Validation Plan

Web validation:

- Form request classes.
- Unique date.
- decimal bounds.
- date filters.

CSV validation:

- header mapping.
- row-level errors.
- duplicate date detection.
- numeric parser tests.

ML validation:

- sorted dates.
- no duplicates.
- close positive.
- no NaN/Inf.
- enough observations.
- valid split.
- expected shapes.

API validation:

- Pydantic schemas.
- API key.
- status codes.
- malformed response handling in native PHP app.

## 28. Error Handling Strategy

| Error                     | Layer   | User Message                       | Log             | Recovery               |
| ------------------------- | ------- | ---------------------------------- | --------------- | ---------------------- |
| Invalid email/password    | Auth    | Email atau password salah          | email/IP        | Retry                  |
| Unauthorized access       | Auth    | Silakan login                      | route/user      | Login                  |
| MySQL unavailable         | DB      | Database tidak tersedia            | exception       | Start MySQL            |
| Duplicate date            | DB/Data | Tanggal sudah ada                  | date            | Edit existing          |
| Missing CSV header        | CSV     | Header CSV tidak lengkap           | filename/header | Fix CSV                |
| Invalid date              | CSV     | Format tanggal tidak valid         | row/value       | Correct row            |
| Invalid close             | CSV     | Close harus angka positif          | row/value       | Correct row            |
| Empty CSV                 | CSV     | File tidak berisi data             | filename        | Upload valid CSV       |
| Python offline            | API     | Layanan ML tidak aktif             | URL/timeout     | Start FastAPI          |
| API timeout               | API     | Training terlalu lama atau timeout | endpoint        | Increase timeout/retry |
| Invalid API key           | API     | Konfigurasi API tidak valid        | endpoint only   | Fix env                |
| Insufficient observations | ML      | Data belum cukup untuk training    | counts          | Add data               |
| NaN/Inf                   | ML      | Data mengandung nilai tidak valid  | index           | Clean data             |
| Model unavailable         | ML      | Model aktif tidak tersedia         | version         | Train/activate model   |
| Scaler unavailable        | ML      | Scaler model tidak tersedia        | path            | Restore artifact       |
| Prediction NaN            | ML      | Prediksi tidak valid               | input/version   | Check model            |
| No report data            | Report  | Tidak ada data pada rentang ini    | filters         | Change filters         |

## 29. Logging Plan

PHP logs:

- auth failures.
- CSV import summary.
- CRUD failures.
- training requests.
- prediction requests.
- API connectivity errors.
- report errors.

Python logs:

- startup.
- model/scaler load.
- training started/completed.
- dataset counts.
- split counts.
- sequence shapes.
- epoch completion summary.
- metrics.
- artifact save/load.
- prediction request/completion.
- validation errors.
- exceptions.

Never log:

- passwords.
- DB passwords.
- API key.
- session IDs.
- full secrets.

## 30. Testing Strategy

PHP feature tests:

- login success/failure.
- protected route.
- logout.
- price CRUD.
- unique date.
- validation.
- filtering/pagination.
- CSV import valid/invalid.
- API online/offline/timeout.
- reports load/print CSS.

Python unit tests:

- date parse.
- numeric parse.
- chronological sort.
- duplicate detection.
- split logic.
- scaler fit train only.
- sliding windows no leakage.
- tensor shapes.
- inverse transform.
- metrics.
- artifact save/load.

API tests:

- health.
- auth.
- train validation.
- predict validation.
- no model.
- insufficient data.

Integration tests:

- PHP health check.
- PHP train request.
- metadata persistence.
- PHP prediction request.
- prediction saved.
- dashboard reads latest model.

Acceptance tests:

| Test ID   | Feature            | Preconditions          | Steps             | Expected Result     | Actual Result | Status |
| --------- | ------------------ | ---------------------- | ----------------- | ------------------- | ------------- | ------ |
| AUTH-01   | Login              | User exists            | Login valid       | Dashboard shown     |               |        |
| DATA-01   | Add data           | Logged in              | Add valid row     | Row saved           |               |        |
| DATA-02   | Filter data        | Data exists            | Filter dates      | Correct rows shown  |               |        |
| IMPORT-01 | CSV import         | Valid CSV              | Preview + confirm | Summary shown       |               |        |
| TRAIN-01  | Train success      | Enough data/API online | Click train       | Model success       |               |        |
| TRAIN-02  | Train blocked      | Too little data        | Click train       | Error shown         |               |        |
| PRED-01   | Prediction success | Active model           | Run prediction    | Prediction saved    |               |        |
| PRED-02   | Prediction blocked | No model               | Open page         | Disabled state      |               |        |
| EVAL-01   | Evaluation         | Model exists           | Open evaluation   | Metrics/chart shown |               |        |
| REPORT-01 | Historical         | Data exists            | Print report      | Printable report    |               |        |
| REPORT-02 | Prediction         | Predictions exist      | Print report      | Printable report    |               |        |
| REPORT-03 | Evaluation         | Model exists           | Print report      | Printable report    |               |        |
| REPORT-04 | Summary            | Data/model exists      | Print report      | Printable summary   |               |        |

## 31. Final Dataset Migration Plan

Checklist:

- Confirm source: Investing.com Copper Futures historical data.
- Confirm date range.
- Export daily historical rows.
- Do not fabricate weekends.
- Do not forward-fill holidays/weekends.
- Import through CSV preview.
- Verify:
  - start/end date.
  - row count.
  - duplicate count.
  - missing close count.
  - close positive.
  - high/low consistency.
  - no unexpected text.
  - no NaN after cleaning.
- Record:
  - dataset count.
  - train/test boundary.
  - training/test date ranges.

## 32. Local Demo Plan

Services:

1. Start MySQL via Laragon.
2. Start PHP native app:

```powershell
cd web
php -S 127.0.0.1:8000 -t public
```

3. Start FastAPI:

```powershell
cd ml-service
.\.venv\Scripts\Activate.ps1
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

4. Open:

```text
http://127.0.0.1:8000
```

Demo order:

1. Login.
2. Dashboard empty/data state.
3. Import CSV.
4. View historical data.
5. Train model.
6. View metrics.
7. Run prediction.
8. Print reports.

## 33. Optional Production Deployment Plan

Not required for thesis completion.

VPS scenario:

- Apache/Nginx or PHP built-in server for the native PHP app.
- MySQL server.
- Python FastAPI as service.
- Uvicorn/Gunicorn behind reverse proxy.
- Environment variables stored securely.
- Artifacts stored outside public web root.
- HTTPS required.
- Firewall Python API to local/private network where possible.

## 34. Thesis BAB IV Evidence Plan

| Feature         | Evidence                                          |
| --------------- | ------------------------------------------------- |
| Login           | screenshot                                        |
| Dashboard       | screenshot                                        |
| Historical Data | screenshot/table                                  |
| Add/Edit Data   | screenshots                                       |
| CSV Import      | screenshot/import summary                         |
| Dataset         | first rows, last rows, count                      |
| Preprocessing   | min/max, normalized sample                        |
| Sliding Window  | real day 1-30 -> day 31 example                    |
| Split           | train/test count/date range                       |
| Model           | summary, hyperparameter table                     |
| Training        | loss chart, epoch count, duration                 |
| Evaluation      | MAE/RMSE/MAPE, actual vs predicted chart          |
| Prediction      | result screenshot                                 |
| Reports         | four printable reports                            |
| UML             | use case, activity, sequence, class, architecture |

Do not write final BAB IV numerical claims until final model is actually trained.

## 35. Definition of Done

AUTH:

- [ ] Login works
- [ ] Logout works
- [ ] Protected routes work
- [ ] Password hash verified

DATA:

- [ ] Historical list
- [ ] Create/edit/delete
- [ ] Filter/search/pagination
- [ ] CSV import with validation

MODEL:

- [ ] Preprocessing
- [ ] Chronological split
- [ ] Train-only scaler
- [ ] Sliding window
- [ ] BiGRU training
- [ ] Artifact save
- [ ] Metadata save

EVALUATION:

- [ ] Inverse transform
- [ ] MAE/RMSE/MAPE
- [ ] Actual vs predicted chart

API:

- [ ] health
- [ ] model info
- [ ] train
- [ ] predict
- [ ] API auth

INTEGRATION:

- [ ] PHP reaches API
- [ ] API unavailable handled
- [ ] Training result stored
- [ ] Prediction stored

REPORTS:

- [ ] Historical
- [ ] Prediction
- [ ] Evaluation
- [ ] Summary
- [ ] Print CSS

DOCUMENTATION:

- [ ] README
- [ ] API contract
- [ ] DB schema
- [ ] UML
- [ ] BAB IV evidence

## 36. Risk Register

| Risk                           | Probability | Impact | Mitigation                                            |
| ------------------------------ | ----------- | ------ | ----------------------------------------------------- |
| TensorFlow setup problem       | Medium      | High   | Use supported Python, CPU install, verify docs        |
| PHP/Python integration failure | Medium      | High   | Build health endpoint early                           |
| Data format changes            | Medium      | Medium | Header mapper and row errors                          |
| Insufficient time              | Medium      | High   | Phase-by-phase implementation                         |
| Poor training results          | Medium      | Medium | Present metrics honestly, tune only documented params |
| ML service offline             | Medium      | Medium | UI service status and error handling                  |
| Data leakage                   | Medium      | High   | Explicit indexing tests                               |
| CSV parsing edge cases         | High        | Medium | Parser unit tests                                     |
| Thesis scope creep             | High        | High   | Keep univariate close-only model                      |

## 37. Implementation Phases

### PHASE 0 - Repository Bootstrap

Objective: create safe monorepo skeleton and documentation placeholders.

Preconditions: empty or inspected repo.

Tasks:

- [ ] Inspect repo.
- [ ] Initialize Git if needed.
- [ ] Create root structure.
- [ ] Add root README.
- [ ] Add `.gitignore`.
- [ ] Add docs placeholders.
- [ ] Add sample data README.

Files Created: `README.md`, `.gitignore`, `docs/*`, `data/README.md`.

Files Modified: none existing unless approved.

Database Changes: none.

API Changes: none.

UI Changes: none.

Tests Added: none.

Commands to Run:

```powershell
git status
git init
git branch -M main
```

Expected Output: clean skeleton.

Verification Checklist:

- [ ] folders exist.
- [ ] no framework installed.
- [ ] no generated app yet.

Common Failure Cases: repo not empty.

Rollback / Recovery: remove only Phase 0 files if created incorrectly.

Completion Criteria: skeleton ready.

Dependencies: none.

Recommended Git Commit Message: `chore: bootstrap repository`

### PHASE 1 - Native PHP + Database Skeleton

Objective: create native PHP app skeleton and database connection.

Preconditions: Phase 0 complete.

Tasks:

- [ ] Create native PHP structure in `web/`.
- [ ] Configure `.env`.
- [ ] Create MySQL database.
- [ ] Create SQL schema files and run them manually through MySQL/phpMyAdmin.
- [ ] Add base layout.

Files Created: native PHP app files.

Files Modified: web/.env.example, front controller, routes, layout.

Database Changes: users/session/cache default tables as needed.

API Changes: none.

UI Changes: base layout.

Tests Added: app boot test.

Commands to Run:

```powershell
cd web
Copy-Item .env.example .env
mysql -u root -e "CREATE DATABASE IF NOT EXISTS copper_bigru"
mysql -u root copper_bigru < database/schema.sql
php tests/run.php
```

Expected Output: native PHP app landing/dashboard shell works.

Verification Checklist:

- [ ] `/` loads.
- [ ] DB connects.
- [ ] tests pass.

Common Failure Cases: PHP extension missing, DB credentials wrong.

Rollback / Recovery: fix env/extensions.

Completion Criteria: native PHP app runs locally.

Dependencies: Phase 0.

Recommended Git Commit Message: `feat: add laravel database skeleton`

### PHASE 2 - Authentication

Objective: login/logout/profile.

Preconditions: Phase 1.

Tasks:

- [ ] Add auth routes/controllers/templates.
- [ ] Seed admin user.
- [ ] Protect dashboard route.
- [ ] Add profile/password change.

Files Created: auth controllers/views/tests.

Files Modified: routes, User repository/model.

Database Changes: users table used.

API Changes: none.

UI Changes: login/profile.

Tests Added: auth tests.

Commands to Run:

```powershell
mysql -u root copper_bigru < database/schema.sql
php tests/run.php --filter=Auth
```

Expected Output: auth flow works.

Verification Checklist:

- [ ] valid login.
- [ ] invalid login.
- [ ] logout.
- [ ] protected route.

Common Failure Cases: missing seed user.

Rollback / Recovery: rerun seeder.

Completion Criteria: authentication complete.

Dependencies: Phase 1.

Recommended Git Commit Message: `feat: add authentication`

### PHASE 3 - Copper Price CRUD

Objective: manage historical price rows.

Preconditions: auth complete.

Tasks:

- [ ] Create SQL table and repository/model.
- [ ] Add controller/service/requests.
- [ ] Add list/filter/search/pagination.
- [ ] Add create/edit/delete.
- [ ] Add validation warnings.

Files Created: `CopperPrice` files.

Files Modified: routes/navigation.

Database Changes: `copper_prices`.

API Changes: none.

UI Changes: price pages.

Tests Added: CRUD tests.

Commands to Run:

```powershell
mysql -u root copper_bigru < database/schema.sql
php tests/run.php --filter=CopperPrice
```

Expected Output: CRUD works.

Verification Checklist:

- [ ] unique date.
- [ ] close positive.
- [ ] filters work.

Common Failure Cases: decimal casting issues.

Rollback / Recovery: adjust casts/validation.

Completion Criteria: data manageable.

Dependencies: Phase 2.

Recommended Git Commit Message: `feat: add copper price management`

### PHASE 4 - CSV Import

Objective: import Investing.com CSV safely.

Preconditions: Phase 3.

Tasks:

- [ ] Build parser.
- [ ] Header mapping.
- [ ] Numeric/date parsing.
- [ ] Dry-run preview.
- [ ] Confirm upsert transaction.
- [ ] Import history.

Files Created: import controller/service/views/tests.

Files Modified: routes/navigation.

Database Changes: `import_histories`.

API Changes: none.

UI Changes: import pages.

Tests Added: CSV parser/import tests.

Commands to Run:

```powershell
mysql -u root copper_bigru < database/schema.sql
php tests/run.php --filter=Import
```

Expected Output: valid CSV imports with summary.

Verification Checklist:

- [ ] K/M volume.
- [ ] percent parse.
- [ ] duplicate update.
- [ ] invalid rows reported.

Common Failure Cases: ambiguous date format.

Rollback / Recovery: explicit date format config.

Completion Criteria: CSV import reliable.

Dependencies: Phase 3.

Recommended Git Commit Message: `feat: add csv import`

### PHASE 5 - Python ML Service Foundation

Objective: FastAPI base service.

Preconditions: Phase 0, Python available.

Tasks:

- [ ] Create venv.
- [ ] Add requirements.
- [ ] Create FastAPI app.
- [ ] Add config/security/logging.
- [ ] Add health endpoint.

Files Created: `ml-service/*`.

Files Modified: root docs.

Database Changes: none.

API Changes: `/health`.

UI Changes: none.

Tests Added: health/auth tests.

Commands to Run:

```powershell
cd ml-service
py -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements.txt
pytest
uvicorn app.main:app --reload --port 8001
```

Expected Output: health endpoint returns ok.

Verification Checklist:

- [ ] service starts.
- [ ] API key works.
- [ ] tests pass.

Common Failure Cases: dependency conflict.

Rollback / Recovery: pin compatible versions.

Completion Criteria: FastAPI foundation ready.

Dependencies: Phase 0.

Recommended Git Commit Message: `feat: add ml service foundation`

### PHASE 6 - Data Preprocessing Pipeline

Objective: leakage-safe preprocessing.

Preconditions: Phase 5.

Tasks:

- [ ] Sort/validate dataset.
- [ ] Chronological split.
- [ ] Train-only scaler fit.
- [ ] Sequence generation.
- [ ] Metrics helpers.
- [ ] Unit tests for leakage.

Files Created: preprocessing modules/tests.

Files Modified: schemas.

Database Changes: none.

API Changes: internal only.

UI Changes: none.

Tests Added: preprocessing tests.

Commands to Run:

```powershell
pytest tests/test_preprocessing.py
```

Expected Output: no leakage tests pass.

Verification Checklist:

- [ ] target not in input.
- [ ] scaler fit train only.
- [ ] first test window uses train context.

Common Failure Cases: off-by-one index.

Rollback / Recovery: correct sequence assertions.

Completion Criteria: preprocessing verified.

Dependencies: Phase 5.

Recommended Git Commit Message: `feat: add leakage-safe preprocessing pipeline`

### PHASE 7 - BiGRU Model

Objective: implement baseline Keras model.

Preconditions: Phase 6.

Tasks:

- [ ] Add model builder.
- [ ] Define architecture.
- [ ] Add model summary utility.
- [ ] Add shape tests.

Files Created: model builder/tests.

Files Modified: config.

Database Changes: none.

API Changes: none.

UI Changes: none.

Tests Added: model shape tests.

Commands to Run:

```powershell
pytest tests/test_model_builder.py
```

Expected Output: model builds with `(30,1)`.

Verification Checklist:

- [ ] output shape one value.
- [ ] architecture matches baseline.

Common Failure Cases: TensorFlow import failure.

Rollback / Recovery: fix Python/TF version.

Completion Criteria: BiGRU model buildable.

Dependencies: Phase 6.

Recommended Git Commit Message: `feat: add bigru model`

### PHASE 8 - Training and Evaluation

Objective: train model and compute metrics.

Preconditions: Phase 7.

Tasks:

- [ ] Training service.
- [ ] Chronological validation split inside train.
- [ ] Callbacks.
- [ ] Inverse transform.
- [ ] MAE/RMSE/MAPE.

Files Created: training/evaluation modules.

Files Modified: preprocessing.

Database Changes: none.

API Changes: none.

UI Changes: none.

Tests Added: training smoke test.

Commands to Run:

```powershell
pytest tests/test_training.py
```

Expected Output: training completes on small sample.

Verification Checklist:

- [ ] no test data for early stopping.
- [ ] metrics original scale.

Common Failure Cases: too little data.

Rollback / Recovery: add minimum data check.

Completion Criteria: training pipeline works.

Dependencies: Phase 7.

Recommended Git Commit Message: `feat: add training and evaluation pipeline`

### PHASE 9 - Model Versioning and Artifact Storage

Objective: save versioned model/scaler/metadata.

Preconditions: Phase 8.

Tasks:

- [ ] Version generator.
- [ ] Artifact save/load.
- [ ] Metadata JSON.
- [ ] Integrity check.
- [ ] Failure cleanup.

Files Created: artifact/versioning modules/tests.

Files Modified: training service.

Database Changes: none.

API Changes: none.

UI Changes: none.

Tests Added: artifact tests.

Commands to Run:

```powershell
pytest tests/test_artifacts.py
```

Expected Output: artifacts reload.

Verification Checklist:

- [ ] no overwrite.
- [ ] metadata complete.

Common Failure Cases: path permission.

Rollback / Recovery: fix artifact dirs.

Completion Criteria: versioned artifacts reliable.

Dependencies: Phase 8.

Recommended Git Commit Message: `feat: add model artifact versioning`

### PHASE 10 - Python REST API

Objective: expose train/predict/model endpoints.

Preconditions: Phase 9.

Tasks:

- [ ] Train endpoint.
- [ ] Predict endpoint.
- [ ] Model metadata endpoint.
- [ ] Error response format.
- [ ] OpenAPI review.

Files Created: API route modules/tests.

Files Modified: main app.

Database Changes: none.

API Changes: train/predict/model.

UI Changes: none.

Tests Added: API tests.

Commands to Run:

```powershell
pytest tests/test_api.py
```

Expected Output: endpoints validated.

Verification Checklist:

- [ ] auth required.
- [ ] bad data rejected.
- [ ] no model handled.

Common Failure Cases: long request timeout.

Rollback / Recovery: tune timeout.

Completion Criteria: API ready.

Dependencies: Phase 9.

Recommended Git Commit Message: `feat: expose ml rest api`

### PHASE 11 - PHP-Python Integration

Objective: native PHP API client and persistence.

Preconditions: Phases 4 and 10.

Tasks:

- [ ] Add ML API config.
- [ ] Add Native PHP cURL client.
- [ ] Health check.
- [ ] Train request.
- [ ] Prediction request.
- [ ] Error mapping.

Files Created: `MlApiClient`, tests.

Files Modified: config/routes.

Database Changes: `model_runs`, `model_metrics`, `predictions`, `prediction_inputs`.

API Changes: none.

UI Changes: service status display.

Tests Added: mocked API tests.

Commands to Run:

```powershell
mysql -u root copper_bigru < database/schema.sql
php tests/run.php --filter=MlApi
```

Expected Output: native PHP app handles API success/failure.

Verification Checklist:

- [ ] timeout handled.
- [ ] invalid response handled.
- [ ] metadata stored.

Common Failure Cases: env mismatch API key.

Rollback / Recovery: sync `.env`.

Completion Criteria: integration works.

Dependencies: Phase 10.

Recommended Git Commit Message: `feat: integrate php with ml api`

### PHASE 12 - Model Management UI

Objective: training and model version pages.

Preconditions: Phase 11.

Tasks:

- [ ] Model list/detail.
- [ ] Training page.
- [ ] Trigger training.
- [ ] Activate previous model.
- [ ] Show metrics.

Files Created: model controllers/views.

Files Modified: nav/routes.

Database Changes: uses model tables.

API Changes: uses train/model.

UI Changes: model pages.

Tests Added: model feature tests.

Commands to Run:

```powershell
php tests/run.php --filter=Model
```

Expected Output: admin can train/view models.

Verification Checklist:

- [ ] successful model active.
- [ ] failed model not active.

Common Failure Cases: long training request.

Rollback / Recovery: increase timeout or async later.

Completion Criteria: model UI complete.

Dependencies: Phase 11.

Recommended Git Commit Message: `feat: add model management ui`

### PHASE 13 - Prediction UI and History

Objective: prediction form/result/history.

Preconditions: Phase 12.

Tasks:

- [ ] Prediction page.
- [ ] Latest window display.
- [ ] Run prediction.
- [ ] Store input snapshot.
- [ ] History/detail pages.

Files Created: prediction controllers/views.

Files Modified: routes/navigation.

Database Changes: predictions tables used.

API Changes: uses predict.

UI Changes: prediction pages.

Tests Added: prediction tests.

Commands to Run:

```powershell
php tests/run.php --filter=Prediction
```

Expected Output: predictions saved and displayed.

Verification Checklist:

- [ ] no model disabled.
- [ ] less than window disabled.
- [ ] result not labeled actual.

Common Failure Cases: missing artifacts.

Rollback / Recovery: activate valid model.

Completion Criteria: prediction workflow complete.

Dependencies: Phase 12.

Recommended Git Commit Message: `feat: add prediction ui and history`

### PHASE 14 - Evaluation UI

Objective: show metrics and charts.

Preconditions: Phase 12.

Tasks:

- [ ] Metrics cards.
- [ ] Actual vs predicted chart.
- [ ] Model selector.
- [ ] Interpretation notes.

Files Created: evaluation controller/view.

Files Modified: routes/navigation.

Database Changes: none.

API Changes: optional model metadata.

UI Changes: evaluation page.

Tests Added: evaluation page tests.

Commands to Run:

```powershell
php tests/run.php --filter=Evaluation
```

Expected Output: evaluation displays.

Verification Checklist:

- [ ] no model empty state.
- [ ] metrics original scale.

Common Failure Cases: missing chart data.

Rollback / Recovery: use stored metadata summary.

Completion Criteria: evaluation UI complete.

Dependencies: Phase 12.

Recommended Git Commit Message: `feat: add evaluation ui`

### PHASE 15 - Reports

Objective: four printable reports.

Preconditions: Phases 13 and 14.

Tasks:

- [ ] Historical report.
- [ ] Prediction report.
- [ ] Evaluation report.
- [ ] Summary report.
- [ ] Print CSS.

Files Created: report controller/views/css.

Files Modified: routes/nav/layout.

Database Changes: none.

API Changes: none.

UI Changes: report pages.

Tests Added: report tests.

Commands to Run:

```powershell
php tests/run.php --filter=Report
```

Expected Output: printable reports load.

Verification Checklist:

- [ ] nav hidden in print.
- [ ] filters shown.
- [ ] no fabricated metrics.

Common Failure Cases: chart print issues.

Rollback / Recovery: include table fallback.

Completion Criteria: reports complete.

Dependencies: Phase 14.

Recommended Git Commit Message: `feat: add printable reports`

### PHASE 16 - UML and Documentation

Objective: thesis-ready docs.

Preconditions: major features done.

Tasks:

- [ ] README setup.
- [ ] API contract.
- [ ] DB schema docs.
- [ ] UML docs.
- [ ] BAB IV evidence checklist.

Files Created/Modified: `docs/*`, `README.md`.

Database Changes: none.

API Changes: none.

UI Changes: none.

Tests Added: none.

Commands to Run: none required.

Expected Output: documentation complete.

Verification Checklist:

- [ ] setup works from README.
- [ ] diagrams match implementation.

Common Failure Cases: docs drift.

Rollback / Recovery: update docs.

Completion Criteria: docs ready.

Dependencies: Phases 1-15.

Recommended Git Commit Message: `docs: add thesis documentation`

### PHASE 17 - Automated Testing

Objective: complete test coverage.

Preconditions: features implemented.

Tasks:

- [ ] PHP feature tests.
- [ ] Python unit/API tests.
- [ ] Integration tests.
- [ ] Acceptance table.

Files Created/Modified: test files.

Database Changes: test DB only.

API Changes: none.

UI Changes: none.

Tests Added: all planned tests.

Commands to Run:

```powershell
cd web
php tests/run.php

cd ..\ml-service
pytest
```

Expected Output: tests pass.

Verification Checklist:

- [ ] leakage tests pass.
- [ ] API failure tests pass.
- [ ] reports tests pass.

Common Failure Cases: flaky integration.

Rollback / Recovery: mock external API where appropriate.

Completion Criteria: test suite stable.

Dependencies: Phases 1-15.

Recommended Git Commit Message: `test: add automated coverage`

### PHASE 18 - Final 10-Year Dataset

Objective: import final dataset.

Preconditions: import works.

Tasks:

- [ ] Download final CSV.
- [ ] Keep private if needed.
- [ ] Import preview.
- [ ] Resolve invalid rows.
- [ ] Confirm import.
- [ ] Record dataset evidence.

Files Created: optional private local CSV, evidence notes.

Files Modified: DB data.

Database Changes: `copper_prices` data.

API Changes: none.

UI Changes: none.

Tests Added: none.

Commands to Run: application import via UI.

Expected Output: final data imported.

Verification Checklist:

- [ ] date range confirmed.
- [ ] count recorded.
- [ ] no duplicates.
- [ ] no invalid close.

Common Failure Cases: date locale mismatch.

Rollback / Recovery: clear imported rows only if explicitly approved.

Completion Criteria: final dataset ready.

Dependencies: Phase 4.

Recommended Git Commit Message: no commit for private dataset; if sample only, `docs: record final dataset checklist`.

### PHASE 19 - Final Training and Evaluation

Objective: train final model and record real metrics.

Preconditions: final dataset imported.

Tasks:

- [ ] Run training.
- [ ] Verify artifacts.
- [ ] Store metrics.
- [ ] Capture charts/tables.
- [ ] Interpret metrics conservatively.

Files Created: artifacts, evidence screenshots.

Files Modified: DB model records.

Database Changes: model/prediction metrics data.

API Changes: none.

UI Changes: none.

Tests Added: none.

Commands to Run: through UI and service startup.

Expected Output: final model active.

Verification Checklist:

- [ ] scaler train-only.
- [ ] metrics original scale.
- [ ] model version recorded.

Common Failure Cases: poor result.

Rollback / Recovery: tune documented hyperparameters or explain limitation.

Completion Criteria: final metrics available.

Dependencies: Phase 18.

Recommended Git Commit Message: no artifact commit unless allowed; `docs: add final training evidence` if evidence docs only.

### PHASE 20 - Thesis Evidence and Final Verification

Objective: final demo readiness.

Preconditions: final training complete.

Tasks:

- [ ] Capture screenshots.
- [ ] Print reports.
- [ ] Verify acceptance tests.
- [ ] Review docs.
- [ ] Prepare demo script.

Files Created/Modified: evidence docs/screenshots if committed.

Database Changes: none.

API Changes: none.

UI Changes: none.

Tests Added: final acceptance results.

Commands to Run:

```powershell
php tests/run.php
pytest
```

Expected Output: demo-ready project.

Verification Checklist:

- [ ] all DoD items checked.
- [ ] no fake results.
- [ ] no secrets committed.

Common Failure Cases: missing screenshot.

Rollback / Recovery: retake evidence after fixing.

Completion Criteria: thesis demonstration ready.

Dependencies: Phase 19.

Recommended Git Commit Message: `docs: add thesis evidence and final verification`

## 38. Dependency Map

Sequential:

```text
0 -> 1 -> 2 -> 3 -> 4
0 -> 5 -> 6 -> 7 -> 8 -> 9 -> 10
4 + 10 -> 11 -> 12 -> 13 -> 14 -> 15
1..15 -> 16 -> 17
4 + 15 -> 18 -> 19 -> 20
```

Parallel possible:

- PHP CRUD/import can progress while Python ML foundation is built.
- UML/documentation drafts can start after architecture stabilizes.
- Python unit tests can progress before PHP integration.

## 39. Critical Path

1. Repository bootstrap.
2. Native PHP + database.
3. Copper price data storage.
4. CSV import.
5. ML preprocessing with leakage prevention.
6. BiGRU training/evaluation.
7. REST API.
8. PHP integration.
9. Prediction/evaluation UI.
10. Final dataset import.
11. Final training.
12. Evidence capture.

## 40. Implementation Order

### A. Folder/File Creation Order

1. root docs/data structure.
2. Native PHP app.
3. Python service.
4. docs updates.
5. scripts.

### B. Migration/Table Creation Order

1. users.
2. copper_prices.
3. import_histories.
4. model_runs.
5. model_metrics.
6. predictions.
7. prediction_inputs.

### C. PHP Module Order

1. auth.
2. dashboard shell.
3. copper prices.
4. import.
5. API client.
6. model management.
7. prediction.
8. evaluation.
9. reports.
10. profile polish.

### D. Python Module Order

1. config/security/logging.
2. schemas.
3. preprocessing.
4. metrics.
5. model builder.
6. training.
7. artifacts.
8. prediction.
9. API routes.

### E. API Endpoint Order

1. health.
2. train.
3. model metadata.
4. predict.
5. metrics.

### F. UI Page Order

1. login.
2. dashboard.
3. historical data.
4. import.
5. model training.
6. model detail.
7. prediction.
8. evaluation.
9. reports.
10. profile.

### G. Testing Order

1. Python preprocessing leakage tests.
2. PHP auth/data tests.
3. CSV parser tests.
4. Python API tests.
5. Native PHP API client tests.
6. integration tests.
7. acceptance tests.

## 41. First Development Sprint

PHASE 0 command-by-command plan only, not executed:

```powershell
# 1. Inspect current directory
Get-ChildItem -Force
git status

# 2. Initialize Git only if not already initialized
git init
git branch -M main

# 3. Create folders
New-Item -ItemType Directory -Force docs
New-Item -ItemType Directory -Force docs\architecture
New-Item -ItemType Directory -Force docs\uml
New-Item -ItemType Directory -Force docs\thesis-evidence
New-Item -ItemType Directory -Force docs\api
New-Item -ItemType Directory -Force web
New-Item -ItemType Directory -Force ml-service
New-Item -ItemType Directory -Force data
New-Item -ItemType Directory -Force data\sample
New-Item -ItemType Directory -Force scripts

# 4. Create root planning files
New-Item -ItemType File README.md
New-Item -ItemType File .gitignore
New-Item -ItemType File docs\architecture\system-architecture.md
New-Item -ItemType File docs\architecture\database-schema.md
New-Item -ItemType File docs\architecture\api-contract.md
New-Item -ItemType File docs\uml\use-case.md
New-Item -ItemType File docs\uml\activity.md
New-Item -ItemType File docs\uml\sequence.md
New-Item -ItemType File docs\uml\class.md
New-Item -ItemType File docs\thesis-evidence\screenshots-checklist.md
New-Item -ItemType File docs\thesis-evidence\bab-iv-evidence-log.md
New-Item -ItemType File data\README.md

# 5. Verify
Get-ChildItem -Recurse -Depth 2
git status --short
```

## 42. READY-TO-IMPLEMENT CHECKLIST

- [ ] Architecture approved.
- [ ] Native PHP + FastAPI accepted.
- [ ] PHP owns MySQL data.
- [ ] Python receives JSON payload.
- [ ] Univariate close-only method accepted.
- [ ] Leakage-safe indexing accepted.
- [ ] CSV duplicate update policy accepted.
- [ ] Automatic active-on-success accepted.
- [ ] HTML print reports accepted.
- [ ] Phase 0 prompt ready.

## 43. QUESTIONS / ASSUMPTIONS

No blocking questions.

Assumptions:

- One role, Admin/System User, is sufficient.
- Final dataset will be imported later via CSV.
- Local thesis demo is the primary target.
- CPU TensorFlow is acceptable unless GPU is already configured.
- Sample CSV may be committed, final dataset should remain private unless explicitly approved.

## 44. NEXT CODEX PROMPT

# NEXT CODEX PROMPT

Implement **PHASE 0 - Repository Bootstrap** only.

Instructions:

- First inspect the repository before modifying anything.
- Do not install dependencies.
- Do not initialize Laravel or any PHP framework.
- Do not create a Python virtual environment.
- Do not create database schema files in Phase 0.
- Do not write production application code.
- Change only Phase 0 files and folders.
- Show the planned Phase 0 changes before editing.
- Then implement the Phase 0 repository skeleton.
- Run verification commands after implementation.
- Report:
  - files/folders created;
  - files modified;
  - commands run;
  - verification results;
  - any deviations from the plan.
- Stop before Phase 1.

