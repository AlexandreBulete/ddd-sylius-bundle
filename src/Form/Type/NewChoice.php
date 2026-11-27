<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Form\Type;

/**
 * What a creatable choice field holds when the pick is not in its list yet:
 * the text typed, or the fields of the creation dialog. The use case behind
 * the form creates it.
 */
final readonly class NewChoice
{
    /**
     * @param array<string, string> $values field => value, `name` for a plain typed text
     */
    public function __construct(
        public array $values,
    ) {
    }

    public static function named(string $name): self
    {
        return new self(['name' => $name]);
    }

    public function get(string $field): ?string
    {
        $value = trim($this->values[$field] ?? '');

        return '' === $value ? null : $value;
    }

    /**
     * The values, in order, as one line: what the field shows.
     */
    public function label(): string
    {
        return trim(implode(' ', array_map('trim', $this->values)));
    }
}
