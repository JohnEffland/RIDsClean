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
/* Include file for the RID software.
* 
* 2009-03-04 2.0 jee genesis is taskconst.php, Ver 1.94
* 2009-03-05 2.1 jee changed Tasks3.php to Rids.php
* 2009-03-07 2.1 jee updated odb for RIDs
* 2009-03-17 2.1 jee TODO don't use PHP_SELF see http://www.thespanner.co.uk/2008/01/14/exploiting-php-self/
* 2009-03-19 2.2 jee turned off echo in sanitize
* 2009-03-25 2.3 jee added ChapterSectionPage to sgetTableRow, getRIDs, and getRIDsSorted.
* 2009-03-26 2.4 jee got number of rids code working
* 2009-03-30 2.5 jee added getNumOpenAndClosedRids
* 2009-03-31 2.6 jee got both descriptions and response boxes to be same height.
* 2009-04-15 2.7 jee updated getRIDPeople
* 2009-08-10 2.8 jee now requires db here rather than in other modules.
* 2009-11-13 3.0 jee now uses rid project numbers
* 2009-11-14 3.1 jee now uses passwords in URL rather than easily guessed passwords.  Added getProjNumFromPassword
* 2009-11-16 3.2 jee SelectBoxFill now encodes html entities
* 2009-11-17 3.3 jee added project password to call to events2
* 2012-01-22 3.31 jee added date_default_timezone_set to prevent errors as of PHP Ver. 5.3
* 2013-01-10 3.32 jee removed Doc: from sgetRIDTable
* 2012-01-10 3.33 jee cleaned up strip-tags in sFormatNotes
*   
*/

require 'database.php';
define("VERSION", "3.33 (2013-01-10)");

/*
 * 2012-01-22 jee need this to prevent errors as of PHP version 5.3
 */
date_default_timezone_set('America/New_York');

// location of associated code
// need 'global' to be used insSortDef class
$ThisCode = $_SERVER['PHP_SELF'];
$lnkRID_EVENTS = dirname($ThisCode) . "/Events2.php";
$lnkRID_NEW_RID = dirname($ThisCode) . "/NewRID.php";
$lnkRID_NEW_EVENT = dirname($ThisCode) . "/NewNote.php";
$lnkRID_MAIN = dirname($ThisCode) . "/Rids.php";

define("DATE_ERROR_STRING", "Most date formats work (with or without the time), but <B>YYYY-MM-DD HH:MM:SS</B> is the ISO 8601 standard.<br>Forms of <B>MM/DD/YYYY</B> also work, but <B>MM/DD/YY</B> works only for some years.<br>The form MM-DD-YY converts to a completely wrong year!");

// name of EntryBy cookie
define("NAME_ASSIGNED_TO_COOKIE", "cookEntryBy");

// set the cookie to expire one year from now
$COOKIE_EXPIRE = time() + (60*60*24*365);

// get the name of the server
$COOKIE_HOST = preg_replace('/^[Ww][Ww][Ww]\./', '',
           preg_replace('/:[0-9]*$/', '', $_SERVER['HTTP_HOST']));
					 
// for sFormatNotes
define("CNT_ROWMAX_NO_TEXTAREA", 3);
define("DEFAULT_MAX_COLUMNS", 80);
define("DEFAULT_MAX_ROWS", 6);
define("DEFAULT_MIN_ROWS", 1);

// for project password, to help sanitize input
define ("PROJECT_PASSWORD_LEN", 8);
					 
//***************************************************************************
//***************************************************************************
function sGetVersionForRidLib()
{
	// returns the version of this file, for more accurate versioning of the apps that use this file
	//
	// 2008-10-06 jee
	//
	return VERSION;
}
function sGetCurrentURL()
{
	// get's the URL of the currently executing web page
	//
	// returns string with URL
	// 2007-09-12 jee
	//
	
	$self_url = sprintf('http%s://%s%s',
  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': ''),
  $_SERVER['HTTP_HOST'],  $_SERVER['REQUEST_URI']);
  
  //echo "<P>Current path = ".$self_url."<P>";
  
  return $self_url;
}
function bSetCookie($cName, $cValue)
{
	// sets cookies
	// Calling params:
	// 	cName is name of cookie
	// 	cValue is cookie's value
	// Returns TRUE if set
	//
	// genesis is the help file for setcookie
	//
	// 2006-11-09 jee
	// 2007-01-03 jee made expire and host global!
	//
	global $COOKIE_EXPIRE, $COOKIE_HOST;
	
	if (strcmp($cValue, "") == 0)
	{
		// delete the cookie
		$cookie_expire_past = time() - 3600;
		setcookie($cName,"",time() - 3600, "/", ".$COOKIE_HOST", FALSE);
		//echo "<P>bSetCookie deleting cookie...<br>Name: '$cName'";
		//echo "CookieHost: '$COOKIE_HOST'<br>";
		//echo "Expires: '$cookie_expire_past'<br>";
	}
	else
	{
		$bReturn = setcookie($cName, $cValue, $COOKIE_EXPIRE, "/", ".$COOKIE_HOST", FALSE);
		$_COOKIE[$cName] = $cValue;        					// Pretend it's already set
		//echo "<P>bSetCookie setting cookie...<br>Name: '$cName'<br>Value:'$cValue'<br>";
		//echo "CookieHost: '$COOKIE_HOST'<br>";
		//echo "Expires: '$COOKIE_EXPIRE'<br>";
	}
	
	//echo "setcookie returns: '$bReturn'<br>";
	
	return $bReturn;
}
//***************************************************************************
function bgetCookieEntryBy(&$sWho)
{
	/*
	Returns the value of the EntryBy cookie
	
	Calling parms: None
	Returns:				$sWho - Name of person to put in this cookie
								 	False if cookie not set
	
	2006-08-11 jee
	2006-10-26 jee changed return params.
	2007-01-03 jee added globals
	
	*/
	
	//global $NAME_ASSIGNED_TO_COOKIE;
	//reset($_cookie);
  //print_r($_COOKIE);
  //reset($_cookie);
	if (isset($_COOKIE[NAME_ASSIGNED_TO_COOKIE]))
	{
		//echo "<br>EntryBy found cookie with name '" . NAME_ASSIGNED_TO_COOKIE; 
		//echo "' and value '" . $_COOKIE[NAME_ASSIGNED_TO_COOKIE] . "'<br>";
		$sWho = $_COOKIE[NAME_ASSIGNED_TO_COOKIE];
		return true;
	}
	else
	{
		//echo "<br>EntryBy found no cookie.<br>";
		return false;
	}
	
}
//***************************************************************************
function bsetCookieEntryBy($sWho)
{
	/*
	Sets a cookie to the assigned to variable
	
	Calling parms: $sWho - name of person to put in this cookie
	Returns:			 TRUE if cookie is set
	
	2006-08-11 jee
	2006-11-09 jee changed to use bsetCookie
	2007-01-03 jee added globals
	
	*/
	
	//global $NAME_ASSIGNED_TO_COOKIE;

	$bReturn = bSetCookie(NAME_ASSIGNED_TO_COOKIE, $sWho);
	if (!$bReturn)
	{
		//echo "'bsetCookieEntryBy' <B>failed</B> to set cookie '" .NAME_ASSIGNED_TO_COOKIE . "' to value '$sWho' .<br>";
	}
	else
	{
		//echo "'bsetCookieEntryBy' set cookie to value '$sWho'.<br>";
	}	
	return $bReturn;
}
//***************************************************************************
function ReturnCookie()
/*
* 2003-12-18 jee untested
* 
* From http://us2.php.net/manual/en/language.variables.scope.php
* 
*/

{
			 echo "<p>Inside ReturnCookie...";
			 print_r($_COOKIE);
       $cookieName = "Test_Cookie";
			 $newCookieValue = null;
       global $$cookieName;
       if (isset($$cookieName))
       {
               echo ("$cookieName is set<br>");
               $returnvalue = $$cookieName;
       }
       else
       {
               $newCookieValue = "Test Value";
               setcookie("$cookieName","$newCookieValue", (time() + 315360000));  // expires in 10 years
               echo ("made a cookie:" . $newCookieValue ."<br>");
               $returnvalue = $newCookieValue;
       }
       echo ("the cookie that was set is now $returnvalue <br>");
       return $returnvalue;
}
/**
	Base class for all others, used primarily to handle errors
	
	2006-01-11 jee
	
 *
 */
class CRIDsBase
{
		/**
		 * error string
		 *
		 * @var unknown_type
		 */
		 private $m_ErrorString;	// standard error string
		
		/**
		 * Returns the error string, along with name of calling routine if supplied
		 *
		 * @param string $sCallingRoutine - name of routine experiencing error (e.g. RIDs1)
		 * @return error string
		 
		 	2006-01-19 jee changed name from sGetErrorString to sGetError
		 */
		function sGetError($sCallingRoutine = "")
		{
			echo "<br>in sGetError<br>";
			if (strlen($sCallingRoutine) ==0)
			{
				// no information about calling program
				return $this->m_ErrorString;
			}
			else
			{
				return "Calling routine: " . $sCallingRoutine . "<br>" . $this->m_ErrorString;
			}
			
		}
		
}
/**
 * Class to generate HTML output
 * 
 * 2006-01-11 jee
 *
 */
class CHTML extends CRIDsBase
{
		// constants for arguments in the SelectBoxFill method:
		// fire the window.location event 
		public $m_SELECT_SUBMIT_WINDOW_LOCATION = -10;
		// fire the this.form.submit event
		public $m_SELECT_SUBMIT_THIS_FORM = -11;
		//-----------------------------------------------------------------
		/**
		 * Class constructor
		 *
		 * @return CHTML
		 */
		function CHTML ()
		{

		}
		//-----------------------------------------------------------------
		/**
		 * returns HTML code formatted for the top header shown on each event page
		 
		   	Calling Params:	None
																		...
		   	Returns: HTML code. 
			            
				2006-01-11 jee
				2006-01-17 jee added $bShowNotes
				2006-01-19 jee added /TABLE -- NO, needed by sgetTableRow
				2006-06-22 jee touched up widths
        2007-11-17 jee added returns to all html strings
        2007-11-19 jee added CSS for table.
        2009-03-05 jee added Originator for RIDs
		  
		 */
		//-----------------------------------------------------------------
		function sgetPageHeader($sTitle = "No title")
		{
			/*
			 * This returns the header information for the web page
			 * DTD HTML 4.01 Transitional using www.w3.org/TR/html4/loose.dtd
			 * TODO GET UTF-8 encoding to work.  When used now, docs pasted to server have A's with hats above them. See http://www.byteflex.co.uk/en/fun_with_utf8_php_and_mysql.html
			 * charset=ISO-8859-1
			 * 
			 * Calling params: $sTitle
			 * Returns: header information
			 * 
			 * 2009-03-19 jee initial
			 */
			$sHTML = "\r";
			$sHTML .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\r";
			$sHTML .= "\t\t\t\t\"http://www.w3.org/TR/html4/loose.dtd\">\r";
			$sHTML .= "<HTML>\r";
			$sHTML .= "<HEAD>\r";
			$sHTML .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=ISO-8859-1\">\r";
			$sHTML .= "\t<LINK REL=\"STYLESHEET\" TYPE=\"text/css\" HREF=\"RIDs.css\">\r";
			$sHTML .= "\t<SCRIPT type=\"text/javascript\" src=\"jsLib.js\"></SCRIPT>\r";
			$sHTML .= "\t<TITLE>" . $sTitle . "</TITLE>\r";
			$sHTML .= "</HEAD>\r";
			return $sHTML;
		}
		//-----------------------------------------------------------------
		function sgetTopHeader($bShowNotes = TRUE)
		{
			/*
			 * This returns the top of the header for page tables, etc
			 * 
			 * Calling Params: None
			 * Returns: HTML
			 * 
			 * 2006-00-00 jee
			 * 2009-03-26 jee added number of notes
			 */
			$sHTML = "\r";
			$sHTML .= "<TABLE ID=\"tableRIDs\">\r";
    	$sHTML .= "\t<TR>\r";
    	$sHTML .= "\t\t<TD CLASS=\"headerRow\" WIDTH=\"3%\">RID<br>#</TD>\r";
			$sHTML .= "\t\t<TD CLASS=\"headerRow\" WIDTH=\"3%\">Major</TD>\r";
  		$sHTML .= "\t\t<TD CLASS=\"headerRow\" WIDTH=\"3%\">Type</TD>\r";
    	$sHTML .= "\t\t<TD CLASS=\"headerRow\" WIDTH=\"30%\">Description</TD>\r";		
			$sHTML .= "\t\t<TD CLASS=\"headerRow\" WIDTH=\"5%\">Originator<br><small>(# of Notes)</small></TD>\r";
  		$sHTML .= "\t\t<TD CLASS=\"headerRow\" WIDTH=\"5%\" >Date<br>Entered</TD>\r";
    	if ($bShowNotes) $sHTML .= "\t\t<TD CLASS=\"headerRow\" WIDTH=\"30%\">Originator's<br>Suggested Solution</TD>\r";
    	$sHTML .= "\t</TR>\r";
			return $sHTML;
		}
		//-----------------------------------------------------------------
		/**
		 * Returns HTML for drop-down select box and button
		 
		 		Calling params: $arList - array with list elements
		 															if empty, "None" is put in box
		 *
		 */
		function SelectBoxFill($BoxName, $arList, $Selected = null, $bSubmitEvent = false, $bSelectByKey = false)
		{
			/* Returns the HTML for a select box filled with the contents of the array
			* 
			*  Calling Parms: $BoxName - name of vars returned when box is selected
			*                 $arList - array with elements to add to box
			*                 $Selected -if non-null, highlight this item
			*                 $bSubmitEvent - if false, don't fire any events
			*                                 if not false, fire one of constants defined for class
			* 																	m_SELECT_SUBMIT_WINDOW_LOCATION - fire the window.location event 
			*																		m_SELECT_SUBMIT_THIS_FORM - fire the this.form.submit event
			*                 $bSelectByKey - if true, compares the key value to $selected to determine the selected entry
			* 
			*  Returns: HTML of select box
			*           False if error
			* 
			*  2004-01-20 jee
		  *  2004-01-22 jee finally, after 2 days of testing, fixed stupid typo!
		  *  2004-03-10 jee fixed typo for SELECTED
		  *  2005-04-01 jee minor spacing fixes
		  *  2006-01-11 jee moved to CHTML
		  *  2006-01-12 jee added OnChange event, but it doesn't work:
		  *                 sHTML = "<SELECT NAME=\"$BoxName\" ONCHANGE='OnChange(this.form.$BoxName)>";
		  *  2006-06-23 jee added strtoupper to eliminate case sensitivity.
		  *  2007-11-16 jee added returns to all elements
		  *  2007-11-19 jee now supports CSS
		  *  2007-11-21 jee submits with the select box
		  *  2007-11-26 jee added m_SELECT_SUBMIT_THIS_FORM, m_SELECT_SUBMIT_WINDOW_LOCATION
		  *  2009-03-09 jee added $bSelectByKey
		  *  2009-03-10 jee got SelectByKey working
		  *  2009-11-16 jee added htmlspecialchars to encode html chars
			* 
			*/
			
			/* echo "<br>" . __Method__  . " with boxname = $BoxName";
			echo "<br>" . __Method__  . " with bSubmitEvent = $bSubmitEvent<br>";
			echo "<br>" . __Method__  . " with Selected  = $Selected";
			echo "<br>" . __Method__  . " with SelectByKey  = $bSelectByKey";
			echo "<br>" . __Method__  . " with array:<br>";
			print_r($arList);
			*/
			if ($bSubmitEvent === $this->m_SELECT_SUBMIT_THIS_FORM)
			{
				// POST the form with the onChange event:
		  	$sHTML = "\r\t\t\t<SELECT NAME=\"$BoxName\" onChange=\"this.form.submit()\">\r";
			}
			elseif ($bSubmitEvent === $this->m_SELECT_SUBMIT_WINDOW_LOCATION)
			{
				// POST the form with the onChange event:
		  	$sHTML = "\r\t\t\t<SELECT NAME=\"$BoxName\" onChange=\"window.location=this.options[this.selectedIndex].value\">\r";
			}
			else
					{
		  	$sHTML = "\r\t\t\t<SELECT NAME=\"$BoxName\">\r";
			}			
			$Name = "";
			$ListValue = "";
			foreach ($arList as $key => $value)
			{
				$sHTML .= "\t\t\t\t<OPTION VALUE=\"";
				$sHTML .= $key;
				if ($bSelectByKey)
				{
					// compare the selected param to the key
					if (strtoupper($Selected) == strtoupper($key))
					{
						$sHTML .= "\" SELECTED=\"SELECTED";
					}
				}
				else
				{
					// compare the selected param to the value
					if (strtoupper($Selected) == strtoupper($value))
					{
						$sHTML .= "\" SELECTED=\"SELECTED";
					}
				}
				$sHTML .=  "\">";		// the variable and description have the same value
				$sHTML .= htmlspecialchars($value, ENT_QUOTES) . "</OPTION>\r";
			}
			$sHTML .= "\t\t\t</SELECT>\r";
			return $sHTML;
		}

