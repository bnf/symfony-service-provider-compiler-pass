<?php
namespace TheCodingMachine\Interop\ServiceProviderBridgeBundle\Tests\Fixtures;

use Psr\Container\ContainerInterface;
use Interop\Container\ServiceProviderInterface;

function myFunctionFactory()
{
    return 42;
}

class TestServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            'serviceA' => function (ContainerInterface $container): \stdClass {
                $instance = new \stdClass();
                $instance->serviceB = $container->get('serviceB');

                return $instance;
            },
            'serviceB' => [ TestServiceProvider::class, 'createServiceB' ],
            'serviceC' => function (ContainerInterface $container): \stdClass {
                return new \stdClass();
            },
            'function' => 'TheCodingMachine\\Interop\\ServiceProviderBridgeBundle\\Tests\\Fixtures\\myFunctionFactory'
        ];
    }

    public static function createServiceB(ContainerInterface $container): \stdClass
    {
        $instance = new \stdClass();
        // Test getting the database_host parameter.
        $instance->parameter = $container->get('database_host');
        return $instance;
    }

    public function getExtensions()
    {
        return [];
    }
}
