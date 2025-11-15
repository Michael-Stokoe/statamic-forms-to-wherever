# Building Custom Connectors

This guide shows you how to create custom connectors for the Forms To Wherever addon.

## Basic Connector Structure

All connectors must implement the `ConnectorInterface`:

```php
<?php

namespace YourVendor\YourPackage\Connectors;

use Stokoe\FormsToWherever\Contracts\ConnectorInterface;
use Statamic\Forms\Submission;

class YourConnector implements ConnectorInterface
{
    public function handle(): string
    {
        return 'your_connector';
    }

    public function name(): string
    {
        return 'Your Service Name';
    }

    public function fieldset(): array
    {
        return [
            // Configuration fields go here
        ];
    }

    public function process(Submission $submission, array $config): void
    {
        // Process the form submission
    }
}
```

## Required Methods

### `handle(): string`
Returns a unique identifier for your connector. Use lowercase with underscores.

```php
public function handle(): string
{
    return 'mailchimp';
}
```

### `name(): string`
Returns the human-readable name displayed in the Control Panel.

```php
public function name(): string
{
    return 'Mailchimp';
}
```

### `fieldset(): array`
Defines the configuration fields shown in the Control Panel. Uses standard Statamic field definitions.

```php
public function fieldset(): array
{
    return [
        [
            'handle' => 'api_key',
            'field' => [
                'type' => 'text',
                'display' => 'API Key',
                'instructions' => 'Your Mailchimp API key',
                'validate' => 'required',
            ],
        ],
        [
            'handle' => 'list_id',
            'field' => [
                'type' => 'text',
                'display' => 'List ID',
                'instructions' => 'The Mailchimp list ID to add subscribers to',
                'validate' => 'required',
            ],
        ],
    ];
}
```

### `process(Submission $submission, array $config): void`
Handles the actual form submission processing.

```php
public function process(Submission $submission, array $config): void
{
    $apiKey = $config['api_key'];
    $listId = $config['list_id'];
    $formData = $submission->data();
    
    // Your processing logic here
}
```

## Complete Example: Mailchimp Connector

```php
<?php

namespace YourVendor\MailchimpConnector\Connectors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToWherever\Contracts\ConnectorInterface;
use Statamic\Forms\Submission;

class MailchimpConnector implements ConnectorInterface
{
    public function handle(): string
    {
        return 'mailchimp';
    }

    public function name(): string
    {
        return 'Mailchimp';
    }

    public function fieldset(): array
    {
        return [
            [
                'handle' => 'api_key',
                'field' => [
                    'type' => 'text',
                    'display' => 'API Key',
                    'instructions' => 'Your Mailchimp API key',
                    'validate' => 'required',
                ],
            ],
            [
                'handle' => 'list_id',
                'field' => [
                    'type' => 'text',
                    'display' => 'List ID',
                    'instructions' => 'The Mailchimp list ID',
                    'validate' => 'required',
                ],
            ],
            [
                'handle' => 'email_field',
                'field' => [
                    'type' => 'text',
                    'display' => 'Email Field',
                    'instructions' => 'Form field containing the email address',
                    'default' => 'email',
                    'validate' => 'required',
                ],
            ],
            [
                'handle' => 'field_mapping',
                'field' => [
                    'type' => 'grid',
                    'display' => 'Field Mapping',
                    'instructions' => 'Map form fields to Mailchimp merge fields',
                    'fields' => [
                        [
                            'handle' => 'form_field',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Form Field',
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'mailchimp_field',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Mailchimp Merge Tag',
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
        $apiKey = $config['api_key'];
        $listId = $config['list_id'];
        $emailField = $config['email_field'];
        $fieldMapping = $config['field_mapping'] ?? [];
        
        $formData = $submission->data();
        $email = $formData[$emailField] ?? null;
        
        if (!$email) {
            Log::warning('Mailchimp connector: No email found', [
                'form' => $submission->form()->handle(),
                'email_field' => $emailField,
            ]);
            return;
        }
        
        // Build merge fields from mapping
        $mergeFields = [];
        foreach ($fieldMapping as $mapping) {
            $formField = $mapping['form_field'] ?? '';
            $mailchimpField = $mapping['mailchimp_field'] ?? '';
            
            if ($formField && $mailchimpField && isset($formData[$formField])) {
                $mergeFields[$mailchimpField] = $formData[$formField];
            }
        }
        
        // Extract datacenter from API key
        $datacenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$listId}/members";
        
        $payload = [
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => $mergeFields,
        ];
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);
            
            if ($response->successful()) {
                Log::info('Mailchimp subscriber added successfully', [
                    'email' => $email,
                    'list_id' => $listId,
                ]);
            } else {
                Log::error('Mailchimp API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'email' => $email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Mailchimp connector exception', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);
        }
    }
}
```

