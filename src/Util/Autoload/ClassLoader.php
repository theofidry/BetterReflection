<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use function array_key_exists;
use function class_exists;
use function interface_exists;
use function spl_autoload_register;
use function trait_exists;

final class ClassLoader
{
    /**
     * @var ReflectionClass[]
     */
    private $reflections = [];

    /**
     * @var LoaderMethodInterface
     */
    private $loaderMethod;

    public function __construct(LoaderMethodInterface $loaderMethod)
    {
        $this->loaderMethod = $loaderMethod;
        spl_autoload_register($this, true, true);
    }

    /**
     * @throws \Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded
     * @throws \Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered
     */
    public function addClass(ReflectionClass $reflectionClass) : void
    {
        if (array_key_exists($reflectionClass->getName(), $this->reflections)) {
            throw Exception\ClassAlreadyRegistered::fromReflectionClass($reflectionClass);
        }

        if (class_exists($reflectionClass->getName(), false)) {
            throw Exception\ClassAlreadyLoaded::fromReflectionClass($reflectionClass);
        }

        $this->reflections[$reflectionClass->getName()] = $reflectionClass;
    }

    /**
     * @throws \Roave\BetterReflection\Util\Autoload\Exception\FailedToLoadClass
     */
    public function __invoke(string $classToLoad) : bool
    {
        if (! array_key_exists($classToLoad, $this->reflections)) {
            return false;
        }

        $this->loaderMethod->__invoke($this->reflections[$classToLoad]);

        if (! (class_exists($classToLoad, false)
            || interface_exists($classToLoad, false)
            || trait_exists($classToLoad, false))) {
            throw Exception\FailedToLoadClass::fromClassName($classToLoad);
        }

        return true;
    }
}
