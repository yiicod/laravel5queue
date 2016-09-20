<?php

namespace yiicod\laravel5queue\connectors;


use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use yiicod\laravel5queue\queues\AsyncMongoQueue;

/**
 * Connector for laravel queue to mongodb
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class AsyncMongoConnector implements ConnectorInterface
{

    /**
     * Database connections.
     *
     */
    protected $connection;

    /**
     * Create a new connector instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array $config
     *
     * @return Queue
     */
    public function connect(array $config)
    {
        $config = array_merge([
            'limit' => 15,
            'yiicAlias' => 'application',
            'binary' => 'php',
            'binaryArgs' => [],
            'connectionName' => 'async'
        ], $config);

        return new AsyncMongoQueue($this->connection, $config['table'], $config['queue'], $config['expire'], $config['limit'], $config['yiicAlias'], $config['binary'], $config['binaryArgs'], $config['connectionName']);
    }

}
