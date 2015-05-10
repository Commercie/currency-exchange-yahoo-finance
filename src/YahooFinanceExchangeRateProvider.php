<?php

/**
 * @file
 * Contains \BartFeenstra\CurrencyExchangeYahooFinance\YahooFinanceExchangeRateProvider.
 */

namespace BartFeenstra\CurrencyExchangeYahooFinance;

use BartFeenstra\CurrencyExchange\ExchangeRate;
use BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Retrieves currency exchange rates from Yahoo! Finance.
 */
class YahooFinanceExchangeRateProvider implements ExchangeRateProviderInterface
{

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $logger;

    /**
     * Constructs a new instance.
     *
     * @param \GuzzleHttp\ClientInterface $httpClient
     *   The HTTP client.
     * @param \Psr\Log\LoggerInterface|null
     *   The logger or NULL.
     */
    public function __construct(
      ClientInterface $httpClient,
      LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function load($sourceCurrencyCode, $destinationCurrencyCode)
    {
        $rates = $this->loadMultiple([
          $sourceCurrencyCode => [$destinationCurrencyCode],
        ]);

        return $rates[$sourceCurrencyCode][$destinationCurrencyCode];
    }

    public function loadMultiple(array $currencyCodes)
    {
        $rates = [];
        foreach ($currencyCodes as $sourceCurrencyCode => $destinationCurrencyCodes) {
            foreach ($destinationCurrencyCodes as $destinationCurrencyCode) {
                $rates[$sourceCurrencyCode][$destinationCurrencyCode] = $this->request($sourceCurrencyCode,
                  $destinationCurrencyCode);
            }
        }

        return $rates;
    }

    /**
     * Requests a rate from the Yahoo! Finance API.
     *
     * @param string $sourceCurrencyCode
     * @param string $destinationCurrencyCode
     *
     * @return string|null
     *   A numeric string, or NULL if no rate was available or the response
     *   could not be parsed.
     */
    protected function request($sourceCurrencyCode, $destinationCurrencyCode)
    {
        if ($this->isSupportedCurrency($sourceCurrencyCode) && $this->isSupportedCurrency($destinationCurrencyCode)) {
            try {
                $response = $this->httpClient->get(sprintf('http://download.finance.yahoo.com/d/quotes.csv?e=.csv&f=sl1&s=%s%s=X',
                  $sourceCurrencyCode, $destinationCurrencyCode));
                $exchangeRate = $this->parseResponse($response->getBody());
                return $exchangeRate ? ExchangeRate::create($sourceCurrencyCode,
                  $destinationCurrencyCode, $exchangeRate)
                  ->setTimestamp(time()) : null;
            } catch (RequestException $e) {
                if ($this->logger) {
                    $this->logger->error(sprintf('The request to the Yahoo! Finance server failed. Reason: %s.',
                      $e->getMessage()));
                }
            }
        }
        return null;
    }

    /**
     * Checks if a currency is supported.
     *
     * @param string $currencyCode
     *
     * @return bool
     *   TRUE if the currency is supported.
     */
    protected function isSupportedCurrency($currencyCode)
    {
        return in_array(strtoupper($currencyCode),
          $this->getSupportedCurrencies());
    }

    /**
     * Returns the codes of all currencies that Yahoo! Finance offers rates for.
     *
     * @return string[]
     *   ISO 4217 currency codes.
     */
    protected function getSupportedCurrencies()
    {
        static $currencyCodes = [
          'AUD',
          'ALL',
          'DZD',
          'XAL',
          'ARS',
          'AWG',
          'GBP',
          'BHD',
          'BBD',
          'BZD',
          'BTN',
          'BWP',
          'BND',
          'BIF',
          'BSD',
          'BDT',
          'BYR',
          'BMB',
          'BOB',
          'BRL',
          'BGN',
          'CAD',
          'KHR',
          'KYD',
          'XAF',
          'COP',
          'XCP',
          'HRK',
          'CZK',
          'CNY',
          'CVE',
          'XOF',
          'CLP',
          'MKF',
          'CRC',
          'CUP',
          'EUR',
          'DJF',
          'XCD',
          'EGP',
          'ERN',
          'ETB',
          'FJD',
          'DKK',
          'DOP',
          'ECS',
          'SVC',
          'EEK',
          'FKP',
          'HKD',
          'INR',
          'GHC',
          'XAU',
          'GNF',
          'HTG',
          'HUF',
          'IRR',
          'ILS',
          'IDR',
          'GMD',
          'GIP',
          'GTQ',
          'GYD',
          'HNL',
          'ISK',
          'IQD',
          'JPY',
          'JOD',
          'KES',
          'LAK',
          'LBP',
          'LRD',
          'LTL',
          'JMD',
          'KZT',
          'KWD',
          'LVL',
          'LSL',
          'LYD',
          'MOP',
          'MWK',
          'MVR',
          'MRO',
          'MXN',
          'MNT',
          'MMK',
          'MKD',
          'MYR',
          'MTL',
          'MUR',
          'MDL',
          'MAD',
          'NAD',
          'ANG',
          'NIO',
          'KPW',
          'OMR',
          'NPR',
          'NZD',
          'NGN',
          'NOK',
          'XPF',
          'XPD',
          'PGK',
          'PEN',
          'XPT',
          'QAR',
          'RUB',
          'PKR',
          'PAB',
          'PYG',
          'PHP',
          'PLN',
          'RON',
          'RWF',
          'CHF',
          'WST',
          'SAR',
          'SLL',
          'SGD',
          'SIT',
          'SOS',
          'LKR',
          'SDG',
          'SEK',
          'KRW',
          'STD',
          'SCR',
          'XAG',
          'SKK',
          'SBD',
          'ZAR',
          'SHP',
          'SZL',
          'SYP',
          'USD',
          'TRY',
          'TZS',
          'TTD',
          'AED',
          'UAH',
          'THB',
          'TWD',
          'TOP',
          'TND',
          'UGX',
          'UYU',
          'VUV',
          'VND',
          'ZMK',
          'VEF',
          'YER',
          'ZWD',
        ];

        return $currencyCodes;
    }

    /**
     * Parses a response from the Yahoo! Finance server.
     *
     * @param string $response
     *
     * @return string|null
     *   A numeric string, or NULL if the response could not be parsed.
     */
    protected function parseResponse($response)
    {
        // The response is a CSV string, of which the first column is the
        // original query, and the second the exchange rate.
        $responseFragments = explode(',', $response);
        if (isset($responseFragments[1])) {
            // The message may contain newlines.
            $amount = trim($responseFragments[1]);
            // A rate of 0 means the rate could not be found.
            if (is_numeric($amount) && $amount > 0) {
                return $amount;
            }
        }
        return null;
    }

}
