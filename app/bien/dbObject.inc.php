<?php
class dbObject {
  /*** each child should carry:**/
  static protected $__TABLE__ = null;
  static protected $__UUID__ = null;

  static function get($aSelect) {
    $__cObjects = array();

    global $db;
    $sSql = "SELECT " . static::$__UUID__ . " FROM " .static::$__TABLE__. " AS e 
              WHERE domain_uuid = '".$_SESSION['domain_uuid']."' ";

    if(is_array($aSelect)) {
      $sKind = strtoupper(trim($aSelect[0]));
      switch($sKind) {
        case "AND":
        case "OR":
          if(!is_array($aSelect[1]))  throw new Exception("Second parameter should be an array of field names with select values.");
          $aWhere = array();
          foreach($aSelect[1] as $sKey => $uValue) {
            $aWhere[] = $sKey . " = " . (is_numeric($uValue) ? $uValue : "'" .preg_replace("/\'/", "\\'", $uValue). "'");
          }
          $sSql .= " AND (" . implode("\r\n\t" . $sKind, $aWhere) . ")";
        break;
        case "LIKE":
          if(!is_array($aSelect[1]))  throw new Exception("Second parameter should be an array of field names");
          if(!is_scalar($aSelect[2]))  throw new Exception("Third parameter should contain a search value");
          $sSql .= " AND (CONCAT_WS('||', " . implode(", ", $aSelect[1]) . ") ILIKE '%" .$aSelect[2]. "%') ";
        break;
        default:
          throw new Exception("First select parameter should read `AND´, `OR´ or `LIKE´.");
        break;
      }
    }
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();

    $aObjects = $oPrep->fetchAll(PDO::FETCH_NAMED);
    foreach($aObjects as $aObject) {
      $__cObjects[] = new static($aObject[static::$__UUID__]);
    }
    return $__cObjects;
  }
  
 
  public function preConstruct() {
    return true;
  }

	public function __construct($uUUID){
    global $db;
    $sSql = "SELECT * FROM " .static::$__TABLE__. " AS e ";
    $sSql .= "WHERE " . static::$__UUID__ . " = '" .$uUUID. "' ";
    $sSql .= "AND domain_uuid = '".$_SESSION['domain_uuid']."' ";
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    $aTuple = $oPrep->fetch(PDO::FETCH_NAMED);
    if($aTuple) {
      foreach((array) $aTuple as $sKey => $uValue) {
        $this->setAttribute($sKey, $uValue);
      }
    } else {
      $this->setAttribute(static::$__UUID__, 0);
      $this->setAttribute("domain_uuid", $_SESSION['domain_uuid']);
    }
	}  
  
  public function postConstruct() {
    return true;
  }

  public function getAttribute($sName) {
    return $this->${sName};
  }
  
  public function setAttribute($sName, $uValue) {
    if($sName == "password") {
      $this->__password = $uValue;
      $uValue = md5($uValue);
    } 
    return $this->${sName} = $uValue;
  }
  
  public function toArray($aValue = null) {
    if($aValue === null) $aValue = $this;
    $aResult = array();
    foreach($aValue as $sKey => $uValue ) {
      /*** SKIP PRIVATE FIELDS ***/
      if(substr($sKey, 0, 2) == "__") continue;
      if(is_array($uValue)) $aResult[$sKey] = $this->toArray($uValue);
      elseif(is_object($uValue)) $aResult[$sKey] = $this->toArray($uValue);
      else $aResult[$sKey] = $uValue;
    }
    return $aResult;
  }
  
  public function toJSON() {
    return json_encode($this->toArray());
  }
  
  public function preSave() {
    return true;
  }

  public function save() {
    global $db;

    /*** INSERT ***/
    if($this->getAttribute(static::$__UUID__) === 0) {
      $this->setAttribute(static::$__UUID__, uuid());
      $sSql = "INSERT INTO " .static::$__TABLE__. " \n";
      $aKeys = array();
      $aValues = array();
      foreach($this as $sKey => $uValue ) {
        if(substr($sKey, 0, 2) == "__")  continue;
        if(!$uValue) continue;
        if(is_array($uValue)) continue;
        if(is_object($uValue)) continue;
        if($sKey == "password") $uValue = $this->__password;
        $aKeys[] = $sKey;
        $aValues[] = (is_numeric($uValue) && substr($uValue, 0, 1) != "0" ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= "(" . implode(", ", $aKeys) . ") \n";
      $sSql .= "VALUES (" . implode(", ", $aValues) . ") \n";

    /*** UPDATE ***/
    } else {
      $sSql = "UPDATE " .static::$__TABLE__. " SET \n";
      $aItems = array();
      foreach($this as $sKey => $uValue ) {
        if(substr($sKey, 0, 2) == "__")  continue;
        if(is_array($uValue)) continue;
        if(is_object($uValue)) continue;
        if($sKey == "password") $uValue = $this->__password;
        $aItems[] = $sKey ." = ". (is_numeric($uValue) && substr($uValue, 0, 1) != "0"  ? $uValue : ( strlen($uValue) ? "'" .preg_replace("/\'/", "''", $uValue). "'" : "NULL"));
      }
      $sSql .= implode(", ", $aItems) . " \n";
      $sSql .= "WHERE " .static::$__UUID__. " = '". $this->getAttribute(static::$__UUID__) . "'";
    }    
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();    
  }

}
?>
