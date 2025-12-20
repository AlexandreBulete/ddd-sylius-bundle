<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin\Menu;

use Knp\Menu\ItemInterface;

interface MenuContributorInterface
{
    public function contribute(ItemInterface $menu): void;
}

