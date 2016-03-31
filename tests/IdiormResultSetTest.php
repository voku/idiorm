<?php

use idiorm\orm\IdiormResultSet;
use idiorm\orm\ORM;

class IdiormResultSetTest extends PHPUnit_Framework_TestCase
{

  public function setUp()
  {
    // Enable logging
    ORM::configure('logging', true);

    // Set up the dummy database connection
    $db = new MockPDO('sqlite::memory:');
    ORM::set_db($db);
  }

  public function tearDown()
  {
    ORM::reset_config();
    ORM::reset_db();
  }

  public function testGet()
  {
    $IdiormResultSet = new IdiormResultSet();
    self::assertInternalType('array', $IdiormResultSet->get_results());
  }

  public function testConstructor()
  {
    $result_set = array('item' => new stdClass);
    $IdiormResultSet = new IdiormResultSet($result_set);
    self::assertSame($IdiormResultSet->get_results(), $result_set);
  }

  public function testSetResultsAndGetResults()
  {
    $result_set = array('item' => new stdClass);
    $IdiormResultSet = new IdiormResultSet();
    $IdiormResultSet->set_results($result_set);
    self::assertSame($IdiormResultSet->get_results(), $result_set);
  }

  public function testAsArray()
  {
    $result_set = array('item' => new stdClass);
    $IdiormResultSet = new IdiormResultSet();
    $IdiormResultSet->set_results($result_set);
    self::assertSame($IdiormResultSet->as_array(), $result_set);

    // ---

    $result_set = array(
        'item'  => ORM::for_table('test')->create(array('foo' => 1, 'bar' => 2)),
        'item2' => ORM::for_table('test')->create(array('foo' => 3, 'bar' => 4)),
    );
    $IdiormResultSet = new IdiormResultSet($result_set);
    self::assertEquals(
        $IdiormResultSet->as_array(),
        array(
            array('foo' => 1, 'bar' => 2),
            array('foo' => 3, 'bar' => 4),
        )
    );
    self::assertEquals(
        $IdiormResultSet->as_array('foo'),
        array(
            array('foo' => 1),
            array('foo' => 3),
        )
    );
  }

  public function testCount()
  {
    $result_set = array('item' => new stdClass);
    $IdiormResultSet = new IdiormResultSet($result_set);
    self::assertSame($IdiormResultSet->count(), 1);
    self::assertSame(count($IdiormResultSet), 1);
  }

  public function testGetIterator()
  {
    $result_set = array('item' => new stdClass);
    $IdiormResultSet = new IdiormResultSet($result_set);
    self::assertInstanceOf('ArrayIterator', $IdiormResultSet->getIterator());
  }

  public function testForeach()
  {
    $result_set = array('item' => new stdClass);
    $IdiormResultSet = new IdiormResultSet($result_set);
    $return_array = array();
    foreach ($IdiormResultSet as $key => $record) {
      $return_array[$key] = $record;
    }
    self::assertSame($result_set, $return_array);
  }

  public function testCallingMethods()
  {
    $result_set = array('item' => ORM::for_table('test'), 'item2' => ORM::for_table('test'));
    $IdiormResultSet = new IdiormResultSet($result_set);
    /** @noinspection PhpUndefinedMethodInspection */
    $IdiormResultSet->set('field', 'value')->set('field2', 'value');

    foreach ($IdiormResultSet as $record) {
      self::assertTrue(isset($record->field));
      self::assertSame($record->field, 'value');

      self::assertTrue(isset($record->field2));
      self::assertSame($record->field2, 'value');
    }
  }

}
