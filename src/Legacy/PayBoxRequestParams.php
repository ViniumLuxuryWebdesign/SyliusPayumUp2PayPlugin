<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\Legacy;

interface PayBoxRequestParams {

    /**
     * Paybox Site param.
     */
    const PBX_SITE = "PBX_SITE";

    /**
     * Paybox rang param.
     */
    const PBX_RANG = "PBX_RANG";

    /**
     * Paybox identifiant param.
     */
    const PBX_IDENTIFIANT = "PBX_IDENTIFIANT";

    /**
     * Paybox hash param.
     */
    const PBX_HASH = "PBX_HASH";

    /**
     * Paybox code retour param.
     */
    const PBX_RETOUR = "PBX_RETOUR";

    /**
     * Paybox hmac param.
     */
    const PBX_HMAC = "PBX_HMAC";

    /**
     * Paybox payment type param.
     */
    const PBX_TYPEPAIEMENT = "PBX_TYPEPAIEMENT";

    /**
     * Paybox card type param.
     */
    const PBX_TYPECARTE = "PBX_TYPECARTE";

    /**
     * Paybox total param.
     */
    const PBX_TOTAL = "PBX_TOTAL";

    /**
     * Paybox devise param.
     */
    const PBX_DEVISE = "PBX_DEVISE";

    /**
     * Paybox order number param.
     */
    const PBX_CMD = "PBX_CMD";

    /**
     * Paybox porteur param.
     */
    const PBX_PORTEUR = "PBX_PORTEUR";

    /**
     * Paybox redirect url when payment done.
     */
    const PBX_EFFECTUE = "PBX_EFFECTUE";

    /**
     * Paybox redirect url when payment canceled.
     */
    const PBX_ANNULE = "PBX_ANNULE";

    /**
     * Paybox redirect url when payment refused.
     */
    const PBX_REFUSE = "PBX_REFUSE";

    /**
     * Paybox autoresponse url.
     */
    const PBX_REPONDRE_A = "PBX_REPONDRE_A";

    /**
     * Paybox time param.
     */
    const PBX_TIME = "PBX_TIME";

    /**
     * Paybox responsive param.
     */
    const PBX_SOURCE = "PBX_SOURCE";
}
