<?php

/*
* Database Class
*
* Use PDO MySQL to connect and query database
*
* @TODO Return null instead of false. 
*
*/

class Database {
	private $host;
	private $database;
	private $user;
	private $password;
	private $connection;

	/*
	* Construct
	*
	* Set properties
	*/
	public function __construct(string $host, string $database, string $user, string $password) {
		$this->host = $host;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
	}

	/*
	* Connect
	*
	* Connect to database, select database, set error reporting and set charset
	*/
	public function connect() {
		try {
			$connection = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password);
		}
		catch(PDOException $error) {
			die ($error->getMessage());
		}

		$connection->exec("set names utf8");
		// Set error reporting
		$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		// Set emulate preparies to false so queries return correct datatype for values instead of strings
		$connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

		$this->connection = $connection;
	}

	/*
	* Disconnect
	*
	* Close connection to database
	*/
	public function disconnect() {
		$this->connection = NULL;
	}

	/*
	* Build_select_query
	*
	* Build query for select type
	*
	* @param string $table
	* @param string $data
	* @param array $where
	* @param array $ordering
	* @param int $limit
	*
	* @return string
	*/
	private function build_select_query(string $table, string $data, array $where = null, array $ordering = null, int $limit = null) {
		$query = 'SELECT ' . $data . ' FROM ' . $table;

		if ($where != null) {
			$where_params = $this->crate_placeholders($where);

			$query .= ' WHERE ' . implode(' AND ', $where_params);
		}

		if ($ordering != null) {
			$ordering_array = array();
			foreach ($ordering as $key => $direction) {
				$ordering_array[] = $key . ' ' . $direction;
			}
			$query .= ' ORDER BY ' . implode(', ', $ordering_array);
		}

		if ($limit != null) {
			$query .= ' LIMIT ' . $limit;
		}

		return $query;
	}

	/*
	* Build_insert_query
	*
	* Build query for insert
	*
	* @param string $table
	* @param array $data
	*
	* @return string
	*/
	private function build_insert_query(string $table, array $data) {
		$keys = array_keys($data);
		$prepared_values = $this->crate_insert_placeholders($data);

		$query = 'INSERT INTO ' . $table . '(' .implode(', ', $keys) . ')' . ' VALUES (' . implode(', ', $prepared_values) . ')';

		return $query;
	}

	/*
	* Build_update_query
	*
	* Build query for update
	*
	* @param string $table
	* @param array $data
	* @param array $where
	*
	* @return string
	*/
	private function build_update_query(string $table, array $data, array $where) {
		$set_params = $this->crate_placeholders($data);
		$where_params = $this->crate_placeholders($where);

		$query = 'UPDATE ' . $table . ' SET ' .implode(', ', $set_params) . ' WHERE ' . implode(' AND ', $where_params);

		return $query;
	}

	/*
	* Build_delete_query
	*
	* Build query for delete
	*
	* @param string $table
	* @param array $where
	*
	* @return string
	*/
	private function build_delete_query(string $table, array $where) {
		$where_params = $this->crate_placeholders($where);

		$query = 'DELETE FROM ' . $table . ' WHERE  ' . implode(' AND ', $where_params);

		return $query;
	}

	/*
	* Crate_placeholders
	*
	* Create named placeholders for bind parameters
	*
	* @param array $data
	*
	* @return array
	*/
	private function crate_placeholders($data) {
		$placeholders = array();
		foreach ($data as $key => $value) {
			$placeholders[] = $key . ' = :' . $key;
		}
		return $placeholders;
	}

	/*
	* Create_insert_placeholders
	*
	* Create named placeholders for bind parameters for insert
	*
	* @param array $data
	*
	* @return array
	*/
	private function crate_insert_placeholders($data) {
		$placeholders = array();
		foreach ($data as $key => $value) {
			$placeholders[] = ':' . $key;
		}
		return $placeholders;
	}

	/*
	* Bind_params
	*
	* Bind parameters to statement and return statement
	*
	* @param object $stmt
	* @param array $data
	*
	* @return object
	*/
	private function bind_params(PDOStatement $stmt, array $data) {
		foreach ($data as $key => $value) {
			${$key} = $value;
			$stmt->bindParam(':' . $key, ${$key});
		}
		return $stmt;
	}

	/*
	* Execute_statement
	*
	* Executes statetement
	*
	* @param object $stmt
	*
	* @return object
	*/
	private function execute_statement(PDOStatement $stmt) {
		try {
			$stmt->execute();
		}
		catch(PDOException $error) {
			return new Error($error->getMessage());
		}
		return $stmt;
	}

	/*
	* Get_value
	*
	* Select value from database and returns its value
	*
	* @param string $table
	* @param string $data
	* @param array $where
	*
	* @return string|false
	*/
	public function get_value(string $table, string $data, array $where) {
		$query = $this->build_select_query($table, $data, $where);

		$stmt = $this->connection->prepare($query);

		$stmt = $this->bind_params($stmt, $where);

		$stmt = $this->execute_statement($stmt);

		// Make sure that no more than one value is returned from query
		if ($stmt->rowCount() > 1) {
			return new Error('Database::get_value should return only one value, ' . $stmt->rowCount() . ' values returned');
		}
		$object = $stmt->fetchObject();

		if ($object == false) {
			return false;
		}
		return $object->{$data};
	}

	/*
	* Get_row
	*
	* Select row from database and returns it as object
	*
	* @param string $table
	* @param string $data
	* @param array $where
	*
	* @return object|false
	*/
	public function get_row(string $table, string $data, array $where) {

		$query = $this->build_select_query($table, $data, $where);

		$stmt = $this->connection->prepare($query);

		$stmt = $this->bind_params($stmt, $where);

		$stmt = $this->execute_statement($stmt);

		// Make sure that no more than one value is returned from query
		if ($stmt->rowCount() > 1) {
			return new Error('Database::get_row should return only one value, ' . $stmt->rowCount() . ' values returned');
		}

		return $stmt->fetchObject();
	}

	/*
	* Select
	*
	* Select data from database and returns array containing objects
	*
	* @param string $table
	* @param string $data
	* @param array $where
	* @param array $ordering
	* @param int $limit
	*
	* @return array
	*/
	public function select(string $table, string $data, array $where = null, array $ordering = null, int $limit = null) {

		$query = $this->build_select_query($table, $data, $where, $ordering, $limit);

		$stmt = $this->connection->prepare($query);

		if ($where != null) {
			$stmt = $this->bind_params($stmt, $where);
		}

		$stmt = $this->execute_statement($stmt);

		$return_array = array();
		for($i = 0; $i < $stmt->rowCount(); $i++) {
			$return_array[] = $stmt->fetchObject();
		}
		return $return_array;
	}

	/*
	* Query
	*
	* Custom query to database, returns result
	*
	* @param string $query
	* @param array $params
	*
	* @return object|false
	*/
	public function query(string $query, aray $params = null) {
		$stmt = $this->connection->prepare($query);

		if ($params != null) {
			$stmt = $this->bind_params($stmt, $params);
		}

		$stmt = $this->execute_statement($stmt);

		return $stmt;
	}

	/*
	* Insert
	*
	* Insert data to database and returns insert id
	*
	* @param string $table
	* @param array $data
	*
	* @return int|false
	*/
	public function insert(string $table, array $data) {
		$query = $this->build_insert_query($table, $data);

		$stmt = $this->connection->prepare($query);

		$stmt = $this->bind_params($stmt, $data);

		$stmt = $this->execute_statement($stmt);

		return $this->connection->lastInsertId();
	}

	/*
	* Update
	*
	* Update data to database and return rows updated
	*
	* @param string $table
	* @param array $data
	* @param array $where
	*
	* @return int|false
	*/
	public function update(string $table, array $data, array $where) {
		$bind_params = array();
		foreach ($data as $key => $value) {
			$bind_params[$key] = $value;
		}
		foreach ($where as $key => $value) {
			$bind_params[$key] = $value;
		}

		$query = $this->build_update_query($table, $data, $where);

		$stmt = $this->connection->prepare($query);

		$stmt = $this->bind_params($stmt, $bind_params);

		$stmt = $this->execute_statement($stmt);

		return $stmt->rowCount();
	}

	/*
	* Delete
	*
	* Delete data from database and return number of rows deleted
	*
	* @param string $table
	* @param array $where
	*
	* @return int|false
	*/
	public function delete(string $table, array $where) {
		$query = $this->build_delete_query($table, $where);

		$stmt = $this->connection->prepare($query);

		$stmt = $this->bind_params($stmt, $where);

		$stmt = $this->execute_statement($stmt);

		return $stmt->rowCount();
	}
}
?>
