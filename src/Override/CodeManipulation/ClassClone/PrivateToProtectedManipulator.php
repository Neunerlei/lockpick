<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation\ClassClone;


use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;

class PrivateToProtectedManipulator implements CodeManipulatorInterface
{
    /**
     * @inheritDoc
     */
    public function apply(string $code, int $type, array $context): string
    {
        $code = preg_replace_callback('~(^|\\s|\\t)private(\\s+(?:static\\s|final\\s)?(?:\$|function|const))~i',
            static function ($m) {
                [, $before, $after] = $m;

                return $before . 'protected' . $after;
            }, $code);

        // Special handling for private properties with type-hints,
        // This will also take care of private constructor arguments
        $code = preg_replace_callback('~(^|\\s|\\t)private(\\s+[^$;]*?)\$~i',
            static function ($m) {
                [, $before, $after] = $m;

                return $before . 'protected' . $after . '$';
            }, $code);

        return $code;
    }

}