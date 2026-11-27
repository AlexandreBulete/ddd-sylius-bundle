<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * `switch: true`: a checkbox drawn as an on/off switch, for a setting that
 * takes effect (comments open, favourite) rather than a choice in a list.
 */
final class SwitchExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [CheckboxType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('switch', false);
        $resolver->setAllowedTypes('switch', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['switch'] !== true) {
            return;
        }
        /** @var array<string, string> $labelAttr */
        $labelAttr = $view->vars['label_attr'] ?? [];
        $labelAttr['class'] = trim(($labelAttr['class'] ?? '') . ' checkbox-switch');
        $view->vars['label_attr'] = $labelAttr;
    }
}
