<?php
/**
 * Class Logger - ultra simple logger:
 *
 * logs to 'log' file in working directory
 */
class logger
{
    private LOGFILE = "log";
    private OK = "OK";

    public function __construct($message, $status = OK)
    {
        if (!$message) {
            return;
        }
        file_put_contents(LOGFILE, implode("\t", array(date(DATE_ATOM),$status,$message), FILE_APPEND);
    }
}
