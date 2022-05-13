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
     * @param string method
     * @param array $fields
     */
    protected function doRequest(string $method, array $fields)
    {
        $headers = [];
        $request = $this->messageFactory->createRequest($method, $this->getApiEndpoint(), $headers, http_build_query($fields));
        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response;
    }

    /**
     * @param array $fields
     */
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
    protected function getApiEndpoint(): string
    {
        return $this->options['sandbox'] ? PayboxParams::SERVER_TEST : PayboxParams::SERVER_PRODUCTION;
    }

    /**
     * @param $hmac string hmac key
     * @param $fields array fields
     *
     * @return string
     */
    private function computeHmac(string $hmac, array $fields): string
    {
        // Si la clé est en ASCII, On la transforme en binaire
        $binKey = pack('H*', $hmac);
        $msg = $this->stringify($fields);
        $string = strtoupper(hash_hmac($fields[PayboxParams::PBX_HASH], $msg, $binKey));

        return $string;
    }

    /**
     * Makes an array of parameters become a querystring like string.
     * @param array $array
     *
     * @return string
     */
    private function stringify(array $array): string
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
    public function getOptions() :array
    {
        return $this->options;
    }
}
