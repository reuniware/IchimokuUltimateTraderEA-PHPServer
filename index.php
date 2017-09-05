<?php
define("MYSQL_SERVER", "localhost");
define("MYSQL_USER", "id000000_ichimoku");
define("MYSQL_PASSWORD", "00000000");
define("MYSQL_DB", "000000_ichimoku");
define("TBL_PREFIX", "ichimoku_server_v3");
define("CREATE_DB_IF_NOT_EXISTS", true);
define("CREATE_TABLES_IF_NOT_EXIST", true);
define("LOG_IP", true);
define("LOG_IP_IGNORE", "127.0.0.1");
define("DISABLE_DETAILED_LOG_VIEW", true);
define("DEBUG", true);
define("SHOW_ONLY_TODAY", true);

$date = new Date();

$page = file_get_contents("https://rates.fxcm.com/RatesXML");
$xml = new SimpleXMLElement($page);
$result = $xml->xpath('/Rates/Rate');
//echo 'result count = ' . count($result);
//$rates=array();
for($i=0;$i<count($result);$i++){
    $symbol = (string) $result[$i]->xpath('@Symbol')[0];
    $bid = (string) $result[$i]->xpath('Bid')[0]; // sell price
    $ask = (string) $result[$i]->xpath('Ask')[0]; // buy price
    $rates[$i]["symbol"]=$symbol;
    $rates[$i]["bid"]=$bid;
    $rates[$i]["ask"]=$ask;

    //echo $rates[$i][$symbol];
}


$novalidations = false;
$eightvalidations = false;

if (isset($_GET["novalidations"])){
    $novalidations = true;
}
if (isset($_GET["eightvalidations"]) && $novalidations == false){
    $eightvalidations = true;
}


$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
if ($db->connect_errno) {
    echo "Erreur : " . $db->connect_errno . " <br/>";
    exit;
}

if ($novalidations == false) {
    $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert where timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) order by timestamp desc");
}

if ($novalidations == true) {
    $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) and a.h1_ls_validated like '%*%' order by timestamp desc");
}

if ($eightvalidations == true) {
        $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) and a.h1_ls_validated like '%Validation (max=8) = 8%' order by timestamp desc");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Ichimoku Scanner by InvestData Systems (c) 2017</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Analyse financière automatisée des marchés financiers et aide à la prise de décision en matière d'investissement">

    <!-- Schema.org markup for Google+ -->
    <meta itemprop="name" content="Ichimoku Scanner">
    <meta itemprop="description" content="Analyse financière automatisée des marchés financiers et aide à la prise de décision en matière d'investissement">
    <meta itemprop="image" content="http://investdata.000webhostapp.com/alerts/ichimokuscannerlogo.png">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="Ichimoku Scanner">
    <meta name="twitter:site" content="@InvestdataSystems">
    <meta name="twitter:title" content="Ichimoku Scanner Online">
    <meta name="twitter:description" content="Analyse financière automatisée des marchés financiers et aide à la prise de décision en matière d'investissement">
    <meta name="twitter:creator" content="@InvestDataSystems_At_Yahoo_Dot_Com">
    <!-- Twitter summary card with large image must be at least 280x150px -->
    <meta name="twitter:image:src" content="http://investdata.000webhostapp.com/alerts/ichimokuscannerlogo.png">

    <!-- Open Graph data -->
    <meta property="og:title" content="ichimoku Scanner" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="http://investdata.000webhostapp.com/alerts/" />
    <meta property="og:image" content="http://investdata.000webhostapp.com/alerts/ichimokuscannerlogo.png" />
    <meta property="og:description" content="Analyse financière automatisée des marchés financiers et aide à la prise de décision en matière d'investissement" />
    <meta property="og:site_name" content="Ichimoku Scanner" />
    <!--<meta property="article:published_time" content="2013-09-17T05:59:00+01:00" />
    <meta property="article:modified_time" content="2013-09-16T19:08:47+01:00" />
    <meta property="article:section" content="Article Section" />
    <meta property="article:tag" content="Article Tag" />
    <meta property="fb:admins" content="Facebook numberic ID" />-->
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
</head>
<body>

<div align="center">
    <img src="ichimokuscannerlogo.PNG"/>
    <h1><span style="color: darkslategrey; font-style: italic">Investdata Systems</span></h1>
    <h2>Ichimoku Scanner - Experimental Research Center (fr)</h2>
    <h3>Ichimoku Ultimate Trader EA Server (IUT-EA Server) v4</h3>
    <h4>Realtime Detected Ichimoku Criterias List</h4>
    <h6>JCS=Japanese Candle Stick, HIGH=Higher price, OPEN=Opening price, CLOSE=Closing price</h6>
    <h6>CHIKOU, KUMO, TENKAN and KIJUN are self-explanatory, SSA = Senkou Span A, SSB = Senkou Span B</h6>
    <h4>&nbsp<br/><a href='mailto:investdatasystems@yahoo.com'>Click here to send us an E-mail</a></h4>
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="NNR8W5H23XEZS">
        <input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
        <img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
    </form>
