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
* Ver 2.0  -- 2009-03-05 jee genesis is Events2 Ver 1.97
* 2009-03-10 2.0 jee Continuing to update for RIDs
* 2009-03-17 2.1 jee Updated text about closed RID, updated checking for unique records.
* 2009-03-26 2.2 jee Cleanued up table header
* 2009-03-27 2.31 changed "Return to main form" to Return to Main Screen"
* 2009-08-10 2.4 jee removed require database here
* Wed Nov 11 2009 18:07:21 GMT-0500 (EST) need to pass the project number to this routine.
* 2009-11-13 3.0 jee final testing using project number
* 2009-11-14 3.1 jee now uses project password
* 2009-11-18 3.2 jee cleaned up bug when passing password to "NewNote.php"
*  
*/
$version = "3.2 (2009-11-18)";

define("DEBUG", false);
define("sTITLE","RID Notes");

error_reporting(E_ALL ^ E_NOTICE);
if (DEBUG) error_reporting(E_ALL);

session_start();

//require '../database.php';
require 'RidLib.php';

// clean up the get array with slashes
foreach($_GET as $key => $val) 
{
   $_GET[$key] = stripper($_GET[$key]);
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

try
{
	// include the project type, which is then saved in the object
	$odbRIDs = new CdbRIDs($DatabaseServer, $dbName, $UserID, $Password, $ProjPassword);
}
catch(Exception $e)
{
	echo "<br>Error in routine 'Events2.php': Error opening database object<br>";
	echo($e->getMessage());
	exit("<br><br>Program ends.");
}

// TODO Thu Nov 12 2009 10:17:44 GMT-0500 (EST) CHTML CONSTRUCTOR DOESN'T yet RETURN AN EXCEPTION
try
{
	// include the project type, which is then saved in the object
	$oHTML = new CHTML();
}
catch(Exception $e)
{
	echo "<br>Error in routine 'Events2.php': Error opening CHTML object<br>";
	echo($e->getMessage());
	exit("<br><br>Program ends.");
}

$sHTML = $oHTML->sgetPageHeader(sTITLE);
echo $sHTML;

if (!$_GET['RIDNum'])
{
	echo "Error in routine 'Events2.php':<br>\r";
	echo "No RID number passed to this routine!\r";
	exit("<br><br>Program ends.");
}

// setup the action for the post method of this form 
echo "<FORM METHOD=\"post\" ACTION=\"$lnkRID_NEW_EVENT?type=" . $ProjPassword . "&amp;RIDNum=" . $_GET['RIDNum'] . "\">\r";

// repeat a number of queries to determine what information was entered for the job
if (DEBUG) echo "<b>Events2</b>: getting event notes...<br />";
$result = $odbRIDs->getEventNotes($_GET['RIDNum']);
if (DEBUG) 
{
	echo "<b>Events2</b>:getEventNotes returns result...";
	print_r ($result);
}

if ($result != false)
{
	foreach ($result as $row)
	{
		$rsEvents[$row["DateUpdated"]] = array
	  (
			'date' => $row["DateUpdated"],
			'item' => $row["EntryBy"] . " added notes.",
			'notes' => $row["Notes"]
	  );
	  //echo "<P>Events... rsEvents in foreach loop:</P>";
		//print_r ($rsEvents);
	}
}
//echo "<br>Events... rsEvents just after notes query:<br>";
//print_r ($rsEvents);

// RID assignment
$result = $odbRIDs->getEventAssignment($_GET['RIDNum']);

if ($result != false)
{
	// find person presently assigned
	//$NowAssigned = $odbRIDs->getRIDPeople($_GET['RIDNum'], $odbRIDs->m_SHOW_ASSIGNED_TO);
	foreach ($result as $row)
 	{
 		$rsEvents[$row["DateUpdated"]] = array
	  (
			'date' =>$row["DateUpdated"],
			'item' => "Assignment changed by " . $row["EntryBy"] . ".",
			'notes' => $row["Notes"]
	  );
	}
}

// change in estimated completion date
$result = $odbRIDs->getEventEstCompletion($_GET['RIDNum']);
if ($result != false)
{
	foreach ($result as $row)
 	{
 		$rsEvents[$row["DateUpdated"]] = array
	  (
			'date' => sKeyReturnUnique($row["DateUpdated"],$rsEvents),
			'item' => $row["EntryBy"] . " changed estimated completion date.",
			'notes' => $row["Notes"]
	  );
	}
}

// Completion date
try
{
	$result = $odbRIDs->getEventCompleted($_GET['RIDNum']);
}
catch (Exception $e)
{
	echo "<br>" . nl2br($e->getMessage()) . "<br>";
}

if ($result != false)
{
	foreach ($result as $row)
 	{
 		// make this key unique, because there will be more than one when notes are entered
 		// at the same time as the completion date.
 		$rsEvents[sKeyReturnUnique($row["DateUpdated"],$rsEvents)] = array
	  (
			'date' => sKeyReturnUnique($row["DateUpdated"],$rsEvents),
			'item' => $row["EntryBy"] . " Closed RID.",
			'notes' => ""		// notes are entered separately above.
	  );
	}
}

//echo "<br>Events.. just before table build:<br>";
//print_r ($rsEvents);
// initial heading for page
echo ("\r<TABLE BORDER=0 CELLPADDING=5 WIDTH=\"95%\" BGCOLOR=white>\r");
echo ("\t<TR><TD><H2>Responses and notes listing</H2></TD>\r");

// Build the button to add another event
// TODO Button shrinks when hover over adjacent hyperlink
echo "\t<TD><INPUT TYPE=\"Submit\" NAME=\"new\" VALUE=\"Add notes or respond to this RID\"></TD>\r";

echo "\t<TD><A class=\"big\" HREF=\"" . $lnkRID_MAIN . "?type=" . $ProjPassword . "#" . $_GET['RIDNum'];
echo "\">Return to Main Screen</A></TD>\r";
echo "</TABLE><br>\r";
//echo "<br>";

// generate the RID table header for this particular RID

if (DEBUG) echo "<P>Events2: ready to get top header:<br>";
$sHTML = $oHTML->sgetTopHeader(TRUE);

if (DEBUG) echo "<P>Events2: getting Rids...<br>";
try
{
	$RIDs = $odbRIDs->getRIDs($_GET['RIDNum']);
}
catch(Exception $e)
{
	echo "<br>Error in routine 'Events2.php':<br>";
	echo($e->getMessage());
	exit("<br><br>Program ends.");
}

if (DEBUG) echo "<p>Events2: Got Rids...<p>Events2: getting rid table ...<br>";
try
{
	$sHTML .= $oHTML->sgetRIDTable($RIDs);
}
// TODO TEST THIS!
catch(Exception $e)
{
	echo "<br>Error in routine 'Events2.php':<br>";
	echo($e->getMessage());
	exit("<br><br>Program ends.");
}
if (DEBUG) echo "<p>Events2: got rid table ...<br>";
echo ($sHTML);

//print_r ($rsEvents);

if (count($rsEvents) >= 1)
{
	// sort the output table by date with most recent at beginning
	krsort($rsEvents);

	echo "\r<P>\r";
	echo "\r<DIV ALIGN = \"CENTER\"><H2>Events:</H2></DIV>\r";

	// build the events table
  echo ("\r<TABLE BORDER=\"1\" CELLPADDING=5 WIDTH=\"100%\" BGCOLOR=white>\r");
	echo ("\t<TR><TD ALIGN=center WIDTH=\"18%\"><B>Date</B></TD>\r");
	echo ("\t\t<TD WIDTH=\"25%\"><B>Item</B></TD>\r");
	echo ("\t\t<TD WIDTH=\"60%\"><B>Responses / Notes</B></TD>\r");

	// print out the table rows
	foreach ($rsEvents as $rsOut)
	{
	  printf("\t<TR>\r\t\t<TD><FONT SIZE=2><B>%s</B></FONT>\r\t\t<TD>%s</TD>\r\t\t<TD>%s\t\t</TD>\r", 
	         $rsOut['date'], $rsOut['item'], $oHTML->sFormatNotes($rsOut['notes'],100,15));
	  printf("\t</TR>\r");
	}
  echo ("</TABLE>\r");
}
else
{
	echo "\r<H3><br>No notes found for this RID.</H3>\r";
	echo "<P>\r";
}

echo "<br><br><br><SMALL>Software Ver: $version&nbsp&nbsp&nbsp RidLib Ver: " . sGetVersionForRidLib() . "</SMALL>\r";
echo "</form>\r";
echo "</body>\r";
echo "</HTML>"
?>
