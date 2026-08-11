from datetime import timedelta

import joblib
import numpy as np

from app.services.artifacts import ArtifactStore


class PredictionService:
    def __init__(self):
        self.artifacts = ArtifactStore()

    def predict(self, request) -> dict:
        metadata = self.artifacts.load_metadata(request.model_version)
        window_size = int(metadata["window_size"])
        if len(request.window) < window_size:
            raise ValueError("Prediction window is smaller than model window size.")

        window = sorted(request.window, key=lambda item: item.date)[-window_size:]
        scaler = self.artifacts.load_scaler(request.model_version)
        values = np.array([item.close for item in window], dtype=float).reshape(-1, 1)
        scaled = scaler.transform(values).reshape(1, window_size, 1)
        paths = self.artifacts.paths(request.model_version)

        if paths["model"].exists():
            try:
                from tensorflow.keras.models import load_model
                model = load_model(paths["model"])
                predicted_scaled = model.predict(scaled, verbose=0)
            except Exception as exc:
                raise RuntimeError("Unable to load TensorFlow model.") from exc
        else:
            model = joblib.load(paths["fallback_model"])
            predicted_scaled = model.predict(scaled.reshape(1, -1)).reshape(-1, 1)

        predicted = scaler.inverse_transform(predicted_scaled)[0][0]
        return {
            "model_version": request.model_version,
            "predicted_close": round(float(predicted), 6),
            "prediction_date": window[-1].date + timedelta(days=1),
        }
