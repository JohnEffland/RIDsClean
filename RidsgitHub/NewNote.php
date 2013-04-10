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
* Form to enter new RID
* 
* 2009-03-04 jee genesis is NewNote.php, Ver 1.8
* 2009-03-05 jee changed all text from Task to RID
* 2009-03-10 2.1 jee continued working on this...
* 2009-03-12 2.1 jee added Responder and RID closed
* 2009-03-16 2.2 jee more work
* 2009-03-17 2.3 jee removed state machine, can't get that working and returned to boolean stats.
* 2009-03-18 2.31 jee updated character set from UTF-8 to ISO-8859-1.  Otherwise, text cut from 
*                     outlook in HTML format contains A's with ^ over them.
* 2009-03-19 2.32 jee now using $oHTML->sgetPageHeader
* 2009-03-26 2.33 jee cleaned up header
* 2009-03-27 2.34 changed "Return to main form" to Return to Main Screen"
* 2009-08-10 2.4 jee removed require database here
* 2009-11-12 2.5 jee updated to use project number
* 2009-11-13 3.0 jee final testing for project number
* 2009-11-14 3.1 jee now uses passwords in URL rather than easily guessed passwords.
* 2009-11-18 3.2 jee Bug - password not passed in "return to main screen" link
*        
*  
*/
$version = "3.2 (2009-11-18)";

define("DEBUG", false);
define("PHPINFO", false);
define("sTITLE","Enter RID Notes");


// size of notes text areas
define("NOTE_DESC_COLUMNS",100);
define("NOTE_DESC_ROWS",25);

define(TIME_RELOAD_ON_ERROR,5);
define(TIME_RELOAD_AFTER_DB_POST, 0);
$ReloadTime = TIME_RELOAD_AFTER_DB_POST;

error_reporting(E_ALL ^ E_NOTICE);
if (DEBUG) error_reporting(E_ALL);

// cookies are saved by reloading the same page
session_start();

require 'RidLib.php';

// define comments for right side of input boxes
// these are modified with errors
$sNameError = "";
$sRIDClosedError = "";
$sNotesError = "";
$bStateHasName = false;
$bStateHasRidComplete = false;
$bStateHasNotes = false;
$bStateRetry = false;
$bStateCookieSet = false;
$bStatePosttoDb = false;

// instantiate the state class
// constructor initializes state
//$state = new NewNoteState();

// clean up the post array with slashes
foreach($_POST as $key => $val) 
{
   $_POST[$key] = stripper($_POST[$key]);
}

if (!isset($_GET['RIDNum']))
{
	echo "Error in routine 'RID Notes' Version $version:<br>";
	echo "No RID number passed to this routine!";
	exit;
}

// get the project password from the URL
if (isset($_GET['type']))
{
	$ProjPassword = $_GET['type'];
}
else
{
	exit("<P>Malformed URL.<br><br>Program ends.");
}


// cookie processing - if EntryBy was set the first time through form, store that name in a cookie
if(isset($_GET['EntryBy']))
{
	// if EntryBy is set, try setting the cookie.
	if (bsetCookieEntryBy($_GET['EntryBy']))
	{
	  
		// don't show output...this speeds up refresh
		//$state->setFlag('cookie_found', true);
		$bStateCookieSet = true;
		if (DEBUG) echo "<P>Cookie Set...RID table will reload now in " . TIME_RELOAD_AFTER_DB_POST . 
		     " seconds to $lnkRID_EVENTS ... </P>\r";
	}
	else
	{
		//$state->setFlag('cookie_found', false);
		$bStateCookieSet = false;
		//echo "<P>Error, can't set cookie to store your name!<br>Page will continue in TIME_RELOAD_ON_ERROR secs";	
	}
	echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"" . TIME_RELOAD_AFTER_DB_POST . 
		     "; URL=$lnkRID_EVENTS?type=" . $ProjPassword . "&amp;RIDNum=" . $_GET['RIDNum'] . "\">\r";
}
else
{
	// no "EntryBy" param, (which occurs during cookie setting) so get the old one
	// stored in cookie
	//$state->setFlag('cookie_found', bgetCookieEntryBy($EntryBy));
	$bStateCookieSet = bgetCookieEntryBy($EntryBy);
	if (DEBUG)
	{
		if ($bStateCookieSet)
		{
			echo "<P>Name found in cookie = '" . $EntryBy . "'";
		} 
		else
		{
			echo "<P>No name found in cookie '" . $EntryBy . "'";
		} 
	}
}

