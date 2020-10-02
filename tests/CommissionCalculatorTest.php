<?php

namespace App\Tests;

use App\Helper\CommissionCalculatorHelper;
use App\Helper\DataReceiverHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CommissionCalculatorTest extends KernelTestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct( $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

    }
    protected function setUp()
    {
        static::bootKernel();
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function testCommissionCalculation(){
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var MockObject|CommissionCalculatorHelper $commissionCommandMock */
        /** @var DataReceiverHelper|MockObject $dataReceiverMock */
        $dataReceiverMock = $this->createMock(DataReceiverHelper::class);
        $ratesList = $this->serializer->decode('{"rates":{"CAD":1.5616,"HKD":9.1082,"ISK":162.2,"PHP":56.931,"DKK":7.4421,"HUF":359.89,"CZK":26.918,"AUD":1.6319,"RON":4.874,"SEK":10.4853,"IDR":17423.28,"INR":85.8275,"BRL":6.5821,"RUB":90.605,"HRK":7.563,"JPY":123.98,"THB":37.101,"CHF":1.0787,"SGD":1.6004,"PLN":4.4935,"BGN":1.9558,"TRY":9.0606,"CNY":7.9805,"NOK":10.9073,"NZD":1.7667,"ZAR":19.5373,"USD":1.1752,"MXN":25.6764,"ILS":4.0236,"GBP":0.90723,"KRW":1365.03,"MYR":4.8788},"base":"EUR","date":"2020-10-01"}', 'json')['rates'];
        $dataReceiverMock->expects($this->once())->method('getDecodedBINCountry')->willReturn('LT');
        $transactionRow = '{"bin":"516793","amount":"50.00","currency":"USD"}';
        $this->assertEquals(0.43,(new CommissionCalculatorHelper($dataReceiverMock))->calculateCommission($transactionRow, $ratesList));
    }
}
