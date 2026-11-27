<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Tests\Form;

use AlexandreBulete\DddSyliusBundle\Admin\View\StatusBadge;
use AlexandreBulete\DddSyliusBundle\Form\Extension\AffixExtension;
use AlexandreBulete\DddSyliusBundle\Form\Extension\SectionExtension;
use AlexandreBulete\DddSyliusBundle\Form\Extension\SwitchExtension;
use AlexandreBulete\DddSyliusBundle\Form\Type\RatingType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

final class SectionExtensionTest extends TestCase
{
    private FormFactoryInterface $forms;

    protected function setUp(): void
    {
        $this->forms = Forms::createFormFactoryBuilder()
            ->addTypeExtensions([new SectionExtension(), new AffixExtension(), new SwitchExtension()])
            ->addType(new RatingType())
            ->getFormFactory();
    }

    #[Test]
    public function aFormWithoutSectionsKeepsSyliusLayout(): void
    {
        $view = $this->forms->createNamed('book', FormType::class)->add('title', TextType::class)->createView();

        self::assertArrayNotHasKey('admin_layout', $view->vars);
    }

    #[Test]
    public function fieldsGoToTheirCardsInDeclaredOrderLooseOnesFirst(): void
    {
        $form = $this->forms->createNamed('book', FormType::class, null, [
            'sections' => [
                'review' => ['label' => 'Review'],
                'book' => ['label' => 'Book'],
                'publication' => ['label' => 'Publication', 'column' => 'side'],
            ],
        ])
            ->add('note', TextType::class)
            ->add('title', TextType::class, ['section' => 'book'])
            ->add('author', TextType::class, ['section' => 'book', 'span' => 'half'])
            ->add('status', TextType::class, ['section' => 'publication'])
            ->add('opinion', TextType::class, ['section' => 'review'])
            ->add('extra', FormType::class, ['section' => 'seo', 'label' => 'SEO', 'section_collapsed' => true]);
        $form->get('extra')->add('title', TextType::class);

        $view = $form->createView();
        /** @var array{main: list<array{name: string, fields: list<string>, label: mixed, own: ?string, collapsed: bool}>, side: list<array{name: string, fields: list<string>}>} $layout */
        $layout = $view->vars['admin_layout'];

        self::assertSame(['', 'review', 'book', 'seo'], array_column($layout['main'], 'name'));
        self::assertSame([['note'], ['opinion'], ['title', 'author'], ['extra']], array_column($layout['main'], 'fields'));
        self::assertSame(['publication'], array_column($layout['side'], 'name'));
        self::assertSame('SEO', $layout['main'][3]['label']);
        self::assertSame('extra', $layout['main'][3]['own']);
        self::assertTrue($layout['main'][3]['collapsed']);
        self::assertSame('half', $view['author']->vars['span']);
    }

    #[Test]
    public function aRatingCountsHalfStarsAndCanBeCleared(): void
    {
        $form = $this->forms->create(RatingType::class, null, ['half' => true]);
        self::assertCount(10, $form->createView()->children);

        $form->submit('7');
        self::assertSame(7, $form->getData());

        $form = $this->forms->create(RatingType::class, 7);
        $form->submit('');
        self::assertNull($form->getData());
    }

    #[Test]
    public function affixesAndSwitchesReachTheView(): void
    {
        $view = $this->forms->createNamed('f', FormType::class)
            ->add('slug', TextType::class, ['prefix' => '/blog/'])
            ->add('open', CheckboxType::class, ['switch' => true])
            ->createView();

        self::assertSame('/blog/', $view['slug']->vars['prefix']);
        /** @var array{class: string} $attr */
        $attr = $view['open']->vars['label_attr'];
        self::assertSame('checkbox-switch', $attr['class']);
    }

    #[Test]
    public function aStatusHasAKnownTone(): void
    {
        self::assertSame('success', StatusBadge::success('Published')->tone);
        $this->expectException(\InvalidArgumentException::class);
        new StatusBadge('Odd', 'purple');
    }
}
