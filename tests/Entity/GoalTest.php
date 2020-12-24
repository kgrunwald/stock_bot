<?php

namespace App\Tests\Entity;

use App\Entity\Goal;
use App\Entity\Order;
use App\Entity\Security;
use Exception;
use PHPUnit\Framework\TestCase;

class GoalTest extends TestCase
{
    public function testMultipleOrdersForSameSecurity() 
    {
        $tqqq = new Security();
        $tqqq->setSymbol('TQQQ');

        $g = new Goal();
        $o1 = new Order();
        $o1->setSecurity($tqqq);
        $g->addOrder($o1);

        $o2 = new Order();
        $o2->setSecurity($tqqq);

        try {
            $g->addOrder($o2);
        } catch (Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}