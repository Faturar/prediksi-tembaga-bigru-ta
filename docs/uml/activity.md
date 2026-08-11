# Activity Diagram

```mermaid
flowchart TD
    A[Mulai] --> B[Login]
    B --> C[Import atau input data harga]
    C --> D[Trigger training]
    D --> E[FastAPI preprocessing dan training]
    E --> F[Simpan metadata dan metric]
    F --> G[Jalankan prediksi]
    G --> H[Tampilkan evaluasi dan laporan]
```
