<?php

declare(strict_types=1);

use AlexandreBulete\DddSyliusBundle\Admin\DomainRefusalListener;
use AlexandreBulete\DddSyliusBundle\Admin\Menu\MenuBuilder;
use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;
use AlexandreBulete\DddSyliusBundle\Grid\RequestGridProviderDecorator;
use Sylius\Resource\Grid\State\RequestGridProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(MenuBuilder::class);
    $services->set(DomainRefusalListener::class);
    $services->set(GridPageResolver::class);

    $services->set(RequestGridProviderDecorator::class)
        ->decorate(RequestGridProvider::class)
        ->args([
            service('.inner'),
            service('sylius.grid.provider')->nullOnInvalid(),
        ]);
};

