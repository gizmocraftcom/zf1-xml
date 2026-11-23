<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->files()
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRiskyAllowed(true)
    ->setRules(array(
        '@PSR2'                     => true,
        '@PHPUnit60Migration:risky' => true,
        'binary_operator_spaces'    => array('operators' => array('=' => 'align', '=>' => 'align')),
        'single_quote'              => true,
        'array_syntax'              => array('syntax' => 'long'),
        'concat_space'              => array('spacing' => 'one'),
        'psr_autoloading'           => array('dir' => 'src'),
        'short_scalar_cast'         => true,
    ))
    // php-cs-fixer does not yet support PHP 8.6 syntax and aborts there; allow it until a release supports 8.6.
    ->setUnsupportedPhpVersionAllowed(true)
    ->setUsingCache(true)
    ->setFinder($finder);
;
