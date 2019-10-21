<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Chat;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Backend\Model\UrlInterface;

class ChatComment implements CommentInterface
{
    /**
     * @var UrlInterface
     */
    private $_backendUrl;

    /**
     * ChatComment constructor.
     * @param UrlInterface $_backendUrl
     */
    public function __construct(
        UrlInterface $_backendUrl
    ) {
        $this->_backendUrl = $_backendUrl;
    }

    /**
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue)
    {
        $redirect = $this->_backendUrl->getUrl("dotdigitalgroup_email/chat");
        return  __("Don't have a chat account? Create a free account <a href='$redirect'>here</a>");
    }
}
