from datetime import date, timedelta

import numpy as np
import pytest

from app.ml.metrics import regression_metrics
from app.ml.preprocessing import prepare_dataset
from app.schemas.ml import PricePoint


def points(count=60):
    start = date(2024, 1, 1)
    return [PricePoint(date=start + timedelta(days=i), close=100 + i) for i in range(count)]


def test_scaler_fits_training_only():
    prepared = prepare_dataset(points(), window_size=5)
    assert prepared.scaler.data_max_[0] == 147
    assert prepared.test_prices[0][0] == 148


def test_chronological_split_remains_80_20():
    prepared = prepare_dataset(points(100), window_size=5)
    assert prepared.train_end_index == 80
    assert prepared.dates[0] == date(2024, 1, 1)
    assert prepared.dates[79] == date(2024, 3, 20)
    assert prepared.dates[80] == date(2024, 3, 21)
    assert len(prepared.test_prices) == 20


def test_first_test_window_uses_train_context():
    prepared = prepare_dataset(points(), window_size=5)
    first_test_target = prepared.train_end_index
    assert prepared.dates[first_test_target].isoformat() == "2024-02-18"
    assert prepared.x_test.shape[1:] == (5, 1)
    expected_context = prepared.all_scaled[first_test_target - 5:first_test_target]
    np.testing.assert_allclose(prepared.x_test[0], expected_context)


def test_target_never_appears_inside_own_window():
    prepared = prepare_dataset(points(), window_size=5)
    for i, target in enumerate(prepared.y_train):
        assert not np.any(prepared.x_train[i] == target)
    for i, target in enumerate(prepared.y_test):
        assert not np.any(prepared.x_test[i] == target)


def test_mape_zero_handling():
    metrics = regression_metrics(np.array([[0.0], [100.0]]), np.array([[10.0], [90.0]]))
    assert metrics["mape"] == 10.0


def test_duplicate_dates_rejected():
    duplicate = points(10)
    duplicate[-1] = PricePoint(date=duplicate[0].date, close=200)
    with pytest.raises(ValueError, match="duplicate dates"):
        prepare_dataset(duplicate, window_size=3)
