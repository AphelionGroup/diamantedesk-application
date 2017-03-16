define(['d3', 'd3-tip', 'diamante/palette', 'underscore'], function (d3, d3tip, palette, _) {

  "use strict";

  var RATIO = 16 / 9,
      resizeBars = {};

  window.addEventListener('resize', _.debounce(function(){
    for(var key in resizeBars) {
      if(resizeBars.hasOwnProperty(key)){
        resizeBars[key]();
      }
    }
  }, 100), false);

  return function (options) {

    var data = options.data,
        elem = options._sourceElement.get(0),
        parent = options._sourceElement.parent(),
        plot = d3.select(elem),
        isEmpty =  _.every(data, function (elem) { return elem.y == 0;}) ,
        getRandomInt = function(min, max) {
          return Math.floor(Math.random() * (max - min + 1) + min).toString();
        };

    if ( isEmpty ) {
      $(elem).css({
        opacity: '.2',
        pointerEvents: 'none',
        backgroundColor: '#f2f2f7'
      });
          
      parent.prepend('<div class="empty-widget">No Data. There are no tickets available for analytics yet.</div>');
     
      data  = _.map(data, function(elem){
          elem.y = getRandomInt(0,10);
          return elem
      })
    }

    if (!parent.is('[data-wid]')) {
        parent = parent.parent();
    }

    var w = elem.clientWidth,
        h = w / RATIO,
        margin = {top: 20, right: 20, bottom: 30, left: 40},
        width = w - margin.left - margin.right,
        height = h - margin.top - margin.bottom;

    if(parent[0].id == 'container' && h > parent[0].clientHeight - 100){
      h = parent[0].clientHeight - 100;
      height = h - margin.top - margin.bottom;
    }

    var svg = plot.append("svg")
        .attr("width", w)
        .attr("height", h)
        .attr("viewBox", "0 0 " + w + " " + h);

    var root = svg.append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    var x = d3.scale.ordinal()
        .rangeRoundBands([0, width], .1);

    var y = d3.scale.linear()
        .range([height, 0]);

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom");

    var ticksCount = d3.max(data, function(d) { return parseInt(d.y, 10) }) + 1;
    if(ticksCount > 20) {
      ticksCount = 20;
    }

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left")
        .ticks(ticksCount);

    var tip = d3tip()
        .attr('class', 'diam-d3-tip tooltip top')
        .html(function(d) {
          return '<div class="tooltip-arrow"></div><div class="tooltip-inner">Tickets: <span>' + d.y + '</span></div>';
        });

    var paletteLength = Object.keys(palette).length;
    var dataLength = (data.length <= paletteLength) ? data.length : paletteLength;

    var color = d3.scale.ordinal().domain(data).range(palette[dataLength]);

    x.domain( data.map(function(d) { return d.x; }));
    y.domain([0, d3.max(data, function(d) { return parseInt(d.y, 10) }) + 1]);

    if(x.rangeBand() > width / 4) {
      x.rangeRoundBands([0, width * .5], .1);
    }

    root.call(tip);

    root.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    root.append("g")
        .attr("class", "y axis")
        .call(yAxis);

    root.selectAll(".bar")
        .data(data)
        .enter().append("rect")
        .attr("class", "bar")
        .attr("x", function(d) { return x(d.x); })
        .attr("width", x.rangeBand())
        .attr("y", function(d) { return y(d.y); })
        .attr("height", function(d) { return height - y(d.y); })
        .style("fill", function(d) { return color(d.x); })
        .on('mouseover', tip.show)
        .on('mouseout', tip.hide);

        if ( isEmpty ) {
          $('rect.bar', elem).css('fill', 'rgba(100,100,100,.7)');
        }

    resizeBars[parent[0].id] = function () {
      var w = elem.clientWidth,
          h = w / RATIO,
          width = w - margin.left - margin.right,
          height = h - margin.top - margin.bottom;

      if(w <= 0) {
        delete resizeBars[parent[0].id];
        return;
      }

      if(parent[0].id == 'container' && h > parent[0].clientHeight - 100){
        h = parent[0].clientHeight - 100;
        height = h - margin.top - margin.bottom;
      }

      x.rangeRoundBands([0, width], .1);
      y.range([height, 0]);

      if(x.rangeBand() > width / 4) {
        x.rangeRoundBands([0, width * .5], .1);
      }

      xAxis.scale(x);
      yAxis.scale(y);

      svg.attr("width", w)
          .attr("height", h)
          .attr("viewBox", "0 0 " + w + " " + h);

      svg.select('.x.axis')
          .attr("transform", "translate(0," + height + ")")
          .call(xAxis);

      svg.select('.y.axis')
          .call(yAxis);

      svg.selectAll(".bar")
          .attr("x", function(d) { return x(d.x); })
          .attr("width", x.rangeBand())
          .attr("y", function(d) { return y(d.y); })
          .attr("height", function(d) { return height - y(d.y); });

    };

  };

});
