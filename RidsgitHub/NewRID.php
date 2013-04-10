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
/*Form to enter new RID information
* The form is run at least 3 times:
*   1) bFirstTime - first time through
*   2) bSubmissionNeedsChecked - user entered information, but present it again for final checking
*   3) bSumbmissionChecked - information ready for posting
* 
* 2009-03-04 2.0 jee genesis is NewRID1.php, Ver 1.6
*                		added test for $odbRIDs->getDocTitlesAndNums() failing 
* 2009-03-07 2.1 jee okay, this is working and tested up to db submission
* 2009-03-08 2.2 jee Added check submission before posting web page.  That is, allow user to see and approve their
*                    submission before actually posting the data.
* 2009-03-09 2.2 jee can't use $_SESSION because register globals is off
* 2009-03-10 2.2 jee got selectbox to work by sending it a key value, and got checking screen working.
*                now using NewRidSearch to include filters 
* 2009-03-19 2.3 jee now using $oHTML->sgetPageHeader
*                
*     States:	first time -> search -> first time -> needs checked -> needs posted -> posted
*     				first time -> search -> needs checked -> needs posted -> posted
*     				first time -> needs checked -> needs posted -> posted
*     
* 2009-03-27 2.31 changed "Return to main form" to Return to Main Screen"
* 2009-08-10 2.4 jee removed require database here
* 2009-09-09 3.0 jee updated to use project table
* 2009-11-13 3.1 jee got project table working, but needs more testing
* 2009-11-14 3.2 jee now uses passwords in URL rather than easily guessed passwords.
*     
*  
*/
// TODO 2009-03-07 get cookies working for NewRID form.
// TODO 2009-03-10 get state working as a hidden var.

$version = "3.2 2009-11-14";

define("DEBUG", false);
// Time in secs to reload main form
define(TIME_RELOAD_ON_ERROR,15);
define(TIME_RELOAD_AFTER_DB_POST, 4);
// width of RID description and Suggested Solution text areas
define("RID_DESC_COLUMNS",100);

// TODO Check this form for input with single quotes, etc.
// report all errors except notices
error_reporting(E_ALL ^ E_NOTICE);
if (DEBUG) error_reporting(E_ALL);

// define the state of this page
$arstate = array ("first time"=>1,"needs checked"=>2,"needs posted"=>3, "posted"=>4, "search"=>5);  
//$state = $arstate["first time"];

//phpinfo(INFO_VARIABLES);

require 'RidLib.php';

// clean up the get array with slashes
foreach($_GET as $key => $val) 
{
   $_GET[$key] = stripper($_GET[$key]);
}

// clean up the post array with slashes
foreach($_POST as $key => $val) 
{
   $_POST[$key] = stripper($_POST[$key]);
}

	// TODO get try catch working here

$oHTML = new CHTML();
$sHTML = $oHTML->sgetPageHeader("New RID");
echo $sHTML;
echo "<BODY>\r";

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
	$odbRIDs = new CdbRIDs($DatabaseServer, $dbName, $UserID, $Password, $ProjPassword);
}
catch(Exception $e)
{
	echo "<br>Error in routine 'Rids.php': Error opening database object<br>";
	echo($e->getMessage());
	exit("<br><br>Program ends.");
}

// check which time through sheet
if (isset($_GET["state"]))
{
	if (($_GET{"state"}) >= 1 and ($_GET{"state"}) <= count($arstate))
	{
		$state = $_GET["state"];
		if (DEBUG) echo "<P>State posted parameter set (" . $state . ").";
	}
	else
	{
		die("Bad state parameter.");
	}
}
else
{
	$state = $arstate["first time"];
	if (DEBUG) echo "<P>State parameter not set, assuming first time";
}

if (isset($_POST['btnAddNewRID']))
{
	if (DEBUG) echo "<P>Add New RID button pressed";
	if ($state == $arstate["first time"])
	{
		$state = $arstate["needs checked"];
		if (DEBUG) echo "<P>State changed from 'first time' to 'needs checked' (" . $state . ").<P>";
	}
}

$bFilter = false;
if (DEBUG) echo "<P>bfilter set = $bFilter<P>";
if (isset($_POST['tbFilter']))
//if (isset($_POST['btnFilter']) or isset($_POST['btnFilterClear']))
{
	if (isset($_POST['btnFilter']) or isset($_POST['btnFilterClear']))
	{
		$bFilter = true;
		if ($state != $arstate["first time"] or $state != $arstate["needs checked"])
		{
			$state = $arstate["first time"];
		}
		if (DEBUG) echo "<P>Filter set true with state = $state<br>";
	}
}

