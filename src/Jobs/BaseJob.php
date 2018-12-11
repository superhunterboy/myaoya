<?php

namespace Weiming\Jobs;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;

/**
 * 自动充值 Job
 */
class BaseJob
{
    public $settings = [];
    public $capsule  = null;

    public function setUp()
    {
        // Set up environment for this job
        $this->settings = require __DIR__ . '/../../config/settings.php';
        // Eloquent ORM
        $this->capsule = new Capsule;
        $this->capsule->addConnection($this->settings['db']);
        $this->capsule->setEventDispatcher(new Dispatcher(new Container));
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    public function perform()
    {
        // Run job
    }

    public function tearDown()
    {
        // Remove environment for this job
    }
}