		//-----------------------------------------------------------------
		function sFormatNotes($sNotes, $cntColMax = null, $cntRowMax = null, $cntRowMin = null, &$cntRowsUsed = 1)
		{
			/*
			 * 
			 * Puts Notes into scroll box if size is appropriate
			*
			* Calling params $sNotes - notes to be formatted
			*                $cntColMax - max columns for text box, if null or not set, defaults below used
			*								$cntRowMax - max number of rows for text box, if null or not set, defaults below used
			*                $cntRowMin - miniumum number of rows for text box, if null or not set, defaults below used
			*                                  this is used, e.g., to match the height of two text boxes
			*                                  on the same row of the table.
			*
			*
			* Returns: Notes in HTML format, formatted in scroll box if too big for table cell
			*          $cntRowsUsed - number of rows actually used
			*
			* 2006-01-11 jee
			* 2006-06-22 jee changed width to 70 chars
			* 2007-01-04 jee changed rows and columns
			*                now calculates rows.
			* 2007-02-01 jee added HyperlinkInsert
			* 2007-04-04 jee moved HyperlinkInsert to just non TEXTAREA text, 
			*                because it doesn't work in TEXTAREAs
			* 2007-11-16 jee added returns to HTML
			* 2007-11-19 jee changed max width from 70 to 80 cols
			* 2007-11-20 jee added $cntRowMax
			* 2009-03-31 jee added $cntRowsUsed and $cntRowMin
			* 2009-11-16 jee now encodes the html
			* 2013-01-10 jee removed strip tags and replaced with str replace
			*
			*/
		
			if (!isset($cntColMax))	{
				$cntColMax=DEFAULT_MAX_COLUMNS;
			}
			if (!isset($cntRowMax)) {
				$cntRowMax=DEFAULT_MAX_ROWS;
			}
			if (!isset($cntRowMin))	{
				$cntRowMin=DEFAULT_MIN_ROWS;
			}
			
			$sHTML = "";
			
			if ($sNotes == "") {
				$sHTML = sprintf("&nbsp");
				$cntRowsUsed = 1;
			}
	    else  {
	    	// string has chars in it
	  		// remove the html tags from nl2cr
	      //$sNotes = str_replace("%", "%%", $sNotes);
	      $sNotes = str_replace("<br />", "", $sNotes);
	      
				$cntCarriageRtn = substr_count($sNotes,"\n");
				// remove carriage returns (and newlines, hence times 2) before calculating number of rows from text
		    $cntCalculatedRows = ceil( (strlen($sNotes) - 2*$cntCarriageRtn) /$cntColMax);
				
				if ($cntCarriageRtn > $cntCalculatedRows ) {
					// more carriage returns than filled rows with text, so use that count
					$cntRows = $cntCarriageRtn;
				}
				else {
					$cntRows = $cntCalculatedRows;
				}
				
				if ($cntRows <= CNT_ROWMAX_NO_TEXTAREA and ($cntRowMin <= CNT_ROWMAX_NO_TEXTAREA)) {
		    	 // just print out the notes without a textarea box
					 // htmlentities encodes special characters, which should only be done for non-text box text
					 $cntRowsUsed = $cntRows;
		    	 $sNotes = HyperlinkInsert($sNotes);
		       $sHTML .= sprintf("%s", htmlspecialchars ($sNotes, ENT_QUOTES));
		    }
				else {
					// use a textarea box
					if ($cntRows >= $cntRowMax)	{
						// too many rows, so limit
						$cntRows = $cntRowMax;
					}
					else {
					   //   " Rows less than max count cntrows = '$cntRows' less than cntRowMax = '$cntRowMax'";
					}
					if ($cntRowMin != DEFAULT_MIN_ROWS)	{
						// minimum number of rows limited by calling program
						$cntRows = $cntRowMin;
					}
					
					// return the row size used
					$cntRowsUsed = $cntRows;
		      $sHTML .= sprintf("<TEXTAREA NAME=\"Notes\" ROWS=\"". $cntRows . 
		                        "\" COLS=\"". $cntColMax . "\">%s</TEXTAREA>\r",$sNotes);
				}
	    }
	    return $sHTML;
		}
		
//----------------------------------------------------------------------------
	function sgetTableRow($lnkRID_EVENTS, $ProjPassword, $RIDNum, $bMajorRID, $RIXType, $Description, 
												$ChapterSectionPage, $Originator, $DateAssigned, $SuggestedSolution, $NumResponses = null) 
	{
		/* returns HTML code formatted for a particular row of the table
			 Calling Params:	$lnkRID_EVENTS - URL to call event form
			 								  $ProjPassword - project password (for generating hyperlink)
			 									$RIDNum - RID number
			 									$DocTitle - document title
			 									$bMajorRID - if true, then major RID, otherwise, minor
			 									$RIXType - either discrepancy RID, comment RIC, or Question RIQ
			 									$Description - of RID
			 									$ChapterSectionPage - of document
			 									$Originator - of RID
			 									$DateAssigned - date RID was entered into database
			 									$SuggestedSolution - suggested solution from originator of RID
			 									$NumResponses - number of responses for this RID

			 2006-01-11 jee
			 2006-01-12 jee added $level
			 2006-01-17 jee added bShowDescription
			 2006-06-22 jee changed ... to <br> between dates.
			 2006-09-08 jee fixed errors in </TR> that only showed up in Firefox.
			 2007-09-11 jee added NAME attribute
       2007-11-16 jee added returns to all HTML
			 2007-11-19 jee updated for CSS
			 2009-03-05 jee updated for RIDs
			 2009-03-10 jee further updated for RIDs
			 2009-03-12 jee changed back from Response to SuggestedSolution
			 2009-03-25 jee added ChapterSectionPage and NumResponses
			 2009-03-31 jee added RowLenMinimum to allow both Description and Suggested Solution to have same lengths in table
			 2009-11-17 jee added project password to arguments and hyperlinks
		*/

	  //error_reporting(E_ALL);
		$sChapterSectionPage = "";
		//echo "<P>" . __METHOD__ . " Inside with Rid = '$RIDNum' and NumResponses = '$NumResponses'<P> ";
		//echo "<P>" . __METHOD__ . "Inside with error_reporting = " . error_reporting() . "<P> ";
		
		$sHTML = sprintf("<TR>\r");
		// RID number hyperlink
		$sHTML .= sprintf("<TD CLASS=\"RIDNum\"><A NAME=\"%s\" HREF=\"%s?type=%s&amp;RIDNum=%s\">%s</A></TD>\r",$RIDNum, $lnkRID_EVENTS, $ProjPassword, $RIDNum, $RIDNum);
		
		if ($bMajorRID)
		{
			// user the radical sign, which renders in all browsers.
			$sMajorRID = "&#8730";
		}
		else
		{
			$sMajorRID = "&nbsp";
		}
		$sHTML .= sprintf("<TD CLASS=\"MajorRID\">%s</TD>\r", $sMajorRID);
		
		if (is_null($RIXType))
		{
			$RIXType = "&nbsp";
		}
		$sHTML .= sprintf("<TD CLASS=\"RIXType\">%s</TD>\r", $RIXType);
		
		//echo "<P>" . __METHOD__ . " chapterSectionPage: $ChapterSectionPage<P> ";
		if (!is_null($ChapterSectionPage) and strlen($ChapterSectionPage) > 0)
		{
			//echo "<P>" . __METHOD__ . " chapterSectionPage included: $ChapterSectionPage<P> ";
			// include ChapterSectionPage
			$ChapterSectionPage = "Chap/Sect/Pg: \"" . $ChapterSectionPage . "\"\r";
		}
		// which notes box is longer, Description or SuggestedSolution?
		// run dummy call to return length of these text boxes
		//echo "<P>*************" . __METHOD__ . " RowLenDescription call...";
		$sBuff = $this->sFormatNotes($ChapterSectionPage . $Description, null, null, null, $RowLengthDescription);
		//echo "<br>" . __METHOD__ . " RowLenSuggestedSolution call...";
		$sBuff = $this->sFormatNotes($SuggestedSolution, null, null, null , $RowLengthSuggestedSolution);
		//echo "<br>" . __METHOD__ . " RowLenDescription = '$RowLengthDescription'<br>";
		//echo "<br>" . __METHOD__ . " RowLengthSuggestedSolution = '$RowLengthSuggestedSolution'<br>";
		if ($RowLengthDescription >= $RowLengthSuggestedSolution)
		{
			$RowLengthMin = $RowLengthDescription;
		}
		else
		{
			$RowLengthMin = $RowLengthSuggestedSolution;
		}
		//echo "<P>" . __METHOD__ . "RowLengthMin = '$RowLengthMin'<P>";
		$sHTML .= sprintf("<TD>%s</TD>\r", $this->sFormatNotes($ChapterSectionPage . $Description, null, null, $RowLengthMin));
		if (is_null($Originator))
		{
			$Originator = "&nbsp";
		}
		$sHTML .= sprintf("<TD CLASS=\"respName\">%s",$Originator);
		if (!is_null($NumResponses)){
    	if ($NumResponses > 0)
			{
				$sHTML .= sprintf("<br><br><small>(%s)</small>",$NumResponses);
			}
    } 
		$sHTML .= "</TD>\r";
		$sHTML .= sprintf("<TD CLASS=\"RIDDate\">%s",$DateAssigned);
		$sHTML .= sprintf("</TD>\r");
	  $sHTML .= sprintf("<TD>%s</TD>\r",$this->sFormatNotes($SuggestedSolution, null, null, $RowLengthMin));
		$sHTML .= sprintf("</TR>\r");
		return $sHTML;
	}
	//-----------------------------------------------------------------
	function sgetRIDTable($arRIDs)
	{
		/*
		*	Generates the HTML for the RID table
		* 
		* Calling parms: $arRIDs - array of RIDs: 
		                           arRIDs[0] = {"Doc", "DocTitle", "AssignedTo"...}
		                          ...
		                          
		* Returns: HTML string
		* 
		* 2004-01-16 jee
		* 2004-01-19 jee
		* 2004-01-20 jee added sort button
		* 2004-01-21 jee added $sbFilterName
    * 2004-01-26 jee added Doc column
    * 2004-01-27 jee further testing for HTML select button
    * 2004-01-29 jee changed date "Complete" to "Est.Finish" in header
    * 2005-04-01 jee dates now on one line.
    * 2005-08-11 jee changed "RID Number" to "RID #" in heading
    * 2005-10-26 jee added text area for Description field
    * 2005-12-14 jee Added  argument.
    * 2006-01-18 jee moved to CHTML
    * 2006-06-22 jee readded showing of Description
    * 2007-11-26 jee TODO remove unused parameters!
    * 2008-10-01 jee added test for no records and appropriate return HTML
    * 2008-11-26 jee added stripslashes
    * 2009-03-05 jee updated for RIDs, removed extra params
    * 2009-03-10 jee continued working on RIDs
    * 2009-03-12 jee changed back from RespondersResponse to SuggestedSolution
    * 2009-03-19 jee removed extra <TR>
    * 2009-03-25 jee added ChapterSectionPage, and NumResponses
    * 2009-03-26 jee debugged
    * 2009-04-15 jee $bShowGrouping not even used!
    * 2009-11-16 jee added htmlspecialchars for document titles, removed showgrouping parameter
    * 2009-11-17 jee added project password to array passed to getTableRow
    * 2013-01-10 jee removed Doc:
		*  
		*/
		// this must be defined in EVERY method that uses the global!
		global $lnkRID_EVENTS;
    $DocLast = "";  // used to organize by Doc
    $DocPresent = "";
		$sHTML = "";
    
    if (!$arRIDs)	{
    	$sHTML .= sprintf("\t<TR>\r");
    	$sHTML .= "\t\t<TD COLSPAN=\"5\" ALIGN=\"center\">No Records</TD>\r";
    	$sHTML .= sprintf("\t</TR>\r");
    } 
    else {  
			foreach ($arRIDs as $row) {
		      $DocPresent = $row["DocTitle"];
		      if ($DocPresent != $DocLast) {
		        // add Doc title row
		        $DocLast = $DocPresent;
						$sHTML .= sprintf("\t<TR>\r");
		        $sHTML .= sprintf("\t\t<TD CLASS=\"projRow\" COLSPAN=\"7\">%s</TD>\r", htmlspecialchars($DocPresent, ENT_QUOTES));
		        $sHTML .= sprintf("\t</TR>\r");
		      }
		      // return the table row
					if (isset($row['NumRidNotes'])) {
						$sHTML .= $this->sgetTableRow($lnkRID_EVENTS, $row["ProjPassword"], $row["key"], $row["MajorRID"], $row["RIXType"],
	                    										$row["Description"], $row{"ChapterSectionPage"}, ($row["Originator"]), $row["DateEntered"],
	                    										$row["SuggestedSolution"], $row["NumRidNotes"]);	   
					}
					else {
			      $sHTML .= $this->sgetTableRow($lnkRID_EVENTS, $row["ProjPassword"], $row["key"], $row["MajorRID"], $row["RIXType"],
	                              					$row["Description"], $row{"ChapterSectionPage"}, ($row["Originator"]), $row["DateEntered"],
	                              					$row["SuggestedSolution"]);	   
					}                       
			}
    }
		$sHTML .= "</TABLE>\r";
		return $sHTML;
	}
	//-----------------------------------------------------------------
	//	Converts URL to hyperlink
	//  From http://www.koders.com/php/fidA8C35730D206FA59552C2ADE440B1B71C869AFFA.aspx?s=convert+url+to+href
	//
	//  2006-06-13 jee
	//
	function handle_url_tag($url, $link = '')
	{
		global $pun_user;
	
		$full_url = str_replace(' ', '%20', $url);
		if (strpos($url, 'www.') === 0)			// If it starts with www, we add http://
			$full_url = 'http://'.$full_url;
		else if (strpos($url, 'ftp.') === 0)	// Else if it starts with ftp, we add ftp://
			$full_url = 'ftp://'.$full_url;
		else if (!preg_match('#^([a-z0-9]{3,6})://#', $url, $bah)) 	// Else if it doesn't start with abcdef://, we add http://
			$full_url = 'http://'.$full_url;
	
		// Ok, not very pretty :-)
		$link = ($link == '' || $link == $url) ? ((strlen($url) > 55) ? substr($url, 0 , 39).' &hellip; '.substr($url, -10) : $url) : stripslashes($link);
	
		return '<a href="'.$full_url.'">'.$link.'</a>';
	}

}

