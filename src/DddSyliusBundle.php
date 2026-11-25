<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DddSyliusBundle extends AbstractBundle
{
    /**
     * @param array<mixed, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../src/Resources/config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Create and update titles that a translation can phrase as a whole
        // (see templates/admin/title). Sylius' own title is switched off
        // rather than overridden: the application imports Sylius' hooks
        // after this prepend, and would win on the same template key.
        // Edit breadcrumbs that name the record instead of showing its id.
        $container->extension('sylius_twig_hooks', [
            'hooks' => [
                'sylius_admin.common.update.content.header' => [
                    'breadcrumbs' => ['enabled' => false],
                    'ddd_breadcrumbs' => ['template' => '@DddSylius/admin/breadcrumbs/update.html.twig', 'priority' => 100],
                ],
            ],
        ]);

        // The dashboard's tab title, separated like the other pages. Paths
        // configured here are searched before the bundle's own.
        $container->extension('twig', [
            'paths' => [$this->getPath().'/templates/sylius_admin_ui' => 'SyliusAdminUi'],
        ]);

        foreach (['create', 'update'] as $page) {
            $container->extension('sylius_twig_hooks', [
                'hooks' => [
                    'sylius_admin.common.'.$page.'.content.header.title_block' => [
                        'title' => ['enabled' => false],
                        'ddd_title' => ['template' => '@DddSylius/admin/title/'.$page.'.html.twig', 'priority' => 100],
                    ],
                ],
            ]);
        }

        /** @var string $projectDir */
        $projectDir = $builder->getParameter('kernel.project_dir');
        $paths = glob($projectDir . '/src/*/Infrastructure/Sylius/Resource', GLOB_ONLYDIR);

        if (false === $paths || [] === $paths) {
            return;
        }

        $container->extension('sylius_resource', [
            'mapping' => [
                'paths' => $paths,
            ],
        ]);
    }
}

