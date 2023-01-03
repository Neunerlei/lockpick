<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\CodeManipulation\ClassClone;


use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;

class SelfReferenceManipulator implements CodeManipulatorInterface
{
    /**
     * @inheritDoc
     */
    public function apply(string $code, int $type, array $context): string
    {
        $searches = [];
        $replacements = [];

        $classToOverride = $context['classToOverride'] ?? null;
        $classBaseName = $context['classToOverrideBaseName'] ?? '';

        // Fix the return type in all doc-blocks
        $code = preg_replace_callback('~(/\\*(.|\\n)*?\\*/)~m',
            function ($m) use ($classBaseName, $classToOverride) {
                return $this->replaceSelfInString($m[0], $classBaseName, $classToOverride);
            },
            $code
        );

        // Resolve actual "self" references inside methods to "static" references
        // Also resolve static class base name reference to "static" references as well
        foreach ($this->resolveMethodBlocks($code) as $classBlock) {
            foreach ($classBlock as $methodBlock) {
                $searches[] = $methodBlock;

                $replacements[] = $this->replaceSelfInString($methodBlock, $classBaseName);
            }
        }

        return str_replace($searches, $replacements, $code);
    }

    /**
     * Regex time! A helper to replace all the possible "self" references with "static".
     * While this is not really needed for the code to run correctly, it helps the IDE and the author
     * with code completions
     *
     * @param string $string The string to replace the reference in
     * @param string $classBaseName The base name of the class to replace with static as well
     * @param string|null $classFullName The full name of the class to replace with static, too (optional)
     * @return string
     */
    protected function replaceSelfInString(string $string, string $classBaseName, ?string $classFullName = null): string
    {
        $quotBaseName = preg_quote($classBaseName, '~');
        $map = [
            '~(^|\\s|\\t)self::~i' => '$1static::',
            '~(\\s|\\t)?:([^{]*?\\s)?self(\\s|\\t|\\|)~i' => '$1:$2static$3',
            '~\\|(\\s|\\t)?self(\\s|\\t|\\|)~i' => '|$1static$2',
            '~\\|(\\s|\\t)?' . $quotBaseName . '(\\s|\\t|\\|)~i' => '|$1static$2',
            '~(^|\\s|\\t)' . $quotBaseName . '::~i' => '$1static::',
            '~(\\s|\\t)?:([^{]*?\\s)?' . $quotBaseName . '(?:\\s|\\t|\\|)~i' => '$1:$2static',
            // Only used in doc-blocks if the return type only has a single value
            '~(@return )self(\\s|\\t|)~i' => '$1static$2',
            '~(@return )' . $quotBaseName . '(\\s|\\t|)~i' => '$1static$2',
        ];

        if ($classFullName) {
            $quotFullName = preg_quote($classFullName, '~');

            $map = array_merge($map,
                [
                    '~\\|(\\s|\\t)?(?:\\\\)?' . $quotFullName . '(\\s|\\t|\\|)~i' => '|$1static$2',
                    '~(^|\\s|\\t)(?:\\\\)?' . $quotFullName . '::~i' => '$1static::',
                    '~(\\s|\\t)?:([^{]*?\\s)?(?:\\\\)?' . $quotFullName . '(?:\\s|\\t|\\|)~i' => '$1:$2static',
                    // Only used in doc-blocks if the return type only has a single value
                    '~(@return )(?:\\\\)?' . $quotFullName . '(\\s|\\t|)~i' => '$1static$2',
                ]
            );

        }

        return preg_replace(array_keys($map), $map, $string);
    }

    /**
     * Super simple lexer that splits up the given code into a rudimentary list of classes
     * with a list of method codes inside. This allows us to only work within the boundaries of a method body.
     * @param string $code
     * @return array
     */
    protected function resolveMethodBlocks(string $code): array
    {
        $length = strlen($code);

        $isInClass = false;
        $nextBraceIsClassOpener = false;
        $classList = [];
        $classLevel = 0;

        $isInMethod = false;
        $nextBraceIsMethodOpener = false;
        $methodList = [];
        $methodTmp = '';
        $methodLevel = 0;

        $tmp = '';

        for ($i = 0; $i < $length; $i++) {
            $char = $code[$i];
            $isBrace = $char === '{';
            $isBraceClosing = $char === '}';

            $tmp .= $char;

            if (!$isInClass && str_ends_with($tmp, 'class ')) {
                $nextBraceIsClassOpener = true;
                $tmp = '';
                continue;
            }

            if ($isInMethod) {
                $methodTmp .= $char;
            } elseif ($isInClass && str_ends_with($tmp, 'function ')) {
                $nextBraceIsMethodOpener = true;
                $tmp = '';
                continue;
            }

            if ($isBrace) {
                if ($nextBraceIsClassOpener) {
                    $isInClass = true;
                    $nextBraceIsClassOpener = false;
                    $tmp = '';
                } elseif ($nextBraceIsMethodOpener) {
                    $isInMethod = true;
                    $nextBraceIsMethodOpener = false;
                    $methodTmp = $tmp;
                    $tmp = '';
                }

                if ($isInClass) {
                    $classLevel++;
                }

                if ($isInMethod) {
                    $methodLevel++;
                }

                continue;
            }

            if ($isBraceClosing) {
                if ($isInMethod) {
                    $methodLevel--;

                    if ($methodLevel === 0) {
                        $isInMethod = false;
                        $methodList[] = $methodTmp;
                        $methodTmp = '';
                    }
                }

                if ($isInClass) {
                    $classLevel--;

                    if ($classLevel === 0) {
                        $isInClass = false;
                        $classList[] = $methodList;
                        $methodList = [];
                    }
                }
            }
        }

        return $classList;
    }
}