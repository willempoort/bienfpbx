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
    XML Phone List from Contacts Database 
  
  Willem M. Poort
    Max records per page (Cisco Phones has a limit of 30 - 100 records)
*/

//includes
  require_once "root.php";
  require_once "resources/require.php";
  require_once "contact.inc.php"; 
  require_once "phone.inc.php"; 
  require_once "email.inc.php"; 
 
// User configurable
//    $max_chars    = 20;                   // Max number of chars in an entry (truncate)
  $title = "Bien XML Telefoonboek";  // Title in the returned phonedirectory
  $num_recs = 20;                   // How many entries to return in one page
  $prompt = "Next is volgende ".$num_recs;    // Prompt displayed on phone
// End User configurable

  $oResult = new DomDocument();
  $oRoot = $oResult->createElement('CiscoIPPhoneDirectory');
  $oResult->appendChild($oRoot);
  $oTitle = $oResult->createElement('Title', $title);
  $oRoot->appendChild($oTitle);
  $oPrompt = $oResult->createElement('Prompt', $prompt);
  $oRoot->appendChild($oPrompt);
  
// Get the "page" number to return from the URL used to call this script
  if (isset($_GET['page'])) {
    $page = $_GET['page'];
  } else {
    $page = 1;
  }
  
 switch($_SERVER['REQUEST_METHOD']) {
    case "GET":
      $aSelect = array();
      $aSelect['order_by'] = 'contact_name_family LIMIT '.$num_recs.' OFFSET '.(($page-1)*$num_recs);
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
  
// Refresh header contains the URL that is called by the "next" button on the phone
  header('Content-Type: text/xml');
  header('Refresh: 0; url=' . get_url() . '?page=' . ($page + 1));

  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE");
  header("Content-Type: application/xml");
  print $oResult->saveXML();

  exit();  
  
/* get_url()
  See: http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
  This will return the URL that this script is called with but with all parameters stripped out
 */
  function get_url() {
    $s = &$_SERVER;
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    $uri = $protocol . '://' . $host . $s['REQUEST_URI'];
    $segments = explode('?', $uri, 2);
    $url = $segments[0];
    return $url;
 }

?>

