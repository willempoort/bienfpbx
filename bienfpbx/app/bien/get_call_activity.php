<?php
include "root.php";
require_once "resources/require.php";
require_once "config.inc.php";

$oCache = new Memcached();
$oCache->addServer('localhost', 11211);

function get_extensions($aSelect) {
	global $db, $oCache;
	$aExtensions = array();
  
  $aExtensions = $oCache->get('extensions');
  
  if(!$aExtensions) {
    $aExtensions = array();
    
    //get the extensions and their user status
    $sql = "SELECT ";
    $sql .= "e.extension, ";
    $sql .= "e.number_alias, ";
    $sql .= "e.effective_caller_id_name, ";
    $sql .= "e.effective_caller_id_number, ";
    $sql .= "e.call_group, ";
    $sql .= "e.description, ";
    $sql .= "e.do_not_disturb, ";
    $sql .= "vm.voicemail_enabled, ";
    $sql .= "u.user_uuid, ";
    $sql .= "u.user_status ";
    $sql .= "FROM v_extensions AS e ";
    $sql .= "LEFT OUTER JOIN v_extension_users AS eu ON ( eu.extension_uuid = e.extension_uuid AND eu.domain_uuid = '".$_SESSION['domain_uuid']."' ) ";
    $sql .= "LEFT OUTER JOIN v_users AS u ON ( u.user_uuid = eu.user_uuid AND u.domain_uuid = '".$_SESSION['domain_uuid']."' ) ";
    $sql .= "LEFT OUTER JOIN v_voicemails AS vm ON ( vm.voicemail_id::text = e.extension AND vm.domain_uuid = '".$_SESSION['domain_uuid']."' ) ";
    $sql .= "WHERE ";

    $sql .= "e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
    $sql .= "ORDER BY e.extension ASC ";
    
    $prep_statement = $db->prepare($sql);
    $prep_statement->execute();
    $aRows = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    unset ($prep_statement, $sql);

    /*** INIT EXTENSIONS ***/
    foreach($aRows as $aRow) {
      $aExtensions[$aRow['extension']] = $aRow;
      $aExtensions[$aRow['extension']]['connected'] = false;
      $aExtensions[$aRow['extension']]['callstate'] = null;
    }
    $oCache->set('extensions', $aExtensions, PBX_CACHE);
  } 
  /*** FILTER OUTPUT ***/
  if(is_array($aSelect['extensions']) && count($aSelect['extensions']) > 0) {
    $aOut = array();
    foreach($aSelect['extensions'] as $iExtension) {
      if(isset($aExtensions[$iExtension])) $aOut[$iExtension] = $aExtensions[$iExtension];
    }
    $aExtensions = $aOut;
    unset($aOut);
  } 

  
  /*** GET THE EXTENSIONS REGISTERED ***/
  $obj = new registrations;
  $aRegistrations = $obj->get($profile);
  foreach($aRegistrations as $aRegistration) {
    if(isset($aExtensions[$aRegistration['sip-auth-user']])) $aExtensions[$aRegistration['sip-auth-user']]['connected'] = true;
  }
  unset($aRegistrations);

  /*** GET CURRENT CALLS ***/
  $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
  if ($fp) {
    $switch_cmd = 'show channels as json';
    $switch_result = event_socket_request($fp, 'api '.$switch_cmd);
    $aCalls = json_decode($switch_result, true);
    if(isset($aCalls['rows'])) foreach($aCalls['rows'] as $aCall) {
      $aExtension = explode("@", $aCall['presence_id']);
      $iExtension = (int) $aExtension[0]; 
      if(isset($aExtensions[$iExtension])) $aExtensions[$iExtension]['callstate'] = $aCall['callstate'];
    }
    unset($aCalls);
  }
  

  return $aExtensions;
}

