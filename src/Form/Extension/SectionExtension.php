<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Lays a back-office form out in cards, over a main column and a side one.
 *
 * - A field joins a card with `section`; `span` sets its width in the card
 *   (`full`, `half`, `third`, `two_thirds`).
 * - The form declares its cards with `sections` (name => label, column,
 *   help, collapsed), in display order.
 * - A field may name a card the form does not declare: the card is made from
 *   the field (`section_label`, its label by default; `section_column`,
 *   `section_help`, `section_collapsed`). A package brings its own card so.
 * - Fields without a card go first, in an untitled one.
 *
 * Without any `section`, nothing changes: Sylius' single card.
 */
final class SectionExtension extends AbstractTypeExtension
{
    public const array SPANS = ['full', 'half', 'third', 'two_thirds'];
    public const array COLUMNS = ['main', 'side'];

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'sections' => [],
            'section' => null,
            'span' => 'full',
            'section_label' => null,
            'section_column' => 'main',
            'section_help' => null,
            'section_collapsed' => false,
        ]);
        $resolver->setAllowedTypes('sections', 'array');
        $resolver->setAllowedTypes('section', ['null', 'string']);
        $resolver->setAllowedValues('span', self::SPANS);
        $resolver->setAllowedTypes('section_label', ['null', 'string', 'bool']);
        $resolver->setAllowedValues('section_column', self::COLUMNS);
        $resolver->setAllowedTypes('section_help', ['null', 'string']);
        $resolver->setAllowedTypes('section_collapsed', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['section'] = $options['section'];
        $view->vars['span'] = $options['span'];
        $view->vars['section_options'] = [
            'label' => $options['section_label'],
            'column' => $options['section_column'],
            'help' => $options['section_help'],
            'collapsed' => $options['section_collapsed'],
        ];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($view->parent !== null) {
            return;
        }
        /** @var array<mixed> $declared */
        $declared = $options['sections'];
        $layout = self::layout($view, $declared);
        if ($layout !== null) {
            $view->vars['admin_layout'] = $layout;
        }
    }

    /**
     * @param array<mixed> $declared
     *
     * @return array{main: list<array<string, mixed>>, side: list<array<string, mixed>>}|null
     */
    private static function layout(FormView $view, array $declared): ?array
    {
        $sections = [];
        foreach ($declared as $name => $section) {
            if (!\is_string($name) || !\is_array($section)) {
                throw new \InvalidArgumentException('"sections" maps a name to its options (label, column, help, collapsed).');
            }
            $sections[$name] = self::section($name, $section);
        }

        $loose = [];
        $used = false;
        foreach ($view->children as $name => $child) {
            $prefixes = $child->vars['block_prefixes'] ?? [];
            if ($name === '_token' || (\is_array($prefixes) && ($prefixes[1] ?? null) === 'hidden')) {
                continue;
            }
            $section = $child->vars['section'] ?? null;
            if (!\is_string($section)) {
                $loose[] = (string) $name;
                continue;
            }
            $used = true;
            if (!isset($sections[$section])) {
                /** @var array<string, mixed> $own */
                $own = $child->vars['section_options'] ?? [];
                $sections[$section] = self::section($section, $own);
                if (($own['label'] ?? null) === null && \count($child->children) > 0) {
                    // A compound field as its own card: its label titles the card.
                    $sections[$section]['label'] = $child->vars['label'] ?? null;
                    $sections[$section]['translation_domain'] = $child->vars['translation_domain'] ?? null;
                    $sections[$section]['own'] = (string) $name;
                }
            }
            $sections[$section]['fields'][] = (string) $name;
        }

        if (!$used && $declared === []) {
            return null;
        }
        if ($loose !== []) {
            $first = self::section('', []);
            $first['fields'] = $loose;
            $sections = ['' => $first] + $sections;
        }

        $layout = ['main' => [], 'side' => []];
        foreach ($sections as $section) {
            if ($section['fields'] !== []) {
                $layout[$section['column']][] = $section;
            }
        }

        return $layout;
    }

    /**
     * @param array<mixed> $options
     *
     * @return array{name: string, label: mixed, column: 'main'|'side', help: mixed, collapsed: bool, fields: list<string>, own: ?string, translation_domain: mixed}
     */
    private static function section(string $name, array $options): array
    {
        $column = match ($options['column'] ?? 'main') {
            'main' => 'main',
            'side' => 'side',
            default => throw new \InvalidArgumentException(sprintf('Section "%s": column "main" or "side".', $name)),
        };

        return [
            'name' => $name,
            'label' => $options['label'] ?? null,
            'column' => $column,
            'help' => $options['help'] ?? null,
            'collapsed' => (bool) ($options['collapsed'] ?? false),
            'fields' => [],
            'own' => null,
            'translation_domain' => null,
        ];
    }
}
