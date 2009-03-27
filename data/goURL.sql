-- phpMyAdmin SQL Dump
-- version 3.1.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 27, 2009 at 09:15 AM
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
  `urlID` int(11) NOT NULL,
  `longURL` varchar(1000) NOT NULL,
  `submitDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` varchar(25) DEFAULT NULL,
  `alias` varchar(50) DEFAULT NULL,
  `gaCampaignSource` varchar(50) DEFAULT NULL,
  `gaCampaignName` varchar(100) DEFAULT NULL,
  `gaCampaignTerm` varchar(50) DEFAULT NULL,
  `gaCampaignContent` varchar(50) DEFAULT NULL,
  `gaCampaignMedium` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`urlID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tblURLs`
--

