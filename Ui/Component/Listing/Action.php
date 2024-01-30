<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface as UrlBuilder;

class Action extends Column
{
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilder $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilder $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['id'])) {
                    $item[$name . '_html'] = "<button class='button'><span>".__("Show data")."</span></button>";
                    $item[$name . '_title'] = __('Message body');
                    $item[$name . '_entity_id'] = $item['id'];
                    $item[$name . '_messageUrl'] = $this->urlBuilder->getUrl(
                        'dotdigitalgroup_email/queue/message',
                        ['message_id' => (string) $item['id']]
                    );
                }
            }
        }

        return $dataSource;
    }
}
