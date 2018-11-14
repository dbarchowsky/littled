<?php
namespace Littled\PageContent\Serialized;


use Littled\Exception\ConfigurationUndefinedException;
use Littled\Exception\ConnectionException;
use Littled\Exception\ContentValidationException;
use Littled\Exception\InvalidQueryException;
use Littled\Exception\InvalidTypeException;
use Littled\Exception\NotImplementedException;
use Littled\Exception\RecordNotFoundException;
use Littled\Request\IntegerInput;

class SerializedContent extends SerializedContentUtils
{
	/** @var IntegerInput Record id. */
	public $id;
	/** @var boolean Flag to skip filling object values from input variables (GET or POST). */
	public $bypassCollectFromInput;

	/**
	 * Interface to retrieve table name associated with inherited classes.
	 * @throws NotImplementedException
	 */
	public static function TABLE_NAME()
	{
		throw new NotImplementedException('TABLE_NAME() not implemented in inherited class.');
	}

	/**
	 * SerializedContent constructor.
	 * @param integer[optional] $id Initial value to assign to the object's id property.
	 */
	function __construct($id=null)
	{
		parent::__construct();
		$this->id = new IntegerInput('id', 'id', false, $id);
		$this->bypassCollectFromInput = false;
	}

	/**
	 * Check if a column exists in a given database table in the content item's database table.
	 * @param string $column_name Name of the column to check for.
	 * @param string[optional] $table_name This parameter is ignored in this class's implementation of the routine.
	 * @return boolean True/false depending on if the column is found.
	 * @throws NotImplementedException Inherited classes haven't set table name value.
	 */
	public function columnExists($column_name, $table_name='')
	{
		return(parent::columnExists($column_name, $this->TABLE_NAME()));
	}

	/**
	 * Deletes the record from the database. Uses the value object's id property to look up the record.
	 * @return string Message indicating result of the deletion.
	 * @throws ConfigurationUndefinedException
	 * @throws ConnectionException
	 * @throws ContentValidationException
	 * @throws InvalidQueryException
	 * @throws NotImplementedException
	 */
	public function delete ( )
	{
		if ($this->id->value===null || $this->id->value<1) {
			throw new ContentValidationException("Id not provided.");
		}

		if (!$this->recordExists()) {
			return("The requested record could not be found. \n");
		}

		$query = "DEL"."ETE FROM `".$this->TABLE_NAME()."` WHERE `id` = {$this->id->value}";
		$this->query($query);
		return ("The record has been deleted. \n");
	}

	/**
	 * Create a SQL insert statement using the values of the object's input properties & execute the insert statement.
	 * @throws ConnectionException On connection error.
	 * @throws ConfigurationUndefinedException Database connection properties not set.
	 * @throws NotImplementedException Table name not specified in inherited class.
	 * @throws InvalidQueryException SQL error raised running insert query.
	 */
	protected function executeInsertQuery()
	{
		$fields = $this->formatDatabaseColumnList();

		/* build sql statement */
		$query = "INS"."ERT INTO `".$this->TABLE_NAME()."` (`".
			implode('`,`', array_keys($fields)).
			"`) VALUES (".
			implode(',', array_values($fields)).
			")";

		/* execute sql and store id value of the new record. */
		$this->query($query);
		$this->id->value = $this->retrieveInsertID();
	}

	/**
	 * Create a SQL update statement using the values of the object's input properties & execute the update statement.
	 * @throws ConnectionException On connection error.
	 * @throws ConfigurationUndefinedException Database connection properties not set.
	 * @throws InvalidQueryException SQL error raised running insert query.
	 * @throws NotImplementedException Table name not specified in inherited class.
	 * @throws RecordNotFoundException No record exists that matches the id value.
	 */
	protected function executeUpdateQuery()
	{
		$fields = $this->formatDatabaseColumnList();

		/* confirm that the record exists */
		if (!$this->recordExists()) {
			throw new RecordNotFoundException("Requested record not available for update.");
		}

		$fields_cb = function($key, $value) { return("`{$key}`={$value}"); };

		/* build and execute sql statement */
		$query = "UPDATE `".$this->TABLE_NAME()."` SET ".
			implode(',', array_map($fields_cb, array_keys($fields), $fields))." ".
			"WHERE id = {$this->id->value};";
		$this->query($query);
	}

	/**
	 * Attempts to determine which column in a table holds title or name values.
	 * @todo This routine exists for the benefit of the getRecordName() routine. If the switch that is in that routine
	 * @todo be implemented in inherited classes, then this routine is no longer necessary and can be removed.
	 * @return string Name of the column holding title or name values. Returns empty string if an identifier column couldn't be found.
	 * @throws NotImplementedException Inherited classes haven't set table name value.
	 */
	public function getNameColumnIdentifier()
	{
		switch(1) {
			case ($this->columnExists('name')):
				return ('name');
				break;
			case ($this->columnExists('title')):
				return('title');
				break;
			default:
				return('');
		}
	}

	/**
	 * Attempts to read the title or name from a record in the database and use
	 * its value to set the title or name property of the class instance. Uses the
	 * value of the internal TABLE_NAME() property to determine which table to search.
	 * @throws ConfigurationUndefinedException
	 * @throws ConnectionException
	 * @throws NotImplementedException
	 * @throws RecordNotFoundException
	 */
	function getRecordLabel()
	{
		$column = $this->getNameColumnIdentifier();

		$query = "SEL"."ECT `?` FROM `?` WHERE `id` = ?";
		$data = $this->mysqli()->fetchRecords($query, array($column, $this::TABLE_NAME(), $this->id->escapeSQL($this->mysqli)));
		if (count($data) < 1) {
			throw new RecordNotFoundException('Column value not found');
		}

		/** @todo Move this logic to the appropriate inherited classes. */
		switch (1) {
			case (property_exists($this, "name")):
				list($this->name->value) = $data[0];
				break;
			case (property_exists($this, "title")):
				list($this->title->value) = $data[0];
				break;
			default:
				break;
		}
	}

