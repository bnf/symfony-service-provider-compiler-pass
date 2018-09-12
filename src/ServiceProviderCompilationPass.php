<?php


namespace Bnf\Interop\ServiceProviderBridgeBundle;


use Interop\Container\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
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
        $this->addServiceDefinitionFromCallable($serviceName, $serviceProviderKey, $callable, $container, true);
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

    private function addServiceDefinitionFromCallable($serviceName, $serviceProviderKey, callable $callable, ContainerBuilder $container, bool $extension = false)
    {
        /*if ($callable instanceof DefinitionInterface) {
            // TODO: plug the definition-interop converter here!
        }*/

        $innerName = null;
        $decoratedServiceName = null;
        $factoryDefinition = new Definition($this->getReturnType($callable, $serviceName));
        $factoryDefinition->setPublic(true);
        if ($extension && $container->has($serviceName)) {
            // TODO: Use a ChildDefinition? $factoryDefinition = new ChildDefinition($serviceName);
            list($decoratedServiceName, $previousServiceName) = $this->getDecoratedServiceName($serviceName, $container);
            $innerName = $decoratedServiceName . '.inner';

            $factoryDefinition->setDecoratedService($previousServiceName, $innerName);
        }

        $containerDefinition = new Reference('service_container');

        if ((is_array($callable) && is_string($callable[0])) || is_string($callable)) {
            $factoryDefinition->setFactory($callable);
            $factoryDefinition->addArgument($containerDefinition);
        } else {
            $registryMethod = $extension ? 'extendService' : 'createService';
            $factoryDefinition->setFactory([ new Reference('service_provider_registry_'.$this->registryId), $registryMethod ]);
            $factoryDefinition->addArgument($serviceProviderKey);
            $factoryDefinition->addArgument($serviceName);
            $factoryDefinition->addArgument($containerDefinition);
        }

        if ($innerName) {
            $factoryDefinition->addArgument(new Reference($innerName));
        }

        if ($decoratedServiceName) {
            $container->setDefinition($decoratedServiceName, $factoryDefinition);
        } else {
            $container->setDefinition($serviceName, $factoryDefinition);
        }

        return $factoryDefinition;
    }

    private function getReturnType(callable $callable, string $serviceName): string
    {
        $reflection = null;
        if (is_array($callable)) {
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_object($callable)) {
            if ($callable instanceof \Closure) {
                $reflection = new \ReflectionFunction($callable);
            } else {
                $reflection = new \ReflectionMethod($callable, '__invoke');
            }
        } elseif (is_string($callable)) {
            $reflection = new \ReflectionFunction($callable);
        }

        if ($reflection && ($returnType = $reflection->getReturnType())) {
            return (string) $returnType;
        }

        // If we cannot reflect a return type, assume the serviceName is the FQCN
        return $serviceName;
    }
}
