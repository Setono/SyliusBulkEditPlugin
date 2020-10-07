# Sylius Bulk Edit Plugin

[![Latest Version][ico-version]][link-packagist]
[![Latest Unstable Version][ico-unstable-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

This plugin is a proof of concept of something great to be.

## Installation

### Download

```bash
$ composer require setono/sylius-bulk-edit-plugin
```

### Import configuration

```yaml
# config/packages/setono_sylius_bulk_edit.yaml
imports:
    # ...
    - { resource: "@SetonoSyliusBulkEditPlugin/Resources/config/app/config.yaml" }
```

### Import routes
   
```yaml
# config/routes/setono_sylius_bulk_edit.yaml
setono_sylius_bulk_edit:
    resource: "@SetonoSyliusBulkEditPlugin/Resources/config/routes.yaml"
```

or if your app doesn't use locales:
   
```yaml
# config/routes.yaml
setono_sylius_bulk_edit:
    resource: "@SetonoSyliusBulkEditPlugin/Resources/config/routes_no_locale.yaml"
```

### Add plugin class to your `bundles.php`:

```php
<?php
$bundles = [
    // ...

    Setono\SyliusBulkEditPlugin\SetonoSyliusBulkEditPlugin::class => ['all' => true],

    // ...
];
```

### Extend resource classes

**Extend `ProductRepository`**

```php
<?php

# src/Doctrine/ORM/ProductRepository.php

declare(strict_types=1);

namespace App\Doctrine\ORM;

use Setono\SyliusBulkEditPlugin\Doctrine\ORM\ProductRepositoryTrait;
use Setono\SyliusBulkEditPlugin\Repository\ProductRepositoryInterface;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository implements ProductRepositoryInterface
{
    use ProductRepositoryTrait;
}
```

**Add configuration**

```yaml
# config/packages/_sylius.yaml
sylius_product:
    resources:
        product:
            classes:
                repository: App\Doctrine\ORM\ProductRepository
```

### Done!

Go to `/admin/products`, select a few products and click the `Edit` button.

[ico-version]: https://poser.pugx.org/setono/sylius-bulk-edit-plugin/v/stable
[ico-unstable-version]: https://poser.pugx.org/setono/sylius-bulk-edit-plugin/v/unstable
[ico-license]: https://poser.pugx.org/setono/sylius-bulk-edit-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusBulkEditPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusBulkEditPlugin/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-bulk-edit-plugin
[link-github-actions]: https://github.com/Setono/SyliusBulkEditPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusBulkEditPlugin
