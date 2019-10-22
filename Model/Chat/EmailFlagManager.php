<?php

namespace Dotdigitalgroup\Email\Model\Chat;

use Magento\Framework\Flag as EmailFlag;
use Magento\Framework\Flag\FlagResource as EmailFlagResource;
use Magento\Framework\FlagFactory as EmailFlagFactory;

/**
 * Service that allows to handle a flag object as a scalar value.
 * Ported from Magento 2.2+ so we can support earlier Magento releases
 */
class EmailFlagManager
{
    /**
     * @var EmailFlagResource
     */
    private $emailFlagResource;

    /**
     * @var EmailFlagFactory
     * @see Flag
     */
    private $emailFlagFactory;

    /**
     * EmailFlagManager constructor
     * @param EmailFlagResource $flagResource
     * @param EmailFlagFactory $flagFactory
     */
    public function __construct(
        EmailFlagResource $flagResource,
        EmailFlagFactory $flagFactory
    ) {
        $this->emailFlagResource = $flagResource;
        $this->emailFlagFactory = $flagFactory;
    }

    /**
     * @param string $code The code of flag
     * @return string|int|float|bool|array|null
     */
    public function fetch($code)
    {
        return $this->getEmailFlagObject($code)->getFlagData();
    }

    /**
     * @param string $code The code of flag
     * @param string|int|float|bool|array|null $value The value of flag
     * @return bool
     */
    public function save($code, $value)
    {
        $flag = $this->getEmailFlagObject($code);
        $flag->setFlagData($value);
        $this->emailFlagResource->save($flag);
        return true;
    }

    /**
     * @param string $code The code of flag
     * @return bool
     */
    public function delete($code)
    {
        $flag = $this->getEmailFlagObject($code);
        if ($flag->getId()) {
            $this->emailFlagResource->delete($flag);
        }
        return true;
    }

    /**
     * Returns flag object
     *
     * @param string $code
     * @return EmailFlag
     */
    private function getEmailFlagObject($code)
    {
        /** @var EmailFlag $flag */
        $this->emailFlagResource->load(
            $flag = $this->emailFlagFactory->create(['data' => ['flag_code' => $code]]),
            $code,
            'flag_code'
        );
        return $flag;
    }
}
