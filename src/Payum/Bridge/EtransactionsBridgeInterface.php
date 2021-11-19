<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\Payum\Bridge;

use Vinium\SyliusUp2PayPlugin\Legacy\Etransactions;

interface EtransactionsBridgeInterface
{
    /**
     * @param string $secretKey
     *
     * @return Etransactions
     */
    public function createEtransactions($secretKey);

    /**
     * @param string $secretKey
     *
     * @return bool
     */
    public function paymentVerification($secretKey);

    /**
     * @return bool
     */
    public function isGetMethod();

    /**
     * @return bool
     */
    public function isPostMethod();
}