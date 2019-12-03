<?php

namespace Dotdigitalgroup\Email\Test\Integration;

use Magento\TestFramework\ObjectManager;
use Magento\SalesRule\Model\Rule;

trait LoadsSaleRule
{
    /**
     * @var Rule
     */
    private $salesRule;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule
     */
    private $ruleResource;

    private function loadSalesRule()
    {
        if ($this->salesRule) {
            return $this->salesRule;
        }

        $objectManager = ObjectManager::getInstance();
        $this->salesRule = $objectManager->create(Rule::class);
        $this->ruleResource = $objectManager->create(\Magento\SalesRule\Model\ResourceModel\Rule::class);
        $this->ruleResource->load($this->salesRule, 1);

        return $this->salesRule;
    }
}
