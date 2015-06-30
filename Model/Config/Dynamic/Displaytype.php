<?php

namespace Dotdigitalgroup\Email\Model\Config\Dynamic;

class Displaytype
{
	/**
	 * Display type mode.
	 *
	 * @return array
	 */
	public function toOptionArray()
    {
        return array(
            array('value' => 'grid', 'label' => 'Grid'),
            array('value' => 'list', 'label' => 'List')
        );

    }
}