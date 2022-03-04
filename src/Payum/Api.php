<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use Payum\Core\Reply\HttpPostRedirect;

class Api
{
    protected HttpClientInterface $client;

    protected MessageFactory $messageFactory;

    protected array $options = [];

    private static array $currencies = array(
        'EUR' => '978', 'USD' => '840', 'CHF' => '756', 'GBP' => '826',
        'CAD' => '124', 'JPY' => '392', 'MXP' => '484', 'TRY' => '949',
        'AUD' => '036', 'NZD' => '554', 'NOK' => '578', 'BRC' => '986',
        'ARP' => '032', 'KHR' => '116', 'TWD' => '901', 'SEK' => '752',
        'DKK' => '208', 'KRW' => '410', 'SGD' => '702', 'XPF' => '953',
        'XOF' => '952'
    );

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param array $fields
     *
     */
    protected function doRequest($method, array $fields)
    {
        $headers = [];
        $request = $this->messageFactory->createRequest($method, $this->getApiEndpoint(), $headers, http_build_query($fields));
        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response;
    }

    public function doPayment(array $fields)
    {
        $fields[PayboxParams::PBX_SITE] = $this->options['site'];
        $fields[PayboxParams::PBX_RANG] = $this->options['rang'];
        $fields[PayboxParams::PBX_IDENTIFIANT] = $this->options['identifiant'];
        $fields[PayboxParams::PBX_HASH] = $this->options['hash'];
        $fields[PayboxParams::PBX_SOURCE] = 'RWD';
        $fields[PayboxParams::PBX_RETOUR] = PayboxParams::PBX_RETOUR_VALUE;
        $fields[PayboxParams::PBX_TIME] = date('c');
        $fields[PayboxParams::PBX_HMAC] = $this->computeHmac($this->options['hmac'], $fields);
        $authorizeTokenUrl = $this->getApiEndpoint();
        throw new HttpPostRedirect($authorizeTokenUrl, $fields);
    }

    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        return $this->options['sandbox'] ? PayboxParams::SERVER_TEST : PayboxParams::SERVER_PRODUCTION;
    }

    /**
     * @param $hmac string hmac key
     * @param $fields array fields
     *
     * @return string
     */
    private function computeHmac($hmac, $fields)
    {
        // Si la clé est en ASCII, On la transforme en binaire
        $binKey = pack('H*', $hmac);
        $msg = $this->stringify($fields);
        $string = strtoupper(hash_hmac($fields[PayboxParams::PBX_HASH], $msg, $binKey));

        return $string;
    }

    /**
     * Makes an array of parameters become a querystring like string.
     *
     * @param array $array
     *
     * @return string
     */
    private function stringify(array $array)
    {
        $result = array();
        foreach ($array as $key => $value) {
            $result[] = sprintf('%s=%s', $key, $value);
        }

        return implode('&', $result);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
