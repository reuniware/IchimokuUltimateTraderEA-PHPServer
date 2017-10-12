<?php
define("MYSQL_SERVER", "localhost");
define("MYSQL_USER", "id000000000000000");
define("MYSQL_PASSWORD", "00000000");
define("MYSQL_DB", "00000000000000000");
define("TBL_PREFIX", "ichimoku_server_v3");
define("CREATE_DB_IF_NOT_EXISTS", true);
define("CREATE_TABLES_IF_NOT_EXIST", true);
define("LOG_IP", true);
define("LOG_IP_IGNORE", "127.0.0.1");
define("DISABLE_DETAILED_LOG_VIEW", true);
define("DEBUG", true);
define("SHOW_ONLY_TODAY", true);

//echo basename($_SERVER['PHP_SELF']);

if (LOG_IP==true){
    $db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
    if ($db->connect_errno) {
        exit;
    }
    $client_ip = $_SERVER['REMOTE_ADDR'];
    $nslookup = gethostbyaddr($client_ip);
    $url = $_SERVER['PHP_SELF'];
    $r = mysqli_query($db, "SELECT * FROM " . TBL_PREFIX . "_ip_address_log where ip_address = '" . $client_ip . "'");
    if ($r->num_rows > 0) {
        if($row = $r->fetch_assoc()) {
            $count = $row["count"];
            $r = mysqli_query($db, "update " . TBL_PREFIX . "_ip_address_log set access_date_time = NOW(), count = " . ($count+1) . ", nslookup='" . $nslookup . "', url='" . $url . "' where ip_address = '" . $client_ip . "'");
        }
    } else {
        $r = mysqli_query($db, "insert into " . TBL_PREFIX . "_ip_address_log(ip_address, access_date_time, nslookup, url, count) values ('" . $client_ip . "',NOW(),'" . $nslookup . "','" . $url . "',1)");
    }
    $r = mysqli_query($db, "DELETE FROM " . TBL_PREFIX . "_ip_address_log where ip_address like '%" . LOG_IP_IGNORE . "%'");
    $db->close();
}

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

$title = "";

$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
if ($db->connect_errno) {
    echo "Erreur : " . $db->connect_errno . " <br/>";
    exit;
}

if (isset($_GET['symbol']) && ($_GET['symbol']!='ALLSYMBOLS') && trim($_GET["symbol"]) != "" && (strlen($_GET["symbol"])<=32)) {
  $symbol = trim($_GET["symbol"]);
  $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where symbol='" . $symbol . "' and timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) order by timestamp desc");
  $title = "All data for " . $symbol;
} else if (isset($_GET['ALLVALIDATED'])) {
  $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where h1_ls_validated like '%All 8 validations are ok, with JCS(M15(-1)) % Kumo%' and timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) order by timestamp desc");
  $title = "All data (validated)";  
} else if (isset($_GET['ALLVALIDATEDCROSSINGKUMOWHILEUP'])) {
  $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where h1_ls_validated like '%All 8 validations are ok, with JCS(M15(-1)) crossing Kumo%' and timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) order by timestamp desc");
  $title = "All data (validated) crossing kumo while up";    
} else if (isset($_GET['ALLNONVALIDATEDCROSSINGKUMOWHILEUP'])) {
  $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where h1_ls_validated like '%and JCS(M15(-1)) has crossed KUMO(M15(-1)) while up%' and timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) order by timestamp desc");
  $title = "All data (non validated) crossing kumo while up";        
} else {
  //$r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) and a.h1_ls_validated like '%*%' order by timestamp desc");
  $r = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert a where timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) order by timestamp desc");
  $title = "All data";
}

$s = mysqli_query($db, "select distinct(symbol) from " . TBL_PREFIX . "_2jcs_alert a where timestamp>CONCAT(CURRENT_DATE()- INTERVAL 5 DAY, CURRENT_TIME()) order by symbol asc");


/*
$out = fopen('php://output', 'w');
fputcsv($out, array('this','is some', 'csv "stuff", you know.'), ";");
fclose($out);
*/

