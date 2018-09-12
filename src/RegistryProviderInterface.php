<?php
namespace Bnf\Interop\ServiceProviderBridgeBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service provider registry.
 */
interface RegistryProviderInterface
{
    /**
     * @param ContainerInterface $container
     * @return Registry
     */
    public function getRegistry(ContainerInterface $container): Registry;
}
