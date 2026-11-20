<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Grid;

use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\GridAwareOperationInterface;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;

/**
 * Normalises `?page=` and `?limit=` before the grid provider reads them.
 *
 * The decorated provider hands the raw query to Pagerfanta, which rejects a
 * page or a page size below 1 (a 404) and lets `?limit=` escape the limits the
 * grid declares. The provider therefore receives a copy of the request whose
 * page is at least 1 and whose page size is one of the grid's limits. A page
 * beyond the last one is still a 404: that page does not exist.
 *
 * @see \Sylius\Resource\Grid\State\RequestGridProvider
 */
final readonly class RequestGridProviderDecorator implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $decorated,
        private ?GridProviderInterface $gridProvider = null,
    ) {
    }

    /**
     * @return object|array<array-key, mixed>|null
     */
    public function provide(Operation $operation, Context $context): object|array|null
    {
        return $this->decorated->provide($operation, $this->normalised($operation, $context));
    }

    private function normalised(Operation $operation, Context $context): Context
    {
        $request = $context->get(RequestOption::class)?->request();
        $grid = $operation instanceof GridAwareOperationInterface ? $operation->getGrid() : null;

        if (null === $this->gridProvider || null === $grid || null === $request) {
            return $context;
        }

        $query = $request->query->all();
        $definition = $this->gridProvider->get($grid);
        $parameters = new Parameters($query);

        $normalised = $query;
        if (\array_key_exists('page', $query)) {
            $normalised['page'] = (string) GridPageResolver::getCurrentPage($definition, $parameters);
        }
        if (\array_key_exists('limit', $query)) {
            $normalised['limit'] = (string) GridPageResolver::getItemsPerPage($definition, $parameters);
        }

        if ($normalised === $query) {
            return $context;
        }

        return $context->with(new RequestOption($request->duplicate($normalised)));
    }
}
