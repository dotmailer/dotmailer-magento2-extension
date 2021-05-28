<?php

namespace Dotdigitalgroup\Email\Model;

class Resetter
{
    /**
     * Reset Models are defined in di.xml
     * @var array
     */
    private $resetModels = [];

    /**
     * Resetter constructor.
     * @param array $resetModels
     */
    public function __construct(array $resetModels = [])
    {
        $this->setResetModels($resetModels);
    }

    /**
     * @param null|string $from
     * @param null|string $to
     * @param string $resetType
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function reset(?string $from, ?string $to, string $resetType)
    {
        $resetTypes = array_keys($this->resetModels);
        if (!in_array($resetType, $resetTypes)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid reset type.')
            );
        }

        return $this->resetModels[$resetType]->reset($from, $to);
    }

    /**
     * @return void
     */
    public function setResetModels($resetModels)
    {
        $this->resetModels = $resetModels;
    }
}