// generate the page header
if (empty($oHTML))
{
	$oHTML = new CHTML();
}

$sHTML = $oHTML->sgetPageHeader("RID Note");
echo $sHTML;
echo "<BODY>\r";
echo ("\t\t<FORM METHOD=\"POST\" ACTION=\"$ThisCode?type=" . $ProjPassword . "&amp;RIDNum=" . $_GET['RIDNum'] . "\">");

// private constants for this module
$LEN_OF_RID_DESCRIPTION_FIELD = 50;
$sbDOC_STATUS = "sbDocStatus";
$sError = "";

try
{
	$odbRIDs = new CdbRIDs($DatabaseServer, $dbName, $UserID, $Password, $ProjPassword);
}
catch(Exception $e)
{
	echo "<br>Error in routine 'Rids.php': Error opening database object<br>";
	echo($e->getMessage());
	exit("<br><br>Program ends.");
}

// this  block runs after the code below.
if ($_POST['btnAddNewEvent'])
{
	if (PHPINFO) phpinfo(INFO_VARIABLES);
	// it checks the fields and then adds a new RID to the database
	if (!isset($_POST['EntryBy']) or strlen($_POST['EntryBy']) == 0)
	{
		//$state->setFlag('has_name', false);
		$bStateHasName = false;
		$sNameError = "<- You must enter your last name!";
	}
	else
	{
		// at this point name entered
		$sNameError = "";
		//$state->setFlag('has_name', true);
		$bStateHasName = true;
		$EntryBy = $_POST['EntryBy'];
		if (DEBUG) echo "<P>Name set $EntryBy";	
	}
		
	if (($_POST['chkbRIDComplete']) == "on")
	{
		// RID's complete
		$chkbRIDComplete = "CHECKED";
		//$state->setFlag('has_RID_complete', true);
		$bStateHasRidComplete = true;
		if (DEBUG) echo "<P>Rid Complete set to " . $bStateHasRidComplete . " and bStateHasName set to " . $bStateHasName;	
		if ($bStateHasName)
		{
			if (DEBUG) echo "<br>RID complete checked, posting to database";
			$DateUpdated = date("Y-m-d H:i:s");
			if (!$odbRIDs->setRIDComplete($_GET['RIDNum'], $DateUpdated , $_POST['EntryBy'], $DateUpdated))
			{
				// SQL job failed
					echo "<P>Database update failed with odbRIDs->setRIDComplete!";
					echo $odbRIDs->sGetError();
					exit;
			}
			if (DEBUG) echo "<br>Database updated with RID complete";
		}
	}
	else
	{
		$chkbRIDComplete = "";
		//$state->setFlag('has_RID_complete', false);
		$bStateHasRidComplete = false;
	}
		
	// user just wanted to update notes
	if (isset($_POST['Notes']) and strlen($_POST['Notes']) > 0)
	{
		$Notes = $_POST['Notes'];
		//$state->setFlag('has_notes', true);
		$bStateHasNotes = true;
		if (DEBUG) echo "<P>Notes set to $bStateHasNotes";	
		if($bStateHasName)
		{
			// update notes
			$DateUpdated = date("Y-m-d H:i:s");	
			if (!$odbRIDs->setEventNotes($_GET['RIDNum'], $_POST['EntryBy'], $DateUpdated, $_POST['Notes']))
			{
				// SQL job failed
				echo "<P>Database update failed with odbRIDs->setEventNotes!<P>";
				echo $odbRIDs->sGetError();
				exit;
			}	
		}
	}

	//if (DEBUG) $state->ShowAllStates();
	if ($bStateHasName and ($bStateHasRidComplete or $bStateHasNotes))
	{
		if (DEBUG) "<P>Sufficient information";
		//$state->setFlag('post_to_db', true);
		$bStatePosttoDb = true;
		$sNotesError = "";
		$RIDClosedError = "";
		$sErrorTitle = "";
		$ReloadTime = TIME_RELOAD_AFTER_DB_POST;
		if (DEBUG) echo "Line 189, has complete or notes";
	}
	elseif ($bStateHasRidComplete or $bStateHasNotes)
	{
		if (DEBUG) "<P>Just missing name...";
		$sNotesError = "";
		$RIDClosedError = "";
		$sErrorTitle = "Check below for errors!";
	}
	else
	{
		if (DEBUG) "<P>Missing Notes or Task Complete";
		$sErrorTitle = "Check below for errors!";
		if (!$bStateHasNotes) $sNotesError = "<- You must enter either a note or check the RID closed box!";
		if (!$bStateHasRidComplete)  $RIDClosedError = "< - You must enter either a note or check the RID closed box!";
		//$state->setFlag('retry', true);
		$bStateRetry = true;
	}
	if ($bStatePosttoDb)
	{
		// sufficient information, so reload this page again to save cookie 
		if (DEBUG)
		{	
			echo "<P>Reloading same page to set cookie..." . $ReloadTime . " seconds to $lnkRID_NEW_EVENT ... </P>\r";
			echo "<P>META HTTP-EQUIV contents are \"" . $ReloadTime . "; URL=$lnkRID_NEW_EVENT?type=" . $ProjPassword . "&amp;RIDNum=" . $_GET['RIDNum'] . 
			     "&EntryBy=" . $_POST['EntryBy'] . "\">\r";
		}
		echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"" . $ReloadTime . "; URL=$lnkRID_NEW_EVENT?type=" . $ProjPassword . "&amp;RIDNum=" . $_GET['RIDNum'] . 
		     "&EntryBy=" . $_POST['EntryBy'] . "\">\r";
	}
}

