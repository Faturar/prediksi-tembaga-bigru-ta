from functools import lru_cache
from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    app_env: str = "local"
    host: str = "127.0.0.1"
    port: int = 8001
    api_key: str = "change-me-local"
    model_dir: Path = Path("artifacts/models")
    scaler_dir: Path = Path("artifacts/scalers")
    metadata_dir: Path = Path("artifacts/metadata")
    log_dir: Path = Path("logs")
    default_window_size: int = 30
    default_units: int = 64
    default_dropout: float = 0.2
    default_batch_size: int = 32
    default_epochs: int = 50
    default_learning_rate: float = 0.001

    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8")


@lru_cache
def get_settings() -> Settings:
    settings = Settings()
    for directory in [settings.model_dir, settings.scaler_dir, settings.metadata_dir, settings.log_dir]:
        directory.mkdir(parents=True, exist_ok=True)
    return settings