function get_callFlows($aSelect) {
	global $db, $oCache;

  $aCallFlows = $oCache->get('callflows'); 
  if(!$aCallFlows) {
    $aCallFlows = array();
    //get the extensions and their user status
    $sSql = "SELECT call_flow_extension, call_flow_name, call_flow_status ";
    $sSql .= "FROM v_call_flows AS cf ";
    $sSql .= "WHERE ";

    $sSql .= "cf.domain_uuid = '".$_SESSION['domain_uuid']."' ";
    $sSql .= "ORDER BY cf.call_flow_extension ASC ";

    $prep_statement = $db->prepare($sSql);
    $prep_statement->execute();
    $aRows = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    foreach($aRows as $aRow) {
      $aCallFlows[$aRow['call_flow_extension']] = $aRow;
    }
    unset ($prep_statement, $sSql);
    $oCache->set('callflows', $aCallFlows, PBX_CACHE);
  }
  
  if(is_array($aSelect['callflows']) && count($aSelect['callflows']) > 0) {
    $aOut = array();
    foreach($aSelect['callflows'] as $iCallFlow) {
      if(isset($aCallFlows[$iCallFlow])) $aOut[$iCallFlow] = $aCallFlows[$iCallFlow];
    }
    $aCallFlows = $aOut;
  } 
  return $aCallFlows;
}


function creaStatGroups() {
	global $db, $oCache;
	$ext_user_status = array();

  /*** CREATE TEMP TABLE ***/
  $sSql = "CREATE TEMP TABLE stat_groups (group_name text, extension text)";
  $prep_statement = $db->prepare($sSql);
  $prep_statement->execute();
  
  /*** FILL TEMP TABLE ***/
  $sSql = "INSERT INTO stat_groups (SELECT LEFT(rg.ring_group_name, -1), e.extension FROM v_extensions e 
            JOIN v_ring_group_destinations rgd ON rgd.destination_number = e.extension 
            JOIN v_ring_groups rg ON rg.ring_group_uuid = rgd.ring_group_uuid 
              AND right(trim(ring_group_name), 1) = '$' 
            WHERE e.domain_uuid = '".$_SESSION['domain_uuid']."' 
            ORDER BY 2, 1)";
  $prep_statement = $db->prepare($sSql);
  $prep_statement->execute();
}

