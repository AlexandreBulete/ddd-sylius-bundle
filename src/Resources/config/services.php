<?php

declare(strict_types=1);

use AlexandreBulete\DddSyliusBundle\Admin\DomainRefusalListener;
use AlexandreBulete\DddSyliusBundle\Admin\Menu\MenuBuilder;
use AlexandreBulete\DddSyliusBundle\Admin\Twig\AdminViewExtension;
use AlexandreBulete\DddSyliusBundle\Form\Extension\AffixExtension;
use AlexandreBulete\DddSyliusBundle\Form\Extension\SectionExtension;
use AlexandreBulete\DddSyliusBundle\Form\Extension\SwitchExtension;
use AlexandreBulete\DddSyliusBundle\Form\Type\RatingType;
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
    $services->set(AdminViewExtension::class);
    $services->set(SectionExtension::class);
    $services->set(AffixExtension::class);
    $services->set(SwitchExtension::class);
    $services->set(RatingType::class);
    $services->set(DomainRefusalListener::class);
    $services->set(GridPageResolver::class);

    $services->set(RequestGridProviderDecorator::class)
        ->decorate(RequestGridProvider::class)
        ->args([
            service('.inner'),
            service('sylius.grid.provider')->nullOnInvalid(),
        ]);
};

