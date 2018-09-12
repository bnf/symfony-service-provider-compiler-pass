<?php

namespace TheCodingMachine\Interop\ServiceProviderBridgeBundle\Tests\Fixtures;

use Interop\Container\ServiceProvider;
use Interop\Container\ServiceProviderInterface;

class TestStatefulServiceProvider implements ServiceProviderInterface
{
    public $foo;

    public function __construct($foo = null)
    {
        $this->foo = $foo;
    }

    public function getFactories()
    {
        return [
        ];
    }

    public function getExtensions()
    {
        return [];
    }
}
