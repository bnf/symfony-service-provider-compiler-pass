<?php

namespace Bnf\Interop\ServiceProviderBridgeBundle\Tests;

use Bnf\Interop\ServiceProviderBridgeBundle\InteropServiceProviderBridgeBundle;
use Bnf\Interop\ServiceProviderBridgeBundle\Tests\Fixtures\TestServiceProvider;
use Bnf\Interop\ServiceProviderBridgeBundle\Tests\Fixtures\TestServiceProviderOverride;
use Bnf\Interop\ServiceProviderBridgeBundle\Tests\Fixtures\TestServiceProviderOverride2;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ServiceProviderCompilationPassTest extends TestCase
{
    protected function getContainer(array $lazyArray, $useDiscovery = false)
    {
        $bundle = new InteropServiceProviderBridgeBundle($lazyArray, $useDiscovery);

        $container = new ContainerBuilder();
        $logger = new Definition(NullLogger::class);
        $logger->setPublic(true);
        $container->setDefinition('logger', $logger);

        $bundle->build($container);
        $container->compile();
        $bundle->setContainer($container);
        $bundle->boot();
        return $container;
    }

    public function testSimpleServiceProvider()
    {
        $container = $this->getContainer([
            TestServiceProvider::class
        ]);

        $serviceA = $container->get('serviceA');
        $serviceD = $container->get('serviceD');

        $this->assertInstanceOf(\stdClass::class, $serviceA);
        $this->assertInstanceOf(\stdClass::class, $serviceD);
        $this->assertEquals(42, $container->get('function'));
    }

    public function testServiceProviderOverrides()
    {
        $container = $this->getContainer([
            TestServiceProvider::class,
            TestServiceProviderOverride::class,
            TestServiceProviderOverride2::class
        ]);

        $serviceA = $container->get('serviceA');
        $serviceC = $container->get('serviceC');

        $this->assertInstanceOf(\stdClass::class, $serviceA);
        $this->assertEquals('foo', $serviceA->newProperty);
        $this->assertEquals('bar', $serviceA->newProperty2);
        $this->assertEquals('localhost', $serviceC->serviceB->parameter);
    }

    /**
     * @expectedException \TypeError
     */
    public function testExceptionForInvalidFactories()
    {
        $bundle = new InteropServiceProviderBridgeBundle([
            new class implements ServiceProviderInterface {
                public function getFactories()
                {
                    return [
                        'invalid' => 2
                    ];
                }
                public function getExtensions()
                {
                    return [];
                }
            }
        ]);
        $container = new ContainerBuilder();
        $bundle->build($container);
        $container->compile();
    }

    /**
     * @expectedException \Bnf\Interop\ServiceProviderBridgeBundle\Exception\InvalidArgumentException
     */
    /*public function testExceptionMessageIfNoPuliBundle()
    {
        $bundle = new InteropServiceProviderBridgeBundle([], true);
        $container = new ContainerBuilder();
        $bundle->build($container);
        $container->compile();
    }*/
}
