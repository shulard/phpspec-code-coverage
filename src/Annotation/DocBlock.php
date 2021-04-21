<?php

declare(strict_types=1);

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FriendsOfPhpSpec\PhpSpec\CodeCoverage\Annotation;

use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function count;
use function file;
use function preg_match;
use function preg_match_all;
use function strtolower;
use function substr;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Reflector;

/**
 * This is an abstraction around a PHPUnit-specific docBlock,
 * allowing us to ask meaningful questions about a specific
 * reflection symbol.
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class DocBlock
{
    /** @var string */
    private $docComment;

    /** @var bool */
    private $isMethod;

    /** @var array<string, array<int, string>> pre-parsed annotations indexed by name and occurrence index */
    private $symbolAnnotations;

    /** @var int */
    private $startLine;

    /** @var int */
    private $endLine;

    /** @var string */
    private $fileName;

    /** @var string */
    private $name;

    /**
     * @var string
     *
     * @psalm-var class-string
     */
    private $className;

    public static function ofClass(ReflectionClass $class): self
    {
        $className = $class->getName();

        return new self(
            (string) $class->getDocComment(),
            false,
            self::extractAnnotationsFromReflector($class),
            $class->getStartLine(),
            $class->getEndLine(),
            $class->getFileName(),
            $className,
            $className
        );
    }

    /**
     * @psalm-param class-string $classNameInHierarchy
     */
    public static function ofMethod(ReflectionMethod $method, string $classNameInHierarchy): self
    {
        return new self(
            (string) $method->getDocComment(),
            true,
            self::extractAnnotationsFromReflector($method),
            $method->getStartLine(),
            $method->getEndLine(),
            $method->getFileName(),
            $method->getName(),
            $classNameInHierarchy
        );
    }

    /**
     * Note: we do not preserve an instance of the reflection object, since it cannot be safely (de-)serialized.
     *
     * @param string $docComment
     * @param bool $isMethod
     * @param array<string, array<int, string>> $symbolAnnotations
     * @param int $startLine
     * @param int $endLine
     * @param string $fileName
     * @param string $name
     * @param string $className
     */
    private function __construct(
        string $docComment,
        bool $isMethod,
        array $symbolAnnotations,
        int $startLine,
        int $endLine,
        string $fileName,
        string $name,
        string $className
    ) {
        $this->docComment        = $docComment;
        $this->isMethod          = $isMethod;
        $this->symbolAnnotations = $symbolAnnotations;
        $this->startLine         = $startLine;
        $this->endLine           = $endLine;
        $this->fileName          = $fileName;
        $this->name              = $name;
        $this->className         = $className;
    }

    /**
     * @psalm-return array<string, array{line: int, value: string}>
     */
    public function getInlineAnnotations(): array
    {
        $code        = file($this->fileName);
        $lineNumber  = $this->startLine;
        $startLine   = $this->startLine - 1;
        $endLine     = $this->endLine - 1;
        $codeLines   = array_slice($code, $startLine, $endLine - $startLine + 1);
        $annotations = [];

        foreach ($codeLines as $line) {
            if (preg_match('#/\*\*?\s*@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?\*/$#m', $line, $matches)) {
                $annotations[strtolower($matches['name'])] = [
                    'line'  => $lineNumber,
                    'value' => $matches['value'],
                ];
            }

            $lineNumber++;
        }

        return $annotations;
    }

    public function symbolAnnotations(): array
    {
        return $this->symbolAnnotations;
    }

    /**
     * @param string $docBlock
     * @return array<string, array<int, string>>
     */
    private static function parseDocBlock(string $docBlock): array
    {
        // Strip away the docblock header and footer to ease parsing of one line annotations
        $docBlock    = (string) substr($docBlock, 3, -2);
        $annotations = [];

        if (preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docBlock, $matches)) {
            $numMatches = count($matches[0]);

            for ($i = 0; $i < $numMatches; $i++) {
                $annotations[$matches['name'][$i]][] = (string) $matches['value'][$i];
            }
        }

        return $annotations;
    }

    /** 
     * @param ReflectionClass|ReflectionFunctionAbstract $reflector 
     * @return array
     */
    private static function extractAnnotationsFromReflector(Reflector $reflector): array
    {
        $annotations = [];

        if ($reflector instanceof ReflectionClass) {
            $annotations = array_merge(
                $annotations,
                ...array_map(
                    static function (ReflectionClass $trait): array {
                        return self::parseDocBlock((string) $trait->getDocComment());
                    },
                    array_values($reflector->getTraits())
                )
            );
        }

        return array_merge(
            $annotations,
            self::parseDocBlock((string) $reflector->getDocComment())
        );
    }
}
