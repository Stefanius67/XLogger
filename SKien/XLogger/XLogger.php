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
 * #### History
 * - *2020-07-15*   initial version
 *
 * @package SKien\XLogger
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
     * Set Options for logging.
     * Use any combination of:
     * - XLogger::LOG_IP :      include IP-adress in log item
     * - XLogger::LOG_BT :      include backtrace 'filename (line)' in log item
     * - XLogger::LOG_USER :    include user in log item
     * - XLogger::LOG_UA :      include user-agent in log item
     * 
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
     * Set the path and filename for the logfile.
     * Some placeholders can be replaced.
     * Supported placeholders: {@see XLogger::replacePathPlaceholder()}
     * @param string $strFullpath
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
     * Set the path for the logfile.
     * Some placeholders can be replaced.
     * Supported placeholders: {@see \SKien\XLogger\XLogger::replacePathPlaceholder()}
     * @param string $strPath
     */
    public function setPath(string $strPath)
    {
        $this->closeLogfile();
        $this->strPath = $strPath;
    }
    
    /**
     * Set the filename for the logfile.
     * Some placeholders can be replaced.
     * Supported placeholders: {@see \SKien\XLogger\XLogger::replacePathPlaceholder()}
     * 
     * @param string $strFilename
     * @see \SKien\XLogger\XLogger::replacePathPlaceholder()
     */
    public function setFilename(string $strFilename)
    {
        $this->closeLogfile();
        $this->strFilename = $this->replacePathPlaceholder($strFilename);
    }
    
    /**
     * Get current filename (may be some placeholders are supplemented).
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
     * Replaces placeholders in path/filename/fullpath.
     * Following placeholders are supported:
     * - {date} : will be replaced by current date (Format 'YYYY-MM-DD')
     * - {month} : will be replaced by current month (Format 'YYYY-MM')  
     * - {year} : will be replaced by current year (Format 'YYYY')
     * - {week} : will be replaced by current ISO-8601 week (Format 'YYYY_WW')
     * 
     * @param string $strPath
     * @return string
     */
    protected function replacePathPlaceholder(string $strPath) : string
    {
        $strPath = str_replace('{date}', date('Y-m-d'), $strPath);
        $strPath = str_replace('{month}', date('Y-m'), $strPath);
        $strPath = str_replace('{year}', date('Y'), $strPath);
        $strPath = str_replace('{week}', date('Y_W'), $strPath);
        return $strPath;
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
