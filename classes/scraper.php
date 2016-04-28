<?php

use Goutte\Client;

class scraper
{
    private RESULTSFILE = 'results.json';
    private CONFIGFILE = 'config.json';
    protected $config;
    protected $logger;
    public $client;
    public $crawler;
    public $page;
    public $divisionNav = array('label' => 'divisions', 'select' => 'cboPrecincts', 'submit_button_id' => '#btnNext', 'submit_button_value' => 'View Division Results');
    public $wardNav = array('label' => 'wards', 'select' => 'cboGeography', 'submit_button_id' => '#btnNext', 'submit_button_value' => 'View Results >>');

    public function __construct(&$client)
    {
        $this->config = getConfig();
        $this->client = &$client;
        $this->goHome();
    }

    public function go($url, $method = 'GET')
    {
        $this->crawler = $this->client->request($method, $url);
    }

    public function goHome($crawl = true)
    {
        if ($crawl) {
            // we're going to the ward form
            $this->go($this->config->source->filter_url);
        } else {
            // we're going to the static/home page
            $this->go($this->config->source->static_url);
        }
    }

    private function getConfig()
    {
        json_decode(file_get_contents($this->CONFIGFILE));
    }

    public function getForm($nav)
    {
        return $this->crawler->selectButton($nav['submit_button_value'])->form();
    }
    public function getPage($nav, $value)
    {
        $this->page = $this->client->submit($this->getForm($nav), array($nav['select'] => $value));

        return $this->page;
    }

    public function getNavData($nav, &$crawler)
    {

        // read the id/value pairs for the
        return $crawler->filter('#'.$nav['select'].' option')->each(function ($node) {
            if ($node) {
                return array('id' => $node->attr('value'), 'value' => $node->text());
            }
        });
    }

