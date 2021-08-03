<?php
declare(strict_types=1);

require_once 'autoloader.php';
require_once 'TestClass.php';

use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use SKien\XLogger\ChromePHPLogger;
use SKien\XLogger\FileLogger;
use SKien\XLogger\FirePHPLogger;
use SKien\XLogger\XLogger;
use SKien\XLogger\XMLLogger;

$strMessage = '';
$strLink = '';
$logger = new NullLogger();
$strLogger = isset($_GET['logger']) ? strtolower($_GET['logger']) : 'log';
$strLevel = isset($_GET['level']) ? strtolower($_GET['level']) : 'dev';
$level = LogLevel::DEBUG;
switch ($strLevel) {
    case 'deploy':
        $level = LogLevel::WARNING;
        $strMessage = '<br/>Log-Level for Deployment Environment';
        break;
    case 'test':
        $level = LogLevel::INFO;
        $strMessage = '<br/>Log-Level for Test Environment';
        break;
    case 'dev':
        $level = LogLevel::DEBUG;
        $strMessage = '<br/>Log-Level for Development Environment';
        break;
}
switch ($strLogger) {
    case 'log':
    case 'csv':
    case 'txt':
        $logger = new FileLogger($level);
        $logger->setUser('S./Kien');
        $logger->setFullpath('test_{date}_{name}.' . $strLogger);
        $logger->setOptions(XLogger::LOG_BT | XLogger::LOG_USER);
        $strMessage = 'Logger Output written to ' . $logger->getFilename() . $strMessage;
        break;
    case 'xml':
        $logger = new XMLLogger($level);
        $logger->setFullpath('test_{month}.xml');
        $logger->setXSLFile('XMLLogger.xsl');
        $logger->setOptions(XLogger::LOG_BT | XLogger::LOG_USER | XLogger::LOG_UA);
        $logger->setUser('SKien');
        $strMessage = 'Logger Output written to ' . $logger->getFilename() . $strMessage;
        $strLink = '<a target="_blank" href="' . $logger->getFilename() . '">View Log</a>';
        break;
    case 'firefox':
        $logger = new FirePHPLogger($level);
        $strMessage = 'Logger Output sent to FirePHP Console' . $strMessage;
        break;
    case 'chrome':
        $logger = new ChromePHPLogger($level);
        $strMessage = 'Logger Output sent to Chrome Console' . $strMessage;
        break;
}

$logger->error('bad conditions :-(', ['more' => 'just more Information']);
$logger->info('some informations');
$logger->alert('the bell is ringing...' . PHP_EOL . '... its 5 to 12!');
$logger->warning('something gone wrong ;-)');

$oTest = new TestClass();

// nothing will be logged since setLogger() was not called so far... but dont causes any error!');
$oTest->doSomething();

$oTest->setLogger($logger);
$oTest->doSomething();
$oTest->causeException();
?>
<!DOCTYPE html>
<html>
<body>
	<h1>Logger - Example</h1>
	<p><?php echo $strMessage;?></p>
	<p><?php echo $strLink;?></p>
	<form action="XLogTest.php" method="get">
	<label for="logger">Output to:</label>
	<select name="logger" id ="logger">
		<option <?php echo $strLogger == 'log' ? 'selected' : ''; ?> value="log">LOG File</option>
		<option <?php echo $strLogger == 'csv' ? 'selected' : ''; ?> value="csv">CSV File</option>
		<option <?php echo $strLogger == 'txt' ? 'selected' : ''; ?> value="txt">TXT File</option>
		<option <?php echo $strLogger == 'xml' ? 'selected' : ''; ?> value="xml">XML File</option>
		<option <?php echo $strLogger == 'firefox' ? 'selected' : ''; ?> value="firefox">FirePHP-Console</option>
		<option <?php echo $strLogger == 'chrome' ? 'selected' : ''; ?> value="chrome">Chrome-Console</option>
		<option <?php echo $strLogger == 'null' ? 'selected' : ''; ?> value="null">Null-Logger</option>
	</select><br/>
	<label for="level">Environment:</label>
	<select id="level" name="level">
		<option <?php echo $strLevel == 'dev' ? 'selected' : ''; ?> value="dev">Development</option>
		<option <?php echo $strLevel == 'test' ? 'selected' : ''; ?> value="test">Test</option>
		<option <?php echo $strLevel == 'deploy' ? 'selected' : ''; ?> value="deploy">Deploy</option>
	</select><br/>
	<button type="submit">Reload</button>
	</form>
</body>
</html>
