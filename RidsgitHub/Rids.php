<?php
/*--------------------------------------------------------------------*/
/*   Copyright (C) 2013                                               */
/*   Associated Universities, Inc. Washington DC, USA.                */
/*                                                                    */
/*   This program is free software; you can redistribute it and/or    */
/*   modify it under the terms of the GNU General Public License as   */
/*   published by the Free Software Foundation; either version 2 of   */
/*   the License, or (at your option) any later version.              */
/*                                                                    */
/*   This program is distributed in the hope that it will be useful,  */
/*   but WITHOUT ANY WARRANTY; without even the implied warranty of   */
/*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    */
/*   GNU General Public License for more details.                     */
/*                                                                    */
/*   You should have received a copy of the GNU General Public        */
/*   License along with this program; if not, write to the Free       */
/*   Software Foundation, Inc., 675 Massachusetts Ave, Cambridge,     */
/*   MA 02139, USA.                                                   */
/*                                                                    */
/*   Correspondence concerning this software should be addressed to:  */
/*          Internet email: jeffland@nrao.edu.                        */
/*          Postal address: NTC                                       */
/*                          National Radio Astronomy Observatory      */
/*                          1180 Boxwood Estate Road                  */
/*                          Charlottesville, VA 22903      USA        */
/*--------------------------------------------------------------------*/
/*
 * URL: https://safe.nrao.edu/php/ntc/Rids/RidsGen/source/Rids.php?type=2
 * 
* 2009-03-04 3.0 jee Genesis is from Tasks3.php Ver 2.42
* 2009-03-07 3.0 jee more work
* 2009-03-10 3.0 jee more work
* 2009-03-17 3.1 jee added software docs link
* 2009-03-18 3.2 jee updated doc links, added logo and ref to schedule.
* 2009-03-18 3.21 jee updated RIDTrackingSysGuide.pdf by making RID all caps.
* 2009-03-19 3.22 jee now using $oHTML->sgetPageHeader
* 2009-03-26 3.23 jee Implemented number of notes.
* 2009-03-27 3.24 jee added my e-mail
* 2009-03-30 3.25 jee Added number of rids in dropdown box
* 2009-04-16 3.3  jee added pdf link in dropdown box (and also  links to documentation there)
* 2009-08-10 4.0  jee now works for EA ORR
* 2009-08-10 2.4 jee removed require database here
* 2009-09-09 2.4 jee just fixed the date from 2009-09-10 to the correct 2009-08-10
* 2009-09-09 5.0 jee now uses RIDProjects for different reviews
* Wed Nov 11 2009 17:40:51 GMT-0500 (EST) more work...
* 2009-11-13 5.0 jee final testing...
* 2009-11-14 5.1 jee now uses passwords in URL rather than easily guessed passwords.
* 2013-01-08 5.11 jee added note about web server outage
* 2013-01-09 5.12 jee minor text change re server outage
* 2013-01-10 5.13 jee note removed about server outage, also removed "Doc:" from section header 
* 2013-04-10 5.14 jee removed bad paths
* * 
*/
// TODO add status field for RID's completed...
// TODO THIS SQL INJECTION DOESN'T GET TRIMMED!: https://safe.nrao.edu/php/ntc/Rids/RidsGen/source/Rids.php?type=zkgZ4F27;select%20*%20from%20test;

$version = "5.14 (2013-04-10)";

require 'RidLib.php';

define("DEBUG", false);
if (DEBUG)
{
	error_reporting(E_ALL);
	echo "Debug mode, Error reporting changed to " . error_reporting();
}
else
{
	error_reporting(E_ALL ^ E_NOTICE);
} 

// must be called prior to any output to browser
session_start();

define("OUTAGE_NOTICE", "");
define("sbASSIGNED_TO_FILTER","sbRIDPeople");
define("HELP_FILE_FULL_PATH", "RIDTrackingSysGuide.pdf");
define("HELP_EMAIL_ADDRESS", "mailto: jeffland@nrao.edu?subject=Help with RID Management Program");
define("LOGO_FILE_FULL_PATH", "ALMA_Logo.jpg");