    public function getResults(&$page, &$indexes)
    {
        $results = $result = $temp = [];
        $resultType = '';
        $results['results'] = [];

        // grab everything and drop it in one container
        $rows = $page->filter('form h2, form h3, form h4, form table.results')->each(function ($node) {
            return array('nodeName' => $node->nodeName(), 'nodeText' => $node->text(), 'node' => $node);
        });

        // i'd do this in the each above, but I need to share values among nodes,
        // and these nodes are proximally, not hierarchically, arranged in the page.
        foreach ($rows as $row) {
            switch ($row['nodeName']) {
                case 'h2':
                    $resultType = trim($row['nodeText']);
                    if (count($result)) {
                        array_push($results['results'], array('party' => $partyId, 'progress' => $progress, 'race' => $raceId, 'desc' => $descId, 'candidates' => $result));
                        array_push($indexes['races'][$raceId], array('party' => $partyId, 'prog' => $progress, 'desc' => $descId));
                    }

                    // re-initialize
                    $result = [];
                    break;

                case 'h3':
                    // write extant result
                    if (count($result)) {
                        array_push($results['results'], array('party' => $partyId, 'progress' => $progress, 'race' => $raceId, 'desc' => $descId, 'candidates' => $result));
                        array_push($indexes['races'][$raceId], array('party' => $partyId, 'prog' => $progress, 'desc' => $descId));
                    }

                    // re-initialize
                    $result = [];

                    $temp = explode(':', trim($row['nodeText']));
                    $race = str_replace('*', '', trim($temp[1]));
                    if (!in_array($race, $indexes['races'])) {
                        array_push($indexes['races'], $race);
                    }
                    $raceId = array_search($race, $indexes['races']);
                    $temp = explode('-', $race);
                    $party = '';

                    // the assumptions here could bite me in the ass
                    if ($resultType !== 'Question Results' && count($temp) > 1) {
                        $party = trim($temp[count($temp) - 1]) === 'R' ? 'REPUBLICAN' : (trim($temp[count($temp) - 1]) === 'D' ? 'DEMOCRATIC' : '');
                    }
                    if (!in_array($party, $indexes['parties'])) {
                        array_push($indexes['parties'], $party);
                    }
                    $partyId = array_search($party, $indexes['parties']);
                    break;

                case 'h4':
                    $temp = explode('%', $row['nodeText']);
                    $progress = '0';
                    $desc = count($temp) === 1 ? $temp[0] : $temp;
                    if (count($temp) === 2) {
                        $progress = trim($temp[0]);
                        $desc = trim($temp[1]);
                    }
                    if (!in_array($desc, $indexes['descs'])) {
                        array_push($indexes['descs'], $desc);
                    }
                    $descId = array_search($desc, $indexes['descs']);
                    break;

                case 'table':
                    // pull out the result block
                    $temp = $row['node']->filter('tr')->each(function ($node) {
                        return $node->filter('td')->each(function ($node) {
                            return $node->text();
                        });
                    });

                    // drop row 0 (columns: we won't use)
                    array_shift($temp);

                    array_walk($temp, function ($values) use (&$result, &$indexes, $raceId) {
                        $name = str_replace('*', '', trim($values[0]));
                        if (!in_array($name, $indexes['candidates'])) {
                            array_push($indexes['candidates'], $name);
                        }
                        $nameId = array_search($name, $indexes['candidates']);

                        // percentage is always last element
                        $percentage = explode(' ', $values[count($values) - 1]);
                        if (!isset($indexes['votes'][$indexes['current']])) {
                            $indexes['votes'][$indexes['current']] = [];
                        }

                        if (!isset($indexes['votes'][$indexes['current']][$raceId])) {
                            $indexes['votes'][$indexes['current']][$raceId] = [];
                        }

                        if (!isset($indexes['votes'][$indexes['current']][$raceId][$nameId])) {
                            $indexes['votes'][$indexes['current']][$raceId][$nameId] = [];
                        }

                        switch (count($values)) {
                            case 4:
                                $party = trim($values[1]);
                                if (!in_array($party, $indexes['parties'])) {
                                    array_push($indexes['parties'], $party);
                                }
                                $partyId = array_search($party, $indexes['parties']);
                                array_push($result, array('name' => $nameId, 'party' => $partyId, 'percentage' => (float) trim($percentage[0]), 'votes' => (int) trim($values[2])));
                                array_push($indexes['votes'][$indexes['current']][$raceId][$nameId], array('party' => $partyId, 'perc' => (float) trim($percentage[0]), 'votes' => (int) trim($values[2])));
                                break;

                            case 3:
                                $partyId = array_search('', $indexes['parties']);
                                array_push($result, array('name' => $nameId, 'party' => $partyId, 'percentage' => (float) trim($percentage[0]), 'votes' => (int) trim($values[1])));
                                array_push($indexes['votes'][$indexes['current']][$raceId][$nameId], array('party' => $partyId, 'perc' => (float) trim($percentage[0]), 'votes' => (int) trim($values[1])));

                                break;

                            default:
                                $this->log('unexpected content: '.json_encode($values), 'ERROR');

                                // don't use this set
                                return;
                                break;
                        }
                        unset($party, $percentage, $values);
                    });

                    break;
            }
        }
        if (count($result)) {
            array_push($results['results'], array('party' => $partyId, 'progress' => $progress, 'race' => $raceId, 'desc' => $descId, 'candidates' => $result));
        }

        unset($rows, $result);

        return $results;
    }

    private function log($message, $status = 'OK')
    {
        if (!$this->logger) {
            require_once 'classes/logger.php';
            $this->logger = new Logger();
        }

        return $this->logger($message, $status);
    }

    public function save(&$results)
    {
        file_put_contents($this->RESULTSFILE, json_encode($results));
    }

    public function push()
    {
        if ($this->config->target->server) {
            $sftp = new Net_SFTP($this->config->target->server);
            if (!$sftp->login($this->config->target->user, $this->config->target->pass)) {
                throw new Exception('SFTP Login Failed');
            } else {
                if (!$sftp->put($this->config->target->path.'/'.$this->RESULTSFILE, $this->RESULTSFILE, NET_SFTP_LOCAL_FILE)) {
                    throw new Exception('SFTP Transfer Failed');
                } else {
                    $this->log('successful transfer to '.$this->config->target->server);
                }
            }
        } elseif ($this->config->target->path) {
            $this->log('saving to loal web path '.$this->config->target->path, 'OK');
            copy($this->RESULTSFILE, $this->config->target->path.'/'.$this->RESULTSFILE);
        } else {
            $this->log('configuration (config.json) missing a target server and path', 'ERROR');
        }
    }
}
