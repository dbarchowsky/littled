<?php
namespace Littled\Request;

use Littled\Validation\Validation;

/**
 * Class IntegerInput
 * @package Littled\Request
 */
class IntegerInput extends RequestInput
{
	/**
	 * Collects the value corresponding to the $param property value in GET, POST, session, or cookies.
	 * @param int|null[optional] $filters Filters for parsing request variables, e.g. FILTER_UNSAFE_RAW, FILTER_SANITIZE_STRING, etc.
	 * @param array|null[optional] $src Collection of input data. If not specified, will read input from POST, GET, Session vars.
	 * @param string|null[optional] $key Key to use in place of the internal $key property value.
	 */
	public function collectFromInput($filters = null, $src = null, $key=null)
	{
		if ($this->bypassCollectFromInput===true) {
			return;
		}
		$this->value = Validation::collectIntegerRequestVar((($key)?($key):($this->key)), null, $src);
	}

	/**
	 * Escapes the object's value property for inclusion in SQL queries.
	 * @param \mysqli $mysqli
	 * @return string Escaped value.
	 */
	public function escapeSQL($mysqli)
	{
		$value = Validation::parseInteger($this->value);
		if ($value===null) {
			return('NULL');
		}
		return ($mysqli->real_escape_string($value));
	}

	/**
	 * @param integer $value Value to assign as the value of the object.
	 */
	public function setInputValue($value)
	{
		$this->value = Validation::parseInteger($value);
	}

	/**
	 * Validates the object's current value stored in its $value property.
	 * @returns True if no validation errors are found.
	 * @throws \Littled\Exception\ContentValidationException
	 */
	public function validate()
	{
		parent::validate();
		if (($this->isEmpty()===false) && (Validation::parseInteger($this->value)===null)) {
			$this->throwValidationError(ucfirst($this->label)." is in unrecognized format.");
		}
		return (true);
	}
}