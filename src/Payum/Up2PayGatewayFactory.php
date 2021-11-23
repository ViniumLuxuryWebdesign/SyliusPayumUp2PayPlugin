<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Vinium\SyliusPayumUp2PayPlugin\Payum\Action\CancelAction;
use Vinium\SyliusPayumUp2PayPlugin\Payum\Action\ConvertPaymentAction;
use Vinium\SyliusPayumUp2PayPlugin\Payum\Action\CaptureAction;
use Vinium\SyliusPayumUp2PayPlugin\Payum\Action\NotifyAction;
use Vinium\SyliusPayumUp2PayPlugin\Payum\Action\StatusAction;

class Up2PayGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritdoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name'           => 'up2pay',
            'payum.factory_title'          => 'Up2Pay Etransactions',
            'payum.action.capture'         => new CaptureAction(),
            //'payum.action.authorize'       => new AuthorizeAction(),
            //'payum.action.refund'          => new RefundAction(),
            'payum.action.cancel'          => new CancelAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.status'          => new StatusAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'site'          => '',
                'rang'          => '',
                'identifiant'   => '',
                'hmac'          => '',
                'hash'          => 'SHA512',
                'retour'        => 'Mt:M;Ref:R;Auto:A;Appel:T;Abo:B;Reponse:E;Transaction:S;Pays:Y;Signature:K',
                'sandbox'       => true,
                'type_paiement' => '',
                'type_carte'    => '',
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['site', 'rang', 'identifiant', 'hmac'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