//***************************************************************************
/**
 * this is the mainly RID class
 * 2006-06-21 jee updated for php 5
 * 2006-06-22 jee added $m_DATE_FORMAT_DD_MMM
 * 2006-06-26 jee added show recent RIDs
 * 2006-07-17 jee removed error string from this, it's in parent.
 * 2007-02-02 jee added $m_SHOW_DOCUMENTATION
 * 2007-09-06 jee changed $m_SHOW_DOCUMENTATION to $m_SHOW_HIGH_PRORITY
 * 2007-11-15 jee added  $m_SHOW_RESUMES for resumes
 * 2007-11-19 jee added $m_SHOW_RESUMES_OPEN and $m_SHOW_RESUMES_CLOSED
 * 2009-03-10 jee removed high priority and resumes.
 * 2009-09-09 jee added project type
 * 2009-11-14 jee added $m_ProjectPassword
 * 
 *
 */
class CdbRIDs
{
	private $m_DEBUG = false;
	private $m_db;
	private $m_result;
	private $m_ProjectNum;					// key value for project
	private $m_ProjectPassword;			// when decoded, gives project number (key values in database)
	private $m_ErrorString;
	// Date formats returned by MySQL queries
	// Day of week, month, day of month: $DATE_FORMAT ="'%a %b %e'";
	//		require '../database.php';
	
	//$this->m_DATE_FORMAT_YYYY_MM_DD = "'%Y-%m-%d'"; // these are mySQL formats for the DATE_FORMAT
	//$this->m_DATE_FORMAT_YYYY_MM_DD = "'%d%b%y'"; // these are mySQL formats for the DATE_FORMAT
	private $m_DATE_FORMAT_YYYY_MM_DD = "'%Y-%m-%d'"; // these are mySQL formats for the DATE_FORMAT
	private $m_DATE_FORMAT_DD_MMM = "'%d %b'"; // these are mySQL formats for the DATE_FORMAT
	private $m_DATE_FORMAT_FULL = "'%Y-%m-%d %H:%i:%s %a'"; // function in the SQL string
	
	// these constants must be less than 0 to distinquish them from key values
	public $m_SHOW_CLOSED_RIDS = -1;
	public $m_SHOW_OPEN_RIDS = -2;
	public $m_SHOW_RECENT_ACTIVITY = -3;
	public $m_SHOW_MAJOR_RIDS = -4;
	public $m_SHOW_ORIGINATOR = -5;
	public $m_SHOW_ASSIGNED_TO = -6;

	//----------------------------------------------------------------------------
	function CdbRIDs($dbServer, $dbName, $UserID, $Password, $ProjPW = null)
	{
		/* constructor for CdbRID class
		//     $dbServer - database server
		//     $dbName   - database name
		//     $UserID   - User ID
		//     $Password   - password
		//     $ProjPW - project password, mapped to key value from project table, can be null
		//
		* 2003-12-11 jee
		* 2003-12-15 jee
		* 2004-01-16 jee added setting class variables here		
		* 2004-01-19 jee added global defs here so they can be seen by this class
		* 2004-02-03 jee added m_DATE_FORMAT_FULL to show full date with notes
    * 2004-02-11 jee eliminated seconds from time format
    * 2006-06-21 jee updated for php 5
    * Thu Mar 05 2009 09:43:12 GMT-0500 (EST) Add exception handling
    * 2009-09-09 jee added getProjectTitle
    *                Exception tested and works!
    *                added ProjectType as parameter
    * 2009-11-12 jee 
    * 2009-11-13 jee  
    * 2009-11-14 jee added support for $ProjPW  
    * 2009-11-17 jee now saves project password!           
		*/

		if ($this->m_DEBUG) echo "<br><b>" . __Method__ . "</b> constructor start with parameters: '$dbServer', '$dbName', '$UserID', '$Password', '$ProjPW'";
			
		//Turn off all error reporting, let exceptions handle them
		$iErr = error_reporting();
		error_reporting(0);
    
		$this->m_db = mysql_connect($dbServer, $UserID, $Password);
		if (!$this->m_db)
		{
			$this->m_ErrorString ="<br>Routine: " . __Method__ . 
						 					"<br>Connection to MySQL database on server \"$dbServer\" failed!<P>" . mysql_error();
			throw new Exception($this->m_ErrorString);
		}

		if (!mysql_select_db($dbName, $this->m_db))
		{
			$this->m_ErrorString ="<br>Routine: " . __Method__ . 
						 					"<br>Selection of MySQL database \"$dbName\" on server \"$dbServer\" failed!<P>" . mysql_error();
			throw new Exception($this->m_ErrorString);
		}
		
		// if the password is null (like in a routine that's only passed the rid number, get it later)
		if (!is_null($ProjPW))
		{
			// set the project password
		  $this-> m_ProjectPassword = $ProjPW;
			try
			{
				$this->setProjNumFromPassword($this-> m_ProjectPassword);
			}
			catch (Exception $e)
			{
				$sBuff =  "<br>Routine: " . __Method__;  
				$sBuff .=	"<br>Error getting Project Number from password.<br>";
				$this->m_ErrorString = $sBuff . $this->m_ErrorString;
				throw new Exception($this->m_ErrorString);
			}
		}
		

		// restore error reporting
		error_reporting($iErr);
		
		if ($this->m_DEBUG) echo "<br><b>" . __Method__ . "</b> constructor end."; 
	}
	//------------------------------------------------------------------------------
	function getProjectTitle()
	{
		/* returns the project title given the project number
		 * 
		 * Calling Params: None - gets Project Number (key value from database) from object data
		 * Returns: Project title, or false and throws exception if no project title
		 * 
		 * 2009-09-09 jee Tested all exceptions
		 * 2009-11-13 jee added debug statements and test for null project num
		 *                
		 */
		if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b>: Enter";
		if (is_null($this->m_ProjectNum))
		{	
			$this->m_ErrorString = "Error in routine " . __METHOD__ . ":\n\n this->m_ProjectNum is null!";
			throw new Exception($this->m_ErrorString);
		}

		if (!is_numeric($this->m_ProjectNum))
		{	
			$this->m_ErrorString = "Error in routine " . __METHOD__ . ":\n\n Project number non-numeric";
			throw new Exception($this->m_ErrorString);
		}
		if ($this->m_DEBUG) echo "<br /><b>" . __METHOD__ . "</b>: Project number is numeric\r\r";
		
		$sSQL = "SELECT Name from RIDProjects WHERE keyRIDProjects = '$this->m_ProjectNum'";
		
		if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b>: SQL:<p>$sSQL<p>";
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "<br>Error in routine " . __METHOD__ . "<br><br>";
			$this->m_ErrorString .= "Query error with SQL = '" . $sSQL . "'<br><br>";
			$this->m_ErrorString .= "MySQL returned error: '" . mysql_error() . "'";
			throw new Exception($this->m_ErrorString);
		}
		if (mysql_num_rows($result) == 0)
		{
			$this->m_ErrorString = "<br>Error in routine " . __METHOD__ . "<br><br>";
			$this->m_ErrorString .= "No projects returned for project key value = '" . $this->m_ProjectNum . "'<br><br>";
			throw new Exception($this->m_ErrorString);
		}
		elseif (!mysql_num_rows($result))
		{
			$this->m_ErrorString = "<br>Error in routine " . __METHOD__ . "<br><br>";
			$this->m_ErrorString .= "SQL = '" . $sSQL . "'<br><br>";
			$this->m_ErrorString .= "ran okay, but error fetching the number of results. <br><br>MySQL returned error: '" . mysql_error() . "'";
			throw new Exception($this->m_ErrorString);
		}
		$myrow = mysql_fetch_array($result);
		$ProjTitle = $myrow['0'];
		if (!$ProjTitle)
		{
			$this->m_ErrorString = "<br>Error in routine " . __METHOD__ . "<br><br>";
			$this->m_ErrorString .= "SQL = '" . $sSQL . "'<br><br>";
			$this->m_ErrorString .= "ran okay error fetching project name.<br><br>MySQL returned error: '" . mysql_error() . "'";
			throw new Exception($this->m_ErrorString);
		}

