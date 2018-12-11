<?php

namespace Weiming\Jobs;

use Weiming\Jobs\BaseJob;
use Resque_Event;
use Resque_Exception;

/**
 * 测试 Job
 */
class TestJob extends BaseJob
{
    public function perform()
    {
        // print_r($this->args);
        Resque_Event::trigger('onFailure');
    }
}
