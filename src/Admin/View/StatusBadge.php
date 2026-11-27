<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin\View;

/**
 * A short state shown as a pill: "Published", "Draft", "Scheduled"… The label
 * is a translation key (or plain text); the tone picks the colour.
 */
final readonly class StatusBadge
{
    public const string SUCCESS = 'success';
    public const string WARNING = 'warning';
    public const string INFO = 'info';
    public const string DANGER = 'danger';
    public const string NEUTRAL = 'neutral';

    private const array TONES = [self::SUCCESS, self::WARNING, self::INFO, self::DANGER, self::NEUTRAL];

    public function __construct(
        public string $label,
        public string $tone = self::NEUTRAL,
    ) {
        if (!\in_array($tone, self::TONES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown status tone "%s", one of: %s.', $tone, implode(', ', self::TONES)));
        }
    }

    public static function success(string $label): self
    {
        return new self($label, self::SUCCESS);
    }

    public static function warning(string $label): self
    {
        return new self($label, self::WARNING);
    }

    public static function info(string $label): self
    {
        return new self($label, self::INFO);
    }

    public static function danger(string $label): self
    {
        return new self($label, self::DANGER);
    }

    public static function neutral(string $label): self
    {
        return new self($label, self::NEUTRAL);
    }
}