// store this URL for later retreival
$_SESSION["URLLast"] = sGetCurrentURL();

// variables to set the SELECTED parameter in the select box to the currently
// displayed RIDs

$selected_None = "";
$selected_OpenRids = "";
$selected_ClosedRIDs = "";
$selected_RecentActivity = "";
$selected_ActionItems = "";
$selected_ResumesOpen = "";
$selected_ResumesClosed = "";

// clean up the get array with slashes
foreach($_GET as $key => $val) {
   $_GET[$key] = stripper($_GET[$key]);
}

// get the project password from the URL
if (isset($_GET['type'])) {
	$ProjPassword = $_GET['type'];
} else {
	exit("<P>Malformed URL.<br><br>Program ends.");
}

try {
	$odbRIDs = new CdbRIDs($DatabaseServer, $dbName, $UserID, $Password, $ProjPassword);
} catch(Exception $e) {
	echo "<br>Error in routine 'Rids.php': Error opening database object<br>";
	echo($e->getMessage());
	exit("<br><br>Program ends.");
}

try {
	$TitlePage = $odbRIDs->getProjectTitle();
} catch(Exception $e) {
	echo "<br>Error in routine <b>'Rids.php'</b>: Error getting project title.";
	echo($e->getMessage());
	exit("<br>Program ends.");
}
if (!$TitlePage) {
	exit("<P>Invalid project number.<br><br>Program ends.");
}

if (empty($oHTML)) {
	$oHTML = new CHTML();
}

echo $oHTML->sgetPageHeader($TitlePage);
echo "<BODY>\r";

if (!$_GET['sortdef']) {
	// default to this if nothing entered for sortdef
	$_GET['sortdef'] = $odbRIDs->m_SHOW_OPEN_RIDS;
	$selected_OpenRids = "SELECTED";
}
elseif ($_GET['sortdef'] == $odbRIDs->m_SHOW_OPEN_RIDS) {
	$selected_OpenRids = "SELECTED";
}
elseif ($_GET['sortdef'] == $odbRIDs->m_SHOW_CLOSED_RIDS) {
	// show just the Closed RIDS
	$selected_ClosedRIDs = "SELECTED";
}
else {
	exit("<P>Malformed URL.<br><br>Program ends.");
}

if (isset($_GET['sbRIDPeople'])) {
	// names given in the URL
	$RIDPeople = $_GET['sbRIDPeople'];
}
else {
	//$RIDPeople = "";
}

// TODO Fri Nov 13 2009 13:56:26 GMT-0500 (EST) MOVE THIS TO HTML CLASS
echo ("\r<TABLE ID=\"tableHeader\">");
echo "\t<TR>\r";
echo "<TD WIDTH=\"10%\" ROWSPAN=\"2\" ALIGN=\"center\" ><IMG SRC=\"" . LOGO_FILE_FULL_PATH . "\" ALT=\"ALMA Rid Tracker\"";
echo " WIDTH=60 HEIGHT=90></TD>";

echo "\t\t<TD CLASS=\"titletext\" WIDTH=\"18%\">" . $TitlePage . "</TD>\r";

echo "\t\t<TD WIDTH=\"20%\"></TD><TD WIDTH=\"20%\">" . OUTAGE_NOTICE . "</TD>";
echo "<TD CLASS=\"ecenter\" WIDTH=\"20%\"><a href=\"" . HELP_FILE_FULL_PATH . "\"><b>Help File</b></a>\r";
echo "\t\t<br><br><a href=\"" . HELP_EMAIL_ADDRESS . "\"><b>Email for Help</b></a></TD></TR>\r";

