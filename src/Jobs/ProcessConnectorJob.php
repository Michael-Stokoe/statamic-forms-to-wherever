<?php

declare(strict_types=1);

namespace Stokoe\FormsToWherever\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToWherever\ConnectorManager;
use Statamic\Contracts\Forms\Submission;

class ProcessConnectorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Submission $submission,
        public string $connectorType,
        public array $connectorConfig
    ) {}

    public function handle(ConnectorManager $connectorManager): void
    {
        $connector = $connectorManager->get($this->connectorType);
        
        if (!$connector) {
            Log::warning('Connector not found', [
                'connector' => $this->connectorType,
                'submission_id' => $this->submission->id(),
            ]);
            return;
        }

        try {
            $connector->process($this->submission, $this->connectorConfig);
            
            Log::info('Connector processed successfully', [
                'connector' => $this->connectorType,
                'submission_id' => $this->submission->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Connector processing failed in job', [
                'connector' => $this->connectorType,
                'submission_id' => $this->submission->id(),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Connector job failed permanently', [
            'connector' => $this->connectorType,
            'submission_id' => $this->submission->id(),
            'error' => $exception->getMessage(),
        ]);
    }
}
