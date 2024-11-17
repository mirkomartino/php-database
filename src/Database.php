<?php
namespace Core;
/**
 *Classe per la gestione del database. La configurazione viene prelevata
 *dal file config.inc presente nella root.
 *
 * @version 2.1
 * @final 2017-01-23 18:36
 * aggiunto MSSQL di php lib php-mssql
 *
 */
class Database
{
    
    public $dbType;
    public $dbHost;
    public $dbUser;
    public $dbPass;
    public $dbName;
    public $dbMethod;

    public static $conn;
    public $query;

    public $ErrorID;
    public $ErrorDS;

    public $ErrorDB_Number;
    public $ErrorDB_DS;
    public $ErrorDB_SQL;

    public function __construct($pDbType = DS_DBTYPE){
        if ( defined('DS_DBMETHOD') ){
            $DS_DBMETHOD = DS_DBMETHOD;
        }else{
            $DS_DBMETHOD = '_HOST_';
        }
        $this->dbType = $pDbType;
        $this->dbHost = DS_DBHOST;
        $this->dbUser = DS_DBUSER;
        $this->dbPass = DS_DBPASS;
        $this->dbName = DS_DBNAME;
        $this->dbMethod = $DS_DBMETHOD;
        $this->query = new Query($this->dbType);

        $this->ErrorID = 0;
        $this->ErrorDS = '';
    }
    

    function connect($pDatabase = ""){
        $vDatabase = ($pDatabase) ? $pDatabase : $this->dbName;
        $this->query->dbType = $this->dbType;
        //se non in localhost forzo comuinque ad _HOST_
        if($this->dbHost!='127.0.0.1' && strtolower($this->dbHost)!='localhost'){
            $this->dbMethod = '_HOST_';
        }

        if($this->dbType=='MYSQLI') {
            // Mi connetto a MySQLI
            if ($this->dbMethod=='_SOCKET_'){
                Database::$conn = mysqli_connect('', $this->dbUser, $this->dbPass, $vDatabase, 0, '/var/run/mysqld/mysqld.sock');
            }else{
                Database::$conn = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, $vDatabase);
            }

            if (!defined('_CHARSET'))
                define("_CHARSET", "UTF-8");

            if (_CHARSET == 'UTF-8') {
                mysqli_query(Database::$conn,"set names 'utf8'");
            }
            if(mysqli_connect_errno()) {
                $this->ErrorID = 123;
                $this->ErrorDS = mysqli_connect_error();
                return(false);
            }else{
                return(true);
            }
        }

    }

    /**
     *Disconnessione dal database
     *
     */
    function disconnect (){
        return(true);
    }
    function newdisconnect (){
        if($this->dbType=='MYSQLI') {
            // Chiudo le connessione
            if (@!mysqli_close(Database::$conn)) {
                return(false);
            } else {
                return(true);
            }
        }
    }
    
}

?>