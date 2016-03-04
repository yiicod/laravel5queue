<?php

namespace yiicod\laravel5queue\handlers;

use Yii;
use yiicod\laravel5queue\base\BaseHandlerInterface;

/**
 * Handler for queue jobs
 * 
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
abstract class Handler implements BaseHandlerInterface
{

    /**
     * Run job with restarting connection
     * 
     * @author Virchenko Maksim <muslim1992@gmail.com>
     * 
     * @param type $job
     * @param type $data
     */
    public function fire($job, $data)
    {
        Yii::app()->db->setActive(false);
        Yii::app()->db->setActive(true);
    }

}
