<?php
declare(strict_types=1);

namespace SKien\XLogger;

use CCampbell\ChromePhp\ChromePhp;
use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger for Output to the console of the Chrome browser.
 *
 * This class does not use all of Chrome Logger capabilities, but can be used 
 * very flexibly due to the PSR-3 compatibility components into the browser console.
 *
 * #### History
 * - *2020-07-15*   initial version
 * - *2020-08-02*   output context key(s) as info only in case message does't include placeholder for
 *   
 * @package SKien\XLogger
 * @version 1.0.1
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
        parent::__construct($level);
        ChromePhp::getInstance()->addSetting(ChromePhp::BACKTRACE_LEVEL, 3);
    }
    
    /**
     * Logs with an arbitrary level.
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = array()) : void
    {
        // check, if requested level should be logged
        // causes InvalidArgumentException in case of unknown level.
        if (!$this->logLevel($level)) {
            return;
        }
        $strMessage = 'PHP-' . strtoupper($level) . ': ' . $this->replaceContext($message, $context);
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                ChromePhp::error($strMessage);
                break;
            case LogLevel::WARNING:
                ChromePhp::warn($strMessage);
                break;
            case LogLevel::NOTICE:
                ChromePhp::log($strMessage);
                break;
            case LogLevel::INFO:
                ChromePhp::info($strMessage);
                break;
            case LogLevel::DEBUG:
                ChromePhp::log($strMessage);
                break;
        }
        if (count($context) > 0) {
            ChromePhp::group();
            foreach ($context as $key => $value) {
                // only add, if not included as placeholder in the mesage
                if (strpos($message, '{' . $key . '}') === false) {
                    ChromePhp::info($key, $value);
                }
            }
            ChromePhp::groupEnd();
        }
    }
}
