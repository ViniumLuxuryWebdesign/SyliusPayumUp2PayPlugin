<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Legacy;

use Payum\Core\Reply\HttpPostRedirect;

class Etransactions
{
    const RESPONSE_SUCCESS = '00000';
    const RESPONSE_FAILED_MIN = '00100';
    const RESPONSE_FAILED_MAX = '00199';
    const RESPONSE_PENDING = '99999';

    const UP2PAY_STATUS_INIT = 'init';

    const TEST = "https://recette-tpeweb.e-transactions.fr/php/";
    const PRODUCTION = "https://tpeweb.e-transactions.fr/php/";

    // const TEST = "https://recette-tpeweb.e-transactions.fr/php/";
    // const PRODUCTION = "https://tpeweb.e-transactions.fr/php/";

    const INTERFACE_VERSION = "IR_WS_2.17";
    const INSTALMENT = "INSTALMENT";

    // BYPASS3DS
    const BYPASS3DS_ALL = "ALL";
    const BYPASS3DS_MERCHANTWALLET = "MERCHANTWALLET";

    private $brandsmap = array(
        'ACCEPTGIRO' => 'CREDIT_TRANSFER',
        'AMEX' => 'CARD',
        'BCMC' => 'CARD',
        'BUYSTER' => 'CARD',
        'BANK CARD' => 'CARD',
        'CB' => 'CARD',
        'IDEAL' => 'CREDIT_TRANSFER',
        'INCASSO' => 'DIRECT_DEBIT',
        'MAESTRO' => 'CARD',
        'MASTERCARD' => 'CARD',
        'MASTERPASS' => 'CARD',
        'MINITIX' => 'OTHER',
        'NETBANKING' => 'CREDIT_TRANSFER',
        'PAYPAL' => 'CARD',
        'PAYLIB' => 'CARD',
        'REFUND' => 'OTHER',
        'SDD' => 'DIRECT_DEBIT',
        'SOFORT' => 'CREDIT_TRANSFER',
        'VISA' => 'CARD',
        'VPAY' => 'CARD',
        'VISA ELECTRON' => 'CARD',
        'CBCONLINE' => 'CREDIT_TRANSFER',
        'KBCONLINE' => 'CREDIT_TRANSFER'
    );

    /** @var ShaComposer */
    private $hmac;

    private $pspURL = self::TEST;

    private $responseData;

    private $parameters = array();

    private $pspFields = array(
        'amount', 'cardExpiryDate', 'cardNumber', 'cardCSCValue',
        'currencyCode', 'merchantId', 'interfaceVersion', 'sealAlgorithm',
        'transactionReference', 'keyVersion', 'paymentMeanBrand', 'customerLanguage',
        'billingAddress.city', 'billingAddress.company', 'billingAddress.country',
        'billingAddress', 'billingAddress.postBox', 'billingAddress.state',
        'billingAddress.street', 'billingAddress.streetNumber', 'billingAddress.zipCode',
        'billingContact.email', 'billingContact.firstname', 'billingContact.gender',
        'billingContact.lastname', 'billingContact.mobile', 'billingContact.phone',
        'customerAddress', 'customerAddress.city', 'customerAddress.company',
        'customerAddress.country', 'customerAddress.postBox', 'customerAddress.state',
        'customerAddress.street', 'customerAddress.streetNumber', 'customerAddress.zipCode',
        'customerEmail', 'customerContact', 'customerContact.email', 'customerContact.firstname',
        'customerContact.gender', 'customerContact.lastname', 'customerContact.mobile',
        'customerContact.phone', 'customerContact.title', 'expirationDate', 'automaticResponseUrl',
        'templateName', 'paymentMeanBrandList', 'instalmentData.number', 'instalmentData.datesList',
        'instalmentData.transactionReferencesList', 'instalmentData.amountsList', 'paymentPattern',
        'captureDay', 'captureMode', 'merchantTransactionDateTime', 'fraudData.bypass3DS', 'seal',
        'orderChannel', 'orderId', 'returnContext', 'transactionOrigin', 'merchantWalletId', 'paymentMeanId'
    );

    private $requiredFields = [
        PayBoxRequestParams::PBX_SITE,
        PayBoxRequestParams::PBX_RANG,
        PayBoxRequestParams::PBX_IDENTIFIANT,
        PayBoxRequestParams::PBX_TOTAL,
        PayBoxRequestParams::PBX_DEVISE,
        PayBoxRequestParams::PBX_CMD,
        PayBoxRequestParams::PBX_PORTEUR,
        PayBoxRequestParams::PBX_RETOUR,
        PayBoxRequestParams::PBX_HASH,
        PayBoxRequestParams::PBX_TIME,
        PayBoxRequestParams::PBX_REPONDRE_A,
        PayBoxRequestParams::PBX_SOURCE,
        PayBoxRequestParams::PBX_EFFECTUE,
        PayBoxRequestParams::PBX_ANNULE,
    ];


