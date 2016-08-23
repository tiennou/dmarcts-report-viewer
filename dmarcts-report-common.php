<?php

//####################################################################
//### defines ########################################################
//####################################################################

define("BySerial", 1);
define("ByDomain", 2);
define("ByOrganisation", 3);

//####################################################################
//### utility functions ##############################################
//####################################################################

function util_formatDate($date, $format) {
    $answer = date($format, strtotime($date));
    return $answer;
};

//####################################################################
//### template functions #############################################
//####################################################################

function tmpl_reportList($allowed_reports) {
    $reportlist[] = "";
    $reportlist[] = "<!-- Start of report list -->";

    $reportlist[] = "<h1>DMARC Reports</h1>";
    $reportlist[] = "<table class='reportlist'>";
    $reportlist[] = "  <thead>";
    $reportlist[] = "    <tr>";
    $reportlist[] = "      <th>Start Date</th>";
    $reportlist[] = "      <th>End Date</th>";
    $reportlist[] = "      <th>Domain</th>";
    $reportlist[] = "      <th>Reporting Organization</th>";
    $reportlist[] = "      <th>Report ID</th>";
    $reportlist[] = "      <th>Messages</th>";
    $reportlist[] = "    </tr>";
    $reportlist[] = "  </thead>";

    $reportlist[] = "  <tbody>";

    foreach ($allowed_reports[BySerial] as $row) {
        $date_output_format = "r";
        $reportlist[] =  "    <tr>";
        $reportlist[] =  "      <td class='right'>". util_formatDate($row['mindate'], $date_output_format). "</td>";
        $reportlist[] =  "      <td class='right'>". util_formatDate($row['maxdate'], $date_output_format). "</td>";
        $reportlist[] =  "      <td class='center'>". $row['domain']. "</td>";
        $reportlist[] =  "      <td class='center'>". $row['org']. "</td>";
        $reportlist[] =  "      <td class='center'><a href='?report=". $row['serial']. "#rpt". $row['serial']. "'>". $row['reportid']. "</a></td>";
        $reportlist[] =  "      <td class='center'>". $row['rcount']. "</td>";
        $reportlist[] =  "    </tr>";
    }
    $reportlist[] =  "  </tbody>";

    $reportlist[] =  "</table>";

    $reportlist[] = "<!-- End of report list -->";
    $reportlist[] = "";

    #indent generated html by 2 extra spaces
    return implode("\n  ",$reportlist);
}

function tmpl_reportData($reportnumber, $allowed_reports) {

    if (!$reportnumber) {
        return "";
    }

    $reportdata[] = "";
    $reportdata[] = "<!-- Start of report rata -->";

    if (isset($allowed_reports[BySerial][$reportnumber])) {
        $row = $allowed_reports[BySerial][$reportnumber];
        $reportdata[] = "<div class='center reportdesc'><p> Report from ".$row['org']." for ".$row['domain']."<br>(". util_formatDate($row['mindate'], "r" ). " - ".util_formatDate($row['maxdate'], "r" ).")</p></div>";
    } else {
        return "Unknown report number!";
    }

    $reportdata[] = "<a id='rpt".$reportnumber."'></a>";
    $reportdata[] = "<table class='reportdata'>";
    $reportdata[] = "  <thead>";
    $reportdata[] = "    <tr>";
    $reportdata[] = "      <th>IP Address</th>";
    $reportdata[] = "      <th>Host Name</th>";
    $reportdata[] = "      <th>Message Count</th>";
    $reportdata[] = "      <th>Disposition</th>";
    $reportdata[] = "      <th>Reason</th>";
    $reportdata[] = "      <th>DKIM Domain</th>";
    $reportdata[] = "      <th>Raw DKIM Result</th>";
    $reportdata[] = "      <th>SPF Domain</th>";
    $reportdata[] = "      <th>Raw SPF Result</th>";
    $reportdata[] = "    </tr>";
    $reportdata[] = "  </thead>";

    $reportdata[] = "  <tbody>";

    global $mysqli;
    $sql = "SELECT * FROM rptrecord where serial = $reportnumber";
    $query = $mysqli->query($sql) or die("Query failed: ".$mysqli->error." (Error #" .$mysqli->errno.")");
    while($row = $query->fetch_assoc()) {
        $status="";
        if (($row['dkimresult'] == "fail") && ($row['spfresult'] == "fail")) {
            $status="red";
        } elseif (($row['dkimresult'] == "fail") || ($row['spfresult'] == "fail")) {
            $status="orange";
        } elseif (($row['dkimresult'] == "pass") && ($row['spfresult'] == "pass")) {
            $status="lime";
        } else {
            $status="yellow";
        };

        if ( $row['ip'] ) {
            $ip = long2ip($row['ip']);
        }
        if ( $row['ip6'] ) {
            $ip = inet_ntop($row['ip6']);
        }

        $reportdata[] = "    <tr class='".$status."'>";
        $reportdata[] = "      <td>". $ip. "</td>";
        $reportdata[] = "      <td>". gethostbyaddr($ip). "</td>";
        $reportdata[] = "      <td>". $row['rcount']. "</td>";
        $reportdata[] = "      <td>". $row['disposition']. "</td>";
        $reportdata[] = "      <td>". $row['reason']. "</td>";
        $reportdata[] = "      <td>". $row['dkimdomain']. "</td>";
        $reportdata[] = "      <td>". $row['dkimresult']. "</td>";
        $reportdata[] = "      <td>". $row['spfdomain']. "</td>";
        $reportdata[] = "      <td>". $row['spfresult']. "</td>";
        $reportdata[] = "    </tr>";
    }
    $reportdata[] = "  </tbody>";
    $reportdata[] = "</table>";

    $reportdata[] = "<!-- End of report rata -->";
    $reportdata[] = "";

    #indent generated html by 2 extra spaces
    return implode("\n  ",$reportdata);
}

function tmpl_page ($body) {
    $html = array();

    $html[] = "<!DOCTYPE html>";
    $html[] = "<html>";
    $html[] = "  <head>";
    $html[] = "    <title>DMARC Report Viewer</title>";
    $html[] = "    <link rel='stylesheet' href='default.css'>";
    $html[] = "  </head>";

    $html[] = "  <body>";

    $html[] = $body;

    $html[] = "  <div class='footer'>Brought to you by <a href='http://www.techsneeze.com'>TechSneeze.com</a> - <a href='mailto:dave@techsneeze.com'>dave@techsneeze.com</a></div>";
    $html[] = "  </body>";
    $html[] = "</html>";

    return implode("\n",$html);
}

//####################################################################
//### database functions #############################################
//####################################################################

// The file is expected to be in the same folder as this script, and it
// must exist.
include "dmarcts-report-config.php";

// Make a MySQL Connection using mysqli
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
}

?>
