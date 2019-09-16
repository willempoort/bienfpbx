<?php
class pbxCallFlow extends dbObject {
  static protected $__TABLE__ = 'v_call_flows';
  static protected $__UUID__ = 'call_flow_uuid';

  static function toggle($sExtension) {
    global $db;
    $sSql = "SELECT " .self::$__UUID__. ", call_flow_status FROM ".self::$__TABLE__." WHERE call_flow_extension = '" .$sExtension. "'";
    $oPrep = $db->prepare(check_sql($sSql));
    $oPrep->execute();
    
    $aTuple = $oPrep->fetch(PDO::FETCH_NAMED);
    if($aTuple) {
      $oCallFlow = new pbxCallFlow($aTuple[self::$__UUID__]);
      $oCallFlow->setAttribute('call_flow_status', $oCallFlow->getAttribute('call_flow_status') == "true" ? "false" : "true");
      $oCallFlow->save();
    } 
    
    return $oCallFlow;
  }

}
?>
