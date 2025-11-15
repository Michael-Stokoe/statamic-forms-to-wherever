<?php

namespace Stokoe\FormsToWherever\Listeners;

use Stokoe\FormsToWherever\ConnectorManager;
use Statamic\Events\FormSubmitted;
use Illuminate\Support\Facades\Log;

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

        // Find any form_connectors fields in the form blueprint
        $connectorFields = $form->blueprint()->fields()->all()->filter(function ($field) {
            return $field->fieldtype()->handle() === 'form_connectors';
        });

        if ($connectorFields->isEmpty()) {
            return;
        }

        // Process each connector field
        foreach ($connectorFields as $fieldHandle => $field) {
            $fieldConfig = $form->blueprint()->field($fieldHandle)->config();
            $connectors = $this->configParser->parseFromBlueprint($fieldConfig);

            foreach ($connectors as $connectorConfig) {
                $connector = $this->connectorManager->get($connectorConfig['type']);

                if (! $connector) {
                    Log::warning('Unknown connector type', [
                        'type' => $connectorConfig['type'],
                        'form' => $form->handle(),
                        'submission_id' => $submission->id(),
                    ]);
                    continue;
                }

                try {
                    $connector->process($submission, $connectorConfig);
                } catch (\Exception $e) {
                    Log::error('Connector processing failed', [
                        'connector' => $connectorConfig['type'],
                        'error' => $e->getMessage(),
                        'form' => $form->handle(),
                        'submission_id' => $submission->id(),
                    ]);
                }
            }
        }
}
}
