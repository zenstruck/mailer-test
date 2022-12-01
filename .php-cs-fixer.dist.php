<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->notName('*.tpl.php')
;

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@DoctrineAnnotation' => true,
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,
        '@PHPUnit75Migration:risky' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'multiline_comment_opening_closing' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'imports_order' => ['const', 'class', 'function'],
        ],
        'ordered_class_elements' => true,
        'native_function_invocation' => ['include' => ['@internal']],
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'escape_implicit_backslashes' => true,
        'mb_str_functions' => true,
        'logical_operators' => true,
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'php_unit_test_annotation' => ['style' => 'annotation'],
        'no_unreachable_default_argument_value' => true,
        'declare_strict_types' => false,
        'void_return' => false,
        'single_trait_insert_per_statement' => false,
        'simple_to_complex_string_variable' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'allow_unused_params' => true,
            'remove_inheritdoc' => true,
        ],
        'phpdoc_to_comment' => false,
        'function_declaration' => ['closure_function_spacing' => 'none', 'closure_fn_spacing' => 'none'],
        'nullable_type_declaration_for_default_null_value' => true,
        'phpdoc_separation' => ['groups' => [
            ['test', 'dataProvider', 'before', 'internal', 'after'],
            ['template', 'implements', 'extends'],
            ['phpstan-type', 'phpstan-import-type'],
            ['deprecated', 'link', 'see', 'since'],
            ['author', 'copyright', 'license', 'source'],
            ['category', 'package', 'subpackage'],
            ['property', 'property-read', 'property-write'],
        ]],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
