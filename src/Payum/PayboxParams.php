<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use League\ISO3166\ISO3166;

class PayboxParams
{
    // Default servers urls
    const SERVER_TEST = "https://recette-tpeweb.e-transactions.fr/php/";
    const SERVER_PRODUCTION = "https://tpeweb.e-transactions.fr/php/";

    const INTERFACE_VERSION = "IR_WS_2.17";
    const INSTALMENT = "INSTALMENT";

    // Requests params values
    const PBX_RETOUR_VALUE = 'Mt:M;Ref:R;Auto:A;Appel:T;Abo:B;Reponse:E;Transaction:S;Pays:Y;Signature:K';
    const PBX_DEVISE_EURO = '978';

    // Requests params keys
    const PBX_SITE = 'PBX_SITE';
    const PBX_RANG = 'PBX_RANG';
    const PBX_IDENTIFIANT = 'PBX_IDENTIFIANT';
    const PBX_HASH = 'PBX_HASH';
    const PBX_RETOUR = 'PBX_RETOUR';
    const PBX_HMAC = 'PBX_HMAC';
    const PBX_TYPEPAIEMENT = 'PBX_TYPEPAIEMENT';
    const PBX_TYPECARTE = 'PBX_TYPECARTE';
    const PBX_TOTAL = 'PBX_TOTAL';
    const PBX_DEVISE = 'PBX_DEVISE';
    const PBX_CMD = 'PBX_CMD';
    const PBX_PORTEUR = 'PBX_PORTEUR';
    const PBX_EFFECTUE = 'PBX_EFFECTUE';
    const PBX_ATTENTE = 'PBX_ATTENTE';
    const PBX_ANNULE = 'PBX_ANNULE';
    const PBX_REFUSE = 'PBX_REFUSE';
    const PBX_REPONDRE_A = 'PBX_REPONDRE_A';
    const PBX_TIME = 'PBX_TIME';
    const PBX_SOURCE = 'PBX_SOURCE';
    const PBX_BILLING = "PBX_BILLING";
    const PBX_SHOPPINGCART = "PBX_SHOPPINGCART";
    const PBX_ERRORCODETEST = "PBX_ERRORCODETEST";

    private array $currencies = [
        'EUR' => '978', 'USD' => '840', 'CHF' => '756', 'GBP' => '826',
        'CAD' => '124', 'JPY' => '392', 'MXP' => '484', 'TRY' => '949',
        'AUD' => '036', 'NZD' => '554', 'NOK' => '578', 'BRC' => '986',
        'ARP' => '032', 'KHR' => '116', 'TWD' => '901', 'SEK' => '752',
        'DKK' => '208', 'KRW' => '410', 'SGD' => '702', 'XPF' => '953',
        'XOF' => '952'
    ];

    private LocaleContextInterface $localeContext;

    public function __construct(LocaleContextInterface $localeContext)
    {
        $this->localeContext = $localeContext;
    }

    public function convertCurrencyToCurrencyCode($currency)
    {
        if (!\in_array($currency, array_keys($this->currencies))) {
            throw new \InvalidArgumentException("Unknown currencyCode $currency.");
        }
        return $this->currencies[$currency];
    }

