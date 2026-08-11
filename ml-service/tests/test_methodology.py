import inspect

import pytest

from app.ml import model as model_module
from app.services import prediction as prediction_module
from app.services import training as training_module


def test_training_has_no_linear_regression_fallback():
    source = inspect.getsource(training_module)
    assert "LinearRegression" not in source
    assert "fallback" not in source.lower()


def test_training_fit_disables_shuffle_and_validation_split():
    source = inspect.getsource(training_module.TrainingService.train)
    assert "shuffle=False" in source
    assert "validation_split" not in source


def test_prediction_does_not_invent_next_calendar_day():
    source = inspect.getsource(prediction_module)
    assert "timedelta" not in source
    assert '"prediction_date": None' in source


def test_model_architecture_exactly_one_bidirectional_layer():
    pytest.importorskip("tensorflow")
    built = model_module.build_bigru(window_size=30, units=64, dropout=0.2, learning_rate=0.001)
    bidirectional_layers = [layer for layer in built.layers if layer.__class__.__name__ == "Bidirectional"]
    dense_layers = [layer for layer in built.layers if layer.__class__.__name__ == "Dense"]

    assert len(bidirectional_layers) == 1
    assert bidirectional_layers[0].layer.__class__.__name__ == "GRU"
    assert bidirectional_layers[0].layer.units == 64
    assert bidirectional_layers[0].layer.return_sequences is False
    assert dense_layers[-1].units == 1
    assert built.loss == "mse"
    assert built.optimizer.__class__.__name__ == "Adam"
