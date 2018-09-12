<?php
namespace Bnf\Interop\ServiceProviderBridgeBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Bnf\ServiceProvider\RegistryInterface;

/**
 * Provides a service provider registry.
 */
interface RegistryProviderInterface
{
    /**
     * @param ContainerInterface $container
     * @return RegistryInterface
     */
    public function getRegistry(ContainerInterface $container);
}