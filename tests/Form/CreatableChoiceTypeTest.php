<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Tests\Form;

use AlexandreBulete\DddSyliusBundle\Form\Type\CreatableChoiceType;
use AlexandreBulete\DddSyliusBundle\Form\Type\NewChoice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

final class CreatableChoiceTypeTest extends TestCase
{
    private FormFactoryInterface $forms;

    protected function setUp(): void
    {
        $this->forms = Forms::createFormFactoryBuilder()->addType(new CreatableChoiceType())->getFormFactory();
    }

    #[Test]
    public function aPickFromTheListIsItsValue(): void
    {
        self::assertSame('id-2', $this->submit('id-2'));
    }

    #[Test]
    public function aTextTheListLacksIsANewEntry(): void
    {
        $new = $this->submit('Actes Sud');

        self::assertInstanceOf(NewChoice::class, $new);
        self::assertSame('Actes Sud', $new->label());
    }

    #[Test]
    public function theDialogFieldsComeBackByName(): void
    {
        $new = $this->submit('__new__:{"firstName":"Annie","lastName":"Ernaux","extra":["x"]}');

        self::assertInstanceOf(NewChoice::class, $new);
        self::assertSame('Annie', $new->get('firstName'));
        self::assertSame('Ernaux', $new->get('lastName'));
        self::assertNull($new->get('extra'));
        self::assertNull($new->get('missing'));
    }

    #[Test]
    public function nothingPickedIsNull(): void
    {
        self::assertNull($this->submit('  '));
    }

    #[Test]
    public function theViewCarriesTheChoicesAndAPendingNewEntry(): void
    {
        $form = $this->form();
        $form->submit('Gallimard');
        $view = $form->createView();

        self::assertSame(['Seuil' => 'id-1', 'Minuit' => 'id-2'], $view->vars['choices']);
        self::assertSame('Gallimard', $view->vars['new_label']);
    }

    private function submit(string $value): mixed
    {
        $form = $this->form();
        $form->submit($value);
        self::assertTrue($form->isSynchronized());

        return $form->getData();
    }

    /**
     * @return FormInterface<mixed>
     */
    private function form(): FormInterface
    {
        return $this->forms->createNamed('publisher', CreatableChoiceType::class, null, [
            'choices' => ['Seuil' => 'id-1', 'Minuit' => 'id-2'],
            'required' => false,
        ]);
    }
}
