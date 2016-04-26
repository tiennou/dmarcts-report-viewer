<?php

// dmarcts-report-viewer - A PHP based viewer of parsed DMARC reports.
// Copyright (C) 2016 TechSneeze.com and John Bieling
//
// Available at:
// https://github.com/techsneeze/dmarcts-report-viewer
//
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program.  If not, see <http://www.gnu.org/licenses/>.
//
//####################################################################
//### configuration ##################################################
//####################################################################

// Copy dmarcts-report-viewer-config.php.sample to 
// dmarcts-report-viewer-config.php and edit with the appropriate info
// for your database authentication and location.

//####################################################################
//### functions ######################################################
//####################################################################

function format_date($date, $format) {
	$answer = date($format, strtotime($date));
	return $answer;
};

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
		$reportlist[] =  "      <td class='right'>". format_date($row['mindate'], $date_output_format). "</td>";
		$reportlist[] =  "      <td class='right'>". format_date($row['maxdate'], $date_output_format). "</td>";
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
		$reportdata[] = "<div class='center reportdesc'><p> Report from ".$row['org']." for ".$row['domain']."<br>(". format_date($row['mindate'], "r" ). " - ".format_date($row['maxdate'], "r" ).")</p></div>";
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
            $hostname = lookupHostname($ip, TRUE);
		}
		if ( $row['ip6'] ) {
            $ip = inet_ntop($row['ip6']);
            $hostname = lookupHostname($ip, FALSE);
		}

		$reportdata[] = "    <tr class='".$status."'>";
		$reportdata[] = "      <td>". $ip. "</td>";
		$reportdata[] = "      <td>". $hostname. "</td>";
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

function lookupHostname($ip, $ip4 = TRUE) {
    global $dnscache;

    if (array_key_exists($ip, $dnscache)) return $dnscache[$ip];

    // Need to lookup and insert into DB 
    $hostname = gethostbyaddr($ip);


    //if (!$hostname || $hostname == $ip ) return $ip;
    // Insert into DB, cache and return
    $dnscache[$ip] = $hostname;
    if ($ip4) {
        $ip = ip2long($ip);
        $query = "INSERT INTO dnscache SET ip4='$ip', hostname='$hostname'";
    } else {
        $ip = inet_pton($ip);
        $query = "INSERT INTO dnscache SET ip6='$ip', hostname='$hostname'";
    }
    global $mysqli;
    $mysqli->query($query) or die("Query failed: ".$mysqli->error." (Error #" .$mysqli->errno.")");

    return $hostname;
}



//####################################################################
//### main ###########################################################
//####################################################################

// The file is expected to be in the same folder as this script, and it
// must exist.
include "dmarcts-report-viewer-config.php";

// Make a MySQL Connection using mysqli
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
	echo "Error: Failed to make a MySQL connection, here is why: \n";
	echo "Errno: " . $mysqli->connect_errno . "\n";
	echo "Error: " . $mysqli->connect_error . "\n";
	exit;
}

define("BySerial", 1);
define("ByDomain", 2);
define("ByOrganisation", 3);

// Get allowed reports and cache them - using serial as key
$allowed_reports = array();
# Include the rcount via left join, so we do not have to make an sql query for every single report.
$sql = "SELECT report.* , sum(rptrecord.rcount) as rcount FROM `report` LEFT Join rptrecord on report.serial = rptrecord.serial group by serial order by mindate";
$query = $mysqli->query($sql) or die("Query failed: ".$mysqli->error." (Error #" .$mysqli->errno.")");
while($row = $query->fetch_assoc()) {
	//todo: check ACL if this row is allowed
	if (true) {
		//add data by serial
		$allowed_reports[BySerial][$row['serial']] = $row;
		//make a list of serials by domain and by organisation
		$allowed_reports[ByDomain][$row['domain']][] = $row['serial'];
		$allowed_reports[ByOrganisation][$row['org']][] = $row['serial'];
	}
}

// Get DNS cache
$dnscache = [];
$sql = "SELECT * FROM `dnscache`";
$query = $mysqli->query($sql) or die("Query failed: ".$mysqli->error." (Error #" .$mysqli->errno.")");
while($row = $query->fetch_assoc()) {
    if ($row['ip4']) $dnscache[long2ip($row['ip4'])] = $row['hostname'];
    if ($row['ip6']) $dnscache[inet_ntop($row['ip6'])] = $row['hostname'];
}


if(isset($_GET['report']) && is_numeric($_GET['report'])){
	$reportid=$_GET['report'];
}elseif(!isset($_GET['report'])){
	$reportid=false;
}else{
	die('Invalid Report ID');
}
// Generate Page with report list and report data (if a report is selected).
echo tmpl_page( ""
	.tmpl_reportList($allowed_reports)
	.tmpl_reportData($reportid, $allowed_reports )
);
?>
