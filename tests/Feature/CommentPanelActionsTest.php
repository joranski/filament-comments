<?php

declare(strict_types=1);

use Filament\Actions\Contracts\HasActions;
use Joranski\FilamentComments\Comments\Livewire\CommentPanel;

test('comment panel implements filament actions for rich editor attach files', function (): void {
    expect(CommentPanel::class)
        ->toImplement(HasActions::class)
        ->and(method_exists(CommentPanel::class, 'mountAction'))->toBeTrue();
});
