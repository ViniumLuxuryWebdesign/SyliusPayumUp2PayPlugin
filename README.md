<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Sylius Up2Pay Plugin</h1>

<p align="center">Up2Pay Gateway</p>


## Quickstart Installation

1. Install plugin
```
   composer require vinium/sylius-up2pay-plugin
```

2. Add bundle on bundles.php (if autorecipe is not used)

    ```php
    Vinium\SyliusExtendedUserPlugin\ViniumSyliusUp2PayPlugin::class => ['all' => true]
    ```

3. Add Routing.Edit config/routes.yaml and add following values

```yaml
vinium_sylius_up2pay:
    resource: "@ViniumSyliusUp2PayPlugin/Resources/config/routing.yaml"
 ```

## Documentation Up2Pay officielle

https://www.ca-moncommerce.com/espace-client-mon-commerce/up2pay-e-transactions/ma-documentation/

## Carte de test

Numéro : 1111222233334444

Date de validité : 12/25

CCV : 123
