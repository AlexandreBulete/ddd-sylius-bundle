<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Keeps a resource's menu entry current on the resource's other pages.
 *
 * An entry links to the list (`app_admin_role_index`); the creation, edition
 * and detail pages (`app_admin_role_create`, `…_update`, `…_show`) are routes
 * of the same resource, which KnpMenu's route voter does not recognise — the
 * entry, and its heading, would lose their highlight on every form.
 */
final readonly class ResourcePageVoter implements VoterInterface
{
    private const string LIST_ACTION = 'index';

    public function __construct(
        private RequestStack $requestStack,
    ) {}

    public function matchItem(ItemInterface $item): ?bool
    {
        $current = $this->requestStack->getCurrentRequest()?->attributes->get('_route');
        if (!is_string($current)) {
            return null;
        }

        foreach ($this->routesOf($item) as $route) {
            if ($route === $current || !str_ends_with($route, '_' . self::LIST_ACTION)) {
                continue;
            }

            $prefix = substr($route, 0, -strlen(self::LIST_ACTION));
            $action = substr($current, strlen($prefix));
            // One plain action of this resource only: `app_admin_book_comment_create`
            // belongs to the book comments, not to the books.
            if (str_starts_with($current, $prefix) && preg_match('/^[a-z]+$/', $action) === 1) {
                return true;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function routesOf(ItemInterface $item): array
    {
        $routes = [];
        $declared = $item->getExtra('routes', []);
        foreach (is_array($declared) ? $declared : [] as $route) {
            if (is_string($route)) {
                $routes[] = $route;
            } elseif (is_array($route) && isset($route['route']) && is_string($route['route'])) {
                $routes[] = $route['route'];
            }
        }

        return $routes;
    }
}
