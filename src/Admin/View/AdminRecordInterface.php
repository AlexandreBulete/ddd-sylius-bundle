<?php

declare(strict_types=1);

namespace AlexandreBulete\DddSyliusBundle\Admin\View;

/**
 * A resource that says more about itself on its edit page: its name as the
 * page title, its state as a pill beside it, and where the public sees it.
 * Each method may return null to keep Sylius' default.
 */
interface AdminRecordInterface
{
    public function adminLabel(): ?string;

    public function adminStatus(): ?StatusBadge;

    public function adminPublicUrl(): ?string;
}