    public function setBilling(OrderInterface $order): string
    {
        /** @var AddressInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();
        $firstName = $this->formatTextValue($billingAddress->getFirstName(), 'ANP', 30);
        $lastName = $this->formatTextValue($billingAddress->getLastName(), 'ANP', 30);
        $addressLine1 = $this->formatTextValue($billingAddress->getStreet(), 'ANS', 50);
        //$addressLine2 = $this->formatTextValue('', 'ANS', 50);
        $zipCode = $this->formatTextValue($billingAddress->getPostcode(), 'ANS', 16);
        $city = $this->formatTextValue($billingAddress->getCity(), 'ANS', 50);
        $countryCode = $billingAddress->getCountryCode() ? $billingAddress->getCountryCode() : 'FR';
        $phone = $this->formatTextValue($billingAddress->getPhoneNumber(), 'N', 10);
        $prefixPhone = $this->getISDCodeFromISO2($countryCode) ?: '+33';
        $dataIso = (new ISO3166)->alpha2($countryCode);
        //default french if not found
        $countryIso3661 = $dataIso['numeric'] ?? 250;
        $xml = sprintf(
            '<?xml version="1.0" encoding="utf-8"?><Billing><Address><FirstName>%s</FirstName><LastName>%s</LastName><Address1>%s</Address1><ZipCode>%s</ZipCode><City>%s</City><CountryCode>%d</CountryCode><CountryCodeMobilePhone>%s</CountryCodeMobilePhone><MobilePhone>%s</MobilePhone></Address></Billing>',
            $firstName,
            $lastName,
            $addressLine1,
            $zipCode,
            $city,
            $countryIso3661,
            $prefixPhone,
            $phone,
        );

        return $xml;
    }

    public function setShoppingCart($value): string
    {
        // totalQuantity must be less or equal than 99
        $totalQuantity = min($value, 99);
        $xml = sprintf('<?xml version="1.0" encoding="utf-8"?><shoppingcart><total><totalQuantity>%d</totalQuantity></total></shoppingcart>', $totalQuantity);

        return $xml;
    }

    /**
     * Format a value to respect specific rules
     *
     * @param string $value
     * @param string $type
     * @param int|null $maxLength
     * @return string
     */
    private function formatTextValue(string $value, string $type, int $maxLength = 0): string
    {
        /*
        AN : Alphanumerical without special characters
        ANP : Alphanumerical with spaces and special characters
        ANS : Alphanumerical with special characters
        N : Numerical only
        A : Alphabetic only
        */
        switch ($type) {
            default:
            case 'ANS':
            case 'AN':
                $value = $this->removeAccents($value);
                break;
            case 'ANP':
                $value = $this->removeAccents($value);
                $value = preg_replace('/[^-. a-zA-Z0-9]/', '', $value);
                break;
            case 'N':
                $value = preg_replace('/[^0-9]/', '', $value);
                break;
            case 'A':
                $value = $this->removeAccents($value);
                $value = preg_replace('/[^A-Za-z]/', '', $value);
                break;
        }
        // Remove carriage return characters
        $value = trim(preg_replace("/\r|\n/", '', $value));
        //Remove special characters
        $list = array_fill_keys(['&', '<', '>', '"', "'", '/'], '');
        $value = strtr($value, $list);
        // Cut the string when needed
        if (!empty($maxLength)) {
            if (function_exists('mb_strlen')) {
                if (mb_strlen($value) > $maxLength) {
                    $value = mb_substr($value, 0, $maxLength);
                }
            } elseif (strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength);
            }
        }

        return $value;
    }

