<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Accounts;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Dotdigitalgroup\Email\Model\Integration\AccountDetails;

class AccountInfo extends AbstractElement
{
    /**
     * @var AccountDetails
     */
    private $accountDetails;

    /**
     * Account info constructor.
     *
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param AccountDetails $accountDetails
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        AccountDetails $accountDetails,
        array $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->accountDetails = $accountDetails;
    }

    /**
     * Get html.
     *
     * @return mixed|string
     */
    public function getHtml()
    {
        return $this->getDefaultHtml();
    }

    /**
     * Get the default html.
     *
     * @return mixed
     */
    public function getDefaultHtml()
    {
        $html = $this->getData('default_html');
        if ($html === null) {
            $html .= $this->getElementHtml();
        }
        return $html;
    }

    /**
     * Get element html.
     *
     * @return string
     */
    public function getElementHtml()
    {
        if (!$this->accountDetails->isEnabled()) {
            return '';
        }

        $accountInfo = $this->accountDetails->getAccountInfo();
        if ($this->accountDetails->getIsConnected()) {
            $accountDetails = sprintf(
                '<strong> %s (region %s) </strong>',
                $accountInfo["email"],
                $accountInfo["region"]
            );

            return
                '<div class="message ddg-account-details ddg-connected">
                    <span>'
                .__("This website is currently connected to the Dotdigital account:").$accountDetails.'
                    </span>
                </div>';
        } else {
            return
                '<div class="message ddg-account-details ddg-disconnected">
                    <span>'
                .__("The connection between this website and Dotdigital is currently failing.").'
                    </span>
                </div>';
        }
    }
}