// this code runs before the post button is pressed
if (DEBUG) echo "<P>Starting heading...";

echo "\r";
echo ("<TABLE BORDER=0 CELLPADDING=5 WIDTH=\"100%\">\r");
echo ("\t<TR>\r");
echo ("\t\t<TD class=\"blockheading\">Enter notes for this RID<br><big>$sErrorTitle</big></TD>\r");

// setup the action for the post method of this form
echo "\t\t<TD><INPUT TYPE=\"Submit\" NAME=\"btnAddNewEvent\" VALUE=\"Post a note or close this RID\"><br></TD>\r";

echo "\t<TD><A class=\"big\" HREF=\"" . $lnkRID_MAIN . "?type=" . $ProjPassword . "#" . $_GET['RIDNum'];
echo "\">Return to Main Screen</A></TD>\r";
echo "</TABLE><br>\r\r";

// generate RID info table

$sHTML = $oHTML->sgetTopHeader(TRUE);
$RIDs = $odbRIDs->getRIDs($_GET['RIDNum']);
$sHTML .= $oHTML->sgetRIDTable($RIDs);
echo ($sHTML);

$DateCompleted = date("Y-m-d H:i:s");

// now build the input boxes using A table
// spacing between labels and input cells is obtained from blank column
echo ("\r<TABLE ID=\"tblNotesInput\">\r");

// notes text box
echo "<TR>\r";
echo "<TD CLASS=\"colLabel\">Enter Responder's Solution or Notes:\r";
echo "<br><br><SMALL>&nbsp&nbsp (Responder can enter proposed solution<br>or originator can enter additional notes here)</SMALL></TD>\r";
echo "<TD CLASS=\"colInputFields\">";
echo "<TEXTAREA NAME=\"Notes\" ROWS=" . NOTE_DESC_ROWS . " COLS=" . NOTE_DESC_COLUMNS . ">$Notes</TEXTAREA>";
echo "&nbsp&nbsp&nbsp<big>$sNotesError</big></TD></TR>\r";
	
echo "<TR>\r";
echo "<TD CLASS=\"colLabel\">RID closed:</TD>\r";
echo "<TD CLASS=\"colInputFields\">";
echo "<INPUT TYPE=\"checkbox\"  $chkbRIDComplete NAME=\"chkbRIDComplete\" >";
echo "<SMALL>&nbsp&nbsp (Only originator of RID should check this)&nbsp&nbsp</SMALL><big>$RIDClosedError</big></TD></TR>\r";

echo "<TR>\r";
	echo "<TD CLASS=\"colLabel\">Your Last Name:</TD>\r";
echo "<TD CLASS=\"colInputFields\"><input SIZE = 35 type=\"Text\" name=\"EntryBy\" value=\"$EntryBy\">";
echo "<SMALL>&nbsp&nbsp&nbsp(Required)&nbsp&nbsp</SMALL><big>$sNameError</big></TD></TR>\r";

echo ("</TABLE>\r");
echo "<br><br><br><SMALL>Software Ver $version &nbsp&nbsp&nbsp RidLib Ver: " . sGetVersionForRidLib() . "</SMALL>\r";
echo "</FORM>\r";
echo "</BODY>\r";
echo "</HTML>\r";
?>
