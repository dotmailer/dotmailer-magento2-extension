<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

class AutomationData
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * Set id.
     *
     * This is the row id from email_automation.
     *
     * @param int $id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return void
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
