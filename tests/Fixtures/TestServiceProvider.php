<?php
namespace Bnf\SymfonyServiceProviderCompilerPass\Tests\Fixtures;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

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
            'serviceD' => new class {
                public function __invoke(ContainerInterface $container): \stdClass
                {
                    return new \stdClass();
                }
            },
            'function' => 'Bnf\\SymfonyServiceProviderCompilerPass\\Tests\\Fixtures\\myFunctionFactory'
        ];
    }

    public static function createServiceB(ContainerInterface $container): \stdClass
    {
        $instance = new \stdClass();
        $instance->parameter = 'localhost';
        return $instance;
    }

    public function getExtensions()
    {
        return [];
    }
}
