<?php
declare(strict_types=1);

namespace SKien\XLogger;

use FirePHP\Core\FirePHP;
use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger for Output to the FirePHP console of the browser.
 *
 * This class does not use all of FirePHP's capabilities, but can be used 
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
class FirePHPLogger extends XLogger
{
    /** @var FirePHP    the FirePHP instance     */
    protected FirePHP $fb;
    
    /**
     * Create instance of the FirePHP (anyway implemented to use as singleton...)
     * Add own class, file and dir to the ignore list of FirePHP
     * so correct filename(line) of logger-call in the FirePHP outputwindow is displayed
     * @param string $level
     */
    public function __construct(string $level = LogLevel::DEBUG)
    {
        $this->setLogLevel($level);
        $this->fb = FirePHP::getInstance(true);
        
        $this->fb->ignoreClassInTraces(get_class());
        $this->fb->ignorePathInTraces(__DIR__);
        $this->fb->ignorePathInTraces(__FILE__);
    }
    
    /**
     * Logs with an arbitrary level.
     * @param string    $level
     * @param mixed     $message
     * @param mixed[]   $context
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
                $this->fb->error($message);
                break;
            case LogLevel::WARNING:
                $this->fb->warn($message);
                break;
            case LogLevel::NOTICE:
                $this->fb->log($message);
                break;
            case LogLevel::INFO:
                $this->fb->info($message);
                break;
            case LogLevel::DEBUG:
                $this->fb->log($message);
                break;
        }
        if (count($context) >0) {
            // $this->fb->group('context');
            foreach ($context as $key => $value) {
                $this->fb->log($value, $key);
            }
            // $this->fb->groupEnd();
        }
    }
}
