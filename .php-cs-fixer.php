<?php

use PhpCsFixer\Config;
use PhpCsFixer\ConfigInterface;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

class Linter
{
    protected static array $notName = [
        '_ide_helper_actions.php',
        '_ide_helper_models.php',
        '.phpstorm.meta.php',
        '.php-cs-fixer.php',
        '_ide_helper.php',
        '*.blade.php',
        'server.php',
    ];

    protected static array $exclude = [
        'bin',
        'docs',
        'logs',
        'plugins',
        'resources',
        'templates',
        'tmp',
        'vendor',
        'webroot',
    ];

    public static function create(): ConfigInterface
    {
        return (new Config())
            ->setFinder(self::finder())
            ->setRules(self::rules())
            ->setRiskyAllowed(true)
            ->setUsingCache(true)
            ->setParallelConfig(ParallelConfigFactory::detect());
    }

    protected static function finder(): Finder
    {
        return Finder::create()
            ->in(__DIR__)
            ->notName(static::$notName)
            ->exclude(static::$exclude)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);
    }

    protected static function rules(): array
    {
        return [
            // Groups
            '@PhpCsFixer' => true,
            '@PhpCsFixer:risky' => true,
            '@PHP84Migration' => true,
            '@PHP82Migration:risky' => true,
            '@PHPUnit100Migration:risky' => true,

            // Class Notation
            'ordered_class_elements' => ['order' => [
                'use_trait',
                'case',
                'constant',
                'property',
                'construct',
                'method',
            ]],
            'single_trait_insert_per_statement' => true,
            'class_attributes_separation' => [
                'elements' => [
                    'const' => 'one',
                    'method' => 'one',
                    'property' => 'one',
                    'trait_import' => 'none',
                    'case' => 'one',
                ],
            ],

            // Comment
            'single_line_comment_style' => ['comment_types' => ['hash']],

            // Control Structure
            'trailing_comma_in_multiline' => [
                'after_heredoc' => true,
                'elements' => ['arguments', 'arrays', 'match', 'parameters'],
            ],
            'yoda_style' => [
                'equal' => false,
                'identical' => false,
                'less_and_greater' => false,
            ],

            // Function Notation
            'native_function_invocation' => false,

            // Import
            'fully_qualified_strict_types' => false,
            'global_namespace_import' => [
                'import_classes' => true,
                'import_constants' => true,
                'import_functions' => true,
            ],

            // Operator
            'increment_style' => ['style' => 'post'],
            'new_with_parentheses' => ['anonymous_class' => false],
            'operator_linebreak' => ['position' => 'end', 'only_booleans' => true],

            // Rule overwrites
            'list_syntax' => ['syntax' => 'short'],

            // Phpunit
            'php_unit_method_casing' => ['case' => 'snake_case'],
            'php_unit_strict' => false,
            'php_unit_test_class_requires_covers' => false,
            'php_unit_internal_class' => false,
            // 'php_unit_test_case_static_method_calls' => ['call_type' => 'this'], // Risky
            // 'php_unit_attributes' => true,
            'php_unit_fqcn_annotation' => false,

            // PHP Doc
            'phpdoc_to_param_type' => false,
            'phpdoc_to_return_type' => false,
            'phpdoc_to_comment' => false,
            'phpdoc_no_empty_return' => false,
            'phpdoc_line_span' => true,
            'phpdoc_types_order' => [
                'sort_algorithm' => 'alpha',
                'null_adjustment' => 'always_last',
            ],

            // Semicolon
            'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],

            // Strict
            'strict_comparison' => false,
            'strict_param' => false,

            // String Notation
            'string_length_to_empty' => false,

            // Whitespace
            'blank_line_before_statement' => ['statements' => [
                'continue',
                'declare',
                'foreach',
                'return',
                'throw',
                'while',
                'yield',
                'goto',
                'try',
                'for',
                'if',
                'do',
            ]],
            'method_chaining_indentation' => false,
        ];
    }
}

return Linter::create();
