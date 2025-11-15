<?php

namespace Stokoe\FormsToWherever;

use Statamic\Contracts\Data\Augmented;
use Statamic\Forms\Form as BaseForm;

class Form extends BaseForm
{
    public function newAugmentedInstance(): Augmented
    {
        return new AugmentedForm($this);
    }
}
