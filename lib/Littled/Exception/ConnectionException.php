<?php
namespace Littled\Exception;

/**
 * Class ConnectionException
 * @package Littled\Exception
 */
class ConnectionException extends \Exception
{
	/**
	 * ConnectionException constructor.
	 * @param string $message Error message.
	 * @param int[optional] $code Error code.
	 * @param \Exception|null $previous
	 */
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        // some code

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

	/**
	 * custom string representation of object
	 */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}