// the code immediately below runs the 2nd or subsequent times through
if (($state == $arstate["needs checked"] or $state == $arstate["needs posted"]) and !$bFilter)
{	
	// even if the submission is ready to post, check it one more time
	//phpinfo(INFO_VARIABLES);
	if (DEBUG) echo "<br>in checking section...";
	
	if ($_POST['sbDocNum'] == "0")
	{
    echo "<p>Please select a valid <b>document</b> from the list.</P><br>";
		echo "Use your browser's <b>Back button</b> to correct<br>";
		exit;
	}
	
	// we'll let chapter field be null

	// is RID Major?
	if  (is_null($_POST['radbMajorRID']) or $_POST['radbMajorRID'] == "" )
	{
		echo "<P>Classification not selected for this RID.</P>";
		echo "<P>You must select either <b>Major</b> or <b>Minor</b>.</P><br>";
		echo "Use your browser's <b>Back button</b> to correct<br>";
		exit;
	}
	elseif ($_POST['radbMajorRID'] == 'MAJOR')
	{
		$bMajorRID = true;
	}
	elseif ($_POST['radbMajorRID'] == 'MINOR')
	{
		$bMajorRID = false;
	}
	else
	{
		echo "Routine RIDs.php -- logic error testing for major rid.  Program must die.";
		die();
	}

	// decode the RID type: RID (descrepancy), RIC (comment) or RIQ (Question)
	if (is_null($_POST['radbRIX']) or $_POST['radbRIX'] == "" )
	{
		echo "<P>Type of RID not selected.</P><br>";
		echo "<P>Please choose either <b>Descrepancy</b> (RID) or <b>Question</b> (RIQ) for <b>Comment</b> (RIC).</P><br>";
		echo "Use your browser's <b>Back button</b> to correct<br>";
		exit;
	}

	if (strlen($_POST['tbDescription']) == 0)
	{
    echo "<P><b>RID description</b> field must be filled in.</P><br>";
		echo "Use your browser's <b>Back button</b> to correct<br>";
		exit;
	}
	
		if (strlen($_POST['tbOriginator']) == 0)
	{
    echo "<P>Field containing <b>your name</b> must be filled in.</P><br>";
		echo "Use your browser's <b>Back button</b> to correct<br>";
		exit;
	}
} // end of checking section	
	
// at this point, all input fields are OK, so insert new record into database
if ($state == $arstate["needs posted"])
{ 
	$state = $arstate["posted"];
	if (DEBUG) echo "<P>State = 'posted'";
	try
	{
		$odbRIDs->setInsertNewRID($_POST['sbDocNum'], "", "", 
								$_POST['tbChapter'],                                       
		                        date("Y-m-d H:i:s"), 
								$bMajorRID,
								$_POST['radbRIX'],
		                        $_POST['tbDescription'], 
								$_POST['tbSuggestedSolution'],
		                        $_POST['tbOriginator']); 
	}
	catch(Exception $e)
	{
		echo "<P>Database update failed!:</P>";
		echo($e->getMessage());
		echo "<P>Will return to home page in " . TIME_RELOAD_ON_ERROR ." secs"; 
		echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"" . TIME_RELOAD_ON_ERROR . "; URL=$lnkRID_MAIN?type=$ProjPassword\">"; 
	}
	echo "<P>Database update succeeded!:";
	echo "<br>Returning to home page in " . TIME_RELOAD_AFTER_DB_POST ." secs";
	echo "<br>Hint - Use <b>Back button</b> now to modify and submit a similar RID.</P>";
	// return to home page  
	echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"" . TIME_RELOAD_AFTER_DB_POST . "; URL=$lnkRID_MAIN?type=$ProjPassword\">"; 
}
if (DEBUG) echo "<P>bFilter = " . $bFilter;
if ($state == $arstate["needs checked"] or $bFilter)
{
	// submission needs rechecking by user, 
	// or for Filter, resubmit same page after saving user input
	if (DEBUG) echo"<br>In rechecking/Filter section...";
	$Description = $_POST['tbDescription'];
	$Chapter = $_POST['tbChapter'];
	$Originator = $_POST['tbOriginator'];
	$SuggestedSolution = $_POST['tbSuggestedSolution'];
  	$DocNum = $_POST['sbDocNum'];
	if (isset($_POST['btnFilterClear']))
	{
		$tbFilter = "";
	}
	else
	{
		$tbFilter = $_POST{'tbFilter'};
	}
	if ($_POST['radbMajorRID'] == 'MAJOR')
	{
		$sMajorRIDChecked = "CHECKED";
		$sMinorRIDChecked = "";
	}
	elseif ($_POST['radbMajorRID'] == 'MINOR')
	{
		$sMajorRIDChecked = "";
		$sMinorRIDChecked = "CHECKED";
	}
	$sRIDChecked = "";
	$sRIQChecked = "";
	$sRICChecked = "";
	switch ($_POST['radbRIX'])
	{
		case 'RID':
			$sRIDChecked = "CHECKED";
			break;
		case 'RIQ':
			$sRIQChecked = "CHECKED";
			break;
		case 'RIC':
			$sRICChecked = "CHECKED";
			break;
		default:
	}
	// if Filtering, just refresh with old state, otherwise move on to next state
	if (!$bFilter)
	{
		// not Filtering... move on to next state
		$sTitleOfPage = "<big>All required fields have been completed.<br>This is the last chance to check and change your entries,<br>";
		$sTitleOfButton = "Submit entry to database";
		$sTitleOfPage .= "then press '$sTitleOfButton' to post in database -></big>";
		$state = $arstate["needs posted"]; 
	}
}

