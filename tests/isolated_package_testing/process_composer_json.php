<?php

declare(strict_types=1);

const COMPOSER_AMENDMENTS = [
    'typhoon/change-detector' => [
        'require-dev' => [
            'mikey179/vfsstream' => '^1.6.11',
        ],
    ],
    'typhoon/reflection' => [
        'require-dev' => [
            'symfony/finder' => '^6.4.10 || ^7.1.3',
            'typhoon/phpstorm-reflection-stubs' => '*',
        ],
    ],
];

/**
 * @var array{
 *     name: non-empty-string,
 *     autoload?: array{
 *         "psr-4"?: array<string, string>,
 *         files?: list<string>,
 *     },
 * }
 */
$data = json_decode(file_get_contents('php://stdin'), associative: true, flags: JSON_THROW_ON_ERROR);

$data['minimum-stability'] = 'dev';

if (isset($data['autoload']['psr-4'])) {
    foreach ($data['autoload']['psr-4'] as $namespace => &$path) {
        if ($path === '') {
            $data['autoload-dev']['psr-4'][$namespace] = 'tests/' . $path;
        }

        $path = 'src/' . $path;
    }
}

if (isset($data['autoload']['files'])) {
    foreach ($data['autoload']['files'] as &$path) {
        $path = 'src/' . $path;
    }
}

if (isset(COMPOSER_AMENDMENTS[$data['name']])) {
    $data = array_merge_recursive($data, COMPOSER_AMENDMENTS[$data['name'] ?? ''] ?? []);
}

/** @psalm-suppress ForbiddenCode */
echo json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
