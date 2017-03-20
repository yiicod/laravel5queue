<?php

namespace yiicod\laravel5queue\commands;

use CConsoleCommand;
use Illuminate\Queue\Capsule\Manager;
use Yii;
use yiicod\laravel5queue\base\WorkerInterface;
use yiicod\laravel5queue\failed\MongoFailedJobProvider;
use yiicod\laravel5queue\handlers\ExceptionHandler;
use yiicod\laravel5queue\Worker;

/**
 * Command to start worker
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class WorkerCommand extends CConsoleCommand implements WorkerInterface
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
     * Queue name
     * @var string
     */
    protected $queue = 'default';

    /**
     * Connection name
     * @var string
     */
    protected $connection = 'default';

    /**
     * @var string Daemon display name
     */
    protected $daemonName = 'laravel5queue-daemon';

    /**
     * Default action. Starts daemon.
     *
     * @param string $connection
     * @param string $queue
     *
     * @return mixed|void
     */
    public function actionStart(string $connection = 'default', string $queue = 'default')
    {
        $this->queue = $queue;
        $this->connection = $connection;
        $this->createDaemon();
    }

    /**
     * Stops daemon.
     * Close server and close all connections.
     *
     * @param string $connection
     * @param string $queue
     *
     * @return mixed|void
     */
    public function actionStop(string $connection = 'default', string $queue = 'default')
    {
        $this->queue = $queue;
        $this->connection = $connection;
        if (false === $this->isAlreadyRunning()) {
            echo sprintf("[%s] is not running.\n", $this->daemonName);
            Yii::app()->end();
        }
        $this->stopDaemon();
    }

    /**
     * Creates daemon.
     * Check is daemon already run and if false then starts daemon and update lock file.
     */
    protected function createDaemon()
    {
        if (true === $this->isAlreadyRunning()) {
            echo sprintf("[%s] is running already.\n", $this->daemonName);
            Yii::app()->end();
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            exit('Error while forking process.');
        } elseif ($pid) {
            exit();
        } else {
            $pid = getmypid();
            $this->addPid($pid);
        }

        echo sprintf("[%s] running with PID: %s\n", $this->daemonName, $pid);
        $this->worker();
    }

    /**
     * Stop daemon
     */
    protected function stopDaemon()
    {
        if (file_exists($this->getPidsFilePath())) {
            $runingPids = explode(',', trim(file_get_contents($this->getPidsFilePath())));
            foreach ($runingPids as $pid) {
                shell_exec("kill $pid");
            }
        }
        @unlink($this->getPidsFilePath());

        echo sprintf("[%s] stoped.\n", $this->daemonName);
    }

    /**
     * Checks if daemon already running.
     *
     * @return bool
     */
    protected function isAlreadyRunning()
    {
        if (false === file_exists($this->getPidsFilePath())) {
            return false;
        }

        $runingPids = explode(',', trim(file_get_contents($this->getPidsFilePath())));
        $systemPids = explode("\n", trim(shell_exec("ps -e | awk '{print $1}'")));

        $result = true;

        foreach ($runingPids as $pid) {
            if (false === in_array($pid, $systemPids)) {
                $this->stopDaemon();
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Add pid
     *
     * @param $pid
     */
    protected function addPid($pid)
    {
        $pids = '';
        if (file_exists($this->getPidsFilePath())) {
            $pids = file_get_contents($this->getPidsFilePath(), $pid);
        }
        $pids .= empty($pids) ? $pid : (',' . $pid);
        file_put_contents($this->getPidsFilePath(), $pids);
    }

    /**
     * Gets path to lock file.
     * Lock file keeps pids of started daemons.
     *
     * @return string
     */
    protected function getPidsFilePath()
    {
        $pidFile = 'laravel5queue-' . md5($this->queue . $this->connection) . '.pid';

        return Yii::app()->basePath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . $pidFile;
    }

    /**
     * Run queue worker
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     */
    protected function worker()
    {
        /** @var Manager $queueManager */
        $manager = Yii::app()->laravel5queue->getManager();

        $worker = new Worker($manager->getQueueManager(), new MongoFailedJobProvider(Yii::app()->mongodb, 'YiiJobsFailed'), new ExceptionHandler());
        $worker->daemon($this->connection, $this->queue, $this->delay, $this->memory, $this->sleep, $this->maxTries);
    }
}
