<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin\Menu;

use Knp\Menu\ItemInterface;

/**
 * Shapes the menu the contributors built, without them knowing: the
 * application names groups of top-level entries, each under a heading; what
 * no group lists goes under a last one. An entry with a single child becomes
 * a plain link, and an entry shows the sum of its children's badges.
 */
final readonly class MenuGrouper
{
    public const string HEADING = 'ddd_heading';
    public const string BADGE = 'badge';

    /**
     * @param array<string, array{label: string, items: list<string>}> $groups
     * @param list<string>                                              $pinned entries kept on top, above the groups
     */
    public function __construct(
        private array $groups = [],
        private string $otherLabel = 'ddd_sylius.menu.other',
        private array $pinned = ['dashboard'],
    ) {
    }

    public function shape(ItemInterface $menu): void
    {
        foreach ($menu->getChildren() as $item) {
            $this->flatten($item);
            $this->sumBadges($item);
        }
        if ([] === $this->groups) {
            return;
        }

        $present = array_keys($menu->getChildren());
        $order = array_values(array_intersect($this->pinned, $present));
        $placed = $order;
        foreach ($this->groups as $key => $group) {
            $items = array_values(array_diff(array_intersect($group['items'], $present), $placed));
            if ([] === $items) {
                continue;
            }
            $order[] = $this->heading($menu, 'ddd_group_'.$key, $group['label']);
            array_push($order, ...$items);
            array_push($placed, ...$items);
        }
        $others = array_values(array_diff($present, $placed));
        if ([] !== $others) {
            $order[] = $this->heading($menu, 'ddd_group_other', $this->otherLabel);
            array_push($order, ...$others);
        }

        $menu->reorderChildren($order);
    }

    private function heading(ItemInterface $menu, string $name, string $label): string
    {
        $menu->addChild($name)->setLabel($label)->setExtra(self::HEADING, true);

        return $name;
    }

    /**
     * A drop-down holding one link is a click for nothing: the entry links
     * there itself, with its own label and icon.
     */
    private function flatten(ItemInterface $item): void
    {
        if (1 !== \count($item->getChildren()) || null !== $item->getUri()) {
            return;
        }
        $child = $item->getChildren()[array_key_first($item->getChildren())];
        if ($child->hasChildren()) {
            return;
        }
        $item->setUri($child->getUri());
        foreach (['routes', self::BADGE] as $extra) {
            if (null !== $child->getExtra($extra)) {
                $item->setExtra($extra, $child->getExtra($extra));
            }
        }
        $item->removeChild($child);
    }

    private function sumBadges(ItemInterface $item): void
    {
        if (null !== $item->getExtra(self::BADGE) || !$item->hasChildren()) {
            return;
        }
        $sum = 0;
        foreach ($item->getChildren() as $child) {
            $badge = $child->getExtra(self::BADGE);
            $sum += is_numeric($badge) ? (int) $badge : 0;
        }
        if ($sum > 0) {
            $item->setExtra(self::BADGE, $sum);
        }
    }
}
