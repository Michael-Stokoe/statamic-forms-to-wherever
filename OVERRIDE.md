# Form Field Override Implementation

## The Problem

The `form_connectors` fieldtype needs to be:
- **Visible and configurable** in the Statamic Control Panel
- **Completely hidden** from frontend form rendering
- **Compatible with ALL frontends** without requiring custom modifications

## The Challenge

Statamic's form serialization process goes through several layers:
1. `Form` class contains the form data and blueprint
2. `AugmentedForm` class handles serialization for frontend consumption
3. Fields are collected from the blueprint and included in the serialized output
4. Frontend templates receive this serialized data via `{{ form | to_json }}`

Simply overriding fieldtype methods wasn't sufficient because the field was still being included in the form's field collection during serialization.

## The Solution

### 1. Custom Form Class (`src/Form.php`)

```php
class Form extends BaseForm
{
    public function newAugmentedInstance(): Augmented
    {
        return new AugmentedForm($this);
    }
}
```

**Why this works:** Overrides the base Form class to return our custom AugmentedForm instead of Statamic's default one.

### 2. Custom AugmentedForm Class (`src/AugmentedForm.php`)

```php
class AugmentedForm extends BaseAugmentedForm
{
    public function get($key): Value
    {
        $value = parent::get($key);
        
        if ($key === 'fields' && $value instanceof Value) {
            // Filter out form_connectors fields from the collection
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
```

**Why this works:** Intercepts the `fields` key during serialization and filters out any fields with type `form_connectors` before returning the Value object.

### 3. Repository Override (`src/ServiceProvider.php`)

```php
$this->app->extend(\Statamic\Contracts\Forms\FormRepository::class, function ($repository, $app) {
    return new class($repository) {
        private $repository;
        
        public function __construct($repository) {
            $this->repository = $repository;
        }
        
        public function make($handle = null) {
            return new \Stokoe\FormsToWherever\Form($handle);
        }
        
        public function __call($method, $args) {
            $result = $this->repository->$method(...$args);
            
            // Convert any returned Form instances to our custom Form class
            if ($result instanceof \Statamic\Forms\Form) {
                $custom = new \Stokoe\FormsToWherever\Form();
                $reflection = new \ReflectionClass($result);
                foreach ($reflection->getProperties() as $property) {
                    $property->setAccessible(true);
                    $property->setValue($custom, $property->getValue($result));
                }
                return $custom;
            }
            
            // Handle collections of forms
            if ($result instanceof \Illuminate\Support\Collection) {
                return $result->map(function ($item) {
                    if ($item instanceof \Statamic\Forms\Form) {
                        // Convert to custom Form class
                    }
                    return $item;
                });
            }
            
            return $result;
        }
    };
});
```

**Why this works:** Extends the FormRepository to ensure that whenever forms are retrieved (via `make()`, `find()`, `all()`, etc.), they return instances of our custom Form class instead of the base Statamic Form class.

## How It All Works Together

1. **Repository Override**: Ensures all form instances are our custom `Form` class
2. **Custom Form**: Returns our custom `AugmentedForm` when serialization is needed
3. **Custom AugmentedForm**: Filters out `form_connectors` fields during the `get('fields')` call
4. **Seamless Integration**: Works transparently with all existing Statamic functionality

## Why This Approach is Superior

- **No frontend modifications required**: Works with any template system
- **Maintains CP functionality**: Fields remain fully configurable in the Control Panel
- **Preserves all form features**: Email notifications, submissions, validation all work normally
- **Clean separation**: Connector logic is completely hidden from frontend concerns
- **Future-proof**: Works with any Statamic form rendering approach

## The Key Insight

The breakthrough was understanding that Statamic's augmentation system is the final step before serialization. By intercepting at the `AugmentedForm` level and filtering the `fields` key specifically, we can remove connector fields from the serialized output while leaving all other functionality intact.

This approach leverages Statamic's own architecture rather than fighting against it, resulting in a clean, maintainable solution that works universally across all frontend implementations.
