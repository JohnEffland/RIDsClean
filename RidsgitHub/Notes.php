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
die();
/* 
Notes for RID Program 

Thu Mar 05 2009 08:53:37 GMT-0500 (EST)
Wed Sep 09 2009 12:25:05 GMT-0400 (EDT) Use table ORR_Projects
Wed Nov 11 2009 17:33:36 GMT-0500 (EST) Checking if this can be updated in time for upcoming reviews.
																				Added fkRidProjects to Rids table, which points to the particular review.  don't need this fk in RidNotes, because
                                        the key values will be unique.  This might create problems, however, because the RID numbers won't start with 1.        
Thu Nov 12 2009 10:20:41 GMT-0500 (EST) Look in task list for high priority todo
                                        https://safe.nrao.edu/php/ntc/Rids/RidsGen/source/Events2.php?RIDNum=95
																				URL to dev version is here:  https://safe.nrao.edu/php/ntc/Rids/RidsGen/source/Rids.php?type=3
Fri Nov 13 2009 14:29:15 GMT-0500 (EST) Finished coding and checked all pages with http://validator.w3.org!
                                                                     
                                        root dir is /export/home/teller/vhosts/safe.nrao.edu/active
                                        
                                        ---------------------------------
                                        Features:
                                        * manually add type parameter, which points to the particular review?
                                        * ADD expanded view
                                        Testing:
                                        * all rids show in table
                                        * number of open and closed rids consistent with project
                                        * enter new rid
                                        * view notes
                                        * 
Sat Nov 14 2009 14:34:38 GMT-0500 (EST) * Updated to include project password
                                        *
                                        * Amp Cal CDR is https://safe.nrao.edu/php/ntc/Rids/RidsGen/source/Rids.php?type=CW894qYa
                                        * Test database is https://safe.nrao.edu/php/ntc/Rids/RidsGen/source/Rids.php?type=zkgZ4F27
Mon Nov 16 2009 09:54:09 GMT-0500 (EST) * PHP Version 5.2.9: from https://safe.nrao.edu/php/ntc/Rids/RidsGen/source/phpinfo.php
Wed 2012-11-14 jee On git, now tested RidsProduction with Multi Fuel Power Generation System ACRV using
https://safe.nrao.edu/php/ntc/Rids/Rids.php?type=P*e.r8me

                                        
                                        
*******************************************************
Requirements:
Thu Mar 05 2009 08:56:27 GMT-0500 (EST) 
Send e-mail to users listing their RIDs left open, closed, etc.
Send e-mail to users when their RIDs are answered.

*/
?>