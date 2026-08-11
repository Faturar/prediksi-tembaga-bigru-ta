import time

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
        best_epoch = None

        model = build_bigru(request.window_size, request.units, request.dropout, request.learning_rate)
        history = model.fit(
            prepared.x_train,
            prepared.y_train,
            epochs=request.epochs,
            batch_size=request.batch_size,
            shuffle=False,
            verbose=0,
        )
        model.save(paths["model"])
        predicted_scaled = model.predict(prepared.x_test, verbose=0)
        final_training_loss = float(history.history["loss"][-1])
        model_path = paths["model"]
        model_type = "bigru"

        actual = prepared.prices[prepared.train_end_index:]
        predicted = prepared.scaler.inverse_transform(predicted_scaled)
        metrics = regression_metrics(actual, predicted)
        test_dates = prepared.dates[prepared.train_end_index:]

        scaler_path = self.artifacts.save_scaler(request.version, prepared.scaler)
        metadata = {
            "version": request.version,
            "model_type": model_type,
            "units": request.units,
            "dropout": request.dropout,
            "batch_size": request.batch_size,
            "configured_epochs": request.epochs,
            "actual_epochs": actual_epochs,
            "learning_rate": request.learning_rate,
            "optimizer": "Adam",
            "loss": "mse",
            "window_size": request.window_size,
            "dataset_start_date": prepared.dates[0],
            "dataset_end_date": prepared.dates[-1],
            "train_start_date": prepared.dates[0],
            "train_end_date": prepared.dates[prepared.train_end_index - 1],
            "test_start_date": prepared.dates[prepared.train_end_index],
            "test_end_date": prepared.dates[-1],
            "last_date": prepared.dates[-1],
            "total_records": len(prepared.prices),
            "train_samples": int(len(prepared.x_train)),
            "test_samples": int(len(prepared.x_test)),
            "metrics": {
                **metrics,
                "final_training_loss": final_training_loss,
                "final_validation_loss": final_validation_loss,
            },
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
