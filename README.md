# Forms To Wherever

A base forms connector system for Statamic that allows you to send form submissions to various external services through connector packages.

## Installation

1. Install the addon via Composer:
```bash
composer require stokoe/forms-to-wherever
```

2. Add the `form_connectors` field to your form blueprints:

```yaml
fields:
  -
    handle: form_connectors_field
    field:
      type: form_connectors
      display: Form Connectors
      instructions: Configure where form submissions should be sent
```

## Built-in Connectors

### Webhook Connector
Sends form data to any webhook URL via HTTP POST/PUT/PATCH with customizable field mapping.

**Features:**
- Configurable HTTP method (POST, PUT, PATCH)
- Custom field mapping (map form fields to webhook payload keys)
- Automatic form metadata inclusion (form name, submission ID, timestamp)



## Creating Custom Connectors

Create a connector by implementing the `ConnectorInterface`:

```php
<?php

namespace YourPackage\Connectors;

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
        return 'Your Service';
    }

    public function fieldset(): array
    {
        return [
            [
                'handle' => 'api_key',
                'field' => [
                    'type' => 'text',
                    'display' => 'API Key',
                    'validate' => 'required',
                ],
            ],
        ];
    }

    public function process(Submission $submission, array $config): void
    {
        // Process the submission
        // Access form data: $submission->data()
        // Access config: $config['api_key'], etc.
    }
}
```

Register your connector in your service provider:

```php
use Stokoe\FormsToWherever\Facades\FormConnectors;

public function boot()
{
    FormConnectors::register(new YourConnector);
}
```

## Usage

1. Add the `form_connectors` field to your form blueprint
2. Configure connectors in the Control Panel form settings
3. Enable desired connectors and configure their settings
4. Form submissions will automatically be sent to all enabled connectors

## Architecture

The system uses an event-driven architecture:
- Listens for `FormSubmitted` events
- Processes configured connectors for each form
- Handles errors gracefully with logging
- Supports multiple connectors per form

## Extending

This addon provides the base infrastructure. Additional connector packages can be developed separately and registered with the system, allowing for a marketplace of specialized connectors for different services.
