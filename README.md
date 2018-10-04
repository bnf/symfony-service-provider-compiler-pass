[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bnf/symfony-service-provider-compiler-pass/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bnf/symfony-service-provider-compiler-pass/?branch=master)
[![Build Status](https://travis-ci.org/bnf/symfony-service-provider-compiler-pass.svg?branch=master)](https://travis-ci.org/bnf/symfony-service-provider-compiler-pass)
[![Coverage Status](https://coveralls.io/repos/bnf/symfony-service-provider-compiler-pass/badge.svg?branch=master&service=github)](https://coveralls.io/github/bnf/symfony-service-provider-compiler-pass?branch=master)


# container-interop/service-provider compiler pass

Import `service-provider` as defined in `container-interop` into a Symfony dependency injection container.

*This is a fork of
[thecodingmachine/service-provider-bridge-bundle](https://github.com/thecodingmachine/service-provider-bridge-bundle)
to support Symfony 4. Credits go to David Négrier.*

## Usage

You have to declare service providers manually in the constructor of the registry.

```php
$registry = new \Bnf\SymfonyServiceProviderCompilerPass\Registry([
    new MyServiceProvide1(),
    new MyServiceProvide2()
]);
// during compilation set:
$container->addCompilerPass(new \Bnf\SymfonyServiceProviderCompilerPass\Registry($registry, 'service_provider_registry'));
$container->compile();
$container->set('service_provider_registry', $registry);
```

Alternatively, you can also pass the service provider class name. This is interesting because the service-provider registry will not instantiate the service provider unless it is needed for a service.
You can therefore improve performances of your application.

```php
$registry = new \Bnf\SymfonyServiceProviderCompilerPass\Registry([
    MyServiceProvide1::class,
    MyServiceProvide2::class
]);
// during compilation set:
$container->addCompilerPass(new \Bnf\SymfonyServiceProviderCompilerPass\Registry($registry, 'service_provider_registry'));
$container->compile();
$container->set('service_provider_registry', $registry);
```

Finally, if you need to pass parameters to the constructors of the service providers, you can do this by passing an array:

```php
$registry = new \Bnf\SymfonyServiceProviderCompilerPass\Registry([
    [ MyServiceProvide1::class, [ "param1", "param2" ] ],
    [ MyServiceProvide2::class, [ 42 ] ],
]);
// during compilation set:
$container->addCompilerPass(new \Bnf\SymfonyServiceProviderCompilerPass\Registry($registry, 'service_provider_registry'));
$container->compile();
$container->set('service_provider_registry', $registry);
```
