<?php

namespace src;

use Exception;
use mysqli;
use mysqli_result;

class DatabaseSingleton
{
    private static $instance = null; # empty instance by default
    private $connection; # connection of the instance
    private static $host = "host.docker.internal";
    private static $db = "t4api";
    private static $user = "t4api";

    private static $password = "t4api1234";
    private static $port = 10003;

    /*
     * This constructor creates a DB object and connects to the database, the instance is saved in the connection variable
     * */
    private function __construct() {
        $this->connection = new mysqli("p:".self::$host, self::$user, self::$password, self::$db, self::$port);
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        $this->connection->select_db(self::$db);
    }

    /**
     * @throws Exception
     * prevent the instance from being cloned, preventing duplication of the Singleton object
     */
    private function __clone() {
        throw new Exception("Cannot clone a singleton.");
    }

    /**
     * Creates a new instance if it does not exist, otherwise returns the existing instance
     * @throws Exception
     * @return DatabaseSingleton
     */
    public static function getInstance(): DatabaseSingleton
    {
        if (self::$instance == null) {
            self::$instance = new DatabaseSingleton();
        }
        return self::$instance;
    }

    /**
     * Executes a raw query with defined parameters, always prepared.
     * @return mysqli_result|bool
     */
    public function perform_query($rawquery, $params): mysqli_result|bool
    {
        return $this->connection->execute_query($rawquery,$params);
    }

    public function get_last_inserted_id(): int
    {
        return $this->connection->insert_id;
    }
}