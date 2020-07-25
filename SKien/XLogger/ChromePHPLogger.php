<?php
declare(strict_types=1);

namespace SKien\XLogger;

use CCampbell\ChromePhp\ChromePhp;
use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger for Output to the console of the Chrome browser.
 *
 * This class does not use all of Chrome Logger capabilities, but can be used 
 * very flexibly due to the PSR-3 compatibility - also for logging existing 
 * components into the browser console.
 *
 * history:
 * date         version
 * 2020-07-15   initial version
 *
 * @package XLogger
 * @version 1.0.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class ChromePHPLogger extends XLogger
{
    /**
     * Set backtrace level to 'ignore' last calls inside logger class when
     * displaying filename/line of the log call. 
     * @param string $level
     */
    public function __construct(string $level = LogLevel::DEBUG)
    {
        $this->setLogLevel($level);
        ChromePhp::getInstance()->addSetting(ChromePhp::BACKTRACE_LEVEL, 3);
    }
    
    /**
     * Logs with an arbitrary level.
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = array())
    {
        // check, if requested level should be logged
        // causes InvalidArgumentException in case of unknown level.
        if (!$this->logLevel($level)) {
            return;
        }
        $message = 'PHP-' . strtoupper($level) . ': ' . $this->replaceContext($message, $context);
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                ChromePhp::error($message);
                break;
            case LogLevel::WARNING:
                ChromePhp::warn($message);
                break;
            case LogLevel::NOTICE:
                ChromePhp::log($message);
                break;
            case LogLevel::INFO:
                ChromePhp::info($message);
                break;
            case LogLevel::DEBUG:
                ChromePhp::log($message);
                break;
        }
        if (count($context) >0) {
            ChromePhp::group();
            foreach ($context as $key => $value) {
                ChromePhp::info($key, $value);
            }
            ChromePhp::groupEnd();
        }
    }
}
