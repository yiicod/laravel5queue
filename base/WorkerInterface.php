<?php

namespace yiicod\laravel5queue\base;

/**
 * Interface WorkerInterface
 * Main interface for workers
 *
 * @package yiicod\laravel5queue\base
 */
interface WorkerInterface
{
    /**
     * Start worker
     *
     * @param $connection
     * @param $queue
     *
     * @return mixed
     */
    public function actionStart($connection, $queue);

    /**
     * Stop worker
     *
     * @param $connection
     * @param $queue
     *
     * @return mixed
     */
    public function actionStop($connection, $queue);

}