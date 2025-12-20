<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Grid;

use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;

class GridPageResolver
{
    public static function getCurrentPage(Grid $grid, Parameters $parameters): int
    {
        return $parameters->has('page') ? (int) $parameters->get('page') : 1;
    }

    public static function getItemsPerPage(Grid $grid, Parameters $parameters): int
    {
        return $parameters->has('limit') ? (int) $parameters->get('limit') : $grid->getLimits()[0] ?? 10;
    }
}

