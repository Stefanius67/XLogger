<?php
declare(strict_types=1);

namespace SKien\XLogger;

use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger for Output to XML file.
 *
 * This class creates XML file that can be transformed with given xsl into HTML
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
class XMLLogger extends XLogger
{
    /** @var  \DOMDocument dom document for logging    */
    protected ?\DOMDocument  $xmlDoc = null;
    /** @var  \DOMNode root element of the dom document    */
    protected ?\DOMNode  $xmlRoot = null;
    /** @var string fullpath to XSL file for HTML transformation of the XML log     */
    protected string $strXSLFile = '';
    
    /**
     * Create instance of the logger
     */
    public function __construct(string $strFullpath, string $level = LogLevel::DEBUG)
    {
        $this->setLogLevel($level);
        $this->setFullpath($strFullpath);
        // init with remote user, if available
        $this->strUser = isset($_SERVER["REMOTE_USER"]) ? $_SERVER["REMOTE_USER"] : '';
    }

    /**
     * Set XSL file to transform the log to HTML
     * @param string $strXSLFilen
     */
    public function setXSLFile(string $strXSLFilen) 
    {
        $this->strXSLFile = $strXSLFilen;
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
        if ($this->logLevel($level)) {
            // First open the logfile.
            $this->openLogfile();
            if (!$this->xmlDoc || !$this->xmlRoot) {
                return;
            }
            
            $xmlItem = $this->addChildToDoc('item', '', $this->xmlRoot);
            
            $this->addChildToDoc('timestamp', date('Y-m-d H:i:s'), $xmlItem);
            // IP adress
            if (($this->iOptions & self::LOG_IP) != 0 ) {
                $strIP = $_SERVER['REMOTE_ADDR'];
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $strIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
                $this->addChildToDoc('IP-adress', $strIP, $xmlItem);
            }
            // user
            if (($this->iOptions & self::LOG_USER) != 0 ) {
                $this->addChildToDoc('user', $this->strUser, $xmlItem);
            }
            // backtrace - caller
            if (($this->iOptions & self::LOG_BT) != 0 ) {
                $this->addChildToDoc('caller', $this->getCaller(), $xmlItem);
            }
            // the message
            $strMessage = $this->replaceContext($message, $context);
            $this->addChildToDoc('level', strtoupper($level), $xmlItem);
            $this->addChildToDoc('message', $strMessage, $xmlItem);
            // user agent
            if (($this->iOptions & self::LOG_UA) != 0 ) {
                $this->addChildToDoc('useragent', $_SERVER["HTTP_USER_AGENT"], $xmlItem);
            }
            $this->xmlDoc->save($this->getFullpath());
        }
    }
    
    /**
     * Get the logfilehandle. If not opened so far, the file will be opened and
     * dependend onm the file extensiopn the speparotor is set.
     * @return resource
     */
    protected function openLogfile() 
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
     */
    protected function createLogfile() 
    {
        $this->xmlDoc = null;
        $this->xmlRoot = null;

        $this->xmlDoc = new \DOMDocument();
        $this->xmlDoc->preserveWhiteSpace = false;
        $this->xmlDoc->formatOutput = true;
        if (strlen($this->strXSLFile) > 0 ) {
            $xslt = $this->xmlDoc->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="' . $this->strXSLFile . '"');
            $this->xmlDoc->appendChild($xslt);
        }
        $this->xmlRoot = $this->addChildToDoc('log');
        $this->xmlDoc->save($this->getFullpath());
    }

    /**
     * Close the logfile
     */
    protected function closeLogfile()
    {
        if ($this->xmlDoc) {
            $this->xmlDoc = null;
        }
        if ($this->xmlRoot) {
            $this->xmlRoot = null;
        }
    }

    /**
     * Get full path of the logfile.
     * @return string
     */
    protected function getFullpath() : string 
    {
        $strFullPath = rtrim($this->strPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->strFilename;
        return $strFullPath;
    }
    
    /**
     * create new DOMElement and append it to given parent
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
