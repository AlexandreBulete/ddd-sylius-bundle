<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Grid;

use Pagerfanta\Pagerfanta;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Component\Grid\View\GridViewInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\GridAwareOperationInterface;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;

/**
 * Keeps the page size a request asks for (`?limit=`) within the limits the
 * grid declares, instead of the provider's raw value.
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
        $gridView = $this->decorated->provide($operation, $context);

        if (!$gridView instanceof GridViewInterface || !$operation instanceof GridAwareOperationInterface) {
            return $gridView;
        }

        $grid = $operation->getGrid();

        $request = $context->get(RequestOption::class)?->request();
        if (null === $this->gridProvider || null === $grid || null === $request || !$request->query->has('limit')) {
            return $gridView;
        }

        $data = $gridView->getData();
        if ($data instanceof Pagerfanta) {
            $data->setMaxPerPage(GridPageResolver::getItemsPerPage(
                $this->gridProvider->get($grid),
                new Parameters($request->query->all()),
            ));
        }

        return $gridView;
    }
}
