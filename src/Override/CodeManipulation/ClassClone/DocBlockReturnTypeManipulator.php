<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation\ClassClone;


use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;

class DocBlockReturnTypeManipulator implements CodeManipulatorInterface
{
    /**
     * @inheritDoc
     */
    public function apply(string $code, int $type, array $context): string
    {
        // TODO: Implement apply() method.
    }

}