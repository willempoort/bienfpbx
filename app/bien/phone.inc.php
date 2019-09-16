<?php
class pbxPhone {

  static function del($uSelect){
    global $db;
    $sSql = "DELETE FROM v_contact_phones WHERE contact_phone_uuid ";
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
    $__cPhones = array();
    $sSql = "SELECT p.contact_phone_uuid ";
    $sSql .= "FROM v_contact_phones AS p ";
    $sSql .= "WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";
    
    if (strlen($aSelect["contact_uuid"]) > 0) {
      $sSql .= "AND contact_uuid = '" .$aSelect["contact_uuid"]. "' ";
    }

    if($aSelect["order_by"] != "") {
      $sSql .= "ORDER BY " . $aSelect["order_by"];
    } else {
      $sSql .= "ORDER BY phone_label ASC ";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    $aPhones = $oPrep->fetchAll(PDO::FETCH_NAMED);
    foreach($aPhones as $aPhone) {
      $__cPhones[] = new pbxPhone($aPhone["contact_phone_uuid"]);
    }
    return $__cPhones;
  }
  
	public function __construct($sPhone){
    global $db;
    $sSql = "SELECT * FROM v_contact_phones AS p 
              WHERE contact_phone_uuid = '" .$sPhone. "'
                AND domain_uuid = '".$_SESSION['domain_uuid']."' ";
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    $aContact = $oPrep->fetch(PDO::FETCH_NAMED);
    if($aContact) {
      foreach((array) $aContact as $sKey => $uValue) {
        $this->setAttribute($sKey, $uValue);
      }
    } else {
      $this->setAttribute("contact_phone_uuid", 0);
      $this->setAttribute("domain_uuid", $_SESSION['domain_uuid']);
      $this->setAttribute("phone_type_voice", 1);
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
    if($this->getAttribute("contact_phone_uuid") === 0) {
      $this->setAttribute("contact_phone_uuid", uuid());
      $sSql = "INSERT INTO v_contact_phones \n";
      $aKeys = array();
      $aValues = array();
      foreach($this as $sKey => $uValue ) {
        if(!$uValue) continue;
        $aKeys[] = $sKey;
        $aValues[] = (is_numeric($uValue) && !preg_match("/^(0|\+)/", $uValue) ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= "(" . implode(", ", $aKeys) . ") \n";
      $sSql .= "VALUES (" . implode(", ", $aValues) . ") \n";
    } else {
      $sSql = "UPDATE v_contact_phones SET \n";
      $aItems = array();
      foreach($this as $sKey => $uValue ) {
        $aItems[] = $sKey ." = ". (is_numeric($uValue) && !preg_match("/^(0|\+)/", $uValue) ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= implode(", ", $aItems) . " \n";
      $sSql .= "WHERE contact_phone_uuid = '". $this->getAttribute("contact_phone_uuid") . "'";
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
  }
}
?>
