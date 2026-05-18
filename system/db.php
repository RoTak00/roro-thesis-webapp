<?php

class DBResult
{
    public $rows = [];
    public $row = null;
    public $num_rows = 0;
    public $insert_id = 0;
    public $rows_affected = 0;
    public $success = false;
}
class DB
{
    private $conn;

    public function __construct($SERVERNAME, $USERNAME, $PASSWORD, $DATABASE)
    {

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conn = new mysqli($SERVERNAME, $USERNAME, $PASSWORD, $DATABASE);


        // Check connection
        if ($this->conn->connect_error) {
            //AddAlert("Eroare la conectare la baza de date.", "danger");
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->query('set character_set_client=utf8');
        $this->conn->query('set character_set_connection=utf8');
        $this->conn->query('set character_set_results=utf8');
        $this->conn->query('set character_set_server=utf8');
    }

    public function __destruct()
    {
        $this->conn->close();
    }


    public function query($query, $types = null, $params = [])
    {
        $db_result = new DBResult();

        if ($types) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $db_result->success = $stmt->execute();
            $db_result->insert_id = $this->conn->insert_id;
            $db_result->rows_affected = $stmt->affected_rows;

            $result = $stmt->get_result();
            if ($result instanceof mysqli_result) {
                $db_result->rows = $result->fetch_all(MYSQLI_ASSOC);
                $db_result->row = $db_result->rows[0] ?? null;
                $db_result->num_rows = $result->num_rows;
            }

            $stmt->close();
            return $db_result;
        }

        $result = $this->conn->query($query);
        $db_result->success = ($result !== false);
        $db_result->insert_id = $this->conn->insert_id;
        $db_result->rows_affected = $this->conn->affected_rows;

        if ($result instanceof mysqli_result) {
            $db_result->rows = $result->fetch_all(MYSQLI_ASSOC);
            $db_result->row = $db_result->rows[0] ?? null;
            $db_result->num_rows = $result->num_rows;
            $result->close();
        }

        return $db_result;
    }

    public function error()
    {
        return $this->conn->error;
    }

    public function escape($string)
    {
        return $this->conn->real_escape_string($string);
    }
    public function insert_id()
    {
        return $this->conn->insert_id;
    }
}