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
   composer require vinium/sylius-payum-up2pay-plugin
```

2. Add bundle on bundles.php (if autorecipe is not used)

    ```php
    Vinium\SyliusPayumUp2PayPlugin\ViniumSyliusPayumUp2PayPlugin::class => ['all' => true],
    ```
3. Add configuration. Create file config/packages/vinium_sylius_up2pay_plugin.yaml and add following values

```yaml
imports:
  - { resource: '@ViniumSyliusPayumUp2PayPlugin/Resources/config/config.yml' }
 ```

## Documentation Up2Pay officielle

https://www.ca-moncommerce.com/espace-client-mon-commerce/up2pay-e-transactions/ma-documentation/

## Carte de test

Numéro : 1111222233334444

Date de validité : 12/25

CCV : 123

## Réalisation de test

https://static.ca-moncommerce.com/documents/e-transactions_mise-en_place_des_tests_v0.1.pdf

# Env variable for local capture if IPN cannot be call

```
VINIUM_SYLIUS_PAYUM_UP2PAY_PLUGIN_LOCAL_CAPTURE=1
```
