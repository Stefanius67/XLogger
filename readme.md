# XLogger - PSR3 compliant Logging to Browser Console, Text- or XML-File

 ![Latest Stable Version](https://img.shields.io/badge/release-v1.0.0-brightgreen.svg)
 ![License](https://img.shields.io/packagist/l/gomoob/php-pushwoosh.svg)
 [![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
 [![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://phpstan.org/)
 [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Stefanius67/XLogger/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Stefanius67/XLogger/?branch=master)
----------
This package provides **PSR-3** compliant Loggers for Output to
- Browser Console via FirePHP
- Browser Console via Chrome Logger
- Plain Text (log, txt, csv)
- XML File (can be transformed to HTML with included XSL)

For debugging in particular, it is often very time-saving if the logging output goes directly to the browser console.  
Since the loggers comply with the PSR-3 specification, they can be used for existing code and there is the possibility to configure the most suitable logger for different scenarios and / or environments (development / test / delpoyment) at runtime.

**For more information about the PSR-3 specification visit [www.php-fig.org](https://www.php-fig.org/psr/psr-3)**

For logging to the browser console one of the following Add-Ons have to be installed (both are available for Firefox and Chrome):
- FirePHP
- Chrome Logger
 
## Installation   
You can download the  Latest Version from PHPClasses.org. The namespaces, class- and filenames meet the **PSR-4** autoloader recommendations.

## Usage
The use of the available logger classes and the integration of the PSR-3 interfaces / treats can be seen in the examples ***XLogTest.php*** and ***TestClass.php***.
For XML-HTML Transformation of the XML-Format use Example XSL-Template ***XMLLogger.XSL***

##### FirePHP Add-On
Can be found on [www.firephp.org](http://www.firephp.org)

##### Chrome Logger Add-On
For Google Chrome can be found on
[chrome.google.com](https://chrome.google.com/webstore/detail/chrome-logger/noaneddfkdjfnfdakjjmocngnfkfehhd)  

For Firefox can be found on [addons.mozilla.org](https://addons.mozilla.org/de/firefox/addon/chromelogger/)
