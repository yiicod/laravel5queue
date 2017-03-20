<?php

namespace yiicod\laravel5queue\handlers;

use Illuminate\Queue\Jobs\Job;
use Yii;
use yiicod\laravel5queue\base\BaseHandlerInterface;

/**
 * Handler for queue jobs
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
abstract class HandlerAbstract implements BaseHandlerInterface
{
    /**
     * Run job with restarting connection
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     *
     * @param Job $job
     * @param array $data
     */
    public function fire(Job $job, array $data)
    {
        Yii::app()->db->setActive(false);
        Yii::app()->db->setActive(true);
    }
}
