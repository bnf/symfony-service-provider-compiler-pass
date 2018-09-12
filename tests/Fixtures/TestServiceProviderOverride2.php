<?php

namespace Bnf\Interop\ServiceProviderBridgeBundle\Tests\Fixtures;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class TestServiceProviderOverride2 implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [];
    }

    public static function overrideServiceA(ContainerInterface $container, $serviceA = null): \stdClass
    {
        $serviceA->newProperty2 = 'bar';

        return $serviceA;
    }

    public function getExtensions()
    {
        return [
            'serviceA' => [self::class, 'overrideServiceA'],
            'serviceC' => function (ContainerInterface $container, \stdClass $instance): \stdClass {
                $instance->serviceB = $container->get('serviceB');

                return $instance;
            },
        ];
    }
}
