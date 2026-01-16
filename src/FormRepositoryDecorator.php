<?php

namespace Stokoe\FormsToWherever;

use Statamic\Contracts\Forms\FormRepository;

class FormRepositoryDecorator implements FormRepository
{
    public function __construct(
        protected FormRepository $repository
    ) {}

    public function make($handle = null)
    {
        return $this->repository->make($handle);
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function findByHandle($handle)
    {
        return $this->repository->findByHandle($handle);
    }

    public function all()
    {
        return $this->repository->all();
    }

    public function __call($method, $args)
    {
        return $this->repository->$method(...$args);
    }
}
