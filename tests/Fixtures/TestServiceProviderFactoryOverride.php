<?php
namespace Bnf\SymfonyServiceProviderCompilerPass\Tests\Fixtures;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class TestServiceProviderFactoryOverride implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            'serviceB' => [ self::class, 'createServiceB' ],
        ];
    }

    public static function createServiceB(ContainerInterface $container): \stdClass
    {
        $instance = new \stdClass();
        $instance->parameter = 'remotehost';
        return $instance;
    }

    public function getExtensions()
    {
        return [];
    }
}
