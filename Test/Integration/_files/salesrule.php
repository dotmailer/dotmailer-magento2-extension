<?php

declare(strict_types=1);

use Magento\Customer\Model\GroupManagement;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Rule $salesRule */
$salesRule = $objectManager->create(Rule::class);
$salesRule->setData(
    [
        'rule_id' => 1,
        'name' => '1 Chaz Kangaroo hoodie free when you using the code RBV',
        'is_active' => 1,
        'customer_group_ids' => [GroupManagement::NOT_LOGGED_IN_ID],
        'coupon_type' => Rule::COUPON_TYPE_AUTO,
        'conditions' => [
            [
                'type' => \Magento\SalesRule\Model\Rule\Condition\Address::class,
                'attribute' => 'base_subtotal',
                'operator' => '>',
                'value' => 45,
            ],
        ],
        'simple_action' => Rule::CART_FIXED_ACTION,
        'discount_amount' => 15,
        'discount_step' => 0,
        'use_auto_generation' => 1,
        'stop_rules_processing' => 1,
        'website_ids' => [
            $objectManager->get(StoreManagerInterface::class)->getWebsite()->getId(),
        ],
        'store_labels' => [
            'store_id' => 0,
            'store_label' => 'TestRule_Coupon',
        ]
    ]
);
$objectManager->get(\Magento\SalesRule\Model\ResourceModel\Rule::class)->save($salesRule);
