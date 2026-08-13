from fastapi.testclient import TestClient
from pydantic import ValidationError

from app.main import app
from app.schemas.ml import PredictRequest, PredictResponse


client = TestClient(app)


def test_health():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_api_requires_key():
    response = client.post("/api/train", json={})
    assert response.status_code == 401


def test_prediction_response_allows_null_prediction_date():
    response = PredictResponse(
        model_version="m1",
        predicted_close=6.7123,
        prediction_date=None,
        predictions=[{"step": 1, "predicted_close": 6.7123}],
    )
    assert response.prediction_date is None


def test_prediction_horizon_defaults_to_one():
    request = PredictRequest(model_version="m1", window=[{"date": "2026-08-11", "close": 6.5}])
    assert request.horizon == 1


def test_prediction_horizon_rejects_zero():
    try:
        PredictRequest(model_version="m1", window=[{"date": "2026-08-11", "close": 6.5}], horizon=0)
    except ValidationError:
        return
    raise AssertionError("horizon=0 should fail validation")


def test_prediction_horizon_rejects_above_seven():
    try:
        PredictRequest(model_version="m1", window=[{"date": "2026-08-11", "close": 6.5}], horizon=8)
    except ValidationError:
        return
    raise AssertionError("horizon=8 should fail validation")
