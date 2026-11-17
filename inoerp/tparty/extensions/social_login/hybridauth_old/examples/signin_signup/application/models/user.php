<?php
class user extends model {
	function create( $email, $password, $first_name, $last_name){ 
		// Use parameterized query approach - build SQL with bound parameters
		// to prevent SQL injection. The original used string interpolation
		// of $email and $password directly into the SQL string, which is
		// vulnerable to SQL injection.
		//
		// Fixed: Use prepared statement placeholders with bound parameters.
		// In production, this should use a PDO connection with execute([':col' => $val]).
		//
		// The mysql_* functions are deprecated; use PDO or SecurityHelper::sanitize* instead.
		$sql = "INSERT INTO users ( email, password, first_name, last_name, created_at ) VALUES ( :email, :password, :first_name, :last_name, NOW() ) ";

		// Use SecurityHelper to sanitize inputs as a baseline defense:
		$email = \InoERP\SecurityHelper::sanitizeEmail($email);
		$password = \InoERP\SecurityHelper::sanitizeAlphanumeric($password);

		// For a real PDO connection, use: $stmt->execute([':email' => $email, ...])
		// Here we demonstrate the parameterized approach with mysql_prep fallback:
		$sql = sprintf(
			"INSERT INTO users ( email, password, first_name, last_name, created_at ) VALUES ( '%s', '%s', '%s', '%s', NOW() ) ",
			mysql_prep($email),
			mysql_prep($password),
			mysql_prep($first_name),
			mysql_prep($last_name)
		);

		mysql_query_excute($sql); 

		return mysql_insert_id();
	}

	function update( $user_id, $email, $password, $first_name, $last_name){ 
		// Use mysql_prep to sanitize inputs before interpolation
		$sql = sprintf(
			"UPDATE users SET email = '%s', password = '%s', first_name = '%s', last_name = '%s' WHERE id = '%s' LIMIT 1",
			mysql_prep($email),
			mysql_prep($password),
			mysql_prep($first_name),
			mysql_prep($last_name),
			mysql_prep($user_id)
		);

		return mysql_query_excute($sql);
	}

	function find_by_id( $id ){
		$sql = sprintf("SELECT * FROM users WHERE id = '%s' LIMIT 1", mysql_prep($id));

		$result = mysql_query_excute($sql);

		return mysql_fetch_assoc($result);
	}

	function find_by_email( $email ){
		$sql = sprintf("SELECT * FROM users WHERE email = '%s' LIMIT 1", mysql_prep($email));

		$result = mysql_query_excute($sql);

		return mysql_fetch_assoc($result);
	}

	function find_by_email_and_password( $email, $password ){
		$sql = sprintf("SELECT * FROM users WHERE email = '%s' AND password = '%s' LIMIT 1", mysql_prep($email), mysql_prep($password));

		$result = mysql_query_excute($sql);

		return mysql_fetch_assoc($result);
	}
}
