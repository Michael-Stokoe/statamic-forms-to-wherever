<?php

namespace Stokoe\FormsToWherever\Tests;

use Stokoe\FormsToWherever\ConnectorManager;
use Stokoe\FormsToWherever\Connectors\WebhookConnector;

class ConnectorManagerTest extends TestCase
{
    public function test_it_can_register_and_retrieve_connectors()
    {
        $manager = new ConnectorManager;
        $connector = new WebhookConnector;
        
        $manager->register($connector);
        
        $this->assertSame($connector, $manager->get('webhook'));
        $this->assertArrayHasKey('webhook', $manager->all());
    }

    public function test_it_returns_null_for_unknown_connectors()
    {
        $manager = new ConnectorManager;
        
        $this->assertNull($manager->get('unknown'));
    }

    public function test_it_can_get_fieldsets()
    {
        $manager = new ConnectorManager;
        $manager->register(new WebhookConnector);
        
        $fieldsets = $manager->getFieldsets();
        
        $this->assertArrayHasKey('webhook', $fieldsets);
        $this->assertEquals('Webhook', $fieldsets['webhook']['display']);
        $this->assertIsArray($fieldsets['webhook']['fields']);
    }
}
