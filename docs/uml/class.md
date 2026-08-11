# Class Diagram

```mermaid
classDiagram
    class Router
    class Controller
    class CopperPriceRepository
    class ModelRunRepository
    class PredictionRepository
    class CsvImportService
    class MlApiClient
    Controller <|-- DashboardController
    Controller <|-- ModelController
    ModelController --> MlApiClient
    ModelController --> CopperPriceRepository
    PredictionController --> PredictionRepository
```
