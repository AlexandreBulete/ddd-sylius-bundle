<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Tests\Admin;

use AlexandreBulete\DddSyliusBundle\Admin\Menu\ResourcePageVoter;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ResourcePageVoterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?bool}>
     */
    public static function pages(): iterable
    {
        yield 'creation' => ['app_admin_role_create', true];
        yield 'edition' => ['app_admin_role_update', true];
        yield 'detail' => ['app_admin_role_show', true];
        yield 'the list itself is left to the route voter' => ['app_admin_role_index', null];
        yield 'another resource' => ['app_admin_user_update', null];
        yield 'a nested resource sharing the prefix' => ['app_admin_role_permission_create', null];
    }

    #[Test]
    #[DataProvider('pages')]
    public function aResourceEntryStaysCurrentOnItsOtherPages(string $current, ?bool $expected): void
    {
        $item = (new MenuFactory())->createItem('roles', ['extras' => ['routes' => [['route' => 'app_admin_role_index']]]]);

        self::assertSame($expected, $this->voter($current)->matchItem($item));
    }

    #[Test]
    public function anEntryWithoutRouteIsNotDecided(): void
    {
        self::assertNull($this->voter('app_admin_role_create')->matchItem((new MenuFactory())->createItem('iam')));
    }

    private function voter(string $route): ResourcePageVoter
    {
        $stack = new RequestStack();
        $stack->push(new Request(attributes: ['_route' => $route]));

        return new ResourcePageVoter($stack);
    }
}
