<?php

  /***************************************
   * http://www.program-o.com
   * Program-O
   * Version: 2.4.2
   * Build: 1402028162
   * FILE: DB.php
   * AUTHOR: Elizabeth Perreau and Dave Morton
   * DATE: 5/28/2016 - 8:02 PM
   * DETAILS: ${DESCRIPTION}
   ***************************************/
  class DB {

    protected $credentials;
    private $dbh;

    /**
     * DB constructor.
     */
    public function __construct($credentials) {
      file_put_contents(LOG_PATH . 'credentials.txt', print_r($credentials, true));
      extract($credentials);
      $dsn = "mysql:dbname=$dbName;host=$dbHost";
      try {
        $dbh = new PDO($dsn, $dbUser, $dbPass);
        $dbh->setAttribute(PDO :: ATTR_ERRMODE, PDO :: ERRMODE_EXCEPTION);
        $dbh->setAttribute(PDO :: MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $dbh->setAttribute(PDO :: ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->dbh = $dbh;
      }
      catch (PDOException $e) {
        # simple error handling - replace with something that won't break the output when used in a production environment
        $this->runDebug('Connection failed: ' . $e->getMessage(),'badConnection.txt', true);
      }
    }

    /**
     * Function runDebug
     *
     * @param $message
     * @param string $filename
     * @param bool   $append
     * @return void
     */
    public function runDebug($message, $filename = '', $append = false) {
      $append = ($append) ? FILE_APPEND : $append;
      if (empty($filename)) {
        $filename = 'debug.txt';
        $append = FILE_APPEND;
      }
      file_put_contents(LOG_PATH . $filename, "$message\n", $append);
    }

    /**
     * Function db_fetch_all
     *
     * * @param $sql
     * @param null   $params
     * @param string $file
     * @param string $function
     * @param string $line
     * @return array|bool|string
     */
    public function db_fetch_all($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown') {
      $dbh = $this->dbh;
      try {
        $sth = $dbh->prepare($sql);
        ($params === null) ? $sth->execute() : $sth->execute($params);
        $out = $sth->fetchAll();
        if (count($out) == 0) {
          $out = 'Empty Result set!';
          ob_start();
          $sth->debugDumpParams();
          $sqlDebug = ob_get_contents();
          ob_end_clean();
          $this->runDebug("Empty result set. SQL = \n$sqlDebug\n", 'badSQL.txt');
        }
        return $out;
      }
      catch (Exception $e) {
        $this->runDebug("bad SQL encountered in file $file, line #$line. SQL:\n$sql\n", 'dbErrors.txt', true);
        $pdoError = print_r($dbh->errorInfo(), true);
        $psError  = print_r($sth->errorInfo(), true) . "\nSQL = $sql\n";
        $this->runDebug("OOPS? error in file $file, function $function, line $line\npdoError = $pdoError\npsError = $psError\nSQL = $sql", 'dbErrors.txt', true);
        return false;
      }
    }

    /**
     * Function db_fetch_row
     *
     * * @param $sql
     * @param null   $params
     * @param string $file
     * @param string $function
     * @param string $line
     * @return bool|mixed
     */
    public function db_fetch_row($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown') {
      $dbh = $this->dbh;
      try {
        $sth = $dbh->prepare($sql);
        ($params === null) ? $sth->execute() : $sth->execute($params);
        $out = $sth->fetch();
        return $out;
      }
      catch (Exception $e) {
        $this->runDebug("bad SQL encountered in file $file, line #$line. SQL:\n$sql\n", 'dbErrors.txt', true);
        $pdoError = print_r($dbh->errorInfo(), true);
        $psError  = print_r($sth->errorInfo(), true);
        $this->runDebug("OOPS? error in file $file, function $function, line $line\npdoError = $pdoError\npsError = $psError\nSQL = $sql", 'dbErrors.txt', true);
        return false;
      }
    }
    public function db_write($sql, $params = null, $multi = false, $file = 'unknown', $function = 'unknown', $line = 'unknown') {
      $dbh = $this->dbh;
      try {
        $sth = $dbh->prepare($sql);
        switch (true) {
          case ($params === null):
            $sth->execute();
          break;
          case ($multi === true):
            foreach ($params as $row) {
              $sth->execute($row);
            }
          break;
          default:
            $sth->execute($params);
        }
        return $sth->rowCount();
      }
      catch (Exception $e) {
        $pdoError = print_r($dbh->errorInfo(), true);
        $psError  = print_r($sth->errorInfo(), true);
        error_log("OOPS? Bad SQL encountered in file $file, line #$line. SQL:\n$sql\nPDO Error:\n$pdoError\nSTH Error:\n$psError\nEsception Message:\n" . $e->getMessage(), 3,'logs/db_write.txt');
        return false;
      }
    }
  }