<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';

/**
 *
 * Mock version of the PDOStatement class.
 *
 */
class MockPDOStatement extends PDOStatement
{
  private $current_row = 0;
  private $statement   = null;
  private $bindParams  = array();

  /**
   * Store the statement that gets passed to the constructor
   *
   * @param $statement
   */
  public function __construct($statement)
  {
    $this->statement = $statement;
  }

  /**
   * Check that the array
   *
   * @param null|array $params
   *
   * @throws Exception
   */
  public function execute($params = null)
  {
    $m = array();

    if (is_null($params)) {
      $params = $this->bindParams;
    }

    if (preg_match_all('/"[^"\\\\]*(?:\\?)[^"\\\\]*"|\'[^\'\\\\]*(?:\\?)[^\'\\\\]*\'|(\\?)/', $this->statement, $m, PREG_SET_ORDER)) {

      $count = count($m);
      for ($v = 0; $v < $count; $v++) {
        if (count($m[$v]) == 1) {
          unset($m[$v]);
        }
      }

      $count = count($m);
      for ($i = 0; $i < $count; $i++) {
        if (!isset($params[$i])) {
          ob_start();
          var_dump($m, $params);
          $output = ob_get_clean();
          throw new Exception('Incorrect parameter count. Expected ' . $count . ' got ' . count($params) . ".\n" . $this->statement . "\n" . $output);
        }
      }

    }
  }

  /**
   * Add data to arrays
   */
  public function bindParam($index, $value, $type)
  {
    // Do check on index, and type
    if (!is_int($index)) {
      throw new Exception('Incorrect parameter type. Expected $index to be an integer.');
    }

    if (!is_int($type) || ($type != PDO::PARAM_STR && $type != PDO::PARAM_NULL && $type != PDO::PARAM_BOOL && $type != PDO::PARAM_INT)) {
      throw new Exception('Incorrect parameter type. Expected $type to be an integer.');
    }

    // Add param to array
    $this->bindParams[$index - 1] = $value;
  }

  /**
   * Return some dummy data
   *
   * @param int|null $fetch_style
   * @param int      $cursor_orientation
   * @param int      $cursor_offset
   *
   * @return array|bool
   */
  public function fetch($fetch_style = PDO::FETCH_BOTH, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
  {
    if ($this->current_row == 5) {
      return false;
    } else {
      return array('name' => 'Fred', 'age' => 10, 'id' => ++$this->current_row);
    }
  }
}

/**
 * Another mock PDOStatement class, used for testing multiple connections
 */
class MockDifferentPDOStatement extends MockPDOStatement
{
}

/**
 *
 * Mock database class implementing a subset
 * of the PDO API.
 *
 */
class MockPDO extends PDO
{
  public $last_query = '';

  /**
   * Return a dummy PDO statement
   *
   * @param string $statement
   * @param array  $driver_options
   *
   * @return MockPDOStatement
   */
  public function prepare($statement, $driver_options = array())
  {
    $this->last_query = new MockPDOStatement($statement);

    return $this->last_query;
  }
}

/**
 * A different mock database class, for testing multiple connections
 * Mock database class implementing a subset of the PDO API.
 */
class MockDifferentPDO extends MockPDO
{

  /**
   * Return a dummy PDO statement
   *
   * @param string $statement
   * @param array  $driver_options
   *
   * @return MockDifferentPDOStatement
   */
  public function prepare($statement, $driver_options = array())
  {
    $this->last_query = new MockDifferentPDOStatement($statement);

    return $this->last_query;
  }
}

class MockMsSqlPDO extends MockPDO
{

  public $fake_driver = 'mssql';

  /**
   * If we are asking for the name of the driver, check if a fake one
   * has been set.
   *
   * @param int $attribute
   *
   * @return mixed|string
   */
  public function getAttribute($attribute)
  {
    if ($attribute == self::ATTR_DRIVER_NAME) {
      if (!is_null($this->fake_driver)) {
        return $this->fake_driver;
      }
    }

    return parent::getAttribute($attribute);
  }

}
