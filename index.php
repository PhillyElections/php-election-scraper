<?php

ini_set('display_errors', 1);
require __DIR__ . '/vendor/autoload.php';
use Goutte\Client;

// Let's lay out some constants
define('AP', __DIR__);
define('DS', DIRECTORY_SEPARATOR);

// get the config
$config = json_decode(file_get_contents(dirname(__FILE__) . DS . 'config.json'));

error_reporting(E_NOTICE);
require_once 'classes/scraper.php';
require_once 'classes/filer.php';
require_once 'classes/locker.php';
$t1 = microtime(1);

$logger  = new Katzgrau\KLogger\Logger(AP);
$locker  = new locker($config);
$filer   = new filer($config, $logger);
$client  = new Client();
$scraper = new scraper($client, $config, $logger);
$logger->info('run started');
try {
    if (!$locker->isUnlocked()) {
        throw new Exception('Already working...  waiting for the next ');
    }
    $locker->lock();
} catch (Exception $e) {
    $logger->error($e->getMessage());
    ddd($e->getMessage(), $e);
}

// instantiate needed segmented arrays
$iSegments = 'candidates,current,descs,parties,races,votes';
$rSegments = 'city,wards,division';
$indexes   = $results   = $divisionNavigation   = [];
foreach (explode(',', $iSegments) as $index) {
    $indexes[$index] = [];
    // the php array to json object conversion will screw with my '0' and 0 indexes, so let's just get rid of that problem...
    $indexes[$index][] = '.';
}
foreach (explode(',', $rSegments) as $index) {
    $results[$index] = [];
}

// seed some needed values
array_push($indexes['parties'], 'DEMOCRATIC');
array_push($indexes['parties'], 'REPUBLICAN');
array_push($indexes['parties'], '');

try {
    // get ward navigaion
    $wardNavigation = $scraper->getNavData($scraper->wardNav, $scraper->crawler);

    // lets shift off the city navigation
    foreach ($wardNavigation as $wardNav) {
        // don't use '0' or 0, force '00', which won't resolve to ''
        $indexes['current']                    = $wardNav['value'] === 'All Wards' ? '00' : (int) $wardNav['value'];
        $results['wards'][$wardNav['value']]   = [];
        $divisionNavigation[$wardNav['value']] = [];
        $page                                  = $scraper->getPage($scraper->wardNav, $wardNav['id']);
        $results['wards'][$wardNav['value']]   = $scraper->getPageResults($page, $indexes);
        $divisionNavigation[$wardNav['value']] = $scraper->getNavData($scraper->divisionNav, $page);
    }
} catch (Exception $e) {
    $logger->error('unable to complete run: ' . $e->getMessage());
    $locker->unlock();
    ddd($e->getMessage(), $e, $results);
}
if (!count($results['wards'])) {
    $locker->unlock();
    $logger->info('empty result set');
    exit;
}
$running_time    = microtime(1) - $t1;
$results['city'] = $results['wards']['All Wards'];
unset($indexes['current']);
// and now we eat those extra '0' elements
foreach (explode(',', $iSegments) as $index) {
    unset($indexes[$index][0]);
}
$results['running_time'] = $running_time;
$results['timestamp']    = time();

unset($results['wards'], $results['divisions']);
foreach ($indexes as $key => $index) {
    $results[$key] = $index;
}

try {
    // from same server use this
    //$scraper->wwSave($results);
    $filer->save($results);
    $logger->info('successful result pull taking ' . $running_time . ' seconds.');
    $locker->unlock();
    $filer->push();
} catch (Exception $e) {
    $logger->error($e->getMessage);
}
