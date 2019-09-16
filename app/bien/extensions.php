<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
 
  Larry H Poort 
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

  require_once "dbObject.inc.php"; 
	require_once "extension.inc.php"; 
	require_once "callFlow.inc.php"; 
	require_once "voicemail.inc.php"; 
  
  $aResult = array("code" => 100, "message" => "Unknown.");
  try {
    switch($_SERVER['REQUEST_METHOD']) {
      case "GET":
        /*** LOGIN CHECK ***/
        if(isset($_GET['extension']) && isset($_GET['password'])) {
          $aResult = pbxExtension::login($_GET['extension'], $_GET['password']);
        } 
        /*** SEARCH ***/
        if(isset($_GET['search'])) {
          $sSearch = preg_replace("/\s+/", "%", $_GET['search']);
          $aSelect = array("LIKE", array("extension", "effective_caller_id_name", "directory_first_name", "directory_last_name", "description"), $sSearch);
          $aResult['extensions'] = pbxExtension::get($aSelect);
          $sSearch = preg_replace("/\s+/", "%", $_GET['search']);
          $aSelect = array("LIKE", array("call_flow_extension", "call_flow_name"), $sSearch);
          $aResult['callFlows'] = pbxCallFlow::get($aSelect);
        } 
      break;
      case "POST":
        if(isset($_POST['extension'])) {
          $oExtension = new pbxExtension($_POST['extension']);
          /*** DND ***/
          $oExtension->setAttribute("do_not_disturb", $_POST['do_not_disturb'] === "true" ? "true" : "false");
          $oVoicemail = $oExtension->getAttribute("voicemail");
          $oVoicemail->setAttribute("voicemail_enabled", $_POST['voicemail_enabled'] === "true" ? "true" : "false");
          if($_POST['forward_all_enabled'] === "true") {
            $oExtension->setAttribute("forward_all_enabled", "true");
            $oExtension->setAttribute("forward_all_destination", $_POST['forward_all_destination']);
            $sFWExtension = $oExtension->getAttribute("forward_all_destination") . "@" . $oExtension->getAttribute("user_context");
            $oFWExt = new pbxExtension($sFWExtension);
            $forward_caller_id_uuid = $oFWExt->getAttribute("extension_uuid");
            $call_forward = new call_forward;
            $call_forward->domain_uuid = $_SESSION['domain_uuid'];
            $call_forward->domain_name = $_SESSION['domain_name'];
            $call_forward->extension_uuid = $oExtension->getAttribute("extension_uuid");
            $call_forward->forward_all_destination = $oExtension->getAttribute("forward_all_destination");
            $call_forward->forward_all_enabled = $oExtension->getAttribute("forward_all_destination");
            $call_forward->forward_caller_id_uuid = $forward_caller_id_uuid;
            $call_forward->set();
            unset($call_forward);          
          } else {
            $oExtension->setAttribute("forward_all_enabled", "false");
          }
          $oExtension->setAttribute("effective_caller_id_name", $_POST['effective_caller_id_name']);
          $oExtension->setAttribute("directory_first_name", $_POST['directory_first_name']);
          if(strlen($_POST['directory_mid_fix']) > 0) $oExtension->setAttribute("directory_mid_fix", $_POST['directory_mid_fix']);
          $oExtension->setAttribute("directory_last_name", $_POST['directory_last_name']);
          $oExtension->save();

          $dnd = new do_not_disturb;
          $dnd->domain_uuid = $_SESSION['domain_uuid'];
          $dnd->domain_name = $_SESSION['domain_name'];
          $dnd->extension_uuid = $oExtension->getAttribute("extension_uuid");
          $dnd->extension = $oExtension->getAttribute("extension");
          $dnd->enabled = $oExtension->getAttribute("do_not_disturb");
          $dnd->set();
          $dnd->user_status();
          unset($dnd);
          
          //clear the cache
          $cache = new cache;
          $cache->delete("directory:" . $oExtension->getAttribute("extension")."@".$_SESSION['domain_name']);
          if(strlen($oExtension->getAttribute("number_alias")) > 0){
            $cache->delete("directory:" . $oExtension->getAttribute("number_alias") . "@".$_SESSION['domain_name']);
          }
          
          $aResult["code"] = 0;
          $aResult["message"] = "Data has been saved.";
          $aResult["extension"] = (array) $oExtension->toArray();
        } 
      break;
    }
  } catch(Exception $oError) {
    $aResult["code"] = 200;
    $aResult["message"] = $oError->getMessage();
  }
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json");
  print json_encode($aResult);
  exit();  
?>
