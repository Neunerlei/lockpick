<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation\ClassClone;


use Neunerlei\FileSystem\Path;
use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;
use Neunerlei\Lockpick\Override\Exception\OverrideClassRenamingFailedException;

class ClassRenamingManipulator implements CodeManipulatorInterface
{
    /**
     * @inheritDoc
     */
    public function apply(string $code, int $type, array $context): string
    {
        if ($type !== CodeManipulatorInterface::TYPE_CLASS_CLONE) {
            return $code;
        }

        $classToOverride = $context['classToOverride'] ?? null;
        $copyClassName = $context['copyClassName'] ?? null;

        if (!$classToOverride || !$copyClassName) {
            return $code;
        }

        $classBaseName = Path::classBasename($classToOverride);
        $copyClassBaseName = Path::classBasename($copyClassName);
        $nameWasChanged = false;

        $code = preg_replace_callback(
            '~(class\\s+)(.*?)(\\s*(?:;|$|{|\\n)|\\s+\\w|\\s+})~si',
            static function ($m) use ($classBaseName, $copyClassBaseName, &$nameWasChanged) {
                if ($m[2] !== $classBaseName) {
                    return $m[0];
                }
                $nameWasChanged = true;

                return $m[1] . $copyClassBaseName . $m[3];
            },
            $code
        );

        if (!$nameWasChanged) {
            throw new OverrideClassRenamingFailedException(
                'Failed to rewrite the name of class: ' . $classBaseName . ' to: ' .
                $copyClassBaseName . ' when creating a copy of class: ' . $classToOverride);
        }

        return $code;
    }

}