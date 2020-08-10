<?php
declare(strict_types=1);

namespace SKien\XLogger;

use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger for Output to XML file.
 *
 * This class creates XML file that can be transformed with given xsl into HTML
 * #### Structure of the XML-File
 * ```xml
 *      <log>
 *          <item>
 *              <timestamp>2020-07-21 18:22:58</timestamp>
 *              <user>SKien</user>
 *              <caller>/packages/XLogger/XLogTest.php (62)</caller>
 *              <level>ERROR</level>
 *              <message>bad conditions :-(</message>
 *          </item>
 *          <item>
 *              ...
 *          </item>
 *      </log>
 * ```
 *
 * #### History
 * - *2020-07-15*   initial version
 *
 * @package SKien\XLogger
 * @version 1.0.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class XMLLogger extends XLogger
{
    /** @var  \DOMDocument dom document for logging    */
    protected ?\DOMDocument  $xmlDoc = null;
    /** @var  \DOMElement root element of the dom document    */
    protected ?\DOMElement  $xmlRoot = null;
    /** @var string fullpath to XSL file for HTML transformation of the XML log     */
    protected string $strXSLFile = '';
    
    /**
     * Create instance of the logger
     * @param string $level
     */
    public function __construct(string $level = LogLevel::DEBUG)
    {
        parent::__construct($level);
    }

    /**
     * Set XSL file to transform the log to HTML
     * @param string $strXSLFilen
     * @return void
     */
    public function setXSLFile(string $strXSLFilen) : void
    {
        $this->strXSLFile = $strXSLFilen;
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
            // First open the logfile.
            $this->openLogfile();
            if (!$this->xmlDoc || !$this->xmlRoot) {
                return;
            }
            
            $xmlItem = $this->addChildToDoc('item', '', $this->xmlRoot);
            
            $this->addChildToDoc('timestamp', date('Y-m-d H:i:s'), $xmlItem);
            // IP adress
            if (($this->iOptions & self::LOG_IP) != 0) {
                $strIP = $_SERVER['REMOTE_ADDR'];
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $strIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
                $this->addChildToDoc('IP-adress', $strIP, $xmlItem);
            }
            // user
            if (($this->iOptions & self::LOG_USER) != 0) {
                $this->addChildToDoc('user', $this->strUser, $xmlItem);
            }
            // backtrace - caller
            if (($this->iOptions & self::LOG_BT) != 0) {
                $this->addChildToDoc('caller', $this->getCaller(), $xmlItem);
            }
            // the message
            $strMessage = $this->replaceContext($message, $context);
            $this->addChildToDoc('level', strtoupper($level), $xmlItem);
            $this->addChildToDoc('message', $strMessage, $xmlItem);
            // user agent
            if (($this->iOptions & self::LOG_UA) != 0) {
                $this->addChildToDoc('useragent', $_SERVER["HTTP_USER_AGENT"], $xmlItem);
            }
            
            if (count($context) > 0) {
                foreach ($context as $key => $value) {
                    if ($key == 'exception') {
                        $xmlEx = $this->addChildToDoc('exception', '', $xmlItem);
                        $this->addChildToDoc('msg', (string)$value, $xmlEx);
                        $this->addChildToDoc('class', get_class($value), $xmlEx);
                        $aTrace = $value->getTrace();
                        foreach ($aTrace as $aTraceItem) {
                            $xmlTrace = $this->addChildToDoc('trace', '', $xmlEx);
                            foreach ($aTraceItem as $key => $value) {
                                $this->addChildToDoc($key, (string)$value, $xmlTrace);
                            }
                        }
                    } else if (strpos($message, '{' . $key . '}') === false) {
                        $xmlContext = $this->addChildToDoc('context', '', $xmlItem);
                        $this->addChildToDoc('key', (string)$key, $xmlContext);
                        $this->addChildToDoc('value', (string)$value, $xmlContext);
                    }
                }
            }
            
            $this->xmlDoc->save($this->getFullpath());
        }
    }
    
    /**
     * Open the logfile. 
     * If not opened so far, the file will be opened and
     * root element to append new items is set.
     * If file does not exist, it will be created.
     * @return void
     */
    protected function openLogfile() : void
    {
        if (!$this->xmlDoc ||  !$this->xmlRoot) {
            $strFullPath = $this->getFullpath();
            if (!file_exists($strFullPath)) {
                $this->createLogfile();
            } else {
                $this->xmlDoc = new \DOMDocument();
                $this->xmlDoc->preserveWhiteSpace = false;
                $this->xmlDoc->formatOutput = true;
                $this->xmlDoc->load($strFullPath);
                $this->xmlRoot = $this->xmlDoc->documentElement;
            }
        }
    }

    /**
     * Create new logfile and insert base XML-structure.
     * @return void
     */
    protected function createLogfile() : void
    {
        $this->xmlDoc = null;
        $this->xmlRoot = null;

        $this->xmlDoc = new \DOMDocument();
        $this->xmlDoc->preserveWhiteSpace = false;
        $this->xmlDoc->formatOutput = true;
        if (strlen($this->strXSLFile) > 0) {
            $xslt = $this->xmlDoc->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="' . $this->strXSLFile . '"');
            $this->xmlDoc->appendChild($xslt);
        }
        $this->xmlRoot = $this->addChildToDoc('log');
        $this->xmlDoc->save($this->getFullpath());
    }

    /**
     * Close the logfile
     * @return void
     */
    protected function closeLogfile() : void
    {
        if ($this->xmlDoc) {
            $this->xmlDoc = null;
        }
        if ($this->xmlRoot) {
            $this->xmlRoot = null;
        }
    }
    
    /**
     * create new DOMNode and append it to given parent
     * @param string $strName
     * @param string $strValue
     * @param \DOMElement $oParent
     * @return \DOMElement
     */
    public function addChildToDoc(string $strName, string $strValue='', \DOMElement $oParent=null) : ?\DOMElement
    {
        $oChild = null;
        if ($this->xmlDoc) {
            $oChild = $this->xmlDoc->createElement($strName, $strValue);
            $oParent ? $oParent->appendChild($oChild) : $this->xmlDoc->appendChild($oChild);
        }
        return $oChild;
    }
}
