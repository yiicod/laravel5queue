<?php
namespace yiicod\laravel5queue\base;

interface WorkerInterface
{
    public function actionStart($connection, $queue/*, $force*/);

    public function actionStop($connection, $queue);
    
}