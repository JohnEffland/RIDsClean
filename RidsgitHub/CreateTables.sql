-- --------------------------------------------------------
-- Host:                         192.33.115.226
-- Server version:               5.0.45-log - Source distribution
-- Server OS:                    pc-linux-gnu
-- HeidiSQL version:             7.0.0.4053
-- Date/time:                    2013-03-28 17:03:55
-- --------------------------------------------------------
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


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

-- Dumping structure for table dbRIDs.RIDdocs
CREATE TABLE IF NOT EXISTS `RIDdocs` (
  `keyRIDdocs` int(10) unsigned NOT NULL auto_increment,
  `fkRIDProjects` int(6) default '0',
  `Name` varchar(255) NOT NULL default '',
  `Priority` float default NULL,
  `DateEntered` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `EnteredBy` varchar(50) default NULL,
  `Notes` text,
  PRIMARY KEY  (`keyRIDdocs`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;

-- Data exporting was unselected.


-- Dumping structure for table dbRIDs.RIDEvents
CREATE TABLE IF NOT EXISTS `RIDEvents` (
  `keyRIDEvents` int(10) unsigned NOT NULL auto_increment,
  `fkRIDs` int(10) unsigned default NULL,
  `DateUpdated` datetime default NULL,
  `EntryBy` varchar(50) NOT NULL default '',
  `AssignedPrev` varchar(50) default NULL,
  `WaitingOnPrev` varchar(50) default NULL,
  `DateEstCompletionPrev` datetime default NULL,
  `DateCompleted` datetime default NULL,
  `Effort` float default NULL,
  `Notes` text,
  `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`keyRIDEvents`),
  UNIQUE KEY `keyTaskEvents` (`keyRIDEvents`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table dbRIDs.RIDNames
CREATE TABLE IF NOT EXISTS `RIDNames` (
  `keyRIDNames` int(11) NOT NULL auto_increment,
  `Name` varchar(128) default NULL,
  `Notes` mediumtext,
  `DateEntered` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`keyRIDNames`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table dbRIDs.RIDProjects
CREATE TABLE IF NOT EXISTS `RIDProjects` (
  `keyRIDProjects` int(10) unsigned NOT NULL auto_increment,
  `Password` varchar(15) default NULL,
  `Name` varchar(50) NOT NULL default '',
  `DateEntered` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `URLtoDocumentation` text,
  `EnteredBy` varchar(50) default NULL,
  `Notes` text,
  `PasswordLogin` tinytext,
  PRIMARY KEY  (`keyRIDProjects`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table dbRIDs.RIDs
CREATE TABLE IF NOT EXISTS `RIDs` (
  `keyRIDs` int(10) unsigned NOT NULL auto_increment,
  `fkRIDdocs` int(10) unsigned default NULL,
  `Priority` tinyint(6) default NULL,
  `fkRidProjects` int(10) unsigned default NULL,
  `RIDNum` int(10) unsigned default NULL,
  `Major` tinyint(4) default NULL COMMENT 'Is this a major RID?',
  `RIXType` char(3) default NULL COMMENT 'RID, RIX, or RIC',
  `Originator` varchar(50) default NULL,
  `AssignedTo` varchar(50) default NULL,
  `WaitingOn` varchar(50) default NULL,
  `DateEntered` datetime default NULL,
  `ChapterSectionPage` varchar(100) default NULL,
  `DocTitle` text,
  `DocNum` varchar(50) default NULL,
  `SuggestedSolution` text,
  `FileInput` blob,
  `DateEstCompletion` datetime default NULL,
  `DateCompleted` datetime default NULL,
  `Description` text,
  `RespondersSolution` text,
  `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `PriorityFlagSet` int(10) unsigned default NULL,
  PRIMARY KEY  (`keyRIDs`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
/*!40014 SET FOREIGN_KEY_CHECKS=1 */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
