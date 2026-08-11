from datetime import date, timedelta

from app.ml.preprocessing import prepare_dataset
from app.schemas.ml import PricePoint


def points(count=60):
    start = date(2024, 1, 1)
    return [PricePoint(date=start + timedelta(days=i), close=100 + i) for i in range(count)]


def test_scaler_fits_training_only():
    prepared = prepare_dataset(points(), window_size=5)
    assert prepared.scaler.data_max_[0] == 147
    assert prepared.test_prices[0][0] == 148


def test_first_test_window_uses_train_context():
    prepared = prepare_dataset(points(), window_size=5)
    first_test_target = prepared.train_end_index
    assert prepared.dates[first_test_target].isoformat() == "2024-02-18"
    assert prepared.x_test.shape[1:] == (5, 1)
