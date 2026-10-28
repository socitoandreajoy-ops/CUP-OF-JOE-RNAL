<?php
$conn = mysqli_connect("localhost", "root", "", "cupofjoernal");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>