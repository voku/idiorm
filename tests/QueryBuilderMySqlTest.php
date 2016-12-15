<?php

use idiorm\orm\ORM;

/**
 * Class QueryBuilderMySqlTest
 */
class QueryBuilderMySqlTest extends PHPUnit_Framework_TestCase
{

  protected $tableName = 'test_page';

  public function setUp()
  {
    // Enable logging
    ORM::configure('logging', true);

    // Set DB
    ORM::configure('mysql:host=localhost;dbname=mysql_test;port=3306;charset=utf8');
    ORM::configure('username', 'root');
    ORM::configure('password', '');
  }

  public function tearDown()
  {
    ORM::reset_config();
    ORM::reset_db();
  }

  public function testFindOne()
  {
    ORM::for_table($this->tableName)->find_one();
    $expected = 'SELECT * FROM `' . $this->tableName . '` LIMIT 1';
    self::assertSame($expected, ORM::get_last_query());
  }

  public function testLimit()
  {
    ORM::for_table($this->tableName)->limit(5)->find_many();
    $expected = 'SELECT * FROM `' . $this->tableName . '` LIMIT 5';
    self::assertSame($expected, ORM::get_last_query());
  }

  public function testTransactionCommit()
  {
    //
    // prepare the test
    //

    ORM::configure('id_column', 'page_id');

    // get the last id
    ORM::for_table($this->tableName)->create(
        array(
            'page_template' => 'tpl_new_中',
            'page_type' => 'lall'
        )
    )->save();
    $lastData = ORM::for_table($this->tableName)->order_by_desc('page_id')->find_one();
    $lastDataId = $lastData['page_id'];

    // --------------

    ORM::get_db()->beginTransaction();

    $orm = ORM::for_table($this->tableName)->create(
        array(
            'page_template' => 'tpl_new_中',
            'page_type' => 'lall'
        )
    );
    self::assertInstanceOf('idiorm\orm\ORM', $orm);

    $success =$orm->save();
    self::assertSame(true, $success);

    ORM::get_db()->commit();

    $newPageId = $orm->id();
    self::assertSame((string)($lastDataId + 1), $newPageId);

    $newData = ORM::for_table($this->tableName)->find_one($newPageId);
    self::assertSame($newPageId, $newData['page_id']);
    self::assertSame('tpl_new_中', $newData['page_template']);
    self::assertSame('lall', $newData['page_type']);
  }

  public function testTransactionRollBack()
  {
    //
    // prepare the test
    //

    ORM::configure('id_column', 'page_id');

    // get the last id
    ORM::for_table($this->tableName)->create(
        array(
            'page_template' => 'tpl_new_中',
            'page_type' => 'lall'
        )
    )->save();
    $lastData = ORM::for_table($this->tableName)->order_by_desc('page_id')->find_one();
    $lastDataId = $lastData['page_id'];

    // --------------

    ORM::get_db()->beginTransaction();

    $orm = ORM::for_table($this->tableName)->create(
        array(
            'page_template' => 'tpl_new_中',
            'page_type' => 'lall'
        )
    );
    self::assertInstanceOf('idiorm\orm\ORM', $orm);

    $success =$orm->save();
    self::assertSame(true, $success);

    ORM::get_db()->rollBack(); // INFO: here we revert the changes ...

    $newPageId = $orm->id();
    self::assertSame((string)($lastDataId + 1), $newPageId);

    $newData = ORM::for_table($this->tableName)->find_one($newPageId);
    self::assertSame(null, $newData['page_id']);
    self::assertSame(null, $newData['page_template']);
    self::assertSame(null, $newData['page_type']);
  }
}
