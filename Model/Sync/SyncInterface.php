<?php

namespace Dotdigitalgroup\Email\Model\Sync;

interface SyncInterface
{
    /**
     * Run this sync
     * @return void
     */
    public function sync();
}