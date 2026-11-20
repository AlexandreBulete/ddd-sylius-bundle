<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Grid;

use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

/**
 * Resolves the page and page size a grid request asks for, within the limits
 * the grid declares.
 */
final class GridPageResolver
{
    private const int DEFAULT_MAX_PER_PAGE = 10;

    public static function getCurrentPage(Grid $grid, Parameters $parameters): int
    {
        $page = $parameters->has('page') ? self::toInt($parameters->get('page')) : 1;

        return max(1, $page);
    }

    public static function getItemsPerPage(Grid $grid, Parameters $parameters): int
    {
        /** @var list<int> $limits */
        $limits = array_values(array_map(self::toInt(...), $grid->getLimits()));

        return self::resolveMaxPerPage(
            $parameters->has('limit') ? self::toInt($parameters->get('limit')) : null,
            $limits,
        );
    }

    /**
     * The smallest declared limit that holds the requested size, the largest
     * one if none does: a request never escapes the grid's limits.
     *
     * @param list<int> $gridLimits
     */
    private static function resolveMaxPerPage(?int $requestLimit, array $gridLimits): int
    {
        $gridLimits = array_values(array_filter($gridLimits, static fn (int $v): bool => $v > 0));

        if (null === $requestLimit || $requestLimit < 1) {
            return $gridLimits[0] ?? self::DEFAULT_MAX_PER_PAGE;
        }

        if ([] === $gridLimits) {
            return $requestLimit;
        }

        $ceilings = array_filter($gridLimits, static fn (int $v): bool => $v >= $requestLimit);

        return [] !== $ceilings ? min($ceilings) : max($gridLimits);
    }

    private static function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
