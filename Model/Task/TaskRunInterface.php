<?php

namespace Dotdigitalgroup\Email\Model\Task;

interface TaskRunInterface
{
    /**
     * Run this task
     * @return void
     */
    public function run();
}
