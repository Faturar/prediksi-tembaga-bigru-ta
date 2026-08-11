from dataclasses import dataclass
from datetime import date

import numpy as np
from sklearn.preprocessing import MinMaxScaler


@dataclass
class PreparedData:
    dates: list[date]
    prices: np.ndarray
    train_prices: np.ndarray
    test_prices: np.ndarray
    train_scaled: np.ndarray
    all_scaled: np.ndarray
    scaler: MinMaxScaler
    x_train: np.ndarray
    y_train: np.ndarray
    x_test: np.ndarray
    y_test: np.ndarray
    train_end_index: int


def sort_points(points: list) -> list:
    ordered = sorted(points, key=lambda item: item.date)
    if len({item.date for item in ordered}) != len(ordered):
        raise ValueError("Dataset contains duplicate dates.")
    return ordered


def prepare_dataset(points: list, window_size: int, train_ratio: float = 0.8) -> PreparedData:
    ordered = sort_points(points)
    if len(ordered) < window_size + 10:
        raise ValueError("Dataset is too small for the selected window size.")

    dates = [item.date for item in ordered]
    prices = np.array([item.close for item in ordered], dtype=float).reshape(-1, 1)
    train_end_index = int(len(prices) * train_ratio)
    if train_end_index <= window_size or len(prices) - train_end_index < 1:
        raise ValueError("Train/test split leaves insufficient data.")

    train_prices = prices[:train_end_index]
    test_prices = prices[train_end_index:]

    scaler = MinMaxScaler()
    train_scaled = scaler.fit_transform(train_prices)
    all_scaled = scaler.transform(prices)

    x_train, y_train = make_sequences(all_scaled, window_size, window_size, train_end_index)
    x_test, y_test = make_sequences(all_scaled, window_size, train_end_index, len(prices))

    return PreparedData(
        dates=dates,
        prices=prices,
        train_prices=train_prices,
        test_prices=test_prices,
        train_scaled=train_scaled,
        all_scaled=all_scaled,
        scaler=scaler,
        x_train=x_train,
        y_train=y_train,
        x_test=x_test,
        y_test=y_test,
        train_end_index=train_end_index,
    )


def make_sequences(values: np.ndarray, window_size: int, start_target: int, end_target: int) -> tuple[np.ndarray, np.ndarray]:
    x, y = [], []
    for target_index in range(start_target, end_target):
        x.append(values[target_index - window_size:target_index])
        y.append(values[target_index])
    return np.array(x, dtype=float), np.array(y, dtype=float)
