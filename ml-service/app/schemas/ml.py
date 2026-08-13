from datetime import date

from pydantic import BaseModel, Field


class PricePoint(BaseModel):
    date: date
    close: float = Field(gt=0)


class TrainRequest(BaseModel):
    version: str
    data: list[PricePoint]
    window_size: int = Field(default=30, ge=2)
    units: int = Field(default=64, ge=1)
    dropout: float = Field(default=0.2, ge=0, lt=1)
    batch_size: int = Field(default=32, ge=1)
    epochs: int = Field(default=50, ge=1)
    learning_rate: float = Field(default=0.001, gt=0)


class PredictRequest(BaseModel):
    model_version: str
    window: list[PricePoint]
    horizon: int = Field(default=1, ge=1, le=7)


class Metrics(BaseModel):
    final_training_loss: float | None = None
    final_validation_loss: float | None = None
    mae: float
    rmse: float
    mape: float


class TrainResponse(BaseModel):
    version: str
    total_records: int
    dataset_start_date: date
    dataset_end_date: date
    train_start_date: date
    train_end_date: date
    test_start_date: date
    test_end_date: date
    train_samples: int
    test_samples: int
    actual_epochs: int
    best_epoch: int | None = None
    model_path: str
    scaler_path: str
    metadata_path: str
    metrics: Metrics
    training_duration_seconds: float


class ForecastPoint(BaseModel):
    step: int
    predicted_close: float


class PredictResponse(BaseModel):
    model_version: str
    predicted_close: float
    prediction_date: date | None = None
    horizon: int = 1
    strategy: str = "recursive"
    predictions: list[ForecastPoint]
