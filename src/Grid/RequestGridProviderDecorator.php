<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Grid;

use Pagerfanta\Pagerfanta;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\GridAwareOperationInterface;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;
use Sylius\Component\Grid\Parameters;

/**
 * @see \Sylius\Resource\Grid\State\RequestGridProvider
 * 
 * This decorator is used to decorate the RequestGridProvider 
 * to add the ability to change the max per page based on the grid limits.
 */
final class RequestGridProviderDecorator implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $decorated,
        private ?GridProviderInterface $gridProvider = null
    ) {
    }

    public function provide(Operation $operation, Context $context): object|array|null
    {
        $gridView = $this->decorated->provide($operation, $context);
    
        if (null === $gridView) {
            return null;
        }

        $request = $context->get(RequestOption::class)?->request();
        if (null !== $request && $request->query->has('limit')) {
            $data = $gridView->getData();

            if ($data instanceof Pagerfanta && $operation instanceof GridAwareOperationInterface) {
                $gridDefinition = $this->gridProvider->get($operation->getGrid());
                $params = new Parameters($request->query->all());

                $data->setMaxPerPage(
                    GridPageResolver::getItemsPerPage($gridDefinition, $params)
                );
            }
        }
        

        return $gridView;
    }
}
