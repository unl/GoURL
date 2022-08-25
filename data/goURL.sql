-- phpMyAdmin SQL Dump
-- version 3.1.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 02, 2009 at 11:04 AM
-- Server version: 5.1.31
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `goURL`
--

USE goURL;

-- --------------------------------------------------------

--
-- Table structure for table `tblURLs`
--

DROP TABLE IF EXISTS `tblURLs`;
CREATE TABLE IF NOT EXISTS `tblURLs` (
  `urlID` varchar(255) NOT NULL,
  `groupID` int(10) unsigned NULL,
  `longURL` varchar(1000) NOT NULL,
  `submitDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` varchar(25) DEFAULT NULL,
  `redirects` int(11) unsigned DEFAULT 0,
  `lastRedirect` timestamp NULL,
  PRIMARY KEY (`urlID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ALTER TABLE `tblURLs` ADD COLUMN `groupID` int(10) unsigned NULL AFTER `urlID`;
-- ALTER TABLE `tblURLs` ADD COLUMN `lastRedirect` timestamp NULL AFTER `redirects`;

DROP TABLE IF EXISTS `tblGroups`;
CREATE TABLE IF NOT EXISTS `tblGroups` (
  `groupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `groupName` varchar(255) NOT NULL,
  PRIMARY KEY (`groupID`),
  UNIQUE KEY (`groupName`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `tblGroupUsers`;
CREATE TABLE IF NOT EXISTS `tblGroupUsers` (
  `groupID` int(10) unsigned NOT NULL,
  `uid` varchar(50) NOT NULL,
  PRIMARY KEY (`groupID`, `uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tblURLs`
--

INSERT INTO `tblURLs` (`urlID`, `longURL`, `submitDate`, `createdBy`) VALUES
('0', 'http://admissions.unl.edu/consider', '2009-04-02 11:01:08', NULL);