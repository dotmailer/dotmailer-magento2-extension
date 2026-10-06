<?php

namespace Dotdigitalgroup\Email\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Search\Helper\Data as SearchHelper;

/**
 * @deprecated The Tag captures Search data via dotdigital_product_list_capture.phtml
 * @see \Dotdigitalgroup\Email\ViewModel/DotdigitalSearchCaptureView
 */
class DotdigitalSearchCaptureView implements ArgumentInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SearchHelper
     */
    private $searchHelper;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param SearchHelper $searchHelper
     */
    public function __construct(
        RequestInterface $request,
        SearchHelper $searchHelper
    ) {
        $this->request = $request;
        $this->searchHelper = $searchHelper;
    }

    /**
     * Get search input value
     *
     * @return string|null
     */
    public function getSearchQuery(): ?string
    {
        $queryParam = $this->searchHelper->getQueryParamName();
        return $this->request->getParam($queryParam);
    }
}
