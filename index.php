<?php

require __DIR__.'/vendor/autoload.php';
use Goutte\Client;

error_reporting(E_ERROR);
require_once 'classes/scraper.php';
require_once 'classes/logger.php';
require_once 'classes/locker.php';

$t1 = microtime(1);

$client = new Client();
$scraper = new Scraper($client);
$scraper->logthis('run started');

try {
    if (!$scraper->isUnlocked()) {
        throw new Exception('Already working...  waiting for the next ');
    }
    $scraper->lock();
} catch (Exception $e) {
    $scraper->logthis($e->getMessage(), 'ERROR');
    ddd($e->getMessage(), $e);
}

// instantiate needed segmented arrays
$iSegments = 'candidates,current,descs,parties,races,votes';
$rSegments = 'city,wards,division';
$indexes = $results = $divisionNavigation = [];
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
        $indexes['current'] = $wardNav['value'] === 'All Wards' ? '00' : (int) $wardNav['value'];
        $results['wards'][$wardNav['value']] = [];
        $divisionNavigation[$wardNav['value']] = [];
        $page = $scraper->getPage($scraper->wardNav, $wardNav['id']);
        $results['wards'][$wardNav['value']] = $scraper->getResults($page, $indexes);
        $divisionNavigation[$wardNav['value']] = $scraper->getNavData($scraper->divisionNav, $page);
    }
} catch (Exception $e) {
    $scraper->logthis('unable to complete run: '.$e->getMessage(), 'ERROR');
    $scraper->unlock();
    ddd($e->getMessage(), $e, $results);
}
$running_time = microtime(1) - $t1;
$results['city'] = $results['wards']['All Wards'];
unset($indexes['current']);
// and now we eat those extra '0' elements
foreach (explode(',', $iSegments) as $index) {
    unset($indexes[$index][0]);
}
$results['running_time'] = $running_time;
$results['timestamp'] = time();

unset($results['wards'], $results['divisions']);
foreach ($indexes as $key => $index) {
    $results[$key] = $index;
}

try {
    // from same server use this
    //$scraper->wwSave($results);
    $scraper->save($results);
    $scraper->logthis('successful result pull taking '.$running_time.' seconds.');
    $scraper->unlock();
    $scraper->push();
} catch (Exception $e) {
    $scraper->logthis($e->getMessage, 'ERROR');
}
