import logging
import re
import time
from datetime import datetime

from app.core.config import get_settings
from app.ml.metrics import regression_metrics
from app.ml.model import build_bigru
from app.ml.preprocessing import prepare_dataset
from app.services.artifacts import ArtifactStore

logger = logging.getLogger("ml_service.training")


def training_log_path(version: str):
    safe_version = re.sub(r"[^A-Za-z0-9_.-]+", "_", version)
    return get_settings().log_dir / f"training-{safe_version}.log"


def reset_training_log(version: str) -> None:
    training_log_path(version).write_text("", encoding="utf-8")


def write_training_log(version: str, message: str) -> None:
    line = f"{datetime.now().strftime('%Y-%m-%d %H:%M:%S')} | {message}\n"
    with training_log_path(version).open("a", encoding="utf-8") as handle:
        handle.write(line)


def training_progress_callback(version: str, total_epochs: int):
    from tensorflow.keras.callbacks import Callback

    class TrainingProgressLogger(Callback):
        def on_epoch_end(self, epoch, logs=None):
            logs = logs or {}
            loss = logs.get("loss")
            val_loss = logs.get("val_loss")
            message = "[TRAIN][EPOCH] version=%s epoch=%s/%s loss=%s"
            values = [
                version,
                epoch + 1,
                total_epochs,
                f"{float(loss):.8f}" if loss is not None else "-",
            ]
            if val_loss is not None:
                message += " val_loss=%s"
                values.append(f"{float(val_loss):.8f}")

            logger.info(message, *values)
            write_training_log(version, message % tuple(values))

    return TrainingProgressLogger()


class TrainingService:
    def __init__(self):
        self.artifacts = ArtifactStore()

    def train(self, request) -> dict:
        started = time.perf_counter()
        reset_training_log(request.version)
        logger.info(
            "[TRAIN][START] version=%s records=%s window=%s units=%s dropout=%s batch=%s epochs=%s lr=%s",
            request.version,
            len(request.data),
            request.window_size,
            request.units,
            request.dropout,
            request.batch_size,
            request.epochs,
            request.learning_rate,
        )
        write_training_log(
            request.version,
            "[TRAIN][START] version=%s records=%s window=%s units=%s dropout=%s batch=%s epochs=%s lr=%s"
            % (
                request.version,
                len(request.data),
                request.window_size,
                request.units,
                request.dropout,
                request.batch_size,
                request.epochs,
                request.learning_rate,
            ),
        )

        try:
            logger.info("[TRAIN][PREPARE] version=%s sorting dataset and building train/test sequences", request.version)
            write_training_log(request.version, f"[TRAIN][PREPARE] version={request.version} sorting dataset and building train/test sequences")
            prepared = prepare_dataset(request.data, request.window_size)
            logger.info(
                "[TRAIN][DATA] version=%s dataset=%s..%s total=%s train_samples=%s test_samples=%s train_period=%s..%s test_period=%s..%s",
                request.version,
                prepared.dates[0],
                prepared.dates[-1],
                len(prepared.prices),
                len(prepared.x_train),
                len(prepared.x_test),
                prepared.dates[0],
                prepared.dates[prepared.train_end_index - 1],
                prepared.dates[prepared.train_end_index],
                prepared.dates[-1],
            )
            write_training_log(
                request.version,
                "[TRAIN][DATA] version=%s dataset=%s..%s total=%s train_samples=%s test_samples=%s train_period=%s..%s test_period=%s..%s"
                % (
                    request.version,
                    prepared.dates[0],
                    prepared.dates[-1],
                    len(prepared.prices),
                    len(prepared.x_train),
                    len(prepared.x_test),
                    prepared.dates[0],
                    prepared.dates[prepared.train_end_index - 1],
                    prepared.dates[prepared.train_end_index],
                    prepared.dates[-1],
                ),
            )
            paths = self.artifacts.paths(request.version)

            final_training_loss = None
            final_validation_loss = None
            actual_epochs = request.epochs
            best_epoch = None

            logger.info("[TRAIN][MODEL] version=%s building BiGRU architecture", request.version)
            write_training_log(request.version, f"[TRAIN][MODEL] version={request.version} building BiGRU architecture")
            model = build_bigru(request.window_size, request.units, request.dropout, request.learning_rate)
            logger.info("[TRAIN][FIT] version=%s starting model.fit", request.version)
            write_training_log(request.version, f"[TRAIN][FIT] version={request.version} starting model.fit")
            history = model.fit(
                prepared.x_train,
                prepared.y_train,
                epochs=request.epochs,
                batch_size=request.batch_size,
                shuffle=False,
                verbose=0,
                callbacks=[training_progress_callback(request.version, request.epochs)],
            )
            logger.info("[TRAIN][SAVE_MODEL] version=%s path=%s", request.version, paths["model"])
            write_training_log(request.version, f"[TRAIN][SAVE_MODEL] version={request.version} path={paths['model']}")
            model.save(paths["model"])
            logger.info("[TRAIN][PREDICT_TEST] version=%s generating predictions for test set", request.version)
            write_training_log(request.version, f"[TRAIN][PREDICT_TEST] version={request.version} generating predictions for test set")
            predicted_scaled = model.predict(prepared.x_test, verbose=0)
            final_training_loss = float(history.history["loss"][-1])
            model_path = paths["model"]
            model_type = "bigru"

            logger.info("[TRAIN][EVALUATE] version=%s calculating MAE/RMSE/MAPE", request.version)
            write_training_log(request.version, f"[TRAIN][EVALUATE] version={request.version} calculating MAE/RMSE/MAPE")
            actual = prepared.prices[prepared.train_end_index:]
            predicted = prepared.scaler.inverse_transform(predicted_scaled)
            metrics = regression_metrics(actual, predicted)
            test_dates = prepared.dates[prepared.train_end_index:]
            logger.info(
                "[TRAIN][METRICS] version=%s mae=%.6f rmse=%.6f mape=%.4f final_loss=%.8f",
                request.version,
                metrics["mae"],
                metrics["rmse"],
                metrics["mape"],
                final_training_loss,
            )
            write_training_log(
                request.version,
                "[TRAIN][METRICS] version=%s mae=%.6f rmse=%.6f mape=%.4f final_loss=%.8f"
                % (request.version, metrics["mae"], metrics["rmse"], metrics["mape"], final_training_loss),
            )

            logger.info("[TRAIN][SAVE_SCALER] version=%s", request.version)
            write_training_log(request.version, f"[TRAIN][SAVE_SCALER] version={request.version}")
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
            logger.info("[TRAIN][SAVE_METADATA] version=%s", request.version)
            write_training_log(request.version, f"[TRAIN][SAVE_METADATA] version={request.version}")
            metadata_path = self.artifacts.save_metadata(request.version, metadata)

            duration = round(time.perf_counter() - started, 3)
            logger.info(
                "[TRAIN][DONE] version=%s duration=%ss model=%s scaler=%s metadata=%s",
                request.version,
                duration,
                model_path,
                scaler_path,
                metadata_path,
            )
            write_training_log(
                request.version,
                f"[TRAIN][DONE] version={request.version} duration={duration}s model={model_path} scaler={scaler_path} metadata={metadata_path}",
            )
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
                "training_duration_seconds": duration,
            }
        except Exception:
            duration = round(time.perf_counter() - started, 3)
            logger.exception(
                "[TRAIN][FAILED] version=%s duration=%ss",
                request.version,
                duration,
            )
            write_training_log(request.version, f"[TRAIN][FAILED] version={request.version} duration={duration}s")
            raise
