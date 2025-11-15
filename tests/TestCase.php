<?php

namespace Stokoe\FormsToWherever\Tests;

use Stokoe\FormsToWherever\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
