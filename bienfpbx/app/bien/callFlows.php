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
	require_once "callFlow.inc.php"; 
  
  $aResult = array("code" => 100, "message" => "Unknown.");
  try {
    switch($_SERVER['REQUEST_METHOD']) {
      case "POST":
        if(isset($_POST['callflow'])) {
          $oCallFlow = pbxCallFlow::toggle($_POST['callflow']);

          $aResult["code"] = 0;
          $aResult["message"] = "Data has been saved.";
          $aResult["callFlow"] = (array) $oCallFlow->toArray();
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
