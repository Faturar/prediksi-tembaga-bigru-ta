# API Contract

Base URL defaults to `http://127.0.0.1:8001`.

All `/api/*` endpoints require `X-API-Key`.

## `GET /health`

Returns service status.

## `POST /api/train`

Receives model hyperparameters and ordered price points:

```json
{
  "version": "bigru_20260811_120000",
  "window_size": 30,
  "units": 64,
  "dropout": 0.2,
  "batch_size": 32,
  "epochs": 50,
  "learning_rate": 0.001,
  "data": [{"date": "2024-01-01", "close": 3.82}]
}
```

Returns artifact paths, chronological 80:20 date ranges, sample counts, and original-scale MAE/RMSE/MAPE metrics. Training uses one Bidirectional GRU layer, no validation split, and `shuffle=False`.

## `POST /api/predict`

Receives active model version and the latest close-price window. Returns the predicted close value for the next trading observation. `prediction_date` is `null` unless a real trading calendar is implemented.

## `GET /api/models/{version}`

Returns saved model metadata, including split dates, model type, window size, and test-series actual/predicted values when available.
