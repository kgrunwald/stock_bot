<?php

namespace App\Tests\Service;

use App\Entity\Holding;
use App\Entity\Security;
use App\Repository\SecretRepository;
use App\Security\SecurityService;
use App\Service\BrokerService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BrokerServiceTest extends TestCase
{
    /**
     * @dataProvider orderToBuyAmountProvider
     */
    public function testOrderToBuyAmount($amount, $price, $qty, $limit)
    {
        $service = $this->getMockBuilder(BrokerService::class)->disableOriginalConstructor()->setMethods(['getCurrentAsk'])->getMock();
        
        $security = new Security();
        $security->setSymbol('TQQQ');
        $holding = new Holding();
        $holding->setSecurity($security);

        $service->expects($this->once())->method('getCurrentAsk')->with($security)->willReturn($price);
        $info = $service->getOrderToBuyAmount($holding, $amount);  
        
        $this->assertEquals($qty, $info['qty']);
        $this->assertEquals($limit, $info['limit']);
    }

    public function orderToBuyAmountProvider()
    {
        return [
            [10000, 1000, 10, 1000],
        ];
    }

    /**
     * @dataProvider orderToSellAmountProvider
     */
    public function testOrderToSellAmount($amount, $price, $qty, $limit)
    {
        $service = $this->getMockBuilder(BrokerService::class)->disableOriginalConstructor()->setMethods(['getCurrentBid'])->getMock();
        
        $security = new Security();
        $security->setSymbol('TQQQ');
        $holding = new Holding();
        $holding->setQuantity(100);
        $holding->setSecurity($security);

        $service->expects($this->once())->method('getCurrentBid')->with($security)->willReturn($price);
        $info = $service->getOrderToSellAmount($holding, $amount);  
        
        $this->assertEquals($qty, $info['qty']);
        $this->assertEquals($limit, $info['limit']);
    }

    public function orderToSellAmountProvider()
    {
        return [
            [111111, 14322, 8, 13889],
            [1000, 1000, 1, 1000],
            [900, 1000, 1, 900],
            [0, 1000, 0, 1000]
        ];
    }

    public function testOrderToSellAmountInsufficientQuantity()
    {
        $service = $this->getMockBuilder(BrokerService::class)->disableOriginalConstructor()->setMethods(['getCurrentBid'])->getMock();
        
        $security = new Security();
        $security->setSymbol('TQQQ');
        $holding = new Holding();
        $holding->setQuantity(1);
        $holding->setSecurity($security);

        $service->expects($this->once())->method('getCurrentBid')->with($security)->willReturn(14322);
        $info = $service->getOrderToSellAmount($holding, 111111);  
        
        $this->assertEquals(1, $info['qty']);
        $this->assertEquals(14322, $info['limit']);
    }

}