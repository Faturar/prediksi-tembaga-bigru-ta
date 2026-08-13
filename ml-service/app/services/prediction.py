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
        scaled_window = scaler.transform(values)
        paths = self.artifacts.paths(request.model_version)

        if not paths["model"].exists():
            raise FileNotFoundError(f"Keras BiGRU model for {request.model_version} was not found.")

        try:
            model = self._load_model(paths["model"])
        except Exception as exc:
            raise RuntimeError("Unable to load TensorFlow/Keras BiGRU model.") from exc

        predictions = []
        for step in range(1, request.horizon + 1):
            x = scaled_window.reshape(1, window_size, 1)
            predicted_scaled = np.asarray(model.predict(x, verbose=0), dtype=float).reshape(1, 1)
            predicted = scaler.inverse_transform(predicted_scaled)[0][0]
            predictions.append({"step": step, "predicted_close": round(float(predicted), 6)})
            scaled_window = np.concatenate([scaled_window[1:], predicted_scaled], axis=0)

        return {
            "model_version": request.model_version,
            "predicted_close": predictions[0]["predicted_close"],
            "prediction_date": None,
            "horizon": request.horizon,
            "strategy": "recursive",
            "predictions": predictions,
        }

    def _load_model(self, path):
        from tensorflow.keras.models import load_model

        return load_model(path)
