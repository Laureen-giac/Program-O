# Program O Version 3: A Completely New Creature

readme info:
- Version:   3.0.0
- Authors: Dave Morton, Elizabeth Perreau and Brent Edds
- Date: Aug. 19th, 2016


## Introduction


Program O Version 3 is an AIML interpretor written in PHP, but unlike previous versions,
will (probably) not use a MySQL database to store the chatbot's information or AIML,
though the option to use a database to store user/admin information will be available.

## Features

Program O version 3 will support the following features:
    - AIML 2.0
    - Multiple human languages (e.g. Englisih, Spanish, Japanese, Chinese, etc.)
    - <sraix> and <oob> tags (with configuration)
    - "Custom" AIML tags (e.g. <google>, <wiki>), now fully configurable in the admin
    - Multiple PHP caching extensions (depending on what is available on the server)__†__

__†__Note: If there are no PHP caching extensions available, Program O will implement it's
own caching class, which while slower than other caching functions, will still improve
performance over not using one at all

## Requirements


For now, Program O version 3 requires only PHP version 5.3 or higher, but that may change
as development continues. We'll try to keep the requirements as low as possible (no bleeding
edge features), but as more functionality is brouguht online it's possible that we'll have
to raise the requirements.

## Installation

As with prior versions of Program O, installaton will be as painless and simple as humanly
possible. A self-installing script will guide you through a step-by-step process of
installation and configuration of your chatbot(s), and a set of admin pages will allow for
configuration of the chatbot, it's map and set files, and it's predicates.

## Using the bot


Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vitae bibendum enim,
at pharetra nibh. Fusce rutrum placerat rhoncus. Morbi dui massa, gravida sit amet orci
eu, mollis varius elit. Donec dapibus velit vel dolor ullamcorper, vitae pulvinar odio
facilisis. Etiam non convallis lorem. Aenean dapibus blandit quam. Donec id mi massa.
Donec feugiat, nulla a ullamcorper bibendum, dui est tincidunt augue, eu eleifend metus
urna eget urna. Nullam scelerisque placerat euismod. In cursus scelerisque ligula, non
consectetur lectus porta nec. Nulla justo dui, aliquet non vestibulum eu, placerat ac
purus. Nullam nec imperdiet dui, sed euismod dolor. Etiam ut ullamcorper ex. Praesent
tempor justo id ullamcorper aliquet.

