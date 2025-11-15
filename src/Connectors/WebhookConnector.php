<?php

declare(strict_types=1);

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
                'handle' => 'auth_header',
                'field' => [
                    'type' => 'text',
                    'display' => 'Authorization Header',
                    'instructions' => 'Optional: Bearer token or API key for authentication',
                ],
            ],
            [
                'handle' => 'secret_key',
                'field' => [
                    'type' => 'text',
                    'display' => 'Secret Key',
                    'instructions' => 'Optional: Secret key for request signing (HMAC-SHA256)',
                ],
            ],
            [
                'handle' => 'allowed_ips',
                'field' => [
                    'type' => 'textarea',
                    'display' => 'Allowed IPs',
                    'instructions' => 'Optional: Comma-separated list of allowed IP addresses/ranges',
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
        $authHeader = $config['auth_header'] ?? null;
        $secretKey = $config['secret_key'] ?? null;
        $allowedIps = $config['allowed_ips'] ?? null;

        if (!$url) {
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

        // IP allowlist check
        if ($allowedIps && !$this->isIpAllowed($url, $allowedIps)) {
            Log::warning('Webhook URL not in allowed IPs', [
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
            $data['data'] = $submission->data();
        }

        $headers = ['Content-Type' => 'application/json'];
        
        // Add authorization header
        if ($authHeader) {
            $headers['Authorization'] = $authHeader;
        }
        
        // Add signature header
        if ($secretKey) {
            $payload = json_encode($data);
            $signature = hash_hmac('sha256', $payload, $secretKey);
            $headers['X-Signature-SHA256'] = 'sha256=' . $signature;
        }

        try {
            $response = Http::timeout(10)->withHeaders($headers)->$method($url, $data);
            
            if ($response->successful()) {
                Log::info('Webhook request successful', [
                    'status' => $response->status(),
                    'url' => $url,
                    'method' => $method,
                    'form' => $submission->form()->handle(),
                    'submission_id' => $submission->id(),
                    'response_time' => $response->transferStats?->getTransferTime(),
                ]);
            } else {
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
        }
    }

    protected function isIpAllowed(string $url, string $allowedIps): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $ip = gethostbyname($host);
        
        $allowedList = array_map('trim', explode(',', $allowedIps));
        
        foreach ($allowedList as $allowed) {
            if ($ip === $allowed || $host === $allowed) {
                return true;
            }
        }
        
        return false;
    }
}
