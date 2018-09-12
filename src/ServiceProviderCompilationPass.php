<?php


namespace Bnf\Interop\ServiceProviderBridgeBundle;


use Interop\Container\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ServiceProviderCompilationPass implements CompilerPassInterface
{
    /**
     * @var int
     */
    private $registryId;

    /**
     * @var RegistryProviderInterface
     */
    private $registryProvider;

    /**
     * @param int $registryId
     * @param RegistryProviderInterface $registryProvider
     */
    public function __construct(int $registryId, RegistryProviderInterface $registryProvider)
    {
        $this->registryId = $registryId;
        $this->registryProvider = $registryProvider;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // Now, let's store the registry in the container (an empty version of it... it will be dynamically added at runtime):
        $this->registerRegistry($container);

        $registry = $this->registryProvider->getRegistry($container);

        // Note: in the 'boot' method of a bundle, the container is available.
        // We use that to push the lazy array in the container.
        // The lazy array can be used by the registry that is also part of the container.
        // The registry can itself be used by a factory that creates services!

        foreach ($registry as $serviceProviderKey => $serviceProvider) {
            $this->registerFactories($serviceProviderKey, $serviceProvider, $container);
        }

        foreach ($registry as $serviceProviderKey => $serviceProvider) {
            $this->registerExtensions($serviceProviderKey, $serviceProvider, $container);
        }
    }


    private function registerRegistry(ContainerBuilder $container)
    {
        $definition = new Definition(Registry::class);
        $definition->setSynthetic(true);
        $definition->setPublic(true);

        $container->setDefinition('service_provider_registry_'.$this->registryId, $definition);
    }

    private function registerFactories($serviceProviderKey, ServiceProviderInterface $serviceProvider, ContainerBuilder $container) {
        $serviceFactories = $serviceProvider->getFactories();

        foreach ($serviceFactories as $serviceName => $callable) {
            $this->registerService($serviceName, $serviceProviderKey, $callable, $container);
        }
    }

    private function registerExtensions($serviceProviderKey, ServiceProviderInterface $serviceProvider, ContainerBuilder $container) {
        $serviceFactories = $serviceProvider->getExtensions();

        foreach ($serviceFactories as $serviceName => $callable) {
            $this->extendService($serviceName, $serviceProviderKey, $callable, $container);
        }
    }

    private function registerService($serviceName, $serviceProviderKey, $callable, ContainerBuilder $container) {
        $this->addServiceDefinitionFromCallable($serviceName, $serviceProviderKey, $callable, $container);
    }

    private function extendService($serviceName, $serviceProviderKey, $callable, ContainerBuilder $container) {
        $this->addServiceDefinitionFromCallable($serviceName, $serviceProviderKey, $callable, $container, 'extendService');
    }

    private function getDecoratedServiceName($serviceName, ContainerBuilder $container) {
        $counter = 1;
        while ($container->has($serviceName.'_decorated_'.$counter)) {
            $counter++;
        }
        return [
            $serviceName.'_decorated_'.$counter,
            $counter === 1 ? $serviceName : $serviceName.'_decorated_'.($counter-1)
        ];
    }

    private function addServiceDefinitionFromCallable($serviceName, $serviceProviderKey, callable $callable, ContainerBuilder $container, string $method = 'createService')
    {
        $finalServiceName = $serviceName;
        $innerName = null;

        $factoryDefinition = new Definition($this->getReturnType($callable, $serviceName));
        $factoryDefinition->setPublic(true);

        if ($method === 'extendService' && $container->has($serviceName)) {
            list($finalServiceName, $previousServiceName) = $this->getDecoratedServiceName($serviceName, $container);
            $innerName = $finalServiceName . '.inner';

            $factoryDefinition->setDecoratedService($previousServiceName, $innerName);
        }

        if ((is_array($callable) && is_string($callable[0])) || is_string($callable)) {
            $factoryDefinition->setFactory($callable);
        } else {
            $factoryDefinition->setFactory([ new Reference('service_provider_registry_'.$this->registryId), $method ]);
            $factoryDefinition->addArgument($serviceProviderKey);
            $factoryDefinition->addArgument($serviceName);
        }

        $factoryDefinition->addArgument(new Reference('service_container'));
        if ($innerName !== null) {
            $factoryDefinition->addArgument(new Reference($innerName));
        }

        $container->setDefinition($finalServiceName, $factoryDefinition);
    }

    private function getReturnType(callable $callable, string $serviceName): string
    {
        return $this->getReflection($callable)->getReturnType() ?: $serviceName;
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
}
