<?php

namespace Stokoe\FormsToWherever\Tests;

use Stokoe\FormsToWherever\ConfigurationParser;
use Stokoe\FormsToWherever\ConnectorManager;
use Stokoe\FormsToWherever\Connectors\WebhookConnector;

class ConfigurationParserTest extends TestCase
{
    public function test_it_parses_enabled_connectors()
    {
        $manager = new ConnectorManager;
        $manager->register(new WebhookConnector);
        
        $parser = new ConfigurationParser($manager);
        
        $config = [
            'webhook_enabled' => true,
            'webhook_url' => 'https://example.com/webhook',
            'webhook_method' => 'POST',
        ];
        
        $result = $parser->parseFromBlueprint($config);
        
        $this->assertCount(1, $result);
        $this->assertEquals('webhook', $result[0]['type']);
        $this->assertEquals('https://example.com/webhook', $result[0]['url']);
    }

    public function test_it_skips_disabled_connectors()
    {
        $manager = new ConnectorManager;
        $manager->register(new WebhookConnector);
        
        $parser = new ConfigurationParser($manager);
        
        $config = ['webhook_enabled' => false];
        
        $result = $parser->parseFromBlueprint($config);
        
        $this->assertEmpty($result);
    }
}