function get_call_activity() {
	global $db, $oCache;
  $aResult = array();
  $aResult['active'] = null;
  $aResult['calls'] = null;
	$ext_user_status = array();

  $aExtensions = $oCache->get('extensionsB');
  if(!$aExtensions) {
    creaStatGroups();

    $aExtensions = array();
    //get the extensions and their user status
		$sql = "SELECT ";
		$sql .= "e.extension, ";
		//$sql .= "e.number_alias, ";
		$sql .= "e.effective_caller_id_name, ";
		//$sql .= "e.effective_caller_id_number, ";
		$sql .= "sg.group_name AS call_group, ";
		//$sql .= "e.description, ";
		$sql .= "e.do_not_disturb, ";
		$sql .= "vm.voicemail_enabled, ";
		//$sql .= "u.user_uuid, ";
    //$sql .= "u.user_status, ";
    $sql .= "(SELECT COUNT(*) FROM v_xml_cdr AS c WHERE c.caller_destination = e.extension AND hangup_cause = 'NO_ANSWER' and date(start_stamp) = date(NOW())) as missed_calls ";
		$sql .= "FROM v_extensions as e ";
    $sql .= "JOIN stat_groups AS sg ON sg.extension = e.extension ";
		$sql .= "left outer join v_extension_users as eu on ( eu.extension_uuid = e.extension_uuid and eu.domain_uuid = '".$_SESSION['domain_uuid']."' ) ";
		$sql .= "left outer join v_users as u on ( u.user_uuid = eu.user_uuid and u.domain_uuid = '".$_SESSION['domain_uuid']."' ) ";
		$sql .= "left outer join v_voicemails as vm on ( vm.voicemail_id::text = e.extension and vm.domain_uuid = '".$_SESSION['domain_uuid']."' ) ";
		$sql .= "where ";
		$sql .= "e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "order by ";
		$sql .= "e.extension asc ";
    
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$aExtensions = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    $oCache->set('extensionsB', $aExtensions, PBX_CACHE);
		unset ($prep_statement, $sql);
  }
  //get the registrations
    $obj = new registrations;
    $registrations = $obj->get($profile);
    $extreg = array();
    foreach($registrations as $aRegistration) {
      $extreg[$aRegistration["sip-auth-user"]]["user"] = $aRegistration["user"];
      $extreg[$aRegistration["sip-auth-user"]]["agent"] = $aRegistration["agent"];
      $extreg[$aRegistration["sip-auth-user"]]["status"] = $aRegistration["status"];
    }
    unset($registrations);
	//store extension status by user uuid
		if (isset($aExtensions)) foreach($aExtensions as &$row) {
			if ($row['user_uuid'] != '') {
				$ext_user_status[$row['user_uuid']] = $row['user_status'];
				unset($row['user_status']);
			}
		}

	//send the command
  $aCalls = array();
  $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
  if ($fp) {
    $switch_cmd = 'show channels as json';
    $switch_result = event_socket_request($fp, 'api '.$switch_cmd);
    $json_array = json_decode($switch_result, true);
    foreach($json_array['rows'] as $aLeg) {
      $sUUID = $aLeg['call_uuid'] != '' ? $aLeg['call_uuid'] : $aLeg['uuid'];
      if(isset($aCalls[$sUUID])) {
        if($aCalls[$sUUID]['created_epoch'] > $aLeg['created_epoch']) {
          $aCalls[$sUUID]['created_epoch'] = $aLeg['created_epoch'];
        } else {
          $aCalls[$sUUID]['callstate'] = $aLeg['callstate'];
          //$aCalls[$sUUID]['legs'][] = $aLeg;
        }
      } else {
        $aCalls[$sUUID] = array();
        $aCalls[$sUUID]['callstate'] = $aLeg['callstate'];
        $aCalls[$sUUID]['created_epoch'] = $aLeg['created_epoch'];
        $aCalls[$sUUID]['cid_num'] = $aLeg['cid_num'];
      }
      if($aCalls[$sUUID]['callstate'] == 'ACTIVE' && $aCalls[$sUUID]['dest'] == '8001') {
        $aCalls[$sUUID]['callstate'] = 'MENU';
      }
    }
  }

  $aResult['active'] = count($aCalls) > 0 ? $aCalls : null;
  $aCalls = null;

	//build the response
		$x = 0;
		if (isset($aExtensions)) foreach($aExtensions as &$row) {
			$user = $row['extension'];
			if (strlen($row['number_alias']) > 0 ) {
				$user = $row['number_alias'];
			}

			//add the extension details
				$array[$x] = $row;
        $array[$x]["connected"] = isset($extreg[$user]) ? true : false;
			//set the call detail defaults
				$array[$x]["uuid"] = null;
				$array[$x]["direction"] = null;
				$array[$x]["created"] = null;
				$array[$x]["created_epoch"] = null;
				$array[$x]["name"] = null;
				$array[$x]["state"] = null;
				$array[$x]["cid_name"] = null;
				$array[$x]["cid_num"] = null;
				$array[$x]["ip_addr"] = null;
        $array[$x]["dest"] = null;
				$array[$x]["application"] = null;
				$array[$x]["application_data"] = null;
				$array[$x]["dialplan"] = null;
				$array[$x]["context"] = null;
				$array[$x]["read_codec"] = null;
				$array[$x]["read_rate"] = null;
				$array[$x]["read_bit_rate"] = null;
				$array[$x]["write_codec"] = null;
				$array[$x]["write_rate"] = null;
				$array[$x]["write_bit_rate"] = null;
				$array[$x]["secure"] = null;
				$array[$x]["hostname"] = null;
				$array[$x]["presence_id"] = null;
				$array[$x]["presence_data"] = null;
				$array[$x]["callstate"] = null;
				$array[$x]["callee_name"] = null;
				$array[$x]["callee_num"] = null;
				$array[$x]["callee_direction"] = null;
				$array[$x]["call_uuid"] = null;
				$array[$x]["sent_callee_name"] = null;
				$array[$x]["sent_callee_num"] = null;
				$array[$x]["destination"] = null;

			//add the active call details
				$found = false;
        $now = time();
				if (isset($json_array['rows'])) foreach($json_array['rows'] as &$field) {
					$presence_id = $field['presence_id'];
					$presence = explode("@", $presence_id);
					$presence_id = $presence[0];
					$presence_domain = $presence[1];
					if ($user == $presence_id) {
						if ($presence_domain == $_SESSION['domain_name']) {
							$found = true;
							break;
						}
					}
				}

			//normalize the array
				if ($found) {
					$array[$x]["uuid"] =  $field['uuid'];
					$array[$x]["direction"] = $field['direction'];
					$array[$x]["created"] = $field['created'];
					$array[$x]["created_epoch"] = $field['created_epoch'];
					$array[$x]["name"] = $field['name'];
					$array[$x]["state"] = $field['state'];
					$array[$x]["cid_name"] = $field['cid_name'];
					$array[$x]["cid_num"] = $field['cid_num'];
					$array[$x]["ip_addr"] = $field['ip_addr'];
					$array[$x]["dest"] = $field['dest'];
					$array[$x]["application"] = $field['application'];
					$array[$x]["application_data"] = $field['application_data'];
					$array[$x]["dialplan"] = $field['dialplan'];
					$array[$x]["context"] = $field['context'];
					$array[$x]["read_codec"] = $field['read_codec'];
					$array[$x]["read_rate"] = $field['read_rate'];
					$array[$x]["read_bit_rate"] = $field['read_bit_rate'];
					$array[$x]["write_codec"] = $field['write_codec'];
					$array[$x]["write_rate"] = $field['write_rate'];
					$array[$x]["write_bit_rate"] = $field['write_bit_rate'];
					$array[$x]["secure"] = $field['secure'];
					$array[$x]["hostname"] = $field['hostname'];
					$array[$x]["presence_id"] = $field['presence_id'];
					$array[$x]["presence_data"] = $field['presence_data'];
					$array[$x]["callstate"] = $field['callstate'];
					$array[$x]["callee_name"] = $field['callee_name'];
					$array[$x]["callee_num"] = $field['callee_num'];
					$array[$x]["callee_direction"] = $field['callee_direction'];
					$array[$x]["call_uuid"] = $field['call_uuid'];
					$array[$x]["sent_callee_name"] = $field['sent_callee_name'];
					$array[$x]["sent_callee_num"] = $field['sent_callee_num'];
					$array[$x]["destination"] = $user;

				//calculate and set the call length
					$call_length_seconds = time() - $array[$x]["created_epoch"];
					$call_length_seconds = $now - $array[$x]["created_epoch"];
					$call_length_hour = floor($call_length_seconds/3600);
					$call_length_min = floor($call_length_seconds/60 - ($call_length_hour * 60));
					$call_length_sec = $call_length_seconds - (($call_length_hour * 3600) + ($call_length_min * 60));
					$call_length_min = sprintf("%02d", $call_length_min);
					$call_length_sec = sprintf("%02d", $call_length_sec);
					$call_length = $call_length_hour.':'.$call_length_min.':'.$call_length_sec;
					$array[$x]['call_now'] = $now;
					$array[$x]['call_length'] = $call_length;

				//send the command
					if ($field['state'] != '') {
						if ($fp) {
							$switch_cmd = 'uuid_dump '.$field['uuid'].' json';
							$dump_result = event_socket_request($fp, 'api '.$switch_cmd);
							$dump_array = json_decode($dump_result, true);
							if (isset($dump_array)) foreach ($dump_array as $dump_var_name => $dump_var_value) {
								$array[$x][$dump_var_name] = $dump_var_value;
							}
						}
					}

				}

			//increment the row
				$x++;
		}

    /*** REINDEX ARRAY USING extension WITH DATA USED ***/
		$aCalls = array();
		if (isset($array)) foreach ($array as $index => $subarray) {
			$extension = $subarray['extension'];
			if (isset($subarray)) foreach ($subarray as $field => $value) {
        switch($field) {
          case "extension":
          case "effective_caller_id_name":
          case "call_group":
          case "do_not_disturb":
          case "missed_calls":
          case "callstate":
          case "direction":
          case "dest":
          case "call_length":
          case "voicemail_enabled":
          case "connected": 
            $aCalls[$extension][$field] = $array[$index][$field];
          break;
        }
				unset($array[$index][$field]);
			}
			unset($array[$subarray['extension']]['extension']);
			unset($array[$index]);
		}
    $aResult['calls'] = $aCalls;
    
		return $aResult;
}

