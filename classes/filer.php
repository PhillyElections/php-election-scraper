<?php

class filer
{
    /**
     * @var mixed
     */
    public $logger;
    public $config;

    /**
     * @param $config
     */
    public function __construct(&$config, &$logger)
    {
        if (!$config || !$logger) {
            return new Exception('class filer needs config object and logger class.');
        }
        $this->config = &$config;
        $this->logger = &$logger;
    }

    /**
     * @param $results
     */
    public function save(&$results)
    {
        file_put_contents(AP . DS . $this->config->files->results, json_encode($results));
    }

    /**
     *
     */
    public function push()
    {
        if ($this->config->target->server) {
            $sftp =
            new Net_SFTP($this->config->target->server);
            if (!$sftp->login($this->config->target->user, $this->config->target->pass)) {
                throw new Exception('SFTP Login Failed');
            } else {
                if (!$sftp->put($this->config->target->path . '/' . $this->config->files->results, $this->config->files->results, NET_SFTP_LOCAL_FILE)) {
                    throw new Exception('SFTP Transfer Failed');
                } else {
                    $this->logger->info('successful transfer to ' . $this->config->target->server);
                }
            }
        } elseif ($this->config->target->path) {
            $this->logger->info('saving to loal web path ' . $this->config->target->path);
            copy(AP . DS . $this->config->files->results, $this->config->target->path . '/' . $this->config->files->results);
        } else {
            $this->logger->error('configuration (config.json) missing a target server and path');
        }
    }
}
