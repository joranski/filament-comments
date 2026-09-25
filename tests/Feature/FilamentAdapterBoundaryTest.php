<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Filament panel integration (widgets, schema helpers, form components,
 * settings) lives under src/Filament. CommentPanel and the attachment
 * contract still depend on Filament's RichEditor until the Flux composer
 * lands; this test keeps new panel-only code from spreading outside the adapter.
 */
it('keeps Filament widgets, schema components and form fields inside src/Filament', function (): void {
    $pattern = '/^use\s+Filament\\\\(Widgets|Schemas\\\\Components)\\\\|extends\s+(Widget|RichEditor)\b/m';

    $offenders = [];

    foreach (Finder::create()->files()->name('*.php')->in(dirname(__DIR__, 2).'/src')->notPath('Filament') as $file) {
        if (preg_match($pattern, $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
