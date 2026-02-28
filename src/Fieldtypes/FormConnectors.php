<?php

namespace Stokoe\FormsToWherever\Fieldtypes;

use Illuminate\Support\Str;
use Stokoe\FormsToWherever\ConnectorManager;
use Statamic\Fields\Fieldtype;

class FormConnectors extends Fieldtype
{
    protected static $title = 'Form Connectors';

    protected $icon = 'hyperlink';

    protected $selectable = false;
    protected $selectableInForms = true;
    protected $hidden = true;

    public function preload()
    {
        return [];
    }

    protected function configFieldItems(): array
    {
        $connectorManager = app(ConnectorManager::class);
        $sections = [];

        // Global settings section
        $sections[] = [
            'display' => 'Processing Settings',
            'fields' => [
                'async_processing' => [
                    'display' => 'Asynchronous Processing',
                    'type' => 'toggle',
                    'instructions' => 'Process connectors in background jobs (recommended for production)',
                    'default' => true,
                    'width' => 100,
                ],
                'throw_on_error' => [
                    'display' => 'Throw on Error',
                    'type' => 'toggle',
                    'instructions' => 'Re-throw connector exceptions during synchronous processing, allowing your frontend to catch and display errors to users',
                    'default' => false,
                    'width' => 100,
                    'if' => ['async_processing' => 'equals false'],
                ],
                'condition_field' => [
                    'display' => 'Condition Field',
                    'type' => 'text',
                    'instructions' => 'Only run these connectors when this form field handle has the value specified below. Leave blank to always run.',
                    'width' => 50,
                ],
                'condition_value' => [
                    'display' => 'Condition Value',
                    'type' => 'text',
                    'instructions' => 'The value the condition field must match for these connectors to run',
                    'width' => 50,
                    'if' => ['condition_field' => 'not empty'],
                ],
            ],
        ];

        foreach ($connectorManager->all() as $handle => $connector) {
            $fields = [
                $handle . '_enabled' => [
                    'display' => 'Enable ' . $connector->name(),
                    'type' => 'toggle',
                    'default' => false,
                    'width' => 100,
                ],
            ];

            // Add connector-specific fields
            foreach ($connector->fieldset() as $field) {
                $fieldHandle = $field['handle'];
                $fieldConfig = $field['field'];

                // Make validation conditional on connector being enabled
                if (isset($fieldConfig['validate'])) {
                    $validation = $fieldConfig['validate'];
                    if (Str::contains($validation, 'required')) {
                        $fieldConfig['validate'] = Str::replace('required', "required_if:{$handle}_enabled,true", $validation);
                    }
                }

                $fields[$handle . '_' . $fieldHandle] = array_merge($fieldConfig, [
                    'if' => [$handle . '_enabled' => 'equals true']
                ]);
            }

            $sections[] = [
                'display' => $connector->name(),
                'fields' => $fields,
            ];
        }

        return $sections;
    }
}
