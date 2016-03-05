<?php
use Littled\Validation\Validation;

class ValidationTest extends PHPUnit_Framework_TestCase
{
	public function testParseNumeric()
	{
		$int_overflow = (PHP_INT_MAX+1);

		$this->assertEquals(Littled\Validation\Validation::parseNumeric("1"), 1, "\"1\" returns numeric value.");
		$this->assertEquals(Littled\Validation\Validation::parseNumeric("0"), 0, "\"0\" returns numeric value.");
		$this->assertEquals(Littled\Validation\Validation::parseNumeric("-1"), -1);
		$this->assertEquals(Littled\Validation\Validation::parseNumeric("5"), 5);
		$this->assertEquals(Littled\Validation\Validation::parseNumeric("".PHP_INT_MAX), PHP_INT_MAX, "parseNumeric() with largest possible integer value");
		$this->assertEquals(Littled\Validation\Validation::parseNumeric("".(PHP_INT_MAX+1)), $int_overflow, "parseNumeric() with value overflowing int max value");
		$this->assertEquals(Littled\Validation\Validation::parseNumeric("0.01"), 0.01);
		$this->assertEquals(Littled\Validation\Validation::parseNumeric("4.5"), 4.5);
		$this->assertNull(Littled\Validation\Validation::parseNumeric("zero"));
		$this->assertNull(Littled\Validation\Validation::parseNumeric("j01"));
		$this->assertNull(Littled\Validation\Validation::parseNumeric("01jx"));
		$this->assertNull(Littled\Validation\Validation::parseNumeric("true"));
		$this->assertNull(Littled\Validation\Validation::parseNumeric("false"));
		$this->assertNull(Littled\Validation\Validation::parseNumeric(true));
		$this->assertNull(Littled\Validation\Validation::parseNumeric(false));
	}
	
	public function testIsInteger()
	{
		$this->assertTrue(Validation::isInteger(1));
		$this->assertTrue(Validation::isInteger(0));
		$this->assertTrue(Validation::isInteger(-1));
		$this->assertTrue(Validation::isInteger("1"));
		$this->assertTrue(Validation::isInteger("0"));
		$this->assertTrue(Validation::isInteger("-1"));
		$this->assertFalse(Validation::isInteger("-"));
		$this->assertFalse(Validation::isInteger("true"));
		$this->assertFalse(Validation::isInteger("false"));
		$this->assertFalse(Validation::isInteger(true));
		$this->assertFalse(Validation::isInteger(false));
		$this->assertFalse(Validation::isInteger(4.5));
		$this->assertFalse(Validation::isInteger('4.5'));
		$this->assertFalse(Validation::isInteger(null));
	}

	public function testParseInteger()
	{
		$this->assertEquals(Validation::parseInteger(1), 1);
		$this->assertEquals(Validation::parseInteger(0), 0);
		$this->assertEquals(Validation::parseInteger(-1), -1);
		$this->assertEquals(Validation::parseInteger("1"), 1);
		$this->assertEquals(Validation::parseInteger("0"), 0);
		$this->assertEquals(Validation::parseInteger("-1"), -1);
		$this->assertNull(Validation::parseInteger("-"));
		$this->assertNull(Validation::parseInteger("true"));
		$this->assertNull(Validation::parseInteger("false"));
		$this->assertNull(Validation::parseInteger(true));
		$this->assertNull(Validation::parseInteger(false));
		$this->assertNull(Validation::parseInteger(4.5));
		$this->assertNull(Validation::parseInteger('4.5'));
		$this->assertNull(Validation::parseInteger(null));
	}
}