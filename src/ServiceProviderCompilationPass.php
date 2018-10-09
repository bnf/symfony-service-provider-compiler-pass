<?php

namespace Bnf\SymfonyServiceProviderCompilerPass;

use Interop\Container\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ServiceProviderCompilationPass implements CompilerPassInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var string
     */
    private $registryServiceName;

    /**
     * @param Registry $registry
     * @param string $registryServiceName
     */
    public function __construct(Registry $registry, string $registryServiceName = 'service_provider_registry')
    {
        $this->registry = $registry;
        $this->registryServiceName = $registryServiceName;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // Now, let's store the registry in the container (an empty version of it... it has to be dynamically added at runtime):
        $this->registerRegistry($container);

        foreach ($this->registry as $serviceProviderKey => $serviceProvider) {
            $this->registerFactories($serviceProviderKey, $serviceProvider, $container);
        }

        foreach ($this->registry as $serviceProviderKey => $serviceProvider) {
            $this->registerExtensions($serviceProviderKey, $serviceProvider, $container);
        }
    }

    private function registerRegistry(ContainerBuilder $container)
    {
        $definition = new Definition(Registry::class);
        $definition->setSynthetic(true);
        $definition->setPublic(true);

        $container->setDefinition($this->registryServiceName, $definition);
    }

    private function registerFactories($serviceProviderKey, ServiceProviderInterface $serviceProvider, ContainerBuilder $container)
    {
        $serviceFactories = $serviceProvider->getFactories();

        foreach ($serviceFactories as $serviceName => $callable) {
            $this->registerService($serviceName, $serviceProviderKey, $callable, $container);
        }
    }

    private function registerExtensions($serviceProviderKey, ServiceProviderInterface $serviceProvider, ContainerBuilder $container)
    {
        $serviceFactories = $serviceProvider->getExtensions();

        foreach ($serviceFactories as $serviceName => $callable) {
            $this->extendService($serviceName, $serviceProviderKey, $callable, $container);
        }
    }

    private function registerService($serviceName, $serviceProviderKey, $callable, ContainerBuilder $container)
    {
        if (!$container->has($serviceName)) {
            // Create a new definition
            $factoryDefinition = new Definition();
            $container->setDefinition($serviceName, $factoryDefinition);
        } else {
            // Merge into an existing definition to keep possible addMethodCall/properties configurations
            // (which act like a service extension)
            // Retrieve the existing factory and overwrite it.
            $factoryDefinition = $container->findDefinition($serviceName);
        }

        $className = $this->getReturnType($this->getReflection($callable), $serviceName);
        $factoryDefinition->setClass($className);
        $factoryDefinition->setPublic(true);

        $staticallyCallable = $this->getStaticallyCallable($callable);
        if ($staticallyCallable !== null) {
            $factoryDefinition->setFactory($staticallyCallable);
            $factoryDefinition->setArguments([
                new Reference('service_container')
            ]);
        } else {
            $factoryDefinition->setFactory([ new Reference($this->registryServiceName), 'createService' ]);
            $factoryDefinition->setArguments([
                $serviceProviderKey,
                $serviceName,
                new Reference('service_container')
            ]);
        }
    }

    private function extendService($serviceName, $serviceProviderKey, $callable, ContainerBuilder $container)
    {
        $finalServiceName = $serviceName;
        $innerName = null;

        $reflection = $this->getReflection($callable);
        $className = $this->getReturnType($reflection, $serviceName);

        $factoryDefinition = new Definition($className);
        $factoryDefinition->setClass($className);
        $factoryDefinition->setPublic(true);

        if ($container->has($serviceName)) {
            list($finalServiceName, $previousServiceName) = $this->getDecoratedServiceName($serviceName, $container);
            $innerName = $finalServiceName . '.inner';

            $factoryDefinition->setDecoratedService($previousServiceName, $innerName);
        } elseif ($reflection->getNumberOfRequiredParameters() > 1) {
            throw new \Exception('A registered extension for the service "' . $serviceName . '" requires the service to be available, which is missing.');
        }

        $staticallyCallable = $this->getStaticallyCallable($callable);
        if ($staticallyCallable !== null) {
            $factoryDefinition->setFactory($staticallyCallable);
            $factoryDefinition->setArguments([
                new Reference('service_container')
            ]);
        } else {
            $factoryDefinition->setFactory([ new Reference($this->registryServiceName), 'extendService' ]);
            $factoryDefinition->setArguments([
                $serviceProviderKey,
                $serviceName,
                new Reference('service_container')
            ]);
        }

        if ($innerName !== null) {
            $factoryDefinition->addArgument(new Reference($innerName));
        }

        $container->setDefinition($finalServiceName, $factoryDefinition);
    }

    /**
     * @param callable $callable
     * @return array|string|null
     */
    private function getStaticallyCallable(callable $callable)
    {
        if (is_string($callable)) {
            return $callable;
        }
        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            return $callable;
        }

        return null;
    }

    private function getReturnType(\ReflectionFunctionAbstract $reflection, string $serviceName): string
    {
        return $reflection->getReturnType() ?: $serviceName;
    }

    private function getReflection(callable $callable): \ReflectionFunctionAbstract
    {
        if (is_array($callable) && count($callable) === 2) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }
        if (is_object($callable) && !$callable instanceof \Closure) {
            return new \ReflectionMethod($callable, '__invoke');
        }

        return new \ReflectionFunction($callable);
    }

    private function getDecoratedServiceName($serviceName, ContainerBuilder $container)
    {
        $counter = 1;
        while ($container->has($serviceName . '_decorated_' . $counter)) {
            $counter++;
        }
        return [
            $serviceName . '_decorated_' . $counter,
            $counter === 1 ? $serviceName : $serviceName . '_decorated_' . ($counter-1)
        ];
    }
}
