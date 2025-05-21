<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

use Magento\Framework\Serialize\SerializerInterface;

class PriceRuleData
{
    /**
     * @var string|null
     */
    private $old_rule;

    /**
     * @var string
     */
    private $new_rule;

    /**
     * Set rule data.
     *
     * @param string|null $old_rule
     * @return void
     */
    public function setOldRule(?string $old_rule): void
    {
        $this->old_rule = $old_rule;
    }

    /**
     * Get rule data.
     *
     * @return string|null
     */
    public function getOldRule(): ?string
    {
        return $this->old_rule;
    }

    /**
     * Set rule new rule data.
     *
     * @param string $new_rule
     * @return void
     */
    public function setNewRule(string $new_rule): void
    {
        $this->new_rule = $new_rule;
    }

    /**
     * Get rule data.
     *
     * @return string
     */
    public function getNewRule(): string
    {
        return $this->new_rule;
    }
}
