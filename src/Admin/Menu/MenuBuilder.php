<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Sylius\AdminUi\Knp\Menu\MenuBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsDecorator(decorates: 'sylius_admin_ui.knp.menu_builder')]
final readonly class MenuBuilder implements MenuBuilderInterface
{
    /**
     * @param iterable<MenuContributorInterface> $contributors
     */
    public function __construct(
        private FactoryInterface $factory,
        #[AutowireIterator('app.menu_contributor')]
        private iterable $contributors,
    ) {
    }

    public function createMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu
            ->addChild('dashboard', [
                'route' => 'sylius_admin_ui_dashboard',
            ])
            ->setLabel('sylius.ui.dashboard')
            ->setLabelAttribute('icon', 'tabler:dashboard');

        // Bounded Contexts loop (eg: Post, User, Comment, Course, etc.)
        foreach ($this->contributors as $contributor) {
            $contributor->contribute($menu);
        }

        return $menu;
    }
}

