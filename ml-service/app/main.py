from fastapi import FastAPI

from app.api.routes import router
from app.core.config import get_settings

app = FastAPI(title="Copper Price BiGRU ML Service", version="0.1.0")
app.include_router(router)


@app.get("/health")
def health():
    settings = get_settings()
    return {"status": "ok", "env": settings.app_env}
