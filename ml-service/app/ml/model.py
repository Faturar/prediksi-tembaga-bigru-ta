def build_bigru(window_size: int, units: int, dropout: float, learning_rate: float):
    try:
        from tensorflow.keras import Sequential
        from tensorflow.keras.layers import Bidirectional, Dense, Dropout, GRU
        from tensorflow.keras.optimizers import Adam
    except Exception as exc:  # pragma: no cover
        raise RuntimeError("TensorFlow is not installed. Install tensorflow to train a BiGRU model.") from exc

    model = Sequential(
        [
            Bidirectional(GRU(units, return_sequences=True), input_shape=(window_size, 1)),
            Dropout(dropout),
            Bidirectional(GRU(max(1, units // 2))),
            Dropout(dropout),
            Dense(1),
        ]
    )
    model.compile(optimizer=Adam(learning_rate=learning_rate), loss="mse")
    return model
