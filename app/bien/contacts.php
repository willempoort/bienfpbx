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
	require_once "contact.inc.php"; 
	require_once "phone.inc.php"; 
	require_once "email.inc.php"; 
  
  switch($_SERVER['REQUEST_METHOD']) {
    case "DELETE":
      parse_str( file_get_contents( 'php://input' ), $_DELETE);
      if(isset($_DELETE['contact_uuid'])) {
        pbxContact::del($_DELETE['contact_uuid']);
        $aResult = array("contact_uuid" => $_DELETE['contact_uuid'], "status" => "deleted");
      }
      if(isset($_DELETE['contact_phone_uuid'])) {
        pbxPhone::del($_DELETE['contact_phone_uuid']);
        $aResult = array("contact_phone_uuid" => $_DELETE['contact_phone_uuid'], "status" => "deleted");
      }
      if(isset($_DELETE['contact_email_uuid'])) {
        pbxEmail::del($_DELETE['contact_email_uuid']);
        $aResult = array("contact_phone_uuid" => $_DELETE['contact_email_uuid'], "status" => "deleted");
      }
      break;
    case "GET":
      if(isset($_GET['contact_uuid'])) {
        $oContact = new pbxContact($_GET['contact_uuid']);
        $aResult = (array) $oContact;
      } else {
        $aSelect = array();
      //get the search criteria
        $aSelect['search_all'] = check_str($_GET["search_all"]);
        $aSelect['phone_number'] = check_str($_GET["phone_number"]);

      //get variables used to control the order
        $aSelect['order_by'] = check_str($_GET["order_by"]);
        $aSelect['order'] = check_str($_GET["order"]);
        $aResult = pbxContact::get($aSelect);
      } 
    break;
    case "POST":
      $oContact = new pbxContact($_POST['contact_uuid']);
      $oContact->setAttribute("contact_name_prefix", $_POST['contact_name_prefix']);
      $oContact->setAttribute("contact_name_given", $_POST['contact_name_given']);
      $oContact->setAttribute("contact_nickname", $_POST['contact_name_given']);
      $oContact->setAttribute("contact_name_middle", $_POST['contact_name_middle']);
      $oContact->setAttribute("contact_name_suffix", $_POST['contact_name_suffix']);
      $oContact->setAttribute("contact_name_family", $_POST['contact_name_family']);
      $oContact->setAttribute("contact_organization", $_POST['contact_organization']);
      foreach($_POST['contact_phones'] as $sKey => $uValue) {
        if(strlen( $_POST['contact_phones'][$sKey]['number']) == 0) continue;
        $oPhone = new pbxPhone($sKey);
        $oPhone->setAttribute("phone_label", $_POST['contact_phones'][$sKey]['label']);
        $oPhone->setAttribute("phone_number", $_POST['contact_phones'][$sKey]['number']);
        $oPhone->setAttribute("phone_description", $_POST['contact_phones'][$sKey]['sequence']);
        $oContact->addPhone($oPhone);
      }
      foreach($_POST['contact_emails'] as $sKey => $uValue) {
        if(strlen( $_POST['contact_emails'][$sKey]['email']) == 0) continue;
        $oEmail = new pbxEmail($sKey);
        $oEmail->setAttribute("email_label", $_POST['contact_emails'][$sKey]['label']);
        $oEmail->setAttribute("email_address", $_POST['contact_emails'][$sKey]['email']);
        $oEmail->setAttribute("email_description", $_POST['contact_emails'][$sKey]['sequence']);
        $oContact->addEmail($oEmail);
      }
      $oContact->save();
      $aResult = (array) $oContact;
    break;
  }
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE");
  header("Content-Type: application/json");
  print json_encode($aResult);
  exit();  
?>
