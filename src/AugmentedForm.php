<?php

namespace Stokoe\FormsToWherever;

use Statamic\Fields\Value;
use Statamic\Forms\AugmentedForm as BaseAugmentedForm;

class AugmentedForm extends BaseAugmentedForm
{
    public function get($key): Value
    {
        $value = parent::get($key);
        
        if ($key === 'fields' && $value instanceof Value) {
            $fields = $value->value();
            
            if ($fields instanceof \Illuminate\Support\Collection) {
                $filtered = $fields->reject(function ($field) {
                    if ($field instanceof \Statamic\Fields\Field) {
                        return $field->type() === 'form_connectors';
                    }
                    return ($field['type'] ?? '') === 'form_connectors';
                })->values();
                return new Value($filtered, $value->handle(), $value->fieldtype(), $value->augmentable());
            }
            
            if (is_array($fields)) {
                $filtered = array_values(array_filter($fields, function ($field) {
                    if ($field instanceof \Statamic\Fields\Field) {
                        return $field->type() !== 'form_connectors';
                    }
                    return ($field['type'] ?? '') !== 'form_connectors';
                }));
                return new Value($filtered, $value->handle(), $value->fieldtype(), $value->augmentable());
            }
        }
        
        return $value;
    }
}
