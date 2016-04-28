<?php
/**
 * Class Logger - ultra simple logger:.
 *
 * logs to 'log' file in working directory
 */
class logger
{
    private $LOGFILE = 'log';
    private $OK = 'OK';

    public function write($message, $status)
    {
        if (!$message) {
            return;
        }
        file_put_contents($this->LOGFILE, implode("\t", array(date(DATE_ATOM), $status || $this->OK, $message), FILE_APPEND));
    }
}
