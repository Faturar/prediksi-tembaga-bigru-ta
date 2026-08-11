# System Architecture

The system is a local monorepo with two services:

- Native PHP web app in `web/` for authentication, CRUD, CSV import, dashboard, reports, and MySQL persistence.
- FastAPI ML service in `ml-service/` for preprocessing, train-only MinMax normalization, sliding windows, one-layer TensorFlow/Keras BiGRU training, evaluation, artifact storage, and prediction.

MySQL is owned by PHP. Python receives JSON payloads over REST and stores only ML artifacts under `ml-service/artifacts/`.

Python does not connect directly to MySQL. TensorFlow/Keras is required for training; the system must not create a Linear Regression fallback artifact.
