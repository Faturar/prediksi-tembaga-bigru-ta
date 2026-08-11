# System Architecture

The system is a local monorepo with two services:

- Native PHP web app in `web/` for authentication, CRUD, CSV import, dashboard, reports, and MySQL persistence.
- FastAPI ML service in `ml-service/` for preprocessing, training, evaluation, artifact storage, and prediction.

MySQL is owned by PHP. Python receives JSON payloads over REST and stores only ML artifacts under `ml-service/artifacts/`.
