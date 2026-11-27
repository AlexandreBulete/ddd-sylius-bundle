<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Text before or after a field, in the same box: `/reviews/` before a slug,
 * `€` or `pages` after a number. Plain text, translated.
 */
final class AffixExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TextType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['prefix' => null, 'suffix' => null]);
        $resolver->setAllowedTypes('prefix', ['null', 'string']);
        $resolver->setAllowedTypes('suffix', ['null', 'string']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['prefix'] = $options['prefix'];
        $view->vars['suffix'] = $options['suffix'];
    }
}
