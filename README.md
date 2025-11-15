# Forms To Wherever

**The ultimate form connector system for Statamic** - Send your form submissions anywhere with zero frontend modifications required.

Transform your Statamic forms into powerful data collection tools that seamlessly integrate with any external service, API, or webhook endpoint. Whether you're connecting to CRMs, email marketing platforms, analytics tools, or custom APIs, Forms To Wherever makes it effortless.

## ✨ Why Choose Forms To Wherever?

### 🚀 **Zero Frontend Impact**
- **Works with ANY frontend** - React, Vue, Alpine.js, vanilla HTML, or headless setups
- **No template modifications required** - Connector fields are automatically hidden from form rendering
- **Universal compatibility** - Works with `{{ form | to_json }}`, AJAX submissions, and traditional forms

### 🔧 **Developer-Friendly Architecture**
- **Extensible connector system** - Build custom connectors for any service
- **Clean, testable code** - Modern PHP with proper error handling and logging
- **Marketplace ecosystem** - Install only the connectors you need

### 🛡️ **Production-Ready**
- **Graceful error handling** - Failed connectors won't break form submissions
- **Comprehensive logging** - Debug issues with detailed error reporting
- **Security-first design** - Built-in SSRF protection and validation

## 🎯 Perfect For

- **Marketing teams** connecting forms to CRMs and email platforms
- **Developers** building custom integrations without reinventing the wheel
- **Agencies** needing flexible form solutions across multiple projects
- **SaaS applications** requiring seamless data flow between systems

## 📦 What's Included

### Built-in Webhook Connector
Send form data to any HTTP endpoint with:
- **Flexible HTTP methods** (POST, PUT, PATCH)
- **Custom field mapping** - Transform form fields to match your API
- **Automatic metadata** - Form name, submission ID, and timestamp included
- **Timeout protection** - Never hang your forms waiting for slow APIs

## 🚀 Quick Start

### 1. Install the Addon
```bash
composer require stokoe/forms-to-wherever
```

### 2. Add to Your Form Blueprint
```yaml
fields:
  # Your existing form fields...
  -
    handle: connectors
    field:
      type: form_connectors
      display: Form Connectors
      instructions: Configure where form submissions should be sent
```

### 3. Configure in the Control Panel
1. Edit your form in the Statamic Control Panel
2. Navigate to the "Form Connectors" section
3. Enable the Webhook connector
4. Enter your endpoint URL and configure field mapping
5. Save and you're done!

### 4. Test Your Integration
Submit a test form and watch your data flow seamlessly to your configured endpoints. Check the logs for detailed processing information.

## 🔌 Extend with Custom Connectors

Building a custom connector is incredibly simple. Here's a complete Mailchimp example:

```php
<?php

namespace YourPackage\Connectors;

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
                    'validate' => 'required',
                ],
            ],
            [
                'handle' => 'list_id',
                'field' => [
                    'type' => 'text',
                    'display' => 'List ID',
                    'validate' => 'required',
                ],
            ],
        ];
    }

    public function process(Submission $submission, array $config): void
    {
        // Your integration logic here
        // Access form data: $submission->data()
        // Access config: $config['api_key'], $config['list_id']
    }
}
```

Register it in your service provider:
```php
use Stokoe\FormsToWherever\Facades\FormConnectors;

public function bootAddon()
{
    FormConnectors::register(new MailchimpConnector);
}
```

**That's it!** Your connector will automatically appear in the Control Panel with your custom configuration fields.

## 📚 Documentation

- **[Building Custom Connectors](CONNECTOR_INSTRUCTIONS.md)** - Complete guide with examples
- **[Technical Implementation](OVERRIDE.md)** - Deep dive into the architecture

## 🏗️ Architecture Highlights

### Intelligent Form Override System
Forms To Wherever uses a sophisticated form augmentation system that:
- Keeps connector fields visible in the Control Panel for configuration
- Automatically hides them from all frontend rendering
- Works transparently with Statamic's existing form system
- Requires zero changes to your existing templates

### Event-Driven Processing
- Listens to Statamic's `FormSubmitted` event
- Processes all enabled connectors for each form
- Isolates failures so one broken connector doesn't affect others
- Provides detailed logging for debugging and monitoring

### Flexible Configuration System
- Dynamic field generation based on connector requirements
- Conditional field visibility in the Control Panel
- Built-in validation for connector configurations
- Extensible for complex connector needs

## 🛠️ Use Cases

### Marketing & Lead Generation
- **CRM Integration**: Automatically sync leads to Salesforce, HubSpot, or Pipedrive
- **Email Marketing**: Add subscribers to Mailchimp, ConvertKit, or Campaign Monitor
- **Analytics**: Send conversion data to Google Analytics, Mixpanel, or custom dashboards

### E-commerce & Business
- **Order Processing**: Connect contact forms to inventory systems
- **Customer Support**: Route inquiries to Zendesk, Intercom, or help desk systems
- **Notifications**: Send alerts to Slack, Discord, or SMS services

### Development & Integration
- **Microservices**: Connect forms to your application's API endpoints
- **Data Warehousing**: Stream form data to analytics platforms
- **Automation**: Trigger Zapier workflows or custom business processes

## 🔒 Security & Reliability

- **SSRF Protection**: Built-in validation prevents malicious URL attacks
- **Timeout Handling**: Configurable timeouts prevent hanging requests
- **Error Isolation**: Failed connectors don't break form submissions
- **Comprehensive Logging**: Track all processing for debugging and compliance

## 🎉 Get Started Today

Transform your Statamic forms from simple contact forms into powerful data collection and integration tools. With Forms To Wherever, you're not just collecting data - you're connecting your entire business ecosystem.

**Install now and start building the integrations your business needs.**

---

## Requirements

- PHP 8.2+
- Laravel 10.0+ | 11.0+ | 12.0+
- Statamic 4.0+ | 5.0+

## License

MIT License - Build amazing things with it!

## Support

Found a bug or need a feature? [Open an issue](https://github.com/stokoe/forms-to-wherever/issues) or contribute to make Forms To Wherever even better.
