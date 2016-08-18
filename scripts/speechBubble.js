// speechBubble.js
      var defaultWidth = 175, currentWidth = defaultWidth;
      var defaultHeight = 4, currentHeight = defaultHeight;
      var dTemplate = 'M 0 0 l -55 -25 v -[h] a 10 10 0 0 0 -10 -10 h -[w] a 10 10 0 0 0 -10 10 v [h] a 10 10 0 0 0 10 10 h [w] z';
      var gXformTemplate = 'translate([x] [y])';
      var defaultGX = -235, currentGX = defaultGX;
      var defaultGY = -22.5, currentGY = defaultGY;
      var sbMaxWidth = 350;
      $(function(){
        var screenW = $(window).width();
        $('#sld_width').slider({
          orientation: 'vertical',
          min: 150,
          max: 350,
          step: 1,
          value: defaultWidth,
          slide: function (evt, ui) {
            currentWidth = ui.value;
            setSize();
          },
        });
        $('#sld_height').slider({
          orientation: 'vertical',
          min: 4,
          max: 200,
          step: 0.25,
          value: defaultHeight,
          slide: function (evt, ui) {
            currentHeight = ui.value;
            setSize();
          },
        });
        $('#svg').on('click', '#speechBubble', function(){
          console.log('test');
          $(this).toggleClass('animate');
        });
      });

      function sbFlash() {
        $('#speechBubble').addClass('animate');
        var aniStop = setTimeout(function(){$('#speechBubble').removeClass('animate')}, 1950);
      }

      function setSize() {
        var xDif = defaultWidth - currentWidth;
        var yDif = defaultHeight - currentHeight;
        currentGX = defaultGX + xDif;
        currentGY = defaultGY + yDif;
        var newG = gXformTemplate.replace(/\[x\]/g, currentGX).replace(/\[y\]/g, currentGY);
        $('#speech_container').attr('transform', newG);
        var newD = dTemplate.replace(/\[h\]/g, currentHeight).replace(/\[w\]/g, currentWidth);
        $('#speechBubble').attr('d', newD);
      }
