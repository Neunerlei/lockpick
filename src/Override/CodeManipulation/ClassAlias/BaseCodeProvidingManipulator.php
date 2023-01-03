<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation\ClassAlias;


use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;

class BaseCodeProvidingManipulator implements CodeManipulatorInterface
{
    protected const TPL = <<<PHP
<?php
declare(strict_types=1);
/**
 * CLASS OVERRIDE GENERATOR - GENERATED FILE
 * This file is generated dynamically! You should not edit its contents,
 * because they will be lost as soon as the storage is cleared
 *
 * The original class can be found here:
 * @see \\{{classToOverride}}
 *
 * The clone of the original class can be found here:
 * @see \\{{copyClassName}}
 *
 * The class which is used as override can be found here:
 * @see \\{{finalClassName}}
 */
Namespace {{classToOverrideNamespace}};
if(!class_exists('\\{{classToOverride}}', false)) {

    class {{classToOverrideBaseName}}
        extends \\{{classToOverrideWith}} {}
}
PHP;

    /**
     * @inheritDoc
     */
    public function apply(string $code, int $type, array $context): string
    {
        if ($type !== CodeManipulatorInterface::TYPE_CLASS_ALIAS) {
            return $code;
        }

        return str_replace(
            [
                '{{classToOverride}}',
                '{{classToOverrideNamespace}}',
                '{{classToOverrideBaseName}}',
                '{{classToOverrideWith}}',
                '{{copyClassName}}',
                '{{finalClassName}}',
            ],
            [
                $context['classToOverride'] ?? '',
                $context['classToOverrideNamespace'] ?? '',
                $context['classToOverrideBaseName'] ?? '',
                $context['classToOverrideWith'] ?? '',
                $context['copyClassName'] ?? '',
                $context['finalClassName'] ?? '',
            ],
            static::TPL
        );
    }

}