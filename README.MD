# Program O AIML Interpreter/Chatbot Engine

readme info:
- Version:   3.0.0
- Authors: Dave Morton
- Date: May 28th, 2016


## Introduction


Program O is an AIML interpretor written in PHP, and uses a MySQL database to store
chatbot information, including the AIML files used to formulate the chatbot's responses.


## Configuration

Program O supports the creation of multiple chatbots. The individual bots are configured
from the admin area. Bot specific variables are stored in the database. Configuration
of Program O itself is done in /config/config.php

## Using the bot

After installation you can chat with your Program O chatbot using several methods:
-Through a static HTML page (example in /gui/plain/index.php)
-Through an AJAX enabled web page (example in /gui/jquery/index.php)
-Through the Program O API (example in /gui/xml/index.php)

For advanced botmasters and programmers, it is possible to use the Program O
API to access a chatbot from stand-alone applications through HTTP requests.
for information on how to create and/or use such a stand-alone application, please
visit the Program O Support Forums at http://www.program-o.com

## Installation

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vitae bibendum enim,
at pharetra nibh. Fusce rutrum placerat rhoncus. Morbi dui massa, gravida sit amet orci
eu, mollis varius elit. Donec dapibus velit vel dolor ullamcorper, vitae pulvinar odio
facilisis. Etiam non convallis lorem. Aenean dapibus blandit quam. Donec id mi massa.
Donec feugiat, nulla a ullamcorper bibendum, dui est tincidunt augue, eu eleifend metus
urna eget urna. Nullam scelerisque placerat euismod. In cursus scelerisque ligula, non
consectetur lectus porta nec. Nulla justo dui, aliquet non vestibulum eu, placerat ac
purus. Nullam nec imperdiet dui, sed euismod dolor. Etiam ut ullamcorper ex. Praesent
tempor justo id ullamcorper aliquet.