// main select box
// first, get the number of open and closed Rids
try {
	$odbRIDs->getNumOpenAndClosedRids($NumRidsOpen, $NumRidsClosed);
}
catch(Exception $e) {
	echo "<p><b>Rids</b> Error:<br>" . $e->getMessage();
	exit ("<p>Program ends.<br>");
}
unset($odb);

echo "\t\t<TR><TD CLASS=\"titleRow\">Show: &nbsp&nbsp\r";
echo ("\t\t\t<select name=\"guidelinks\" class=\"guidelinks\" onChange=\"window.location=this.options[this.selectedIndex].value\">\r");
echo "\t\t\t<option $selected_OpenRids value=\"$ThisCode?type=$ProjPassword&amp;sortdef=$odbRIDs->m_SHOW_OPEN_RIDS\">$NumRidsOpen Open RID" . getS($NumRidsOpen) . "</option>\r";
echo "\t\t\t<option $selected_ClosedRIDs value=\"$ThisCode?type=$ProjPassword&amp;sortdef=$odbRIDs->m_SHOW_CLOSED_RIDS\">$NumRidsClosed Closed RID" . getS($NumRidsClosed) . "</option>\r";
echo ("\t\t\t<option disabled value=\"$ThisCode?type=$ProjPassword&amp;sortdef=$odbRIDs->m_SHOW_OPEN_RIDS\">----------------------------</option>\r");

// TODO add querying project table to get full path to these things
echo ("\t\t\t</select>\r");
echo "\t\t</TD>\r";

// button to add another RID
// don't use a submit button, because that is awkward with the browser's back button
$sHTMLButton = "\t\t<TD CLASS=\"RIDAddButton\"><INPUT TYPE=\"button\" VALUE=\"Add a RID\" OnClick=\"document.location.href='$lnkRID_NEW_RID?type=$ProjPassword'\"></TD>\r";
$sHTML = sprintf("\t\t<TD CLASS=\"RIDAddButton\"><INPUT TYPE=\"button\" VALUE=\"Add a RID\" OnClick=\"document.location.href='$lnkRID_NEW_RID?type=$ProjPassword'\"></TD>\r");

// get the list of originators
if ($_GET['sortdef'] == $odbRIDs->m_SHOW_OPEN_RIDS) {
	// get people with open RIDs
	try {
		$sRIDPeople = $odbRIDs->getRIDPeople(NULL, $odbRIDs->m_SHOW_ORIGINATOR, $odbRIDs->m_SHOW_OPEN_RIDS);
	}
	catch(Exception $e) {
		echo("<p>Error in routine 'Rids':<br>" . $e->getMessage());
		die ("<br>Program ends.<br>");
	}
} else {
	// get people with closed RIDs
	try {
		$sRIDPeople = $odbRIDs->getRIDPeople(NULL, $odbRIDs->m_SHOW_ORIGINATOR, $odbRIDs->m_SHOW_CLOSED_RIDS);
	}
	catch(Exception $e)	{
		echo("<p>Error in routine 'Rids':<br>" . $e->getMessage());
		die ("<br>Program ends.<br>");
	}
}


// Add unfiltered select element to the name list and merge
$sURL = $ThisCode . "?type=" . $ProjPassword;
$arRIDPeopleList = array ($sURL => "None");

// fill the select box with names and URL's
// to do this, fill the array with key values of URL's and values of names for select box
$sURL = "$ThisCode?type=$ProjPassword&amp;sortdef=";
if (DEBUG) {
	if (isset($_GET)) echo "<P>Get value is '" . $_GET['sortdef'] . "'";
	print_r ($_GET);
} 
$sURL .= $_GET['sortdef'] . "&amp;sbRIDPeople=";  // use &amp (in html only): http://www.htmlhelp.com/tools/validator/problems.html#amp
for ($i=0; $i <= sizeof($sRIDPeople)-1; $i++) {
	$arRIDPeopleList[ $sURL . $sRIDPeople[$i] ] = $sRIDPeople[$i];
}
  
