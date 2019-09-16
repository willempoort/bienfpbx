<?php
	require_once "root.php";
	require_once "resources/require.php";
  require_once "config.inc.php";
  try {
    //send the command
    $sUser = $_REQUEST['user'];
    $sDestination = $_REQUEST['destination'];
    $sDestinationName = $_REQUEST['destination_name'];
    if(strlen($sDestination) < 5) {
      $sCommand = "&bridge(user/" .$sDestination. ")";
    } else {
      $sCommand = "&bridge(sofia/gateway/" .PBX_GATEWAY_UUID. "/" .$sDestination. ")";
    }
    $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
    if ($fp) {
      $switch_cmd = "originate user/" . $sUser . " " . $sCommand . " XML default '$sDestinationName' '$sDestination'";

      $sResult = event_socket_request($fp, 'api ' . $switch_cmd);
      if(strToUpper(substr($sResult, 0, 4)) == "-ERR") throw new Exception($sResult, 1050);
      $aResult = array("code" => 0, "message" => $sResult);
    } else {
      throw new Exception("Could not connect to Freeswitch.", 1000);
    }
  } catch(Exception $oError) {
    $aResult = array("code" => $oError->getCode(), "message" => $oError->getMessage());
  }
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json");
  print json_encode($aResult);
  exit();  
?>
