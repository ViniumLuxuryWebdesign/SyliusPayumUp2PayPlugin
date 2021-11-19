<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Vinium\SyliusPayumUp2PayPlugin\Payum\Action\ConvertPaymentAction;

class Up2PayGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'up2pay',
            'payum.factory_title' => 'Up2Pay Etransactions',
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.http_client' => '@vinium.up2pay.payment.bridge',
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'site' => '',
                'rang' => '',
                'identifiant' => '',
                'hmac' => '',
                'hash' => 'SHA512',
                'retour' => 'Mt:M;Ref:R;Auto:A;Erreur:E',
                'sandbox' => false,
                'type_paiement' => '',
                'type_carte' => ''
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['site', 'rang', 'identifiant', 'hmac'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $etransactionsConfig = [
                    'site' => $config['site'],
                    'rang' => $config['rang'],
                    'identifiant' => $config['identifiant'],
                    'hmac' => $config['hmac'],
                    'sandbox' => $config['sandbox']
                ];

                return $etransactionsConfig;
            };
        }
    }
}
