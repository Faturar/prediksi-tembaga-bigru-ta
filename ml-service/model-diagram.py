from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Input, Bidirectional, GRU, Dropout, Dense
from tensorflow.keras.utils import plot_model

window_size = 30

model = Sequential([
    Input(shape=(window_size, 1)),
    Bidirectional(GRU(64, return_sequences=False)),
    Dropout(0.2),
    Dense(1)
])

plot_model(
    model,
    to_file="bigru_model_architecture.png",
    show_shapes=True,
    show_dtype=False,
    show_layer_names=True,
    expand_nested=True,
    dpi=200
)

print("Diagram model disimpan ke bigru_model_architecture.png")