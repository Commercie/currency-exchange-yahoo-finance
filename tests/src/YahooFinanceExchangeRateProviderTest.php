<?php

/**
 * @file Contains \Commercie\Tests\CurrencyExchange\AbstractStackedExchangeRateProviderTest.
 */

namespace Commercie\Tests\CurrencyExchangeYahooFinance;

use Commercie\CurrencyExchangeYahooFinance\YahooFinanceExchangeRateProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass \Commercie\CurrencyExchangeYahooFinance\YahooFinanceExchangeRateProvider
 */
class YahooFinanceExchangeRateProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\Client|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpClient;

    /**
     * The mock HTTP client handler.
     *
     * @var \GuzzleHttp\Handler\MockHandler
     */
    protected $httpClientHandler;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * The class under test.
     *
     * @var \Commercie\CurrencyExchangeYahooFinance\YahooFinanceExchangeRateProvider
     */
    protected $sut;

    public function setUp()
    {
        $this->httpClientHandler = new MockHandler();

        $this->httpClient = new Client([
            'handler' => $this->httpClientHandler,
        ]);

        $this->logger = $this->getMock('\Psr\Log\LoggerInterface');

        $this->sut = new YahooFinanceExchangeRateProvider($this->httpClient,
          $this->logger);
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new YahooFinanceExchangeRateProvider($this->httpClient, $this->logger);
    }

    /**
     * @covers ::load
     * @covers ::getSupportedCurrencies
     * @covers ::isSupportedCurrency
     */
    public function testLoadWithUnsupportedCurrencies()
    {
        $this->assertNull($this->sut->load('FOO', 'BAR'));
    }

    /**
     * @covers ::loadMultiple
     * @covers ::getSupportedCurrencies
     * @covers ::isSupportedCurrency
     */
    public function testLoadMultipleWithUnsupportedCurrencies()
    {
        $exchangeRates = $this->sut->loadMultiple([
          'FOO' => ['BAR', 'BAZ'],
        ]);
        $expectedExchangeRates = [
          'FOO' => [
            'BAR' => null,
            'BAZ' => null,
          ],
        ];

        $this->assertSame($expectedExchangeRates, $exchangeRates);
    }

    /**
     * @covers ::load
     * @covers ::loadMultiple
     * @covers ::request
     * @covers ::parseResponse
     *
     * @dataProvider provideLoadWithInvalidResponse
     *
     * @param string $responseBody
     *   The HTTP response body.
     */
    public function testLoadWithInvalidResponse($responseBody)
    {
        $httpResponse = $this->getMock(ResponseInterface::class);
        $httpResponse->expects($this->atLeastOnce())
          ->method('getBody')
          ->willReturn($responseBody);

        $this->httpClientHandler->append($httpResponse);

        $this->assertNull($this->sut->load('EUR', 'UAH'));
    }

    /**
     * Provides data to self::testLoadWithInvalidResponse().
     */
    public function provideLoadWithInvalidResponse()
    {
        return [
            // Incorrectly formatted responses.
          ['"EURUSP=X",fooBar'],
          ['"BAZ'],
          ['1234567890'],
          [''],
            // The exchange rate is not available.
          ['"EURUSP=X",N/A'],
        ];
    }

    /**
     * @covers ::load
     * @covers ::loadMultiple
     * @covers ::request
     */
    public function testLoadWithRequestException()
    {
        $requestException = $this->getMockBuilder('\GuzzleHttp\Exception\RequestException')
          ->disableOriginalConstructor()
          ->getMock();

        $this->httpClientHandler->append($requestException);

        $this->assertNull($this->sut->load('EUR', 'UAH'));
    }

    /**
     * @covers ::load
     * @covers ::loadMultiple
     * @covers ::request
     * @covers ::parseResponse
     */
    public function testLoadWithAvailableExchangeRate()
    {
        $expectedExchangeRate = sprintf('%s.%s', mt_rand(), mt_rand());

        $httpResponse = $this->getMock(ResponseInterface::class);
        $httpResponse->expects($this->atLeastOnce())
          ->method('getBody')
          ->willReturn(sprintf('"EURUSP=X",%s', $expectedExchangeRate));

        $this->httpClientHandler->append($httpResponse);

        $exchangeRate = $this->sut->load('EUR', 'UAH');

        $this->assertInstanceOf('\Commercie\CurrencyExchange\ExchangeRateInterface',
          $exchangeRate);
        $this->assertSame($expectedExchangeRate, $exchangeRate->getRate());
    }

}
