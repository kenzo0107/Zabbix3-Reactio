#!/usr/bin/php
<?php

require_once dirname ( __FILE__ ) . '/library.php';

if (empty($argv)) {
    error_log('argv is empty.'.PHP_EOL, 3, $log);
    exit(1);
}

$subject = $argv[1];
$message = $argv[2];

$api_key = getApiKeyBySubject($subject);
$status  = getStatusBySubject($subject);
$eventid = getEventIdByMessage($message);

// $logging = sprintf(PHP_EOL.'{"date":"%s", "argv":"%s","subject":"%s", "message":"%s", "api_key":"%s", "status":"%s", "eventid":"%s"}',
//     date("Y/m/d H:i:s"),
//     json_encode($argv),
//     $subject,
//     $message,
//     $api_key,
//     $status,
//     $eventid
// );
// error_log($logging, 3, $log);

switch ($status) {
    case('close'):
        $reactio_incident_id = getReactioIncidentIdByEventId($eventid);
        updateStatusIncident($api_key, $status, $reactio_incident_id);
        break;
    case('open'):
        $res = createIncident($api_key, $status, $subject, $message);
        $reactio_incident_id = $res['id'];
        updateReactioIncidentIdByEventId($eventid, $reactio_incident_id);
        break;
}
