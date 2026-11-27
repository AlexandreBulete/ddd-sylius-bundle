<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A searchable select whose list may grow from the field itself: pick an
 * entry, or type one that is not there yet. The data is the picked value, a
 * {@see NewChoice} for a new entry, or null.
 *
 * With `create_fields`, a new entry opens a dialog with those fields (an
 * author's first and last names) instead of taking the typed text whole.
 */
final class CreatableChoiceType extends AbstractType
{
    private const string NEW_PREFIX = '__new__:';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<string, string> $choices */
        $choices = $options['choices'];
        $values = array_map('strval', array_values($choices));

        $builder->addModelTransformer(new CallbackTransformer(
            static function (mixed $data): string {
                if ($data instanceof NewChoice) {
                    return self::NEW_PREFIX.json_encode($data->values, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
                }

                return \is_scalar($data) ? (string) $data : '';
            },
            static function (mixed $value) use ($values): string|NewChoice|null {
                $value = \is_string($value) ? trim($value) : '';
                if ('' === $value) {
                    return null;
                }
                if (\in_array($value, $values, true)) {
                    return $value;
                }
                if (str_starts_with($value, self::NEW_PREFIX)) {
                    $fields = json_decode(substr($value, \strlen(self::NEW_PREFIX)), true);
                    if (\is_array($fields)) {
                        return new NewChoice(array_map(static fn (mixed $v): string => \is_scalar($v) ? (string) $v : '', array_filter($fields, 'is_string', \ARRAY_FILTER_USE_KEY)));
                    }
                }

                return NewChoice::named($value);
            },
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['choices'] = $options['choices'];
        $view->vars['create_fields'] = $options['create_fields'];
        $view->vars['create_title'] = $options['create_title'];
        $view->vars['placeholder'] = $options['placeholder'];
        $data = $form->getData();
        $view->vars['new_label'] = $data instanceof NewChoice ? $data->label() : null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [],
            'create_fields' => null,
            'create_title' => null,
            'placeholder' => '',
            'compound' => false,
        ]);
        $resolver->setAllowedTypes('choices', 'array');
        $resolver->setAllowedTypes('create_fields', ['null', 'array']);
        $resolver->setAllowedTypes('placeholder', 'string');
        $resolver->setAllowedTypes('create_title', ['null', 'string']);
        $resolver->setInfo('choices', 'label => value, as ChoiceType.');
        $resolver->setInfo('create_fields', 'field => label (translation key): a dialog to fill for a new entry, instead of the typed text.');
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ddd_creatable_choice';
    }
}
