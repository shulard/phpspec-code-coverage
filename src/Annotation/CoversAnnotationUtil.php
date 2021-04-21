<?php

declare(strict_types=1);

namespace FriendsOfPhpSpec\PhpSpec\CodeCoverage\Annotation;

use FriendsOfPhpSpec\PhpSpec\CodeCoverage\Exception\CodeCoverageException;
use FriendsOfPhpSpec\PhpSpec\CodeCoverage\Exception\InvalidCoversTargetException;
use SebastianBergmann\CodeUnit\CodeUnitCollection;
use SebastianBergmann\CodeUnit\InvalidCodeUnitException;
use SebastianBergmann\CodeUnit\Mapper;

final class CoversAnnotationUtil
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @throws CodeCoverageException
     *
     * @return array|bool
     */
    public function getLinesToBeCovered(string $className, string $methodName)
    {
        $annotations = $this->parseTestMethodAnnotations(
            $className,
            $methodName
        );

        if (!$this->shouldCoversAnnotationBeUsed($annotations)) {
            return false;
        }

        return $this->getLinesToBeCoveredOrUsed($className, $methodName, 'covers');
    }

    /**
     * Returns lines of code specified with the @uses annotation.
     *
     * @throws CodeCoverageException
     */
    public function getLinesToBeUsed(string $className, string $methodName): array
    {
        return $this->getLinesToBeCoveredOrUsed($className, $methodName, 'uses');
    }

    public function parseTestMethodAnnotations(string $className, ?string $methodName = ''): array
    {
        if ($methodName !== null) {
            try {
                return [
                    'method' => $this->registry->forMethod($className, $methodName)->symbolAnnotations(),
                    'class'  => $this->registry->forClassName($className)->symbolAnnotations(),
                ];
            } catch (\ReflectionException $methodNotFound) {
                // ignored
            }
        }

        return [
            'method' => null,
            'class' => $this->registry->forClassName($className)->symbolAnnotations(),
        ];
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param string $mode
     * @return array
     * @throws CodeCoverageException
     */
    private function getLinesToBeCoveredOrUsed(string $className, string $methodName, string $mode): array
    {
        $annotations = $this->parseTestMethodAnnotations(
            $className,
            $methodName
        );

        $classShortcut = null;

        if (!empty($annotations['class'][$mode . 'DefaultClass'])) {
            if (count($annotations['class'][$mode . 'DefaultClass']) > 1) {
                throw new CodeCoverageException(
                    sprintf(
                        'More than one @%sClass annotation in class or interface "%s".',
                        $mode,
                        $className
                    )
                );
            }

            $classShortcut = $annotations['class'][$mode . 'DefaultClass'][0];
        }

        $list = $annotations['class'][$mode] ?? [];

        if (isset($annotations['method'][$mode])) {
            $list = array_merge($list, $annotations['method'][$mode]);
        }

        $codeUnits = CodeUnitCollection::fromArray([]);
        $mapper = new Mapper();

        foreach (array_unique($list) as $element) {
            if ($classShortcut && strncmp($element, '::', 2) === 0) {
                $element = $classShortcut . $element;
            }

            $element = preg_replace('/[\s()]+$/', '', $element);
            $element = explode(' ', $element);
            $element = $element[0];

            if ($mode === 'covers' && interface_exists($element)) {
                throw new InvalidCoversTargetException(
                    sprintf(
                        'Trying to @cover interface "%s".',
                        $element
                    )
                );
            }

            try {
                $codeUnits = $codeUnits->mergeWith($mapper->stringToCodeUnits($element));
            } catch (InvalidCodeUnitException $e) {
                throw new InvalidCoversTargetException(
                    sprintf(
                        '"@%s %s" is invalid',
                        $mode,
                        $element
                    ),
                    (int) $e->getCode(),
                    $e
                );
            }
        }

        return $mapper->codeUnitsToSourceLines($codeUnits);
    }

    private function shouldCoversAnnotationBeUsed(array $annotations): bool
    {
        if (isset($annotations['method']['coversNothing'])) {
            return false;
        }

        if (isset($annotations['method']['covers'])) {
            return true;
        }

        if (isset($annotations['class']['coversNothing'])) {
            return false;
        }

        return true;
    }
}
