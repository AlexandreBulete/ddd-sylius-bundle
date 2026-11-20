<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Tests\Grid;

use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

final class GridPageResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{list<int>, array<string, string>, int}>
     */
    public static function pageSizes(): iterable
    {
        yield 'no limit asked: first declared limit' => [[20, 50], [], 20];
        yield 'no limit declared nor asked: default' => [[], [], 10];
        yield 'asked limit rounded up to a declared one' => [[10, 25, 50], ['limit' => '30'], 50];
        yield 'asked limit above every declared one: largest' => [[10, 25], ['limit' => '500'], 25];
        yield 'no limit declared: asked limit' => [[], ['limit' => '42'], 42];
        yield 'garbage limit: first declared limit' => [[15, 30], ['limit' => 'abc'], 15];
    }

    /**
     * @param list<int>             $limits
     * @param array<string, string> $query
     */
    #[Test]
    #[DataProvider('pageSizes')]
    public function itKeepsThePageSizeWithinTheGridLimits(array $limits, array $query, int $expected): void
    {
        $grid = Grid::fromCodeAndDriverConfiguration('posts', 'doctrine/orm', []);
        $grid->setLimits($limits);

        self::assertSame($expected, GridPageResolver::getItemsPerPage($grid, new Parameters($query)));
    }

    #[Test]
    public function thePageIsNeverBelowOne(): void
    {
        $grid = Grid::fromCodeAndDriverConfiguration('posts', 'doctrine/orm', []);

        self::assertSame(1, GridPageResolver::getCurrentPage($grid, new Parameters(['page' => '-3'])));
        self::assertSame(4, GridPageResolver::getCurrentPage($grid, new Parameters(['page' => '4'])));
        self::assertSame(1, GridPageResolver::getCurrentPage($grid, new Parameters([])));
    }
}
