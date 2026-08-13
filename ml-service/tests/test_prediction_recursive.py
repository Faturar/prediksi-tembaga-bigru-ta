from datetime import date, timedelta
from pathlib import Path

import numpy as np

from app.schemas.ml import PredictRequest, PricePoint
from app.services.prediction import PredictionService


class FakeScaler:
    def __init__(self):
        self.transform_calls = 0
        self.inverse_calls = 0

    def transform(self, values):
        self.transform_calls += 1
        return np.asarray(values, dtype=float) / 100.0

    def inverse_transform(self, values):
        self.inverse_calls += 1
        return np.asarray(values, dtype=float) * 100.0


class FakeModel:
    def __init__(self):
        self.calls = []

    def predict(self, x, verbose=0):
        self.calls.append(np.array(x, copy=True))
        return np.array([[x[0, -1, 0] + 0.01]])


class FakeArtifacts:
    def __init__(self, scaler):
        self.scaler = scaler

    def load_metadata(self, version):
        return {"window_size": 3}

    def load_scaler(self, version):
        return self.scaler

    def paths(self, version):
        return {"model": Path(__file__)}


def window_points(count=3):
    start = date(2026, 8, 1)
    return [PricePoint(date=start + timedelta(days=i), close=100 + i) for i in range(count)]


def service_with_fakes():
    scaler = FakeScaler()
    model = FakeModel()
    service = PredictionService()
    service.artifacts = FakeArtifacts(scaler)
    service._load_model = lambda path: model
    return service, scaler, model


def test_one_step_output_still_works():
    service, _, _ = service_with_fakes()
    response = service.predict(PredictRequest(model_version="m1", window=window_points(), horizon=1))
    assert response["predicted_close"] == response["predictions"][0]["predicted_close"]
    assert response["horizon"] == 1
    assert response["prediction_date"] is None


def test_horizon_five_produces_five_ordered_steps():
    service, _, _ = service_with_fakes()
    response = service.predict(PredictRequest(model_version="m1", window=window_points(), horizon=5))
    assert len(response["predictions"]) == 5
    assert [point["step"] for point in response["predictions"]] == [1, 2, 3, 4, 5]


def test_model_loads_once_and_scaler_is_not_refit():
    service, scaler, model = service_with_fakes()
    service.predict(PredictRequest(model_version="m1", window=window_points(), horizon=5))
    assert len(model.calls) == 5
    assert scaler.transform_calls == 1
    assert not hasattr(scaler, "fit")


def test_recursive_step_two_uses_step_one_output_and_fixed_window_length():
    service, _, model = service_with_fakes()
    service.predict(PredictRequest(model_version="m1", window=window_points(), horizon=2))
    assert model.calls[0].shape == (1, 3, 1)
    assert model.calls[1].shape == (1, 3, 1)
    step_one_scaled_output = model.calls[0][0, -1, 0] + 0.01
    assert model.calls[1][0, -1, 0] == step_one_scaled_output


def test_no_future_actual_observations_are_required():
    service, _, _ = service_with_fakes()
    response = service.predict(PredictRequest(model_version="m1", window=window_points(), horizon=3))
    assert "actual" not in response["predictions"][0]
