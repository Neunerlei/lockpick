<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation;


interface CodeManipulatorInterface
{
    public const TYPE_CLASS_CLONE = 0;
    public const TYPE_CLASS_ALIAS = 1;

    /**
     * Has to apply the string manipulations to the given code or return the unchanged code
     * if there are no manipulations that can be applied here.
     *
     * @param string $code The code that should be manipulated
     * @param int $type The type of manipulation to be executed. See the TYPE_ constants as a reference
     * @param array $context Additional information based on the type of manipulation to be executed
     * @return string
     */
    public function apply(string $code, int $type, array $context): string;
}