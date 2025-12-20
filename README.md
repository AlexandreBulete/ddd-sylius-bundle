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

