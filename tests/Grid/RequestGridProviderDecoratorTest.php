<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Tests\Grid;

use AlexandreBulete\DddSyliusBundle\Grid\RequestGridProviderDecorator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class RequestGridProviderDecoratorTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, string>, array<string, string>}>
     */
    public static function queries(): iterable
    {
        yield 'negative page' => [['page' => '-3'], ['page' => '1']];
        yield 'page zero' => [['page' => '0'], ['page' => '1']];
        yield 'garbage page' => [['page' => 'abc'], ['page' => '1']];
        yield 'limit zero' => [['limit' => '0'], ['limit' => '10']];
        yield 'limit between two declared ones' => [['limit' => '7'], ['limit' => '10']];
        yield 'limit above every declared one' => [['limit' => '500'], ['limit' => '50']];
        yield 'other parameters kept' => [['page' => '0', 'criteria' => 'x'], ['page' => '1', 'criteria' => 'x']];
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $expected
     */
    #[Test]
    #[DataProvider('queries')]
    public function itHandsTheProviderANormalisedQuery(array $query, array $expected): void
    {
        $inner = new RecordingProvider();
        $decorator = new RequestGridProviderDecorator($inner, $this->gridProvider([10, 25, 50]));

        $decorator->provide(new Index(grid: 'posts'), new Context(new RequestOption(new Request($query))));

        self::assertSame($expected, $inner->query());
    }

    #[Test]
    public function itLeavesAValidRequestUntouched(): void
    {
        $inner = new RecordingProvider();
        $decorator = new RequestGridProviderDecorator($inner, $this->gridProvider([10, 25]));
        $request = new Request(['page' => '2', 'limit' => '25']);

        $decorator->provide(new Index(grid: 'posts'), new Context(new RequestOption($request)));

        self::assertSame($request, $inner->request);
    }

    #[Test]
    public function itDoesNothingWithoutAGrid(): void
    {
        $inner = new RecordingProvider();
        $decorator = new RequestGridProviderDecorator($inner, $this->gridProvider([10]));
        $request = new Request(['page' => '-1']);

        $decorator->provide(new Index(), new Context(new RequestOption($request)));

        self::assertSame($request, $inner->request);
    }

    /**
     * @param list<int> $limits
     */
    private function gridProvider(array $limits): GridProviderInterface
    {
        $grid = Grid::fromCodeAndDriverConfiguration('posts', 'doctrine/orm', []);
        $grid->setLimits($limits);

        return new readonly class($grid) implements GridProviderInterface {
            public function __construct(private Grid $grid)
            {
            }

            public function get(string $code): Grid
            {
                return $this->grid;
            }
        };
    }
}

/**
 * @internal
 */
final class RecordingProvider implements ProviderInterface
{
    public ?Request $request = null;

    /**
     * @return object|array<array-key, mixed>|null
     */
    public function provide(Operation $operation, Context $context): object|array|null
    {
        $this->request = $context->get(RequestOption::class)?->request();

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return null === $this->request ? [] : $this->request->query->all();
    }
}
