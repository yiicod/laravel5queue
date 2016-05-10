<?php

namespace yiicod\laravel5queue\handlers;

use Exception;
use Yii;

/**
 * DaemonExceptionHandler
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class FatalThrowableError extends Exception
{
    public function __construct($e, $code = 0, Exception $previous = null)
    {
        $this->message = $e->getMessage();
        $this->file = $e->getFile();
        $this->line = $e->getLine();
    }
}
