<?php
declare(strict_types=1);

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
// use Psr\Log\LoggerAwareInterface;

/**
 * Testclass to demonstrate the integration of any PSR-3 logger into own classes.
 * Either implement LoggerAwareInterface or integrate LoggerAwareTrait by use stetemant.
 */
class TestClass // implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * In both cases it makes sense to initialize the $logger property with an 
     * instance of the PSR-3 NullLogger() so nowhere in the code have to be tested, 
     * if any logger is set. 
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }
    
    public function doSomething()
    {
        $this->logger->warning('inside of class');
    }
}
