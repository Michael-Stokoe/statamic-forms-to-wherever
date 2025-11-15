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
        return new Form($handle);
    }

    public function find($id)
    {
        $form = $this->repository->find($id);
        return $form ? $this->wrapForm($form) : null;
    }

    public function findByHandle($handle)
    {
        $form = $this->repository->findByHandle($handle);
        return $form ? $this->wrapForm($form) : null;
    }

    public function all()
    {
        return $this->repository->all()->map(fn($form) => $this->wrapForm($form));
    }

    protected function wrapForm($originalForm)
    {
        $form = new Form();
        $form->handle($originalForm->handle());
        $form->title($originalForm->title());
        $form->blueprint($originalForm->blueprint());
        return $form;
    }

    public function __call($method, $args)
    {
        return $this->repository->$method(...$args);
    }
}
