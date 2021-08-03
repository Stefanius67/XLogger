<?php
declare(strict_types=1);

namespace SKien\XLogger;

use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger for Output to plain text file (.log, .csv, .txt)
 *
 * Each log item is represented by one single line containing the fields
 * separated by defined character.
 *
 * Dependent on the file extension following field separator is used:
 * - `.log` :   TAB
 * - `.csv` :   semicolon (;)
 * - `.txt` :   colon (,)
 *
 * CR/LF in message are replaced with Space to keep one item in a single line.
 * Field separator is replaced to keep the number of fields consistent.
 *
 * @package XLogger
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class FileLogger extends XLogger
{
    /** @var resource|bool file handle of the opened logfile     */
    protected $logfile = false;
    /** @var string separator     */
    protected string $strSep = '';
    /** @var string replacement for separator inside of message      */
    protected string $strReplace = '';
    /** @var bool $bReset reset file when open     */
    protected bool $bReset = false;

    /**
     * Init logging level and remote username (if set).
     * @see XLogger::setLogLevel()
     * @param string $level the min. `LogLevel` to be logged
     */
    public function __construct(string $level = LogLevel::DEBUG)
    {
        parent::__construct($level);
    }

    /**
     * close file if already opened.
     */
    public function __destruct()
    {
        // textbased file kept open...
        $this->closeLogfile();
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
        if ($this->logLevel($level)) {
            // Open file if not opened so far, dependend on the file extension the separator is set.
            $this->openLogfile();
            if (!is_resource($this->logfile)) {
                return;
            }

            // timestamp
            $strLine = date('Y-m-d H:i:s');
            // IP adress
            if (($this->iOptions & self::LOG_IP) != 0) {
                $strIP = $_SERVER['REMOTE_ADDR'];
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $strIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
                $strLine .= $this->strSep . $strIP;
            }
            // user
            if (($this->iOptions & self::LOG_USER) != 0) {
                $strLine .= $this->strSep . $this->prepareText($this->strUser);
            }
            // backtrace - caller
            if (($this->iOptions & self::LOG_BT) != 0) {
                $strLine .= $this->strSep . $this->getCaller();
            }
            // the message
            $strMessage = $this->replaceContext($message, $context);
            $strLine .= $this->strSep . $this->prepareText(strtoupper($level) . ': ' . $strMessage);
            // user agent
            if (($this->iOptions & self::LOG_BT) != 0) {
                $strLine .= $this->strSep . $this->prepareText($_SERVER["HTTP_USER_AGENT"]);
            }

            // and write to the file
            flock($this->logfile, LOCK_EX);
            fwrite($this->logfile, $strLine . PHP_EOL);
            fflush($this->logfile);
            flock($this->logfile, LOCK_UN);
        }
    }

    /**
     * close file if open, reopen and truncate existing file
     */
    public function reset() : void
    {
        if (is_resource($this->logfile)) {
            fclose($this->logfile);
            $this->logfile = fopen($this->getFullpath(), 'w');
        } else {
            $this->bReset = true;
        }
    }

    /**
     * Open the logfile if not done so far.
     * Dependend on the file extension the speparator is set.
     * @return void
     */
    protected function openLogfile() : void
    {
        if ($this->logfile === false) {
            $strFullPath = $this->getFullpath();
            switch (strtolower(pathinfo($strFullPath, PATHINFO_EXTENSION))) {
                case 'csv':
                case 'txt':
                    $this->strSep = ";";
                    $this->strReplace = ",";
                    break;
                case 'log':
                default:
                    $this->strSep = "\t";
                    $this->strReplace = " ";
                    break;
            }
            $this->logfile = fopen($strFullPath, $this->bReset ? 'w' : 'a');
            $this->bReset = false;
        }
    }

    /**
     * close file if already opened.
     * @return void
     */
    protected function closeLogfile() : void
    {
        if (is_resource($this->logfile)) {
            fclose($this->logfile);
            $this->logfile = false;
        }
    }


    /**
     * @param string $strMessage
     * @return string
     */
    protected function prepareText(string $strMessage) : string
    {
        // it make sense to replace LF because each line representing one log item!
        // ... and also the separator should not be included in the message itself
        if (strlen($this->strSep) > 0) {
            $strMessage = str_replace("\r\n", ' ', $strMessage);
            $strMessage = str_replace("\r", ' ', $strMessage);
            $strMessage = str_replace("\n", ' ', $strMessage);
            $strMessage = str_replace($this->strSep, $this->strReplace, $strMessage);
        }
        return $strMessage;
    }
}
