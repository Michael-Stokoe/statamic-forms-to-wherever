<?php

namespace Stokoe\FormsToWherever\Connectors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToWherever\Contracts\ConnectorInterface;
use Statamic\Forms\Submission;

class WebhookConnector implements ConnectorInterface
{
    public function handle(): string
    {
        return 'webhook';
    }

    public function name(): string
    {
        return 'Webhook';
    }

    public function fieldset(): array
    {
        return [
            [
                'handle' => 'url',
                'field' => [
                    'type' => 'text',
                    'display' => 'Webhook URL',
                    'instructions' => 'The URL to send the form data to',
                    'validate' => 'required_if:webhook_enabled,true|sometimes'
                ],
            ],
            [
                'handle' => 'method',
                'field' => [
                    'type' => 'select',
                    'display' => 'HTTP Method',
                    'default' => 'POST',
                    'options' => [
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                    ],
                ],
            ],
            [
                'handle' => 'field_mapping',
                'field' => [
                    'type' => 'grid',
                    'display' => 'Field Mapping',
                    'instructions' => 'Map form fields to webhook payload keys',
                    'fields' => [
                        [
                            'handle' => 'form_field',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Form Field',
                                'instructions' => 'The handle of the form field',
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'webhook_key',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Webhook Key',
                                'instructions' => 'The key to use in the webhook payload',
                                'width' => 50,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function process(Submission $submission, array $config): void
    {
        $url = $config['url'] ?? null;
        $method = strtolower($config['method'] ?? 'post');
        $fieldMapping = $config['field_mapping'] ?? [];

        if (! $url) {
            return;
        }

        // Basic URL validation to prevent SSRF
        if (!filter_var($url, FILTER_VALIDATE_URL) || 
            !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'])) {
            Log::warning('Invalid webhook URL', [
                'url' => $url,
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
            return;
        }

        $data = [
            'form' => $submission->form()->handle(),
            'id' => $submission->id(),
            'date' => $submission->date()->toISOString(),
        ];

        // Apply field mapping
        if (!empty($fieldMapping)) {
            foreach ($fieldMapping as $mapping) {
                $formField = $mapping['form_field'] ?? null;
                $webhookKey = $mapping['webhook_key'] ?? null;

                if ($formField && $webhookKey && $submission->has($formField)) {
                    $data[$webhookKey] = $submission->get($formField);
                }
            }
        } else {
            // If no mapping, include all form data
            $data['data'] = $submission->data();
        }

        try {
            $response = Http::timeout(10)->$method($url, $data);
            
            if (!$response->successful()) {
                Log::warning('Webhook request failed', [
                    'status' => $response->status(),
                    'url' => $url,
                    'method' => $method,
                    'form' => $submission->form()->handle(),
                    'submission_id' => $submission->id(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook request exception', [
                'error' => $e->getMessage(),
                'url' => $url,
                'method' => $method,
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
            // Don't throw - let form submission continue
        }
    }
}