		if ($this->m_DEBUG) echo "<br/><b>" . __METHOD__ . "</b>: exit with project title ='" . $ProjTitle . "'";
		return $ProjTitle;
	}
	//------------------------------------------------------------------------------
	function setRIDAssignmentChange($RIDNum, $AssignedTo1, $EntryBy, $DateUpdated)
	{
		/* changes the person responsible for this RID
		*  Updates two tables:  both 'RIDs' and 'RIDEvents'
		* 
		*  Calling Params: $RIDNum - previously assigned RID number
		* 								 $AssignedTo1 - Name of person with new assignment
		* 								 $EntryBy - name of person entering the completed date
		* 								 $DateUpdated - date that this record was entered
		* 
		*  Returns: TRUE if no errors
		* 
		*  2004-01-19 jee
		*  2005-06-22 jee added mysql_real_escape_string
		*  2008-07-16 jee removed notes parameter -- this routine enters it's own notes only.
		*  2009-04-15 jee added $this->m_SHOW_ASSIGNED_TO
		* 
		*/
		echo "<br>In CdbRIDs.setRIDAssignmentChange with input params: <br>RIDNum = $RIDNum, "
		     . "<br>Assigned to = $AssignedTo1,<br>Entry By = $EntryBy, "
		     . "<br>Date Updated = $DateUpdated,<br>Notes = $Notes";		
		// first, get the name of the person already assigned to this RID
		// for inclusion in the notes field
		$sPreviousAssignment = $this->getRIDPeople($RIDNum, $this->m_SHOW_ASSIGNED_TO);
		if (!$sPreviousAssignment)
		{
			$ErrorString = "Error in setRIDAssignmentChange<p>";
			$ErrorString .= "calling CdbRIDs->getRIDAssignement<p>";
			$ErrorString .= $m_ErrorString;
			$this->m_ErrorString = $ErrorString;
			return false;
		}
		
		// update the RIDs table
		$sSQL = "UPDATE RIDs SET AssignedTo='" . mysql_real_escape_string($AssignedTo1) . "' ";
		$sSQL .= "WHERE keyRIDs = $RIDNum";
		
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in setRIDAssignmentChange<p>";
			$this->m_ErrorString .= "Query error with SQL = <p>" . sSQL;
			return false;
		}
		//echo "<p>setRIDAssignmentChange sql... $sSQL";
		
		// add the assignment change to the notes buffer
		$sNewNotes = "RID assignment changed from $sPreviousAssignment";
		$sNewNotes .= " to $AssignedTo1.  ";
		
		// update the RIDEvents table
		$sSQL = "INSERT INTO RIDEvents SET fkRIDs='$RIDNum',";
		$sSQL .= "AssignedPrev='" . mysql_real_escape_string($sPreviousAssignment) . "',";
		$sSQL .= "DateUpdated='$DateUpdated',";
		$sSQL .= "EntryBy='" . mysql_real_escape_string($EntryBy) . "', Notes='" . mysql_real_escape_string($sNewNotes) . "'";
		
		//echo "<p>setRIDAssignmentChange sql... $sSQL";
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in setRIDAssignmentChange<p>";
			$this->m_ErrorString .= "Query error with SQL = <p>" . sSQL;
			$this->m_ErrorString .= "<br> " . mysql_error();
			return false;
		}
		return true;
		
	}
		//------------------------------------------------------------------------------
	function setPriorityFlag($RIDNum, $bPriorityFlag, $EntryBy, $DateUpdated)
	{
		/* Enters the state into two tables:  both 'RIDs' and 'RIDEvents'
		* 
		*  Calling Params: $RIDNum - RID number
		* 								 $$bPriorityFlag - high priority field value: TRUE or FALSE
		* 								 $EntryBy - name of person entering the completed date
		* 								 $DateUpdated - date that this record was entered
		* 
		*  Returns: TRUE if no errors
		* 
		*  2007-01-25 jee
		*  2007-11-07 jee changed name from setActionItem to setPriorityFlag
		*  2008-07-16 jee removed notes parameter -- this routine enters it's own notes only.
		* 
		*/
		
		if ($bPriorityFlag)
		{
			// high priority flag true
		}
		elseif (!$bPriorityFlag)
		{
			// high priority flag false
		}
		else
		{
			// high priority wrong format
			$this->m_ErrorString = "Error in setPriorityFlag<P>";
			$this->m_ErrorString .= "Parameter 'bPriorityFlag' not boolean.";
			return false;
		}
		
		// update the RIDs table
		$sSQL =  "UPDATE RIDs SET PriorityFlagSet='$bPriorityFlag' ";
		$sSQL .= "WHERE keyRIDs = $RIDNum";
		
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in setPriorityFlag<P>";
			$this->m_ErrorString .= "Query error with SQL = <P>" . $sSQL;
			return false;
		}
		//echo "<p>setPriorityFlag sql... $sSQL";
		
		// add the high priority change to the notes buffer
		if ($bPriorityFlag) 
		{
			$sNewNotes = "High priority flag set.";
		}
		else 
		{
			$sNewNotes = "High priority flag cleared.";
		}
		
		// update the RIDEvents table
		$sSQL = "INSERT INTO RIDEvents SET fkRIDs='$RIDNum',";
		$sSQL .= "DateUpdated='" . mysql_real_escape_string($DateUpdated) . "',";
		$sSQL .= "EntryBy='" . mysql_real_escape_string($EntryBy) . "',";
		$sSQL .= "Notes='" . mysql_real_escape_string($sNewNotes) . "'";
		
		//echo "<p>setPriorityFlag sql... $sSQL";
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "<P>Error in setPriorityFlag with query to events table.";
			$this->m_ErrorString .= "<P>Query error with SQL = '" . $sSQL . "'";
			$this->m_ErrorString .= "<P> " . mysql_error();
			return false;
		}
		return true;
		
	}//------------------------------------------------------------------------------
	function setRIDEstCompletion($RIDNum, $DateEstCompletion, $EntryBy, $DateUpdated)
	{
		/* Enters the estimated completion date into two tables:  both 'RIDs' and 'RIDEvents'
		* 
		*  Calling Params: $RIDNum - previously assigned RID number
		* 								 $DateEstCompletion - New estimated completion date
		* 								 $EntryBy - name of person entering the completed date
		* 								 $DateUpdated - date that this record was entered
		* 
		*  Returns: TRUE if no errors
		* 
		*  2004-01-19 jee
		*  2006-07-20 1) added mysql_real_escape_string
		*             2) checked error statements
			*  2008-07-16 jee removed notes parameter -- this routine enters it's own notes only.
		* 
		*/
		// first, get the current estimated completion date
		// for incclusion in the notes field
		//echo "<br>In CdbRIDs.setRIDEstCompletion with input params: <br>RIDNum = $RIDNum, "
		//     . "<br>Date Est Completion = $DateEstCompletion,<br>Entry By = $EntryBy, "
		//     . "<br>Date Updated = $DateUpdated,<br>Notes = $Notes";
		//error_reporting(0);
		$sPreviousEstDate = $this->getRIDEstCompletionDate($RIDNum);
		//echo "setRIDEstCompletion prev est date is $sPreviousEstDate";
		if (!$sPreviousEstDate)
		{
			$ErrorString = "Error in setRIDEstCompletion<p>";
			$ErrorString .= "calling CdbRIDs->getRIDEstCompletionDate<p>";
			$ErrorString .= $m_ErrorString;
			$this->m_ErrorString = $ErrorString;
			return false;
		}
		
		// update the RIDs table
		$sSQL =  "UPDATE RIDs SET DateEstCompletion='$DateEstCompletion' ";
		$sSQL .= "WHERE keyRIDs = $RIDNum";
		
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in setRIDEstCompletion<P>";
			$this->m_ErrorString .= "Query error with SQL = <P>" . $sSQL;
			return false;
		}
		//echo "<p>setRIDEstCompletion sql... $sSQL";
		
		// add the assignment change to the notes buffer
		$sNewNotes = "Estimated completion date changed from $sPreviousEstDate";
		$sNewNotes .= " to $DateEstCompletion.";
		
		// update the RIDEvents table
		$sSQL = "INSERT INTO RIDEvents SET fkRIDs='$RIDNum',";
		$sSQL .= "DateEstCompletionPrev='" . mysql_real_escape_string($sPreviousEstDate) . "',";
		$sSQL .= "DateUpdated='" . mysql_real_escape_string($DateUpdated) . "',";
		$sSQL .= "EntryBy='" . mysql_real_escape_string($EntryBy) . "',";
		$sSQL .= "Notes='" . mysql_real_escape_string($sNewNotes) . "'";
		
		//echo "<p>setRIDEstCompletion sql... $sSQL";
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "<P>Error in setRIDEstCompletion";
			$this->m_ErrorString .= "<P>Query error with SQL = '" . $sSQL . "'";
			$this->m_ErrorString .= "<P> " . mysql_error();
			return false;
		}
		return true;
		
	}
	//------------------------------------------------------------------------------
	function setLevel($RIDNum, $bUpDown)
	{
		/* increase or decreases the level of the record identified by $RIDNum
		* 
		*  Calling Params: $RIDNum - RID number
		* 						 $bUpDown -- if TRUE, increase the RID level.
		*                              if FALSE, decrease it
		* 
		*  Returns: TRUE if no errors
		* 
		*  2005-01-19 jee
		* 
		*/
		// get the existing level of the RID
		// add a new record into the table
		$sSQL =  "SELECT Level ";
		$sSQL .= "WHERE keyRIDs = $RIDNum";
 
		$result = mysql_query($sSQL,$this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in setLevel<p>";
			$this->m_ErrorString .= "RID number '$RIDNum' is not found.<p>";
			return false;
		}
		else
		{
			$Level_Old = $result["Level"];
		}
		
		if (is_null($Level_Old))
		{
			$Level_New = 1;
		}
		elseif ($result < 1)
		// 1 is highest level
		{
			$Level_New = 1;
		}
		if ($bUpDown)
		{
				// increase RID number
			$Level_New = $Level_Old + 1;
		}
		else
		{
		// decrease RID number
			$Level_New = $Level_Old - 1;
		}
		// update the RIDs table
		$sSQL = "UPDATE RIDs SET Level='$Level_New'";
		$sSQL .= "WHERE keyRIDs = $RIDNum";
		$result = mysql_query($sSQL,$this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in setLevel<p>";
			$this->m_ErrorString .= "RID number '$RIDNum' can't be updated to level '$Level_New'.<p>";
			return false;
		}
		return true;
	}
	//------------------------------------------------------------------------------
	function setEventNotes($RIDNum, $EntryBy, $DateUpdated, $Notes)
	{
		/* Updates the notes field in 'RIDEvents'
		* 
		*  Calling Params: $RIDNum - previously assigned RID number
		* 								 $EntryBy - name of person entering the completed date
		* 								 $DateUpdated - date that this record was entered
		*									 $Notes - notes for this record (recorded into 'RIDEvents')
		* 
		*  Returns: TRUE if no errors
		* 
		*  2004-01-19 jee
    *  2004-02-11 jee added mysql_escape_string to prevent errors from special chars
    *  2006-06-22 jee changed deprecated mysql_escape_string to mysql_real_escape_string
    *  2008-12-05 jee added test for magic quotes.
    *  2009-03-12 jee added sSanitizeInput
		* 
		*/
    // escape all special characters,
		$Notes = $this->sSanitizeInput($Notes);
		$EntryBy = $this->sSanitizeInput($EntryBy);
    
		// add a new record into the table
		$sSQL =  "INSERT INTO RIDEvents SET fkRIDs='$RIDNum', DateUpdated='$DateUpdated', ";
		$sSQL .= "EntryBy='$EntryBy', Notes='$Notes'";
		
		$result = mysql_query($sSQL,$this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in " . __METHOD__ . "<p>";
			$this->m_ErrorString .= "Query error with SQL = <p>" . $sSQL;
			return false;
		}
		return true;
	}
	//------------------------------------------------------------------------------
	function setRIDComplete($RIDNum, $DateCompleted, $EntryBy, $DateUpdated)
	{
		/* Enters the completed date into two tables:  both 'RIDs' and 'RIDEvents'
		* 
		*  Calling Params: $RIDNum - previously assigned RID number
		* 								 $DateCompleted - date RID was completed
		* 								 $EntryBy - name of person entering the completed date
		* 								 $DateUpdated - date that this record was entered
		* 
		*  Returns: TRUE if no errors
		* 
		*  2004-01-19 jee
		*  2006-06-22 jee added mysql_real_escape_string
		*  2008-07-16 jee removed notes parameter.
		*  2009-03-12 jee added sSanitizeInput
		*  2009-03-17 jee got sSanitizeInput working
		* 
		*/
		// update the RIDs table
		$sSQL = "UPDATE RIDs SET DateCompleted='$DateCompleted' ";
		$sSQL .= "WHERE keyRIDs = $RIDNum";
		
		// echo "<P>" . __METHOD__ . " SQL = $sSQL<P>";
		$result = mysql_query($sSQL,$this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in setRIDComplete<p>";
			$this->m_ErrorString .= "Query error with SQL = <p>" . $sSQL;
			return false;
		}
		//echo "<P>" . __METHOD__ . " with db result " . $result;
		$EntryByEsc = $this->sSanitizeInput($EntryBy);
		//echo "<P>" . __METHOD__ . " Sanitized...";;
		// update the RIDEvents table
		$sSQL = "INSERT INTO RIDEvents SET fkRIDs='$RIDNum', DateUpdated='$DateUpdated'";
		$sSQL .= ",DateCompleted='$DateCompleted', EntryBy='$EntryByEsc'";
		//echo "<P>" . __METHOD__ . " SQL = $sSQL<P>";
		$result = mysql_query($sSQL,$this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in " . __METHOD__ . "<p>";
			$this->m_ErrorString .= "Query error with SQL = <p>" . $sSQL;
			$this->m_ErrorString .= "<br>" . mysql_error();
			return false;
		}
		//echo "<P>Returning from " . __METHOD__;
		return true;
		
	}
	//------------------------------------------------------------------------------
	function getRIDPeople($RIDNum = NULL, $iOriginator = null, $iOpenClosed = null)
	{
		/* returns the either the AssignedTo or Originator field from the RID table
		*
		* Calling Params: $RIDNum - RID number to use.
		* 								    	  - if NULL, return list of all unique assignedTo names
		* 								$bOriginator - either m_SHOW_ORIGINATOR or m_SHOW_ASSIGNED_TO
		* 														 - if null, shows originator
		*                 $iOpenClosed - find people using either m_SHOW_OPEN_RIDS or m_SHOW_CLOSED_RIDS
		*                              - if null, then use m_SHOW_OPEN_RIDS
		*
		* Returns: string with field contents
		*          FALSE if error
		*
		* 2004-01-19 jee
		* 2006-06-22 jee added Date completed test to query.
		* 2009-03-10 jee added $bOriginator param
		* 2009-04-15 jee added $iOpenClosed param, changed $bOriginator to $iOriginator
		* 2009-11-13 jee added rid project
		*                Fri Nov 13 2009 11:40:57 GMT-0500 (EST) tested exception with bad sql
		*
		*/
		if ($this->m_DEBUG) echo "<br/><b>" . __METHOD__ . "</b>: Enter";
		
		if (is_null($this->m_ProjectNum))
		{	
			$this->m_ErrorString = "Error in routine " . __METHOD__ . ":\n\n this->m_ProjectNum is null!";
			throw new Exception($this->m_ErrorString);
		}

		if (is_null($iOriginator) or $iOriginator == $this->m_SHOW_ORIGINATOR)
		{
			$sOrigOrAssignedSQL = "Originator";
		}
		else
		{
			$sOrigOrAssignedSQL = "AssignedTo";
		}
		
		if (is_null($iOpenClosed) or $iOpenClosed == $this->m_SHOW_OPEN_RIDS) 
		{
			// return only open RIDs - these have a null 'DateCompleted' field
			$sDateCompletedSQL = "";
		}
		else
		{
			$sDateCompletedSQL = "NOT";
		}
		
		if (is_null($RIDNum))
		{
			$sSQL =  "SELECT DISTINCT $sOrigOrAssignedSQL ";
			$sSQL .= "FROM RIDs ";
			$sSQL .= "WHERE ((DateCompleted IS $sDateCompletedSQL NULL) ";
			$sSQL .= "AND (fkRIDProjects = '$this->m_ProjectNum')) ";
			$sSQL .= "ORDER BY $sOrigOrAssignedSQL ASC";
		}
		else
		{
			// don't need project number for particular rid
			$sSQL =  "SELECT $sOrigOrAssignedSQL ";
			$sSQL .= "FROM RIDs ";
			$sSQL .= "WHERE keyRIDs = $RIDNum ";
		}

		if ($this->m_DEBUG) echo "<br/><b>" . __METHOD__ . "</b>: SQL:<p>$sSQL<p>";

		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString ="Routine: " . __Method__ . "<br>" . mysql_error(); 
			throw new Exception($this->m_ErrorString);
		}
		
		if (is_null($RIDNum))
		{
			// return entire array
			while ($myrow = mysql_fetch_array($result))
			{
				$recset1[] = $myrow[$sOrigOrAssignedSQL];
			}
			return ($recset1);
		}
		else
		{
			// return just single record
			$myrow = mysql_fetch_array($result);
			{
				//return just single element
				return $myrow[0];
			}
		}
		if ($this->m_DEBUG) echo "<br/><b>" . __METHOD__ . "</b>: exit.";
	}
	//------------------------------------------------------------------------------
	function getDocStatusList()
	{
		/* returns array of document status values
		*
		* Calling Params: None
		*
		* Returns: array with field contents
		*          FALSE if error
		*
		* 2007-04-23 jee
		*
		*/
		$sSQL =  "SELECT DISTINCT Status ";
		$sSQL .= "FROM RIDDocStatus ";
		$sSQL .= "ORDER BY Status ASC";

		//echo "<p>getDocStatusList sql... $sSQL";

		$result = mysql_query($sSQL, $this->m_db);

		//echo "<p>getDocStatusList result ... $result";
		if (!$result)
		{
		 	$this->m_ErrorString = "Error in routine getDocStatusList<p>";
		 	$this->m_ErrorString .= "SQL is <p> $sSQL";
		 	return false;
		}
		// return entire array
		while ($myrow = mysql_fetch_array($result))
		{
			$recset1[] = $myrow["Status"];
		}
		//print_r ($recset1);
		return $recset1;
	}	
	//------------------------------------------------------------------------------
	function getNamesResponsible()
	{
		/* returns array of user names responsible for docs
		*
		* Calling Params: None
		*
		* Returns: array with field contents
		*          FALSE if error
		*
		* 2007-04-24 jee
		* 2007-11-09 jee changed query to return all names in RIDs db
		*
		*/
		//$sSQL =  "SELECT DISTINCT keyRIDNames, Name ";
		//$sSQL .= "FROM RIDNames ";
		//$sSQL .= "ORDER BY Name ASC";
		
		$sSQL  = "SELECT DISTINCT AssignedTo ";
		$sSQL .= "FROM RIDs ";
		$sSQL .= "ORDER BY AssignedTo ASC";

		//echo "<p>getNamesResponsible sql... $sSQL";
		//error_reporting(E_ALL);

		$result = mysql_query($sSQL, $this->m_db);

		//echo "<p>getNamesResponsible result ... $result<br>";
		if (!$result)
		{
		 	$this->m_ErrorString = "Error in routine getNamesResponsible<p>";
		 	$this->m_ErrorString .= "<br>SQL is <p> $sSQL <br>";
		 	return false;
		}
		// return entire array
		while ($myrow = mysql_fetch_array($result))
		{
			$recset1[] = $myrow["AssignedTo"];
		}
		//print_r ($recset1);
		return $recset1;
	}	
	//------------------------------------------------------------------------------
	/**
   * returns the project list and ID from the RID subproject table
   *
   * @return unknown
   */
	function getDocTitlesAndNums($sFilterBy = null)
	{
		/* returns the project list and ID from the RID document table
		* 
		* Calling Params:  - $sFilterBy if set, filter title **or** doc number by this
		*                              can use **or**, because the content of the titles and 
		*                              doc nums differs greatly
		*
		* 
		* Returns: string with field contents
    *          ID, Project Name
		*          FALSE if error
		* 
		* 2004-01-27 jee
		* 2009-03-04 jee added test for no results returned
		* 2009-03-06 jee modified for RIDS
		* 2009-03-10 jee added $sFilterBy
		* 2009-03-11 jee clean return now if no docs found
		* 2009-09-09 jee added m_ProjectNum
		* 2009-11-13 jee fixed m_ProjectNum
		* 
		*/
		if ($this->m_DEBUG) echo "<p><b>" . __METHOD__ . "</b> entered with sFilterBy = '$sFilterBy'";
 
		$sSQL =  "SELECT keyRIDdocs, Name ";
		$sSQL .= "FROM RIDdocs WHERE ";
		// TODO Sanitize this filter input!
		if (isset($sFilterBy))
		{
			$sSQL .= "((Name) Like \"%$sFilterBy%\") AND ";
		}
		$sSQL .= "(fkRIDProjects = '" . $this->m_ProjectNum . "') ";
    $sSQL .= "ORDER BY Name";
		
		if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b> SQL:<br>" . $sSQL . "<p>";
		
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result)
		{
			$this->m_ErrorString = "Error in routine " . __METHOD__ . ": ";
			$this->m_ErrorString .= "Query error with SQL = '" . $sSQL . "'<br>";
			$this->m_ErrorString .= "MySQL returned error: '" . mysql_error() . "'";
			throw new Exception($this->m_ErrorString);
			return false;
		}

    	if (mysql_num_rows ($result) < 1)
		{
			$recset1 = array(0 => "No documents found using this filter!");
		}
		else
		{
			// return entire array
			$recset1 = array(0 => "Select a document");
			while ($myrow = mysql_fetch_array($result))
			{
				//echo "<P>" . __METHOD__ . " .myrow:<P>";
				//print_r ($myrow);
				// add new elements to array
				$recset1[$myrow["keyRIDdocs"]] = $myrow["Name"];
			}
		}
	  	//echo "<P>" . __METHOD__ . " .recset1:<P>";
	  	//print_r ($recset1);
		return ($recset1);
	} 
	//------------------------------------------------------------------------------
	function getRIDEstCompletionDate($RIDNum)
	{
		/* returns the estimated completion date field from the RID table
		* 
		* Calling Params: $RIDNum - RID number to use.
		* 
		* Returns: string with field contents
		*          FALSE if error
		* 
		* 2004-01-19 jee
		* 
		*/
		
		$sSQL =  "SELECT DATE_FORMAT(DateEstCompletion, $this->m_DATE_FORMAT_YYYY_MM_DD) AS DateEstCompletion ";
		$sSQL .= "FROM RIDs ";
		$sSQL .= "WHERE keyRIDs = $RIDNum ";
		
		//echo "<p>getRIDEstCompletionDate sql... $sSQL";
					 
		$result = mysql_query($sSQL, $this->m_db);
		
		//echo "<p>getRIDEstCompletionDate result ... $result";
		if (!$result)
		{
		 	$this->m_ErrorString = "Error in routine getRIDEstCompletionDate<p>";
		 	$this->m_ErrorString .= "SQL is <p> $sSQL";
		 	return false;
		} 

		$DateEstCompletion = mysql_fetch_array($result);
		//echo "<p>getRIDEstCompletionDate AssignedTo is ... $AssignedTo[0]";
		return $DateEstCompletion[0];
		
	}
	//------------------------------------------------------------------------------
	function getPriorityFlag($RIDNum)
	{
		/* returns the TRUE if action item field for RID is set
		* 
		* Calling Params: $RIDNum - RID number to use.
		* 
		* Returns: TRUE IF action item field for RID is set
		*          FALSE otherwise
		* 
		* 2007-01-25 jee
		* 2007-11-07 jee changed from getActionItem to getPriorityFlag
		* 
		*/
		
		$sSQL =  "SELECT PriorityFlagSet ";
		$sSQL .= "FROM RIDs ";
		$sSQL .= "WHERE keyRIDs = $RIDNum ";
		
		//echo "<p>getPriorityFlag sql... $sSQL";
					 
		$result = mysql_query($sSQL, $this->m_db);
		
		//echo "<p>getPriorityFlag result ... $result";
		if (!$result)
		{
		 	$this->m_ErrorString = "Error in routine getPriorityFlag<p>";
		 	$this->m_ErrorString .= "SQL is <p> $sSQL";
		 	return false;
		} 

		$bPriorityFlag = mysql_fetch_array($result);
		//print_r($bPriorityFlag);
		//echo "<p>getPriorityFlag $bPriorityFlag is ... $bPriorityFlag[0]";
		return $bPriorityFlag[0];
	}  
	//------------------------------------------------------------------------------
	/**
	 * returns the AssignedTo field from the events table
	 *
	 * @param numeric $RIDNum
	 * @return Boolean
	 */
	function getEventAssignment($RIDNum)
	{
		/* returns the AssignedTo field from the events table
		* 
		* Calling Params: $SortDef - in number to use.  if null, return all records
		* 
		* Returns: Result 2 dim array with first element record and 2nd fields
		* 
		* 2003-12-22 jee
		* 2004-01-15 jee added test for valid recordset
		* 2004-01-16 jee added test for valide result
		* 2004-01-19 jee changed name from getEventAssignments to getEventAssignment
    	* 2004-02-11 jee changed DateUpdated Format
    	* 2005-04-01 jee added description
		* 
		*/
		
		$sSQL =  "SELECT DATE_FORMAT(DateUpdated, $this->m_DATE_FORMAT_FULL) AS DateUpdated, EntryBy, AssignedPrev, Notes ";
		$sSQL .= "FROM RIDEvents ";
		$sSQL .= "WHERE fkRIDs = $RIDNum ";
		$sSQL .= "AND AssignedPrev IS NOT NULL";
					 
		$result = mysql_query($sSQL, $this->m_db);
		
		if (!$result) return false;

		while ($myrow = mysql_fetch_array($result))
		{
			$recset = array("EntryBy"=>$myrow["EntryBy"], "DateUpdated"=>$myrow["DateUpdated"], "AssignedPrev"=>$myrow["AssignedPrev"], "Notes"=>$myrow["Notes"]);
			$recset1[] = $recset;		// assign each table row to new array element
		}
		if (isset($recset1))
		{
			reset ($recset1);
			return ($recset1);
		}
		else
		{
			return (false);
		}		
		
	} 
	//------------------------------------------------------------------------------
	/*
	 * returns the project ID given the password
	 *
	 */
	function getProjNumFromPassword($ProjPassword)
	{
		/* returns the project ID given the Password.  The password is used in URLs to prevent users from guessing
			* the simple numeric project numbers and hence gaining access to other projects
			*		* 
			* Calling Params: $ProjPassword - password
			* 
			* Returns: Project number, or false if can't find project number
			*          Exception set on error
			* 
			* 2009-11-14 jee
			* 
		*/
		if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b> Entered with ProjPassword = '$ProjPassword'.<br>";
		
		if (is_null($ProjPassword)) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has null password as parameter."; 
			throw new Exception($this->m_ErrorString);
		}
		if (strlen($ProjPassword) == 0) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has empty password as parameter."; 
			throw new Exception($this->m_ErrorString);
		}
		// special case for project number = password if password = 5 (Band 5 CDR)
		// since that URL was sent out before this code was completed
		// TODO Mon Nov 16 2009 10:07:24 GMT-0500 (EST) Remove Band 5 special case ASAP
		if ($ProjPassword != 5)
		{
			if (strlen($ProjPassword) <> PROJECT_PASSWORD_LEN) 
			{
				$this->m_ErrorString ="Routine: " . __Method__ . " password wrong length."; 
				throw new Exception($this->m_ErrorString);
			}
		}

		$sSQL = "SELECT keyRIDProjects FROM RIDProjects WHERE (Password = '$ProjPassword')";
		
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> SQL:<p>$sSQL'<p>";
		
		$result = mysql_query($sSQL, $this->m_db);	
		if (!$result) 
		{
			if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b> Query result false, throwing exception.<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . "<br>" . mysql_error(); 
			throw new Exception($this->m_ErrorString);
		}
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> Query ok.<br>";
		if (mysql_num_rows($result) == 0)
		{
			// no matches
			if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> No matching project number found for password!<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . " returned no matching project number for password '$ProjPassword'.<br>"; 
			throw new Exception($this->m_ErrorString);
		}
		if (mysql_num_rows($result) > 1)
		{
			// too many matches
			if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> too many project numbers (" . mysql_num_rows($result) . ") found for password!<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . " returned more than one project number for password '$ProjPassword'.<br>"; 
			throw new Exception($this->m_ErrorString);
		}

		$myrow = mysql_fetch_array($result);
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> Project number '" . $myrow['0'] . "' found for password.<br>";
		return $myrow['0'];
				
	} 
	//------------------------------------------------------------------------------
	/*
	 * returns the normalized next RID number for this project given the project password
	 *
	 */
	function getRIDNumNextFromProjPassword($ProjPassword)
	{
		/* returns the next available normalized RID number given the project password.  The password is used in URLs to prevent users from guessing
			* the simple numeric project numbers and hence gaining access to other projects
			*		* 
			* Calling Params: $ProjPassword - password
			* 
			* Returns: Next available normalized RID number
			*          Exception set on error or if password can't be found
			* 
			* 2009-11-17 jee
			* TODO Tue Nov 17 2009 09:47:51 GMT-0500 (EST) Untested
			* 
		*/
		if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b> Entered with ProjPassword = '$ProjPassword'.<br>";
		
		if (is_null($ProjPassword)) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has null password as parameter."; 
			throw new Exception($this->m_ErrorString);
		}
		if (strlen($ProjPassword) == 0) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has empty password as parameter."; 
			throw new Exception($this->m_ErrorString);
		}
		// special case for project number = password if password = 5 (Band 5 CDR)
		// since that URL was sent out before this code was completed
		// TODO Mon Nov 16 2009 10:07:24 GMT-0500 (EST) Remove Band 5 special case ASAP
		if ($ProjPassword != 5)
		{
			if (strlen($ProjPassword) <> PROJECT_PASSWORD_LEN) 
			{
				$this->m_ErrorString ="Routine: " . __Method__ . " password wrong length."; 
				throw new Exception($this->m_ErrorString);
			}
		}

		$sSQL = "SELECT RIDProjects.Password, Max((RIDNum)+1) AS RIDNumNext ";
		$sSQL .= "FROM RIDs INNER JOIN RIDProjects ON RIDs.fkRidProjects = RIDProjects.keyRIDProjects ";
		$sSQL .= "GROUP BY RIDProjects.Password ";
		$sSQL .= "HAVING (((RIDProjects.Password)='$ProjPassword'))";
		
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> SQL:<p>$sSQL'<p>";
		
		$result = mysql_query($sSQL, $this->m_db);	
		if (!$result) 
		{
			if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b> Query result false, throwing exception.<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . "<br>" . mysql_error(); 
			throw new Exception($this->m_ErrorString);
		}
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> Query ok.<br>";
		if (mysql_num_rows($result) == 0)
		{
			// no matches
			if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> No matching RID number found for project password!<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . " returned no matching RID number for project password '$ProjPassword'.<br>"; 
			throw new Exception($this->m_ErrorString);
		}

		$myrow = mysql_fetch_array($result);
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> RID number '" . $myrow['0'] . "' found for project password.<br>";
		return $myrow['0'];
				
	}
	//------------------------------------------------------------------------------
	/*
	 * returns the project ID given the password
	 *
	 */ 
	function getProjPasswordFromRIDNum($RIDNum)
	{
		/* returns the password given the RID number.  The password is used in URLs to prevent users from guessing
			* the simple numeric project numbers and hence gaining access to other projects
			*		* 
			* Calling Params: $RIDNum - RID number
			* 
			* Returns: project password number, or false if can't find project number
			*          Exception set on error
			* 
			* 2009-11-14 jee
			* 
		*/
		if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b> Entered with RID num = '$RIDNum'.<br>";
		
		if (is_null($RIDNum)) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has null RID number as parameter."; 
			throw new Exception($this->m_ErrorString);
		}
		if (!is_numeric($RIDNum)) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " non-numeric RID number as parameter."; 
			throw new Exception($this->m_ErrorString);
		}

		$sSQL =  "SELECT RIDProjects.Password ";
		$sSQL .= "FROM RIDs INNER JOIN RIDProjects ON RIDs.fkRidProjects = RIDProjects.keyRIDProjects ";
		$sSQL .= "WHERE (((RIDs.keyRIDs)='$RIDNum'))";
	
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> SQL:<p>$sSQL'<p>";
		
		$result = mysql_query($sSQL, $this->m_db);	
		if (!$result) 
		{
			if ($this->m_DEBUG) echo "<br><b>" . __METHOD__ . "</b> Query result false, throwing exception.<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . "<br>" . mysql_error(); 
			throw new Exception($this->m_ErrorString);
		}
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> Query ok.<br>";
		if (mysql_num_rows($result) == 0)
		{
			// no matches
			if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> No matching project password found for password!<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . " returned no matching project password for RID # '$RIDNum'.<br>"; 
			throw new Exception($this->m_ErrorString);
		}
		if (mysql_num_rows($result) > 1)
		{
			// too many matches
			if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> more than one password (" . mysql_num_rows($result) . ") found for RID #!<br>";
			$this->m_ErrorString ="Routine: " . __Method__ . " returned more than one project number for password '$RIDNum'.<br>"; 
			throw new Exception($this->m_ErrorString);
		}

		$myrow = mysql_fetch_array($result);
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> Project password '" . $myrow['0'] . "' found for RID #.<br>";
		return $myrow['0'];
				
	} 
		//------------------------------------------------------------------------------
	/*
	 * returns the project ID given the Rid number
	 *
	 */
	function getProjectNumber($RIDNum)
	{
		/* returns the project ID given the Rid number
		* 
		* Calling Params: $RIDNum - RID number.
		* 
		* Returns: Project number, or false if can't find project nubmer
		*          Exception set on error
		* 
		* 2009-11-12 jee
		* 2009-11-12 jee tested all error states
		* 
		*/
		if (is_null($RIDNum)) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has null RidNum as parameter."; 
			throw new Exception($this->m_ErrorString);
		}
		elseif (!is_numeric($RIDNum)) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has non-numeric RidNum = '" . $RIDNum . "' (should be > 0)."; 
			throw new Exception($this->m_ErrorString);
		}
		elseif ($RIDNum <= 0) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has bad RidNum = '" . $RIDNum . "' (should be > 0)."; 
			throw new Exception($this->m_ErrorString);
		}
		$sSQL = "SELECT fkRidProjects FROM RIDs WHERE (keyRIDs = $RIDNum)";
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . "<br>" . mysql_error(); 
			throw new Exception($this->m_ErrorString);
		}
		if (mysql_num_rows($result) <> 1)
		{
			// no matches
			return 0;
		}
		else
		{
			$myrow = mysql_fetch_array($result);
			return $myrow['0'];
		}		
	} 
			//------------------------------------------------------------------------------
	/*
	 * returns the project ID given the Rid number
	 *
	 */
	function setProjNumFromPassword($ProjPassword)
	{
		/* sets the project ID given the password
		* 
		* Calling Params: $ProjPassword - project password
		* 
		* Returns: true
		*          Exception thrown on error
		* 
		* 2009-11-14
		* 
		*/
		
		if (is_null($ProjPassword)) 
		{
			$this->m_ErrorString ="Routine: " . __Method__ . " has null RidNum as parameter."; 
			throw new Exception($this->m_ErrorString);
		}
		
		// get the project number from the password
		try 
		{
			$this->m_ProjectNum = $this->getProjNumFromPassword($ProjPassword);
		}
		catch(Exception $e)
		{
			$sBuffErr = "Error in routine " . __METHOD__ . "<br>";
			$this->m_ErrorString = $sBuffErr . $this->m_ErrorString;
			throw new Exception($this->m_ErrorString);
		}
		return true;
	} 
	//------------------------------------------------------------------------------
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $RIDNum
	 * @return unknown
	 */
	function getEventCompleted($RIDNum)
	{
		/* returns the Completion date from the events table
		* 
		* Calling Params: $ID - in number to use.  if null, return all records
		* 
		* Returns: Result 2 dim array with first element record and 2nd fields
		* 
		* 2004-01-16 jee
    	* 2004-02-09 jee changed format of DateUpdated.
    	* 2008-07-29 jee added exception
		* 
		*/
		
		$sSQL =  "SELECT DATE_FORMAT(DateUpdated, $this->m_DATE_FORMAT_FULL) AS DateUpdated, 
		                             DATE_FORMAT(DateCompleted, $this->m_DATE_FORMAT_YYYY_MM_DD) AS DateCompleted, 
		                             EntryBy, Notes ";
		$sSQL .= "FROM RIDEvents ";
		$sSQL .= "WHERE fkRIDs = $RIDNum ";
		$sSQL .= "AND DateCompleted IS NOT NULL";
		
		$result = mysql_query($sSQL, $this->m_db);

		//echo "<P> getEventCompleted:<P>";
		//echo "<P>" . $sSQL . "<P>";
		//print_r ($result);
		//echo "<P> ";
		
		if (!$result)
		{
			$this->m_ErrorString = mysql_error();
			throw new Exception("Error in routine CdbRIDs.getEventCompleted:\n\n SQL: " . $sSQL . "\n\n" . $this->m_ErrorString);
			return false;
		}

		while ($myrow = mysql_fetch_array($result))
		{
			$recset = array("EntryBy"=>$myrow["EntryBy"], "DateUpdated"=>$myrow["DateUpdated"], "DateCompleted"=>$myrow["DateCompleted"], "Notes"=>$myrow["Notes"]);
			$recset1[] = $recset;		// assign each table row to new array element
		}
		if (isset($recset1))
		{
			reset ($recset1);
			return ($recset1);
		}
		else
		{
			return (false);
		}			
	} 
	//------------------------------------------------------------------------------
	function getEventEstCompletion($RIDNum)
	{
		/* returns the Estimated completion date from the events table
		* 
		* Calling Params: $ID - in number to use.  if null, return all records
		* 
		* Returns: Result 2 dim array with first element record and 2nd fields
		* 
		* 2004-01-15 jee
		* 2004-01-16 jee added test for valid result
		* 
		*/
		
		$sSQL =  "SELECT DATE_FORMAT(DateUpdated, $this->m_DATE_FORMAT_FULL) AS DateUpdated, ";
		$sSQL .= "DATE_FORMAT(DateEstCompletionPrev, $this->m_DATE_FORMAT_YYYY_MM_DD) AS DateEstCompletionPrev, ";
		$sSQL .= "EntryBy, Notes ";
		$sSQL .= "FROM RIDEvents ";
		$sSQL .= "WHERE fkRIDs = $RIDNum ";
		$sSQL .= "AND DateEstCompletionPrev IS NOT NULL";
		
		//echo "<p> getEventEstCompletion SQL ... <p>$sSQL<p>";
					 
		$result = mysql_query($sSQL,$this->m_db);
		
		if (!$result) return false;
		
		//echo "<p> getEventEstCompletion:<p>";
		//print_r ($result);
		//echo "<p> ";

		while ($myrow = mysql_fetch_array($result))
		{
			$recset = array("EntryBy"=>$myrow["EntryBy"], "DateUpdated"=>$myrow["DateUpdated"], "DateEstCompletionPrev"=>$myrow["DateEstCompletionPrev"], "Notes"=>$myrow["Notes"]);
			$recset1[] = $recset;		// assign each table row to new array element
		}
		if (isset($recset1))
		{
			reset ($recset1);
			return ($recset1);
		}
		else
		{
			return (false);
		}		
		
	} 
	//------------------------------------------------------------------------------
	function getEventNotes($RIDNum)
	{
		/* returns the notes field from the events table
		* 
		* Calling Params: $ID - in number to use.  if null, return all records
		* 
		* Returns: Result 2 dim array with first element record and 2nd fields
		* 
		* 2003-12-22 jee
		* 2004-01-15 jee added test for valid recordset
		* 2004-01-16 jee added test for valid result too.
		* 2004-01-20 jee added nl2br () to properly display crlf's.'
		* 2007-10-07 jee fixed nasty bug that prevented returning records with duplicate timestamps.
		* 2008-07-16 jee fixed bug where notes entered with completion date were not returned.
		*                Also updated uniqueness algorithm for $myrow["DateUpdated"]
		* 2008-07-28 jee updated to use sKeyReturnUnique
		* 2008-09-26 jee added bFirstTime to fix problem with sKeyReturnUnique not having array parameter
		*                when called the first time.  Tested this fix with a number of event recs with same date,
		*                and also setting completion date.
		* 2008-11-26 jee added stripslashes
		* 
		*/
		
		$bFirstTime = true;
		
		//echo "<p>Entering getEventNotes using RIDNum $RIDNum<p>";
		$sSQL =  "SELECT DATE_FORMAT(DateUpdated, $this->m_DATE_FORMAT_FULL) AS DateUpdated, EntryBy, Notes ";
		$sSQL .= "FROM RIDEvents ";
		$sSQL .= "WHERE fkRIDs = $RIDNum ";
		$sSQL .= "AND Notes IS NOT NULL ";

		// echo "<p>getEventNotes sql is ... $sSQL<p>";
		
		$result = mysql_query($sSQL, $this->m_db);
		
		if (!$result) return false;
		
		//echo "<br>getEventNotes: ";
				
		while ($myrow = mysql_fetch_array($result))
		{
			//echo "<P>getEventNotes arraykeys<br>";
			//print_r(array_keys($myrow));
			//echo "<br>Date Updated: " . $myrow["DateUpdated"] . "<br>\n";
			//print_table($myrow);
			//print_r($myrow);
			
			// check the index for duplicate dates (after the $recset has at least one value)
			if ($bFirstTime)
			{
				$bFirstTime = false;
			}
			else
			{
				$myrow["DateUpdated"] = sKeyReturnUnique($myrow["DateUpdated"],$recset);
			}
			// retrieve the data, but strip slashes from the records first
			$recset[$myrow["DateUpdated"]] = array("EntryBy"=>stripslashes($myrow["EntryBy"]), 
			                                       "DateUpdated"=>$myrow["DateUpdated"], 
			                                       "Notes"=>nl2br (stripslashes($myrow["Notes"])));
		}
		
		if (isset($recset))
		{
			reset ($recset);
			return ($recset);
		}
		else
		{
			return (false);
		}		
	}
	//----------------------------------------------------------------------------
	function getNumOpenAndClosedRids(&$NumRidsOpen, &$NumRidsClosed)
	{
		/*
		 * Returns the number of open and closed RIDs
		 * 
		 * Calling Params: None
		 * Returns: $NumRidsOpen - number of Rid notes still
		 *          $NumRidsClosed - number of Rid notes closed
		 * 
		 * 2009-03-30 jee
		 * 2009-09-09 jee updated and tested try-catch
		 * 2009-11-13 jee added project number
		 *            Fri Nov 13 2009 11:58:21 GMT-0500 (EST) tested with bad SQL
		 * 
		 */
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> Entered.<br>";
		
		if (is_null($this->m_ProjectNum))
		{	
			$this->m_ErrorString = "Error in routine " . __METHOD__ . ":\n\n this->m_ProjectNum is null!";
			throw new Exception($this->m_ErrorString);
		}
		
		// open Rids
		$sSQL = "SELECT count(*) FROM RIDs WHERE ((DateCompleted IS NULL) AND (fkRIDProjects = '$this->m_ProjectNum'))";
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result) 
		{
			$this->m_ErrorString ="<br>Routine: <b>" . __Method__ . "</b><p>SQL:<p>$sSQL<p>"; 
			$this->m_ErrorString .="<br>" . mysql_error(); 
			throw new Exception($this->m_ErrorString);
		}
		$myrow = mysql_fetch_array($result);
		$NumRidsOpen = $myrow['0'];
		//print_r ($myrow);
		if (is_null($NumRidsOpen) or ($NumRidsOpen <= 0))
		{
			$NumRidsOpen = 0;
		}
				
		// closed Rids
		$sSQL = "SELECT count(*) FROM RIDs WHERE ((DateCompleted IS NOT NULL) AND (fkRIDProjects = '$this->m_ProjectNum'))";
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result) 
		{
			$this->m_ErrorString ="<br>Routine: <b>" . __Method__ . "</b><p>SQL:<p>$sSQL<p>"; 
			$this->m_ErrorString .="<br>" . mysql_error(); 
			throw new Exception($this->m_ErrorString);
		}
		$myrow = mysql_fetch_array($result);
		$NumRidsClosed = $myrow['0'];
		if (is_null($NumRidsClosed) or ($NumRidsClosed <= 0))
		{
			$NumRidsClosed = 0;
		}
		return;	
		if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> exit.<br>";
	}
		//----------------------------------------------------------------------------
	function getNumRIDNotes($RidNum)
	{
		/*
		 * Returns the number of notes for a given RID number
		 * 
		 * Calling Params: $RidNun - RID number 
		 * Returns: number of notes, or 0 if nun
		 * 
		 * 2009-03-25 jee
		 * 
		 */
		//echo "<P>" . __METHOD__ . " Entered with RidNum = '$RidNum'";
		if (is_null($RidNum))
		{
			$m_ErrorString ="<br>Routine: " . __Method__ . 
						 					"<br>Number of RID Notes not passed to this routine." .
						 					"<P>Program ends.";
			throw new Exception($m_errorString);
		}
		//error_reporting(E_ALL);
		
		$sSQL = "SELECT count(*) FROM RIDEvents WHERE fkRIDs = $RidNum";
		
		$result = mysql_query($sSQL, $this->m_db);
		if (!$result) 
		{
			echo "<P>" . __METHOD__ . " Error for RidNum = '$RidNum'<P>" . mysql_error() ;
			$m_ErrorString ="<br>Routine: " . __Method__ . 
						 					"<br>" . mysql_error() . 
						 					"<P>Program ends.";
			throw new Exception($m_errorString);
		}
		$myrow = mysql_fetch_array($result);
		$NumRids = $myrow['0'];
		//print_r ($myrow);
		if (is_null($NumRids))
		{
			return 0;
		}
		elseif ($NumRids <= 0)
			return 0;
		else
		{
			return $NumRids;
		}
	}
	//----------------------------------------------------------------------------
	function getRIDs($ID = null, $AssignedTo = null, $Priority = null)
	{
		/* returns the RIDs for the listing
		*
		* Calling Params: $ID - ID number of RID to return.
		* 								    - if $m_SHOW_OPEN_RIDS, show all incompleted RIDs
		* 							  		- if null or $m_SHOW_CLOSED_RIDS, return all records
		*                 $Originator 		- return just RIDs with this person as originator
		*                 $AssignedTo 		- return just RIDs given name of person assigned to them
		*                 $Priority 		- return RIDs with at least this priority.
		*                             		  don't filter on this if null.
		* 
		* Returns: Result 2 dim array with first element record and 2nd fields
		* 
		* 2003-12-12 jee
		* 2003-12-16 jee removed initial fetch which was loosing first record.
		* 2003-12-22 jee added $ID parameter
		* 2004-01-16 jee checked for valid result set
		* 2004-01-22 jee Added $AssignedTo
		* 2004-01-23 jee removeved resetting $recset1
		* 2004-01-26 jee added Project column
		* 2006-01-12 jee added level, next, and parent fields
		* 2009-03-10 jee added $Originator
		* 2009-03-12 jee replaced RespondersSolution with SuggestedSolution
		* 2009-11-12 jee cleaned up log statements
		* 2009-11-13 jee
		* 2009-11-17 jee now returns project password, for passing onto the rid number hyperlink
		*/

		if ($this->m_DEBUG) echo "<b>" . __METHOD__. "</b> Enter with params ID = '$ID' and AssignedTo = '$AssignedTo'<br />";
	
		// return RIDs with Originator
		try
		{
			// TODO TEST THIS Thu Nov 12 2009 14:07:16 GMT-0500 (EST)
			$result = $this->getRIDsSorted($ID, $AssignedTo, $Priority, true);
		}
		catch(Exception $e)
		{
			$sBuffErr = "Error in routine " . __METHOD__ . "<br>";
			$sBuffErr .= "Exception returned from call to ". __CLASS__ . ".getRIDsSorted<br>";
			$this->m_ErrorString = $sBuffErr . $this->m_ErrorString;
			throw new Exception($this->m_ErrorString);
		}
		// return key names in array, not both key names and numeric keys
		while ($myrow = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			//echo "<P>" . __METHOD__ . " - Result:<P>";
			//print_r ($myrow);
			try
			{
				$NumRidNotes = $this->getNumRIDNotes($myrow["keyRIDs"]);
			}
			catch (Exception $e)
			{
				// TODO Fri Nov 13 2009 10:26:52 GMT-0500 (EST) TEST THIS
				$sBuffErr = "Error in routine " . __METHOD__ . "<br>";
				$sBuffErr .= "Calling ". __CLASS__ . ".getNumRIDNotes<br>";
				$this->m_ErrorString = $sBuffErr . $this->m_ErrorString;
				throw new Exception($this->m_ErrorString);
			}
			if (is_null($this->m_ProjectPassword) or strlen($this->m_ProjectPassword) == 0)
			{
				$sBuffErr = "Error in routine " . __METHOD__ . "<br />";
				$sBuffErr .= "Project password is null.";
				$this->m_ErrorString = $sBuffErr;
				throw new Exception($this->m_ErrorString);
		  }
			if ($this->m_DEBUG) echo "<b>" . __METHOD__. "</b> Project password: '$this->m_ProjectPassword'<br />"; 
			$recset = array("key"=>$myrow["keyRIDs"], "ProjPassword"=>$this->m_ProjectPassword, "DocTitle"=>$myrow["DocTitle"], "RIXType"=>$myrow["RIXType"], "MajorRID"=>$myrow["MajorRID"],
			                "Originator"=>$myrow["Originator"], "DateEntered"=>$myrow["DateEntered"], 			                
			                "Description"=>$myrow["Description"], "ChapterSectionPage"=>$myrow["ChapterSectionPage"], 
											"SuggestedSolution"=>$myrow["SuggestedSolution"], 
											"RespondersSolution"=>$myrow["RespondersSolution"], "NumRidNotes"=>$NumRidNotes);


			$recset1[] = $recset;		// assign each table row to new array element
		}
		if ($this->m_DEBUG) echo "<b>" . __METHOD__. "</b> recset:<p>"; 
		if ($this->m_DEBUG) print_r($recset);
		if ($this->m_DEBUG) echo "<b>" . __METHOD__. "</b> exit.<br />";
		return ($recset1);
	}
	//----------------------------------------------------------------------------
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	function sGetError()
	{
		/* returns the last sql error string
		* 
		* 2003-11-12 jee
		* 2004-01-19 jee added error string to return
    * 2004-01-22 jee added test for mysql_error
    * 2006-07-18 jee added $this->m_db
		*/
	  //echo "<br>In CdbRIDs->getError ...<br>";
		$ErrorString = $this->m_ErrorString;
		//echo "<br>CdbRIDs->getError has error string: '$ErrorString'<br>";
    $sDataBaseError = mysql_error($this->m_db);
    if (strlen($sDataBaseError > 0))
    {
		  $ErrorString .= "<P>Database returns the following error statement: <P>" .$sDataBaseError;
    }
		return $ErrorString;
	}
  //----------------------------------------------------------------------------
	function getRIDsSorted($RIDNum = null, $People = null, $Days2Show = null, $bOriginator = false)
	{
    /*
		* queries the database for a specified RID or for all complete or incomplete RIDs
		* 
		* Calling Parms: $RIDNum 	- ID for RID
		*                         - 	if null, show open rids
		*                    			- 	m_SHOW_CLOSED_RIDS - show all completed RIDs
		* 					 							- 	$m_SHOW_OPEN_RIDS return incomplete RIDs
		*                    			- 	m_SHOW_RECENT_ACTIVITY - show only RIDs with activity
		*                    			- 	m_SHOW_HIGH_PRORITY - SHOWS all RIDs with events marked Action Item
		*                $People - if not null, return only records with this value in 'AssignedTo' or 'Originator' fields
		*                $Days2Show - just return RIDs having activity within this many days into the past
		*                $bOriginator - if true, return entries from Originators field
		*                               if false, return entrries from AssignedTo field
		* 
		* Returns: db resource
		* 
		* 2003-12-11 jee now working
		* 2003-12-16 jee removed DISTINCT
		* 2003-12-22 jee added $RIDNum parameter
		* 2004-01-16 jee now can return specific RID either completed or incomplete
		* 2004-01-21 jee added $People parameter
	  * 2004-01-26 jee added inner joins for project and documents
	  * 2005-12-14 jee added $Priority argument
	  * 2006-01-12 jee added Level, parent, and next fields 
	  * 2006-06-22 jee changed date format to DD MMM
	  * 2006-06-22 jee changed date format back to YYYY-MM-DD
	  * 2006-06-26 jee added $Days2Show
	  * 2007-01-04 jee added m_SHOW_RECENT_ACTIVITY
	  * 2007-01-25 jee added m_SHOW_HIGH_PRORITY
	  * 2007-02-02 jee added m_SHOW_DOCUMENTATION
	  * 2007-09-06 jee changed m_SHOW_DOCUMENTATION to m_SHOW_HIGH_PRORITY
	  *                added RIDsdocuments.Priority ordering
	  *                updated query for m_SHOW_RECENT_ACTIVITY
	  * 2007-11-15 jee changed SQL logic
	  * 2007-11-19 jee cleaned up "RIDs with Recent Activity"
	  * 2007-11-20 jee added $bSortSpecial and sorting by RIDLevel for resume RIDs
	  * 2007-11-26 jee got resume sorting working with a name assigned.
	  * 2009-03-06 jee updated for RIDs
	  * 2009-03-06 jee removed RIDNext Field
	  * 2009-03-07 jee changed notes to description
	  * 2009-03-10 jee added Originator parameter, removed Priority, bSortSpecial
	  * 2009-03-25 jee added ChapterSectionPage to query
	  * 2009-09-10 jee added m_ProjectNum
	  * 2009-11-12 jee added test for null m_ProjectNum 
	  * 2009-11-12 jee tested all RIDNum types
	  * 2009-11-13 jee removed inner join of Proj table, added test for Proj Num
	  * 2009-11-17 jee now returns project password, for passing onto the rid number hyperlink
		* 
		* 
    */
		//Test vectors
		//$RIDNum = $this->m_SHOW_OPEN_RIDS;
		//$RIDNum = $this->m_SHOW_CLOSED_RIDS;

    if ($this->m_DEBUG) echo "<b>" . __method__ . "</b> Entering with RIDNum = '$RIDNum', People = '$People', 
		                         Days2show = '$Days2Show', and bOriginator = '$bOriginator'<br>";
    
	  if ($bOriginator)
		{
			$sPeopleField = "Originator";
		}
		else
		{
			$sPeopleField = "AssignedTo";
		}
		// if True, then all sorting information must be in main query construction
		//$bSortSpecial = false;
				
    $sSQL =  "SELECT DISTINCTROW keyRIDs, RIDdocs.Name AS DocTitle, Originator, ";
		$sSQL .= "DATE_FORMAT(RIDs.DateEntered, $this->m_DATE_FORMAT_YYYY_MM_DD) AS DateEntered, ";
    $sSQL .= "RIDs.Description, RIDs.ChapterSectionPage, RIDs.SuggestedSolution, RIDs.RespondersSolution, ";
    $sSQL .= "RIXType, Major as MajorRID ";

	if (!is_null($RIDNum) and $RIDNum >= 0)
	{
		// Real RID number must be a key value (which are non-negative)
		if (is_null($this->m_ProjectNum)) 
		{
			// first, get the project number for this rid number
			try	
			{
				$this->m_ProjectNum = $this->getProjectNumber($RIDNum);
			}
			catch(Exception $e)	
			{
				$sBuffErr = "Error in routine " . __METHOD__ . "<br>";
				$sBuffErr .= "Exception returned from call to ". __CLASS__ . ".getProjectNumber<br>";
				$this->m_ErrorString = $sBuffErr . $this->m_ErrorString;
				throw new Exception($this->m_ErrorString);
				return false;
			}
		}
		$sSQL .= "FROM (RIDs ";
		$sSQL .= "INNER JOIN RIDdocs ON RIDs.fkRIDdocs = RIDdocs.keyRIDdocs) ";
		//$sSQL .= "INNER JOIN RIDProjects ON RIDdocs.fkRIDProjects = RIDProjects.keyRIDProjects ";
		$sSQL .= "WHERE ((keyRIDs = '$RIDNum') ";
	}
	elseif (is_null($RIDNum) 
													or $RIDNum == $this->m_SHOW_OPEN_RIDS 
													or $RIDNum == $this->m_SHOW_CLOSED_RIDS
													or $RIDNum == $this->m_SHOW_RECENT_ACTIVITY)
	{
		$sSQL .= "FROM (RIDs ";
		$sSQL .= "INNER JOIN RIDdocs ON RIDs.fkRIDdocs = RIDdocs.keyRIDdocs) ";
		//$sSQL .= "INNER JOIN RIDProjects ON RIDs.fkRIDProjects = RIDProjects.keyRIDProjects ";
		if (is_null($RIDNum) or $RIDNum == $this->m_SHOW_OPEN_RIDS)
		{
				$sSQL .= "WHERE (DateCompleted IS NULL ";
		}
		elseif ($RIDNum == $this->m_SHOW_CLOSED_RIDS)
		{
	 		$sSQL .= "WHERE (DateCompleted IS NOT NULL ";
		}
		elseif ($RIDNum == $this->m_SHOW_RECENT_ACTIVITY)
		{
		 	$this->m_ErrorString = "Error in routine " . __METHOD__ . "<br>";
			$this->m_ErrorString .= "Sort type 'this->m_SHOW_RECENT_ACTIVITY' is not yet coded!";
			throw new Exception($this->m_ErrorString);
		}
	}
	/* TODO GET THIS WORKING
	elseif ($RIDNum == $this->m_SHOW_RECENT_ACTIVITY)
	{
		$sSQL .= "FROM (RIDs INNER JOIN RIDEvents ON RIDs.keyRIDs=RIDEvents.fkRIDs) ";
		$sSQL .= "INNER JOIN RIDdocs ON RIDs.fkRIDdocs=RIDdocs.keyRIDdocs ";
		$sSQL .= "WHERE (((RIDEvents.DateUpdated)>Now()- INTERVAL 7 DAY) AND ((.keyRIDProjects)=$this->m_ProjectNum)";
	}
	elseif ($RIDNum == $this->m_SHOW_HIGH_PRORITY)
	{
		$sSQL .= "FROM RIDs ";
		$sSQL .= "INNER JOIN RIDdocs ON RIDs.fkRIDdocs = RIDdocs.keyRIDdocs ";
		$sSQL .= "WHERE ((RIDs.DateCompleted IS NULL) AND (PriorityFlagSet) AND ((RIDProjects.keyRIDProjects)=$this->m_ProjectNum)";
	}
	*/
	else
	{
		// bad ID
		$this->m_ErrorString = "Error in routine " . __METHOD__ . "<br>";
		$this->m_ErrorString .= "Bad Rid number parameter  '" . $RIDNum ."'.";
		throw new Exception($this->m_ErrorString);
	}		
	if (!is_null($People))
	{
		if ($People == "None")
		{
		 // don't use AssignedTo filter
		}
		else
		{
			// sort by selected people, either AssignedTo or Originator field
		    $sSQL = $sSQL . " AND ($sPeopleField = '$People') ";
		}
	}
	$sSQL = $sSQL . "AND (RIDs.fkRIDProjects = $this->m_ProjectNum)) ORDER BY RIDdocs.Priority DESC, RIDdocs.Name ASC, keyRIDs DESC";
	
	if ($this->m_DEBUG) echo "<b>" . __METHOD__ . "</b> SQL = <p>'$sSQL'<p>";
	
	$this->m_result = mysql_query($sSQL, $this->m_db);
	if (!$this->m_result) 
	{
		$this->m_ErrorString ="Routine: " . __Method__ . "<br><br>" . mysql_error(); 
		throw new Exception($this->m_ErrorString);
	}
	return $this->m_result;
		
}
	//----------------------------------------------------------------------------
	function setInsertNewRID($sfkRIDDocs, $sDocTitle, $sDocNum, $sChapterSectionPage, 
							 $sDateEntered = null, $bMajorRID = false, 
							 $sRIXType, $sDescription,  
	                         $sSuggestedSolution = null, $sOriginator )
	{
		/*
		* Inserts a new RID record into the database
		* Calling Parms: 	$sfkRIDDocs - fk into the docs table
		*                 $sDocTitle - title 
		* 							  $sDocNum - document number
		* 								$DateEntered - date that RID was entered into database
		*                                if null, will use current date
		*                 $bMajorRID - if true, RID is major type
		*                 $sRIXType - RID, RIX, or RIC
		*                 $sDescription - DocTitle of RID							
		*                 $sSuggestedSolution - by originator
		* 								$sOriginator - person who wrote RID
		* 
		* Returns: false if error in db update
		* 
		* 2003-12-16 jee
		* 2004-01-27 jee added project field
		* 2006-07-18 jee added mysql_real_escape_string and mysql_error.
		* 2006-08-11 jee fixed problem with estimated completion date.  Var name was wrong: $ssqlL rather than $sSQL!
		* 2007-11-28 jee added bHighPriority
		* 2009-03-07 jee rewrote for RIDs
		* 2009-09-09 jee updated for project type
		* 2009-09-10 jee corrected error in sql
		* 
		*/
		//echo "Inside setinsertNewRID";
		//error_reporting(0);
		if (is_null($sDateEntered))
		{
			$sDateEntered = date("Y-m-d H:i:s");
		}
				
		$sSQL =  "INSERT INTO RIDs ";
		$sSQL .= "SET fkRIDProjects='" . $this->m_ProjectNum . "', ";
		$sSQL .= "DateEntered='" . $this->sSanitizeInput($sDateEntered) . "', ";
		$sSQL .= "fkRIDdocs='" . $this->sSanitizeInput($sfkRIDDocs) . "', ";
		$sSQL .= "DocTitle='" . $this->sSanitizeInput($sDocTitle) . "', ";
		$sSQL .= "DocNum='" . $this->sSanitizeInput($sDocNum) . "', ";
		$sSQL .= "ChapterSectionPage='" . $this->sSanitizeInput($sChapterSectionPage) . "', ";
		$sSQL .= "Major='" . $this->sSanitizeInput($bMajorRID) . "', ";
		$sSQL .= "RIXType='" . $this->sSanitizeInput($sRIXType) . "', ";
		$sSQL .= "Description='" . $this->sSanitizeInput($sDescription) . "', ";
		$sSQL .= "SuggestedSolution='" . $this->sSanitizeInput($sSuggestedSolution) . "', ";
		$sSQL .= "Originator='" . $this->sSanitizeInput($sOriginator). "'";
 
		//echo $sSQL . "<br>";
  		//echo (mysql_affected_rows() ? "<P>Database successfully updated!" : "<P>Database update failed!");
				
		$result = mysql_query($sSQL, $this->m_db);	
		if (!$result)
		{
			$this->m_ErrorString = "<br>Error in routine " . __METHOD__ . "<br><br>";
			$this->m_ErrorString .= "Query error with SQL = '" . $sSQL . "'<br><br>";
			$this->m_ErrorString .= "MySQL returned error: '" . mysql_error() . "'";
			throw new Exception($this->m_ErrorString);
			return false;
		}
    	return $this->m_result;	
	}
	//----------------------------------------------------------------------------
	function sSanitizeInput($sInput)
	{
		/* this routine cleans up the input so that it's acceptable for SQL updates.
		 * From http://us.php.net/mysql_real_escape_string
		 * 
		 * Calling Param: $sInput STRING
		 * Returns: Sanitized string
		 * 		
		 * 2009-03-11 jee
		 * 2009-03-19 jee turned off echo of output.
		*/
		
		if(get_magic_quotes_gpc()) 
		{
       $sbuff = stripslashes($sInput);
    }
		else 
		{
    	$sbuff = $sInput;
    }
    $sbuff = mysql_real_escape_string($sInput, $this->m_db);
		
		// mysql_real_escape_string doesn't check the backslash (left of 1 key)
		$sbuff = str_replace("`", "\`", $sbuff);
		//echo __METHOD__ . "<P>$sbuff";
		return $sbuff;
	}
}
//***************************************************************************
function stripper($stringvar)
	/* checks if magicquotes are on, if so, it strips them off
	 * 
	 * from http://us.php.net/manual/en/function.get-magic-quotes-gpc.php
	 * 
	 * 2009-03-11 jee
	 */
{ 
    if (1 == get_magic_quotes_gpc()){ 
        $stringvar = stripslashes($stringvar); 
    } 
    return $stringvar; 
} 
//***************************************************************************
function AddToDate($pMonth, $pDay, $pYear)
{
	// returns a date as a string after adding the appropriate units to the present time
	//
	// 2002-11-12 jee
 	// 2003-08-21 jee removed time from date stamp
	//
	$timestamp =  time();
	$date_time_array =  getdate($timestamp);

	$month =  $date_time_array["mon"] + $pMonth;
	$day =  $date_time_array["mday"] + $pDay;
	$year =  $date_time_array["year"] + $pYear;

	// use mktime to recreate the unix timestamp
	// adding 4 weeks to $day and forcing the time to 5 PM
	$hours = 17;
	$minutes = 0;
	$seconds = 0;
	$timestamp =  mktime($hours, $minutes, $seconds , $month, $day, $year);
	return date("Y-m-d", $timestamp);
}
//***************************************************************************
function ConvertDate($pDate)
{
	/*checks a date and returns it as 'YYYY-MM-DD HH:MM:SS'
	* Returns false if date can't be converted
	*
	* 2002-11-14 jee
  * 2003-08-21 jee changed from "Y-m-d H:i:s" to $DATE_FORMAT
	* 2004-01-20 jee changed return value to false, and created class to get date
	*                format.
	* 2006-06-22 jee removed database class instantiation!
	* 2008-09-30 jee removed reference to odbRIDs, since the instantiation was removed long ago.
	*                Also, the return value was fasle, not false!
	*/

	$timestamp = strtotime($pDate);
	if ($timestamp == -1)
	{
		// bad format
		return false;
	}
	// format is YYYY-MM-DD HH:MM:SS
	return date("Y-m-d H:i:s", $timestamp);
}
//***************************************************************************
/**
 * Enter description here...
 *
 *  Calling Parms: $BoxName - name of vars returned when box is selected
 *                 $List - array with elements to add to box
 *                $Selected -if non-null, highlight this item
 * 
 *  Returns: HTML of select box
 *           False if error
 *
 * @param unknown_type $BoxName
 * @param unknown_type $List
 * @param unknown_type $Selected
 * @return unknown
 */
 

