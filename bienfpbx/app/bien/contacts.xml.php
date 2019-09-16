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

  $oResult = new DomDocument();
  $oRoot = $oResult->createElement('CiscoIPPhoneDirectory');
  $oResult->appendChild($oRoot);
  $oTitle = $oResult->createElement('Title', 'Algemeen telefoonboek');
  $oRoot->appendChild($oTitle);
  $oPrompt = $oResult->createElement('Prompt', 'Selecteer');
  $oRoot->appendChild($oPrompt);
  
  switch($_SERVER['REQUEST_METHOD']) {
    case "GET":
      $aSelect = array();
      $aSelect['order_by'] = 'contact_name_family';
      $cContacts = pbxContact::get($aSelect);
      foreach($cContacts as $oContact) {
        if(count($oContact->contact_phones) < 1) continue;
        if($oContact->getFullname() == '') continue;
        $oEntree = $oResult->createElement('DirectoryEntry');
        $oName = $oResult->createElement('Name', $oContact->getFullname());
        $oEntree->appendChild($oName);
        foreach($oContact->contact_phones as $oPhone) {
          $oPhone = $oResult->createElement('Telephone', $oPhone->phone_number);          
          $oEntree->appendChild($oPhone);
        }
        $oRoot->appendChild($oEntree);
      }
    break;
  }
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE");
  header("Content-Type: application/xml");
  print $oResult->saveXML();
  exit();  
?>
