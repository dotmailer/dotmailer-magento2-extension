<?php

namespace Dotdigitalgroup\Email\Model\Connector;

class KeyValidator
{
    const ENGAGEMENT_CLOUD_PERMISSIBLE_KEY_PATTERN = '/[^A-Za-z0-9-_]/';

    /**
     * @param string $label
     * @param string $replaceSpaceWith
     * @param string $replaceCharactersWith
     * @param string $suffix
     *
     * @return string
     */
    public function cleanLabel($label, $replaceSpaceWith = '_', $replaceCharactersWith = '', $suffix = null)
    {
        $label = str_replace(
            ' ',
            $replaceSpaceWith,
            $label
        );
        if ($this->hasInvalidPatternForInsightDataKey($label)) {
            $label = $this->stripInvalidCharactersAndIdentify($label, $replaceCharactersWith, $suffix);
        }
        return $label;
    }

    /**
     * @param string $label
     *
     * https://support.dotdigital.com/hc/en-gb/articles/212214538-Using-Insight-data-developers-guide-#restrictkeys
     *
     * @return false|int
     */
    public function hasInvalidPatternForInsightDataKey($label)
    {
        return preg_match(self::ENGAGEMENT_CLOUD_PERMISSIBLE_KEY_PATTERN, $label);
    }

    /**
     * Remove invalid characters and append a suffix to avoid possible key collisions.
     *
     * @param string $label
     * @param string $replaceSpaceWith
     * @param string $suffix
     *
     * @return string
     */
    private function stripInvalidCharactersAndIdentify($label, $replaceSpaceWith, $suffix)
    {
        $safeLabel = preg_replace(self::ENGAGEMENT_CLOUD_PERMISSIBLE_KEY_PATTERN, '', $label);
        return $safeLabel.$replaceSpaceWith.$suffix;
    }
}
