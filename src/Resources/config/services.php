<?php

declare(strict_types=1);

use AlexandreBulete\DddSyliusBundle\Admin\Menu\MenuBuilder;
use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(MenuBuilder::class);
    $services->set(GridPageResolver::class);
};

