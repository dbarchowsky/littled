<?php
namespace Littled\App;


class LittledGlobals
{
	protected static $mysqlKeysPath = '';

	/** @var string Name of session variable use dto store CSRF tokens. */
	const CSRF_SESSION_KEY = 'csrfToken';

	/** @var string Name of request variable used to pass content type. */
	const CONTENT_TYPE_PARAM = 'tid';
	/** @var string Name of request variable used to pass CSRF tokens. */
	const CSRF_TOKEN_PARAM = 'csrf';
	/** @var string ID request variable name. */
	const ID_PARAM = 'id';

	/** @var string Request variable name holding record ids. */
	const P_ID = 'ID';
	/** @var string Request variable name to cancel operations. */
	const P_CANCEL = 'cancel';
	/** @var string Request variable name to commit operations. */
	const P_COMMIT = 'commit';
	/** @var string Request variable flag indicating that listings are being filtered. */
	const P_FILTER = 'filter';
	/** @var string Request variable containing status message. */
	const P_MESSAGE = 'msg';
	/** @var string Request variable name containing referring URLs. */
	const P_REFERER = 'ref';

	public static function getMySQLKeysPath()
	{
		return (static::$mysqlKeysPath);
	}

	public static function setMySQLKeysPath($path)
	{
		static::$mysqlKeysPath = $path;
	}
}