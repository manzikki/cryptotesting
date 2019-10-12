<?php
//PHP Coin wallet shopping cart by manzikki 2018.
//Installation: 1. this script and buy.php should go to HTTP_ROOT/s.
//2. set the SH_DIR variable here and in buy.php, put your wallet(s), shell scripts and wallet binary there.

$SH_DIR = "/var/www/data";

$realm = 'Restricted area';
$users = array();

//user => password. These correspond with wallet files and their passwords.
if (!include($SH_DIR."/users.php")) 
{ die("user data not found, cannot continue"); 
} else {
    $users = $uarr;
}

//product => name
$products = array('10345678' => 'Power Bank', '12345678' => 'Penguin');

if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="'.$realm.
           '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');

    die('Text to send if user hits Cancel button');
}


// analyze the PHP_AUTH_DIGEST variable
if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) ||
    !isset($users[$data['username']]))
    die('Wrong Credentials!');


// generate the valid response
$A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

if ($data['response'] != $valid_response)
    die('Wrong Credentials!');

echo "<html><body style='font: normal 20px Verdana, Arial, sans-serif;'><meta name='viewport' content='width=device-width, initial-scale=1.0'>";
// ok, valid username & password
echo 'You are logged in as: ' . $data['username'] . "<br/><br/><br/>";
session_start();
$_SESSION['loggedin'] = true;
$_SESSION['username'] = $data['username'];
$user = $data['username'];
$pw = $users[$user];
$_SESSION['pw'] = $pw;

//get parameters
$seller = "";
$price = "";
$productid="";

$q = $_SERVER['QUERY_STRING'];
if ($q ==! "")
{
    $parts = explode("=",$q);
    if (count($parts) == 3)
    {
        $productid = $parts[0];
        $seller = $parts[1];
        $price = $parts[2];
    }
}

//Do we have the product?
if (array_key_exists($productid, $products))
{
    $prod = $products[$productid];
} else {
    $prod = "UNKNOWN";
}

//show balance even if no params
$balance = get_balance($user, $pw);

if (($seller ==! "") && ($price !== ""))
{
    echo "Product: ".$prod."<br/>";
    echo "Seller: ".$seller."<br/>";
    echo "Price: ".$price."<br/>";
} else {
    echo "Error parsing parameters: prodid, price and seller not found.</br>";
    //You cannot buy non-existing things
    $balance = 0;
}

if ($balance > floatval($price))
{
    echo "<br/><br/>";
    echo "<form method='post' action='buy.php'>";
    echo "<input name='price' hidden='true' value=".$price.">";
    echo "<input name='seller' hidden='true' value=".$seller.">";
    echo "<input name='product' hidden='true' value=".$productid.">";
    echo "<center><input style='width:100px;height:50px;' type='submit' value='Buy'/></center>";
    echo "</form>";
}

//end of page
echo "</body></html>";

//function to fetch balance
function get_balance($user, $pw)
{
    $out = array();
    $bline = "";
    $is_error = false;
    global $SH_DIR;
    exec($SH_DIR."/getbal.sh ".$user." ".$pw." 2>&1", $out);
    foreach($out as $line) {
        if (strpos($line, 'Error') !== false) { echo "<br/>".$line."<br/>"; $is_error = true; }

        if (strpos($line, 'available balance') !== false)
        {
            echo "<br/>".$line."<br/>";
            $bline = $line;
        }
    }
    if ($bline == "") {
        echo "<br/>Error getting balance. Wallet may be offline.<br/>";
        var_dump( $out );
        return 0;
    }
    //parse the amount
    $commapos = strpos($bline, ',');
    if ($commapos === false) {
        echo "<br/>Error parsing the balance. Line is ".$bline."<br/>";
        return 0;
    }
    $startlen = strlen("available balance: ");
    $balstr = substr($bline, $startlen, $commapos-$startlen);
    //convert to float. will be 0 if conversion fails
    $floatbal = floatval($balstr);
    if ($is_error == true) { return 0; }
    return floatval($balstr);
}

// function to parse the http auth header
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}
?>