    public $allowedlanguages = array(
        'nl', 'fr', 'de', 'it', 'es', 'cy', 'en'
    );

    private static $currencies = array(
        'EUR' => '978', 'USD' => '840', 'CHF' => '756', 'GBP' => '826',
        'CAD' => '124', 'JPY' => '392', 'MXP' => '484', 'TRY' => '949',
        'AUD' => '036', 'NZD' => '554', 'NOK' => '578', 'BRC' => '986',
        'ARP' => '032', 'KHR' => '116', 'TWD' => '901', 'SEK' => '752',
        'DKK' => '208', 'KRW' => '410', 'SGD' => '702', 'XPF' => '953',
        'XOF' => '952'
    );

    public static function convertCurrencyToCurrencyCode($currency)
    {
        if (!in_array($currency, array_keys(self::$currencies)))
            throw new \InvalidArgumentException("Unknown currencyCode $currency.");
        return self::$currencies[$currency];
    }

    public static function convertCurrencyCodeToCurrency($code)
    {
        if (!in_array($code, array_values(self::$currencies)))
            throw new \InvalidArgumentException("Unknown Code $code.");
        return array_search($code, self::$currencies);
    }

    public static function getCurrencies()
    {
        return self::$currencies;
    }

    public function __construct($hmac)
    {
        $this->hmac = $hmac;
    }

    /** @return string */
    public function getUrl()
    {
        return $this->pspURL;
    }

    public function setUrl($pspUrl)
    {
        $this->validateUri($pspUrl);
        $this->pspURL = $pspUrl;
    }

    public function setReturnVariables()
    {
        $this->parameters[PayBoxRequestParams::PBX_RETOUR] = 'Mt:M;Ref:R;Auto:A;Erreur:E';
    }

    public function setAutomaticResponseUrl($url)
    {
        $this->validateUri($url);
        $this->parameters[PayBoxRequestParams::PBX_REPONDRE_A] = $url;
    }

    public function setSuccessReturnUrl($url)
    {
        $this->validateUri($url);
        $this->parameters[PayBoxRequestParams::PBX_EFFECTUE] = $url;
    }

    public function setCancelReturnUrl($url)
    {
        $this->validateUri($url);
        $this->parameters[PayBoxRequestParams::PBX_ANNULE] = $url;
    }

    public function setTransactionReference($transactionReference)
    {
        if (preg_match('/[^a-zA-Z0-9_-]/', $transactionReference)) {
            throw new \InvalidArgumentException("TransactionReference cannot contain special characters");
        }
        $this->parameters[PayBoxRequestParams::PBX_CMD] = $transactionReference;
    }

    /**
     * Set amount in cents, eg EUR 12.34 is written as 1234
     */
    public function setAmount($amount)
    {
        if (!is_int($amount)) {
            throw new \InvalidArgumentException("Integer expected. Amount is always in cents");
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount must be a positive number");
        }
        $this->parameters[PayBoxRequestParams::PBX_TOTAL] = $amount;

    }

    public function setSource($source)
    {
        $this->parameters[PayBoxRequestParams::PBX_SOURCE] = $source;

    }

    public function setIdentifiant($identifiant)
    {
        $this->parameters[PayBoxRequestParams::PBX_IDENTIFIANT] = $identifiant;
    }

    public function setRang($rang)
    {
        $this->parameters[PayBoxRequestParams::PBX_RANG] = $rang;
    }

    public function setSite($site)
    {
        $this->parameters[PayBoxRequestParams::PBX_SITE] = $site;
    }

    public function setCurrency($currency)
    {
        if (!array_key_exists(strtoupper($currency), self::getCurrencies())) {
            throw new \InvalidArgumentException("Unknown currency");
        }
        $this->parameters[PayBoxRequestParams::PBX_DEVISE] = self::convertCurrencyToCurrencyCode($currency);
    }

    public function setBillingContactEmail($email)
    {
        if (strlen($email) > 50) {
            throw new \InvalidArgumentException("Email is too long");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Email is invalid");
        }
        $this->parameters[PayBoxRequestParams::PBX_PORTEUR] = $email;
    }

    public function setMerchantTransactionDateTime($value)
    {
        if (strlen($value) > 25) {
            throw new \InvalidArgumentException("merchantTransactionDateTime is too long");
        }
        $this->parameters[PayBoxRequestParams::PBX_TIME] = $value;
    }

    public function setHash($value)
    {
        $this->parameters[PayBoxRequestParams::PBX_HASH] = $value;
    }


