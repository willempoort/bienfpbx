
var doActivity = true;
var aGroups = [];
$(document).ready(function() {
  function shoTime(iSeconds) {
    var iMinutes = Math.floor(iSeconds / 60);
    iSeconds -= iMinutes * 60; 
    var sTime =  ("0" + iMinutes).substr(-2) + ":" +  ("0" + iSeconds).substr(-2);
    return sTime; 
  }

  function getActivity() {
    if(doActivity === false) return false;
    /*** CREATE CONTAINER ***/
    if($('ul#panel').length == 0) {
      var $Div = $('<div id="waiting"><h2>Wachtrij</h2><ul></ul></div>');
      $(document.body).append($Div); 
      var $UL = $('<ul id="panel"></ul>');
      $(document.body).append($UL); 
    } 
    $.get("../get_call_activity.php", function(oResult) {
      $("h1.error").remove();
      
      /*** CALLS ***/
      var cExtensions = oResult.calls;
      for(var e in cExtensions) {
        var oExtension = cExtensions[e];
        if(cExtensions[e].effective_caller_id_name === null) continue; 
        if(!cExtensions[e].extension) {
          $(document.body).append("<h1 class='error'>Geen extensions ingelezen</h1>");
          return false;
        }
        var sGroup = oExtension.call_group.toLowerCase().replace(/\s+/g, '');
        if($('li#' + sGroup).length == 0) {
          $('ul#panel').append('<li id="' + sGroup + '"><h2>' + oExtension.call_group + '</h2><ul></ul></li>');
        } else if($('li#' + sGroup + ' h2').length == 0) {
          $('li#' + sGroup).append('<h2>' + oExtension.call_group + '</h2><ul></ul>');
        }
        if($('li#ext' + e).length == 0) {
          $(document.body).append('<li id="ext' + e + '">'
            + '<svg width="100%" height="100%" viewBox="0 0 300 300">' 
            + '<use id="rec" x="0" y="0" xlink:href="telephone.svg#phone" href="telephone.svg#receiver"/>'
            + '<use id="phone" x="0" y="0" xlink:href="telephone.svg#phone" href="telephone.svg#phone"/>'
            + '<svg id="voice" x="150" y="198" viewBox="0 0 100 100">' 
              + '<use xlink:href="voicemail.svg#rec" href="voicemail.svg#rec"/>'
            + '</svg>'
            + '<svg height="100" width="100" y="-10" x="70%" class="miss">'
              + '<circle cx="50" cy="50" r="35" stroke="#fff" stroke-width="3" fill="white" />'
              + '<circle cx="50" cy="50" r="30" stroke="#CC3300" stroke-width="5" fill="white" />'
              + '<text x="50%" y="63" text-anchor="middle"></text>'
            + '</svg>' 
            + '<text y="10%" x="50%" class="dest" text-anchor="middle"></text>'
            + '<text y="40%" x="50%" class="duration" text-anchor="middle"></text>'
            + '<text y="60%" x="50%" class="ext" text-anchor="middle">' + oExtension.extension + '</text>'
            + '<text y="80%" x="50%" class="name" text-anchor="middle">' + (oExtension.effective_caller_id_name === null ? "" : oExtension.effective_caller_id_name)+ '</text>'
            + "</svg></li>");
        }
        /*** SET GROUP ***/
        if($('li#' + sGroup + " ul li#ext" + e).length == 0) $('li#' + sGroup + " ul").append($('li#ext' + e));
        
        /*** SET DND ***/
        if(oExtension.do_not_disturb == "true") {
          $('li#ext' + e).addClass('dnd');
        } else {
          $('li#ext' + e).removeClass('dnd');
        }
        /*** SET MISSED ***/
        $('li#ext' + e + " svg.miss text").text(oExtension.missed_calls);
        if(oExtension.missed_calls > 0) {
          $('li#ext' + e).addClass('missed');
        } else {
          $('li#ext' + e).removeClass('missed');
        }
        
        /*** CALLING ***/ 
        if(['ACTIVE','EARLY'].includes(oExtension.callstate)) {
          $('li#ext' + e).addClass('calling');          
          if(oExtension.direction == "inbound") {
            $('li#ext' + e + ' text.dest').text(oExtension.dest);
            $('li#ext' + e + ' text.duration').text(oExtension.call_length);
          }
        } else {
          $('li#ext' + e).removeClass('calling');
          $('li#ext' + e + ' text.dest').text("");
          $('li#ext' + e + ' text.duration').text("");
        }
        /*** RINGING ***/ 
        if(oExtension.callstate == 'RINGING') {
          $('li#ext' + e).addClass('ringing');  
          if(oExtension.direction == "inbound") $('li#ext' + e + ' text.dest').text(oExtension.dest);
          $('li#ext' + e).addClass(oExtension.direction);          
        } else {
          $('li#ext' + e).removeClass('ringing');
          $('li#ext' + e).removeClass('inbound');
          $('li#ext' + e).removeClass('outbound');
        }
        
        /*** VOICEMAIL ENABLED***/
        if(oExtension.voicemail_enabled == "true") {
          $('li#ext' + e).addClass('voice');  
        } else {
          $('li#ext' + e).removeClass('voice');  
        }

        /*** CONNECTED ***/
        if(oExtension.connected === false) {
          $('li#ext' + e).addClass('discon');  
        } else {
          $('li#ext' + e).removeClass('discon');  
        }
      }
      /*** WAITING LIST ***/
      var cActive = oResult.active;
      $("div#waiting ul").html("");
      for(var a in cActive) {
        var oActive = cActive[a];
        switch(oActive.callstate) {
          case "EARLY":
          case "MENU":
          case "RINGING":
            var tNow = new Date();
            var iTime = parseInt(tNow / 1000 - oActive.created_epoch);
            var iH = iTime < 45 ? 90 - (iTime * 2) : 0;
            var iL = iTime < 60 ? 30 + (iTime / 2) : 60;
            $("div#waiting ul").append('<li style="color: hsla(' + iH + ', 100%, ' + iL + '%, 1);">' + oActive.cid_num + '<span class="hanging">' + shoTime(iTime) + '</span></li>');
          break;
        }
      }
     
      /*** STATS ***/
      var cStats = oResult.stats;
      for(var s in cStats) {
        var sGroup = cStats[s].call_group.toLowerCase();
        var sDuration = "";
        if(cStats[s].duration !== null) {
          var iSecs = parseInt(cStats[s].duration);
          var iHours = Math.floor( iSecs / 3600);
          iSecs = iSecs - (iHours * 3600);
          var iMinutes = Math.floor(iSecs / 60);
          iSecs = iSecs - (iMinutes * 60);
          sDuration = iHours + ":" + ("0" + iMinutes).substr(-2) + ":" + ("0" + iSecs).substr(-2);
        } 

        var sWait = "";
        if(cStats[s].waitsec !== null) {
          iSecs = parseInt(cStats[s].waitsec);
          iHours = Math.floor( iSecs / 3600);
          iSecs = iSecs - (iHours * 3600);
          iMinutes = Math.floor(iSecs / 60);
          iSecs = iSecs - (iMinutes * 60);
          sWait = iHours + ":" + ("0" + iMinutes).substr(-2) + ":" + ("0" + iSecs).substr(-2);
        }

        if($('li#' + sGroup + " ul.stats").length == 0) {
          $('li#' + sGroup).append('<ul class="stats">'
            /*** TOTAL CALLS ***/
            + '<li><svg width="100%" height="100%" viewbox="0 0 100 100" class="total">'
              + '<circle cx="50" cy="50" r="40" stroke="#236db8" stroke-width="5" fill="white" />'
              + '<text x="50" y="60" font-size="30" text-anchor="middle">' + cStats[s].total + '</text>'
            + '</svg></svg></li>' 
            /*** TOTAL CALL DURATION SECONDS ***/
            + '<li><svg width="100%" height="100%" viewbox="0 0 100 100" class="duration">'
              + '<circle cx="50" cy="50" r="40" stroke="#458B74" stroke-width="5" fill="white" />'
              + '<text x="50" y="56" font-size="18" text-anchor="middle">' + sDuration + '</text>'
            + '</svg></li>' 
            /*** TOTAL WAIT SECONDS ***/
            + '<li><svg width="100%" height="100%" viewbox="0 0 100 100" class="waitsec">'
              + '<circle cx="50" cy="50" r="40" stroke="#FF4500" stroke-width="5" fill="white" />'
              + '<text x="50" y="56" font-size="18" text-anchor="middle">' + sWait + '</text>'
            + '</svg></li>' 
            /*** TOTAL MISSED ***/
            + '<li><svg width="100%" height="100%" viewbox="0 0 100 100" class="missed">'
              + '<circle cx="50" cy="50" r="40" stroke="#FF0000" stroke-width="5" fill="white" />'
              + '<text x="50" y="60" font-size="30" text-anchor="middle">' + cStats[s].missed + '</text>'
            + '</svg></li>' 
          + '</ul>');
        } else {
          $('li#' + sGroup + " ul.stats li svg.total text").text(cStats[s].total);
          $('li#' + sGroup + " ul.stats li svg.duration text").text(sDuration);
          $('li#' + sGroup + " ul.stats li svg.waitsec text").text(sWait);
          $('li#' + sGroup + " ul.stats li svg.missed text").text(cStats[s].missed);
        }
      }

      /*** CLEAR EMPTY CALL GROUPS ***/
      $("ul#panel > li").each(function(){
        if($(this).find("ul > li").length == 0) {
          $(this).remove();
        }
      });
    }).always(function() {
        window.setTimeout(getActivity, iTimeout);
    });
  }
  if(window.initPanel) {
    initPanel(getActivity);
  } else {
    getActivity();
  }
});
