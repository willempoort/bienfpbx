var doActivity = true;
$(document).ready(function() {

	/*** STATS ***/
	var height = 500 ;
	var width = 500;
	var animateDuration = 700;
	var animateDelay = 30;
				
	var yScale = d3.scale.linear()
		.domain([0, 20])
		.range([0, height]);

  function createChart(oStat) {

		var sGroup = oStat.call_group ? oStat.call_group.toLowerCase() : "Algemeen";
		
    var myData = [
			{label:"09:00", value:oStat.a09 ? oStat.a09 : 0},
			{label:"10:00", value:oStat.a10 ? oStat.a10 : 0},
			{label:"11:00", value:oStat.a11 ? oStat.a11 : 0},
			{label:"12:00", value:oStat.a12 ? oStat.a12 : 0},
			{label:"13:00", value:oStat.a13 ? oStat.a13 : 0},
			{label:"14:00", value:oStat.a14 ? oStat.a14 : 0},
			{label:"15:00", value:oStat.a15 ? oStat.a15 : 0},
			{label:"16:00", value:oStat.a16 ? oStat.a16 : 0},
			{label:"17:00", value:oStat.a17 ? oStat.a17 : 0},
			{label:"18:00", value:oStat.a18 ? oStat.a18 : 0}
		];
	
		var margin = {
			top: 30,
			right: 30,
			bottom: 40,
			left: 50
		}

		var max = d3.max(myData, function(d) {
			 return d.value; 
		});

		var data = function(){
			var data = [];
			for(var d in myData){
				data.push(myData[d].value)
			}
			return data;
		};
		
		var tooltip = d3.select('body').append('div')
			.style('position', 'absolute')
			.style('background', '#f4f4f4')
			.style('padding', '5 15px')
			.style('border', '1px #333 solid')
			.style('border-radius', '5px')
			.style('opacity', '0')

        var colors = d3.scale.linear()
            .domain([0, myData.length])
            .range(["#e51c20", "#9E0113"])


		var xScale = d3.scale.ordinal()
			.domain(d3.range(0, myData.length))
			.rangeBands([6, width]);

		var myChart = d3.select('li#' + sGroup + ' svg.chart').append('svg')
				.attr('width', '100%')
        .attr('height', '80%')
        .attr('viewbox','0 0 500 500')
				.append('g')
				.attr('transform', 'translate('+margin.left+','+margin.top+')')
				.style('background', '#f4f4f4')
				.selectAll('rect')
					.data(data)
					.enter().append('rect')
                        .style('fill', function(d, i){
                            return colors(i);
                        })
						.attr('width', xScale.rangeBand()-10)
						.attr('height', 0)
						.attr('x', function(d, i){
							return xScale(i);
						})
						.attr('y', height)

				.on('mouseover', function(d){
					tooltip.transition()
						.style('opacity', 1)
					tooltip.html(d)
						.style('left', (d3.event.pageX)+'px')
						.style('top', (d3.event.pageY+'px'))
					d3.select(this).style('opacity', 0.5)
				})
				.on('mouseout', function(d){
					tooltip.transition()
						.style('opacity', 0)
					d3.select(this).style('opacity', 1)
				})

		myChart.transition()
			.attr('height', function(d){
				return yScale(d);
			})
			.attr('y', function(d){
				return height - yScale(d)
			})
			.duration(animateDuration)
			.delay(function(d, i){
				return i * animateDelay
			})
			.ease('elastic')
		var vScale = d3.scale.linear()
			.domain([0, 20])
			.range([height, 0])
		var hScale = d3.scale.ordinal()
			.domain(d3.range(0, myData.length))
			.rangeBands([0, width])

		// V Axis
		var vAxis = d3.svg.axis()
			.scale(vScale)
			.orient('left')
			.tickPadding(5)

		// V Guide
		var vGuide = d3.select('li#' + sGroup + ' svg.chart svg')
			.append('g')
				vAxis(vGuide)
				vGuide.attr('transform','translate('+margin.left+','+margin.top+')')
				vGuide.selectAll('path')
					.style('fill', 'none')
					.style('stroke', '#000')
				vGuide.selectAll('line')
					.style('stroke', '#000')
		
		// H Axis
		var hAxis = d3.svg.axis()
			.scale(hScale)
			.orient('bottom')
			.tickPadding(9)
			.tickFormat(function(d){return myData[d].label;});
			;
	
		// H Guide
		var hGuide = d3.select('li#' + sGroup + ' svg.chart svg')
			.append('g')
				hAxis(hGuide)
				hGuide.attr('transform','translate('+margin.left+','+(height + margin.top)+')')
				hGuide.selectAll('path')
					.style('fill', 'none')
					.style('stroke', '#000')
				hGuide.selectAll('line')
					.style('stroke', '#000')
                
  }
  function getActivity() {
    if(doActivity === false) return false;
    /*** CREATE CONTAINER ***/
    if($('ul#panel').length == 0) {
      $(document.body).append('<ul id="panel"></ul>'); 
    } 
    $.get("../get_call_activity.php", function(oResult) {
      $("h1.error").remove();
      

      var cStats = oResult.stats;
      for(var s in cStats) {
        var sGroup = cStats[s].call_group ? cStats[s].call_group.toLowerCase() : "Algemeen";
        if($('li#' + sGroup).length == 0) {
          $('ul#panel').append('<li id="' + sGroup + '"><h2>' + sGroup + "</h2></li>");
          $('li#' + sGroup).append('<svg class="chart" width="95%" height="85%" viewbox="0 0 500 700"></svg>');
          createChart(cStats[s]);
          
        }

        /*** MODIFY NUMBER CALLS MADE BY GROUP ***/ 
				var oStat = cStats[s];
				// if(cStats[d] + 1){
				$('li#' + sGroup + ' rect:eq(0)').attr('height', yScale(oStat.a09)).attr('y', height - yScale(oStat.a09)); //- 25 van y
				$('li#' + sGroup + ' rect:eq(1)').attr('height', yScale(oStat.a10)).attr('y', height - yScale(oStat.a10));
				$('li#' + sGroup + ' rect:eq(2)').attr('height', yScale(oStat.a11)).attr('y', height - yScale(oStat.a11));
				$('li#' + sGroup + ' rect:eq(3)').attr('height', yScale(oStat.a12)).attr('y', height - yScale(oStat.a12));
				$('li#' + sGroup + ' rect:eq(4)').attr('height', yScale(oStat.a13)).attr('y', height - yScale(oStat.a13));
				$('li#' + sGroup + ' rect:eq(5)').attr('height', yScale(oStat.a14)).attr('y', height - yScale(oStat.a14));
				$('li#' + sGroup + ' rect:eq(6)').attr('height', yScale(oStat.a15)).attr('y', height - yScale(oStat.a15));
				$('li#' + sGroup + ' rect:eq(7)').attr('height', yScale(oStat.a16)).attr('y', height - yScale(oStat.a16));
				$('li#' + sGroup + ' rect:eq(8)').attr('height', yScale(oStat.a17)).attr('y', height - yScale(oStat.a17));
					//.attr('height', '600')
				
        /*** CALL DUARTION ***/
        var iSecs = parseInt(cStats[s].duration);
        var iHours = Math.floor( iSecs / 3600);
        iSecs = iSecs - (iHours * 60);
        var iMinutes = Math.floor(iSecs / 60);
        iSecs = iSecs - (iMinutes * 60);
        var sDuration = iHours + ":" + ("0" + iMinutes).substr(-2) + ":" + ("0" + iSecs).substr(-2);

        iSecs = parseInt(cStats[s].waitsec);
        iHours = Math.floor( iSecs / 3600);
        iSecs = iSecs - (iHours * 60);
        iMinutes = Math.floor(iSecs / 60);
        iSecs = iSecs - (iMinutes * 60);
        var sWait = iHours + ":" + ("0" + iMinutes).substr(-2) + ":" + ("0" + iSecs).substr(-2);

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
        window.setTimeout(getActivity, 3000);
    });
  }

  /*** START ACTIVITY ***/
  getActivity();
});
