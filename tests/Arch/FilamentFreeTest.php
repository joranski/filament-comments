<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * The package is Filament-free: Filament glue (widgets, schemas, settings panels,
 * Filament-format notifications) lives in the host app.
 *
 * @return list<string> "relative/path:line" for every Filament reference under $root
 */
function commentsFilamentReferences(string $root): array
{
    $patterns = [
        '/(?<![A-Za-z])Filament\\\\(?!Comments\\\\)/',
        '/<x-filament(?!-comments::)[\w.:-]*/',
        '/@filament\w*/',
        '/\bfilament(?:-[a-z]+)?::(?<!filament-comments::)/',
        '/\bfilament\.default_filesystem_disk\b/',
        '/\bfi-(?:fo|btn|icon|dropdown|ac|section|wi)-?[\w-]*/',
    ];

    $violations = [];

    foreach (['src', 'resources', 'config'] as $directory) {
        if (! is_dir($root.'/'.$directory)) {
            continue;
        }

        foreach (Finder::create()->files()->in($root.'/'.$directory)->name(['*.php', '*.blade.php', '*.js', '*.css']) as $file) {
            foreach (explode("\n", $file->getContents()) as $number => $line) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line) === 1) {
                        $violations[] = $directory.'/'.$file->getRelativePathname().':'.($number + 1);

                        continue 2;
                    }
                }
            }
        }
    }

    return $violations;
}

it('keeps src, resources and config free of Filament references', function (): void {
    expect(commentsFilamentReferences(dirname(__DIR__, 2)))->toBe([]);
});

it('does not depend on filament/* packages', function (): void {
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), associative: true, flags: JSON_THROW_ON_ERROR);

    $packages = array_keys(array_merge($manifest['require'] ?? [], $manifest['require-dev'] ?? [], $manifest['suggest'] ?? []));

    expect(array_values(array_filter($packages, fn (string $package): bool => str_starts_with($package, 'filament/'))))->toBe([]);
});

it('flags Filament classes and Blade components but not the package namespace', function (): void {
    $root = sys_get_temp_dir().'/comments-filament-probe-'.uniqid();
    mkdir($root.'/src', recursive: true);
    mkdir($root.'/resources/views', recursive: true);

    file_put_contents($root.'/src/Leaky.php', "<?php\n\nuse Filament\\Notifications\\Notification;\n");
    file_put_contents($root.'/src/Allowed.php', "<?php\n\nuse Joranski\\FilamentComments\\Support\\CommentAuthor;\n");
    file_put_contents($root.'/resources/views/leaky.blade.php', "<x-filament::section>Hi</x-filament::section>\n");
    file_put_contents($root.'/resources/views/allowed.blade.php', "<x-filament-comments::editor-mentions />\n@include('filament-comments::partials.composer')\n");

    $violations = commentsFilamentReferences($root);

    array_map(unlink(...), [
        $root.'/src/Leaky.php',
        $root.'/src/Allowed.php',
        $root.'/resources/views/leaky.blade.php',
        $root.'/resources/views/allowed.blade.php',
    ]);
    rmdir($root.'/resources/views');
    rmdir($root.'/resources');
    rmdir($root.'/src');
    rmdir($root);

    expect($violations)->toEqualCanonicalizing([
        'resources/views/leaky.blade.php:1',
        'src/Leaky.php:3',
    ]);
});