	/**
	 * Retrieves the name of the record represented by the provided id value.
	 * @param string $table Name of the table containing the records.
	 * @param int $id ID value of the record.
	 * @param string[optional] $field Column name containing the value to retrieve. Defaults to "name".
	 * @param string[optional] $id_field Column name containing the id value to retrieve. Defaults to "id".
	 * @return string|null Retrieved value.
	 */
	public function getTypeName($table, $id, $field="name", $id_field="id" )
	{
		if ($id===null || $id<1) {
			return(null);
		}

		$query = "SEL"."ECT `?` AS `result` FROM `?` WHERE `?` = ?";
		$data = $this->fetchRecords($query, array($field, $table, $id_field, $id));
		$ret_value = $data[0]->result;
		return($ret_value);
	}

	/**
	 * Indicates if any form data has been entered for the current instance of the object.
	 * @return boolean Returns true if editing an existing record, a title has been entered, or if any gallery images
	 * have been uploaded. Most likely should be overridden in derived classes.
	 */
	public function hasData( )
	{
		return ($this->id->value!==null);
	}

	/**
	 * Retrieves data from the database based on the internal properties of the
	 * class instance. Sets the values of the internal properties of the class
	 * instance using the database data.
	 * @throws ConfigurationUndefinedException
	 * @throws ConnectionException
	 * @throws ContentValidationException Record id not set.
	 * @throws NotImplementedException Table name not set.
	 * @throws InvalidTypeException Record id is not an instance of IntegerInput.
	 * @throws RecordNotFoundException Requested record not available.
	 */
	public function read ()
	{
		if ($this->id instanceof IntegerInput === false) {
			throw new InvalidTypeException("Record id not in expected format.");
		}
		if ($this->id->value===null || $this->id->value<0) {
			throw new ContentValidationException("Record id not set.");
		}

		$fields = $this->formatDatabaseColumnList();
		$query = "SELECT `".
			implode('`,`', array_keys($fields))."` ".
			"FROM `".$this->TABLE_NAME()."` ".
			"WHERE id = {$this->id->value}";
		try {
			$this->hydrateFromQuery($query);
		}
		catch(RecordNotFoundException $ex) {
			$error_msg = "The requested ".$this->TABLE_NAME()." record could not be found.";
			throw new RecordNotFoundException($error_msg);
		}
	}

	/**
	 * Retrieves a list of records from the database using $query. Converts each
	 * row in the result to an object of type $type. Stores the objects as an
	 * array in the object's property specified with $property.
	 * @param string $property Name of property to use to store list.
	 * @param string $type Object type to push onto the array.
	 * @param string $query SQL query to execute to retrieve list.
	 * @throws NotImplementedException Currently only stored procedures are supported.
	 * @throws InvalidTypeException $type does not represent a class derived from SerializedContent.
	 */
	public function readList( $property, $type, $query )
	{
		if (stripos($query, "call")===0) {
			$data = $this->fetchRecords($query);
		}
		else {
			throw new NotImplementedException("Unsupported query type for retrieving record list.");
		}

		$this->$property = array();
		foreach($data as $row) {
			$obj = new $type;
			if (!($obj instanceof SerializedContent)) {
				throw new InvalidTypeException("Cannot store records in object provided.");
			}
			$obj->fill($row);
			array_push($this->$property, $obj);
		}
	}

	/**
	 * Commits the values stored in the class instance's properties to the database.
	 * @throws ConfigurationUndefinedException
	 * @throws ConnectionException Unable to establish database connection.
	 * @throws ContentValidationException Record contains invalid data.
	 * @throws InvalidQueryException Error executing query.
	 * @throws NotImplementedException Table name value not set in inherited class.
	 * @throws RecordNotFoundException No record exists that matches the id value.
	 */
	public function save ()
	{
		if (!$this->hasData()) {
			throw new ContentValidationException("Record has no data to save.");
		}
		if (is_numeric($this->id->value)) {
			$this->executeUpdateQuery();
		}
		else {
			$this->executeInsertQuery();
		}
	}

	/**
	 * Confirm that a record with id value matching the current id value of the object currently exists in the database.
	 * @return bool True/False depending on if a matching record is found.
	 * @throws ConfigurationUndefinedException
	 * @throws ConnectionException
	 * @throws NotImplementedException
	 */
	public function recordExists()
	{
		if ($this->id->value===null || $this->id->value==='' || $this->id->value < 1) {
			return (false);
		}

		$query = "SEL"."ECT EXISTS(SELECT 1 FROM `?` WHERE `id` = ?) AS `record_exists`";
		$data = $this->mysqli()->fetchRecords($query, array($this::TABLE_NAME(), $this->id->escapeSQL($this->mysqli)));
		return ((int)("0".$data[0]->record_exists) === 1);
	}

	/**
	 * Tests for a valid parent record id. Throws ContentValidationException if the property value isn't current set.
	 * @throws ContentValidationException
	 */
	protected function testForParentID()
	{
		if ($this->id->value === null || $this->id->value < 0) {
			throw new ContentValidationException("Could not perform operation. A parent record was not provided.");
		}
	}
}