<?php

namespace yiicod\laravel5queue\base;

/**
 * Interface WorkerInterface
 * @package yiicod\laravel5queue\base\
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
    public function actionStart(string $connection, string $queue);

    /**
     * Stop worker
     *
     * @param $connection
     * @param $queue
     *
     * @return mixed
     */
    public function actionStop(string $connection, string $queue);
}