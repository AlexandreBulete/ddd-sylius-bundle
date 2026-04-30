<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DddSyliusBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../src/Resources/config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var string $projectDir */
        $projectDir = $builder->getParameter('kernel.project_dir');
        $paths = glob($projectDir . '/src/*/Infrastructure/Sylius/Resource', GLOB_ONLYDIR) ?: [];

        if ([] === $paths) {
            return;
        }

        $container->extension('sylius_resource', [
            'mapping' => [
                'paths' => $paths,
            ],
        ]);
    }
}

