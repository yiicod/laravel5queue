<?php

namespace yiicod\laravel5queue\commands;

use CConsoleCommand;
use CLogger;
use Exception;
use Yii;
use yiicod\laravel5queue\failed\MongoFailedJobProvider;
use yiicod\laravel5queue\Worker;

/**
 * Command to start worker
 * 
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class WorkerCommand extends CConsoleCommand
{

    /**
     * Delay before getting jobs
     *
     * @var integer
     */
    public $delay = 0;

    /**
     * Maximum memory usage
     *
     * @var integer
     */
    public $memory = 128;

    /**
     * Sleep before getting new jobs
     *
     * @var integer
     */
    public $sleep = 3;

    /**
     * Max tries to run job
     *
     * @var integer
     */
    public $maxTries = 1;

    /**
     * @var string Daemon display name
     */
    private $daemonName = 'laravel5queue-daemon';

    /**
     * @var string PID file name
     */
    private $pidFile = 'laravel5queue.pid';

    /**
     * @var Worker daemon
     */
    private $worker = null;

    /**
     * Default action. Starts daemon.
     */
    public function actionStart()
    {
        $this->createDaemon();
    }

    /**
     * Stops daemon.
     *
     * Close server and close all connections.
     */
    public function actionStop()
    {
        if (false === $this->isAlreadyRunning()) {
            echo sprintf("\n[%s] is not running.\n", $this->daemonName);
            exit();
        }

        try {
            if ($this->worker) {
                $this->worker->stop();
            }
        } catch (Exception $e) {
            Yii::log($this->daemonName . ' error: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
        }

        if (file_exists($this->getLockFilePath())) {
            $pid = trim(file_get_contents($this->getLockFilePath()));
            unlink($this->getLockFilePath());

            shell_exec("kill $pid");
        }
        echo sprintf("\n[%s] stoped.\n", $this->daemonName);
    }

    /**
     * Creates daemon.
     * Check is daemon already run and if false then starts daemon and update lock file.
     */
    private function createDaemon()
    {
        if ($this->isAlreadyRunning()) {
            echo sprintf("\n[%s] is running already.\n", $this->daemonName);
            exit();
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            exit('Error while forking process.');
        } elseif ($pid) {
            // this is parent
            //pcntl_wait($status); //Protect against Zombie children
            //echo "Exiting parent process " . getmypid() . "\n";
            exit;
        } else {
            // this is children
        }

        $pid = getmypid();
        echo sprintf("\n[%s] running with PID: %s\n", $this->daemonName, $pid);
        file_put_contents($this->getLockFilePath(), $pid);

        $this->runWorker();
    }

    /**
     * Checks if daemon already running.
     *
     * @return bool
     */
    public function isAlreadyRunning()
    {
        if (false === file_exists($this->getLockFilePath())) {
            return false;
        }
        $pid = trim(file_get_contents($this->getLockFilePath()));
        $pids = explode("\n", trim(shell_exec("ps -e | awk '{print $1}'")));

        if (in_array($pid, $pids)) {
            return true;
        }

        unlink($this->getLockFilePath());

        return false;
    }

    /**
     * Gets path to lock file.
     * Lock file keeps pids of started daemons.
     *
     * @return string
     */
    private function getLockFilePath()
    {
        return Yii::app()->basePath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . $this->pidFile;
    }

    /**
     * Run queue worker
     * 
     * @author Virchenko Maksim <muslim1992@gmail.com>
     */
    private function runWorker()
    {
        $queueManager = Yii::app()->laravel5queue->connect();

        $this->worker = new Worker($queueManager->getQueueManager(), new MongoFailedJobProvider(Yii::app()->mongodb, 'YiiJobsFailed'));
        $this->worker->daemon('default', 'default', $this->delay, $this->memory, $this->sleep, $this->maxTries);
    }

}
