<?php

namespace yiicod\laravel5queue\commands;

use CConsoleCommand;
use Symfony\Component\Process\Process;
use Yii;
use yiicod\laravel5queue\failed\MongoFailedJobProvider;
use yiicod\laravel5queue\handlers\DaemonExceptionHandler;
use yiicod\laravel5queue\jobs\MongoJob;
use yiicod\laravel5queue\Worker;

/**
 * Command to start worker
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class AsyncQueueCommand extends CConsoleCommand
{
    public function actionProcess($id, $connection)
    {
        $this->processJob(
            $connection, $id
        );
    }

    /**
     * Run queue worker
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     */
    protected function worker()
    {
        $queueManager = Yii::app()->laravel5queue->connect();

        $worker = new Worker($queueManager->getQueueManager(), new MongoFailedJobProvider(Yii::app()->mongodb, 'YiiJobsFailed'));
        $worker->setDaemonExceptionHandler(new DaemonExceptionHandler());
        return $worker;
    }

    /**
     *  Process the job
     *
     */
    protected function processJob($connectionName, $id)
    {
        $worker = $this->worker();
        $manager = $worker->getManager();
        $connection = $manager->connection($connectionName);

        $job = $connection->getJobFromId($id);

        // If we're able to pull a job off of the stack, we will process it and
        // then immediately return back out. If there is no job on the queue
        // we will "sleep" the worker for the specified number of seconds.
        if (!is_null($job)) {
//            $sleep = max($job->getDatabaseJob()->available_at - time(), 0);var_dump($sleep);die;
//            sleep($sleep);
            return $worker->process(
                $manager->getName($connectionName), $job, 1, 0
            );
        }
        return ['job' => null, 'failed' => false];
    }
}