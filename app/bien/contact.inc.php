<?php
class pbxContact {

  static function del($uSelect){
    global $db;
    /*** CLEAR PHONES CONTACT ***/
    $sSql = "DELETE FROM v_contact_phones WHERE contact_uuid ";
    if(is_array($uSelect)) {
      $sSql .= "IN ('" . implode("'. '", $uSelect) . "')";
    } else {  
      $sSql .= "= '" . $uSelect . "'";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();

    /*** CLEAR EMAILS CONTACT ***/
    $sSql = "DELETE FROM v_contact_emails WHERE contact_uuid ";
    if(is_array($uSelect)) {
      $sSql .= "IN ('" . implode("'. '", $uSelect) . "')";
    } else {  
      $sSql .= "= '" . $uSelect . "'";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();

    /*** CLEAR CONTACTS ***/
    $sSql = "DELETE FROM v_contacts WHERE contact_uuid ";
    if(is_array($uSelect)) {
      $sSql .= "IN ('" . implode("'. '", $uSelect) . "')";
    } else {  
      $sSql .= "= '" . $uSelect . "'";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
  }
  
  static function get($aSelect) {
    $__cContacts = array();
    global $db;
    $sSql = "SELECT c.contact_uuid ";
    $sSql .= "FROM v_contacts AS c ";
    $sSql .= "WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
    
    if (strlen($aSelect["phone_number"]) > 0) {
      $phone_number = preg_replace('{\D}', '', $aSelect["phone_number"]);
      $sSql .= "AND contact_uuid in ( ";
      $sSql .= "	SELECT contact_uuid FROM v_contact_phones ";
      $sSql .= "	WHERE phone_number LIKE '%".$phone_number."%' ";
      $sSql .= ") \n";
    } else {
      if (strlen($aSelect["search_all"]) > 0) {
        $search_all = strToLower($aSelect["search_all"]);
        if (is_numeric($search_all)) {
          $sSql .= "AND contact_uuid in ( \n";
          $sSql .= "	SELECT contact_uuid from v_contact_phones ";
          $sSql .= "	WHERE phone_number LIKE '%".$search_all."%' ";
          $sSql .= ") \n";
        } else {
          $sSql .= "AND contact_uuid in ( \n";
          $sSql .= "	SELECT contact_uuid FROM v_contacts ";
          $sSql .= "	WHERE domain_uuid = '".$_SESSION['domain_uuid']."' \n";
          $sSql .= "	AND LOWER(CONCAT_WS(' '";
          $sSql .= "		, contact_organization";
          $sSql .= "		, contact_name_given";
          $sSql .= "		, contact_name_family";
          $sSql .= "		, contact_nickname";
          $sSql .= "		, contact_title";
          $sSql .= "		, contact_category";
          $sSql .= "		, contact_role";
          $sSql .= "		, contact_url";
          $sSql .= "		, contact_time_zone";
          $sSql .= "		, contact_note";
          $sSql .= "		, contact_type)) LIKE '%".$search_all."%' \n";
          $sSql .= ") \n";
        }
      }
    }
    
    if($aSelect["order_by"] != "") {
      $sSql .= "ORDER BY " . $aSelect["order_by"];
    } else {
      $sSql .= "ORDER BY last_mod_date DESC";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    $aContacts = $oPrep->fetchAll(PDO::FETCH_NAMED);
    foreach($aContacts as $aContact) {
      $__cContacts[] = new pbxContact($aContact["contact_uuid"]);
    }
    return $__cContacts;
  }
  
	public function __construct($sContact){
    global $db;
    $sSql = "SELECT * FROM v_contacts AS c 
              WHERE contact_uuid = '" .$sContact. "'
                AND domain_uuid = '".$_SESSION['domain_uuid']."' ";
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    $aContact = $oPrep->fetch(PDO::FETCH_NAMED);
    if($aContact) {
      foreach((array) $aContact as $sKey => $uValue) {
        $this->setAttribute($sKey, $uValue);
      }
      $Select = array();
      $aSelect["contact_uuid"] = $this->getAttribute("contact_uuid"); 
      $aSelect["order_by"] = "phone_description"; 
      $this->setAttribute("contact_phones", pbxPhone::get($aSelect));
      $aSelect["order_by"] = "email_description"; 
      $this->setAttribute("contact_emails", pbxEmail::get($aSelect));
    } else {
      $this->setAttribute("contact_uuid", 0);
      $this->setAttribute("domain_uuid", $_SESSION['domain_uuid']);
      $this->setAttribute("last_mod_date", date('c'));
      $this->setAttribute("contact_phones", array());
      $this->setAttribute("contact_emails", array());
    }
	}  

  public function getAttribute($sName) {
    return $this->${sName};
  }
  
  public function setAttribute($sName, $uValue) {
    return $this->${sName} = $uValue;
  }
  
  
  public function getFullname() {
    $aName = array();
    if($this->getAttribute("contact_nickname") != '') $aName[] = $this->getAttribute("contact_nickname");
    if($this->getAttribute("contact_name_suffix") != '') $aName[] = $this->getAttribute("contact_name_suffix");
    if($this->getAttribute("contact_name_family") != '') $aName[] = $this->getAttribute("contact_name_family");
    
    return implode(' ', $aName);
  }
  
  public function addPhone($oPhone) {
    //if($oPhone instanceof pbxPhone) throw new Exception("Not a phone object");
    $aPhones = $this->getAttribute("contact_phones");
    $aPhones[] = $oPhone;
    $this->setAttribute("contact_phones", $aPhones);
  }
  
  public function addEmail($oEmail) {
    //if($oEmail instanceof pbxEmail) throw new Exception("Not an email object");
    $aEmails = $this->getAttribute("contact_emails");
    $aEmails[] = $oEmail;
    $this->setAttribute("contact_emails", $aEmails);
  }
  
  public function save() {
    global $db;
    /*** INSERT ***/
    if($this->getAttribute("contact_uuid") === 0) {
      $this->setAttribute("contact_uuid", uuid());
      $sSql = "INSERT INTO v_contacts \n";
      $aKeys = array();
      $aValues = array();
      foreach($this as $sKey => $uValue ) {
        if(!$uValue) continue;
        if(is_array($uValue)) continue;
        $aKeys[] = $sKey;
        $aValues[] = (is_numeric($uValue) ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= "(" . implode(", ", $aKeys) . ") \n";
      $sSql .= "VALUES (" . implode(", ", $aValues) . ") \n";
    } else {
      $sSql = "UPDATE v_contacts SET \n";
      $aItems = array();
      foreach($this as $sKey => $uValue ) {
        if(is_array($uValue)) continue;
        $aItems[] = $sKey ." = ". (is_numeric($uValue) ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= implode(", ", $aItems) . " \n";
      $sSql .= "WHERE contact_uuid= '". $this->getAttribute("contact_uuid") . "'";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    
    foreach($this->getAttribute("contact_phones") as $oPhone) {
      $oPhone->setAttribute("contact_uuid", $this->getAttribute("contact_uuid"));
      $oPhone->save();
    }
    foreach($this->getAttribute("contact_emails") as $oEmail) {
      $oEmail->setAttribute("contact_uuid", $this->getAttribute("contact_uuid"));
      $oEmail->save();
    }
  }
}
?>
