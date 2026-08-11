def build_bigru(window_size: int, units: int, dropout: float, learning_rate: float):
    try:
        from tensorflow.keras import Sequential
        from tensorflow.keras.layers import Bidirectional, Dense, Dropout, GRU, Input
        from tensorflow.keras.optimizers import Adam
    except Exception as exc:  # pragma: no cover
        raise RuntimeError("TensorFlow/Keras is not available. BiGRU training cannot run.") from exc

    model = Sequential(
        [
            Input(shape=(window_size, 1)),
            Bidirectional(GRU(units=units, return_sequences=False)),
            Dropout(dropout),
            Dense(1),
        ]
    )
    model.compile(optimizer=Adam(learning_rate=learning_rate), loss="mse")
    return model
