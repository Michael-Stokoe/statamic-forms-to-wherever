<?php

declare(strict_types=1);

namespace Stokoe\FormsToWherever\Listeners;

use Illuminate\Support\Facades\Log;
use Stokoe\FormsToWherever\ConfigurationParser;
use Stokoe\FormsToWherever\ConnectorManager;
use Stokoe\FormsToWherever\Jobs\ProcessConnectorJob;
use Statamic\Events\FormSubmitted;

class ProcessConnectors
{
    public function __construct(
        protected ConnectorManager $connectorManager,
        protected ConfigurationParser $configParser
    ) {}

    public function handle(FormSubmitted $event): void
    {
        $submission = $event->submission;
        $form = $submission->form();

        $connectorFields = $form->blueprint()->fields()->all()->filter(function ($field) {
            return $field->fieldtype()->handle() === 'form_connectors';
        });

        if ($connectorFields->isEmpty()) {
            return;
        }

        foreach ($connectorFields as $fieldHandle => $field) {
            $fieldConfig = $form->blueprint()->field($fieldHandle)->config();
            $connectors = $this->configParser->parseFromBlueprint($fieldConfig);
            $useAsync = $fieldConfig['async_processing'] ?? true;

            foreach ($connectors as $connectorConfig) {
                if ($useAsync) {
                    ProcessConnectorJob::dispatch(
                        $submission->id(),
                        $connectorConfig['type'],
                        $connectorConfig
                    );
                } else {
                    $this->processSynchronously($submission, $connectorConfig);
                }
            }
        }
    }

    protected function processSynchronously($submission, array $connectorConfig): void
    {
        $connector = $this->connectorManager->get($connectorConfig['type']);

        if (!$connector) {
            Log::warning('Unknown connector type', [
                'type' => $connectorConfig['type'],
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
            return;
        }

        try {
            $connector->process($submission, $connectorConfig);
        } catch (\Exception $e) {
            Log::error('Connector processing failed', [
                'connector' => $connectorConfig['type'],
                'error' => $e->getMessage(),
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
        }
    }
}
