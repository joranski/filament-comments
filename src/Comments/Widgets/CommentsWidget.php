<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Comments\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class CommentsWidget extends Widget
{
    protected string $view = 'filament-comments::widgets.comments-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public ?Model $record = null;

    public string $layout = 'full';

    public ?string $group = null;

    public ?string $topic = null;

    public ?string $excludeGroup = null;

    /** @var list<string>|null */
    public ?array $excludedGroups = null;

    public string $heading = 'Comments';

    public bool $showHeading = true;

    public ?int $threadMaxHeight = null;

    /**
     * Per-panel condensed UI profile (smaller icons, typography, spacing).
     */
    public bool $compactProfile = false;

    /**
     * Enable a condensed panel profile for sidebars and dense workspaces.
     */
    public function compact(bool $compact = true): static
    {
        $this->compactProfile = $compact;

        return $this;
    }
}
