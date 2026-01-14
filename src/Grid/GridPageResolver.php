<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Grid;

use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

final class GridPageResolver
{
    private const int DEFAULT_MAX_PER_PAGE = 10;

    public static function getCurrentPage(Grid $grid, Parameters $parameters): int
    {
        return $parameters->has('page') ? (int) $parameters->get('page') : 1;
    }

    public static function getItemsPerPage(Grid $grid, Parameters $parameters): int
    {
        return self::resolveMaxPerPage(
            $parameters->has('limit') ? (int) $parameters->get('limit') : null,
            $grid->getLimits(),
        );
    }

    private static function resolveMaxPerPage(int|null $requestLimit, array $gridLimits = []): int
    {
        if (null === $requestLimit) {
            $firstGridLimit = reset($gridLimits);

            return false === $firstGridLimit ? self::DEFAULT_MAX_PER_PAGE : $firstGridLimit;
        }

        if ([] !== $gridLimits) {
            sort($gridLimits);
            $validCeils = array_filter($gridLimits, fn($v) => $v >= $requestLimit && $v !== 0);
            return $validCeils ? min($validCeils) : max($gridLimits);
        }

        return $requestLimit;
    }
}