function getStats() {
  global $db, $oCache;

  $aStats = array();
  $aStats = $oCache->get('stats');
  if(!$aStats) {
    $aStats = array();
    creaStatGroups();
    $sSql = "SELECT sg.group_name AS call_group 
        , COUNT(*) AS total
        , SUM(CASE c.hangup_cause WHEN 'NO_ANSWER' THEN 0 ELSE duration - waitsec END) AS duration 
        , AVG(CASE c.hangup_cause WHEN 'NO_ANSWER' THEN NULL ELSE waitsec END) AS waitsec
        , SUM(CASE c.hangup_cause WHEN 'NO_ANSWER' THEN 1 ELSE 0 END) as missed 
        , SUM(CASE WHEN DATE_PART('hour', start_stamp) < 10 THEN 1 ELSE 0 END) as a09 
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) = 10  THEN 1 ELSE 0 END) as a10 
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) = 11 THEN 1 ELSE 0 END) as a11
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) = 12 THEN 1 ELSE 0 END) as a12
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) = 13 THEN 1 ELSE 0 END) as a13
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) = 14 THEN 1 ELSE 0 END) as a14
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) = 15 THEN 1 ELSE 0 END) as a15
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) = 16 THEN 1 ELSE 0 END) as a16
        , SUM(CASE WHEN DATE_PART('hour',start_stamp) > 16 THEN 1 ELSE 0 END) as a17 
      FROM v_extensions AS e
      JOIN stat_groups AS sg ON sg.extension = e.extension
      JOIN v_xml_cdr AS c ON c.domain_uuid = e.domain_uuid 
        AND c.destination_number = e.extension  
        AND DATE(start_stamp) = DATE(NOW())
      GROUP BY sg.group_name";
    $prep_statement = $db->prepare($sSql);
    $prep_statement->execute();
    $aStats = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    $oCache->set('stats', $aStats, PBX_CACHE);
    unset ($prep_statement, $sSql);
  }

  return $aStats;
}

$aResult = array();
if(isset($_REQUEST['call']) && $_REQUEST['call'] == 'ACTIVITY') {
  $aSelect = array();
  $aSelect['extensions'] = isset($_REQUEST['extensions']) ? $_REQUEST['extensions'] : array();
  $aResult['extensions'] = get_extensions($aSelect);

  $aSelect = array();
  $aSelect['callflows'] = isset($_REQUEST['callflows']) ? $_REQUEST['callflows'] : array();
  $aResult['callFlows'] = get_callFlows($aSelect);
} else {
  $aResult = get_call_activity();
  $aResult['stats'] = getStats();
}
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
print json_encode($aResult);
?>