if (is_null(sbASSIGNED_TO_FILTER)) {
	$sHTMLSelectBox = " is null";
} else {
	// fill the select box, and pass the name of the filter inside sbASSIGNED_TO_FILTER
	$sHTMLSelectBox = $oHTML->SelectBoxFill(sbASSIGNED_TO_FILTER, $arRIDPeopleList, 
	                                        $RIDPeople, 
	                                        $oHTML->m_SELECT_SUBMIT_WINDOW_LOCATION);
}

// add the button and select box
$sHTML .= sprintf("<TD><B>Filter by RID Originator:</B>");
$sHTML .= sprintf("%s",$sHTMLSelectBox);
$sHTML .= sprintf("</TD>");

//$sHTML .= sprintf("<TD CLASS=\"bottomNormal\" WIDTH=39%%>Sorted by Date Entered&nbsp(most recent first)</TD>\r");
$sHTML .= sprintf("</TR>\r");
$sHTML .= sprintf("</TABLE>\r");
echo $sHTML;
echo "<P></P>";

// here's the Rid table
$sHTML = sprintf("<TABLE ID=\"tableRIDs\">\r");
$sHTML .= sprintf("\t<TR ALIGN=center>\r");
// DOUBLE THE % SIGNS IN SPRINTF STATEMENTS! :
$sHTML .= sprintf("\t\t<TD CLASS=\"bottomNormal\" WIDTH=20%%>\r");

if (DEBUG) echo "<b>'Rids'</b> getting top header:<br>";
// get the header for the table and show the notes
$sHTML = $oHTML->sgetTopHeader(TRUE);
echo $sHTML;

// get the list of RIDs.
if (DEBUG) echo "<p><b>" . __METHOD__ . "</b>Calling odbRIDs->getRIDs with sortdef = '" . $_GET['sortdef'] . "' and Originator = '" . $RIDPeople . "'.";
try {
	$arRIDs = $odbRIDs->getRIDs($_GET['sortdef'], $RIDPeople);
}
catch(Exception $e) {
	echo("<p>Error in routine Rids:<br>" . $e->getMessage());
	die ("<br>Program ends.<br>");
}
if (DEBUG) echo "<p><b>" . __METHOD__ . "</b>arRIDs:<br>";
if (DEBUG) print_r($arRIDs);

// get the list of originators for the RIDs
// this will be used to fill in the drop-down box
try {
	$arNamesAssigned = $odbRIDs->getRIDPeople(null, $odbRIDs->m_SHOW_ORIGINATOR);
}
catch(Exception $e) {
	echo("<p>Error in routine Rids:<br>" . $e->getMessage());
	die ("<br>Program ends.<br>");
}
// Now build the RID table...
$sTableRIDs = $oHTML->sgetRIDTable($arRIDs, $arNamesAssigned, $_GET['sortdef'], sbASSIGNED_TO_FILTER, $sbRIDPeople);

if (!$sTableRIDs) {
	// no records?
	$sErrors =  $oHTML->sGetError("RIDs3.php");
	echo "</TABLE>\r";
	if (strlen($sErrors) == 0) 	{
		// no records to return
		echo "<H2><br>No RID records!</H2><br>\r";
	} else {
		// errors in query
		echo "<br><H3>Query error in routine 'RIDs3': <br><br>" . $sErrors . "</H3><br>\r";
	}
} else {
	// records returned	
	echo "$sTableRIDs";
}

// make values avalable each time script is run
// TODO Thu Nov 12 2009 17:47:06 GMT-0500 (EST) THIS SHOWS undefined var:
echo "<INPUT TYPE=hidden NAME=\"sbASSIGNED_TO_FILTER\" VALUE=\"$sbRIDPeople\">\r";
echo "<br><br><br><SMALL>Software Ver: $version&nbsp&nbsp&nbsp RidLib Ver: " . sGetVersionForRidLib() . "</SMALL>\r";
echo "</BODY>\r";
echo "</HTML>\r";
?>
