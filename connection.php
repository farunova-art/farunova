<?php
$server   = "102.37.130.206";   // or "localhost"
$username = "root";     // the user you created in MariaDB
$password = ""; // the password you set
$db       = "GROUP1";      // matches your created DB

$conn = new mysqli($server, $username, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

