<?php

namespace Stokoe\FormsToWherever\Tests;

use Stokoe\FormsToWherever\ConfigurationParser;
use Stokoe\FormsToWherever\ConnectorManager;
use Stokoe\FormsToWherever\Contracts\ConnectorInterface;
use Statamic\Forms\Submission;

class ValidationTestConnector implements ConnectorInterface
{
    public function handle(): string
    {
        return 'test_connector';
    }

    public function name(): string
    {
        return 'Test Connector';
    }

    public function fieldset(): array
    {
        return [
            [
                'handle' => 'required_field',
                'field' => [
                    'validate' => 'required'
                ]
            ],
            [
                'handle' => 'optional_field',
                'field' => [
                    'validate' => 'nullable'
                ]
            ]
        ];
    }

    public function process(Submission $submission, array $config): void
    {
    }
}

class ConfigurationValidationTest extends TestCase
{
    public function test_it_validates_required_fields()
    {
        $manager = new ConnectorManager;
        $manager->register(new ValidationTestConnector);

        $parser = new ConfigurationParser($manager);

        // Missing required field
        $config = [
            'test_connector_enabled' => true,
            'test_connector_optional_field' => 'something',
        ];

        $result = $parser->parseFromBlueprint($config);

        $this->assertEmpty($result, 'Should fail validation when required field is missing');

        // With required field
        $config['test_connector_required_field'] = 'value';

        $result = $parser->parseFromBlueprint($config);

        $this->assertCount(1, $result, 'Should pass validation when required field is present');
        $this->assertEquals('value', $result[0]['required_field']);
    }
}
