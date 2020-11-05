<?php

namespace AssignmentFour\Database;

class Connection
{
	private $connection;
	private $host = "mysql";
	private $username = "root";
	private $password = "rootpassword";
	private $database = "RedditDB";

	public function connect()
	{
		$this->connection = new \mysqli(
			$this->host,
			$this->username,
			$this->password,
			$this->database
		);

		if ($this->connection->connect_errno) {
			print "Problem connecting with the database.\n";
		}

		return $this->connection;
	}
}
