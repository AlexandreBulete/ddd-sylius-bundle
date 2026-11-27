<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle;

use Symfony\Component\AssetMapper\AssetMapper;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DddSyliusBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('menu')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('groups')
                            ->info('Headings of the back-office menu, in order: each lists the names of the top-level entries it holds. Empty: the menu as contributed.')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('label')->isRequired()->cannotBeEmpty()->end()
                                    ->arrayNode('items')->scalarPrototype()->end()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('other_label')
                            ->info('The heading of the entries no group lists.')
                            ->defaultValue('ddd_sylius.menu.other')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<mixed, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array{groups: array<string, array{label: string, items: list<string>}>, other_label: string} $menu */
        $menu = $config['menu'];
        $container->parameters()
            ->set('ddd_sylius.menu.groups', $menu['groups'])
            ->set('ddd_sylius.menu.other_label', $menu['other_label']);
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

        // The back office's look: cards over two columns (SectionExtension),
        // the record's name and status in the title, "view on the site",
        // the bundle's stylesheet and script.
        foreach (['create', 'update'] as $page) {
            $container->extension('sylius_twig_hooks', [
                'hooks' => [
                    'sylius_admin.common.'.$page.'.content.form.sections' => [
                        'general' => ['enabled' => false],
                        'ddd_sections' => ['template' => '@DddSylius/admin/form/sections.html.twig', 'priority' => 100],
                    ],
                ],
            ]);
        }
        $container->extension('sylius_twig_hooks', [
            'hooks' => [
                'sylius_admin.common.update.content.header.title_block.actions' => [
                    'ddd_public_link' => ['template' => '@DddSylius/admin/header/public_link.html.twig', 'priority' => 200],
                ],
                // The menu with its headings and badges (MenuGrouper).
                'sylius_admin.common.component.sidebar' => [
                    'menu' => ['enabled' => false],
                    'ddd_menu' => ['template' => '@DddSylius/admin/sidebar/menu.html.twig', 'priority' => -100],
                ],
                'sylius_admin.base#stylesheets' => [
                    'ddd_styles' => ['template' => '@DddSylius/admin/layout/stylesheets.html.twig', 'priority' => -100],
                ],
                'sylius_admin.base#javascripts' => [
                    'ddd_scripts' => ['template' => '@DddSylius/admin/layout/javascripts.html.twig', 'priority' => -100],
                ],
            ],
        ]);
        if (class_exists(AssetMapper::class)) {
            $container->extension('framework', [
                'asset_mapper' => ['paths' => [$this->getPath().'/assets' => '@alexandrebulete/ddd-sylius-bundle']],
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

