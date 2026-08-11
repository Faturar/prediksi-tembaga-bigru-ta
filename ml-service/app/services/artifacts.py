import json
from pathlib import Path

import joblib

from app.core.config import get_settings


class ArtifactStore:
    def __init__(self):
        self.settings = get_settings()

    def paths(self, version: str) -> dict[str, Path]:
        return {
            "model": self.settings.model_dir / f"{version}.keras",
            "fallback_model": self.settings.model_dir / f"{version}.joblib",
            "scaler": self.settings.scaler_dir / f"{version}.joblib",
            "metadata": self.settings.metadata_dir / f"{version}.json",
        }

    def save_scaler(self, version: str, scaler) -> Path:
        path = self.paths(version)["scaler"]
        joblib.dump(scaler, path)
        return path

    def save_metadata(self, version: str, metadata: dict) -> Path:
        path = self.paths(version)["metadata"]
        path.write_text(json.dumps(metadata, indent=2, default=str), encoding="utf-8")
        return path

    def load_metadata(self, version: str) -> dict:
        path = self.paths(version)["metadata"]
        if not path.exists():
            raise FileNotFoundError(f"Metadata for model {version} was not found.")
        return json.loads(path.read_text(encoding="utf-8"))

    def load_scaler(self, version: str):
        return joblib.load(self.paths(version)["scaler"])
