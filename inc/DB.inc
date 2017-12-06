//
// Datenbank-Bibliothek.
// $Id: DB.inc,v 1.46 2007/02/03 08:31:57 michael Exp $
//

<?php
class MysqlException extends Exception {
  public $backtrace;
  public function __construct($link, $message=false, $code=false) {
    if(!$message) {
      $this->message = mysqli_error($link);
    }
    if(!$code) {
      $this->code = mysqli_errno($link);
    }
    $this->backtrace = debug_backtrace();
  }
}

interface DB_Connection {
  public function prepare($query);
  public function execute($query);
}

interface DB_Statement {
  public function execute();
  public function bind_param($key, $value);
  public function fetch_row();
  public function fetch_assoc();
  public function fetchall_assoc();
}

class DB_Mysql implements DB_Connection {
  protected $user;
  protected $pass;
  protected $dbhost;
  protected $dbname;
  protected $dbh;

  public function __construct($user, $pass, $dbhost, $dbname) {
    $this->user = $user;
    $this->pass = $pass;
    $this->dbhost = $dbhost;
    $this->dbname = $dbname;
  }
  protected function connect() {
    $this->dbh = mysqli_connect($this->dbhost, $this->user, $this->pass);
    if(mysqli_connect_errno()) {
      throw new MysqlException($this->dbh, mysqli_connect_error());
    }
    if(!mysqli_select_db($this->dbh, $this->dbname)) {
      throw new MysqlException($this->dbh);
    }
  }
  protected function close() {
  	if(!mysqli_close($this->dbh)) {
      throw new MysqlException($this->dbh);
  	}
  }

  public function execute($query) {
    if(!$this->dbh) {
      $this->connect();
    }
    $ret = mysql_query($this->dbh, $query);
    if(!$ret) {
      throw new MysqlException($this->dbh);
    }
    else if(!is_resource($ret)) {
      return TRUE;
    } else {
      $stmt = new DB_MysqlStatement($this->dbh, $query);
      $stmt->result = $ret;
      return $stmt;
    }
  }
  public function prepare($query) {
    if(!$this->dbh) {
      $this->connect();
    }
    return new DB_MysqlStatement($this->dbh, $query);
  }
  public function get_insert_id() {
    return mysql_insert_id($this->dbh);
  }

}

class DB_MysqlStatement implements DB_Statement {
  public $result;
  public $binds;
  public $query;
  public $dbh;
  public function __construct($dbh, $query) {
    $this->query = $query;
    $this->dbh = $dbh;
    // if(!is_resource($dbh)) {
    //  throw new MysqlException($this->dbh, "Not a valid database connection");
    // }
  }
  public function bind_param($ph, $pv) {
    $this->binds[$ph] = $pv;
    return $this;
  }
  public function execute()
  {

    $query = $this->query;

    if(func_num_args() > 0) {

    	$binds = func_get_args();
    	foreach($binds as $index => $name) {
      		$this->binds[$index + 1] = $name;
    	}

    	$this->binds = array_reverse($this->binds, true);
    	foreach ($this->binds as $ph => $pv) { 
          if($pv!=null) {
            $query = str_replace(":$ph", "'".mysqli_real_escape_string($this->dbh, $pv)."'", $query);
          } else {
            $query = str_replace(":$ph", "NULL", $query);
          }
    	}
    }

    $this->result = mysqli_query($this->dbh, $query);
    if(!$this->result) {
      throw new MysqlException($this->dbh);
    }
    return $this;
  }
  public function fetch_row() {
    if(!$this->result) {
      throw new MysqlException($this->dbh, "Query not executed");
    }
    return mysqli_fetch_row($this->result);
  }
  public function fetch_assoc() {
    return mysqli_fetch_assoc($this->result);
  }
  public function fetchall_assoc() {
    $retval = array();
    while($row = $this->fetch_assoc()) {
      $retval[] = $row;
    }
    return $retval;
  }
  public function num_rows() {
  	return mysqli_num_rows($this->result);
  }
}

class DB_Result {
  protected $stmt;
  protected $result = array();
  private $rowIndex = 0;
  private $currIndex = 0;
  private $done = false;

  public function __construct(DB_Statement $stmt)
  {
    $this->stmt = $stmt;
  }
  public function first()
  {
    if(!$this->result) {
      $this->result[$this->rowIndex++] = $this->stmt->fetch_assoc();
    }
    $this->currIndex = 0;
    return $this;
  }
  public function last()
  {
    if(!$this->done) {
      array_push($this->result, $this->stmt->fetchall_assoc());
    }
    $this->done = true;
    $this->currIndex = $this->rowIndex = count($this->result) - 1;
    return $this;
  }
  public function next()
  {
    if($this->done) {
      return false;
    }
    $offset = $this->currIndex + 1;
    if(!$this->result[$offset]) {
      $row = $this->stmt->fetch_assoc();
      if(!$row) {
        $this->done = true;
        return false;
      }
      $this->result[$offset] = $row;
      ++$this->rowIndex;
      ++$this->currIndex;
      return $this;
    }
    else {
      ++$this->currIndex;
      return $this;
    }
  }
  public function prev()
  {
    if($this->currIndex == 0) {
      return false;
    }
    --$this->currIndex;
    return $this;
  }
  public function __get($value)
  {
    if(array_key_exists($value, $this->result[$this->currIndex])) {
      return $this->result[$this->currIndex][$value];
    }
  }
}

class DB_Mysql_Test extends DB_Mysql {
    protected $user   = "";
    protected $pass   = "";
    protected $dbhost = "localhost";
    protected $dbname = "";

    public function __construct() { }
}

class DB_Mysql_Test_Debug extends DB_Mysql_Test {
  protected $elapsedTime;
  public function execute($query) {
    // set timer;
    parent::execute($query);
    // end timer;
  }
  public function getElapsedTime() {
    return $this->$elapsedTime;
  }
}

class DB_Mysql_Prod extends DB_Mysql {
    protected $user   = "vein";
    protected $pass   = "vein";
    protected $dbhost = "localhost";
    protected $dbname = "vein";

    public function __construct() { }
}

class DB {
  private static $db_handle;
  public static function get_db() {
    if(is_null($db_handle)) {
        $db_handle = new DB_Mysql_Prod();
    }
    return($db_handle);
  }
}

?>