if (isset($_GET['export'])){
    $symbol = "";
    if (isset($_GET['symbol'])){
        $symbol=$_GET['symbol'];
        if (strlen($symbol)>32){
            exit;
        }
    }
    
    $today = date("Y-m-d H:i:s"); 
    $today=str_replace(" ","_",$today);
    $today=str_replace(":","-",$today);
    $space="";
    if ($symbol != "") {
        $space = "_";
    }
    $filename="IchimokuScan_" . $symbol . $space . $today . ".csv";
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    
    if ($symbol=="") {
        $z = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert order by timestamp desc");
    } else {
        $z = mysqli_query($db, "select * from " . TBL_PREFIX . "_2jcs_alert where symbol like '%" . $symbol . "%' order by timestamp desc");
    }
  echo "timestamp;period;symbol;buy;sell;alert1;alert2" . "\n";
        while($row = $z->fetch_assoc()) {              
            $timestamp = $row["timestamp"];
            $period = $row["period"];
            $symbol = $row["symbol"];
            $buy = $row["buy"]; $buy= str_replace(".", ",", $buy);
            $sell = $row["sell"]; $sell= str_replace(".", ",", $sell);
            $h1_ls_validated = $row["h1_ls_validated"];
            $m1_ls_validated = $row["m1_ls_validated"];
            echo $timestamp . ";" . $period . ";" . $symbol . ";" . $buy . ";" . $sell . ";" . $h1_ls_validated . ";" . $m1_ls_validated  . "\n";
        }
    //fclose($handle);
    $db->close();
    //echo "Ichimoku Ultimate Trader EA for Metatrader 5 - http://ichimoku-expert.blogspot.com";
    //header('Content-Type: application/csv');
    //header("Content-Type: text/plain");
    //header('Content-Disposition: attachment; filename="'.$filename.'";');
    
    //header("Content-Disposition: attachment; filename=\"$filename\"");
    //header("Content-Type: application/vnd.ms-excel");

    exit;
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
    <meta property="og:title" content="Ichimoku Ultimate Scanner" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="http://investdata.000webhostapp.com/alerts/" />
    <meta property="og:image" content="http://investdata.000webhostapp.com/alerts/ichimokuscannerlogo.png" />
    <meta property="og:description" content="Analyse financière automatisée des marchés financiers et aide à la prise de décision en matière d'investissement" />
    <meta property="og:site_name" content="Ichimoku Scanner" />
    <!--<meta property="article:published_time" content="2017-09-06T19:08:47+01:00" />
    <meta property="article:modified_time" content="2017-09-29T07:08:47+01:00" />-->
    <!--<meta property="article:section" content="Article Section" />
    <meta property="article:tag" content="Article Tag" />-->
    <meta property="fb:admins" content="100009891679331" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
</head>
<body>

<?php
?>

<div align="center">
    <table>
        <tr>
            <td><img src="ichimokuscannerlogo.PNG"/></td><td><h7><a href="./docs/ichimokuscanner-usdjpy.pdf">Click for PDF about USD/JPY detection</a><br/></h7><h7><a href="./docs/usdtry-detection.pdf">Click for PDF about USD/TRY detection</a><br/></h7><h7><a href="ichimokuscanneruserguide.pdf">Click for PDF User Guide</a><br/></td>
        </tr>
        <tr>
            <td><h4>Experimental Research Resources</h4></td><td></td>
        </tr>
        <tr>
            <td></td><td></td>
        </tr>
        <tr>
            <td></td><td></td>
        </tr>
    </table>
</div>
<div align="center">
<h4>&nbsp</h4>
<?php
  /*echo "<table cols='5'>";
    while($row = $s->fetch_assoc()) {
      
      echo "<tr>";
      ///echo "  <td>" . "<a href='./index.php?symbol=" . $row["symbol"] . "' class='btn btn-default' syle='width: 78px'>" . $row["symbol"] . "</a>" . "</td>";
      echo "<a href='./index.php?symbol=" . $row["symbol"] . "' class='btn btn-primary btn-sm' style='width: 128px'>" . $row["symbol"] . "</a>";
      echo "</tr>";
  } 
  echo "<tr><a href='./index.php?symbol=ALLSYMBOLS' class='btn btn-primary btn-sm' style='width: 128px'>ALL SYMBOLS</a></tr>";

  echo "<table>";*/
  
  echo "<label for='sp1'>Symbol&nbsp&nbsp</label>";
  echo "<select id='sp1' class='selectpicker' onchange='location = this.options[this.selectedIndex].value;'>";

  echo "<option value='index.php?symbol=ALLSYMBOLS'>ALL SYMBOLS</option>";  
  
  if (!isset($_GET['ALLVALIDATED'])) {
    echo "<option value='index.php?ALLVALIDATED'>ALL VALIDATED</option>";  
  } else {
    echo "<option selected value='index.php?ALLVALIDATED'>ALL VALIDATED</option>";  
  }

  if (!isset($_GET['ALLVALIDATEDCROSSINGKUMOWHILEUP'])) {
    echo "<option value='index.php?ALLVALIDATEDCROSSINGKUMOWHILEUP'>ALL VALIDATED CROSSING KUMO WHILE UP</option>";  
  } else {
    echo "<option selected value='index.php?ALLVALIDATEDCROSSINGKUMOWHILEUP'>ALL VALIDATED CROSSING KUMO WHILE UP</option>";  
  }

  if (!isset($_GET['ALLNONVALIDATEDCROSSINGKUMOWHILEUP'])) {
    echo "<option value='index.php?ALLNONVALIDATEDCROSSINGKUMOWHILEUP'>ALL NON VALIDATED CROSSING KUMO WHILE UP</option>";  
  } else {
    echo "<option selected value='index.php?ALLNONVALIDATEDCROSSINGKUMOWHILEUP'>ALL NON VALIDATED CROSSING KUMO WHILE UP</option>";  
  }

  while($row = $s->fetch_assoc()) {
      if ($row["symbol"] != ""){
    if ($_GET['symbol'] == $row["symbol"]){
      echo "<option selected value='index.php?symbol=" . $row["symbol"] . "'>" . $row["symbol"] . "</option>";
    } else {
      echo "<option value='index.php?symbol=" . $row["symbol"] . "'>" . $row["symbol"] . "</option>";
    }
    }
      
  }
  echo "</select>";

?>

<br/>
<a href="./index.php?export" class="btn btn-primary">Export all data to CSV</a>

<!--   <a href="./index.php?novalidations" class="btn btn-primary">View data without validations</a>
    <a href="./index.php" class="btn btn-primary">View data with validations</a>
    <a href="./index.php?eightvalidations" class="btn btn-primary">View only 8/8 validations</a> -->
</div>
    <div><center><br/><h3><?php echo "$title";?></h3></center></div>
<div class='table-responsive'>
    <table class="table table-bordered table-inverse table-hover" style="font-size: 12px; font-family:Verdana;">
        <tbody>
        <thead>
        <tr>
            <th><span style="color: gray">Timestamp (GMT+2)</span></th>
            <th><span style="color: gray">Period</span></th>
            <th><span style="color: gray">Symbol</span></th>
            <th><span style="color: gray">Criteria Detected (from 5 days ago to now, limit = 1000 records)</span></th>
        </tr>
        </thead>
        <?php
        
        $maxrow = 1000;
        $indexrow = 0;
        
        while($row = $r->fetch_assoc()) {
            $indexrow++;
            
            if ($indexrow > $maxrow)
            {
              break;
            }
              
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
                        $delta2 = number_format((double)$rates[$i]["ask"] - (double)$buy, 5);
                        if ($delta > 0) $symbol .= "<br/><b>delta=<font color='green'>+$delta</font></b>";
                        if ($delta < 0) $symbol .= "<br/>delta=<font color='orange'>$delta</font>";
                        if ($delta2 > 0) $symbol .= "<br/><b>delta2=<font color='green'>+$delta2</font></b>";
                        if ($delta2 < 0) $symbol .= "<br/>delta2=<font color='orange'>$delta2</font>";
                    }
                }
            }

            $googlelink = "<a target='_blank' href='https://finance.google.com/finance?q=" . $symbol . "'>$symbol</a>";

            if ($symbolfound == false){
                $symbol = "<b>" . $googlelink . "</b>";
            }

            $h1_ls_validated = "<span style='color:white'>" . $h1_ls_validated . "</span><br/><span style='color:gray'>buy=$buy sell=$sell spread=" . number_format($buy-$sell, 5) . "</span>";

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
    
<div align="center">
    <a href='mailto:investdatasystems@yahoo.com'>Click here to send us an E-mail</a></h4>
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="NNR8W5H23XEZS">
        <input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
        <img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
    </form>
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
