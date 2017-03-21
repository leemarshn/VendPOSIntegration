<?php
$servername = "localhost";
$username   = "dynaphar_app";
$password   = "733securex";
$dbname     = "dynaphar_app";

$afriusername = "leemarshn";
$apikey       = "eeec2690ad46812b217d9829ef9656a6208c42c859797e9de2bdcea8d9107cd3";
require_once('AfricasTalkingGateway.php');
$gateway     = new AfricasTalkingGateway($afriusername, $apikey);
$shortCode   = "22384";
$keyword     = "dyna"; // $keyword = null;
$bulkSMSMode = 0;
$mons = array(1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec");
$today = getdate();
$month = $today['mon'];
$year = date('Y');
// Create connection
$conn        = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$from       = mysqli_real_escape_string($conn, $_POST['from']);
$to         = mysqli_real_escape_string($conn, $_POST['to']);
$text       = mysqli_real_escape_string($conn, $_POST['text']);
$dateOb     = mysqli_real_escape_string($conn, $_POST['date']);
$id         = mysqli_real_escape_string($conn, $_POST['id']);
$linkId     = mysqli_real_escape_string($conn, $_POST['linkId']);
$text_array = explode(" ", $text);
//echo var_dump($text_array);

$sql = "INSERT INTO sms_messages VALUES ('$from','$to','$text','$dateOb','$id','$linkId')";

$options = array(
    'keyword' => $keyword,
    'linkId' => $linkId
);

if ($conn->query($sql) === TRUE) {
    $recipients = $from;
    if (trim(strtolower($text_array[1])) == "bvs") {
        $sql = "SELECT fullNames,SUM(BvPoints),rank FROM pricelist AS pric 
            INNER JOIN sales AS sal  
            ON pric.ProductCode = sal.ProductCode 
            INNER JOIN distributors as distrb 
            ON sal.DRN = distrb.DRN 
            WHERE
            YEAR(transactionDate) = YEAR(CURRENT_DATE())  AND
            MONTH(transactionDate) = MONTH(CURRENT_DATE()) AND sal.DRN LIKE '%$text_array[2]%' 
            GROUP BY fullNames,rank";
        $result = $conn->query($sql);
        $distributor_name = "";
        $bv_points        = 0;
        $rank = "";
        $bonus_threshold = 0;
        /* fetch object array */
        if($result->num_rows > 0) {
            while ($row = $result->fetch_row()) {
                $distributor_name = $row[0];
                $bv_points        = (int) $row[1];
                $rank = $row[2];
            }
            //echo $$distributor_name." ".$bv_points." ".$rank;
            if ($rank == "DI") { $bonus_threshold = 20; }
            if ($rank == "M" || $rank == "SM" || $rank == "RM" || $rank == "DM" || $rank == "CDM") { $bonus_threshold = 50; }
            $message = "";//$bv_points > 42
            if ($bv_points > $bonus_threshold) {
                //Replace Sep 2016 or Aug 2016 with appropriate value from DB
                $message = "Dear $distributor_name ($rank), $mons[$month] $year BV Points are $bv_points. Congratulations! You have qualified for bonus.";
            } else {
                $deficit = $bonus_threshold - $bv_points;
                //echo $mons[$month];
                $message = "Dear $distributor_name ($rank), $mons[$month] $year BV Points are $bv_points, do purchases amounting to $deficit points or more to qualify for bonus.";
            }
            //sendSMS($gateway, $recipients, $message, $shortCode, $bulkSMSMode, $options);
            echo $message;
        }


    }
    if (trim(strtolower($text_array[1])) == "bvh") {
        //confirm BVS history
        // sendSMS($gateway,$recipients, $message, $shortCode, $bulkSMSMode, $options);
        $sql = "SELECT  MONTH(transactionDate) AS transmonth,SUM(p.BVPoints) AS totallBvPoints FROM sales s INNER JOIN pricelist AS p ON p.ProductCode = s.ProductCode WHERE DRN LIKE'%$text_array[2]%' GROUP BY transmonth";
        $result = $conn->query($sql);
        $result_text = "";
        if($result->num_rows > 0) {
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $result_text .= $mons[$row["transmonth"]]. ": ".$row["totallBvPoints"]." pt(s)".PHP_EOL;
                //echo var_dump($row);
            }
            echo $result_text;
        }
    }
    if (trim(strtolower($text_array[1])) == "find") {
        //Confirm DK or Telephone number
        $message = "";
        $pos = strrpos("$text_array[2]","DK");
        if ($pos !== 0) {
            //Confirm telephone
            $rank = $fullNames = $drn = $mobile = "";
            $sql = "SELECT rank,fullNames,drn,mobile FROM distributors WHERE mobile = $text_array[2]";
            echo $sql;

            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                while ($row = $result->fetch_row()) {
                    $rank = $row[0];
                    $fullNames = $row[1];
                    $drn = $row[2];
                    $mobile = $row[3];
                }

                $message = "Code: $drn".PHP_EOL."Name: $fullNames ".PHP_EOL."Rank: $rank".PHP_EOL."Mobile: $mobile";
                echo $message;
            }

        } else {
            //Confirm DRN
            $rank = $fullNames = $mobile = $drn = "";
            $sql = "SELECT rank,fullNames,mobile,DRN FROM distributors WHERE DRN LIKE '%$text_array[2]%'";
            $result = $conn->query($sql);
            if($result->num_rows > 0) {
                while ($row = $result->fetch_row()) {
                    $rank = $row[0];
                    $fullNames = $row[1];
                    $mobile = $row[2];
                    $drn = $row[3];
                }
                $message = "Code: $drn".PHP_EOL."Name: $fullNames ".PHP_EOL."Rank: $rank".PHP_EOL."Mobile: $mobile";
                echo $message;
            }

        }

        //sendSMS($gateway,$recipients, $message, $shortCode, $bulkSMSMode, $options);
    }
    if (trim(strtolower($text_array[1])) == "price") {
        $sql = "SELECT ProductName,DPriceKSH,CPriceKSH,BVPoints FROM pricelist WHERE productName LIKE '%$text_array[2]%' LIMIT 3";
        $result = $conn->query($sql);
        $result_text = "";
        if($result->num_rows > 0) {
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {//$row[""]
                //$total_sales = $row[0];
                $result_text .= $row["ProductName"]." - ". " Price: ".$row["DPriceKSH"]."(DP), ".$row["CPriceKSH"]."(CP), BVs: ".$row["BVPoints"].PHP_EOL;
                //echo var_dump($row);
            }
            echo $result_text;
            //To confirm price
            // sendSMS($gateway,$recipients, $message, $shortCode, $bulkSMSMode, $options);
        }
    }
    if (trim(strtolower($text_array[1])) == "sales") {
        //To confirm price


        $sql = "SELECT sum(totalAmount) FROM sales WHERE DRN LIKE '%$text_arra[2]%' AND YEAR(transactionDate) = YEAR(CURRENT_DATE()) AND MONTH(transactionDate) = MONTH(CURRENT_DATE())";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            $total_sales = "";
            while ($row = $result->fetch_row()) {
                $total_sales = $row[0];
            }
            $message = "Dear customer Your sales for $mons[$month] is $total_sales. Thank you for your business.";
            echo $message;
            //sendSMS($gateway,$recipients, $message, $shortCode, $bulkSMSMode, $options);
        }

    }
    if (trim(strtolower($text_array[1])) == "mobile") {
        //to update telephone DYNA MOBILE DK**** 07*****
        //$text_array = array("DYNA","MOBILE","DK***","07*****");
        //$message ="Your number has been changed to ".$text_array[3];
        //$sql = "UPDATE distributors SET mobile = $text_array[3] WHERE drn LIKE '%$text_array[2]%'";

    }
    if (trim(strtolower($text_array[1])) == "downline") {

        $sql = "SELECT fullNames,directUpline FROM distributors WHERE directUpline LIKE '%$text_array[2]%' LIMIT 4";
        $result = $conn->query($sql);
        $downline = "";

        if($result->num_rows > 0) { //fetch_array(MYSQLI_NUM)
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                //echo $row["fullNames"];
                $downline .= $row["fullNames"]." ". $row["directUpline"].PHP_EOL;
                //("FullName: %s  upline: %s", $row["fullNames"], $row["directUpline"]);
            }
            echo $downline;

        }
        //To confirm downline
        // sendSMS($gateway,$recipients, $message, $shortCode, $bulkSMSMode, $options);          }

    }
    if (trim(strtolower($text_array[1])) == "bonus") {
        $MESSAGE = "";
        $sql = "SELECT amountPaid,DRN,MONTH(DateAwarded) AS monthAwarded, CASE WHEN status = 1 THEN '(Pd)' ELSE '(Os)' END AS PaidStatus FROM bonus WHERE DRN LIKE '%$text_array[2]%' ORDER BY DateAwarded DESC LIMIT 20";
        //To confirm bonus
        //$text_array = array(Dyna, bonus, Dk220558);
        $result = $conn->query($sql);
        $result_text = "";
        if($result->num_rows > 0) {
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $result_text .= $mons[$row["monthAwarded"]]. ": ".$row["amountPaid"]." ".$row["status"].PHP_EOL;
            }
            echo $result_text;
            // sendSMS($gateway,$recipients, $message, $shortCode, $bulkSMSMode, $options);
        }

    }
    //echo "New record created successfully, mobile number " . $from;
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();

/**
 *This is the function used to send sms to the user
 *call at the end of each conditional statement
 */
function sendSMS($gateway, $recipients, $message, $shortCode, $bulkSMSMode, $options)
{
    try {
        $results = $gateway->sendMessage($recipients, $message, $shortCode, $bulkSMSMode, $options);

        foreach ($results as $result) {
            // status is either "Success" or "error message"
            echo " Number: " . $result->number;
            echo " Status: " . $result->status;
            echo " MessageId: " . $result->messageId;
            echo " Cost: " . $result->cost . "\n";
        }
    }
    catch (AfricasTalkingGatewayException $e) {
        echo "Encountered an error while sending: " . $e->getMessage();
    }
}