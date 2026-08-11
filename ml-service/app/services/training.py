import time

import joblib
import numpy as np
from sklearn.linear_model import LinearRegression

from app.ml.metrics import regression_metrics
from app.ml.model import build_bigru
from app.ml.preprocessing import prepare_dataset
from app.services.artifacts import ArtifactStore


class TrainingService:
    def __init__(self):
        self.artifacts = ArtifactStore()

    def train(self, request) -> dict:
        started = time.perf_counter()
        prepared = prepare_dataset(request.data, request.window_size)
        paths = self.artifacts.paths(request.version)

        final_training_loss = None
        final_validation_loss = None
        actual_epochs = request.epochs
        best_epoch = request.epochs

        try:
            model = build_bigru(request.window_size, request.units, request.dropout, request.learning_rate)
            history = model.fit(
                prepared.x_train,
                prepared.y_train,
                validation_split=0.2,
                epochs=request.epochs,
                batch_size=request.batch_size,
                verbose=0,
            )
            model.save(paths["model"])
            predicted_scaled = model.predict(prepared.x_test, verbose=0)
            final_training_loss = float(history.history["loss"][-1])
            final_validation_loss = float(history.history.get("val_loss", [None])[-1])
            model_path = paths["model"]
            model_type = "keras_bigru"
        except RuntimeError:
            # Keeps the API usable in lightweight local environments; thesis final training should install TensorFlow.
            fallback = LinearRegression()
            fallback.fit(prepared.x_train.reshape((prepared.x_train.shape[0], -1)), prepared.y_train.reshape(-1))
            joblib.dump(fallback, paths["fallback_model"])
            predicted_scaled = fallback.predict(prepared.x_test.reshape((prepared.x_test.shape[0], -1))).reshape(-1, 1)
            model_path = paths["fallback_model"]
            model_type = "linear_regression_fallback"
            actual_epochs = 1
            best_epoch = 1

        actual = prepared.prices[prepared.train_end_index:]
        predicted = prepared.scaler.inverse_transform(predicted_scaled)
        metrics = regression_metrics(actual, predicted)
        test_dates = prepared.dates[prepared.train_end_index:]

        scaler_path = self.artifacts.save_scaler(request.version, prepared.scaler)
        metadata = {
            "version": request.version,
            "model_type": model_type,
            "window_size": request.window_size,
            "dataset_start_date": prepared.dates[0],
            "dataset_end_date": prepared.dates[-1],
            "train_start_date": prepared.dates[0],
            "train_end_date": prepared.dates[prepared.train_end_index - 1],
            "test_start_date": prepared.dates[prepared.train_end_index],
            "test_end_date": prepared.dates[-1],
            "last_date": prepared.dates[-1],
            "test_series": [
                {
                    "date": test_dates[i],
                    "actual": round(float(actual[i][0]), 6),
                    "predicted": round(float(predicted[i][0]), 6),
                }
                for i in range(len(test_dates))
            ],
        }
        metadata_path = self.artifacts.save_metadata(request.version, metadata)

        return {
            **metadata,
            "total_records": len(prepared.prices),
            "train_samples": int(len(prepared.x_train)),
            "test_samples": int(len(prepared.x_test)),
            "actual_epochs": actual_epochs,
            "best_epoch": best_epoch,
            "model_path": str(model_path),
            "scaler_path": str(scaler_path),
            "metadata_path": str(metadata_path),
            "metrics": {
                **metrics,
                "final_training_loss": final_training_loss,
                "final_validation_loss": final_validation_loss,
            },
            "training_duration_seconds": round(time.perf_counter() - started, 3),
        }
