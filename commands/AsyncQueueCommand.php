<?php

namespace yiicod\laravel5queue\commands;

use CConsoleCommand;
use Illuminate\Queue\Capsule\Manager;
use Yii;
use yiicod\laravel5queue\failed\MongoFailedJobProvider;
use yiicod\laravel5queue\handlers\ExceptionHandler;
use yiicod\laravel5queue\Worker;

/**
 * Command to start worker
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class AsyncQueueCommand extends CConsoleCommand
{
    /**
     * Process action
     *
     * @param $id
     * @param $connection
     */
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
        /** @var Manager $manager */
        $manager = Yii::app()->laravel5queue->getManager();

        // automatically send every new message to available log routes
        Yii::getLogger()->autoFlush = 1;
        // when sending a message to log routes, also notify them to dump the message
        // into the corresponding persistent storage (e.g. DB, email)
        Yii::getLogger()->autoDump = true;

        $worker = new Worker($manager->getQueueManager(), new MongoFailedJobProvider(Yii::app()->mongodb, 'YiiJobsFailed'));
        $worker->setDaemonExceptionHandler(new ExceptionHandler());
        return $worker;
    }

    /**
     * Process the job
     *
     * @param string $connectionName
     * @param $id
     *
     * @return array|null
     */
    protected function processJob(string $connectionName, $id)
    {
        $worker = $this->worker();
        $manager = $worker->getManager();
        $connection = $manager->connection($connectionName);

        $job = $connection->getJobFromId($id);

        // If we're able to pull a job off of the stack, we will process it and
        // then immediately return back out. If there is no job on the queue
        // we will "sleep" the worker for the specified number of seconds.
        if (!is_null($job)) {
            return $worker->process(
                $manager->getName($connectionName), $job, 1, 0
            );
        }

        return ['job' => null, 'failed' => false];
    }
}