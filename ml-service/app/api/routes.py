from fastapi import APIRouter, Depends, HTTPException

from app.core.security import require_api_key
from app.schemas.ml import PredictRequest, PredictResponse, TrainRequest, TrainResponse
from app.services.artifacts import ArtifactStore
from app.services.prediction import PredictionService
from app.services.training import TrainingService

router = APIRouter(prefix="/api", dependencies=[Depends(require_api_key)])


@router.post("/train", response_model=TrainResponse)
def train(request: TrainRequest):
    try:
        return TrainingService().train(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except RuntimeError as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc


@router.post("/predict", response_model=PredictResponse)
def predict(request: PredictRequest):
    try:
        return PredictionService().predict(request)
    except FileNotFoundError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except RuntimeError as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc


@router.get("/models/{version}")
def model_metadata(version: str):
    try:
        return ArtifactStore().load_metadata(version)
    except FileNotFoundError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
