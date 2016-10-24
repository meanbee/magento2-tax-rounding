<?php

namespace Meanbee\TaxRounding\Test\Unit\Model;

class PriceCurrencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Meanbee\TaxRounding\Model\PriceCurrency
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyFactory;

    public function setUp()
    {
        $this->storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyFactory = $this->getMockBuilder('Magento\Directory\Model\CurrencyFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->priceCurrency = $objectManager->getObject('Meanbee\TaxRounding\Model\PriceCurrency', [
            'storeManager' => $this->storeManager,
            'currencyFactory' => $this->currencyFactory
        ]);
    }
    protected function getCurrentCurrencyMock()
    {
        $currency = $this->getMockBuilder('Magento\Directory\Model\Currency')
            ->disableOriginalConstructor()
            ->getMock();

        return $currency;
    }

    protected function getStoreMock($baseCurrency)
    {
        $store = $this->getMockBuilder('Magento\Store\Model\Store')
            ->disableOriginalConstructor()
            ->getMock();

        $store->expects($this->atLeastOnce())
            ->method('getBaseCurrency')
            ->will($this->returnValue($baseCurrency));

        return $store;
    }

    protected function getBaseCurrencyMock($amount, $convertedAmount, $currency)
    {
        $baseCurrency = $this->getMockBuilder('Magento\Directory\Model\Currency')
            ->disableOriginalConstructor()
            ->getMock();

        $baseCurrency->expects($this->once())
            ->method('convert')
            ->with($amount, $currency)
            ->will($this->returnValue($convertedAmount));

        return $baseCurrency;
    }

    /**
     * Confirm rounding to 3 decimal places works as expected.
     * @dataProvider pricingProvider
     * @test
     *
     */
    public function testConvertAndRound($amount, $convertedAmount, $roundedConvertedAmount)
    {
        $storeCode = 2;

        $currency = $this->getCurrentCurrencyMock();
        $baseCurrency = $this->getBaseCurrencyMock($amount, $convertedAmount, $currency);
        $store = $this->getStoreMock($baseCurrency);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeCode)
            ->will($this->returnValue($store));

        $this->assertEquals(
            $roundedConvertedAmount,
            $this->priceCurrency->convertAndRound($amount, $storeCode, $currency)
        );
    }

    /**
     * data provider for testConvertAndRound.
     * @return array
     */
    public function pricingProvider()
    {
        return [
            [
                5.6, // base amount
                9.3262, // amount once converted
                9.326 // rounded converted amount.
            ],
            [
                8.325, // 9.99 / 1.2 (VAT)
                8.325,
                8.325 // Expected to not to change.
            ],
            [
                7.491666667, // 8.99 / 1.2 (VAT)
                7.491666667,
                7.492 // Expect to be rounded to 3 decimal places
            ],
            [
                16.658333333, // 19.99 / 1.2 (VAT)
                16.658333333,
                16.658
            ],
            [
                33.316666667, // 39.98 / 1.2 (VAT) (i.e. 2 x 19.99)
                33.316666667,
                33.317 // 33.317 * 1.2 = 39.9804 (rounded: 39.98)
            ],
            [
                21.625, // 25.95 / 1.2 (VAT)
                21.625,
                21.625
            ]
        ];
    }

}
