<?php

declare(strict_types=1);

use App\Core\Logger;
use App\Repositories\CopperPriceRepository;
use App\Repositories\ModelRunRepository;
use App\Services\MlApiClient;

require __DIR__ . '/../app/Core/bootstrap.php';
require __DIR__ . '/../app/Helpers/security.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$runId = (int) ($argv[1] ?? 0);
if ($runId <= 0) {
    exit(1);
}

$repo = new ModelRunRepository();

try {
    @set_time_limit(0);
    ignore_user_abort(true);

    $run = $repo->find($runId);
    if (!$run || $run['status'] !== 'running') {
        throw new RuntimeException("Training run {$runId} tidak ditemukan atau tidak berstatus running.");
    }

    writeTrainingLog($run['version'], "[PHP][START] run_id={$runId} version={$run['version']} worker background dimulai.");
    $data = (new CopperPriceRepository())->orderedClosePrices();
    writeTrainingLog($run['version'], '[PHP][DATA] total_records=' . count($data) . " window={$run['window_size']} units={$run['units']} batch={$run['batch_size']} epochs={$run['configured_epochs']} lr={$run['learning_rate']}");
    writeTrainingLog($run['version'], '[PHP][REQUEST] Mengirim request training ke ML service. Detail epoch akan muncul setelah ML service memakai kode logging terbaru.');
    $result = (new MlApiClient())->train([
        'version' => $run['version'],
        'window_size' => (int) $run['window_size'],
        'units' => (int) $run['units'],
        'dropout' => (float) $run['dropout'],
        'batch_size' => (int) $run['batch_size'],
        'epochs' => (int) $run['configured_epochs'],
        'learning_rate' => (float) $run['learning_rate'],
        'data' => $data,
    ]);

    $repo->markSuccess($runId, $result);
    writeTrainingLog($run['version'], '[PHP][DONE] Training selesai dan status model berhasil disimpan ke database.');
} catch (Throwable $error) {
    if (isset($run['version'])) {
        writeTrainingLog($run['version'], '[PHP][FAILED] ' . $error->getMessage());
    }
    if ($runId > 0) {
        $repo->markFailed($runId, $error->getMessage());
    }
    Logger::error($error);
    exit(1);
}

function writeTrainingLog(string $version, string $message): void
{
    $safeVersion = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $version) ?: 'training';
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ml-service' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . "training-{$safeVersion}.log";
    $line = sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $message, PHP_EOL);
    file_put_contents($path, $line, FILE_APPEND);
}