    public function removeAccents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }
        if ($this->seemsUtf8($string)) {
            $chars = [
                // Decompositions for Latin-1 Supplement.
                'ª' => 'a',
                'º' => 'o',
                'À' => 'A',
                'Á' => 'A',
                'Â' => 'A',
                'Ã' => 'A',
                'Ä' => 'A',
                'Å' => 'A',
                'Æ' => 'AE',
                'Ç' => 'C',
                'È' => 'E',
                'É' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Ì' => 'I',
                'Í' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ð' => 'D',
                'Ñ' => 'N',
                'Ò' => 'O',
                'Ó' => 'O',
                'Ô' => 'O',
                'Õ' => 'O',
                'Ö' => 'O',
                'Ù' => 'U',
                'Ú' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'Ý' => 'Y',
                'Þ' => 'TH',
                'ß' => 's',
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'æ' => 'ae',
                'ç' => 'c',
                'è' => 'e',
                'é' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ð' => 'd',
                'ñ' => 'n',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ý' => 'y',
                'þ' => 'th',
                'ÿ' => 'y',
                'Ø' => 'O',
                // Decompositions for Latin Extended-A.
                'Ā' => 'A',
                'ā' => 'a',
                'Ă' => 'A',
                'ă' => 'a',
                'Ą' => 'A',
                'ą' => 'a',
                'Ć' => 'C',
                'ć' => 'c',
                'Ĉ' => 'C',
                'ĉ' => 'c',
                'Ċ' => 'C',
                'ċ' => 'c',
                'Č' => 'C',
                'č' => 'c',
                'Ď' => 'D',
                'ď' => 'd',
                'Đ' => 'D',
                'đ' => 'd',
                'Ē' => 'E',
                'ē' => 'e',
                'Ĕ' => 'E',
                'ĕ' => 'e',
                'Ė' => 'E',
                'ė' => 'e',
                'Ę' => 'E',
                'ę' => 'e',
                'Ě' => 'E',
                'ě' => 'e',
                'Ĝ' => 'G',
                'ĝ' => 'g',
                'Ğ' => 'G',
                'ğ' => 'g',
                'Ġ' => 'G',
                'ġ' => 'g',
                'Ģ' => 'G',
                'ģ' => 'g',
                'Ĥ' => 'H',
                'ĥ' => 'h',
                'Ħ' => 'H',
                'ħ' => 'h',
                'Ĩ' => 'I',
                'ĩ' => 'i',
                'Ī' => 'I',
                'ī' => 'i',
                'Ĭ' => 'I',
                'ĭ' => 'i',
                'Į' => 'I',
                'į' => 'i',
                'İ' => 'I',
                'ı' => 'i',
                'Ĳ' => 'IJ',
                'ĳ' => 'ij',
                'Ĵ' => 'J',
                'ĵ' => 'j',
                'Ķ' => 'K',
                'ķ' => 'k',
                'ĸ' => 'k',
                'Ĺ' => 'L',
                'ĺ' => 'l',
                'Ļ' => 'L',
                'ļ' => 'l',
                'Ľ' => 'L',
                'ľ' => 'l',
                'Ŀ' => 'L',
                'ŀ' => 'l',
                'Ł' => 'L',
                'ł' => 'l',
                'Ń' => 'N',
                'ń' => 'n',
                'Ņ' => 'N',
                'ņ' => 'n',
                'Ň' => 'N',
                'ň' => 'n',
                'ŉ' => 'n',
                'Ŋ' => 'N',
                'ŋ' => 'n',
                'Ō' => 'O',
                'ō' => 'o',
                'Ŏ' => 'O',
                'ŏ' => 'o',
                'Ő' => 'O',
                'ő' => 'o',
                'Œ' => 'OE',
                'œ' => 'oe',
                'Ŕ' => 'R',
                'ŕ' => 'r',
                'Ŗ' => 'R',
                'ŗ' => 'r',
                'Ř' => 'R',
                'ř' => 'r',
                'Ś' => 'S',
                'ś' => 's',
                'Ŝ' => 'S',
                'ŝ' => 's',
                'Ş' => 'S',
                'ş' => 's',
                'Š' => 'S',
                'š' => 's',
                'Ţ' => 'T',
                'ţ' => 't',
                'Ť' => 'T',
                'ť' => 't',
                'Ŧ' => 'T',
                'ŧ' => 't',
                'Ũ' => 'U',
                'ũ' => 'u',
                'Ū' => 'U',
                'ū' => 'u',
                'Ŭ' => 'U',
                'ŭ' => 'u',
                'Ů' => 'U',
                'ů' => 'u',
                'Ű' => 'U',
                'ű' => 'u',
                'Ų' => 'U',
                'ų' => 'u',
                'Ŵ' => 'W',
                'ŵ' => 'w',
                'Ŷ' => 'Y',
                'ŷ' => 'y',
                'Ÿ' => 'Y',
                'Ź' => 'Z',
                'ź' => 'z',
                'Ż' => 'Z',
                'ż' => 'z',
                'Ž' => 'Z',
                'ž' => 'z',
                'ſ' => 's',
                // Decompositions for Latin Extended-B.
                'Ș' => 'S',
                'ș' => 's',
                'Ț' => 'T',
                'ț' => 't',
                // Euro sign.
                '€' => 'E',
                // GBP (Pound) sign.
                '£' => '',
                // Vowels with diacritic (Vietnamese).
                // Unmarked.
                'Ơ' => 'O',
                'ơ' => 'o',
                'Ư' => 'U',
                'ư' => 'u',
                // Grave accent.
                'Ầ' => 'A',
                'ầ' => 'a',
                'Ằ' => 'A',
                'ằ' => 'a',
                'Ề' => 'E',
                'ề' => 'e',
                'Ồ' => 'O',
                'ồ' => 'o',
                'Ờ' => 'O',
                'ờ' => 'o',
                'Ừ' => 'U',
                'ừ' => 'u',
                'Ỳ' => 'Y',
                'ỳ' => 'y',
                // Hook.
                'Ả' => 'A',
                'ả' => 'a',
                'Ẩ' => 'A',
                'ẩ' => 'a',
                'Ẳ' => 'A',
                'ẳ' => 'a',
                'Ẻ' => 'E',
                'ẻ' => 'e',
                'Ể' => 'E',
                'ể' => 'e',
                'Ỉ' => 'I',
                'ỉ' => 'i',
                'Ỏ' => 'O',
                'ỏ' => 'o',
                'Ổ' => 'O',
                'ổ' => 'o',
                'Ở' => 'O',
                'ở' => 'o',
                'Ủ' => 'U',
                'ủ' => 'u',
                'Ử' => 'U',
                'ử' => 'u',
                'Ỷ' => 'Y',
                'ỷ' => 'y',
                // Tilde.
                'Ẫ' => 'A',
                'ẫ' => 'a',
                'Ẵ' => 'A',
                'ẵ' => 'a',
                'Ẽ' => 'E',
                'ẽ' => 'e',
                'Ễ' => 'E',
                'ễ' => 'e',
                'Ỗ' => 'O',
                'ỗ' => 'o',
                'Ỡ' => 'O',
                'ỡ' => 'o',
                'Ữ' => 'U',
                'ữ' => 'u',
                'Ỹ' => 'Y',
                'ỹ' => 'y',
                // Acute accent.
                'Ấ' => 'A',
                'ấ' => 'a',
                'Ắ' => 'A',
                'ắ' => 'a',
                'Ế' => 'E',
                'ế' => 'e',
                'Ố' => 'O',
                'ố' => 'o',
                'Ớ' => 'O',
                'ớ' => 'o',
                'Ứ' => 'U',
                'ứ' => 'u',
                // Dot below.
                'Ạ' => 'A',
                'ạ' => 'a',
                'Ậ' => 'A',
                'ậ' => 'a',
                'Ặ' => 'A',
                'ặ' => 'a',
                'Ẹ' => 'E',
                'ẹ' => 'e',
                'Ệ' => 'E',
                'ệ' => 'e',
                'Ị' => 'I',
                'ị' => 'i',
                'Ọ' => 'O',
                'ọ' => 'o',
                'Ộ' => 'O',
                'ộ' => 'o',
                'Ợ' => 'O',
                'ợ' => 'o',
                'Ụ' => 'U',
                'ụ' => 'u',
                'Ự' => 'U',
                'ự' => 'u',
                'Ỵ' => 'Y',
                'ỵ' => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin).
                'ɑ' => 'a',
                // Macron.
                'Ǖ' => 'U',
                'ǖ' => 'u',
                // Acute accent.
                'Ǘ' => 'U',
                'ǘ' => 'u',
                // Caron.
                'Ǎ' => 'A',
                'ǎ' => 'a',
                'Ǐ' => 'I',
                'ǐ' => 'i',
                'Ǒ' => 'O',
                'ǒ' => 'o',
                'Ǔ' => 'U',
                'ǔ' => 'u',
                'Ǚ' => 'U',
                'ǚ' => 'u',
                // Grave accent.
                'Ǜ' => 'U',
                'ǜ' => 'u',
            ];
            // Used for locale-specific rules.
            $locale = $this->localeContext->getLocaleCode();
            if (in_array($locale, ['de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'de_AT'], true)) {
                $chars['Ä'] = 'Ae';
                $chars['ä'] = 'ae';
                $chars['Ö'] = 'Oe';
                $chars['ö'] = 'oe';
                $chars['Ü'] = 'Ue';
                $chars['ü'] = 'ue';
                $chars['ß'] = 'ss';
            } elseif ('da_DK' === $locale) {
                $chars['Æ'] = 'Ae';
                $chars['æ'] = 'ae';
                $chars['Ø'] = 'Oe';
                $chars['ø'] = 'oe';
                $chars['Å'] = 'Aa';
                $chars['å'] = 'aa';
            } elseif ('ca' === $locale) {
                $chars['l·l'] = 'll';
            } elseif ('sr_RS' === $locale || 'bs_BA' === $locale) {
                $chars['Đ'] = 'DJ';
                $chars['đ'] = 'dj';
            }
            $string = strtr($string, $chars);
        } else {
            $chars = array();
            // Assume ISO-8859-1 if not UTF-8.
            $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
                . "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
                . "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
                . "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
                . "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
                . "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
                . "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
                . "\xec\xed\xee\xef\xf1\xf2\xf3"
                . "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
                . "\xfc\xfd\xff";

            $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';
            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars = [];
            $double_chars['in'] = ["\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe"];
            $double_chars['out'] = ['OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th'];
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }

    private function seemsUtf8($str): bool
    {
        $this->mbstringBinarySafeEncoding();
        $length = strlen($str);
        $this->mbstringBinarySafeEncoding(true);
        for ($i = 0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $n = 0; // 0bbbbbbb
            } elseif (($c & 0xE0) == 0xC0) {
                $n = 1; // 110bbbbb
            } elseif (($c & 0xF0) == 0xE0) {
                $n = 2; // 1110bbbb
            } elseif (($c & 0xF8) == 0xF0) {
                $n = 3; // 11110bbb
            } elseif (($c & 0xFC) == 0xF8) {
                $n = 4; // 111110bb
            } elseif (($c & 0xFE) == 0xFC) {
                $n = 5; // 1111110b
            } else {
                return false; // Does not match any model.
            }
            for ($j = 0; $j < $n; $j++) { // n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function mbstringBinarySafeEncoding(bool $reset = false)
    {
        static $encodings = array();
        static $overloaded = null;

        if (is_null($overloaded)) {
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2); // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
        }

        if (false === $overloaded) {
            return;
        }

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    private function getISDCodeFromISO2(string $iso2): ?string
    {
        $isdCodes = [
            "AF" => "+93",
            "AL" => "+355",
            "DZ" => "+213",
            "AS" => "+1-684",
            "AD" => "+376",
            "AO" => "+244",
            "AI" => "+1-264",
            "AQ" => "+672",
            "AG" => "+1-268",
            "AR" => "+54",
            "AM" => "+374",
            "AW" => "+297",
            "AU" => "+61",
            "AT" => "+43",
            "AZ" => "+994",
            "BS" => "+1-242",
            "BH" => "+973",
            "BD" => "+880",
            "BB" => "+1-246",
            "BY" => "+375",
            "BE" => "+32",
            "BZ" => "+501",
            "BJ" => "+229",
            "BM" => "+1-441",
            "BT" => "+975",
            "BO" => "+591",
            "BA" => "+387",
            "BW" => "+267",
            "BR" => "+55",
            "IO" => "+246",
            "BN" => "+673",
            "BG" => "+359",
            "BF" => "+226",
            "BI" => "+257",
            "KH" => "+855",
            "CM" => "+237",
            "CA" => "+1",
            "CV" => "+238",
            "KY" => "+1-345",
            "CF" => "+236",
            "TD" => "+235",
            "CL" => "+56",
            "CN" => "+86",
            "CO" => "+57",
            "KM" => "+269",
            "CG" => "+242",
            "CD" => "+243",
            "CK" => "+682",
            "CR" => "+506",
            "HR" => "+385",
            "CU" => "+53",
            "CY" => "+357",
            "CZ" => "+420",
            "DK" => "+45",
            "DJ" => "+253",
            "DM" => "+1-767",
            "DO" => "+1-809",
            "EC" => "+593",
            "EG" => "+20",
            "SV" => "+503",
            "GQ" => "+240",
            "ER" => "+291",
            "EE" => "+372",
            "ET" => "+251",
            "FJ" => "+679",
            "FI" => "+358",
            "FR" => "+33",
            "GA" => "+241",
            "GM" => "+220",
            "GE" => "+995",
            "DE" => "+49",
            "GH" => "+233",
            "GI" => "+350",
            "GR" => "+30",
            "GL" => "+299",
            "GD" => "+1-473",
            "GU" => "+1-671",
            "GT" => "+502",
            "GN" => "+224",
            "GW" => "+245",
            "GY" => "+592",
            "HT" => "+509",
            "HN" => "+504",
            "HK" => "+852",
            "HU" => "+36",
            "IS" => "+354",
            "IN" => "+91",
            "ID" => "+62",
            "IR" => "+98",
            "IQ" => "+964",
            "IE" => "+353",
            "IL" => "+972",
            "IT" => "+39",
            "JM" => "+1-876",
            "JP" => "+81",
            "JO" => "+962",
            "KZ" => "+7",
            "KE" => "+254",
            "KI" => "+686",
            "KP" => "+850",
            "KR" => "+82",
            "KW" => "+965",
            "KG" => "+996",
            "LA" => "+856",
            "LV" => "+371",
            "LB" => "+961",
            "LS" => "+266",
            "LR" => "+231",
            "LY" => "+218",
            "LI" => "+423",
            "LT" => "+370",
            "LU" => "+352",
            "MO" => "+853",
            "MK" => "+389",
            "MG" => "+261",
            "MW" => "+265",
            "MY" => "+60",
            "MV" => "+960",
            "ML" => "+223",
            "MT" => "+356",
            "MH" => "+692",
            "MR" => "+222",
            "MU" => "+230",
            "MX" => "+52",
            "FM" => "+691",
            "MD" => "+373",
            "MC" => "+377",
            "MN" => "+976",
            "ME" => "+382",
            "MS" => "+1-664",
            "MA" => "+212",
            "MZ" => "+258",
            "MM" => "+95",
            "NA" => "+264",
            "NR" => "+674",
            "NP" => "+977",
            "NL" => "+31",
            "NZ" => "+64",
            "NI" => "+505",
            "NE" => "+227",
            "NG" => "+234",
            "NU" => "+683",
            "NF" => "+672",
            "MP" => "+1-670",
            "NO" => "+47",
            "OM" => "+968",
            "PK" => "+92",
            "PW" => "+680",
            "PA" => "+507",
            "PG" => "+675",
            "PY" => "+595",
            "PE" => "+51",
            "PH" => "+63",
            "PL" => "+48",
            "PT" => "+351",
            "PR" => "+1-787",
            "QA" => "+974",
            "RO" => "+40",
            "RU" => "+7",
            "RW" => "+250",
            "WS" => "+685",
            "SM" => "+378",
            "ST" => "+239",
            "SA" => "+966",
            "SN" => "+221",
            "RS" => "+381",
            "SC" => "+248",
            "SL" => "+232",
            "SG" => "+65",
            "SK" => "+421",
            "SI" => "+386",
            "SB" => "+677",
            "SO" => "+252",
            "ZA" => "+27",
            "ES" => "+34",
            "LK" => "+94",
            "SD" => "+249",
            "SR" => "+597",
            "SZ" => "+268",
            "SE" => "+46",
            "CH" => "+41",
            "SY" => "+963",
            "TW" => "+886",
            "TJ" => "+992",
            "TZ" => "+255",
            "TH" => "+66",
            "TL" => "+670",
            "TG" => "+228",
            "TO" => "+676",
            "TT" => "+1-868",
            "TN" => "+216",
            "TR" => "+90",
            "TM" => "+993",
            "TV" => "+688",
            "UG" => "+256",
            "UA" => "+380",
            "AE" => "+971",
            "GB" => "+44",
            "US" => "+1",
            "UY" => "+598",
            "UZ" => "+998",
            "VU" => "+678",
            "VA" => "+39",
            "VE" => "+58",
            "VN" => "+84",
            "WF" => "+681",
            "EH" => "+212",
            "YE" => "+967",
            "ZM" => "+260",
            "ZW" => "+263",
        ];
        $iso2 = mb_strtoupper($iso2);
        if (\array_key_exists($iso2, $isdCodes)) {
            return $isdCodes[$iso2];
        } else {
            return null;
        }
    }
}
