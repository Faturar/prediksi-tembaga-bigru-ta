import numpy as np


def regression_metrics(actual: np.ndarray, predicted: np.ndarray) -> dict[str, float]:
    actual = actual.reshape(-1)
    predicted = predicted.reshape(-1)
    mae = float(np.mean(np.abs(actual - predicted)))
    rmse = float(np.sqrt(np.mean((actual - predicted) ** 2)))
    safe_actual = np.where(actual == 0, np.nan, actual)
    mape = float(np.nanmean(np.abs((actual - predicted) / safe_actual)) * 100)
    return {"mae": mae, "rmse": rmse, "mape": mape}
