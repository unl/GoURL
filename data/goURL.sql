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

-- --------------------------------------------------------

--
-- Table structure for table `tblURLs`
--

DROP TABLE IF EXISTS `tblURLs`;
CREATE TABLE IF NOT EXISTS `tblURLs` (
  `urlID` varchar(255) NOT NULL,
  `longURL` varchar(1000) NOT NULL,
  `submitDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` varchar(25) NOT NULL,
  `redirects` int(11) unsigned NOT NULL,
  `lastRedirectDate` timestamp NOT NULL,
  PRIMARY KEY (`urlID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tblURLs`
--

INSERT INTO `tblURLs` (`urlID`, `longURL`, `submitDate`, `createdBy`) VALUES
('3qqn', 'https://cas.unl.edu/career-development', '2019-11-07 11:01:08', 'pnguyen16');