<?php

namespace Weiming\Events;

class TestEvent
{
    public static function onFailure($exception, $job)
    {
        $obj     = $job->getInstance();
        $jobData = $job->getArguments();
        var_dump($obj);
        var_dump($jobData);
    }
}
