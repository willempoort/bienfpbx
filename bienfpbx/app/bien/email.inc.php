<?php
class pbxEmail {

  static function del($uSelect){
    global $db;
    $sSql = "DELETE FROM v_contact_emails WHERE contact_email_uuid ";
    if(is_array($uSelect)) {
      $sSql .= "IN ('" . implode("'. '", $uSelect) . "')";
    } else {  
      $sSql .= "= '" . $uSelect . "'";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();

  }
  
  static function get($aSelect) {
    global $db;
    $__cEmails = array();
    $sSql = "SELECT e.contact_email_uuid ";
    $sSql .= "FROM v_contact_emails AS e ";
    $sSql .= "WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
    
    if (strlen($aSelect["contact_uuid"]) > 0) {
      $sSql .= "AND contact_uuid = '" .$aSelect["contact_uuid"]. "' ";
    }

    if($aSelect["order_by"] != "") {
      $sSql .= "ORDER BY " . $aSelect["order_by"];
    } else {
      $sSql .= "ORDER BY email_label ASC ";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    $aEmails = $oPrep->fetchAll(PDO::FETCH_NAMED);
    foreach($aEmails as $aEmail) {
      $__cEmails[] = new pbxEmail($aEmail["contact_email_uuid"]);
    }
    return $__cEmails;
  }
  
	public function __construct($sEmail){
    global $db;
    $sSql = "SELECT * FROM v_contact_emails AS p 
              WHERE contact_email_uuid = '" .$sEmail. "'
                AND domain_uuid = '".$_SESSION['domain_uuid']."' ";
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    $aContact = $oPrep->fetch(PDO::FETCH_NAMED);
    if($aContact) {
      foreach((array) $aContact as $sKey => $uValue) {
        $this->setAttribute($sKey, $uValue);
      }
    } else {
      $this->setAttribute("contact_email_uuid", 0);
      $this->setAttribute("domain_uuid", $_SESSION['domain_uuid']);
    }
	}  

  public function getAttribute($sName) {
    return $this->${sName};
  }
  
  public function setAttribute($sName, $uValue) {
    return $this->${sName} = $uValue;
  }
  
  public function save() {
    global $db;
    /*** INSERT ***/
    if($this->getAttribute("contact_email_uuid") === 0) {
      $this->setAttribute("contact_email_uuid", uuid());
      $sSql = "INSERT INTO v_contact_emails \n";
      $aKeys = array();
      $aValues = array();
      foreach($this as $sKey => $uValue ) {
        if(!$uValue) continue;
        $aKeys[] = $sKey;
        $aValues[] = (is_numeric($uValue) ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= "(" . implode(", ", $aKeys) . ") \n";
      $sSql .= "VALUES (" . implode(", ", $aValues) . ") \n";
    } else {
      $sSql = "UPDATE v_contact_emails SET \n";
      $aItems = array();
      foreach($this as $sKey => $uValue ) {
        $aItems[] = $sKey ." = ". (is_numeric($uValue) && substr($uValue,0, 1) != "0" ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= implode(", ", $aItems) . " \n";
      $sSql .= "WHERE contact_email_uuid = '". $this->getAttribute("contact_email_uuid") . "'";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
  }
}
?>
