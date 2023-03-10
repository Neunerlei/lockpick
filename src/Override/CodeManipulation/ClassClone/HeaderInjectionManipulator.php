<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation\ClassClone;


use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;

class HeaderInjectionManipulator implements CodeManipulatorInterface
{
    protected const TPL = <<<PHP
/**
 * CLASS OVERRIDE GENERATOR - GENERATED FILE
 * This file is generated dynamically! You should not edit its contents,
 * because they will be lost as soon as the storage is cleared
 *
 * THIS FILE IS AUTOMATICALLY GENERATED!
 *
 * This is a copy of the class: {{classToOverride}}
 *
 * @see {{classToOverride}}
 */

PHP;

    /**
     * @inheritDoc
     */
    public function apply(string $code, int $type, array $context): string
    {
        $header = str_replace('{{classToOverride}}', $context['classToOverride'] ?? '', static::TPL);
        return preg_replace('~(<\?php|<\?)~i', '$1' . PHP_EOL . $header, $code, 1);
    }

}