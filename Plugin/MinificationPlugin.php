<?php

namespace Dotdigitalgroup\Email\Plugin;

class MinificationPlugin
{
    /**
     * Exclude external js from minification
     *
     * @param \Magento\Framework\View\Asset\Minification $subject
     * @param callable $proceed
     * @param string $contentType
     *
     * @return array
     */
    public function aroundGetExcludes(
        \Magento\Framework\View\Asset\Minification $subject,
        callable $proceed,
        $contentType
    ) {
        $result = $proceed($contentType);

        //Content type can be css or js
        if ($contentType == 'js') {
            $result[] = 'trackedlink.net/_dmpt.js';
            $result[] = 'trackedlink.net/_dmmpt.js';
        }

        return $result;
    }
}
