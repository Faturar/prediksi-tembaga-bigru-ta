# Acceptance Checklist

- Admin can login with seeded account.
- Admin can add copper price data manually.
- Admin can import CSV data and see import statistics.
- Imported duplicate dates update existing rows.
- Admin can trigger model training from the PHP UI.
- PHP sends ordered close prices to FastAPI over REST.
- FastAPI fits scaler on training data only.
- Train/test split is chronological and unshuffled.
- Metrics are calculated on original price scale.
- Successful training stores model metadata and marks the model active.
- Admin can run prediction using the active model.
- Prediction history stores input window and predicted close.
- Evaluation page shows model metrics.
- Printable reports load without fabricated metrics.
- Secrets and final/private datasets are not committed.
