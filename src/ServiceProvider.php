<?php

namespace Stokoe\FormsToWherever;

use Stokoe\FormsToWherever\Connectors\WebhookConnector;
use Stokoe\FormsToWherever\Fieldtypes\FormConnectors;
use Stokoe\FormsToWherever\Listeners\ProcessConnectors;
use Statamic\Events\FormSubmitted;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        FormConnectors::class,
    ];

    protected $listen = [
        FormSubmitted::class => [ProcessConnectors::class],
    ];

    public function register()
    {
        $this->app->singleton(ConnectorManager::class);
        $this->app->singleton(ConfigurationParser::class);
        
        $this->app->extend(\Statamic\Contracts\Forms\FormRepository::class, function ($repository) {
            return new FormRepositoryDecorator($repository);
        });
    }

    public function bootAddon()
    {
        $connectorManager = app(ConnectorManager::class);
        
        // Register built-in connectors
        $connectorManager->register(new WebhookConnector);
    }
}
