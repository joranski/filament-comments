<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentGroups;

test('comment groups expose stable group and state constants', function (): void {
    expect(CommentGroups::DELAY)->toBe('delay')
        ->and(CommentGroups::CHAT)->toBe('chat')
        ->and(CommentGroups::STATE_PROMOTED)->toBe('promoted');
});
