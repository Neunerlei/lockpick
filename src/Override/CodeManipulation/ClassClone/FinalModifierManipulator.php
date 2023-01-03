<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation\ClassClone;


use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;

class FinalModifierManipulator implements CodeManipulatorInterface
{
    /**
     * @inheritDoc
     */
    public function apply(string $code, int $type, array $context): string
    {
        return preg_replace(
            '~(^|\\s|\\t)final\\s+((?:protected\\s|private\\s|public\\s|static\\s|abstract\\s)*(?:function|class|const))~',
            '$1$2',
            $code
        );
    }

}