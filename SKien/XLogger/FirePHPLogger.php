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
 * @package XLogger
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class FirePHPLogger extends XLogger
{
    /** @var FirePHP    the FirePHP instance     */
    protected FirePHP $fb;

    /**
     * Init logging level and remote username (if set).
     * @see XLogger::setLogLevel()
     * @param string $level the min. `LogLevel` to be logged
     */
    public function __construct(string $level = LogLevel::DEBUG)
    {
        parent::__construct($level);

        // Create instance of the FirePHP (anyway implemented to use as singleton...)
        // Add own class, file and dir to the ignore list of FirePHP
        // so correct filename(line) of logger-call in the FirePHP outputwindow is displayed

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
                $this->fb->error($strMessage);
                break;
            case LogLevel::WARNING:
                $this->fb->warn($strMessage);
                break;
            case LogLevel::NOTICE:
                $this->fb->log($strMessage);
                break;
            case LogLevel::INFO:
                $this->fb->info($strMessage);
                break;
            case LogLevel::DEBUG:
                $this->fb->log($strMessage);
                break;
        }
        if (count($context) > 0) {
            foreach ($context as $key => $value) {
                // only add, if not included as placeholder in the mesage
                if (strpos($message, '{' . $key . '}') === false) {
                    $this->fb->log($value, $key);
                }
            }
        }
    }
}
