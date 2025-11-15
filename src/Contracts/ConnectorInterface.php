<?php

namespace Stokoe\FormsToWherever\Contracts;

use Statamic\Forms\Submission;

interface ConnectorInterface
{
    public function handle(): string;
    
    public function name(): string;
    
    public function fieldset(): array;
    
    public function process(Submission $submission, array $config): void;
}
