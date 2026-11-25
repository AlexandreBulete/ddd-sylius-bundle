# DDD Sylius Bundle

Sylius Stack Bundle for DDD Foundation. Provides Admin UI menu builder, Grid utilities, and Resource integration.

## Installation

```bash
composer require alexandrebulete/ddd-sylius-bundle
```

## Configuration

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    AlexandreBulete\DddSyliusBundle\DddSyliusBundle::class => ['all' => true],
];
```

## Structure

```
src/
├── DddSyliusBundle.php
├── Admin/
│   └── Menu/
│       ├── MenuBuilder.php
│       └── MenuContributorInterface.php
├── Grid/
│   └── GridPageResolver.php
└── Resources/
    └── config/
        ├── services.php
        └── sylius_resource.yaml
```

## Features

### Menu Builder

The bundle provides a modular menu builder for Sylius Admin UI. Each bounded context can contribute menu items.

#### Create a Menu Contributor

```php
use AlexandreBulete\DddSyliusBundle\Admin\Menu\MenuContributorInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.menu_contributor')]
final readonly class PostMenuContributor implements MenuContributorInterface
{
    public function contribute(ItemInterface $menu): void
    {
        $menu
            ->addChild('posts', [
                'route' => 'app_admin_post_index',
            ])
            ->setLabel('Posts')
            ->setLabelAttribute('icon', 'tabler:article');
    }
}
```

### Grid Page Resolver

Utility for resolving pagination parameters from Grid and Request:

```php
use AlexandreBulete\DddSyliusBundle\Grid\GridPageResolver;

$page = GridPageResolver::getCurrentPage($grid, $parameters);
$itemsPerPage = GridPageResolver::getItemsPerPage($grid, $parameters);
```

### Domain refusals in the back office

An admin action the domain refuses (removing a category still in use,
deleting a published post) throws a `\DomainException` from the use case.
On a write to a Sylius `admin` route, `DomainRefusalListener` turns it into an
error flash and a redirect back to the page the click came from — instead of
an error page. Reads, other sections and other exceptions are left alone.

The flash is the translation of `domain_refusal.<snake_case class name>` in
the `flashes` domain, with the exception's public properties as parameters:

```php
final class StillInUseException extends \DomainException
{
    public function __construct(public readonly string $name, public readonly int $posts) { … }
}
```

```yaml
# translations/flashes.fr.yaml
domain_refusal:
    still_in_use_exception: "« %name% » est encore utilisé par %posts% article(s)."
```

Without a translation, the exception message is shown.

### Back-office titles

Sylius names a screen from translation keys built on the resource's
application and names: `<app>.ui.<pluralName>` (tab title, breadcrumbs,
index header) and `<app>.ui.<name>`. Its create and edit headers then read
"New" / "Edit" + the singular, which cannot agree in gender ("Nouveau page").

The bundle replaces those two headers: they read
`<app>.ui.new_<name>` and `<app>.ui.edit_<name>` when translated, and fall
back to Sylius' composition otherwise. For a `blog.post` resource:

```yaml
# translations/messages.fr.yaml
blog:
    ui:
        posts: Articles
        post: article
        new_post: Nouvel article
        edit_post: Modifier l’article
```

Prefer the typographic apostrophe (`’`): Sylius escapes the tab title twice,
and `'` shows as `&#039;`.

It also translates the grid columns Sylius leaves untranslated in French
(`sylius.ui.created_at`, `sylius.ui.updated_at`).

Two more details Sylius leaves rough:

- **Edit breadcrumbs** name the record by its first readable field — the
  hook's `rendered_field`, then `title`, `label`, `name`, `email`, `subject`,
  `code` — instead of its id (a ULID, or `default` for a one-row resource).
  A record with none of them is left out of the trail.
- **The dashboard's tab title** reads "Dashboard | <app>", separated like
  every other page.

### Sylius Resource Configuration

Configure resource mapping for your bounded contexts:

```yaml
# config/packages/sylius_resource.yaml
sylius_resource:
    mapping:
        paths:
            - '%kernel.project_dir%/src/*/Infrastructure/Sylius/Resource'
```

## Example: Full Bounded Context Setup

```
src/Post/
├── Domain/
│   └── Model/Post.php
├── Infrastructure/
│   ├── Sylius/
│   │   ├── Resource/
│   │   │   └── PostResource.php
│   │   └── Grid/
│   │       └── PostGrid.php
│   └── Symfony/
│       └── Menu/
│           └── PostMenuContributor.php
```

