<?php

declare(strict_types=1);

namespace Stokoe\FormsToWherever;

use Stokoe\FormsToWherever\Contracts\ConnectorInterface;

class ConnectorManager
{
    protected array $connectors = [];

    public function register(ConnectorInterface $connector): void
    {
        $this->connectors[$connector->handle()] = $connector;
    }

    public function get(string $handle): ?ConnectorInterface
    {
        return $this->connectors[$handle] ?? null;
    }

    public function all(): array
    {
        return $this->connectors;
    }

    public function getFieldsets(): array
    {
        $fieldsets = [];
        
        foreach ($this->connectors as $connector) {
            $fieldsets[$connector->handle()] = [
                'display' => $connector->name(),
                'fields' => $connector->fieldset(),
            ];
        }

        return $fieldsets;
    }
}
