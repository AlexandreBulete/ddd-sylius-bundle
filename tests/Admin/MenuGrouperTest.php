<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Tests\Admin;

use AlexandreBulete\DddSyliusBundle\Admin\Menu\MenuGrouper;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MenuGrouperTest extends TestCase
{
    #[Test]
    public function entriesGoUnderTheirHeadingsAndTheRestUnderTheLastOne(): void
    {
        $menu = $this->menu(['dashboard', 'iam', 'comment', 'blog', 'reading', 'outbox']);

        (new MenuGrouper([
            'content' => ['label' => 'Content', 'items' => ['reading', 'blog', 'pages']],
            'readers' => ['label' => 'Readers', 'items' => ['comment']],
            'empty' => ['label' => 'Nothing here', 'items' => ['menus']],
        ], 'Other'))->shape($menu);

        self::assertSame(
            ['dashboard', 'ddd_group_content', 'reading', 'blog', 'ddd_group_readers', 'comment', 'ddd_group_other', 'iam', 'outbox'],
            array_keys($menu->getChildren()),
        );
        self::assertTrue((bool) $menu->getChild('ddd_group_content')?->getExtra(MenuGrouper::HEADING));
        self::assertSame('Other', $menu->getChild('ddd_group_other')?->getLabel());
    }

    #[Test]
    public function withoutGroupsTheOrderStays(): void
    {
        $menu = $this->menu(['dashboard', 'iam', 'blog']);

        (new MenuGrouper())->shape($menu);

        self::assertSame(['dashboard', 'iam', 'blog'], array_keys($menu->getChildren()));
    }

    #[Test]
    public function anEntryWithOneLinkBecomesThatLink(): void
    {
        $menu = (new MenuFactory())->createItem('root');
        $pages = $menu->addChild('pages')->setLabel('Pages');
        $pages->addChild('pages_index', ['uri' => '/admin/pages'])->setExtra('routes', [['route' => 'page_index']])->setExtra('badge', 2);

        (new MenuGrouper())->shape($menu);

        self::assertFalse($pages->hasChildren());
        self::assertSame('/admin/pages', $pages->getUri());
        self::assertSame('Pages', $pages->getLabel());
        self::assertSame(2, $pages->getExtra('badge'));
    }

    #[Test]
    public function anEntryCountsWhatWaitsInItsLinks(): void
    {
        $menu = (new MenuFactory())->createItem('root');
        $contact = $menu->addChild('contact');
        $contact->addChild('messages', ['uri' => '/m'])->setExtra('badge', 3);
        $contact->addChild('spam', ['uri' => '/s'])->setExtra('badge', 2);
        $contact->addChild('settings', ['uri' => '/x']);

        (new MenuGrouper())->shape($menu);

        self::assertSame(5, $contact->getExtra('badge'));
    }

    /**
     * @param list<string> $names
     */
    private function menu(array $names): ItemInterface
    {
        $menu = (new MenuFactory())->createItem('root');
        foreach ($names as $name) {
            $menu->addChild($name, ['uri' => '/'.$name]);
        }

        return $menu;
    }
}