if ($state == $arstate["first time"] or $bFilter)
{
	if (DEBUG) echo "<P>State = first time";
	if (!bFilter)
	{
		// code arrives here first time through, before any input has been made by user
		
		$DocNum = "";
		$Chapter = "";
		$sMajorRIDChecked = "";
		$sMinorRIDChecked = "";
		$sRIDChecked = ""; 
		$sRIQChecked = ""; 
		$sRICChecked = "";
		$Description = "";
		$SuggestedSolution = "";
		$Originator = "";
		$tbFilter = "";
		$state = $arstate["needs checked"];
		if (DEBUG) echo "<P>State changed to 'needs checked'<br>";
	}
	if ($_POST{"btnFilterClear"})
	{
		$tbFilter = "";
	}
	$sTitleOfPage = "Enter new RID";
	$sTitleOfButton = "Submit entry for checking";
	//echo "<INPUT TYPE=hidden NAME=\"stateVar\" value=\"$state\">";
}

// if posted, just fall to the end
if ($state != $arstate["posted"])
{
	$DateEntered = date("Y-m-d H:i:s");
	// just reload this form when posted and include parameters
	echo "<FORM METHOD=\"post\" ACTION=\"$ThisCode?type=$ProjPassword&amp;state=$state\">\r";
	
	// initial heading for page
	echo ("<TABLE BORDER=0 CELLPADDING=5 WIDTH=\"100%\" BGCOLOR=WHITE>\r");
	echo ("<TR><TD class=\"blockheading\" ALIGN=\"right\" WIDTH=\"33%\">$sTitleOfPage\r");
	echo "<TD ALIGN=\"center\" WIDTH=\"33%\"><INPUT TYPE=\"submit\" NAME=\"btnAddNewRID\" VALUE=\"$sTitleOfButton\">\r";
	echo "<TD ALIGN=\"center\" WIDTH=\"33%\"><A class=\"big\" HREF=\"$lnkRID_MAIN?type=$ProjPassword\">Return to Main Screen</A></TD>\r";		// hypertext to allow Return to Main Screen
	echo "</TABLE><br>\r";
	echo "<br>";
	
	// now build the input boxes	
	if (($bFilter and strlen($_POST['tbFilter'])>0) and !$_POST['btnFilterClear'])
	{
		// filter the records only if there's someting in the filter box and the filter's on.
		//echo "<P>Now here with error reporting = " . error_reporting();
		//echo "<P>Filtering for '" . $_POST['tbFilter'] . "'<br>";
		try
		{
			$rsProjects = $odbRIDs->getDocTitlesAndNums($_POST['tbFilter']);
		}
		catch(Exception $e)
		{
			echo "<br>Error in routine 'NewRID.php': Error opening database object<br>";
			echo($e->getMessage());
			exit("<br><br>Program ends.");
		}
	}
	else
	{
		try
		{
			$rsProjects = $odbRIDs->getDocTitlesAndNums();
		}
		catch(Exception $e)
		{
			echo "<br>Error in routine 'NewRID.php': Error opening database object<br>";
			echo($e->getMessage());
			exit("<br><br>Program ends.");
		}
	}
	
	if (!$rsProjects) 
	{
	  die($odbRIDs->sGetError());
	}
	
	if (isset($_POST['sbDocNum']) and strlen($_POST['sbDocNum'])>0)
	{
		// select by the document number
		$sHTMLSelectBox = $oHTML->SelectBoxFill("sbDocNum", $rsProjects, $_POST['sbDocNum'], false, true);
	}
	else
	{
		$sHTMLSelectBox = $oHTML->SelectBoxFill("sbDocNum", $rsProjects);
	}
	
	if ($bFilter and !isset($_POST['btnFilterClear']) and strlen($_POST['tbFilter'])>0)
	{
		$sFiltered = "&nbsp&nbsp (filtered)";
	}
	else
	{
		$sFiltered = "";
	}

	echo "<TABLE ID=\"tblNotesInput\">";
	echo "<TR><TD CLASS=\"colLabel\">Select Document:</TD>";
	echo "<TD CLASS=\"colInputFields\">$sHTMLSelectBox$sFiltered";
	echo "<SMALL>&nbsp&nbsp (Required)</SMALL></TD></TR>\r";
	
	echo "<TR><TD CLASS=\"colLabel\"><SMALL>(Use filter here to narrow choices) -></SMALL></TD>";
	echo "<TD CLASS=\"colInputFields\"><input SIZE=30 type=\"Text\" name= \"tbFilter\" value=\"$tbFilter\">";
	echo "&nbsp&nbsp<INPUT TYPE=\"submit\" NAME=\"btnFilter\" VALUE=\"Set Filter\">";
	echo "&nbsp&nbsp<INPUT TYPE=\"submit\" NAME=\"btnFilterClear\" VALUE=\"Clear Filter\">";
	echo "<SMALL>&nbsp&nbsp (Filter by doc title and/or number)</SMALL></TD></TR>\r";

	echo "<TR><TD>&nbsp</TD></TR>";
	echo "<TR><TD CLASS=\"colLabel\">Chapter or Section and Page:";
	echo "<TD CLASS=\"colInputFields\"><input SIZE = 50 type=\"Text\" name= \"tbChapter\" value=\"$Chapter\">";
	echo "<SMALL>&nbsp&nbsp (Optional, but recommended)</SMALL></TD></TR>\r";
	
	echo "<TR><TD CLASS=\"colLabel\">Classification:\r";
  // TODO ability to modify RID entries
	echo "<TD CLASS=\"colInputFields\">";
	echo "Major:<INPUT TYPE=\"radio\" NAME=\"radbMajorRID\" $sMajorRIDChecked value=\"MAJOR\">\r\r";
	echo "&nbsp&nbsp&nbsp ";
	echo "Minor:<INPUT TYPE=\"radio\" NAME=\"radbMajorRID\" $sMinorRIDChecked value=\"MINOR\">";
	echo "<SMALL>&nbsp&nbsp(Required)</SMALL></TD></TR>\r";
	
	echo "<TR><TD CLASS=\"colLabel\">Type:</TD>\r";
	echo "<TD CLASS=\"colInputFields\">";
	echo "Discrepancy: <INPUT TYPE=\"radio\" NAME=\"radbRIX\" $sRIDChecked value=\"RID\">\r\r";
	echo "&nbsp&nbsp&nbsp&nbsp ";
	echo "Question: <INPUT TYPE=\"radio\" NAME=\"radbRIX\" $sRIQChecked value=\"RIQ\">\r";
	echo "&nbsp&nbsp&nbsp&nbsp ";
	echo "Comment: <INPUT TYPE=\"radio\" NAME=\"radbRIX\" $sRICChecked value=\"RIC\">";
	echo "<SMALL>&nbsp&nbsp (Required)</SMALL></TD></TR>\r";
	
	echo "<TR><TD CLASS=\"colLabel\">RID Description:";
	echo "<TD CLASS=\"colInputFields\"><textarea name=\"tbDescription\"rows=\"10\"cols=".RID_DESC_COLUMNS.">$Description</textarea>";
	echo "<SMALL>&nbsp&nbsp (Required)</SMALL></TD></TR>\r";
	
	echo "<TR><TD CLASS=\"colLabel\">Suggested Solution:";
	echo "<TD CLASS=\"colInputFields\"><textarea name=\"tbSuggestedSolution\"rows=\"10\"cols=".RID_DESC_COLUMNS.">$SuggestedSolution</textarea>";
	echo "<SMALL>&nbsp&nbsp (Optional)</SMALL></TD></TR>\r";
	
	echo "<TR><TD CLASS=\"colLabel\">Your Last Name:";
	echo "<TD CLASS=\"colInputFields\"><INPUT SIZE = 50 TYPE=\"Text\" NAME= \"tbOriginator\" VALUE=\"$Originator\">";
	echo "<SMALL>&nbsp&nbsp (Required)</SMALL></TD></TR>\r";
	// TODO entry for user file upload
	echo ("</TABLE>");
}

echo "<br><br><br><SMALL>Software Ver $version &nbsp&nbsp&nbsp RidLib Ver: " . sGetVersionForRidLib() . "</SMALL>\r";

echo "</form>";
echo "</body>";
echo "</HTML>\r";
?>
