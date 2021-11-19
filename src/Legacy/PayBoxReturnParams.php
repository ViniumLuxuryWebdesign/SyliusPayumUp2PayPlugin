<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\Legacy;

interface PayBoxReturnParams {

    /**
     * Montant de la transaction (précisé dans PBX_TOTAL).
     */
    const TRANSACTION_AMOUNT = "M";

    /**
     * Référence commande (précisée dans PBX_CMD) : espace URL encodé.
     */
    const REFERENCE = "R";

    /**
     * Numéro d’appel.
     */
    const PHONE = "T";

    /**
     * Numéro d’Autorisation (numéro remis par le centre d’autorisation) : URL encodé
     */
    const AUTHORIZATION_NUMBER = "A";

    /**
     * Numéro d’abonnement (numéro remis par la plateforme).
     */
    const SUBSCRIPTION_NUMBER = "B";

    /**
     * Type de Carte retenu (cf. PBX_TYPECARTE).
     */
    const CARD_TYPE = "C";

    /**
     * Date de fin de validité de la carte du porteur. Format : AAMM.
     */
    const CARD_END_VALIDITY = "D";

    /**
     * Code réponse de la transaction (cf. Tableau 2 : Codes réponse PBX_RETOUR).
     */
    const TRANSACTION_RESPONSE_CODE = "E";

    /**
     * Etat de l’authentification du porteur vis-à-vis du programme 3-D Secure :
     * Y : Porteur authentifié
     * A : Authentification du porteur forcée par la banque de l’acheteur
     * U : L’authentification du porteur n’a pas pu s’effectuer
     * N : Porteur non authentifié
     */
    const THREE_D_SECURE_AUTHENTICATION_STATE = "F";

    /**
     * Garantie du paiement par le programme 3-D Secure. Format : O ou N
     */
    const THREE_D_SECURE_WARRANTY = "G";

    /**
     * Empreinte de la carte.
     */
    const EMPREINTE_CARD = "H";

    /**
     * Code pays de l’adresse IP de l’internaute. Format : ISO 3166 (alphabétique).
     */
    const USER_IP = "I";

    /**
     * 2 derniers chiffres du numéro de carte du porteur.
     */
    const CARD_LAST_DIGITS = "J";

    /**
     * Signature sur les variables de l’URL. Format : url-encodé.
     */
    const URL_SIGNATURE = "K";

}
