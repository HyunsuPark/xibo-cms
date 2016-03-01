<?php
/*
 * Xibo - Digital Signage - http://www.xibo.org.uk
 * Copyright (C) 2006-2015 Daniel Garner
 *
 * This file (PDOConnect.php) is part of Xibo.
 *
 * Xibo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version. 
 *
 * Xibo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Xibo.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Xibo\Storage;

use Xibo\Helper\Config;
use Xibo\Helper\Log;

/**
 * Class PDOConnect
 * Manages global connection state and the creation of connections
 * @package Xibo\Storage
 */
class PDOConnect implements StorageInterface
{
    /**
     * @var \PDO The connection
     */
	private $conn = NULL;

    /**
     * Logger
     * @var Log
     */
    private $log;

    /**
     * Count of Connections
     * @var int
     */
    private static $countConnections = 0;

    /**
     * Count of Selects
     * @var int
     */
    private static $countSelects = 0;

    /**
     * Count of Inserts
     * @var int
     */
    private static $countInserts = 0;

    /**
     * Count of Updates
     * @var int
     */
    private static $countUpdates = 0;

    /**
     * PDOConnect constructor.
     * @param Log $logger
     */
	public function __construct($logger = null)
    {
        $this->log = $logger;

        // Create a new connection
        $this->conn = PDOConnect::newConnection();
    }

    /**
     * Closes the stored connection
     */
    public function close()
    {
        if ($this->conn) {
            $this->conn = null;
        }
    }

    /**
     * Create a DSN from the host/db name
     * @param string $host
     * @param string[Optional] $name
     * @return string
     */
    private static function createDsn($host, $name = null)
    {
        if (strstr($host, ':')) {
            $hostParts = explode(':', $host);
            $dsn = 'mysql:host=' . $hostParts[0] . ';port=' . $hostParts[1] . ';';
        }
        else {
            $dsn = 'mysql:host=' . $host . ';';
        }

        if ($name != null)
            $dsn .= 'dbname=' . $name . ';';

        return $dsn;
    }

    /**
     * Open a new connection using the stored details
     * @return \PDO
     */
	public static function newConnection()
    {
        $dsn = PDOConnect::createDsn(Config::$dbConfig['host'], Config::$dbConfig['name']);

		// Open the connection and set the error mode
		$conn = new \PDO($dsn, Config::$dbConfig['user'], Config::$dbConfig['password']);
		$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$conn->query("SET NAMES 'utf8'");

        self::$countConnections++;

		return $conn;
	}

    /**
     * Open a connection with the specified details
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string[Optional] $name
     * @return \PDO
     */
	public function connect($host, $user, $pass, $name = null)
    {
		if (!$this->conn) {
			$this->close();
		}

        $dsn = PDOConnect::createDsn($host, $name);

        // Open the connection and set the error mode
		$this->conn = new \PDO($dsn, $user, $pass);
		$this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$this->conn->query("SET NAMES 'utf8'");
		

		return $this->conn;
	}

    /**
     * Get the Raw Connection
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Check to see if the query returns records
     * @param string $sql
     * @param array[mixed] $params
     * @return bool
     */
    public function exists($sql, $params)
    {
        if ($this->log != null)
            $this->log->sql($sql, $params);

        $sth = $this->conn->prepare($sql);
        $sth->execute($params);

        self::$countSelects++;

        if ($sth->fetch())
            return true;
        else
            return false;
    }
    
    /**
     * Run Insert SQL
     * @param string $sql
     * @param array $params
     * @param \PDO[Optional] $dbh
     * @return int
     * @throws \PDOException
     */
    public function insert($sql, $params)
	{
        if ($this->log != null)
            $this->log->sql($sql, $params);

        if (!$this->conn->inTransaction())
            $this->conn->beginTransaction();

        $sth = $this->conn->prepare($sql);

        $sth->execute($params);

        self::$countInserts++;

        return intval($this->conn->lastInsertId());
    }

	/**
	 * Run Update SQL
	 * @param string $sql
	 * @param array $params
     * @param \PDO[Optional] $dbh
	 * @throws \PDOException
	 */
	public function update($sql, $params)
	{
        if ($this->log != null)
            $this->log->sql($sql, $params);

        if (!$this->conn->inTransaction())
            $this->conn->beginTransaction();

        $sth = $this->conn->prepare($sql);

        $sth->execute($params);

        self::$countUpdates++;
	}

	/**
	 * Run Select SQL
	 * @param $sql
	 * @param $params
	 * @return array
	 * @throws \PDOException
	 */
	public function select($sql, $params)
	{
        if ($this->log != null)
            $this->log->sql($sql, $params);

        $sth = $this->conn->prepare($sql);

        $sth->execute($params);

        self::$countSelects++;

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}

    /**
     * Commit if necessary
     */
    public function commitIfNecessary()
    {
        if ($this->conn->inTransaction())
            $this->conn->commit();
    }

	/**
     * Set the TimeZone for this connection
	 * @param \PDO $connection
	 * @param string $timeZone e.g. -8:00
	 */
	public function setTimeZone($timeZone, $connection = null)
	{
        $connection->query('SET time_zone = \'' . $timeZone . '\';');

        self::$countSelects++;
	}

    /**
     * PDO stats
     * @return array
     */
    public static function stats()
    {
        return [
            'connections' => self::$countConnections,
            'selects' => self::$countSelects,
            'inserts' => self::$countInserts,
            'updates' => self::$countUpdates
        ];
    }
}