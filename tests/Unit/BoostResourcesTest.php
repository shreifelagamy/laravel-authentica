<?php

const BOOST = __DIR__.'/../../resources/boost';

it('has a guideline that uses no Blade beyond verbatim', function () {
    $guideline = file_get_contents(BOOST.'/guidelines/core.blade.php');

    expect($guideline)->toBeString()->not->toBeEmpty()
        ->not->toContain('{{')
        ->not->toContain('{!!')
        ->not->toContain('<?');

    preg_match_all('/(?<![\w.])@(\w+)/', (string) $guideline, $matches);

    expect(array_unique($matches[1]))->each->toBeIn(['verbatim', 'endverbatim'])
        ->and(substr_count((string) $guideline, '@verbatim'))->toBe(substr_count((string) $guideline, '@endverbatim'));
});

it('has a skill with name and description frontmatter', function () {
    $skill = (string) file_get_contents(BOOST.'/skills/authentica-development/SKILL.md');

    expect(preg_match('/\A---\n(.*?)\n---\n/s', $skill, $frontmatter))->toBe(1);

    preg_match('/^name: (.+)$/m', $frontmatter[1], $name);
    preg_match('/^description: (.+)$/m', $frontmatter[1], $description);

    expect($name[1] ?? null)->toBe('authentica-development')
        ->and($description[1] ?? '')->not->toBeEmpty();
});

it('tells agents nothing to run or install', function (string $file) {
    expect((string) file_get_contents(BOOST.'/'.$file))
        ->not->toContain('artisan')
        ->not->toContain('composer ')
        ->not->toContain('npm ');
})->with(['guidelines/core.blade.php', 'skills/authentica-development/SKILL.md']);
