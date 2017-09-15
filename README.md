# database
PHP MySQL PDO database class. Require PHP7 or class named Error for error handling.
All methods return PHP7 Error object in case of error.

# Usage
Create database object: $database = new Database($host, $dbname, $dbuser, $dbpass);

Connect to database: $database->connect();

Discoonect from database (call this from class destructor): $database->disconnect();

All statements support multiple where values. Returning object properties are named as coresponding database column name.

# get_value
@param string $table

@param string $data

@param array $where

Get one value from database, where parameters should be unique. Return array containing objects

$database->get_value($table, $value_name, array($where_key => $where_value));

# get_row
@param string $table

@param string $data

@param array $where

Get one row from database, where parameters should be unique. Return object

$database->get_row($table, $column_names, array($where_key => $where_value));

# select
@param string $table

@param string $data

@param array $where

@param array $ordering

@param int $limit

General select query, $where, $ordering and $limit are optional, if not set, null should be used. If using multiple directions, first is primary ordering, and so on.

$database->select($table, $data, array($where_key => $where_value), array($column_name => $direction), $limit)

# query
@param string $query

@param array $params

General query, can be used in more complex queries like statemts with LIKE and different logical operators. Return exequted PDO statement-object

Example: $query = 'SELECT * FROM foobar WHERE foo != :foo AND (bar > :bar OR foobar LIKE :foobar)';

$database->query($query, $params);

# insert
@param string $table

@param array $data

Insert data to table, return insert id

$database->insert($table, array($key => $value));

# update
@param string $table

@param array $data

@param array $where

Update data, return number of rows updated

$database->update($table, array($key => $value), array($where_key => $where_value));

# delete
@param string $table

@param array $where

Delete data, return number of rows deleted

$database->delete($table, array($where_key => $where_value));
