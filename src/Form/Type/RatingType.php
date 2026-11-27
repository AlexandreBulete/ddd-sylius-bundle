<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A rating as stars, picked with the mouse or the arrow keys (a radio group).
 * The value is a whole number of steps: stars, or half stars with `half`
 * (0 to 10 for 5 stars). Not required: it can be cleared.
 */
final class RatingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'max' => 5,
            'half' => false,
            'expanded' => true,
            'multiple' => false,
            'required' => false,
            'placeholder' => false,
            'choice_translation_domain' => false,
            'choices' => static function (Options $options): array {
                /** @var int $max */
                $max = $options['max'];
                $steps = $options['half'] === true ? $max * 2 : $max;
                $choices = [];
                for ($value = $steps; $value >= 1; --$value) {
                    $choices[(string) $value] = $value;
                }

                return $choices;
            },
        ]);
        $resolver->setAllowedTypes('max', 'int');
        $resolver->setAllowedTypes('half', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['max'] = $options['max'];
        $view->vars['half'] = $options['half'];
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ddd_rating';
    }
}
