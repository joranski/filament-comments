<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Tests\Support;

use Livewire\ComponentHook;

final class EnsureErrorBagHook extends ComponentHook
{
    public function hydrate($memo): void
    {
        $this->component->resetErrorBag();
    }

    public function mount(): void
    {
        $this->component->resetErrorBag();
    }
}
