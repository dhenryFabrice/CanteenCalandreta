<?php
/* Copyright (C) 2012 Calandreta Del Païs Murethin
 *
 * This file is part of CanteenCalandreta.
 *
 * CanteenCalandreta is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CanteenCalandreta is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CanteenCalandreta; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Support module : process the update of a family. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.3
 *     - 2012-07-12 : taken into account the FamilySpecialAnnualContribution field
 *     - 2013-09-18 : taken into account the FCT_ACT_PARTIAL_READ_ONLY access right and
 *                    the FamilyMonthlyContributionMode field
 *     - 2015-09-21 : send notification when an e-mail of family is changed
 *     - 2016-10-12 : taken into account Bcc and load some configuration variables from database
 *     - 2017-09-21 : taken into account FCT_ACT_UPDATE_OLD_USER access for old families. They can update
 *                    only some data even the desactivation date is set. Send notification if contacts
 *                    allowed change. If desactivation date set, the user account is changed
 *     - 2019-01-21 : taken into account FamilyMainEmailInCommittee and FamilySecondEmailInCommittee fields
 *     - 2019-07-16 : taken into account the FamilyAnnualContributionBalance field to split payments for bills
 *                    and payments for annual contributions
 *
 * @since 2012-01-23
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted family ID
 // Crypted ID
 if (!empty($_GET["Cr"]))
 {
     $CryptedID = (string)strip_tags($_GET["Cr"]);
 }
 else
 {
     $CryptedID = "";
 }

 // No-crypted ID
 if (!empty($_GET["Id"]))
 {
     $Id = (string)strip_tags($_GET["Id"]);
 }
 else
 {
     $Id = "";
 }

 if ((empty($Id)) && (isSet($_POST['hidFamilyID'])))
 {
     $Id = trim(strip_tags($_POST['hidFamilyID']));
 }

 //################################ FORM PROCESSING ##########################
 // The supporter must be allowed to update a family
 $iNbMailsSent = 0;
 $cUserAccess = FCT_ACT_NO_RIGHTS;
 $AccessRules = $CONF_ACCESS_APPL_PAGES[FCT_FAMILY];

 if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
 {
     // Update mode
     $cUserAccess = FCT_ACT_UPDATE;
 }
 elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
 {
     // Read mode
     $cUserAccess = FCT_ACT_READ_ONLY;
 }
 elseif ((isset($AccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
 {
     // Partial read mode
     $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
 }
 elseif ((isset($AccessRules[FCT_ACT_UPDATE_OLD_USER])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE_OLD_USER])))
 {
     // Update old user mode (for old families)
     $cUserAccess = FCT_ACT_UPDATE_OLD_USER;
 }

 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS',
                                              'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                              'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                              'CONF_CANTEEN_PRICES',
                                              'CONF_NURSERY_PRICES',
                                              'CONF_NURSERY_DELAYS_PRICES'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the family
         if (isExistingFamily($DbCon, $Id))
         {
             // The family exists
             $FamilyID = $Id;
         }
         else
         {
             // ERROR : the purpose doesn't exist
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $Lastname = existedPOSTFieldValue("sLastname", NULL);
         if (!is_Null($Lastname))
         {
             $Lastname = trim(strip_tags($_POST["sLastname"]));
             if (empty($Lastname))
             {
                 $ContinueProcess = FALSE;
             }
         }

         $MainEmail = trim(strip_tags($_POST["sMainEmail"]));
         if ((empty($MainEmail)) || (!isValideEmailAddress($MainEmail)))
         {
             // Wrong e-mail
             $ContinueProcess = FALSE;
         }

         // We check if the main e-mail address is allowed to be contacted
         $MainEmailContactAllowed = existedPOSTFieldValue("chkMainEmailContactAllowed", NULL);
         if (in_array($cUserAccess, array(FCT_ACT_PARTIAL_READ_ONLY, FCT_ACT_UPDATE_OLD_USER)))
         {
             if (is_null($MainEmailContactAllowed))
             {
                 $MainEmailContactAllowed = 0;
             }
         }
         else
         {
             if (is_null($MainEmailContactAllowed))
             {
                 $MainEmailContactAllowed = NULL;
             }
         }

         // We check if the main e-mail address is in the committee
         $MainEmailInCommittee = existedPOSTFieldValue("chkMainEmailInCommittee", NULL);
         if (in_array($cUserAccess, array(FCT_ACT_PARTIAL_READ_ONLY, FCT_ACT_UPDATE_OLD_USER)))
         {
             if (is_null($MainEmailInCommittee))
             {
                 $MainEmailInCommittee = 0;
             }
         }
         else
         {
             if (is_null($MainEmailInCommittee))
             {
                 $MainEmailInCommittee = NULL;
             }
         }

         $SecondEmail = trim(strip_tags($_POST["sSecondEmail"]));
         if (!empty($SecondEmail))
         {
             if (!isValideEmailAddress($SecondEmail))
             {
                 // Wrong e-mail
                 $ContinueProcess = FALSE;
             }
         }

         // We check if the second e-mail address is allowed to be contacted
         $SecondEmailContactAllowed = existedPOSTFieldValue("chkSecondEmailContactAllowed", NULL);
         if (in_array($cUserAccess, array(FCT_ACT_PARTIAL_READ_ONLY, FCT_ACT_UPDATE_OLD_USER)))
         {
             if (is_null($SecondEmailContactAllowed))
             {
                 $SecondEmailContactAllowed = 0;
             }
         }
         else
         {
             if (is_null($SecondEmailContactAllowed))
             {
                 $SecondEmailContactAllowed = NULL;
             }
         }

         // We check if the second e-mail address is in the committee
         if (empty($SecondEmail))
         {
             // This field can be set if no e-mail address set
             $SecondEmailInCommittee = 0;
         }
         else
         {
             $SecondEmailInCommittee = existedPOSTFieldValue("chkSecondEmailInCommittee", NULL);
             if (in_array($cUserAccess, array(FCT_ACT_PARTIAL_READ_ONLY, FCT_ACT_UPDATE_OLD_USER)))
             {
                 if (is_null($SecondEmailInCommittee))
                 {
                     $SecondEmailInCommittee = 0;
                 }
             }
             else
             {
                 if (is_null($SecondEmailInCommittee))
                 {
                     $SecondEmailInCommittee = NULL;
                 }
             }
         }

         // We check if the family is a "special" family about the annual contribution
         $SpecialAnnualContribution = existedPOSTFieldValue("chkSpecialAnnualContribution", NULL);
         if (in_array($cUserAccess, array(FCT_ACT_PARTIAL_READ_ONLY)))
         {
             if (is_null($SpecialAnnualContribution))
             {
                 $SpecialAnnualContribution = NULL;
             }
         }
         else
         {
             if (is_null($SpecialAnnualContribution))
             {
                 $SpecialAnnualContribution = 0;
             }
         }

         // Get the monthly contribution mode
         $MonthyContributionMode = existedPOSTFieldValue("lMonthlyContributionMode", NULL);
         if (!is_null($MonthyContributionMode))
         {
             $MonthyContributionMode = strip_tags($MonthyContributionMode);
         }

         $NbNumbers = existedPOSTFieldValue("sNbMembers", NULL);
         if (!is_Null($NbNumbers))
         {
             $NbNumbers = nullFormatText(strip_tags($_POST["sNbMembers"]), "NULL");
             if (is_Null($NbNumbers))
             {
                 // By default, 1 family = 1 member
                 $NbNumbers = 1;
             }
             else
             {
                 // The number of members must be an integer > 1
                 if ((integer)$NbNumbers < 1)
                 {
                     $ContinueProcess = FALSE;
                 }
             }
         }

         $NbPoweredNumbers = existedPOSTFieldValue("sNbPoweredMembers", NULL);
         if (!is_Null($NbPoweredNumbers))
         {
             $NbPoweredNumbers = nullFormatText(strip_tags($_POST["sNbPoweredMembers"]), "NULL");
             if (is_Null($NbPoweredNumbers))
             {
                 // By default, 1 family = 0 member
                 $NbPoweredNumbers = 0;
             }
             else
             {
                 // The number of powered members must be an integer >= 0
                 if ((integer)$NbPoweredNumbers < 0)
                 {
                     $ContinueProcess = FALSE;
                 }
             }
         }

         // We get the town
         $TownID = $_POST["lTownID"];
         if ($TownID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We have to convert the desactivation date in english format (format used in the database)
         $DesactivationDate = existedPOSTFieldValue("desactivationDate", NULL);
         if (!is_Null($DesactivationDate))
         {
             $DesactivationDate = nullFormatText(formatedDate2EngDate($_POST["desactivationDate"]), "NULL");
         }

         $Comment = existedPOSTFieldValue("sComment", NULL);
         if (!is_Null($Comment))
         {
             $Comment = formatText($_POST["sComment"]);
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // Get the previous values of nb of members (with or without power)
             $RecordOldFamily = getTableRecordInfos($DbCon, "Families", $FamilyID);

             // Now, we can update the family with the new values
             $FamilyID = dbUpdateFamily($DbCon, $FamilyID, NULL, $Lastname, $TownID, $MainEmail, $SecondEmail, $NbNumbers,
                                        $NbPoweredNumbers, NULL, $Comment, $DesactivationDate, $SpecialAnnualContribution,
                                        $MonthyContributionMode, $MainEmailContactAllowed, $SecondEmailContactAllowed,
                                        $MainEmailInCommittee, $SecondEmailInCommittee, NULL);

             if ($FamilyID != 0)
             {
                 $iOldNbMembers = $RecordOldFamily['FamilyNbMembers'] + $RecordOldFamily['FamilyNbPoweredMembers'];

                 // We search the current school year
                 $CurrentDate = date('Y-m-d');
                 $SchoolYear = getSchoolYear($CurrentDate);

                 // How many contributions?
                 $NbContributions = $NbNumbers + $NbPoweredNumbers;
                 $iNbDiffContributions = $iOldNbMembers - $NbContributions;

                 if (isset($CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][$iOldNbMembers]))
                 {
                     $OldPrice = $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][$iOldNbMembers];
                 }

                 if (isset($CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][$NbContributions]))
                 {
                     $NewPrice = $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][$NbContributions];
                 }

                 if ($RecordOldFamily['FamilySpecialAnnualContribution'] != $SpecialAnnualContribution)
                 {
                     // Price off for annual contribution?
                     $AnnualPriceOff = 0;
                     if ($SpecialAnnualContribution == 1)
                     {
                         // Special family : we remove 1 powered number to compute the annual contribution
                         if ($NbPoweredNumbers > 0)
                         {
                             $AnnualPriceOff = $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][1];
                         }
                     }
                     else
                     {
                         // Not a special family : we remove the proce off
                         if ($NbPoweredNumbers > 0)
                         {
                             $AnnualPriceOff = -1.00 * $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$SchoolYear][1];
                         }
                     }

                     $NewPrice -= $AnnualPriceOff;
                 }

                 $Balance = -1.00 * ($NewPrice - $OldPrice);
                 $NewBalance = updateFamilyAnnualContributionBalance($DbCon, $FamilyID, $Balance);

                 // We check if the user account associated to this family must changed in old user account
                 if ((!empty($DesactivationDate)) && (empty($RecordOldFamily['FamilyDesactivationDate'])))
                 {
                     // Get the ID of old support member state
                     if ((isset($CONF_ACCESS_APPL_PAGES[FCT_FAMILY][FCT_ACT_UPDATE_OLD_USER]))
                         && (count($CONF_ACCESS_APPL_PAGES[FCT_FAMILY][FCT_ACT_UPDATE_OLD_USER]) > 0))
                     {
                         $OldSupportMemberStateID = $CONF_ACCESS_APPL_PAGES[FCT_FAMILY][FCT_ACT_UPDATE_OLD_USER][0];

                         // Get all user accounts associated to this family
                         $ArrayFamilySupporters = dbSearchSupportMember($DbCon, array('FamilyID' => array($FamilyID)), '', 1, 0);
                         if ((isset($ArrayFamilySupporters['SupportMemberID'])) && (count($ArrayFamilySupporters['SupportMemberID']) > 0))
                         {
                             foreach($ArrayFamilySupporters['SupportMemberID'] as $sm => $CurrentSupporterID)
                             {
                                 dbSetSupportMemberStateOfSupportMember($DbCon, $CurrentSupporterID, $OldSupportMemberStateID);
                             }
                         }
                     }

                     unset($ArrayFamilySupporters);
                 }

                 // We check if one of the family's e-mails is changed
                 $sChangedEmails = '';
                 if (($RecordOldFamily['FamilyMainEmail'] != $MainEmail) && (!empty($MainEmail)))
                 {
                     // The main e-mail can only be updated (not added or removed)
                     $sChangedEmails = $RecordOldFamily['FamilyMainEmail']
                                       ." $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_EMAIL_REPLACED_BY $MainEmail";
                 }

                 if ($RecordOldFamily['FamilySecondEmail'] != $SecondEmail)
                 {
                     if (!empty($sChangedEmails))
                     {
                         $sChangedEmails .= ", ";
                     }

                     if (empty($RecordOldFamily['FamilySecondEmail']))
                     {
                         // Second e-mail added
                         $sChangedEmails .= "$SecondEmail $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_EMAIL_ADDED";
                     }
                     elseif (empty($SecondEmail))
                     {
                         // Second e-mail removed
                         $sChangedEmails .= $RecordOldFamily['FamilySecondEmail']
                                            ." $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_EMAIL_REMOVED";
                     }
                     else
                     {
                         // Second e-mail updated
                         $sChangedEmails .= $RecordOldFamily['FamilySecondEmail']
                                            ." $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_EMAIL_REPLACED_BY $SecondEmail";
                     }
                 }

                 // If one of family's e-mails changed, we send a notification
                 if ((!empty($sChangedEmails)) && (isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Template]))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Template]))
                    )
                 {
                     // We have to send a notification
                     $EmailSubject = $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SYSTEM]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SYSTEM].$EmailSubject;
                     }

                     $FamilyUrl = $CONF_URL_SUPPORT."Canteen/UpdateFamily.php?Cr=".md5($FamilyID)."&Id=$FamilyID";
                     $FamilyLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     // We get concerned classrooms (so activated children)
                     $sChildrenClassrooms = '';
                     $ArrayChildren = dbSearchChild($DbCon, array('FamilyID' => $FamilyID, 'Activated' => array($SchoolYear)),
                                                    "ChildClass", 1, 0);

                     if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
                     {
                         $ArrayClassrooms = array_unique($ArrayChildren['ChildClass']);
                         foreach($ArrayChildren['ChildClass'] as $c => $CurrentClass)
                         {
                             if (!empty($sChildrenClassrooms))
                             {
                                 $sChildrenClassrooms .= ", ";
                             }

                             $sChildrenClassrooms .= $CONF_CLASSROOMS[$SchoolYear][$CurrentClass];
                         }
                     }

                     unset($ArrayChildren, $ArrayClassrooms);

                     // We define the content of the mail
                     $TemplateToUse = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{FamilyLastname}", "{FamilyChangedEmails}", "{ChildrenClassrooms}",
                                                      "{FamilyUrl}", "{FamilyLinkTip}"
                                                     ),
                                                array(
                                                      $Lastname, $sChangedEmails, $sChildrenClassrooms, $FamilyUrl, $FamilyLinkTip
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList = array();
                     if (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][To]))
                     {
                         $MailingList["to"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][To];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Cc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Cc]))
                        )
                     {
                         $MailingList["cc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Cc];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Bcc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Bcc]))
                        )
                     {
                         $MailingList["bcc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailUpdated'][Bcc];
                     }

                     // DEBUG MODE
                     if ($GLOBALS["CONF_MODE_DEBUG"])
                     {
                         if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                         {
                             // Without this test, there is a server mail error...
                             $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                         }
                     }

                     // We send the e-mail
                     sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                     $iNbMailsSent++;
                 }

                 // We check if the family has changed allowed contacts
                 $sChangedAllowedContacts = '';
                 if (($RecordOldFamily['FamilyMainEmailContactAllowed'] != $MainEmailContactAllowed)
                     && (!is_null($MainEmailContactAllowed)))
                 {
                     // The main e-mail contact allowed has been updated
                     $sChangedAllowedContacts = "$MainEmail : $LANG_FAMILY_EMAIL_CONTACT_ALLOWED -> ";
                     if ($MainEmailContactAllowed == 1)
                     {
                         $sChangedAllowedContacts .= $LANG_YES;
                     }
                     else
                     {
                         $sChangedAllowedContacts .= $LANG_NO;
                     }
                 }

                 if (($RecordOldFamily['FamilySecondEmailContactAllowed'] != $SecondEmailContactAllowed)
                     && (!is_null($SecondEmailContactAllowed)))
                 {
                     if (!empty($sChangedAllowedContacts))
                     {
                         $sChangedAllowedContacts .= "<br />\n";
                     }

                     // The second e-mail contact allowed has been updated
                     $sChangedAllowedContacts .= "$SecondEmail : $LANG_FAMILY_EMAIL_CONTACT_ALLOWED -> ";
                     if ($SecondEmailContactAllowed == 1)
                     {
                         $sChangedAllowedContacts .= $LANG_YES;
                     }
                     else
                     {
                         $sChangedAllowedContacts .= $LANG_NO;
                     }
                 }

                 // If one of family's allowed contact by e-mail changed, we send a notification
                 if ((!empty($sChangedAllowedContacts)) && (isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Template]))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Template]))
                    )
                 {
                     // We have to send a notification
                     $EmailSubject = $LANG_SYSTEM_EMAIL_OLD_FAMILY_EMAIL_CONTACT_ALLOWED_UPDATED_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SYSTEM]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SYSTEM].$EmailSubject;
                     }

                     $FamilyUrl = $CONF_URL_SUPPORT."Canteen/UpdateFamily.php?Cr=".md5($FamilyID)."&Id=$FamilyID";
                     $FamilyLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     // We define the content of the mail
                     $TemplateToUse = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{FamilyLastname}", "{FamilyEmailContactAllowedChanged}",
                                                      "{FamilyUrl}", "{FamilyLinkTip}"
                                                     ),
                                                array(
                                                      $Lastname, $sChangedAllowedContacts, $FamilyUrl, $FamilyLinkTip
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList = array();
                     if (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][To]))
                     {
                         $MailingList["to"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][To];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Cc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Cc]))
                        )
                     {
                         $MailingList["cc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Cc];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Bcc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Bcc]))
                        )
                     {
                         $MailingList["bcc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['FamilyEmailContactAllowedUpdated'][Bcc];
                     }

                     // DEBUG MODE
                     if ($GLOBALS["CONF_MODE_DEBUG"])
                     {
                         if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                         {
                             // Without this test, there is a server mail error...
                             $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                         }
                     }

                     // We send the e-mail
                     sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                     $iNbMailsSent++;
                 }

                 // Log event
                 logEvent($DbCon, EVT_FAMILY, EVT_SERV_FAMILY, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $FamilyID);

                 // The family is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_FAMILY_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($FamilyID)."&Id=$FamilyID"; // For the redirection
             }
             else
             {
                 // The family can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_FAMILY;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "Cr=".md5($FamilyID)."&Id=$FamilyID"; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($Lastname))
             {
                 // The lastname is empty
                 $ConfirmationSentence = $LANG_ERROR_FAMILY_LASTNAME;
             }
             elseif ($TownID == 0)
             {
                 // No town
                 $ConfirmationSentence = $LANG_ERROR_TOWN;
             }
             elseif ((empty($MainEmail)) || (!isValideEmailAddress($MainEmail)))
             {
                 // The main e-mail is empty or wrong
                 $ConfirmationSentence = $LANG_ERROR_WRONG_MAIN_EMAIL;
             }
             elseif ((!empty($SecondEmail)) && (!isValideEmailAddress($SecondEmail)))
             {
                 // The second e-mail is wrong
                 $ConfirmationSentence = $LANG_ERROR_WRONG_SECOND_EMAIL;
             }
             elseif ((!empty($NbMembers)) && ((integer)$NbMembers < 1))
             {
                 // Wrong nb members
                 $ConfirmationSentence = $LANG_ERROR_WRONG_NB_MEMBERS;
             }
             elseif ((!empty($NbPoweredNumbers)) && ((integer)$NbPoweredNumbers < 0))
             {
                 // Wrong nb powered members
                 $ConfirmationSentence = $LANG_ERROR_WRONG_NB_POWERED_MEMBERS;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "Cr=".md5($FamilyID)."&Id=$FamilyID"; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = $QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the UpdateFamily.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = $QUERY_STRING; // For the redirection
 }

 if ($iNbMailsSent > 0)
 {
     // A notification is sent
     $ConfirmationSentence .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
 }

 //################################ END FORM PROCESSING ##########################
 $RedirectUrl = "UpdateFamily.php";
 if (in_array($cUserAccess, array(FCT_ACT_PARTIAL_READ_ONLY)))
 {
     $RedirectUrl = "FamilyDetails.php";
 }

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Canteen/$RedirectUrl?$UrlParameters', $CONF_TIME_LAG)"
                     );

 // Content of the web page
 openArea('id="content"');

 openFrame($ConfirmationCaption);
 displayStyledText($ConfirmationSentence, $ConfirmationStyle);
 closeFrame();

 // To measure the execution script time
 if ($CONF_DISPLAY_EXECUTION_TIME_SCRIPT)
 {
     openParagraph('InfoMsg');
     initEndTime();
     displayExecutionScriptTime('ExecutionTime');
     closeParagraph();
 }

 // Close the <div> "content"
 closeArea();

 closeGraphicInterface();
?>