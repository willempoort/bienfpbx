<?php
class pbxVoicemail extends dbObject {
  static protected $__TABLE__ = 'v_voicemails';
  static protected $__UUID__ = 'voicemail_uuid';
  
  public function __construct($uUUID){
    global $db;
    $sSql = "SELECT * FROM " .static::$__TABLE__. " AS e ";
    
    if(is_numeric($uUUID)) {
      $sSql .= "WHERE voicemail_id = '" .$uUUID. "' ";
    } else {
      $sSql .= "WHERE " . static::$__UUID__ . " = '" .$uUUID. "' ";
    }
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
      if(is_numeric($uUUID)) $this->setAttribute("extension", $uUUID);
    }
	}  


  
}
?>
