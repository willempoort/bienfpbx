<?php
class pbxExtension extends dbObject {
  static protected $__TABLE__ = 'v_extensions';
  static protected $__UUID__ = 'extension_uuid';
  protected $__password = null;

  static function login($sExtension, $sPassword) {
    global $db;
    $aExtension = explode('@', $sExtension);
    
    $sSql = "SELECT " . static::$__UUID__ . ", password FROM " .static::$__TABLE__. " AS e ";
    $sSql .= "WHERE extension = :extension ";
    $sSql .= "AND domain_uuid = :domain_uuid ";
    if(count($aExtension) > 1) $sSql .= "AND user_context = :user_context ";
    
    $oPrep = $db->prepare($sSql);
    $oPrep->bindParam(':extension', $aExtension[0]);
    $oPrep->bindParam(':domain_uuid', $_SESSION['domain_uuid']);
    if(count($aExtension) > 1) $oPrep->bindParam(':user_context', $aExtension[1]);
    $oPrep->execute();
    
    $aTuple = $oPrep->fetch(PDO::FETCH_NAMED);
    if($aTuple) {
      if(strlen($sPassword) == 32 && $sPassword != md5($aTuple['password'])) {
        /*** EXPIRED ***/
        $aResult['code'] = 10;
        $aResult['message'] = "Uw login gegevens zijn niet meer correct.";
      } elseif(strlen($sPassword) < 32 && $sPassword != $aTuple['password'])  {
        /*** INCORRECT ***/
        $aResult['code'] = 20;
        $aResult['message'] = "Uw login gegevens zijn onjuist.";
      } else {
        /*** CONNECTED ***/
        $aResult['code'] = 0;
        $aResult['message'] = "U bent verbonden met de server.";
        $aResult['extension'] = new pbxExtension($aTuple[static::$__UUID__]);
      }
    } else {
      $aResult['code'] = 30;
      $aResult['message'] = "De extensie (" .$sExtension. ") is niet gedefiniëerd.";
    }
    return $aResult;
  }
  
	public function __construct($uUUID){
    global $db;
    $aParams =array();
    $aUUID = explode('@', $uUUID);
    $sSql = "SELECT * FROM " .static::$__TABLE__. " AS e ";
    
    if(is_numeric($aUUID[0])) {
      $sSql .= "WHERE extension = :extension ";
      $aParams[':extension'] = $aUUID[0];
      if(count($aUUID) > 1) {
        $sSql .= "AND user_context = :user_context ";
        $aParams[':user_context'] = $aUUID[1];
      }
    } else {
      $sSql .= "WHERE " . static::$__UUID__ . " = :uuid ";
      $aParams[':uuid'] = $aUUID[0];
    }
    $sSql .= "AND domain_uuid = :domain_uuid ";
    $aParams[':domain_uuid'] = $_SESSION['domain_uuid'];

    $oPrep = $db->prepare($sSql);
    foreach($aParams as $kParam => $uValue) {
      $oPrep->bindParam($kParam, $aParams[$kParam]);
    }
    $oPrep->execute();
    $aTuple = $oPrep->fetch(PDO::FETCH_NAMED);
    if($aTuple) {
      foreach((array) $aTuple as $sKey => $uValue) {
        $this->setAttribute($sKey, $uValue);
      }
      $this->setAttribute("voicemail", new pbxVoicemail($this->getAttribute("extension")));
    } else {
      $this->setAttribute(static::$__UUID__, 0);
      $this->setAttribute("domain_uuid", $_SESSION['domain_uuid']);
    }

    /*** RETREIVE PREFIX LAST NAME ***/
    $aLastName = null;
    if(preg_match_all("/^([^\,]+)\,\s+(.+)$/", $this->getAttribute("directory_last_name"), $aLastName)) {
      $this->setAttribute("directory_last_name", $aLastName[1]);
      $this->setAttribute("directory_mid_fix", $aLastName[2]);
    }
	}

  function save(){
    $aLastName = null;
    if(strlen($this->directory_mid_fix) > 0) {
      $this->setAttribute("directory_last_name", $this->getAttribute("directory_last_name") . ", " . $this->getAttribute("directory_mid_fix"));
    }
    unset($this->directory_mid_fix);
    parent::save();
    if($this->voicemail instanceof pbxVoicemail) $this->voicemail->save();
  } 
}
?>
