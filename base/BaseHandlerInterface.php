<?php

namespace yiicod\laravel5queue\base;

/**
 * Base interface for handlers
 * 
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
interface BaseHandlerInterface
{

    /**
     * Run command from queue
     * 
     * @author Virchenko Maksim <muslim1992@gmail.com>
     * 
     * @param Job $job
     * @param array $data
     */
    public function fire($job, $data);
}