## Registering Your Connector

In your addon's service provider:

```php
<?php

namespace YourVendor\MailchimpConnector;

use Stokoe\FormsToWherever\Facades\FormConnectors;
use YourVendor\MailchimpConnector\Connectors\MailchimpConnector;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        FormConnectors::register(new MailchimpConnector);
    }
}
```

## Advanced Field Types

### Toggle Fields
```php
[
    'handle' => 'double_optin',
    'field' => [
        'type' => 'toggle',
        'display' => 'Double Opt-in',
        'instructions' => 'Require email confirmation',
        'default' => true,
    ],
],
```

### Select Fields
```php
[
    'handle' => 'status',
    'field' => [
        'type' => 'select',
        'display' => 'Subscriber Status',
        'options' => [
            'subscribed' => 'Subscribed',
            'pending' => 'Pending',
            'unsubscribed' => 'Unsubscribed',
        ],
        'default' => 'subscribed',
    ],
],
```

### Grid Fields for Complex Mapping
```php
[
    'handle' => 'field_mapping',
    'field' => [
        'type' => 'grid',
        'display' => 'Field Mapping',
        'fields' => [
            [
                'handle' => 'form_field',
                'field' => [
                    'type' => 'text',
                    'display' => 'Form Field',
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'service_field',
                'field' => [
                    'type' => 'text',
                    'display' => 'Service Field',
                    'width' => 50,
                ],
            ],
        ],
    ],
],
```

## Best Practices

### Error Handling
Always wrap API calls in try-catch blocks and log errors:

```php
try {
    $response = Http::post($url, $data);
    
    if (!$response->successful()) {
        Log::error('API request failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        return;
    }
    
    Log::info('Successfully processed submission');
} catch (\Exception $e) {
    Log::error('Connector exception', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

### Configuration Validation
Validate required configuration in your process method:

```php
public function process(Submission $submission, array $config): void
{
    if (empty($config['api_key'])) {
        Log::warning('Missing API key for connector');
        return;
    }
    
    // Continue processing...
}
```

### Data Transformation
Transform form data as needed for your service:

```php
$formData = $submission->data();

// Transform data
$payload = [
    'email' => $formData['email'],
    'first_name' => $formData['first_name'],
    'last_name' => $formData['last_name'],
    'custom_fields' => [
        'company' => $formData['company'] ?? '',
        'phone' => $formData['phone'] ?? '',
    ],
];
```

## Testing Your Connector

Create a simple test form with your connector enabled and verify:

1. Configuration fields appear in the Control Panel
2. Form submissions trigger your connector
3. Data is sent correctly to your service
4. Errors are logged appropriately

## Publishing Your Connector

Consider creating separate addon packages for each connector to allow users to install only what they need. This also enables you to monetize premium connectors while keeping the base system free.

Your connector addon should:
- Depend on `stokoe/forms-to-wherever`
- Register itself in the service provider
- Include comprehensive documentation
- Provide clear configuration instructions
