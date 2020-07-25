<?php
namespace SKien\XLogger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * abstract class for several types of logger.
 * 
 * Class implements the PSR-3 LoggerInferface through AbstractLogger.
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
abstract class XLogger extends AbstractLogger
{
    /** include IP-adress in log item     */    
    const LOG_IP        = 0x01;
    /** include backtrace (caller) in log item     */
    const LOG_BT        = 0x02;
    /** include user in log item     */
    const LOG_USER      = 0x04;
    /** include user-agent in log item     */
    const LOG_UA        = 0x08;
    
    /** @var int levels to include in the log (bitmask of internal levels)     */
    protected int $iLogLevel = 0;
    /** @var int options bitmask     */
    protected int $iOptions = self::LOG_IP | self::LOG_USER | self::LOG_UA;
    /** @var string username for logging (if LOG_USER is set)     */
    protected string $strUser = '';
    /** @var string path to store the logfile     */
    protected string $strPath = '.';
    /** @var string filename of the logfile     */
    protected string $strFilename = '';
    
    /**
     * Set the level from witch items should be logged.
     * All entries with a lower level than the specified are ignored.
     * The relevance of the defined PSR-3 level from hight to low are:
     * EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG
     * @param string $strLogLevel
     */
    public function setLogLevel(string $level)
    {
        $this->iLogLevel = $this->getIntLevel($level);
    }
    
    /**
     * Check, if item for requested level should be logged.
     * @param string $level
     * @return bool
     */
    public function logLevel($level) : bool
    {
        return ($this->iLogLevel <= $this->getIntLevel($level));
    }

    /**
     * @param int $iOptions
     */
    public function setOptions(int $iOptions)
    {
        $this->iOptions = $iOptions;
    }

    /**
     * Set the username.
     * @param string $strUser
     */
    public function setUser(string $strUser)
    {
        $this->strUser = $strUser;
    }

    /**
     * @param string $strPath
     */
    public function setFullpath(string $strFullpath)
    {
        $this->closeLogfile();
        if (strlen($strFullpath) > 0) {
            $strFullpath = str_replace('{date}', date('Y-m-d'), $strFullpath);
            $this->strPath = pathinfo($strFullpath, PATHINFO_DIRNAME);
            $this->strFilename = pathinfo($strFullpath, PATHINFO_BASENAME);
        }
    }
    
    /**
     * @param string $strPath
     */
    public function setPath(string $strPath)
    {
        $this->closeLogfile();
        $this->strPath = $strPath;
    }
    
    /**
     * @param string $strFilename
     */
    public function setFilename(string $strFilename)
    {
        $this->closeLogfile();
        $strFilename = str_replace('{date}', date('Y-m-d'), $strFilename);
        $this->strFilename = $strFilename;
    }
    
    /**
     * Get current filename (may be supplemented with the date)
     * @return string
     */
    public function getFilename() : string
    {
        return $this->strFilename;
    }
    
    /**
     * Replace placeholders with the coresponding values.
     * @param mixed $message    string or object implements __toString() method
     * @param array $context
     * @return string
     */
    protected function replaceContext($message, array $context = array()) : string
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        // interpolate replacement values into the message and return
        $strMessage = (string)$message;
        $strMessage = strtr($strMessage, $replace);
        
        return $strMessage;
    }
    
    /**
     * Get the caller from backtrace in format "<filename>(<line>)".
     * Document root is cut off from the full path.
     * @return string
     */
    protected function getCaller() : string
    {
        $strCaller = '';
        $aBacktrace = debug_backtrace();
        while (($aCaller = array_shift($aBacktrace)) !== null) {
            // just get latest call outside of the logger interface
            // if ($aCaller['class'] != get_class() ) {
            if (!is_subclass_of($aCaller['class'], 'Psr\Log\AbstractLogger')) {
                break;
            }
        }
        if ($aCaller) {
            // the base path on server isn't from interest.. 
            $strFile = str_replace($_SERVER['DOCUMENT_ROOT'], '', $aCaller['file']);
            $strCaller = $strFile . ' ('.$aCaller['line'].')';
        }
        return $strCaller;
    }
    
    /**
     * 
     * @param string $level
     * @throws InvalidArgumentException
     * @return int
     */
    protected function getIntLevel(string $level) : int
    {
        $aLevel = array(
            LogLevel::EMERGENCY => 7,
            LogLevel::ALERT => 6,
            LogLevel::CRITICAL => 5,
            LogLevel::ERROR => 4,
            LogLevel::WARNING => 3,
            LogLevel::NOTICE => 2,
            LogLevel::INFO => 1,
            LogLevel::DEBUG => 0
        );
        if (!isset($aLevel[$level])) {
            throw new InvalidArgumentException("Unknown logging level ($level)");
        }
        return $aLevel[$level];
    }

    /**
     * Has nothing to do in the abstract class... 
     * ...but does not necessarily have to be implemented in extended classes
     * and is therefore not declared as an abstract at this point. 
     */
    protected function openLogfile()
    {
    }
    
    /**
     * Has nothing to do in the abstract class... 
     * ...but does not necessarily have to be implemented in extended classes
     * and is therefore not declared as an abstract at this point. 
     */
    protected function createLogfile()
    {
    }
    
    /**
     * Has nothing to do in the abstract class... 
     * ...but does not necessarily have to be implemented in extended classes
     * and is therefore not declared as an abstract at this point. 
     */
    protected function closeLogfile()
    {
    }
}
