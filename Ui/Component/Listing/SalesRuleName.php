<?php

namespace Dotdigitalgroup\Email\Ui\Component\Listing;

use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use RuntimeException;

class SalesRuleName extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param RuleRepositoryInterface $ruleRepository
     * @param LayoutInterface $layout
     * @param Logger $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        RuleRepositoryInterface $ruleRepository,
        LayoutInterface $layout,
        Logger $logger,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->ruleRepository = $ruleRepository;
        $this->layout = $layout;
        $this->logger = $logger;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['sales_rule_id']) && $item['sales_rule_id']) {
                    // Render the phtml template and inject it into the data source
                    $item[$fieldName] = $this->renderTemplate((int)$item['sales_rule_id']);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Render template with sales rule information
     *
     * @param int $salesRuleId
     * @return string
     */
    private function renderTemplate(int $salesRuleId): string
    {
        $templateData = [
            'rule_name' => null,
            'edit_url' => null,
            'rule_not_found' => false,
            'sales_rule_id' => $salesRuleId,
        ];

        try {
            $rule = $this->ruleRepository->getById($salesRuleId);

            $templateData['rule_name'] = $rule->getName();
            $templateData['edit_url'] = $this->urlBuilder->getUrl(
                'sales_rule/promo_quote/edit',
                ['id' => $salesRuleId]
            );
        } catch (NoSuchEntityException $e) {
            $templateData['rule_not_found'] = true;
        } catch (\Exception $e) {
            $this->logger->debug('Failed to load sales rule: ' . $e->getMessage());
        }

        /** @var Template $block */
        $block = $this->layout->createBlock(Template::class, '', [
            'data' => [
                'template' => 'Dotdigitalgroup_Email::coupon-job/grid/job-sales-rule-name.phtml'
            ]
        ]);
        $block->setData($templateData);

        return $block->toHtml();
    }
}
