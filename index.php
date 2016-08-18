<?php
  /***************************************
   * http://www.program-o.com
   * Program-O
   * Version: 3.0.0
   *
   * FILE: API.php
   * AUTHOR: Dave Morton and Elizabeth Perreau
   * DATE: 5/28/2016 - 8:48 AM
   * DETAILS: Example GUI page for the Program O chatbot
   ***************************************/
?>
<!DOCTYPE HTML>
<html lang="en">
  <head>
      <meta charset="utf-8">
    <title>Program O Example GUI</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="css/style.css" type="text/css">
  </head>
  <body><br>
    <!-- start here -->
    <div id="logo_div"></div>
    <div id="speech_bubble"></div>
    <div id="sld_width"></div>
    <div id="sld_height"></div>
    <div id="svg"></div>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script><!--  -->
    <script type="text/javascript" src="scripts/avatar.js"></script>
    <script type="text/javascript" src="scripts/speechBubble.js"></script>
    <script type="text/javascript">
      var API_URL = 'API.php';
      $(function(){
        // load avatar and speech bubbles
        $('#logo_div').load('assets/avatar.svg');
        $('#speech_bubble').load('assets/sb.svg');
      });
    </script>
  </body>
</html>