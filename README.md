[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bnf/service-provider-bridge-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bnf/service-provider-bridge-bundle/?branch=master)
[![Build Status](https://travis-ci.org/bnf/service-provider-bridge-bundle.svg?branch=master)](https://travis-ci.org/bnf/service-provider-bridge-bundle)
[![Coverage Status](https://coveralls.io/repos/bnf/service-provider-bridge-bundle/badge.svg?branch=master&service=github)](https://coveralls.io/github/bnf/service-provider-bridge-bundle?branch=master)


# container-interop/service-provider bridge bundle

Import `service-provider` as defined in `container-interop` into a Symfony application.

*This is a fork of
[thecodingmachine/service-provider-bridge-bundle](https://github.com/thecodingmachine/service-provider-bridge-bundle)
to support Symfony 4. Credits go to David Négrier.*

## Usage

### Installation

Add `Bnf\Interop\ServiceProviderBridgeBundle\InteropServiceProviderBridgeBundle` in your kernel (the `app/AppKernel.php` file).

**AppKernel.php**
```php
    public function registerBundles()
    {
        $bundles = [
            ...
            new \Bnf\Interop\ServiceProviderBridgeBundle\InteropServiceProviderBridgeBundle()
        ];
        ...
    }
```


### Usage

You have to declare service providers manually in the constructor of the bundle.

**AppKernel.php**
```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            ...
            new \Bnf\Interop\ServiceProviderBridgeBundle\InteropServiceProviderBridgeBundle([
                new MyServiceProvide1(),
                new MyServiceProvide2()
            ])
        ];
        ...
    }
}
```

Alternatively, you can also pass the service provider class name. This is interesting because the service-provider bundle will not instantiate the service provider unless it is needed for a service.
You can therefore improve performances of your application.

**AppKernel.php**
```php
    public function registerBundles()
    {
        $bundles = [
            ...
            new \Bnf\Interop\ServiceProviderBridgeBundle\InteropServiceProviderBridgeBundle([
                MyServiceProvide1::class,
                MyServiceProvide2::class
            ])
        ];
        ...
    }
```

Finally, if you need to pass parameters to the constructors of the service providers, you can do this by passing an array:

**AppKernel.php**
```php
    public function registerBundles()
    {
        $bundles = [
            ...
            new \Bnf\Interop\ServiceProviderBridgeBundle\InteropServiceProviderBridgeBundle([
                [ MyServiceProvide1::class, [ "param1", "param2" ] ],
                [ MyServiceProvide2::class, [ 42 ] ],
            ])
        ];
        ...
    }
```
