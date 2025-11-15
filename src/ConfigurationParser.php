<?php

declare(strict_types=1);

namespace Stokoe\FormsToWherever;

use Stokoe\FormsToWherever\Contracts\ConnectorInterface;

class ConfigurationParser
{
    public function __construct(
        protected ConnectorManager $connectorManager
    ) {}

    public function parseFromBlueprint(array $blueprintConfig): array
    {
        $connectors = [];
        
        foreach ($this->connectorManager->all() as $handle => $connector) {
            if (!($blueprintConfig["{$handle}_enabled"] ?? false)) {
                continue;
            }

            $config = ['type' => $handle, 'enabled' => true];
            
            foreach ($connector->fieldset() as $field) {
                $key = "{$handle}_{$field['handle']}";
                if (isset($blueprintConfig[$key])) {
                    $config[$field['handle']] = $blueprintConfig[$key];
                }
            }
            
            if ($this->isValidConfig($connector, $config)) {
                $connectors[] = $config;
            }
        }
        
        return $connectors;
    }

    protected function isValidConfig(ConnectorInterface $connector, array $config): bool
    {
        foreach ($connector->fieldset() as $field) {
            $validation = $field['field']['validate'] ?? '';
            if (str_contains($validation, 'required') && empty($config[$field['handle']])) {
                return false;
            }
        }
        return true;
    }
}