function SelectBoxFill($BoxName, $List, $Selected = null)
{
	/* Returns the HTML for a select box filled with the contents of the array
	* 
	*  Calling Parms: $BoxName - name of vars returned when box is selected
	*                 $List - array with elements to add to box
	*                 $Selected -if non-null, highlight this item
	* 
	*  Returns: HTML of select box
	*           False if error
	* 
	*  2004-01-20 jee
  *  2004-01-22 jee finally, after 2 days of testing, fixed stupid typo!
  *  2004-03-10 jee fixed typo for SELECTED
  *  2005-04-01 jee minor spacing fixes
	* 
	*/
	
  $sHTML = "<SELECT NAME=\"$BoxName\">";
	$Name = "";
	$ListValue = "";
	//echo "<P>SelectBoxFill: Box Name is $BoxName AND Selected Parameter is $Selected<P>";
 	//echo "<P>SelectBoxFill: List parameter is:<P>";
  //print_r($List);
  

	foreach ($List as $key => $value)
	{
		$sHTML .= "<OPTION VALUE=\"";
		$sHTML .= $key;
		if ($Selected == $value)
		{
			$sHTML .= "\" SELECTED=\"SELECTED";
		}
		$sHTML .=  "\">";		// the variable and description have the same value
		$sHTML .= $value  . "</OPTION>";
	}
	$sHTML .= "</SELECT>";
	//echo "<P>SelectBoxFill HTML is ...<P> $sHTML";
	
	return $sHTML;
}
//***************************************************************************
function HyperlinkInsert($str)
{
	// searches for "http://" or "//cvfiler" or "\\cvfiler" in string and if found, adds HREF to it
	//
	// Calling Parms: $str - string to search
	// Returns: string with HREFs or regular string if nothing found
	//
	// 2007-02-01 jee
	// 2007-03-08 jee added more strings for stop positions
	// 2007-10-23 jee added test for "//cvfiler" or "\\cvfiler"
	// TO DO: Can't get this to work with Mozilla!
	// 2007-11-20 jee now case insensitive
	// 2007-12-03 jee now searches for https, too.
	// TO DO: search for https doesn't work.
	// 2008-10-06 jee see notes for if ($bSlashForward)
	//
			      
	//$strTestStart = "http://";
	$arrTestStart = array('http://', 'https://', '//cvfiler', "\\\cvfiler");
	//print_r ($arrTestStart);
	$arrTestStop = array(" ", "<", "\n", "\t", "\r");
	$bSlashForward = false;
	$bSlashReverse = false;
	
  // look for starting label
	reset($arrTestStart);
	$posStart = false;
	// look in $str for each element of the start array $arrTestStart
	while (list($key, $value) = each($arrTestStart))
	{
		$posStart = strpos(strtoupper($str), strtoupper($value));
   	if ($posStart !== false)
   	{
   		// search string found, check for type
   		//echo "<br>At location '$posStart 'found '$value' in '$str'.";
	   	if ($value === '//cvfiler') $bSlashForward = true;
	   	if ($value === '\\\cvfiler')
	   	{
	   		// replace reverse slashes with forward slashes for Mozilla
	   		$str = strtr($str,"\\","/");
	   		//echo "<br> replaced backward slashes:" . $str;
	   		$bSlashForward = true;
	   	}
	   	break;
   	}
	}
	if ($posStart === false) 
	{
	   // URL/UNC not in string
	   return $str;
	}
	 
	// URL/UNC found, assume space defines end of link, starting from http label
	reset($arrTestStop);
	$posStop = false;
	while (list($key, $value) = each($arrTestStop)) 
	{
		$posStop = strpos($str, $value, $posStart);
   	if ($posStop !== false)
   	{
   		// stop position found
   		//echo "<br>Stop Position found - StopCharKey = '$key', Pos Start = '$posStart', Pos Stop = '$posStop'";
   		break;
   	}
	}
	if ($posStop === false)
	{
		// no delimeter found, but http string found, so assume hyperlink runs to end of string
		$posStop = strlen($str);
		//echo "<br>Didn't find stop delimeter...\r";
	}
	if ($posStop >= $posStart)
	{
		// valid stop position found for hyperlink text, insert end text
		if ($posStop === strlen($str))
		{
			// hyperlink runs to end of string, append HREF terminator
			$str .= "\">link</A>";
			//echo "<br>Replaced at end of string of length " . strlen($str) . "\r";
		}
		else
		{
			// end of hyperlink is inside string
			$str = substr_replace($str, "\">link</A> ", $posStop-1, 0);
			//echo "<br>Replaced string of length " . strlen($str) . " at $posStop\r";
		}
		
		// insert HREF text at beginning of string
 		if ($bSlashForward)
		{
			// forward slash, so add three more so mozilla works with link
			//	TODO:  2008-02-11 JEE CAN'T QUITE GET THIS WORKING.
			//  2008-10-06 from http://www.velocityreviews.com/forums/t109881-put-a-href-to-file-w-unc.html
			// <a href="file://\\server
			// Nope, the Mozilla site says this will now work by design: http://locallink.mozdev.org/
			//
			//echo "<br>Forward slashes....<br>" . $str . "\r";

			//$strBuff = "<A HREF=\"///";
			$strBuff = "<A HREF=\"file:";
		}
		else
		{
			// normal http ref
			//echo "<br>HTML string...<br>" . $str;
			$strBuff = "<A HREF=\"";
		}
	  
		$str = substr_replace($str, $strBuff, $posStart, 0);
		//echo substr_replace($str, 'bob', 0, 0) . "<br />\n";

		//$str = "Start = $posStart<br>Stop = $posStop<br>" . $str;
		
	}
	//echo "<br>Final string is $str<br>\r";
	return $str;
}
//***************************************************************************
function print_table( &$array ) 
{
		// from PHP manual
		// 2007-10-07 jee
		//
		echo "<br>In print-table with array:<br>";
		print_r($array);
    $array = array_values( $array );
    
    $keys = array_keys( $array[0] );
    
    echo '<table border="1"><tr>';
    foreach( $keys as $key ) {
        echo '<td>'.$key.'</td>';
    }
    echo '</tr>';
    
    foreach( $array as $row ) {
        echo '<tr>';
        foreach( $row as $value ) {
            echo '<td>'.$value.'</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}
//****************************************************************************
function sKeyReturnUnique($key, $array)
{
		// checks array for existing $key.  If $key exists in array, then returns 
		// unique key
		// Calling Parms: $key - key value to check
		//                $array - array to check
		// Returns:       $key unchanged if it doesn't exist in array, or
		//                     appended with (1) if it already exists
		//
		// 2008-07-28 jee
		// 2008-09-26 jee added array type casting for $array arg
		// 2009-03-17 jee removed type casting for array arg, and added test instead
		//
		$Index = 0;
		
		if (!is_array($array))
		{
			// no array given, so just return key
			return $key;
		}
		else
		{
			while (array_key_exists ( $key, $array ))
			{
				// this key already exists, so modify so that array element isn't lost
				// check if char already appended
				$PosUnderScore = strpos($key,"_");
				if ($PosUnderScore > 0)
				{
					// number already appended, append new number
					$key = substr_replace($key,"_".$Index, $PosUnderScore);
				}
				else
				{
					// no number appended yet
					$key .= "_" . $Index;
				}
			$Index += 1;
			}
			return $key;
		}
}
//************************************************************************************************
class NewNoteState 
{ 
	/* Flag class for status
	 * TODO 2009-03-17 JEE THIS DOESN'T WORK!
	 * 
	 * Usage:
	   $art = new Artwork(); 

			$art->setFlag( 'has_name', true ); 
			$art->setFlag( 'has_RID_complete', false ); 
			$art->setFlag( 'has_notes', true ); 
			
			
				if( $art->getFlag( 'has_name' ) ) { 
				    do something
				} 
			 

	 * 
	 * from http://us.php.net/manual/en/language.operators.bitwise.php
	 * 2009-03-16 jee
	 */
  protected static $has_name = 1;  				// bitmask for 1st bit (0x1) - name field set
  protected static $has_RID_complete = 2;     // bitmask for 2nd bit (0x2) - complete bit set
  protected static $has_notes = 4;    		// bitmask for 3rd bit (0x4) - notes field set 
  protected static $retry = 8;    				// bitmask for 3rd bit (0x8) - error in checking -- retry
  protected static $cookie_found = 16;		// found cookie for the page
  protected static $first_time = 32;			// first time through page
  protected static $post_to_db = 128;			// okay to post record to database  
  
	protected $flags = 0;          					// the property we're interested in... 
  
	function NewNoteState ()
	{
		// constructor
		$this->setFlag('has_name', false);
		$this->setFlag('has_RID_complete', false);
		$this->setFlag('has_notes', false);
		$this->setFlag('retry', false);
		$this->setFlag('cookie_found', false);
		$this->setFlag('first_time', true);		// TODO this returns state of 32! (or 64...) 
		$this->setFlag('post_to_db', false);
	}
  // simple setter function for any declared static variable 
  public function setFlag( $flag, $state ) 
  { 
		echo "<br>" . __METHOD__ . " " . $flag . " state = '" . $state . "'"; 
    $this->flags = ($state ? ($this->flags | self::$$flag) : ($this->flags & ~self::$$flag));
		echo " state = '" . $state . "' \$this->flags = '" . $this->flags . 
		     "' and self::\$\$flag = '" . self::$$flag . "'" . "' and ~self::\$\$flag = '" . ~self::$$flag . "'"; 
  } 

  // returns the state of the flag 
  public function getFlag( $flag ) 
  { 
		// double dollar signs allow a variable of a variable
		// http://www.php.net/manual/en/language.variables.variable.php
    return( $this->flags & self::$$flag ); 
  } 

  // outputs an integer representation of the bitmask 
  public function __toString()
	{ 
    return __METHOD__ . "[flags=$this->flags]"; 
  } 
	//---------------------------------
	function ShowAllStates ()
	{
		// returns the state of all flags
		// 2009-03-17 jee
		//
		echo "<br>" . __METHOD__ . " - has_name : " . $this->getFlag('has_name');
		echo "<br>" . __METHOD__ . " - has_RID_complete : " . $this->getFlag('has_RID_complete');
		echo "<br>" . __METHOD__ . " - has_notes : " . $this->getFlag('has_notes');
		echo "<br>" . __METHOD__ . " - retry : " . $this->getFlag('has_name');
		echo "<br>" . __METHOD__ . " - cookie_found : " . $this->getFlag('cookie_found');
		echo "<br>" . __METHOD__ . " - first_time : " . $this->getFlag('first_time');
		echo "<br>" . __METHOD__ . " - post_to_db : " .  $this->getFlag('post_to_db');
	}
};
function getS($Count)
{
	/* simply returns an "s" if count is greater than 1, "" if count is one, null, or empty
	 * 
	 * Calling Params $Count - number (originally used for number of rids
	 * Returns:				"s" - if count is > 1, blank string otherwise
	 * 
	 * 2009-11-14 jee
	 * 2009-11-16 jee fixed bug - when count is zero, return plural
	 */
	 if (!is_numeric($Count)) return "";
	 if ($Count != 1) return "s";
	 else return "";
};
function nl2brex($text) 
{ 
	/*
	 * Converts newline and various incarnations to html break characters
	 * 
	 * Calling Params: $text
	 * Returns : string with substitutions
	 * 
	 * From http://us2.php.net/manual/en/function.nl2br.php
	 * 2009-11-16 jee
	 * 
	 */
   return strtr($text, array("\r\n" => '<br />', "\r" => '<br />', "\n" => '<br />')); 
} 



?>
