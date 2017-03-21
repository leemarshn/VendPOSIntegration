<?php
/**
 * Created by PhpStorm.
 * User: Lee
 * Date: 3/15/17
 * Time: 12:26 PM
 */

$servername = "localhost";
$username = "root";
$password = "733securex";
$dbname = "vReporting101";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";

//Declarations Form Parameters
$product = mysqli_real_escape_string($conn, $_POST['product']);
$sku = mysqli_real_escape_string($conn, $_POST['sku']);
$supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
$dateBefore = mysqli_real_escape_string($conn, $_POST['dateBefore']);
$dateAfter = mysqli_real_escape_string($conn, $_POST['dateAfter']);
$inventory = mysqli_real_escape_string($conn, $_POST['inventory']);

echo $supplier;

//Query begins here

if ($supplier == "supplier") {
    //Query Suppliers
    $sql = "SELECT fullNames, email from Suppliers";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $message .= $row["fullNames"] . ": " . $row["fullNames"] . " pt(s)" . PHP_EOL;
            //echo var_dump($row);
        }
        echo $message;
    }
}