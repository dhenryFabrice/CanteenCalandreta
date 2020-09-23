-- phpMyAdmin SQL Dump
-- version 4.8.4
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le :  lun. 14 oct. 2019 à 13:35
-- Version du serveur :  5.5
-- Version de PHP :  5.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de données :  `CantineTest`
--

-- --------------------------------------------------------

--
-- Structure de la table `Alias`
--

CREATE TABLE `Alias` (
  `AliasID` smallint(5) UNSIGNED NOT NULL,
  `AliasName` varchar(50) NOT NULL,
  `AliasDescription` varchar(255) DEFAULT NULL,
  `AliasMailingList` mediumtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Banks`
--

CREATE TABLE `Banks` (
  `BankID` smallint(5) UNSIGNED NOT NULL,
  `BankName` varchar(50) NOT NULL,
  `BankAcronym` varchar(5) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `Banks`
--

INSERT INTO `Banks` (`BankID`, `BankName`, `BankAcronym`) VALUES
(1, 'Banque Populaire', 'BP'),
(2, 'Crédit Agricole', 'CA'),
(3, 'Crédit Mutuel', 'CM'),
(4, 'Société Générale', 'SG'),
(5, 'Caisse d\'Epargne', 'CE'),
(6, 'Banque Nationale de Paris', 'BNP'),
(7, 'Banque Populaire Occitane', 'BPO'),
(8, 'Crédit Lyonnais', 'LCL'),
(9, 'Crédit Industriel et Commercial', 'CIC'),
(10, 'Banque Courtois', 'BC'),
(11, 'Crédit Coopératif', 'CC'),
(12, 'Banque Postale', 'CCP'),
(13, 'AXA banque', 'AXA'),
(14, 'HSBC', 'HSBC');

-- --------------------------------------------------------

--
-- Structure de la table `Bills`
--

CREATE TABLE `Bills` (
  `BillID` mediumint(8) UNSIGNED NOT NULL,
  `BillDate` datetime NOT NULL,
  `BillForDate` date NOT NULL,
  `BillPreviousBalance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillDeposit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillMonthlyContribution` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillCanteenAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillWithoutMealAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillNurseryAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillNurseryNbDelays` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `BillPaidAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillPaid` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `CanteenRegistrations`
--

CREATE TABLE `CanteenRegistrations` (
  `CanteenRegistrationID` mediumint(8) UNSIGNED NOT NULL,
  `CanteenRegistrationDate` date NOT NULL,
  `CanteenRegistrationForDate` date NOT NULL,
  `CanteenRegistrationAdminDate` date DEFAULT NULL,
  `CanteenRegistrationChildGrade` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `CanteenRegistrationChildClass` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `CanteenRegistrationWithoutPork` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `CanteenRegistrationValided` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ChildID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `CanteenRegistrationsChildrenHabits`
--

CREATE TABLE `CanteenRegistrationsChildrenHabits` (
  `CanteenRegistrationChildHabitID` mediumint(8) UNSIGNED NOT NULL,
  `CanteenRegistrationChildHabitProfil` smallint(5) UNSIGNED NOT NULL,
  `CanteenRegistrationChildHabitRate` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `CanteenRegistrationChildHabitType` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ChildID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Children`
--

CREATE TABLE `Children` (
  `ChildID` smallint(5) UNSIGNED NOT NULL,
  `ChildFirstname` varchar(50) NOT NULL,
  `ChildSchoolDate` date NOT NULL,
  `ChildDesactivationDate` date DEFAULT NULL,
  `ChildGrade` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ChildClass` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ChildWithoutPork` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ChildEmail` varchar(100) DEFAULT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `ConfigParameters`
--

CREATE TABLE `ConfigParameters` (
  `ConfigParameterID` smallint(5) UNSIGNED NOT NULL,
  `ConfigParameterName` varchar(255) NOT NULL,
  `ConfigParameterType` varchar(10) NOT NULL,
  `ConfigParameterValue` mediumtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `ConfigParameters`
--

INSERT INTO `ConfigParameters` (`ConfigParameterID`, `ConfigParameterName`, `ConfigParameterType`, `ConfigParameterValue`) VALUES
(1, 'CONF_SCHOOL_YEAR_START_DATES', 'xml', '<config-parameters>\r\n<school-year id=\"2010\">2009-09-05</school-year>\r\n<school-year id=\"2011\">2010-09-06</school-year>\r\n<school-year id=\"2012\">2011-09-05</school-year>\r\n<school-year id=\"2013\">2012-09-04</school-year>\r\n<school-year id=\"2014\">2013-09-03</school-year>\r\n<school-year id=\"2015\">2014-09-02</school-year>\r\n<school-year id=\"2016\">2015-09-01</school-year>\r\n<school-year id=\"2017\">2016-09-01</school-year>\r\n<school-year id=\"2018\">2017-09-04</school-year>\r\n<school-year id=\"2019\">2018-09-03</school-year>\r\n<school-year id=\"2020\">2019-09-02</school-year>\r\n</config-parameters>'),
(2, 'CONF_CLASSROOMS', 'xml', '<config-parameters>\r\n<school-year id=\"2010\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>MS-GS</classroom>\r\n<classroom>CP-CE1</classroom>\r\n<classroom>CE2-CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2011\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>MS-GS</classroom>\r\n<classroom>CP-CE1</classroom>\r\n<classroom>CE2-CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2012\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>MS-GS</classroom>\r\n<classroom>CP-CE1</classroom>\r\n<classroom>CE2-CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2013\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>MS-GS</classroom>\r\n<classroom>CP-CE1</classroom>\r\n<classroom>CE2-CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2014\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>GS-CP</classroom>\r\n<classroom>CE1-CE2</classroom>\r\n<classroom>CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2015\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>GS-CP</classroom>\r\n<classroom>CE1-CE2</classroom>\r\n<classroom>CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2016\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>GS-CP</classroom>\r\n<classroom>CE1-CE2</classroom>\r\n<classroom>CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2017\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>GS-CP</classroom>\r\n<classroom>CE1-CE2</classroom>\r\n<classroom>CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2018\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>GS-CP</classroom>\r\n<classroom>CE1-CE2</classroom>\r\n<classroom>CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2019\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>GS-CP</classroom>\r\n<classroom>CE1-CE2</classroom>\r\n<classroom>CM1-CM2</classroom>\r\n</school-year>\r\n<school-year id=\"2020\">\r\n<classroom>-</classroom>\r\n<classroom>TPS-PS-MS</classroom>\r\n<classroom>GS-CP</classroom>\r\n<classroom>CE1-CE2</classroom>\r\n<classroom>CM1-CM2</classroom>\r\n</school-year>\r\n</config-parameters>'),
(3, 'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS', 'xml', '<config-parameters>\r\n<school-year id=\"2010\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2011\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2012\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2013\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2014\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2015\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2016\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2017\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2018\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2019\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n<school-year id=\"2020\">\r\n<amount nbvotes=\"0\">15.00</amount>\r\n<amount nbvotes=\"1\">15.00</amount>\r\n<amount nbvotes=\"2\">30.00</amount>\r\n<amount nbvotes=\"3\">45.00</amount>\r\n</school-year>\r\n</config-parameters>'),
(5, 'CONF_CANTEEN_PRICES', 'xml', '<config-parameters>\r\n<school-year id=\"2010\">\r\n<lunch-price>3.66</lunch-price>\r\n<nursery-price>0.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2011\">\r\n<lunch-price>3.66</lunch-price>\r\n<nursery-price>0.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2012\">\r\n<lunch-price>3.66</lunch-price>\r\n<nursery-price>0.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2013\">\r\n<lunch-price>3.73</lunch-price>\r\n<nursery-price>0.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2014\">\r\n<lunch-price>3.79</lunch-price>\r\n<nursery-price>0.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2015\">\r\n<lunch-price>4.00</lunch-price>\r\n<nursery-price>0.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2016\">\r\n<lunch-price>3.40</lunch-price>\r\n<nursery-price>1.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2017\">\r\n<lunch-price>3.04</lunch-price>\r\n<nursery-price>1.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2018\">\r\n<lunch-price>3.08</lunch-price>\r\n<nursery-price>1.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2019\">\r\n<lunch-price>3.14</lunch-price>\r\n<nursery-price>1.00</nursery-price>\r\n</school-year>\r\n<school-year id=\"2020\">\r\n<lunch-price>3.14</lunch-price>\r\n<nursery-price>1.00</nursery-price>\r\n</school-year>\r\n</config-parameters>'),
(6, 'CONF_NURSERY_PRICES', 'xml', '<config-parameters>\r\n<school-year id=\"2010\">\r\n<am-nursery-price>1.25</am-nursery-price>\r\n<pm-nursery-price>1.25</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2011\">\r\n<am-nursery-price>1.25</am-nursery-price>\r\n<pm-nursery-price>1.25</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2012\">\r\n<am-nursery-price>1.25</am-nursery-price>\r\n<pm-nursery-price>1.25</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2013\">\r\n<am-nursery-price>1.25</am-nursery-price>\r\n<pm-nursery-price>1.25</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2014\">\r\n<am-nursery-price>1.25</am-nursery-price>\r\n<pm-nursery-price>1.25</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2015\">\r\n<am-nursery-price>1.50</am-nursery-price>\r\n<pm-nursery-price>1.50</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2016\">\r\n<am-nursery-price>1.50</am-nursery-price>\r\n<pm-nursery-price>1.50</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2017\">\r\n<am-nursery-price>1.50</am-nursery-price>\r\n<pm-nursery-price>1.50</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2018\">\r\n<am-nursery-price>1.50</am-nursery-price>\r\n<pm-nursery-price>1.50</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2019\">\r\n<am-nursery-price>1.50</am-nursery-price>\r\n<pm-nursery-price>1.50</pm-nursery-price>\r\n</school-year>\r\n<school-year id=\"2020\">\r\n<am-nursery-price>1.50</am-nursery-price>\r\n<pm-nursery-price>1.50</pm-nursery-price>\r\n</school-year>\r\n</config-parameters>'),
(4, 'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS', 'xml', '<config-parameters>\r\n<school-year id=\"2010\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_BENEFACTOR_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2011\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_BENEFACTOR_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2012\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_BENEFACTOR_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2013\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_BENEFACTOR_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2014\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_BENEFACTOR_MODE\">\r\n<amount nbchildren=\"1\">1.00</amount>\r\n<amount nbchildren=\"2\">13.48</amount>\r\n<amount nbchildren=\"3\">15.04</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2015\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_BENEFACTOR_MODE\">\r\n<amount nbchildren=\"1\">1.00</amount>\r\n<amount nbchildren=\"2\">13.48</amount>\r\n<amount nbchildren=\"3\">15.04</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2016\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">17.16</amount>\r\n<amount nbchildren=\"2\">29.64</amount>\r\n<amount nbchildren=\"3\">31.20</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_BENEFACTOR_MODE\">\r\n<amount nbchildren=\"1\">1.00</amount>\r\n<amount nbchildren=\"2\">13.48</amount>\r\n<amount nbchildren=\"3\">15.04</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2017\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">25.00</amount>\r\n<amount nbchildren=\"2\">43.75</amount>\r\n<amount nbchildren=\"3\">56.25</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_1_MODE\">\r\n<amount nbchildren=\"1\">17.50</amount>\r\n<amount nbchildren=\"2\">30.63</amount>\r\n<amount nbchildren=\"3\">39.38</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_2_MODE\">\r\n<amount nbchildren=\"1\">20.00</amount>\r\n<amount nbchildren=\"2\">35.00</amount>\r\n<amount nbchildren=\"3\">45.00</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_3_MODE\">\r\n<amount nbchildren=\"1\">22.55</amount>\r\n<amount nbchildren=\"2\">39.38</amount>\r\n<amount nbchildren=\"3\">50.63</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2018\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">25.34</amount>\r\n<amount nbchildren=\"2\">44.34</amount>\r\n<amount nbchildren=\"3\">57.01</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_1_MODE\">\r\n<amount nbchildren=\"1\">17.74</amount>\r\n<amount nbchildren=\"2\">31.04</amount>\r\n<amount nbchildren=\"3\">39.91</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_2_MODE\">\r\n<amount nbchildren=\"1\">20.27</amount>\r\n<amount nbchildren=\"2\">35.47</amount>\r\n<amount nbchildren=\"3\">45.61</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_3_MODE\">\r\n<amount nbchildren=\"1\">22.80</amount>\r\n<amount nbchildren=\"2\">39.91</amount>\r\n<amount nbchildren=\"3\">51.31</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2019\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">25.69</amount>\r\n<amount nbchildren=\"2\">44.96</amount>\r\n<amount nbchildren=\"3\">57.81</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_1_MODE\">\r\n<amount nbchildren=\"1\">17.99</amount>\r\n<amount nbchildren=\"2\">31.47</amount>\r\n<amount nbchildren=\"3\">40.47</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_2_MODE\">\r\n<amount nbchildren=\"1\">20.55</amount>\r\n<amount nbchildren=\"2\">35.97</amount>\r\n<amount nbchildren=\"3\">46.25</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_3_MODE\">\r\n<amount nbchildren=\"1\">23.12</amount>\r\n<amount nbchildren=\"2\">40.47</amount>\r\n<amount nbchildren=\"3\">52.03</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n<school-year id=\"2020\">\r\n<monthly-contribution mode=\"MC_DEFAULT_MODE\">\r\n<amount nbchildren=\"1\">32.90</amount>\r\n<amount nbchildren=\"2\">52.48</amount>\r\n<amount nbchildren=\"3\">65.53</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_1_MODE\">\r\n<amount nbchildren=\"1\">23.03</amount>\r\n<amount nbchildren=\"2\">36.74</amount>\r\n<amount nbchildren=\"3\">45.87</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_2_MODE\">\r\n<amount nbchildren=\"1\">26.32</amount>\r\n<amount nbchildren=\"2\">41.98</amount>\r\n<amount nbchildren=\"3\">52.42</amount>\r\n</monthly-contribution>\r\n<monthly-contribution mode=\"MC_FAMILY_COEFF_3_MODE\">\r\n<amount nbchildren=\"1\">29.61</amount>\r\n<amount nbchildren=\"2\">47.23</amount>\r\n<amount nbchildren=\"3\">58.97</amount>\r\n</monthly-contribution>\r\n</school-year>\r\n</config-parameters>'),
(7, 'CONF_NURSERY_DELAYS_PRICES', 'xml', '<config-parameters>\r\n<school-year id=\"2010\"></school-year>\r\n<school-year id=\"2011\"></school-year>\r\n<school-year id=\"2012\"></school-year>\r\n<school-year id=\"2013\"></school-year>\r\n<school-year id=\"2014\">\r\n<nursery-delay-price nbdelays=\"1\">11.80</nursery-delay-price>\r\n<nursery-delay-price nbdelays=\"2\">11.80</nursery-delay-price>\r\n</school-year>\r\n<school-year id=\"2015\">\r\n<nursery-delay-price nbdelays=\"1\">11.80</nursery-delay-price>\r\n<nursery-delay-price nbdelays=\"2\">11.80</nursery-delay-price>\r\n</school-year>\r\n<school-year id=\"2016\">\r\n<nursery-delay-price nbdelays=\"1\">11.80</nursery-delay-price>\r\n<nursery-delay-price nbdelays=\"2\">11.80</nursery-delay-price>\r\n</school-year>\r\n<school-year id=\"2017\">\r\n<nursery-delay-price nbdelays=\"1\">11.80</nursery-delay-price>\r\n<nursery-delay-price nbdelays=\"2\">11.80</nursery-delay-price>\r\n</school-year>\r\n<school-year id=\"2018\">\r\n<nursery-delay-price nbdelays=\"1\">11.80</nursery-delay-price>\r\n<nursery-delay-price nbdelays=\"2\">11.80</nursery-delay-price>\r\n</school-year>\r\n<school-year id=\"2019\">\r\n<nursery-delay-price nbdelays=\"1\">11.80</nursery-delay-price>\r\n<nursery-delay-price nbdelays=\"2\">11.80</nursery-delay-price>\r\n</school-year>\r\n<school-year id=\"2020\">\r\n<nursery-delay-price nbdelays=\"1\">11.80</nursery-delay-price>\r\n<nursery-delay-price nbdelays=\"2\">11.80</nursery-delay-price>\r\n</school-year>\r\n</config-parameters>'),
(9, 'CONF_TEST_VAR_XML', 'xml', '<config-parameters>\r\n<year id=\"2015\">\r\n<Template idtype=\"const\">fichedescriptiveformulaire_5952.pdf</Template>\r\n<Language idtype=\"const\">fr-FR</Language>\r\n<Unit idtype=\"const\">EUR</Unit>\r\n<Page idtype=\"const\">\r\n<Page id=\"1\">\r\n<part id=\"Recipient\" idtype=\"const\">\r\n<field id=\"Name\">\r\n<Text idtype=\"const\">Calandreta Del Païs Murethin</Text>\r\n<PosX idtype=\"const\">52</PosX>\r\n<PosY idtype=\"const\">33</PosY>\r\n</field>\r\n</part>\r\n</Page>\r\n<Page id=\"2\">\r\n<part id=\"Donator\" idtype=\"const\">\r\n<field id=\"Nature\">\r\n<list keep=\"0\">\r\n<item id=\"Numéraire\">\r\n<Items idtype=\"const\" type=\"array\">\r\n<value keep=\"0\">0</value>\r\n</Items>\r\n<Text idtype=\"const\">X</Text>\r\n<PosX idtype=\"const\">9.7</PosX>\r\n<PosY idtype=\"const\">133.7</PosY>\r\n</item>\r\n<item id=\"Titres de sociétés côtés\">\r\n<Items idtype=\"const\" type=\"array\"></Items>\r\n<Text idtype=\"const\">X</Text>\r\n<PosX idtype=\"const\">52.3</PosX>\r\n<PosY idtype=\"const\">133.7</PosY>\r\n</item>\r\n<item id=\"Autres\">\r\n<Items idtype=\"const\" type=\"array\">\r\n<value keep=\"0\">1</value>\r\n<value keep=\"0\">2</value>\r\n</Items>\r\n<Text idtype=\"const\">X</Text>\r\n<PosX idtype=\"const\">110</PosX>\r\n<PosY idtype=\"const\">133.7</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n</part>\r\n</Page>\r\n</Page>\r\n</year>\r\n</config-parameters>'),
(8, 'CONF_DONATION_TAX_RECEIPT_PARAMETERS', 'xml', '<config-parameters>\r\n<year id=\"2017\">\r\n<Template>fichedescriptiveformulaire_5952.pdf</Template>\r\n<Language>fr-FR</Language>\r\n<Unit>EUR</Unit>\r\n<pages>\r\n<Page id=\"1\">\r\n<part id=\"Recipient\">\r\n<field id=\"Reference\">\r\n<PosX>170</PosX>\r\n<PosY>8</PosY>\r\n</field>\r\n<field id=\"Name\">\r\n<Text>Calandreta Del Païs Murethin</Text>\r\n<PosX>52</PosX>\r\n<PosY>33</PosY>\r\n</field>\r\n<field id=\"StreetNum\">\r\n<Text>Avenue du Maréchal Lyautey</Text>\r\n<PosX>36</PosX>\r\n<PosY>44</PosY>\r\n</field>\r\n<field id=\"ZipCode\">\r\n<Text>31600</Text>\r\n<PosX>31</PosX>\r\n<PosY>50</PosY>\r\n</field>\r\n<field id=\"TownName\">\r\n<Text>Muret</Text>\r\n<PosX>70</PosX>\r\n<PosY>50</PosY>\r\n</field>\r\n<field id=\"Subject\">\r\n<Text>Enseignement en occitan de la TPS au CM2.</Text>\r\n<PosX>25</PosX>\r\n<PosY>60</PosY>\r\n</field>\r\n<field id=\"Organization\">\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>126.3</PosY>\r\n</field>\r\n</part>\r\n</Page>\r\n<Page id=\"2\">\r\n<part id=\"Donator\">\r\n<field id=\"Lastname\">\r\n<PosX>21</PosX>\r\n<PosY>16</PosY>\r\n</field>\r\n<field id=\"Firstname\">\r\n<PosX>119</PosX>\r\n<PosY>16</PosY>\r\n</field>\r\n<field id=\"Address\">\r\n<PosX>26</PosX>\r\n<PosY>29</PosY>\r\n</field>\r\n<field id=\"ZipCode\">\r\n<PosX>31</PosX>\r\n<PosY>35</PosY>\r\n</field>\r\n<field id=\"TownName\">\r\n<PosX>72</PosX>\r\n<PosY>35</PosY>\r\n</field>\r\n<field id=\"Amount\">\r\n<PosX>77</PosX>\r\n<PosY>60.5</PosY>\r\n</field>\r\n<field id=\"AmountInLetters\">\r\n<PosX>54</PosX>\r\n<PosY>70.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateDay\">\r\n<PosX>62</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateMonth\">\r\n<PosX>76</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateYear\">\r\n<PosX>92</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"Entity\">\r\n<list>\r\n<item id=\"200 du CGI\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>47.5</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n<item id=\"238 bis du CGI\">\r\n<Items>\r\n<value>1</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>97.3</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n<item id=\"885-0 V bis A du CGI\">\r\n<Items></Items>\r\n<Text>X</Text>\r\n<PosX>147.5</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"Type\">\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>111.5</PosY>\r\n</field>\r\n<field id=\"Nature\">\r\n<list>\r\n<item id=\"Numéraire\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n<item id=\"Titres de sociétés côtés\">\r\n<Items></Items>\r\n<Text>X</Text>\r\n<PosX>52.3</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n<item id=\"Autres\">\r\n<Items>\r\n<value>1</value>\r\n<value>2</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"PaymentMode\">\r\n<list>\r\n<item id=\"Remise d\'espèces\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n<item id=\"Chèque\">\r\n<Items>\r\n<value>1</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>52.3</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n<item id=\"Virement, prélèvement, carte bancaire\">\r\n<Items>\r\n<value>2</value>\r\n<value>3</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"TaxReceiptDateDay\">\r\n<PosX>134</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"TaxReceiptDateMonth\">\r\n<PosX>142</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"TaxReceiptDateYear\">\r\n<PosX>150</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"SignIn\">\r\n<Text>TamponCalandretaTr.png</Text>\r\n<PosX>123</PosX>\r\n<PosY>237</PosY>\r\n<DimWidth>70</DimWidth>\r\n</field>\r\n</part>\r\n</Page>\r\n</pages>\r\n</year>\r\n<year id=\"2018\">\r\n<Template>fichedescriptiveformulaire_5952.pdf</Template>\r\n<Language>fr-FR</Language>\r\n<Unit>EUR</Unit>\r\n<pages>\r\n<Page id=\"1\">\r\n<part id=\"Recipient\">\r\n<field id=\"Reference\">\r\n<PosX>170</PosX>\r\n<PosY>8</PosY>\r\n</field>\r\n<field id=\"Name\">\r\n<Text>Calandreta Del Païs Murethin</Text>\r\n<PosX>52</PosX>\r\n<PosY>33</PosY>\r\n</field>\r\n<field id=\"StreetNum\">\r\n<Text>Avenue du Maréchal Lyautey</Text>\r\n<PosX>36</PosX>\r\n<PosY>44</PosY>\r\n</field>\r\n<field id=\"ZipCode\">\r\n<Text>31600</Text>\r\n<PosX>31</PosX>\r\n<PosY>50</PosY>\r\n</field>\r\n<field id=\"TownName\">\r\n<Text>Muret</Text>\r\n<PosX>70</PosX>\r\n<PosY>50</PosY>\r\n</field>\r\n<field id=\"Subject\">\r\n<Text>Enseignement en occitan de la TPS au CM2.</Text>\r\n<PosX>25</PosX>\r\n<PosY>60</PosY>\r\n</field>\r\n<field id=\"Organization\">\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>126.3</PosY>\r\n</field>\r\n</part>\r\n</Page>\r\n<Page id=\"2\">\r\n<part id=\"Donator\">\r\n<field id=\"Lastname\">\r\n<PosX>21</PosX>\r\n<PosY>16</PosY>\r\n</field>\r\n<field id=\"Firstname\">\r\n<PosX>119</PosX>\r\n<PosY>16</PosY>\r\n</field>\r\n<field id=\"Address\">\r\n<PosX>26</PosX>\r\n<PosY>29</PosY>\r\n</field>\r\n<field id=\"ZipCode\">\r\n<PosX>31</PosX>\r\n<PosY>35</PosY>\r\n</field>\r\n<field id=\"TownName\">\r\n<PosX>72</PosX>\r\n<PosY>35</PosY>\r\n</field>\r\n<field id=\"Amount\">\r\n<PosX>77</PosX>\r\n<PosY>60.5</PosY>\r\n</field>\r\n<field id=\"AmountInLetters\">\r\n<PosX>54</PosX>\r\n<PosY>70.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateDay\">\r\n<PosX>62</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateMonth\">\r\n<PosX>76</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateYear\">\r\n<PosX>92</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"Entity\">\r\n<list>\r\n<item id=\"200 du CGI\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>47.5</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n<item id=\"238 bis du CGI\">\r\n<Items>\r\n<value>1</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>97.3</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n<item id=\"885-0 V bis A du CGI\">\r\n<Items></Items>\r\n<Text>X</Text>\r\n<PosX>147.5</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"Type\">\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>111.5</PosY>\r\n</field>\r\n<field id=\"Nature\">\r\n<list>\r\n<item id=\"Numéraire\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n<item id=\"Titres de sociétés côtés\">\r\n<Items></Items>\r\n<Text>X</Text>\r\n<PosX>52.3</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n<item id=\"Autres\">\r\n<Items>\r\n<value>1</value>\r\n<value>2</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"PaymentMode\">\r\n<list>\r\n<item id=\"Remise d\'espèces\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n<item id=\"Chèque\">\r\n<Items>\r\n<value>1</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>52.3</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n<item id=\"Virement, prélèvement, carte bancaire\">\r\n<Items>\r\n<value>2</value>\r\n<value>3</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"TaxReceiptDateDay\">\r\n<PosX>134</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"TaxReceiptDateMonth\">\r\n<PosX>142</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"TaxReceiptDateYear\">\r\n<PosX>150</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"SignIn\">\r\n<Text>TamponCalandretaTr.png</Text>\r\n<PosX>123</PosX>\r\n<PosY>237</PosY>\r\n<DimWidth>70</DimWidth>\r\n</field>\r\n</part>\r\n</Page>\r\n</pages>\r\n</year>\r\n<year id=\"2019\">\r\n<Template>fichedescriptiveformulaire_5952.pdf</Template>\r\n<Language>fr-FR</Language>\r\n<Unit>EUR</Unit>\r\n<pages>\r\n<Page id=\"1\">\r\n<part id=\"Recipient\">\r\n<field id=\"Reference\">\r\n<PosX>170</PosX>\r\n<PosY>8</PosY>\r\n</field>\r\n<field id=\"Name\">\r\n<Text>Calandreta Del Païs Murethin</Text>\r\n<PosX>52</PosX>\r\n<PosY>33</PosY>\r\n</field>\r\n<field id=\"StreetNum\">\r\n<Text>Avenue du Maréchal Lyautey</Text>\r\n<PosX>36</PosX>\r\n<PosY>44</PosY>\r\n</field>\r\n<field id=\"ZipCode\">\r\n<Text>31600</Text>\r\n<PosX>31</PosX>\r\n<PosY>50</PosY>\r\n</field>\r\n<field id=\"TownName\">\r\n<Text>Muret</Text>\r\n<PosX>70</PosX>\r\n<PosY>50</PosY>\r\n</field>\r\n<field id=\"Subject\">\r\n<Text>Enseignement en occitan de la TPS au CM2.</Text>\r\n<PosX>25</PosX>\r\n<PosY>60</PosY>\r\n</field>\r\n<field id=\"Organization\">\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>126.3</PosY>\r\n</field>\r\n</part>\r\n</Page>\r\n<Page id=\"2\">\r\n<part id=\"Donator\">\r\n<field id=\"Lastname\">\r\n<PosX>21</PosX>\r\n<PosY>16</PosY>\r\n</field>\r\n<field id=\"Firstname\">\r\n<PosX>119</PosX>\r\n<PosY>16</PosY>\r\n</field>\r\n<field id=\"Address\">\r\n<PosX>26</PosX>\r\n<PosY>29</PosY>\r\n</field>\r\n<field id=\"ZipCode\">\r\n<PosX>31</PosX>\r\n<PosY>35</PosY>\r\n</field>\r\n<field id=\"TownName\">\r\n<PosX>72</PosX>\r\n<PosY>35</PosY>\r\n</field>\r\n<field id=\"Amount\">\r\n<PosX>77</PosX>\r\n<PosY>60.5</PosY>\r\n</field>\r\n<field id=\"AmountInLetters\">\r\n<PosX>54</PosX>\r\n<PosY>70.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateDay\">\r\n<PosX>62</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateMonth\">\r\n<PosX>76</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"ReceptionDateYear\">\r\n<PosX>92</PosX>\r\n<PosY>79.5</PosY>\r\n</field>\r\n<field id=\"Entity\">\r\n<list>\r\n<item id=\"200 du CGI\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>47.5</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n<item id=\"238 bis du CGI\">\r\n<Items>\r\n<value>1</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>97.3</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n<item id=\"885-0 V bis A du CGI\">\r\n<Items></Items>\r\n<Text>X</Text>\r\n<PosX>147.5</PosX>\r\n<PosY>93.7</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"Type\">\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>111.5</PosY>\r\n</field>\r\n<field id=\"Nature\">\r\n<list>\r\n<item id=\"Numéraire\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n<item id=\"Titres de sociétés côtés\">\r\n<Items></Items>\r\n<Text>X</Text>\r\n<PosX>52.3</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n<item id=\"Autres\">\r\n<Items>\r\n<value>1</value>\r\n<value>2</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>133.7</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"PaymentMode\">\r\n<list>\r\n<item id=\"Remise d\'espèces\">\r\n<Items>\r\n<value>0</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>9.7</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n<item id=\"Chèque\">\r\n<Items>\r\n<value>1</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>52.3</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n<item id=\"Virement, prélèvement, carte bancaire\">\r\n<Items>\r\n<value>2</value>\r\n<value>3</value>\r\n</Items>\r\n<Text>X</Text>\r\n<PosX>110</PosX>\r\n<PosY>156.1</PosY>\r\n</item>\r\n</list>\r\n</field>\r\n<field id=\"TaxReceiptDateDay\">\r\n<PosX>134</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"TaxReceiptDateMonth\">\r\n<PosX>142</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"TaxReceiptDateYear\">\r\n<PosX>150</PosX>\r\n<PosY>236.5</PosY>\r\n</field>\r\n<field id=\"SignIn\">\r\n<Text>TamponCalandretaTr.png</Text>\r\n<PosX>123</PosX>\r\n<PosY>237</PosY>\r\n<DimWidth>70</DimWidth>\r\n</field>\r\n</part>\r\n</Page>\r\n</pages>\r\n</year>\r\n</config-parameters>');

-- --------------------------------------------------------

--
-- Structure de la table `DiscountsFamilies`
--

CREATE TABLE `DiscountsFamilies` (
  `DiscountFamilyID` mediumint(8) UNSIGNED NOT NULL,
  `DiscountFamilyType` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DiscountFamilyReasonType` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DiscountFamilyReason` varchar(255) DEFAULT NULL,
  `DiscountFamilyDate` datetime NOT NULL,
  `DiscountFamilyAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Table which contains discounts of families';

-- --------------------------------------------------------

--
-- Structure de la table `DocumentsApprovals`
--

CREATE TABLE `DocumentsApprovals` (
  `DocumentApprovalID` smallint(5) UNSIGNED NOT NULL,
  `DocumentApprovalDate` datetime NOT NULL,
  `DocumentApprovalName` varchar(255) NOT NULL,
  `DocumentApprovalFile` varchar(255) NOT NULL,
  `DocumentApprovalType` tinyint(3) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Table which contains documents to approve';

-- --------------------------------------------------------

--
-- Structure de la table `DocumentsFamiliesApprovals`
--

CREATE TABLE `DocumentsFamiliesApprovals` (
  `DocumentFamilyApprovalID` mediumint(8) UNSIGNED NOT NULL,
  `DocumentFamilyApprovalDate` datetime NOT NULL,
  `DocumentFamilyApprovalComment` varchar(255) DEFAULT NULL,
  `DocumentApprovalID` smallint(5) UNSIGNED NOT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Table which contains approvals of families';

-- --------------------------------------------------------

--
-- Structure de la table `Donations`
--

CREATE TABLE `Donations` (
  `DonationID` mediumint(8) UNSIGNED NOT NULL,
  `DonationReference` varchar(20) NOT NULL,
  `DonationEntity` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DonationLastname` varchar(100) NOT NULL,
  `DonationFirstname` varchar(25) NOT NULL,
  `DonationAddress` varchar(255) NOT NULL,
  `DonationPhone` varchar(30) DEFAULT NULL,
  `DonationMainEmail` varchar(100) DEFAULT NULL,
  `DonationSecondEmail` varchar(100) DEFAULT NULL,
  `DonationFamilyRelationship` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DonationReceptionDate` date NOT NULL,
  `DonationType` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DonationNature` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DonationValue` decimal(10,2) NOT NULL,
  `DonationReason` varchar(255) DEFAULT NULL,
  `DonationPaymentMode` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DonationPaymentCheckNb` varchar(30) DEFAULT NULL,
  `BankID` smallint(5) UNSIGNED DEFAULT NULL,
  `TownID` smallint(5) UNSIGNED NOT NULL,
  `FamilyID` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `EventRegistrations`
--

CREATE TABLE `EventRegistrations` (
  `EventRegistrationID` int(10) UNSIGNED NOT NULL,
  `EventRegistrationDate` datetime NOT NULL,
  `EventRegistrationValided` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `EventRegistrationComment` varchar(255) DEFAULT NULL,
  `EventID` mediumint(8) UNSIGNED NOT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Events`
--

CREATE TABLE `Events` (
  `EventID` mediumint(8) UNSIGNED NOT NULL,
  `EventDate` datetime NOT NULL,
  `EventTitle` varchar(100) NOT NULL,
  `EventStartDate` date NOT NULL,
  `EventStartTime` time DEFAULT NULL,
  `EventEndDate` date NOT NULL,
  `EventEndTime` time DEFAULT NULL,
  `EventDescription` mediumtext NOT NULL,
  `EventMaxParticipants` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `EventRegistrationDelay` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `EventClosingDate` date DEFAULT NULL,
  `ParentEventID` mediumint(8) UNSIGNED DEFAULT NULL,
  `EventTypeID` tinyint(3) UNSIGNED NOT NULL,
  `TownID` smallint(5) UNSIGNED NOT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `EventSwappedRegistrations`
--

CREATE TABLE `EventSwappedRegistrations` (
  `EventSwappedRegistrationID` int(10) UNSIGNED NOT NULL,
  `EventSwappedRegistrationDate` datetime NOT NULL,
  `EventSwappedRegistrationClosingDate` datetime DEFAULT NULL,
  `RequestorFamilyID` smallint(5) UNSIGNED NOT NULL,
  `RequestorEventID` mediumint(8) UNSIGNED NOT NULL,
  `AcceptorFamilyID` smallint(5) UNSIGNED DEFAULT NULL,
  `AcceptorEventID` mediumint(8) UNSIGNED DEFAULT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `EventTypes`
--

CREATE TABLE `EventTypes` (
  `EventTypeID` tinyint(3) UNSIGNED NOT NULL,
  `EventTypeName` varchar(25) NOT NULL,
  `EventTypeCategory` tinyint(3) UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `EventTypes`
--

INSERT INTO `EventTypes` (`EventTypeID`, `EventTypeName`, `EventTypeCategory`) VALUES
(1, 'Journée entretien/travaux', 1),
(2, 'Bal', 0),
(3, 'Vide-grenier', 0),
(4, 'Dictée', 0),
(5, 'Manifestation', 0),
(6, 'Remplacement cantine', 1),
(7, 'Remplacement garderie', 1),
(8, 'Journée ménage', 1);

-- --------------------------------------------------------

--
-- Structure de la table `ExitPermissions`
--

CREATE TABLE `ExitPermissions` (
  `ExitPermissionID` mediumint(8) UNSIGNED NOT NULL,
  `ExitPermissionDate` date NOT NULL,
  `ExitPermissionName` varchar(100) NOT NULL,
  `ExitPermissionAuthorizedPerson` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `ChildID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Families`
--

CREATE TABLE `Families` (
  `FamilyID` smallint(5) UNSIGNED NOT NULL,
  `FamilyLastname` varchar(100) NOT NULL,
  `FamilyMainEmail` varchar(100) DEFAULT NULL,
  `FamilyMainEmailContactAllowed` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `FamilyMainEmailInCommittee` tinyint(3) UNSIGNED DEFAULT '0',
  `FamilySecondEmail` varchar(100) DEFAULT NULL,
  `FamilySecondEmailContactAllowed` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `FamilySecondEmailInCommittee` tinyint(3) UNSIGNED DEFAULT '0',
  `FamilyDate` date NOT NULL,
  `FamilyDesactivationDate` date DEFAULT NULL,
  `FamilyNbMembers` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `FamilyNbPoweredMembers` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `FamilySpecialAnnualContribution` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `FamilyMonthlyContributionMode` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `FamilyAnnualContributionBalance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `FamilyBalance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `FamilyComment` mediumtext,
  `TownID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `HistoFamilies`
--

CREATE TABLE `HistoFamilies` (
  `HistoFamilyID` mediumint(8) UNSIGNED NOT NULL,
  `HistoDate` datetime NOT NULL,
  `HistoFamilyMonthlyContributionMode` tinyint(3) UNSIGNED NOT NULL,
  `HistoFamilyBalance` decimal(10,2) NOT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL,
  `TownID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `HistoLevelsChildren`
--

CREATE TABLE `HistoLevelsChildren` (
  `HistoLevelChildID` mediumint(8) UNSIGNED NOT NULL,
  `HistoLevelChildYear` smallint(5) UNSIGNED NOT NULL DEFAULT '2011',
  `HistoLevelChildGrade` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `HistoLevelChildClass` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `HistoLevelChildWithoutPork` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ChildID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Holidays`
--

CREATE TABLE `Holidays` (
  `HolidayID` smallint(5) UNSIGNED NOT NULL,
  `HolidayStartDate` date NOT NULL,
  `HolidayEndDate` date NOT NULL,
  `HolidayDescription` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `Holidays`
--

INSERT INTO `Holidays` (`HolidayID`, `HolidayStartDate`, `HolidayEndDate`, `HolidayDescription`) VALUES
(1, '2012-02-13', '2012-02-24', 'Vacances d\'hiver.'),
(2, '2012-04-09', '2012-04-20', 'Vacances de Pâques.'),
(3, '2012-07-06', '2012-09-03', 'Vacances d\'été.'),
(4, '2012-10-29', '2012-11-09', 'Toussaint.'),
(5, '2012-12-24', '2013-01-04', 'Vacances de Noël.'),
(6, '2012-05-07', '2012-05-07', 'Pont.'),
(7, '2012-05-18', '2012-05-18', 'Pont.'),
(8, '2013-02-25', '2013-03-08', 'Vacances d\'hiver.'),
(9, '2013-04-22', '2013-05-03', 'Vacances de Pâques.'),
(10, '2013-07-08', '2013-09-02', 'Vacances d\'été.'),
(11, '2013-10-19', '2013-11-03', 'Toussaint.'),
(12, '2013-12-21', '2014-01-05', 'Vacances de Noël.'),
(13, '2014-03-01', '2014-03-16', 'Vacances d\'hiver.'),
(14, '2014-04-26', '2014-05-11', 'Vacances de Pâques.'),
(15, '2014-07-05', '2014-09-01', 'Vacances d\'été.'),
(16, '2014-05-30', '2014-05-30', 'Congrès regentas.'),
(17, '2014-10-18', '2014-11-02', 'Toussaint.'),
(18, '2014-12-20', '2015-01-04', 'Vacances de Noël.'),
(19, '2015-02-07', '2015-02-22', 'Vacances d\'hiver.'),
(20, '2015-04-11', '2015-04-26', 'Vacances de Pâques.'),
(21, '2015-07-04', '2015-08-31', 'Vacances d\'été.'),
(22, '2015-10-17', '2015-11-01', 'Toussaint.'),
(23, '2015-12-19', '2016-01-03', 'Vacances de Noël.'),
(24, '2016-02-20', '2016-03-06', 'Vacances d\'hiver.'),
(25, '2016-04-16', '2016-05-01', 'Vacances de Pâques.'),
(26, '2016-07-06', '2016-08-31', 'Vacances d\'été.'),
(27, '2015-05-15', '2015-05-15', 'Pont.'),
(28, '2016-10-20', '2016-11-02', 'Toussaint.'),
(29, '2016-12-17', '2017-01-02', 'Vacances de Noël.'),
(30, '2017-02-04', '2017-02-19', 'Vacances d\'hivers.'),
(31, '2017-04-01', '2017-04-17', 'Vacances de Pâques.'),
(32, '2017-07-08', '2017-09-03', 'Vacances d\'été.'),
(33, '2017-05-26', '2017-05-26', 'Pont de mai.'),
(34, '2017-10-23', '2017-11-03', 'Toussaint.'),
(35, '2017-12-23', '2018-01-05', 'Vacances de Noël.'),
(36, '2018-02-17', '2018-03-02', 'Vacances d\'hivers.'),
(37, '2018-04-14', '2018-04-27', 'Vacances de Pâques.'),
(38, '2018-07-09', '2018-08-31', 'Vacances d\'été.'),
(39, '2017-12-01', '2017-12-01', 'Ecole fermée'),
(40, '2018-10-20', '2018-11-04', 'Toussaint.'),
(41, '2018-12-22', '2019-01-06', 'Vacances de Noël.'),
(42, '2019-02-23', '2019-03-10', 'Vacances d\'hiver.'),
(43, '2019-04-20', '2019-05-05', 'Vacances de Pâques.'),
(44, '2019-07-06', '2019-09-01', 'Vacances d\'été.'),
(45, '2019-10-21', '2019-11-01', 'Toussaint.'),
(46, '2019-12-23', '2020-01-03', 'Vacances de Noël.'),
(47, '2020-02-10', '2020-02-21', 'Vacances d\'hiver.'),
(48, '2020-04-06', '2020-04-17', 'Vacances de Pâques.'),
(49, '2020-07-06', '2020-08-31', 'Vacances d\'été.'),
(50, '2020-05-22', '2020-05-22', 'Pont Ascension.');

-- --------------------------------------------------------

--
-- Structure de la table `JobParameters`
--

CREATE TABLE `JobParameters` (
  `JobParameterID` int(10) UNSIGNED NOT NULL,
  `JobParameterName` varchar(50) NOT NULL,
  `JobParameterValue` mediumblob NOT NULL,
  `JobID` mediumint(8) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Jobs`
--

CREATE TABLE `Jobs` (
  `JobID` mediumint(8) UNSIGNED NOT NULL,
  `JobPlannedDate` datetime NOT NULL,
  `JobExecutionDate` datetime DEFAULT NULL,
  `JobType` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `JobNbTries` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `JobResult` varchar(255) DEFAULT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `LaundryRegistrations`
--

CREATE TABLE `LaundryRegistrations` (
  `LaundryRegistrationID` mediumint(8) UNSIGNED NOT NULL,
  `LaundryRegistrationDate` date NOT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `LogEvents`
--

CREATE TABLE `LogEvents` (
  `LogEventID` bigint(20) UNSIGNED NOT NULL,
  `LogEventDate` datetime NOT NULL,
  `LogEventItemID` int(10) UNSIGNED NOT NULL,
  `LogEventItemType` varchar(30) NOT NULL,
  `LogEventService` varchar(30) NOT NULL,
  `LogEventAction` varchar(30) NOT NULL,
  `LogEventLevel` tinyint(3) UNSIGNED NOT NULL DEFAULT '5',
  `LogEventTitle` varchar(255) DEFAULT NULL,
  `LogEventDescription` mediumtext,
  `LogEventLinkedObjectID` int(10) UNSIGNED DEFAULT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `MoreMeals`
--

CREATE TABLE `MoreMeals` (
  `MoreMealID` smallint(5) UNSIGNED NOT NULL,
  `MoreMealDate` date NOT NULL,
  `MoreMealForDate` date NOT NULL,
  `MoreMealQuantity` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `MoreMealWithoutPorkQuantity` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `NurseryRegistrations`
--

CREATE TABLE `NurseryRegistrations` (
  `NurseryRegistrationID` mediumint(8) UNSIGNED NOT NULL,
  `NurseryRegistrationDate` date NOT NULL,
  `NurseryRegistrationForDate` date NOT NULL,
  `NurseryRegistrationAdminDate` date DEFAULT NULL,
  `NurseryRegistrationForAM` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `NurseryRegistrationForPM` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `NurseryRegistrationChildGrade` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `NurseryRegistrationChildClass` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `NurseryRegistrationIsLate` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ChildID` smallint(5) UNSIGNED NOT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `OpenedSpecialDays`
--

CREATE TABLE `OpenedSpecialDays` (
  `OpenedSpecialDayID` smallint(5) UNSIGNED NOT NULL,
  `OpenedSpecialDayDate` date NOT NULL,
  `OpenedSpecialDayDescription` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Payments`
--

CREATE TABLE `Payments` (
  `PaymentID` mediumint(8) UNSIGNED NOT NULL,
  `PaymentDate` datetime NOT NULL,
  `PaymentReceiptDate` date NOT NULL,
  `PaymentType` tinyint(3) UNSIGNED NOT NULL,
  `PaymentMode` tinyint(3) UNSIGNED NOT NULL,
  `PaymentCheckNb` varchar(30) DEFAULT NULL,
  `PaymentAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `PaymentUsedAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BankID` smallint(5) UNSIGNED DEFAULT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `PaymentsBills`
--

CREATE TABLE `PaymentsBills` (
  `PaymentBillID` int(10) UNSIGNED NOT NULL,
  `BillID` mediumint(8) UNSIGNED NOT NULL,
  `PaymentID` mediumint(8) UNSIGNED NOT NULL,
  `PaymentBillPartAmount` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `SnackRegistrations`
--

CREATE TABLE `SnackRegistrations` (
  `SnackRegistrationID` mediumint(8) UNSIGNED NOT NULL,
  `SnackRegistrationDate` date NOT NULL,
  `SnackRegistrationClass` tinyint(3) UNSIGNED NOT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `SupportMembers`
--

CREATE TABLE `SupportMembers` (
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL,
  `SupportMemberLastname` varchar(50) NOT NULL DEFAULT '',
  `SupportMemberFirstname` varchar(25) NOT NULL DEFAULT '',
  `SupportMemberPhone` varchar(30) DEFAULT NULL,
  `SupportMemberEmail` varchar(100) NOT NULL DEFAULT '',
  `SupportMemberLogin` varchar(32) NOT NULL DEFAULT '',
  `SupportMemberPassword` varchar(32) NOT NULL DEFAULT '',
  `SupportMemberActivated` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `SupportMemberOpenIdUrl` varchar(255) DEFAULT NULL,
  `SupportMemberWebServiceKey` varchar(32) DEFAULT NULL,
  `SupportMemberStateID` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `FamilyID` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `SupportMembers`
--

INSERT INTO `SupportMembers` VALUES (1, 'NomAdmin', 'PrénomAdmin', NULL, 'admin@test.fr', '21232f297a57a5a743894a0e4a801fc3', '21232f297a57a5a743894a0e4a801fc3', 1, NULL, 'e00cf25ad42683b3df678c61f42c6bda', 1, NULL);
INSERT INTO `SupportMembers` VALUES (2, 'NomRF', 'PrénomRF', NULL, 'rf@test.fr', 'bea2f3fe6ec7414cdf0bf233abba7ef0', 'bea2f3fe6ec7414cdf0bf233abba7ef0', 1, NULL, 'a20990097370a570a2caad4ab750050e', 2, NULL);
INSERT INTO `SupportMembers` VALUES (3, 'NomRI', 'PrénomRI', NULL, 'ri@test.fr', '08c7b0daa33b1e5e86a230c1801254c9', '08c7b0daa33b1e5e86a230c1801254c9', 1, NULL, '43b11c8e7713467ebfc35483568956cb', 3, NULL);
INSERT INTO `SupportMembers` VALUES (4, 'NomAjude', 'PrénomAjude', NULL, 'ajude@test.fr', '3b6f421e7550395e28e091c5565ac80a', '3b6f421e7550395e28e091c5565ac80a', 1, NULL, 'e83fe65c5aad6c81d9d227f616eaf11e', 4, NULL);
INSERT INTO `SupportMembers` VALUES (5, 'Famille-Test1', 'PrénomFT1', NULL, 'ft1@test.fr', 'd877a9de8f3d2d5e8720df6a02b3ff11', 'd877a9de8f3d2d5e8720df6a02b3ff11', 1, NULL, 'ed51ca4140e446a6b5430cc42517db19', 5, 1);
INSERT INTO `SupportMembers` VALUES (6, 'Famille-Test2', 'PrénomFT2', NULL, 'ft2@test.fr', '6323fc22fa46f55f24c2d516802f8c35', '6323fc22fa46f55f24c2d516802f8c35', 1, NULL, '21d8634624ea87dd4e3aad0c097c9a86', 5, 2);
INSERT INTO `SupportMembers` VALUES (7, 'NomRA', 'PrénomRA', NULL, 'ra@test.fr', 'db26ee047a4c86fbd2fba73503feccb6', 'db26ee047a4c86fbd2fba73503feccb6', 1, NULL, 'ee5be0221b2f8f58735b187268a01154', 6, NULL);
INSERT INTO `SupportMembers` VALUES (8, 'NomRV', 'PrénomRV', NULL, 'rv@test.fr', '108bc7b6961e71b2e770387a378cbc10', '108bc7b6961e71b2e770387a378cbc10', 1, NULL, '4d6951e105a7046766bb400574fb91e7', 7, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `SupportMembersStates`
--

CREATE TABLE `SupportMembersStates` (
  `SupportMemberStateID` tinyint(3) UNSIGNED NOT NULL,
  `SupportMemberStateName` varchar(20) NOT NULL DEFAULT '',
  `SupportMemberStateDescription` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `SupportMembersStates`
--

INSERT INTO `SupportMembersStates` (`SupportMemberStateID`, `SupportMemberStateName`, `SupportMemberStateDescription`) VALUES
(1, 'Administrateur', NULL),
(2, 'Resp Facture', 'Responsable facturation.'),
(3, 'Resp Inscript', 'Responsable inscriptions cantine.'),
(4, 'Ajude', 'Ajude de la Calandreta.'),
(5, 'Famille', 'Famille de la Calandreta.'),
(6, 'Resp Admin', 'Responsable Administratif.'),
(7, 'Resp Ev', 'Responsable événements.'),
(8, 'Ancienne famille', 'Ancienne famille de la Calandreta.');

-- --------------------------------------------------------

--
-- Structure de la table `Suspensions`
--

CREATE TABLE `Suspensions` (
  `SuspensionID` smallint(5) UNSIGNED NOT NULL,
  `SuspensionStartDate` date NOT NULL,
  `SuspensionEndDate` date DEFAULT NULL,
  `SuspensionReason` varchar(255) DEFAULT NULL,
  `ChildID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Towns`
--

CREATE TABLE `Towns` (
  `TownID` smallint(5) UNSIGNED NOT NULL,
  `TownName` varchar(50) NOT NULL,
  `TownCode` varchar(5) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `Towns`
--

INSERT INTO `Towns` (`TownID`, `TownName`, `TownCode`) VALUES
(1, 'Muret', '31600'),
(2, 'Lherm', '31600'),
(3, 'Saint-Hilaire', '31410'),
(4, 'Labarthe-sur-Lèze', '31860'),
(5, 'Bérat', '31370'),
(6, 'Salles-sur-Garonne', '31390'),
(7, 'Roquettes', '31120'),
(8, 'Villeneuve-Tolosane', '31270'),
(9, 'Portet-sur-Garonne', '31120'),
(10, 'Cugnaux', '31270');

-- --------------------------------------------------------

--
-- Structure de la table `WorkGroupRegistrations`
--

CREATE TABLE `WorkGroupRegistrations` (
  `WorkGroupRegistrationID` smallint(5) UNSIGNED NOT NULL,
  `WorkGroupRegistrationDate` datetime NOT NULL,
  `WorkGroupRegistrationLastname` varchar(50) NOT NULL,
  `WorkGroupRegistrationFirstname` varchar(25) NOT NULL,
  `WorkGroupRegistrationEmail` varchar(100) NOT NULL,
  `WorkGroupRegistrationReferent` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `WorkGroupID` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `FamilyID` smallint(5) UNSIGNED DEFAULT NULL,
  `SupportMemberID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `WorkGroups`
--

CREATE TABLE `WorkGroups` (
  `WorkGroupID` tinyint(3) UNSIGNED NOT NULL,
  `WorkGroupName` varchar(50) NOT NULL,
  `WorkGroupDescription` varchar(255) DEFAULT NULL,
  `WorkGroupEmail` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `Alias`
--
ALTER TABLE `Alias`
  ADD PRIMARY KEY (`AliasID`);

--
-- Index pour la table `Banks`
--
ALTER TABLE `Banks`
  ADD PRIMARY KEY (`BankID`);

--
-- Index pour la table `Bills`
--
ALTER TABLE `Bills`
  ADD PRIMARY KEY (`BillID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `CanteenRegistrations`
--
ALTER TABLE `CanteenRegistrations`
  ADD PRIMARY KEY (`CanteenRegistrationID`),
  ADD KEY `ChildID` (`ChildID`);

--
-- Index pour la table `CanteenRegistrationsChildrenHabits`
--
ALTER TABLE `CanteenRegistrationsChildrenHabits`
  ADD PRIMARY KEY (`CanteenRegistrationChildHabitID`),
  ADD KEY `ChildID` (`ChildID`);

--
-- Index pour la table `Children`
--
ALTER TABLE `Children`
  ADD PRIMARY KEY (`ChildID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `ConfigParameters`
--
ALTER TABLE `ConfigParameters`
  ADD PRIMARY KEY (`ConfigParameterID`);

--
-- Index pour la table `DiscountsFamilies`
--
ALTER TABLE `DiscountsFamilies`
  ADD PRIMARY KEY (`DiscountFamilyID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `DocumentsApprovals`
--
ALTER TABLE `DocumentsApprovals`
  ADD PRIMARY KEY (`DocumentApprovalID`),
  ADD KEY `DocumentApprovalType` (`DocumentApprovalType`);

--
-- Index pour la table `DocumentsFamiliesApprovals`
--
ALTER TABLE `DocumentsFamiliesApprovals`
  ADD PRIMARY KEY (`DocumentFamilyApprovalID`),
  ADD KEY `SupportMemberID` (`SupportMemberID`);

--
-- Index pour la table `Donations`
--
ALTER TABLE `Donations`
  ADD PRIMARY KEY (`DonationID`),
  ADD KEY `TownID` (`TownID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `EventRegistrations`
--
ALTER TABLE `EventRegistrations`
  ADD PRIMARY KEY (`EventRegistrationID`),
  ADD KEY `EventID` (`EventID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `Events`
--
ALTER TABLE `Events`
  ADD PRIMARY KEY (`EventID`),
  ADD KEY `ParentEventID` (`ParentEventID`),
  ADD KEY `EventTypeID` (`EventTypeID`),
  ADD KEY `SupportMemberID` (`SupportMemberID`);

--
-- Index pour la table `EventSwappedRegistrations`
--
ALTER TABLE `EventSwappedRegistrations`
  ADD PRIMARY KEY (`EventSwappedRegistrationID`),
  ADD KEY `RequestorEventID` (`RequestorEventID`),
  ADD KEY `AcceptorEventID` (`AcceptorEventID`);

--
-- Index pour la table `EventTypes`
--
ALTER TABLE `EventTypes`
  ADD PRIMARY KEY (`EventTypeID`),
  ADD KEY `EvenTypeCategory` (`EventTypeCategory`);

--
-- Index pour la table `ExitPermissions`
--
ALTER TABLE `ExitPermissions`
  ADD PRIMARY KEY (`ExitPermissionID`),
  ADD KEY `ChildID` (`ChildID`);

--
-- Index pour la table `Families`
--
ALTER TABLE `Families`
  ADD PRIMARY KEY (`FamilyID`),
  ADD KEY `TownID` (`TownID`);

--
-- Index pour la table `HistoFamilies`
--
ALTER TABLE `HistoFamilies`
  ADD PRIMARY KEY (`HistoFamilyID`),
  ADD KEY `FamilyID` (`FamilyID`),
  ADD KEY `TownID` (`TownID`);

--
-- Index pour la table `HistoLevelsChildren`
--
ALTER TABLE `HistoLevelsChildren`
  ADD PRIMARY KEY (`HistoLevelChildID`),
  ADD KEY `HistoLevelChildYear` (`HistoLevelChildYear`),
  ADD KEY `ChildID` (`ChildID`);

--
-- Index pour la table `Holidays`
--
ALTER TABLE `Holidays`
  ADD PRIMARY KEY (`HolidayID`);

--
-- Index pour la table `JobParameters`
--
ALTER TABLE `JobParameters`
  ADD PRIMARY KEY (`JobParameterID`),
  ADD KEY `JobID` (`JobID`);

--
-- Index pour la table `Jobs`
--
ALTER TABLE `Jobs`
  ADD PRIMARY KEY (`JobID`),
  ADD KEY `JobType` (`JobType`);

--
-- Index pour la table `LaundryRegistrations`
--
ALTER TABLE `LaundryRegistrations`
  ADD PRIMARY KEY (`LaundryRegistrationID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `LogEvents`
--
ALTER TABLE `LogEvents`
  ADD PRIMARY KEY (`LogEventID`),
  ADD KEY `SupportMemberID` (`SupportMemberID`),
  ADD KEY `LogEventItemID` (`LogEventItemID`);

--
-- Index pour la table `MoreMeals`
--
ALTER TABLE `MoreMeals`
  ADD PRIMARY KEY (`MoreMealID`),
  ADD KEY `SupportMemberID` (`SupportMemberID`);

--
-- Index pour la table `NurseryRegistrations`
--
ALTER TABLE `NurseryRegistrations`
  ADD PRIMARY KEY (`NurseryRegistrationID`),
  ADD KEY `ChildID` (`ChildID`),
  ADD KEY `SupportMemberID` (`SupportMemberID`);

--
-- Index pour la table `OpenedSpecialDays`
--
ALTER TABLE `OpenedSpecialDays`
  ADD PRIMARY KEY (`OpenedSpecialDayID`);

--
-- Index pour la table `Payments`
--
ALTER TABLE `Payments`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `PaymentsBills`
--
ALTER TABLE `PaymentsBills`
  ADD PRIMARY KEY (`PaymentBillID`);

--
-- Index pour la table `SnackRegistrations`
--
ALTER TABLE `SnackRegistrations`
  ADD PRIMARY KEY (`SnackRegistrationID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `SupportMembers`
--
ALTER TABLE `SupportMembers`
  ADD PRIMARY KEY (`SupportMemberID`),
  ADD KEY `SupportMemberLastname` (`SupportMemberLastname`),
  ADD KEY `SupportMemberStateID` (`SupportMemberStateID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `SupportMembersStates`
--
ALTER TABLE `SupportMembersStates`
  ADD PRIMARY KEY (`SupportMemberStateID`),
  ADD KEY `SupportMemberStateName` (`SupportMemberStateName`);

--
-- Index pour la table `Suspensions`
--
ALTER TABLE `Suspensions`
  ADD PRIMARY KEY (`SuspensionID`),
  ADD KEY `ChildID` (`ChildID`);

--
-- Index pour la table `Towns`
--
ALTER TABLE `Towns`
  ADD PRIMARY KEY (`TownID`);

--
-- Index pour la table `WorkGroupRegistrations`
--
ALTER TABLE `WorkGroupRegistrations`
  ADD PRIMARY KEY (`WorkGroupRegistrationID`),
  ADD KEY `WorkGroupRegistrationReferent` (`WorkGroupRegistrationReferent`),
  ADD KEY `WorkGroupID` (`WorkGroupID`),
  ADD KEY `FamilyID` (`FamilyID`);

--
-- Index pour la table `WorkGroups`
--
ALTER TABLE `WorkGroups`
  ADD PRIMARY KEY (`WorkGroupID`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `Alias`
--
ALTER TABLE `Alias`
  MODIFY `AliasID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Banks`
--
ALTER TABLE `Banks`
  MODIFY `BankID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `Bills`
--
ALTER TABLE `Bills`
  MODIFY `BillID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `CanteenRegistrations`
--
ALTER TABLE `CanteenRegistrations`
  MODIFY `CanteenRegistrationID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `CanteenRegistrationsChildrenHabits`
--
ALTER TABLE `CanteenRegistrationsChildrenHabits`
  MODIFY `CanteenRegistrationChildHabitID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Children`
--
ALTER TABLE `Children`
  MODIFY `ChildID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ConfigParameters`
--
ALTER TABLE `ConfigParameters`
  MODIFY `ConfigParameterID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `DiscountsFamilies`
--
ALTER TABLE `DiscountsFamilies`
  MODIFY `DiscountFamilyID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `DocumentsApprovals`
--
ALTER TABLE `DocumentsApprovals`
  MODIFY `DocumentApprovalID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `DocumentsFamiliesApprovals`
--
ALTER TABLE `DocumentsFamiliesApprovals`
  MODIFY `DocumentFamilyApprovalID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Donations`
--
ALTER TABLE `Donations`
  MODIFY `DonationID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `EventRegistrations`
--
ALTER TABLE `EventRegistrations`
  MODIFY `EventRegistrationID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Events`
--
ALTER TABLE `Events`
  MODIFY `EventID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `EventSwappedRegistrations`
--
ALTER TABLE `EventSwappedRegistrations`
  MODIFY `EventSwappedRegistrationID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `EventTypes`
--
ALTER TABLE `EventTypes`
  MODIFY `EventTypeID` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `ExitPermissions`
--
ALTER TABLE `ExitPermissions`
  MODIFY `ExitPermissionID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Families`
--
ALTER TABLE `Families`
  MODIFY `FamilyID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `HistoFamilies`
--
ALTER TABLE `HistoFamilies`
  MODIFY `HistoFamilyID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `HistoLevelsChildren`
--
ALTER TABLE `HistoLevelsChildren`
  MODIFY `HistoLevelChildID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Holidays`
--
ALTER TABLE `Holidays`
  MODIFY `HolidayID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT pour la table `JobParameters`
--
ALTER TABLE `JobParameters`
  MODIFY `JobParameterID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Jobs`
--
ALTER TABLE `Jobs`
  MODIFY `JobID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `LaundryRegistrations`
--
ALTER TABLE `LaundryRegistrations`
  MODIFY `LaundryRegistrationID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `LogEvents`
--
ALTER TABLE `LogEvents`
  MODIFY `LogEventID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `MoreMeals`
--
ALTER TABLE `MoreMeals`
  MODIFY `MoreMealID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `NurseryRegistrations`
--
ALTER TABLE `NurseryRegistrations`
  MODIFY `NurseryRegistrationID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `OpenedSpecialDays`
--
ALTER TABLE `OpenedSpecialDays`
  MODIFY `OpenedSpecialDayID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Payments`
--
ALTER TABLE `Payments`
  MODIFY `PaymentID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `PaymentsBills`
--
ALTER TABLE `PaymentsBills`
  MODIFY `PaymentBillID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `SnackRegistrations`
--
ALTER TABLE `SnackRegistrations`
  MODIFY `SnackRegistrationID` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `SupportMembers`
--
ALTER TABLE `SupportMembers`
  MODIFY `SupportMemberID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `SupportMembersStates`
--
ALTER TABLE `SupportMembersStates`
  MODIFY `SupportMemberStateID` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `Suspensions`
--
ALTER TABLE `Suspensions`
  MODIFY `SuspensionID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Towns`
--
ALTER TABLE `Towns`
  MODIFY `TownID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `WorkGroupRegistrations`
--
ALTER TABLE `WorkGroupRegistrations`
  MODIFY `WorkGroupRegistrationID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;
