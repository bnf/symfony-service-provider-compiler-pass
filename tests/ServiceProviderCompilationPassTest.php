<?php

namespace Bnf\SymfonyServiceProviderCompilerPass\Tests;

use Bnf\SymfonyServiceProviderCompilerPass\Registry;
use Bnf\SymfonyServiceProviderCompilerPass\ServiceProviderCompilationPass;
use Bnf\SymfonyServiceProviderCompilerPass\Tests\Fixtures\TestServiceProvider;
use Bnf\SymfonyServiceProviderCompilerPass\Tests\Fixtures\TestServiceProviderOverride;
use Bnf\SymfonyServiceProviderCompilerPass\Tests\Fixtures\TestServiceProviderOverride2;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ServiceProviderCompilationPassTest extends TestCase
{
    protected function getContainer(array $lazyArray)
    {
        static $id = 0;
        $registry = new Registry($lazyArray);
        $registryServiceName = 'service_provider_registry_' . ++$id;

        $container = new ContainerBuilder();
        $logger = new Definition(NullLogger::class);
        $logger->setPublic(true);
        $container->setDefinition('logger', $logger);

        $container->addCompilerPass(new ServiceProviderCompilationPass($registry, $registryServiceName));
        $container->compile();
        $container->set($registryServiceName, $registry);

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
        $registry = new Registry([
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
        $registryServiceName = 'service_provider_registry_test';
        $container->addCompilerPass(new ServiceProviderCompilationPass($registry, $registryServiceName));
        $container->compile();
    }
}