    public function toArray()
    {
        ksort($this->parameters);
        return $this->parameters;
    }

    public function validate()
    {
        foreach ($this->requiredFields as $field) {
            if (empty($this->parameters[$field])) {
                throw new \RuntimeException($field . " can not be empty");
            }
        }
    }

    protected function validateUri($uri)
    {
        if (!filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Uri is not valid");
        }
        if (strlen($uri) > 200) {
            throw new \InvalidArgumentException("Uri is too long");
        }
    }

    // Traitement des reponses de Mercanet
    // -----------------------------------

    /** @var string */
    const SHASIGN_FIELD = "SEAL";

    /** @var string */
    const DATA_FIELD = "DATA";

    /**
     * @var string
     */
    private $shaSign;

    private $dataString;

    private $responseRequest;

    private $parameterArray;

    /**
     * Filter http request parameters
     * @param array $httpRequest
     * @return array
     */
    private function filterRequestParameters(array $httpRequest)
    {
        //filter request for Sips parameters
        if (!array_key_exists(self::DATA_FIELD, $httpRequest) || $httpRequest[self::DATA_FIELD] == '') {
            throw new \InvalidArgumentException('Data parameter not present in parameters.');
        }
        $parameters = array();
        $this->responseData = $httpRequest[self::DATA_FIELD];
        $dataString = $httpRequest[self::DATA_FIELD];
        $this->dataString = $dataString;
        $dataParams = explode('|', $dataString);
        foreach ($dataParams as $dataParamString) {
            $dataKeyValue = explode('=', $dataParamString, 2);
            $parameters[$dataKeyValue[0]] = $dataKeyValue[1];
        }

        return $parameters;
    }

    public function getSeal()
    {
        return $this->shaSign;
    }

    private function extractShaSign(array $parameters)
    {
        if (!array_key_exists(self::SHASIGN_FIELD, $parameters) || $parameters[self::SHASIGN_FIELD] == '') {
            throw new \InvalidArgumentException('SHASIGN parameter not present in parameters.');
        }

        return $parameters[self::SHASIGN_FIELD];
    }

    /**
     * Checks if the response is valid
     * @return bool
     */
    public function isValid($post_data, $ip)
    {
        $ip = str_replace('::ffff:', '', $ip); //ipv4 format
        if ($post_data['error_code'] == '00000' && in_array($ip, array('195.101.99.73', '195.101.99.76', '194.2.160.69', '194.2.160.76', '195.25.7.158', '195.25.7.149', '194.2.122.158', '194.2.122.190', '195.101.99.76', '195.25.67.22', '195.25.7.166', '195.101.99.67', '194.2.160.81', '194.2.160.89', '195.25.67.9', '195.25.67.1', '195.25.7.145', '194.2.160.90', '195.25.67.10')))
        {
          return true;
        }

        return false;
    }

    function getXmlValueByTag($inXmlset, $needle)
    {
        $resource = xml_parser_create();//Create an XML parser
        xml_parse_into_struct($resource, $inXmlset, $outArray);// Parse XML data into an array structure
        xml_parser_free($resource);//Free an XML parser
        for ($i = 0; $i < count($outArray); $i++) {
            if ($outArray[$i]['tag'] == strtoupper($needle)) {
                $tagValue = $outArray[$i]['value'];
            }
        }
        return $tagValue;
    }

    /**
     * Retrieves a response parameter
     * @param string $key
     * @throws \InvalidArgumentException
     */
    public function getParam($key)
    {
        return $this->parameterArray[$key];
    }

    public function getResponseRequest()
    {
        return $this->responseRequest;
    }

     /**
     * @param $hmac string hmac key
     * @param $fields array fields
     * @return string
     */
    protected function computeHmac($hmac, $fields)
    {
        // Si la clé est en ASCII, On la transforme en binaire
        $binKey = pack("H*", $hmac);
        $msg = self::stringify($fields);

        return strtoupper(hash_hmac($fields[PayBoxRequestParams::PBX_HASH], $msg, $binKey));
    }

    /**
     * Makes an array of parameters become a querystring like string.
     *
     * @param  array $array
     *
     * @return string
     */
    static public function stringify(array $array)
    {
        $result = array();
        foreach ($array as $key => $value) {
            $result[] = sprintf('%s=%s', $key, $value);
        }
        return implode('&', $result);
    }

    public function executeRequest()
    {
        $fields = [];

        foreach ($this->requiredFields as $key) {
            $fields[$key] = $this->parameters[$key];
        }

        $fields[PayBoxRequestParams::PBX_HMAC] = strtoupper($this->computeHmac($this->hmac, $fields));

        throw new HttpPostRedirect($this->getUrl(), $fields);
    }


}