</div>
<div align="center">
<h4>&nbsp</h4>
    <a href="./index.php?novalidations" class="btn btn-primary">View data without validations</a>
    <a href="./index.php" class="btn btn-primary">View data with validations</a>
    <a href="./index.php?eightvalidations" class="btn btn-primary">View only 8/8 validations</a>
</div>
<div class='table-responsive'>
    <table class="table table-bordered table-inverse table-hover" style="font-size: 12px; font-family:Verdana;">
        <tbody>
        <thead>
        <tr>
            <th><span style="color: gray">Timestamp</span></th>
            <th><span style="color: gray">Period</span></th>
            <th><span style="color: gray">Symbol</span></th>
            <th><span style="color: gray">Criteria Detected (from 5 days ago to now)</span></th>
        </tr>
        </thead>
        <?php
        while($row = $r->fetch_assoc()) {
            $timestamp = $row["timestamp"];
            $period = $row["period"];
            $symbol = $row["symbol"];
            $buy = $row["buy"];
            $sell = $row["sell"];
            $h1_ls_validated = $row["h1_ls_validated"];
            $m1_ls_validated = $row["m1_ls_validated"];

            if (strpos($h1_ls_validated, "* JCS(M15(-1)) has crossed KUMO(M15(-1)) while up *") != false){
                $h1_ls_validated .= "<br/><font color='gray'>" . "buy=" . $buy . " sell=" . $sell . "</font>";
            }
            else if (strpos($h1_ls_validated, "OK") != false){
                $h1_ls_validated .= "<br/><font color='gray'>" . "buy=" . $buy . " sell=" . $sell . "</font>";
            }
            else if (strpos($h1_ls_validated, "KO") != false){
                $h1_ls_validated .= "<br/><font color='gray'>" . "buy=" . $buy . " sell=" . $sell . "</font>";
            }

            $extendedsymbol=substr($symbol, 0, 3) . '_' . substr($symbol, 3, 6);
            $extendedsymbollink = "<a target='_blank' href='https://www.forex.com/en/markets/popular-markets/" . $extendedsymbol . "'>$symbol</a>";

            $symbolfound = false;
            if (count($rates>0)) {
                for ($i = 0; $i < count($rates); $i++) {
                    if ($rates[$i]["symbol"] == $symbol) {
                        $symbolfound = true;
                        $symbol = "<b>" . $extendedsymbollink . "</b>" . "<br/>bid/sell:" . $rates[$i]["bid"];
                        $symbol .= "<br/>ask/buy:" . $rates[$i]["ask"];
                        $delta = number_format((double)$rates[$i]["bid"] - (double)$buy, 5);
                        if ($delta > 0) $symbol .= "<br/><b>delta=<font color='green'>+$delta</font></b>";
                        if ($delta < 0) $symbol .= "<br/>delta=<font color='orange'>$delta</font>";
                    }
                }
            }

            if ($symbolfound == false){
                $symbol = "<b>" . $extendedsymbollink . "</b>";
            }

            $stringLenToRemove = strlen("2017-09-01 21:00: SGDJPY: ");
            if(strlen($h1_ls_validated) > $stringLenToRemove){
                $h1_ls_validated = substr($h1_ls_validated, $stringLenToRemove, strlen($h1_ls_validated));
            }

            if($h1_ls_validated === "*** JCS(H4(-1)) > KUMO(H4(-1)) and JCS(H1(-1)) > KUMO(H1(-1)) and JCS(M15(-1)) has crossed KUMO(M15(-1)) while up ***")
            {
                $h1_ls_validated = "<span style='color:limegreen'>" . $h1_ls_validated . "<br/>buy=$buy sell=$sell spread=" . number_format($buy-$sell, 5) . "</span>";
            }

            if($eightvalidations == true){
                $h1_ls_validated = "<font color='gray'>buy=" . $buy . " sell=" . $sell . " spread=" . number_format($buy-$sell, 5) . "</font>";
            }

            echo "
<tr>
    <td width='12%'>$timestamp</td>
    <td width='5%'>$period</td>
    <td width='5%'>$symbol</td>
    <td width='88%'>$h1_ls_validated</td>
</tr>
  </div>";
        }
        $db->close();
        ?>
        </tbody>
    </table>
</div>

<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>

<!-- Optional JavaScript -->
<script>
    $(document).ready(function () {
    });
</script>

</body>
</html>

