<?php
class user extends model {
	function create( $email, $password, $first_name, $last_name){ 
		$sql = "INSERT INTO users ( email, password, first_name, last_name, created_at ) VALUES ( '$email', '$password', '$first_name', '$last_name', NOW() ) ";

		// Sanitize inputs to prevent SQL injection via mysql_prep
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
