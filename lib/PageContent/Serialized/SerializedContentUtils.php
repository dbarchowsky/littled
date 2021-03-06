<?php
namespace Littled\PageContent\Serialized;

use Littled\Database\AppContentBase;
use Littled\Exception\ConfigurationUndefinedException;
use Littled\Exception\ConnectionException;
use Littled\Exception\InvalidQueryException;
use Littled\Exception\InvalidValueException;
use Littled\Exception\RecordNotFoundException;
use Littled\Exception\ResourceNotFoundException;
use Littled\Exception\InvalidTypeException;
use Littled\PageContent\Albums\Gallery;
use Littled\PageContent\PageContent;
use Littled\Request\RequestInput;
use Littled\Request\StringInput;
use Exception;


/**
 * Class SerializedContentUtils
 * @package Littled\PageContent\Serialized
 */
class SerializedContentUtils extends AppContentBase
{
	/** @var array Container for validation error messages. */
	public $validationErrors;
	/** @var string Error message returned when invalid form data is encountered. */
	public $validationMessage;
	/** @var string Path to cache template. */
	protected static $cache_template = '';
	/** @var string Path to rendered cache file to use on site front-end. */
	protected static $output_cache_file = '';

    /**
     * SerializedContentUtils constructor.
     */
	public function __construct()
    {
        parent::__construct();
        $this->validationErrors = [];
        $this->validationMessage = "Errors were found in the content.";
    }

	/**
     * Add a separator string after a string.
     * @param string $str Source string.
     * @param string $separator (Optional) Character or string to append to the source string. Defaults to a comma.
     * @return string Modified string containing the separator.
     */
    public function appendSeparator(string $str, string $separator=','): string
    {
        if(!is_null($str) && strlen(trim($str)) > 0) {
            $str = rtrim($str)."$separator ";
        }
        return ($str);
    }

    /**
	 * Returns the form data members of the objects as series of nested associative arrays.
	 * @param array|null $exclude_keys (Optional) array of parameter names to exclude from the returned array.
	 * @return array Associative array containing the object's form data members as name/value pairs.
	 */
	public function arrayEncode ($exclude_keys=null ): array
	{
		$ar = array();
		foreach ($this as $key => $item) {
			if (is_object($item)) {
				if (!is_array($exclude_keys) || !in_array($key, $exclude_keys)) {
					if ($item instanceof RequestInput) {
						$ar[$key] = $item->value;
					}
					elseif ($item instanceof SerializedContent) {
						/** @var SerializedContent $item */
						$ar[$key] = $item->arrayEncode();
					}
					elseif ($item instanceof Gallery) {
						/** @var Gallery $item */
						$ar[$key] = $item->arrayEncode(array("tn", "site_section"));
					}
				}
			}
		}
		return ($ar);
	}

	/**
	 * Clears the data container values in the object.
	 */
	public function clearValues( )
	{
		foreach ($this as $item) {
			/** @var object $item */
			if(is_object($item) && method_exists($item, 'clearValue')) {
				$item->clearValue();
			}
			elseif(is_object($item) && method_exists($item, 'clearValues')) {
				$item->clearValues();
			}
		}
	}

	/**
	 * Set property values using input variable values, e.g. GET, POST, cookies
	 * @param array[optional] $src Collection of input data. If not specified, will read input from POST, GET, Session vars.
	 */
	public function collectFromInput($src=null)
	{
		foreach($this as $item) {
			if (is_object($item) && method_exists($item, 'collectFromInput')) {
				if (!property_exists($item, 'bypassCollectFromInput') || $item->bypassCollectFromInput===false) {
					$item->collectFromInput(null, $src);
				}
			}
		}
	}

