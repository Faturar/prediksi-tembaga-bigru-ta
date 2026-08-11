from fastapi.testclient import TestClient
from app.main import app
from app.schemas.ml import PredictResponse


client = TestClient(app)


def test_health():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"


def test_api_requires_key():
    response = client.post("/api/train", json={})
    assert response.status_code == 401


def test_prediction_response_allows_null_prediction_date():
    response = PredictResponse(model_version="m1", predicted_close=6.7123, prediction_date=None)
    assert response.prediction_date is None
