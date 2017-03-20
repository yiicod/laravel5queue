<?php

namespace yiicod\laravel5queue\queues;

use Illuminate\Queue\Jobs\Job;
use Symfony\Component\Process\Process;
use yiicod\laravel5queue\jobs\MongoJob;

/**
 * Class AsyncMongoQueue
 * @package yiicod\laravel5queue\queues
 */
class AsyncMongoQueue extends MongoQueue
{
    /** @var string */
    protected $binary;

    /** @var string */
    protected $binaryArgs;

    /** @var int */
    protected $limit = 15;

    /** @var string */
    protected $yiicAlias = 'application';

    /** @var string */
    protected $connectionName;

    /**
     * Create a new database queue instance.
     *
     * @param $mongo
     * @param string $table
     * @param string $default
     * @param int $expire
     * @param int $limit
     * @param $yiicAlias
     * @param string $binary
     * @param string|array $binaryArgs
     * @param string $connectionName
     */
    public function __construct($mongo, $table, $default = 'default', $expire = 60, $limit = 15, $yiicAlias, $binary = 'php', $binaryArgs = '', $connectionName = '')
    {
        parent::__construct($mongo, $table, $default, $expire);
        $this->limit = $limit;
        $this->binary = $binary;
        $this->binaryArgs = $binaryArgs;
        $this->connectionName = $connectionName;
        $this->yiicAlias = $yiicAlias;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     *
     * @return Job|null|true
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if (!is_null($this->expire)) {
            $this->releaseJobsThatHaveBeenReservedTooLong($queue);
        }

        if ($job = $this->getNextAvailableJob($queue)) {
            $this->startProcess((string)$job->_id);

            return true;
        }

        return null;
    }

    /**
     * Check if can run process
     *
     * @return bool
     */
    protected function canRunProcess()
    {
        return count($this->getTable()->find(['reserved' => 1])) < $this->limit;
    }

    /**
     * Get the next available job for the queue.
     *
     * @param $id
     *
     * @return null|\StdClass|MongoJob
     */
    public function getJobFromId($id)
    {
        $job = $this->getTable()->findOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);

        return is_null($job) ? $job : new MongoJob($this->container, $this, $job, $job->queue);
    }

    /**
     * Make a Process for the Artisan command for the job id.
     *
     * @param $id
     *
     * @return void
     */
    public function startProcess($id)
    {
        if ($this->canRunProcess()) {
            $this->markJobAsReserved($id);

            $command = $this->getCommand($id);
            $cwd = $this->getYiicPath();

            $process = new Process($command, $cwd);
            $process->run();
        } else {
            sleep(1);
        }
    }

    /**
     * Get the Artisan command as a string for the job id.
     *
     * @param $id
     *
     * @return string
     */
    protected function getCommand($id)
    {
        $connection = $this->connectionName;
        $cmd = '%s yiic asyncqueue process --id=%s --connection=%s';
        $cmd = $this->getBackgroundCommand($cmd);

        $binary = $this->getPhpBinary();

        return sprintf($cmd, $binary, $id, $connection);
    }

    /**
     * Get the escaped PHP Binary from the configuration
     *
     * @return string
     */
    protected function getPhpBinary()
    {
        $path = $this->binary;
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $path = escapeshellarg($path);
        }

        $args = $this->binaryArgs;
        if (is_array($args)) {
            $args = implode(' ', $args);
        }
        return trim($path . ' ' . $args);
    }

    /**
     * Get yiic path
     *
     * @return mixed
     */
    protected function getYiicPath()
    {
        return \Yii::getPathOfAlias($this->yiicAlias);
    }

    /**
     * Get command
     *
     * @param $cmd
     *
     * @return string
     */
    protected function getBackgroundCommand($cmd)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return 'start /B ' . $cmd . ' > NUL';
        } else {
            return $cmd . ' > /dev/null 2>&1 &';
        }
    }
}