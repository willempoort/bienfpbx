const iTimeout = 3000;

function initPanel(fCallback) {
  var $Div = $('<div id="waiting"><h2>Wachtrij</h2><ul></ul></div>');
  $(document.body).append($Div); 
  var $UL = $('<ul id="panel"></ul>');
  $(document.body).append($UL); 
  $('ul#panel').append('<li id="aftersales"></li>');
  $('ul#panel').append('<li id="receptie"></li>');
  $('ul#panel').append('<li id="sales"></li>');
  $('ul#panel').append('<li id="algemeen"></li>');
  fCallback();
}
