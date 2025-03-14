<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Class UrlRewriteSavePlugin - reset product in email_catalog when url rewrite is saved for product.
 */
class UrlRewriteSavePlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

    /**
     * UrlRewriteSavePlugin constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
    ) {
        $this->request = $request;
        $this->catalogResource = $catalogResource;
    }

    /**
     * After execute.
     *
     * @param \Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite\Save $subject
     * @param void $result
     *
     * @return void
     */
    public function afterExecute(
        \Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite\Save $subject,
        $result
    ) {
        $productId = (int)$this->request->getParam('product', 0);
        if ($productId) {
            $this->catalogResource->setUnprocessedByIds([$productId]);
        }
        return $result;
    }
}
