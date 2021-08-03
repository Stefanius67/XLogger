<?php
declare(strict_types=1);

namespace SKien\XLogger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * abstract class for several types of logger.
 *
 * Class implements the PSR-3 LoggerInferface through AbstractLogger.
 *
 * @package XLogger
 * @author Stefanius <s.kientzler@online.de>
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
     * Init logging level and remote username (if set).
     * @see XLogger::setLogLevel()
     * @param string $level the min. `LogLevel` to be logged
     */
    public function __construct(string $level = LogLevel::DEBUG)
    {
        $this->setLogLevel($level);
        // init with remote user, if available
        $this->strUser = isset($_SERVER["REMOTE_USER"]) ? $_SERVER["REMOTE_USER"] : '';
    }

    /**
     * Set the level from witch items should be logged.
     * All entries with a lower level than the specified are ignored. <br/>
     * The relevance of the defined PSR-3 level from hight to low are: <br/><ul>
     * <li> EMERGENCY </li>
     * <li> ALERT </li>
     * <li> CRITICAL </li>
     * <li> ERROR </li>
     * <li> WARNING </li>
     * <li> NOTICE </li>
     * <li>INFO </li>
     * <li> DEBUG </li></ul>
     *
     * @param string $strLogLevel   the min. `LogLevel` to be logged
     * @return void
     */
    public function setLogLevel(string $strLogLevel) : void
    {
        $this->iLogLevel = $this->getIntLevel($strLogLevel);
    }

    /**
     * Set Options for logging.
     * Use any combination of: <ul>
     * <li> XLogger::LOG_IP :      include IP-adress in log item </li>
     * <li> XLogger::LOG_BT :      include backtrace 'filename (line)' in log item </li>
     * <li> XLogger::LOG_USER :    include user in log item </li>
     * <li> XLogger::LOG_UA :      include user-agent in log item </li></ul>
     *
     * @param int $iOptions any combination (bitwise or, '|') of the flags described
     * @return void
     */
    public function setOptions(int $iOptions) : void
    {
        $this->iOptions = $iOptions;
    }

    /**
     * Set the username.
     * @param string $strUser
     * @return void
     */
    public function setUser(string $strUser) : void
    {
        $this->strUser = $strUser;
    }

    /**
     * Set the path and filename for the logfile.
     * In order to be able to assign logfiles chronologically and / or to a user, it is
     * possible to use placeholders in the file name or path, which are replaced accordingly
     * before the file is created.
     * Following placeholders are supported:  <br/><ul>
     * <li> {date}   : will be replaced by current date (Format 'YYYY-MM-DD') </li>
     * <li> {month} : will be replaced by current month (Format 'YYYY-MM') </li>
     * <li> {year}   : will be replaced by current year (Format 'YYYY') </li>
     * <li> {week}   : will be replaced by current ISO-8601 week (Format 'YYYY_WW') </li>
     * <li> {name}   : will be replaced by the username </li></ul>
     *
     * > Note: <br/>
     * > If you use the placeholder for the user name, this have to be set BEFORE the call of
     *   this method. In the username, all characters except A-Z, a-z, 0-9, '_' and '-' are
     *   filtered out (to always get a valid file name)!
     * @param string $strFullpath
     * @return void
     */
    public function setFullpath(string $strFullpath) : void
    {
        $this->closeLogfile();
        if (strlen($strFullpath) > 0) {
            $strFullpath = $this->replacePathPlaceholder($strFullpath);
            // scrutinizer didn't realize, that pathinfo returns allways string, if $options set!
            $this->strPath = /** @scrutinizer ignore-type */ pathinfo($strFullpath, PATHINFO_DIRNAME);
            $this->strFilename = /** @scrutinizer ignore-type */ pathinfo($strFullpath, PATHINFO_BASENAME);
        }
    }

    /**
     * Set the path for the logfile.
     * Some placeholders can be used for date/month/year/week.
     * @param string $strPath
     * @return void
     * @see XLogger::setFullpath()
     */
    public function setPath(string $strPath) : void
    {
        $this->closeLogfile();
        $this->strPath = $this->replacePathPlaceholder($strPath);
    }

    /**
     * Set the filename for the logfile.
     * Some placeholders can be used for date/month/year/week.
     * @param string $strFilename
     * @return void
     * @see XLogger::setFullpath()
     */
    public function setFilename(string $strFilename) : void
    {
        $this->closeLogfile();
        $this->strFilename = $this->replacePathPlaceholder($strFilename);
    }

    /**
     * Get current filename (may be some placeholders are supplemented).
     * @return string
     * @see XLogger::setFullpath()
     */
    public function getFilename() : string
    {
        return $this->strFilename;
    }

    /**
     * Get full path of the logfile.
     * @return string
     * @see XLogger::setFullpath()
     */
    public function getFullpath() : string
    {
        $strFullPath = rtrim($this->strPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->strFilename;
        return $strFullPath;
    }

    /**
     * may be implemented in extending classes
     * @internal
     */
    public function reset() : void
    {
    }

    /**
     * Check, if item for requested level should be logged.
     * @param string $level
     * @return bool
     */
    protected function logLevel($level) : bool
    {
        return ($this->iLogLevel <= $this->getIntLevel($level));
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
            $strCaller = $strFile . ' (' . $aCaller['line'] . ')';
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
     * @see XLogger::setFullPath()
     * @param string $strPath
     * @return string
     */
    protected function replacePathPlaceholder(string $strPath) : string
    {
        $strPath = str_replace('{date}', date('Y-m-d'), $strPath);
        $strPath = str_replace('{month}', date('Y-m'), $strPath);
        $strPath = str_replace('{year}', date('Y'), $strPath);
        $strPath = str_replace('{week}', date('Y_W'), $strPath);

        $strUser = preg_replace("/[^A-Za-z0-9_-]/", '', $this->strUser);
        $strPath = str_replace('{name}', $strUser, $strPath);

        return $strPath;
    }

    /**
     * Has nothing to do in the abstract class...
     * ...but does not necessarily have to be implemented in extended classes
     * and is therefore not declared as an abstract at this point.
     * @return void
     */
    protected function openLogfile() : void
    {
    }

    /**
     * Has nothing to do in the abstract class...
     * ...but does not necessarily have to be implemented in extended classes
     * and is therefore not declared as an abstract at this point.
     * @return void
     */
    protected function createLogfile() : void
    {
    }

    /**
     * Has nothing to do in the abstract class...
     * ...but does not necessarily have to be implemented in extended classes
     * and is therefore not declared as an abstract at this point.
     * @return void
     */
    protected function closeLogfile() : void
    {
    }
}
