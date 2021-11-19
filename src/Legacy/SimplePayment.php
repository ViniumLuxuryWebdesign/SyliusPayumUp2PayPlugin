<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Legacy;

use Payum\Core\Reply\HttpResponse;

final class SimplePayment
{
    /**
     * @var Etransactions|object
     */
    private $etransactions;

    /**
     * @var string
     */
    private $rang;

    /**
     * @var string
     */
    private $identifiant;

    /**
     * @var string
     */
    private $site;

    /**
     * @var bool
     */
    private $sandbox;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $transactionReference;

    /**
     * @var string
     */
    private $customerEmail;

    /**
     * @var string
     */
    private $automaticResponseUrl;

    /**
     * @var string
     */
    private $successUrl;

    /**
     * @var string
     */
    private $cancelUrl;

    /**
     * @param Etransactions $etransactions
     * @param $identifiant
     * @param $rang
     * @param $amount
     * @param $targetUrl
     * @param $currency
     * @param $transactionReference
     * @param $customerEmail
     * @param $automaticResponseUrl
     * @param $successUrl
     * @param $cancelUrl
     * @param $shoppingCart
     * @param $billingData
     * @param $locale
     */
    public function __construct(
        Etransactions $etransactions,
        $identifiant,
        $rang,
        $site,
        $sandbox,
        $amount,
        $currency,
        $transactionReference,
        $customerEmail,
        $automaticResponseUrl,
        $successUrl,
        $cancelUrl,
        $shoppingCart,
        $billingData,
        $locale
    )
    {
        $this->automaticResponseUrl = $automaticResponseUrl;
        $this->successUrl = $successUrl;
        $this->cancelUrl = $cancelUrl;
        $this->transactionReference = $transactionReference;
        $this->etransactions = $etransactions;
        $this->rang = $rang;
        $this->site = $site;
        $this->sandbox = $sandbox;
        $this->identifiant = $identifiant;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->customerEmail = $customerEmail;
        $this->shoppingCart = $shoppingCart;
        $this->billingData = $billingData;
        $this->locale = $locale;
    }

    public function execute()
    {
        $this->resolveEnvironment();

        $this->etransactions->setSite($this->site);
        $this->etransactions->setRang($this->rang);
        $this->etransactions->setIdentifiant($this->identifiant);
        $this->etransactions->setAmount($this->amount);
        $this->etransactions->setCurrency($this->currency);
        $this->etransactions->setTransactionReference($this->transactionReference);
        $this->etransactions->setBillingContactEmail($this->customerEmail);
        //$this->etransactions->setInterfaceVersion(Etransactions::INTERFACE_VERSION);
        //$this->etransactions->setKeyVersion('1');
        $this->etransactions->setReturnVariables();
        $this->etransactions->setHash("SHA512");
        $this->etransactions->setSource("RWD");
        $this->etransactions->setMerchantTransactionDateTime(date('c'));
        $this->etransactions->setAutomaticResponseUrl($this->automaticResponseUrl);
        $this->etransactions->setSuccessReturnUrl($this->successUrl);
        $this->etransactions->setCancelReturnUrl($this->cancelUrl);
        $this->etransactions->setShoppingCart($this->shoppingCart);
        $this->etransactions->setBillingData($this->billingData);
        $this->etransactions->setLocale($this->locale);

        $this->etransactions->validate();

        $response = $this->etransactions->executeRequest();

        throw new HttpResponse($response);
    }

    /**
     * @return void
     */
    private function resolveEnvironment()
    {
        if ($this->sandbox) {
            $this->etransactions->setUrl(Etransactions::TEST);
        } else {
            $this->etransactions->setUrl(Etransactions::PRODUCTION);
        }

        return;
    }
}
