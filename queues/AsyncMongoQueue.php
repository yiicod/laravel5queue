<?php
namespace yiicod\laravel5queue\queues;

use DateTime;
use Symfony\Component\Process\Process;
use yiicod\laravel5queue\jobs\MongoJob;

/**
 * Class AsyncMongoQueue
 * Mongo queues for async worker
 *
 * @package yiicod\laravel5queue\queues
 */
class AsyncMongoQueue extends MongoQueue
{
    /** @var string */
    protected $binary;

    /** @var string */
    protected $binaryArgs;

    /**
     * @var int
     */
    protected $limit = 15;

    /**
     * @var string
     */
    protected $yiicAlias = 'application';

    /** @var string */
    protected $connectionName;

    /**
     * Create a new database queue instance.
     *
     * @param  MongoDB connection $mongo
     * @param  string $table
     * @param  string $default
     * @param  int $expire
     * @param  string $binary
     * @param  string|array $binaryArgs
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
     * Push a new job onto the queue.
     *
     * @param string $job
     * @param mixed $data
     * @param string|null $queue
     *
     * @return int
     */
    public function push($job, $data = '', $queue = null)
    {
        $id = parent::push($job, $data, $queue);
        //$this->startProcess($id);

        return $id;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        $id = parent::pushRaw($payload, $queue, $options);
        //$this->startProcess($id);

        return $id;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTime|int $delay
     * @param string $job
     * @param mixed $data
     * @param string|null $queue
     *
     * @return int
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $id = parent::later($delay, $job, $data, $queue);
        //$this->startProcess($id);

        return $id;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     *
     * @return Job|null
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
     * Check if process can run
     *
     * @return bool
     */
    protected function canRunProcess()
    {
        return $this->database->{$this->table}->count(['reserved' => 1]) < $this->limit;
    }

    /**
     * Push work to database
     *
     * @param DateTime|int $delay
     * @param null|string $queue
     * @param string $payload
     * @param int $attempts
     *
     * @return string
     */
    protected function pushToDatabase($delay, $queue, $payload, $attempts = 0)
    {
//        if ($this->canRunProcess()) {
//            $availableAt = $delay instanceof DateTime ? $delay : Carbon::now()->addSeconds($delay);
//            $result = $this->database->{$this->table}->insertOne([
//                'queue' => $this->getQueue($queue),
//                'payload' => $payload,
//                'attempts' => $attempts,
//                'reserved' => 1,
//                'reserved_at' => $this->getTime(),
//                'available_at' => $availableAt->getTimestamp(),
//                'created_at' => $this->getTime(),
//            ]);
//        } else {
        $result = parent::pushToDatabase($delay, $queue, $payload, $attempts);
//        }
        return (string)$result->getInsertedId();
    }

    /**
     * Get the next available job for the queue.
     *
     * @param $id
     *
     * @return null|\StdClass
     */
    public function getJobFromId($id)
    {
        $job = $this->database->{$this->table}->findOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);

        return is_null($job) ? $job : new MongoJob($this->container, $this, $job, $job->queue);
    }

    /**
     * Make a Process for the Artisan command for the job id.
     *
     * @param $id
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
     * Get path for yiic
     *
     * @return mixed
     */
    protected function getYiicPath()
    {
        return \Yii::getPathOfAlias($this->yiicAlias);
    }

    /**
     * Get background cmd command
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