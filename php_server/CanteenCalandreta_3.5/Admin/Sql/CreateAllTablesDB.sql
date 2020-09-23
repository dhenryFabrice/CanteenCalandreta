-- phpMyAdmin SQL Dump
-- version 4.8.4
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le :  mer. 23 septembre 2020 à 14:37
-- Version du serveur :  5.5.62-MariaDB
-- Version de PHP :  5.6.40

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

DROP TABLE IF EXISTS `Alias`;
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

DROP TABLE IF EXISTS `Banks`;
CREATE TABLE `Banks` (
  `BankID` smallint(5) UNSIGNED NOT NULL,
  `BankName` varchar(50) NOT NULL,
  `BankAcronym` varchar(5) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Bills`
--

DROP TABLE IF EXISTS `Bills`;
CREATE TABLE `Bills` (
  `BillID` mediumint(8) UNSIGNED NOT NULL,
  `BillDate` datetime NOT NULL,
  `BillForDate` date NOT NULL,
  `BillPreviousBalance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillDeposit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillMonthlyContribution` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillNbCanteenRegistrations` TINYINT UNSIGNED NULL DEFAULT NULL,
  `BillCanteenAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillWithoutMealAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BillNbNurseryRegistrations` TINYINT UNSIGNED NULL DEFAULT NULL,
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

DROP TABLE IF EXISTS `CanteenRegistrations`;
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

DROP TABLE IF EXISTS `CanteenRegistrationsChildrenHabits`;
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

DROP TABLE IF EXISTS `Children`;
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=PAGE;

-- --------------------------------------------------------

--
-- Structure de la table `ConfigParameters`
--

DROP TABLE IF EXISTS `ConfigParameters`;
CREATE TABLE `ConfigParameters` (
  `ConfigParameterID` smallint(5) UNSIGNED NOT NULL,
  `ConfigParameterName` varchar(255) NOT NULL,
  `ConfigParameterType` varchar(10) NOT NULL,
  `ConfigParameterValue` mediumtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `DiscountsFamilies`
--

DROP TABLE IF EXISTS `DiscountsFamilies`;
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

DROP TABLE IF EXISTS `DocumentsApprovals`;
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

DROP TABLE IF EXISTS `DocumentsFamiliesApprovals`;
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

DROP TABLE IF EXISTS `Donations`;
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

DROP TABLE IF EXISTS `EventRegistrations`;
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

DROP TABLE IF EXISTS `Events`;
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

DROP TABLE IF EXISTS `EventSwappedRegistrations`;
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

DROP TABLE IF EXISTS `EventTypes`;
CREATE TABLE `EventTypes` (
  `EventTypeID` tinyint(3) UNSIGNED NOT NULL,
  `EventTypeName` varchar(25) NOT NULL,
  `EventTypeCategory` tinyint(3) UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `ExitPermissions`
--

DROP TABLE IF EXISTS `ExitPermissions`;
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

DROP TABLE IF EXISTS `Families`;
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=PAGE;

-- --------------------------------------------------------

--
-- Structure de la table `HistoFamilies`
--

DROP TABLE IF EXISTS `HistoFamilies`;
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

DROP TABLE IF EXISTS `HistoLevelsChildren`;
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

DROP TABLE IF EXISTS `Holidays`;
CREATE TABLE `Holidays` (
  `HolidayID` smallint(5) UNSIGNED NOT NULL,
  `HolidayStartDate` date NOT NULL,
  `HolidayEndDate` date NOT NULL,
  `HolidayDescription` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `JobParameters`
--

DROP TABLE IF EXISTS `JobParameters`;
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

DROP TABLE IF EXISTS `Jobs`;
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

DROP TABLE IF EXISTS `LaundryRegistrations`;
CREATE TABLE `LaundryRegistrations` (
  `LaundryRegistrationID` mediumint(8) UNSIGNED NOT NULL,
  `LaundryRegistrationDate` date NOT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `LogEvents`
--

DROP TABLE IF EXISTS `LogEvents`;
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
-- Structure de la table `MeetingRooms`
--

DROP TABLE IF EXISTS `MeetingRooms`;
CREATE TABLE `MeetingRooms` (
`MeetingRoomID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `MeetingRoomName` varchar(100) NOT NULL,
  `MeetingRoomRestrictions` varchar(255) DEFAULT NULL,
  `MeetingRoomEmail` varchar(255) DEFAULT NULL,
  `MeetingRoomActivated` tinyint(3) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Table which contains meeting rooms.';

-- --------------------------------------------------------

--
-- Structure de la table `MeetingRoomsRegistrations`
--

DROP TABLE IF EXISTS `MeetingRoomsRegistrations`;
CREATE TABLE `MeetingRoomsRegistrations` (
`MeetingRoomRegistrationID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `MeetingRoomRegistrationDate` datetime NOT NULL,
  `MeetingRoomRegistrationTitle` varchar(100) NOT NULL,
  `MeetingRoomRegistrationStartDate` datetime NOT NULL,
  `MeetingRoomRegistrationEndDate` datetime NOT NULL,
  `MeetingRoomRegistrationMailingList` varchar(255) DEFAULT NULL,
  `MeetingRoomRegistrationDescription` mediumtext,
  `SupportMemberID` smallint(5) unsigned NOT NULL,
  `MeetingRoomID` tinyint(3) unsigned NOT NULL,
  `EventID` mediumint(8) unsigned DEFAULT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Table which contains registrations of meeting rooms.';

-- --------------------------------------------------------

--
-- Structure de la table `MoreMeals`
--

DROP TABLE IF EXISTS `MoreMeals`;
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

DROP TABLE IF EXISTS `NurseryRegistrations`;
CREATE TABLE `NurseryRegistrations` (
  `NurseryRegistrationID` mediumint(8) UNSIGNED NOT NULL,
  `NurseryRegistrationDate` date NOT NULL,
  `NurseryRegistrationForDate` date NOT NULL,
  `NurseryRegistrationAdminDate` date DEFAULT NULL,
  `NurseryRegistrationForAM` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `NurseryRegistrationForPM` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `NurseryRegistrationOtherTimeslots` TINYINT UNSIGNED NULL,
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

DROP TABLE IF EXISTS `OpenedSpecialDays`;
CREATE TABLE `OpenedSpecialDays` (
  `OpenedSpecialDayID` smallint(5) UNSIGNED NOT NULL,
  `OpenedSpecialDayDate` date NOT NULL,
  `OpenedSpecialDayDescription` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Payments`
--

DROP TABLE IF EXISTS `Payments`;
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

DROP TABLE IF EXISTS `PaymentsBills`;
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

DROP TABLE IF EXISTS `SnackRegistrations`;
CREATE TABLE `SnackRegistrations` (
  `SnackRegistrationID` mediumint(8) UNSIGNED NOT NULL,
  `SnackRegistrationDate` date NOT NULL,
  `SnackRegistrationClass` tinyint(3) UNSIGNED NOT NULL,
  `FamilyID` smallint(5) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Stats`
--

DROP TABLE IF EXISTS `Stats`;
CREATE TABLE `Stats` (
`StatID` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `StatPeriod` varchar(20) NOT NULL,
  `StatType` varchar(30) NOT NULL,
  `StatSubType` varchar(30) DEFAULT NULL,
  `StatValue` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Table which contains some stats about the application.';

-- --------------------------------------------------------

--
-- Structure de la table `SupportMembers`
--

DROP TABLE IF EXISTS `SupportMembers`;
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

-- --------------------------------------------------------

--
-- Structure de la table `SupportMembersStates`
--

DROP TABLE IF EXISTS `SupportMembersStates`;
CREATE TABLE `SupportMembersStates` (
  `SupportMemberStateID` tinyint(3) UNSIGNED NOT NULL,
  `SupportMemberStateName` varchar(20) NOT NULL DEFAULT '',
  `SupportMemberStateDescription` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `Suspensions`
--

DROP TABLE IF EXISTS `Suspensions`;
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

DROP TABLE IF EXISTS `Towns`;
CREATE TABLE `Towns` (
  `TownID` smallint(5) UNSIGNED NOT NULL,
  `TownName` varchar(50) NOT NULL,
  `TownCode` varchar(5) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `UploadedFiles`
--

DROP TABLE IF EXISTS `UploadedFiles`;
CREATE TABLE `UploadedFiles` (
`UploadedFileID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `UploadedFileObjectType` tinyint(3) unsigned NOT NULL,
  `UploadedFileDate` datetime NOT NULL,
  `UploadedFileName` varchar(255) NOT NULL,
  `UploadedFileDescription` varchar(255) DEFAULT NULL,
  `ObjectID` mediumint(8) unsigned NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Table which contains uploaded files linked to application''s objects.';

-- --------------------------------------------------------

--
-- Structure de la table `WorkGroupRegistrations`
--

DROP TABLE IF EXISTS `WorkGroupRegistrations`;
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

DROP TABLE IF EXISTS `WorkGroups`;
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
-- Index pour la table `MeetingRoomsRegistrations`
--
ALTER TABLE `MeetingRoomsRegistrations` 
  ADD KEY `SupportMemberID` (`SupportMemberID`), 
  ADD KEY `EventID` (`EventID`), 
  ADD KEY `MeetingRoomID` (`MeetingRoomID`);

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
  MODIFY `BankID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `ConfigParameterID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `EventTypeID` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `HolidayID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `SupportMemberID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `SupportMembersStates`
--
ALTER TABLE `SupportMembersStates`
  MODIFY `SupportMemberStateID` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Suspensions`
--
ALTER TABLE `Suspensions`
  MODIFY `SuspensionID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Towns`
--
ALTER TABLE `Towns`
  MODIFY `TownID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `WorkGroupRegistrations`
--
ALTER TABLE `WorkGroupRegistrations`
  MODIFY `WorkGroupRegistrationID` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;
