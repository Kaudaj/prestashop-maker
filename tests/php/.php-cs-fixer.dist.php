<?php
$rootDir = __DIR__ . '/../../';

$finder = (new PhpCsFixer\Finder())
    ->in($rootDir)
    ->exclude(['var', 'vendor'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'echo_tag_syntax' => ['format' => 'short']
    ])
    ->setLineEnding(PHP_EOL)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setFinder($finder)
;
