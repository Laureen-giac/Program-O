      var smileHorizCenter = 0;
      var blinkSpeed = 8;
      var bc = 100, ubc = 0; // blink counter and unblink counter
      var bt, ubt; // blink and unblink timer handles
      var lowerPathTemplate = 'M-50, [y] A 50 [ry] 0 0 [dir] 0 0 A 50 [ry] 0 0 [dir] 50 [y]';// c0, 0-39.5, 24-85, 24.5 S170, [y], 170, [y]z
      var eyeScaleTemplate = 'scale(1 [y])';
      var rightAngleTemplate = 'translate(120 70) rotate([a] 60 190)';
      var leftAngleTemplate = 'translate(280 70) rotate([a] 60 190)';
      var rbt, rtw, ltw, smt;
      var lastIndex = 0;
      $(function(){
        rbt = setTimeout(randomBlink, Math.random() * 10000);
        rtw = setTimeout(randomTwitchRight, Math.random() * 10000);
        ltw = setTimeout(randomTwitchLeft, Math.random() * 3000);
        //smt = setTimeout(randomSmile, Math.random() * 3000);
      });

      function randomSmile() {
        var rInterval = Math.floor((Math.random() - 0.5) * 5000) + 8000;
        var smileSize = (Math.random() - 0.5) * 5;
        smile(smileSize);
        //smt = setTimeout(randomSmile, rInterval);
      }

      function getNewD (index) {
        var ry = Math.abs(index);
        var dir = (index < 0) ? 1 : 0;
        var newY = smileHorizCenter - index;
        var newD = lowerPathTemplate.replace(/\[y\]/g, newY).replace(/\[ry\]/g, -index).replace(/\[dir\]/g, dir);
        return newD;
      }

      function smile(idx) {
        var index = idx * 11;
        var dur = 1000;
        $('#lips').velocity(
          {d: index},
          {
            duration: dur,
            step: function(now) {
              newD = getNewD(now);
              $('#lips').attr('d', newD);
            },
            start: function() {
              var newD = getNewD(lastIndex);
              $('#lips').attr('d', newD);
            }
          }
        )
/*
        .delay(600)
        .animate(
          {d: 1},
          {
            duration: 800,
            step: function(now) {
              var ry = Math.abs(now);
              var dir = (now < 0) ? 1 : 0;
              var nextY = smileHorizCenter - now;
              var nextD = lowerPathTemplate.replace(/\[y\]/g, nextY).replace(/\[ry\]/g, -now).replace(/\[dir\]/g, dir);
              $('#lips').attr('d', nextD);
            }
          }
        )
*/
        ;
        lastIndex = -idx;
      }

      function twitchRightEar(angle) {
        var dur = Math.floor(Math.random() * 100) + 50;
        $('#Right_Ear').animate(
          {transform: angle},
          {duration: dur,
            step: function(now){
              var transform = rightAngleTemplate.replace('[a]', Math.floor(now));
              $('#Right_Ear').attr('transform', transform);
            }
          }
        ).animate(
          {transform: 0},
          {
            duration: dur,
            step: function(now){
              var transform = rightAngleTemplate.replace('[a]', Math.floor(now));
              $('#Right_Ear').attr('transform', transform);
            }
          }
        )
      }

      function twitchLeftEar(angle) {
        var dur = Math.floor(Math.random() * 100) + 50;
        $('#Left_Ear').animate({transform: angle},
          {duration: dur,
            step: function(now){
              var transform = leftAngleTemplate.replace('[a]', Math.floor(now));
              $('#Left_Ear').attr('transform', transform);
            }
          }
        ).animate(
          {transform: 0},
          {
            duration: dur,
            step: function(now){
              var transform = leftAngleTemplate.replace('[a]', Math.floor(now));
              $('#Left_Ear').attr('transform', transform);
            }
          }
        )
      }

      function winkLeft() {
        wink($('#Left_Eye'), lwc);
        lwc -= blinkSpeed;
        if (lwc > blinkSpeed) {
          lwt = setTimeout(winkLeft, 1);
        }
        else {
          clearTimeout(lwt);
          uwlc = 0;
          unwinkLeft();
        }
      }

      function unwinkLeft() {
        wink($('#Left_Eye'), uwlc);
        uwlc += blinkSpeed;
        if (uwlc < 100) {
          uwlt = setTimeout(unwinkLeft, 1);
        }
        else {
          clearTimeout(uwlt);
          lwc = 100;
        }
      }

      function winkRight() {
        wink($('#Right_Eye'), rwc);
        rwc -= blinkSpeed;
        if (rwc > blinkSpeed) {
          rwt = setTimeout(winkRight, 1);
        }
        else {
          clearTimeout(rwt);
          uwrc = 0;
          unwinkRight();
        }
      }

      function unwinkRight() {
        wink($('#Right_Eye'), uwrc);
        uwrc += blinkSpeed;
        if (uwrc < 100) {
          uwrt = setTimeout(unwinkRight, 1);
        }
        else {
          clearTimeout(uwrt);
          rwc = 100;
        }
      }

      function doBlink() {
        wink($('#Left_Eye, #Right_Eye'), bc);
        bc -= blinkSpeed;
        if (bc > blinkSpeed) {
          bt = setTimeout(doBlink, 1);
        }
        else {
          clearTimeout(bt);
          ubc = 0;
          doUnBlink();
        }
      }

      function doUnBlink() {
        wink($('#Left_Eye, #Right_Eye'), ubc);
        ubc += blinkSpeed;
        if (ubc < 100) {
          ubt = setTimeout(doUnBlink, 1);
        }
        else {
          clearTimeout(ubt);
          bc = 100;
        }
      }

      function randomBlink() {
        var rInterval = Math.floor((Math.random() - 0.5) * 5000) + 8000;
        doBlink();
        rbt = setTimeout(randomBlink, rInterval);
      }

      function randomTwitchRight() {
        var rInterval = Math.floor((Math.random() - 0.5) * 5000) + 8000;
        var angle = Math.floor((Math.random() - 0.5) * 45);
        twitchRightEar(angle);
        rtw = setTimeout(randomTwitchRight, rInterval);
      }

      function randomTwitchLeft() {
        var rInterval = Math.floor((Math.random() - 0.5) * 5000) + 4000; // 8000
        var angle = Math.floor((Math.random() - 0.5) * 45);
        twitchLeftEar(angle);
        ltw = setTimeout(randomTwitchLeft, rInterval);
      }

      function moveSmile(index) {
        var ry = Math.abs(index);
        var dir = (index < 0) ? 1 : 0;
        var newY = smileHorizCenter - index;
        var lowerNewD = lowerPathTemplate.replace(/\[y\]/g, newY).replace(/\[ry\]/g, -index).replace(/\[dir\]/g, dir);
        $('#lips').attr('d', lowerNewD);
      }

      function wink(eye, pct) {
        //console.log('val =', pct);
        var scale = pct / 100;
        var transform = eyeScaleTemplate.replace('[y]', scale);
        eye.find('ellipse').attr('transform', transform);
      }
