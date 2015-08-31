<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Transactional;

class Yesno
{
	protected $_helper;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data
	)
	{
		$this->_helper  = $data;
	}

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $status = $this->_helper->isSmtpEnabled();
        if (!$status) {
            return array(
                array('value' => 0, 'label'=> __('No')),
            );
        } else {
	        return array(
		        array('value' => 0, 'label' => __('No')),
		        array('value' => 1, 'label' => __('Yes'))
	        );
        }
    }

}