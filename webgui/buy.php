<?php

session_start();

$SH_DIR = "/var/www/natto.mooo.com/data";
echo "<html><body style='font: normal 20px Verdana, Arial, sans-serif;'>";

if ($_SESSION['loggedin'])
{
    //reconstruct the NFC string for the script
    $ok = true;
    if ($_POST["product"] == "") { echo "Product ID not found!"; $ok = false; }
    if ($_POST["price"] == "") { echo "Price not found!"; $ok = false; }
    if ($_POST["seller"] == "") { echo "Seller ID not found!"; $ok = false; }
    $wallet = $_SESSION['username'];
    $wpw = $_SESSION['pw'];
    if ($wallet == "") { echo "Wallet not found!"; $ok = false; }
    if ($wpw == "") { echo "Wallet password not found!"; $ok = false; }
    if ($ok == true)
    {
        $nfc = $_POST["product"]."=".$_POST["seller"]."=".$_POST["price"];
        global $SH_DIR;
        //echo $nfc;
        exec($SH_DIR."/buy.sh ".$nfc." ".$wallet." ".$wpw, $out);
        $ok = false;
        $all = "";
        foreach($out as $line) {
             $all = $all."<br/>".$line."<br/>";
             if (strpos($line, 'Error') !== false) { echo "<br/>".$line."<br/>"; }
             if (strpos($line, 'Money succ') !== false) { echo "<br/>".$line."<br/>"; $ok = true; }
             //echo "<br/>".$line."<br/>";
        }

        if ($ok == false) {
             echo "<br/>Something went wrong, full debug below</br>".$all;
        }
    }
}
else
{
   echo "<br/>Not authenticated or invalid input.<br/>";
}

echo "<a href=\"index.php\">Back to front page</a>";
echo "</body></html>";
?>
