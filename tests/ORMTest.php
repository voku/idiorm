<?php

use idiorm\orm\ORM;

/**
 * Class ORMTest
 */
class ORMTest extends PHPUnit_Framework_TestCase
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

  public function testStaticAtrributes()
  {
    self::assertSame(0, ORM::CONDITION_FRAGMENT);
    self::assertSame(1, ORM::CONDITION_VALUES);
  }

  public function testForTable()
  {
    $result = ORM::for_table('test');
    self::assertInstanceOf('idiorm\orm\ORM', $result);
  }

  public function testCreate()
  {
    $model = ORM::for_table('test')->create();
    self::assertInstanceOf('idiorm\orm\ORM', $model);
    self::assertTrue($model->is_new());
  }

  public function testIsNew()
  {
    $model = ORM::for_table('test')->create();
    self::assertTrue($model->is_new());

    $model = ORM::for_table('test')->create(array('test' => 'test'));
    self::assertTrue($model->is_new());
  }

  public function testIsDirty()
  {
    $model = ORM::for_table('test')->create();
    self::assertFalse($model->is_dirty('test'));

    $model = ORM::for_table('test')->create(array('test' => 'test'));
    self::assertTrue($model->is_dirty('test'));

    $model = ORM::forTable('test')->create(array('test' => null));
    self::assertTrue($model->isDirty('test'));

    $model->test = null;
    self::assertTrue($model->is_dirty('test'));

    $model->test = '';
    self::assertTrue($model->is_dirty('test'));
  }

  public function testArrayAccess()
  {
    $value = 'test';
    $model = ORM::for_table('test')->create();
    $model['test'] = $value;
    self::assertTrue(isset($model['test']));
    self::assertSame($model['test'], $value);
    unset($model['test']);
    self::assertFalse(isset($model['test']));
  }

  public function testFindResultSet()
  {
    $result_set = ORM::for_table('test')->find_result_set();
    self::assertInstanceOf('idiorm\orm\IdiormResultSet', $result_set);
    self::assertSame(count($result_set), 5);
  }

  public function testFindResultSetByDefault()
  {
    ORM::configure('return_result_sets', true);

    $result_set = ORM::for_table('test')->find_many();
    self::assertInstanceOf('idiorm\orm\IdiormResultSet', $result_set);
    self::assertSame(count($result_set), 5);

    ORM::configure('return_result_sets', false);

    $result_set = ORM::for_table('test')->find_many();
    self::assertInternalType('array', $result_set);
    self::assertSame(count($result_set), 5);
  }

  public function testGetLastPdoStatement()
  {
    ORM::for_table('widget')->where('name', 'Fred')->find_one();
    $statement = ORM::get_last_statement();
    self::assertInstanceOf('MockPDOStatement', $statement);
  }

  /**
   * @expectedException idiorm\orm\IdiormMethodMissingException
   */
  public function testInvalidORMFunctionCallShouldCreateException()
  {
    $orm = ORM::for_table('test');
    /** @noinspection PhpUndefinedMethodInspection */
    $orm->invalidFunctionCall();
  }

  /**
   * @expectedException idiorm\orm\IdiormMethodMissingException
   */
  public function testInvalidResultsSetFunctionCallShouldCreateException()
  {
    $resultSet = ORM::for_table('test')->find_result_set();
    /** @noinspection PhpUndefinedMethodInspection */
    $resultSet->invalidFunctionCall();
  }

  /**
   * These next two tests are needed because if you have select()ed some fields,
   * but not the primary key, then the primary key is not available for the
   * update/delete query - see issue #203.
   * We need to change the primary key here to something other than `id`
   * becuase MockPDOStatement->fetch() always returns an id.
   */
  public function testUpdateNullPrimaryKey()
  {
    try {
      $widget = ORM::for_table('widget')
                   ->use_id_column('primary')
                   ->select('foo')
                   ->where('primary', 1)
                   ->find_one();

      $widget->foo = 'bar';
      $widget->save();

      throw new Exception('Test did not throw expected exception');
    } catch (Exception $e) {
      self::assertSame($e->getMessage(), 'Primary key ID missing from row or is null');
    }
  }

  public function testDeleteNullPrimaryKey()
  {
    try {
      $widget = ORM::for_table('widget')
                   ->use_id_column('primary')
                   ->select('foo')
                   ->where('primary', 1)
                   ->find_one();

      $widget->delete();

      throw new Exception('Test did not throw expected exception');
    } catch (Exception $e) {
      self::assertSame($e->getMessage(), 'Primary key ID missing from row or is null');
    }
  }

  public function testNullPrimaryKey()
  {
    try {
      $widget = ORM::for_table('widget')
                   ->use_id_column('primary')
                   ->select('foo')
                   ->where('primary', 1)
                   ->find_one();

      $widget->id(true);

      throw new Exception('Test did not throw expected exception');
    } catch (Exception $e) {
      self::assertSame($e->getMessage(), 'Primary key ID missing from row or is null');
    }
  }

  public function testNullPrimaryKeyPart()
  {
    try {
      $widget = ORM::for_table('widget')
                   ->use_id_column(array('id', 'primary'))
                   ->select('foo')
                   ->where('id', 1)
                   ->where('primary', 1)
                   ->find_one();

      $widget->id(true);

      throw new Exception('Test did not throw expected exception');
    } catch (Exception $e) {
      self::assertSame($e->getMessage(), 'Primary key ID contains null value(s)');
    }
  }

  public function testAsArray()
  {
    $model = ORM::for_table('test')->create(array('foo' => 1, 'bar' => 2));
    self::assertSame($model->as_array(), array('foo' => 1, 'bar' => 2));
    self::assertSame($model->as_array('foo'), array('foo' => 1));

    $model = ORM::for_table('test')->create(array('test' => 'test'));
    self::assertSame($model->as_array(), array('test' => 'test'));
  }

  public function testAsJson()
  {
    $model = ORM::for_table('test')->create(array('test' => 'test'));
    self::assertSame($model->as_json(), '{"test":"test"}');
  }
}