	/**
	 * Copies the property values from one object into this instance.
	 * @param mixed $src Object to use to copy values over to this object.
	 * @throws InvalidTypeException Source is not a valid object.
	 */
	public function copy( $src )
	{
		if (!is_object($src)) {
			throw new InvalidTypeException("Source for copy is not an object.");
		}
		if (get_class($this) != get_class($src)) {
			throw new InvalidTypeException("Invalid object for copy.");
		}
		foreach (get_object_vars($src) as $key => $value) {
			if ((is_object($value)) && ($value instanceof RequestInput)) {
				$this->$key->value = $value->value;
			}
			elseif((is_object($this->$key)) && method_exists($this->$key, 'copy')) {
				$this->$key->copy($value);
			}
			elseif(!is_object($value)) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Fills object properties using property values found in $src argument.
	 * @param array|object $src Source object containing values to assign to this instance.
	 */
	public function fill($src)
	{
		foreach ($src as $key => $val) {
			if (property_exists($this, $key)) {
				if ($this->$key instanceof RequestInput) {
					$this->$key->setInputValue($val);
				}
				elseif (!is_object($this->$key)) {
					$this->$key = $val;
				}
			}
		}
	}

	/**
	 * Returns a list of column names to use to format SQL queries that will be used to read and update
	 * records.
	 * @param array $used_keys (Optional) Properties that have already been added to the stack.
	 * @return array Key/value pairs for each RequestInput property of the class.
	 * @throws ConnectionException
	 * @throws ConfigurationUndefinedException
	 */
	protected function formatDatabaseColumnList($used_keys=[]): array
	{
		$fields = array();
		foreach ($this as $key => $item) {
			if ($this->isInput($key, $item, $used_keys)) {
				if ($item->isDatabaseField===false) {
					continue;
				}
				/* format column name and value for SQL statement */
				if ($item->columnName) {
					$fields[$item->columnName] = $this->escapeSQLValue($item->value);
				} else {
					$fields[$key] = $this->escapeSQLValue($item->value);
				}
			}
		}
		return ($fields);
	}

	/**
	 * Returns cache template path.
	 * @return string Cache template path.
	 */
	public static function getCacheTemplatePath(): string
	{
		return (static::$cache_template);
	}

	/**
	 * Checks of SECTION_ID has been defined as a constant of the class and returns its value if it has.
	 * @return ?int Class's content type id value, if it has been defined.
	 */
	public function getContentTypeID(): ?int
	{
		$content_type_const = get_class($this)."::SECTION_ID";
		return ((defined($content_type_const))?(constant($content_type_const)):(null));
	}

	/**
	 * Assign values contained in array to object input properties.
	 * @param string $query SQL SELECT statement to use to hydrate object property values.
	 * @throws RecordNotFoundException
	 * @throws InvalidQueryException
	 */
	protected function hydrateFromQuery( string $query )
	{
		$data = $this->fetchRecords($query);
		if (count($data) < 1) {
			throw new RecordNotFoundException("Record not found.");
		}
		$this->hydrateFromRecordsetRow($data[0]);
	}

	/**
	 * Assign values contained in array to object input properties.
	 * @param object $row Recordset row containing values to copy into the object's properties.
	 */
	protected function hydrateFromRecordsetRow( object $row )
	{
		$used_keys = array();
		foreach ($this as $key => $item) {
			/** @var RequestInput $item */
			if ($this->isInput($key, $item, $used_keys)) {
				/* store value retrieved from database */
				if ($item->columnName) {
					$custom_key = $item->columnName;
					$item->setInputValue($row->$custom_key);
				}
				else {
					$item->setInputValue($row->$key);
				}
			}
		}
	}

	/**
	 * Checks if the class property is an input object and should be used for
	 * various operations such as updating or retrieving data from the database,
	 * or retrieving data from forms.
	 * @param string $key Name of the class property.
	 * @param object $item Value of the class property.
	 * @param array $used_keys Array containing a list of the objects that
	 * have already been listed as input properties.
	 * @return boolean True if the object is an input class and should be used to update the database. False otherwise.
	 */
	protected function isInput(string $key, object $item, array &$used_keys): bool
	{
		$is_input = (($item instanceof RequestInput) &&
			($key != "id") &&
			($key != "index") &&
			($item->isDatabaseField==true));
		if ($is_input) {
			/* Check if this item has already been used as in input property.
			 * This prevents references used as aliases of existing properties
			 * from being included in database queries.
			 */
			if (in_array($item->key, $used_keys)) {
				$is_input = false;
			}
			else {
				/* once an input property is marked as such, track it so it
				 * can't be included again.
				 */
				$used_keys[] = $item->key;
			}
		}
		return ($is_input);
	}

	/**
	 * Return the form data members of the object as a JSON string.
	 * @param array[optional] $exclude_keys Array of property names to exclude from the encoding.
	 * @return string JSON-encoded name/value pairs extracted from the object.
	 */
	public function jsonEncode ($exclude_keys=null): string
	{
		return (json_encode($this->arrayEncode($exclude_keys)));
	}

	/**
	 * Returns an appropriate label given the value of $count if $count requires the label to be pluralized.
	 * @param int $count Number determining if the label is plural or not.
	 * @param string $property_name Name of property to make plural.
	 * @return string Plural form of the record label if $count is not 1.
	 * @throws ConfigurationUndefinedException
	 * @throws InvalidValueException
	 */
	public function pluralLabel( int $count, string $property_name ): string
	{
		if (!property_exists($this, $property_name)) {
			throw new ConfigurationUndefinedException(
				"Cannot get plural label for unknown property \"$property_name\" of ".get_class($this)
			);
		}
		if ($this->{$property_name} instanceof StringInput === false) {
			throw new ConfigurationUndefinedException(
				"Cannot get plural label for non-string input ".get_class($this)."::$property_name."
			);
		}
		if ($this->{$property_name}->value === null || $this->{$property_name}->value === '') {
			throw new InvalidValueException(
				"Cannot get plural label for ".get_class($this)."::$property_name null or empty string."
			);
		}

		$label = strtolower($this->{$property_name}->value);
		if ($count==1) {
			return ($label);
		}
		elseif (substr($label, -1)=='y') {
			return (substr($label, 0, -1).'ies');
		}
		elseif (substr($label, -1)=='s') {
			return ($label);
		}
		else {
			return ($label.'s');
		}
	}

    /**
     * Add a separator string before a string.
     * @param string $str Source string.
     * @param string $separator (Optional) Character or string to prepend to the source string. Defaults to a comma.
     * @return string Modified string containing the separator.
     */
    public function prependSeparator(string $str, string $separator=','): string
    {
        if(!is_null($str) && strlen(trim($str)) > 0) {
            $str = "$separator ".ltrim($str);
        }
        return ($str);
    }

	/**
	 * Loads content from a template file. Writes the parsed content to a separate file.
	 * @param array[optional] $context Array containing name/value pairs representing variable names and values to insert into the source template at $src_path;
	 * @param string|null[optional] $cache_template Path to content template. If not supplied, the internal $cache_template value will be used.
	 * @param string|null[optional] $output_cache_file Path to cache file. If not supplied, the internal $output_cache_file value will be used.
	 * @throws ResourceNotFoundException Cache template not found.
	 * @throws Exception File error.
	 */
	function updateCacheFile ($context=null, $cache_template=null, $output_cache_file=null)
	{
		if ($cache_template===null) {
			$cache_template = static::$cache_template;
			if (!file_exists($cache_template)) {
				throw new ResourceNotFoundException("External link cache template not available at \"$cache_template\".");
			}
		}
		if ($output_cache_file===null) {
			$output_cache_file = static::$output_cache_file;
		}
		$cache_content = PageContent::loadTemplateContent($cache_template, $context);
		$f = fopen($output_cache_file, "w");
		fputs($f, $cache_content);
		fclose($f);
	}
}
