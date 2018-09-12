<?php

namespace Bnf\Interop\ServiceProviderBridgeBundle\Tests;

use PHPUnit\Framework\TestCase;
use Bnf\Di\Container;
use Bnf\Interop\ServiceProviderBridgeBundle\Registry;
use Bnf\Interop\ServiceProviderBridgeBundle\Tests\Fixtures\TestRegistryServiceProvider;
use Bnf\Interop\ServiceProviderBridgeBundle\Tests\Fixtures\TestStatefulServiceProvider;

class ServiceProviderRegistryTest extends TestCase
{
    public function testRegistry()
    {
        $registry = new Registry([
            TestRegistryServiceProvider::class,
        ]);

        $this->assertEquals(new TestRegistryServiceProvider(), $registry[0]);
    }

    public function testRegistryInjectInstance()
    {
        $registry = new Registry([
            new TestRegistryServiceProvider(),
        ]);

        $this->assertEquals(new TestRegistryServiceProvider(), $registry[0]);
        $this->assertSame($registry[0], $registry[0]);
    }

    public function testRegistryArrayWithNoParams()
    {
        $registry = new Registry([
            [TestStatefulServiceProvider::class],
        ]);

        $this->assertInstanceOf(TestStatefulServiceProvider::class, $registry[0]);
        $this->assertEquals(null, $registry[0]->foo);
    }

    public function testRegistryArrayWithParams()
    {
        $registry = new Registry([
            [TestStatefulServiceProvider::class, [42]],
        ]);

        $this->assertInstanceOf(TestStatefulServiceProvider::class, $registry[0]);
        $this->assertEquals(42, $registry[0]->foo);
    }

    public function testUnset()
    {
        $registry = new Registry([
            TestRegistryServiceProvider::class,
        ]);

        $this->assertArrayHasKey(0, $registry);
        unset($registry[0]);
        $this->assertArrayNotHasKey(0, $registry);
    }

    public function testPush()
    {
        $registry = new Registry();

        $key = $registry->push(TestStatefulServiceProvider::class, 42);
        $this->assertArrayHasKey($key, $registry);
        $this->assertInstanceOf(TestStatefulServiceProvider::class, $registry[$key]);
        $this->assertEquals(42, $registry[$key]->foo);
    }

    public function testPushObject()
    {
        $registry = new Registry();

        $key = $registry->push(new TestRegistryServiceProvider());
        $this->assertArrayHasKey($key, $registry);
        $this->assertInstanceOf(TestRegistryServiceProvider::class, $registry[$key]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPushException()
    {
        $registry = new Registry();

        $registry->push(array());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOffsetGetException()
    {
        $registry = new Registry([1]);

        $registry->offsetGet(0);
    }

    /**
     * @expectedException \LogicException
     */
    public function testSet()
    {
        $registry = new Registry();

        $registry[0] = 12;
    }

    public function testGetServices()
    {
        $registry = new Registry([
            new TestRegistryServiceProvider(),
        ]);

        $services = $registry->getFactories(0);
        $this->assertArrayHasKey('serviceA', $services);

        $services2 = $registry->getFactories(0);

        $this->assertSame($services['serviceA'], $services2['serviceA']);
    }

    public function testExtendServices()
    {
        $registry = new Registry([
            new TestRegistryServiceProvider(),
        ]);

        $services = $registry->getExtensions(0);
        $this->assertArrayHasKey('serviceB', $services);

        $services2 = $registry->getExtensions(0);

        $this->assertSame($services['serviceB'], $services2['serviceB']);
    }

    public function testGetServiceFactory()
    {
        $registry = new Registry([
            new TestRegistryServiceProvider(),
        ]);

        $service = $registry->createService(0, 'param', new Container([]));

        $this->assertEquals(42, $service);
    }

    public function testGetServiceExtension()
    {
        $registry = new Registry([
            new TestRegistryServiceProvider(),
        ]);

        $service = $registry->extendService(0, 'serviceB', new Container([]), null);

        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testIterator()
    {
        $registry = new Registry([
            TestRegistryServiceProvider::class,
            TestRegistryServiceProvider::class,
        ]);

        $i = 0;
        foreach ($registry as $key => $serviceProvider) {
            $this->assertEquals($i, $key);
            $this->assertInstanceOf(TestRegistryServiceProvider::class, $serviceProvider);
            $i++;
        }
    }
}
