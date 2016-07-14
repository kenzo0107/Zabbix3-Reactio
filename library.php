<?php

require_once dirname ( __FILE__ ).'/MysqliDb.php';

global $config;
$config = parse_ini_file('config.inc', true);

$log = '/usr/lib/zabbix/alertscripts/zabbix-reactio/reactio.log';

function getReactioIncidentIdByEventId($eventid) {

    global $config;

    if (empty($eventid)) {
        return;
    }

    $db_info = $config['db_info'];
    $db = new Mysqlidb ($db_info['host'], $db_info['user'], $db_info['pass'], $db_info['db']);
    $db->where ("eventid", $eventid);
    $res = $db->getOne('alerts', 'reactio_incident_id');
    $reactio_incident_id = $res['reactio_incident_id'];
    return $reactio_incident_id;
}

function updateReactioIncidentIdByEventId($eventid, $reactio_incident_id) {

    global $config;

    $is_success = false;

    if (empty($eventid)) {
        return $is_success;
    }
    if (empty($reactio_incident_id)) {
        return $is_success;
    }

    $db_info = $config['db_info'];
    $db = new Mysqlidb ($db_info['host'], $db_info['user'], $db_info['pass'], $db_info['db']);
    $db->where ('eventid', $eventid);
    $data['reactio_incident_id'] = $reactio_incident_id;
    if ($db->update ('alerts', $data)) {
        $is_success = true;
    }
    return $is_success;
}

/**
 * Zabbix 障害検知のサブジェクトよりステータス状況取得
 *
 * @param $subject string - Zabbix subject
 * @return string         - Reatio status of Incident
 */
function getStatusBySubject($subject) {

    if (empty($subject)) {
        return;
    }

    if (preg_match('/RECOVERY/', $subject)) {
        return 'close';
    } else if ( preg_match('/PROBLEM/', $subject) ) {
        return 'open';
    }
    return;
}

/**
 * Zabbix 障害検知メッセージよりイベントID取得
 *
 * @param $message string - Zabbix message
 * @return int            - Zabbix EVENT ID
 */
function getEventIdByMessage($message) {

    if (empty($message)) {
        return;
    }

    preg_match('/EVENT_ID: (?P<eventid>\d+)/', $message, $match);
    if (!empty($match['eventid'])) {
        return $match['eventid'];
    }
    return;
}

/**
 * Zabbix 障害検知サブジェクトよりApiKey取得
 *
 * @param $subject string - Zabbix subject
 * @param $project string - Zabbix HOSTNAME
 * @return string         - Reactio API KEY
 */
function getApiKeyBySubject($subject) {

    global $config;

    if (empty($subject)) {
        return;
    }

    if (empty($config['api_key'])) {
        return;
    }

    $api_key = '';
    foreach($config['api_key'] as $host => $key) {
        $pattern = '/'.$host.'/';
        if (preg_match($pattern, $subject)) {
            $api_key = $key;
            break;
        }
    }

    return $api_key;
}

/**
 * インシデント作成
 *
 * @param $api_key string  - Reactio API KEY
 * @param $status  integer - Incident status
 * @param $message string  - Incident message
 * @return json            - Reactio API response JSON
 */
function createIncident($api_key, $status, $subject, $message) {

    global $config;

    if (empty($config['reactio_url']['default'])) {
        return;
    }

    $url = $config['reactio_url']['default'];

    $post_data = array();
    $post_data['name']   = $subject;
    $post_data['status'] = $status;
    $post_data['topics'] = ["原因調査","復旧作業"];
    $post_data['notification_text'] = $message;
    $post_data['notification_call'] = true;
    $post_data['message'] = '原因調査を実施お願いします';

    $result = curlPost($api_key, $url, $post_data);

    return $result;
}

/**
 * インシデント作成
 *
 * @param $api_key string      - Reactio API KEY
 * @param $status  integer     - Incident status
 * @param $incident_id integer - Incident ID
 * @return json                - Reactio API response JSON
 */
function updateStatusIncident($api_key, $status, $incident_id) {

    global $config;

    if (empty($config['reactio_url']['default'])) {
        return;
    }

    $url = sprintf( '%s/%s/status', $config['reactio_url']['default'], $incident_id);

    $post_data = array();
    $post_data['status'] = $status;

    $result = curlPost($api_key, $url, $post_data);

    return $result;
}

/**
 * curl による POST
 * @param $api_key   string    - API KEY
 * @param $url       string    - URL
 * @param $post_data array     - POST DATA
 * @return json                - Reactio API response JSON
 */
function curlPost($api_key, $url, $post_data) {

    $header = array(
        "Accept: application/json",
        "Content-type: application/json",
        "X-Api-Key: {$api_key}"
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);

    $response = curl_exec($curl);

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $result = json_decode($body, true);
    curl_close($curl);

    return $result;
}

?>
