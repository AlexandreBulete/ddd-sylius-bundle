<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin\Twig;

use AlexandreBulete\DddSyliusBundle\Admin\View\StatusBadge;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `admin_status(badge)`, `admin_stars(value)`: the pill and the stars,
 * anywhere in the back office.
 */
final class AdminViewExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_status', $this->status(...), ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('admin_stars', $this->stars(...), ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Read-only stars: `admin_stars(3.5)`, `admin_stars(4, 10)`.
     */
    public function stars(Environment $twig, int|float|null $value, int $max = 5): string
    {
        return $value === null ? '' : $twig->render('@DddSylius/admin/stars.html.twig', ['value' => max(0, min($max, $value)), 'max' => $max]);
    }

    public function status(Environment $twig, ?StatusBadge $badge): string
    {
        return $badge === null ? '' : $twig->render('@DddSylius/admin/status.html.twig', ['badge' => $badge]);
    }
}
