<?php

namespace Bnf\Interop\ServiceProviderBridgeBundle\Tests\Fixtures;

use Interop\Container\ServiceProviderInterface;

class TestRegistryServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            'serviceA' => function () {
                return new \stdClass();
            },
            'param' => function () {
                return 42;
            },
        ];
    }

    public function getExtensions()
    {
        return [
            'serviceB' => function () {
                return new \stdClass();
            },
        ];
    }
}
