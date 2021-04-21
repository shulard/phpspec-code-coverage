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

use function array_key_exists;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Reflection information, and therefore DocBlock information, is static within
 * a single PHP process. It is therefore okay to use a Singleton registry here.
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Registry
{
    /**
     * @var array<string, DocBlock> indexed by class name
     */
    private $classDocBlocks = [];

    /**
     * @var array<string, array<string, DocBlock>> indexed by class name and method name
     */
    private $methodDocBlocks = [];

    /**
     * @param string $class
     * @return DocBlock
     * @throws ReflectionException
     */
    public function forClassName(string $class): DocBlock
    {
        if (array_key_exists($class, $this->classDocBlocks)) {
            return $this->classDocBlocks[$class];
        }

        $reflection = new ReflectionClass($class);

        return $this->classDocBlocks[$class] = DocBlock::ofClass($reflection);
    }

    /**
     * @param string $classInHierarchy
     * @param string $method
     * @return DocBlock
     * @throws ReflectionException
     */
    public function forMethod(string $classInHierarchy, string $method): DocBlock
    {
        if (isset($this->methodDocBlocks[$classInHierarchy][$method])) {
            return $this->methodDocBlocks[$classInHierarchy][$method];
        }

        $reflection = new ReflectionMethod($classInHierarchy, $method);

        return $this->methodDocBlocks[$classInHierarchy][$method] = DocBlock::ofMethod($reflection, $classInHierarchy);
    }
}
