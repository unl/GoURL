<?php

class PdoDB extends PDO {
	private $_error;
	private $_sql;
	private $_bind;
	private $_errorCallbackFunction;
	private $_errorMsgFormat;

	public function __construct($dsn, $user="", $passwd="", $options=array()) {
		if (empty($options)) {
			$options = array(
				PDO::ATTR_PERSISTENT => false,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			);
		}

		try {
			parent::__construct($dsn, $user, $passwd, $options);
		} catch (PDOException $e) {
			trigger_error($e->getMessage());
			return false;
		}
	}

	private function debug() {
		if (!empty($this->_errorCallbackFunction)) {
			$error = array("Error" => $this->_error);
			if (!empty($this->_sql)) {
				$error["SQL Statement"] = $this->_sql;
			}
			if (!empty($this->_bind)) {
				$error["Bind Parameters"] = trim(print_r($this->_bind, true));
			}

			$backtrace = debug_backtrace();
			if (!empty($backtrace)) {
				foreach ($backtrace as $info) {
					if (isset($info["file"] ) && $info["file"] != __FILE__) {
						$error["Backtrace"] = $info["file"] . " at line " . $info["line"];
					}
				}
			}

			$msg = "";
			if ($this->_errorMsgFormat == "html") {
				if (!empty($error["Bind Parameters"])) {
					$error["Bind Parameters"] = "<pre>" . $error["Bind Parameters"] . "</pre>";
				}
				$css = trim(file_get_contents(dirname(__FILE__) . "/error.css"));
				$msg .= '<style type="text/css">' . "\n" . $css . "\n</style>";
				$msg .= "\n" . '<div class="db-error">' . "\n\t<h3>SQL Error</h3>";
				foreach ($error as $key => $val) {
					$msg .= "\n\t<label>" . $key . ":</label>" . $val;
				}
				$msg .= "\n\t</div>\n</div>";
			}
			elseif ($this->_errorMsgFormat == "text") {
				$msg .= "SQL Error\n" . str_repeat("-", 50);
				foreach ($error as $key => $val) {
					$msg .= "\n\n$key:\n$val";
				}
			}

			$func = $this->_errorCallbackFunction;
			$func($msg);
		}
	}

	public function delete($table, $where, $bind="") {
		$sql = "DELETE FROM " . $table . " WHERE " . $where . ";";
		return $this->run($sql, $bind);
	}

	private function filter($table, $info) {
		$driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($driver == 'sqlite') {
			$sql = "PRAGMA table_info('" . $table . "');";
			$key = "name";
		}
		elseif ($driver == 'mysql') {
			$sql = "DESCRIBE " . $table . ";";
			$key = "Field";
		}
		else {
			$sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';";
			$key = "column_name";
		}

		if (false !== ($list = $this->run($sql, '', FALSE, PDO::FETCH_ASSOC))) {
			$fields = array();
			foreach ($list as $record) {
				$fields[] = $record[$key];
			}
			return array_values(array_intersect($fields, array_keys($info)));
		}
		return array();
	}

	private function cleanup($bind) {
		if (!is_array($bind)) {
			if (!empty($bind)) {
				$bind = array($bind);
			}
			else {
				$bind = array();
			}
		}
		foreach ($bind as $key => $val) {
			$bind[$key] = stripslashes($val);
		}
		return $bind;
	}

	public function insert($table, $info) {
		$fields = $this->filter($table, $info);
		$sql = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
		$bind = array();
		foreach ($fields as $field) {
			$bind[":$field"] = $info[$field];
		}
		return $this->run($sql, $bind);
	}

	public function run($sql, $bind="", $fetchOne = FALSE, $fetchFormat = PDO::FETCH_OBJ) {
		$this->_sql = trim($sql);
		$this->_bind = $this->cleanup($bind);
		$this->_error = "";

		try {
			$pdostmt = $this->prepare($this->_sql);
			if ($pdostmt->execute($this->_bind) !== false) {
				if (preg_match("/^(" . implode("|", array("\(select", "select", "describe", "pragma")) . ") /i", $this->_sql)) {
					return $fetchOne === TRUE ? $pdostmt->fetch($fetchFormat) : $pdostmt->fetchAll($fetchFormat);
				} elseif(preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $this->_sql)) {
					return $pdostmt->rowCount();
				}
			}
		} catch (PDOException $e) {
			$this->_error = $e->getMessage();
			$this->debug();
			return false;
		}
	}

	public function select($table, $where="", $bind="", $fields="*", $fetchOne = FALSE, $fetchFormat = PDO::FETCH_OBJ) {
		$sql = "SELECT " . $fields . " FROM " . $table;
		if (!empty($where)) {
			$sql .= " WHERE " . $where;
		}
		$sql .= ";";
		return $this->run($sql, $bind, $fetchOne, $fetchFormat);
	}

	public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat="html") {
		//Variable functions for won't work with language constructs such as echo and print, so these are replaced with print_r.
		if (in_array(strtolower($errorCallbackFunction), array("echo", "print"))) {
			$errorCallbackFunction = "print_r";
		}

		if (function_exists($errorCallbackFunction)) {
			$this->_errorCallbackFunction = $errorCallbackFunction;
			if (!in_array(strtolower($errorMsgFormat), array("html", "text"))) {
				$errorMsgFormat = "html";
			}
			$this->_errorMsgFormat = $errorMsgFormat;
		}
	}

	public function update($table, $info, $where, $bind="") {
		$fields = $this->filter($table, $info);
		$fieldSize = sizeof($fields);

		$sql = "UPDATE " . $table . " SET ";
		for ($f = 0; $f < $fieldSize; ++$f) {
			if ($f > 0) {
				$sql .= ", ";
			}
			$sql .= $fields[$f] . " = :update_" . $fields[$f];
		}
		$sql .= " WHERE " . $where . ";";

		$bind = $this->cleanup($bind);
		foreach ($fields as $field) {
			$bind[":update_$field"] = $info[$field];
		}

		return $this->run($sql, $bind);
	}
}