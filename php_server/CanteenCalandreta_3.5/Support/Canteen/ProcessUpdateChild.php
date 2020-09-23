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
 * Support module : process the update of a child. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2014-08-06 : if desactivation date is set, no new entry in the history is added
 *     - 2015-01-16 : if desactivation date is set, delete canteen registrations after desactivation date
 *     - 2015-06-18 : taken into account "Firstname" and "without pork" fields can be updated with
 *                    FCT_ACT_PARTIAL_READ_ONLY access right, update snack planning and laundry planning
 *                    if the child is desactivated
 *     - 2015-10-06 : update canteen registrations with a date after the current date if "Without pork" field
 *                    is changed
 *     - 2016-10-28 : taken into account Bcc for notifications and load some configuration variables from database
 *     - 2017-09-21 : taken into account ChildEmail field and send notification if changed, Without pork becomes
 *                    meal type
 *
 * @since 2012-01-24
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted child ID
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

 //################################ FORM PROCESSING ##########################
 $iNbMailsSent = 0;
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

         // We identify the child
         if (isExistingChild($DbCon, $Id))
         {
             // The child exists
             $ChildID = $Id;
         }
         else
         {
             // ERROR : the child doesn't exist
             $ContinueProcess = FALSE;
         }

         // We get the ID of the family of the child
         $FamilyID = $_POST["hidFamilyID"];
         if ($FamilyID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $Firstname = NULL;
         if (isset($_POST["sFirstname"]))
         {
             $Firstname = trim(strip_tags($_POST["sFirstname"]));
             if (empty($Firstname))
             {
                 $ContinueProcess = FALSE;
             }
         }

         $Grade = NULL;
         if (isset($_POST["lGrade"]))
         {
             $Grade = strip_tags($_POST["lGrade"]);
         }

         $Class = NULL;
         if (isset($_POST["lClass"]))
         {
             $Class = strip_tags($_POST["lClass"]);
         }

         $MealType = NULL;
         if (isset($_POST["lMealType"]))
         {
             $MealType = strip_tags($_POST["lMealType"]);
         }

         // We have to convert the desactivation date in english format (format used in the database)
         $DesactivationDate = NULL;
         if (isset($_POST["desactivationDate"]))
         {
             $DesactivationDate = nullFormatText(formatedDate2EngDate($_POST["desactivationDate"]), "NULL");
         }

         $Email = existedPOSTFieldValue("sEmail", NULL);
         if (!is_Null($Email))
         {
             $Email = trim(strip_tags($Email));
         }

         if ((!empty($Email)) && (!isValideEmailAddress($Email)))
         {
             // Wrong e-mail
             $ContinueProcess = FALSE;
         }

         $RecordOldChild = getTableRecordInfos($DbCon, "Children", $ChildID);

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             $ChildID = dbUpdateChild($DbCon, $ChildID, NULL, $Firstname, $FamilyID, $Grade, $Class, $MealType,
                                      $DesactivationDate, $Email);
             if ($ChildID != 0)
             {
                 $RecordNewChild = getTableRecordInfos($DbCon, "Children", $ChildID);

                 // Update the history of the child (so, the last entry in the history of the child)
                 $SchoolYear = getSchoolYear(date('Y-m-d'));
                 $ArrayHistory = getHistoLevelsChild($DbCon, $ChildID, 'HistoLevelChildYear DESC');

                 if (is_null($Grade))
                 {
                     $Grade = $RecordOldChild['ChildGrade'];
                 }

                 if (is_null($Class))
                 {
                     $Class = $RecordOldChild['ChildClass'];
                 }

                 if ($ArrayHistory['HistoLevelChildYear'][0] == $SchoolYear)
                 {
                     dbUpdateHistoLevelChild($DbCon, $ArrayHistory['HistoLevelChildID'][0], $ChildID, $ArrayHistory['HistoLevelChildYear'][0], $Grade, $Class, $MealType);
                 }
                 elseif (($SchoolYear > $ArrayHistory['HistoLevelChildYear'][0]) && (is_null($DesactivationDate)))
                 {
                     // Entry addeed only if no desactivation date is set
                     dbAddHistoLevelChild($DbCon, $ChildID, $SchoolYear, $Grade, $Class, $MealType);
                 }

                 // Log event
                 logEvent($DbCon, EVT_FAMILY, EVT_SERV_CHILD, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $ChildID);

                 // We check if the child's e-mail has changed
                 $sChangedEmail = '';
                 if ($RecordOldChild['ChildEmail'] != $Email)
                 {
                     if (empty($RecordOldChild['ChildEmail']))
                     {
                         // E-mail added
                         $sChangedEmail .= "$Email $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_EMAIL_ADDED";
                     }
                     elseif (empty($Email))
                     {
                         // E-mail removed
                         $sChangedEmail .= $RecordOldChild['ChildEmail']
                                            ." $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_EMAIL_REMOVED";
                     }
                     else
                     {
                         // E-mail updated
                         $sChangedEmail .= $RecordOldChild['ChildEmail']
                                            ." $LANG_SYSTEM_EMAIL_FAMILY_EMAIL_UPDATED_EMAIL_REPLACED_BY $Email";
                     }
                 }

                 // If child's e-mail changed, we send a notification
                 if ((!empty($sChangedEmail)) && (isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Template]))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Template]))
                    )
                 {
                     // We have to send a notification
                     $EmailSubject = $LANG_SYSTEM_EMAIL_CHILD_EMAIL_UPDATED_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SYSTEM]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SYSTEM].$EmailSubject;
                     }

                     $Firstname = $RecordNewChild['ChildFirstname'];
                     $Lastname = getFamilyLastname($DbCon, $RecordNewChild['FamilyID']);

                     $ChildUrl = $CONF_URL_SUPPORT."Canteen/UpdateChild.php?Cr=".md5($ChildID)."&Id=$ChildID";
                     $ChildLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     // We define the content of the mail
                     $TemplateToUse = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{ChildFirstname}", "{FamilyLastname}", "{ChildChangedEmail}",
                                                      "{ChildUrl}", "{ChildLinkTip}"
                                                     ),
                                                array(
                                                      $Firstname, $Lastname, $sChangedEmail, $ChildUrl, $ChildLinkTip
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList = array();
                     if (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][To]))
                     {
                         $MailingList["to"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][To];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Cc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Cc]))
                        )
                     {
                         $MailingList["cc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Cc];
                     }

                     if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Bcc]))
                         && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Bcc]))
                        )
                     {
                         $MailingList["bcc"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['ChildEmailUpdated'][Bcc];
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

                 // If desactivation date set :
                 // 1) delete canteen registrations > desactivation date
                 // 2) check if the snack planning must be updated (because the family leaves the school)
                 // 3) check if the laundry planning must be updated (same reason)
                 if (!is_null($DesactivationDate))
                 {
                     $StartDate = getSchoolYearStartDate($SchoolYear);
                     $EndDate = getSchoolYearEndDate($SchoolYear);

                     //******** 1) Delete canteen registration > desactivation date ********
                     // Compute the next date to order canteen registrations
                     $NextDate = getNextWorkingDay($DbCon, date('Y-m-d', max(strtotime($DesactivationDate), strtotime("now"))));
                     $MaxDate = getCanteenRegistrationMaxDate($DbCon);
                     $ArrayCanteenRegistrations = getCanteenRegistrations($DbCon, $NextDate, $MaxDate, 'CanteenRegistrationForDate',
                                                                          $ChildID);

                     if ((isset($ArrayCanteenRegistrations['CanteenRegistrationID'])) && (!empty($ArrayCanteenRegistrations['CanteenRegistrationID'])))
                     {
                         foreach($ArrayCanteenRegistrations['CanteenRegistrationID'] as $cr => $CanteenRegistrationID)
                         {
                             // We create the record about the canteen registration to delete
                             $RecordCanteenRegistration = array(
                                                                'CanteenRegistrationID' => $CanteenRegistrationID,
                                                                'CanteenRegistrationDate' => $ArrayCanteenRegistrations['CanteenRegistrationDate'][$cr],
                                                                'CanteenRegistrationForDate' => $ArrayCanteenRegistrations['CanteenRegistrationForDate'][$cr],
                                                                'ChildID' => $ChildID,
                                                                'CanteenRegistrationChildGrade' => $ArrayCanteenRegistrations['ChildGrade'][$cr],
                                                                'CanteenRegistrationChildClass' => $ArrayCanteenRegistrations['ChildClass'][$cr],
                                                                'CanteenRegistrationWithoutPork' => $ArrayCanteenRegistrations['CanteenRegistrationWithoutPork'][$cr]
                                                               );

                             if (dbDeleteCanteenRegistration($DbCon, $CanteenRegistrationID))
                             {
                                 // Log event
                                 logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                          $CanteenRegistrationID, array('CanteenRegistrationDetails' => $RecordCanteenRegistration));
                             }
                         }
                     }

                     //******** 2) check if the snack planning must be updated (because the family leaves the school) ********
                     // First, we get snack registrations after the desactivation date
                     $ArrayFamilySnackRegistrations = getSnackRegistrations($DbCon, $DesactivationDate, $EndDate,
                                                                            'SnackRegistrationClass, SnackRegistrationDate', $FamilyID,
                                                                            PLANNING_BETWEEN_DATES,
                                                                            array('SnackRegistrationClass' => array($RecordOldChild['ChildClass'])));

                     if ((isset($ArrayFamilySnackRegistrations['SnackRegistrationID']))
                         && (count($ArrayFamilySnackRegistrations['SnackRegistrationID']) > 0))
                     {
                         // Yes, there are snack registrations for this family and for this classroom
                         // We check if the family has other activated children for this classroom and this school year
                         $ArrayChildren = dbSearchChild($DbCon, array('FamilyID' => $FamilyID, 'Activated' => TRUE,
                                                                      'SchoolYear' => array($SchoolYear),
                                                                      'ChildClass' => array($RecordOldChild['ChildClass'])),
                                                                      "ChildID", 1, 0);

                         if ((isset($ArrayChildren['ChildID'])) && (count($ArrayChildren['ChildID']) == 0))
                         {
                             // The family hasn't other child in this classroom for this school year : we have to update
                             // the snack planning !
                             // We count the number of snacks registrations for each family for the current school year and the
                             // concerned classroom : we keep families with low conters
                             $ArraySnackRegistrations = getSnackRegistrations($DbCon, $StartDate, $EndDate,
                                                                              'SnackRegistrationClass, SnackRegistrationDate', NULL,
                                                                              PLANNING_BETWEEN_DATES,
                                                                              array('SnackRegistrationClass' => array($RecordOldChild['ChildClass'])));

                             // We get too previous/next families in the snack registrations list to avoid a family brings the snack
                             // for 2 weeks
                             $ArrayFamiliesToAvoid = array();
                             $ArrayFamiliesSnackCounters = array();
                             $NbSnackRegistrations = count($ArraySnackRegistrations['SnackRegistrationID']);
                             foreach($ArraySnackRegistrations['SnackRegistrationID'] as $sr => $CurrentSnackID)
                             {
                                 if (isset($ArrayFamiliesSnackCounters[$ArraySnackRegistrations['FamilyID'][$sr]]))
                                 {
                                     $ArrayFamiliesSnackCounters[$ArraySnackRegistrations['FamilyID'][$sr]]++;
                                 }
                                 else
                                 {
                                     $ArrayFamiliesSnackCounters[$ArraySnackRegistrations['FamilyID'][$sr]] = 1;
                                 }

                                 if (in_array($CurrentSnackID, $ArrayFamilySnackRegistrations['SnackRegistrationID']))
                                 {
                                     // We get the previous and next family
                                     if ($sr > 0)
                                     {
                                         $ArrayFamiliesToAvoid[] = $ArraySnackRegistrations['FamilyID'][$sr - 1];
                                     }

                                     if ($sr < $NbSnackRegistrations - 2)
                                     {
                                         $ArrayFamiliesToAvoid[] = $ArraySnackRegistrations['FamilyID'][$sr + 1];
                                     }
                                 }
                             }

                             // We keep only families with 1 snack registration and not in the list of families to avoid
                             foreach($ArrayFamiliesSnackCounters as $CurrentFamilyID => $Nb)
                             {
                                 if (($Nb > 1) || (in_array($CurrentFamilyID, $ArrayFamiliesToAvoid)))
                                 {
                                     unset($ArrayFamiliesSnackCounters[$CurrentFamilyID]);
                                 }
                             }

                             $ArrayPossibleFamilies = array_keys($ArrayFamiliesSnackCounters);
                             shuffle($ArrayPossibleFamilies);

                             // We replace the family of the desactivated child by other possible families
                             $f = 0;
                             $ArraySelectedFamilies = array();
                             foreach($ArrayFamilySnackRegistrations['SnackRegistrationID'] as $sr => $CurrentSnackID)
                             {
                                 // We check if we can do the change of family
                                 if (isset($ArrayPossibleFamilies[$f]))
                                 {
                                     if (dbUpdateSnackRegistration($DbCon, $CurrentSnackID,
                                                                   $ArrayFamilySnackRegistrations['SnackRegistrationDate'][$sr],
                                                                   $ArrayPossibleFamilies[$f],
                                                                   $ArrayFamilySnackRegistrations['SnackRegistrationClass'][$sr]))
                                     {
                                         $ArraySelectedFamilies[$ArrayPossibleFamilies[$f]] = array($ArrayFamilySnackRegistrations['SnackRegistrationDate'][$sr],
                                                                                                    $ArrayFamilySnackRegistrations['SnackRegistrationClass'][$sr]);

                                         // Log event
                                         logEvent($DbCon, EVT_SNACK, EVT_SERV_PLANNING, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                                                  $CurrentSnackID, array('ChildDetails' => $RecordNewChild));
                                     }

                                     $f++;
                                 }
                             }

                             // We send an e-mail to selected families fo bring the snack instead of the family of the desactivated child
                             $NotificationType = 'UpdatedSnackPlanning';
                             if ((count($ArraySelectedFamilies) > 0)
                                 && (isset($CONF_SNACK_NOTIFICATIONS[$NotificationType][Template]))
                                 && (!empty($CONF_SNACK_NOTIFICATIONS[$NotificationType][Template]))
                                )
                             {
                                 $EmailSubject = $LANG_SNACK_PLANNING_UPDATED_EMAIL_SUBJECT;

                                 if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SNACK_PLANNING]))
                                 {
                                     $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_SNACK_PLANNING].$EmailSubject;
                                 }

                                 $SnackUrl = $CONF_URL_SUPPORT."Canteen/SnackPlanning.php?lYear=$SchoolYear";
                                 $SnackLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                                 // We define the content of the mail
                                 $TemplateToUse = $CONF_SNACK_NOTIFICATIONS[$NotificationType][Template];

                                 foreach($ArraySelectedFamilies as $CurrentFamilyID => $ArrayData)
                                 {
                                     $ReplaceInTemplate = array(
                                                                array(
                                                                      "{SnackUrl}", "{SnackLinkTip}", "{SnackRegistrationClass}",
                                                                      "{SnackRegistrationDate}"
                                                                     ),
                                                                array(
                                                                      $SnackUrl, $SnackLinkTip, $CONF_CLASSROOMS[$SchoolYear][$ArrayData[1]],
                                                                      date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ArrayData[0]))
                                                                     )
                                                               );

                                     // We send the notification to concerned family : we get e-mails of family
                                     $MailingList = array();
                                     $RecordFamily = getTableRecordInfos($DbCon, "Families", $CurrentFamilyID);
                                     $MailingList["to"][] = $RecordFamily['FamilyMainEmail'];
                                     if (!empty($RecordFamily['FamilySecondEmail']))
                                     {
                                         $MailingList["to"][] = $RecordFamily['FamilySecondEmail'];
                                     }

                                     if ((isset($CONF_SNACK_NOTIFICATIONS[$NotificationType][Cc]))
                                         && (!empty($CONF_SNACK_NOTIFICATIONS[$NotificationType][Cc])))
                                     {
                                         $MailingList["cc"] = $CONF_SNACK_NOTIFICATIONS[$NotificationType][Cc];
                                     }

                                     if ((isset($CONF_SNACK_NOTIFICATIONS[$NotificationType][Bcc]))
                                         && (!empty($CONF_SNACK_NOTIFICATIONS[$NotificationType][Bcc])))
                                     {
                                         $MailingList["bcc"] = $CONF_SNACK_NOTIFICATIONS[$NotificationType][Bcc];
                                     }

                                     // DEBUG MODE
                                     if ($GLOBALS["CONF_MODE_DEBUG"])
                                     {
                                         if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                                         {
                                             // Without this test, there is a server mail error...
                                             $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS),
                                                                              $MailingList["to"]);
                                         }
                                     }

                                     // We send the e-mail
                                     $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                                     if ($bIsEmailSent)
                                     {
                                         $iNbMailsSent++;
                                     }
                                 }
                             }
                         }
                     }

                     unset($ArrayFamilySnackRegistrations, $ArrayFamiliesSnackCounters, $ArrayFamiliesToAvoid, $ArrayPossibleFamilies,
                           $ArraySelectedFamilies);

                     //******** 3) check if the laundry planning must be updated (because the family leaves the school) ********
                     // First, we get laundry registrations after the desactivation date
                     $ArrayFamilyLaundryRegistrations = getLaundryRegistrations($DbCon, $DesactivationDate, $EndDate,
                                                                                'LaundryRegistrationDate', $FamilyID,
                                                                                PLANNING_BETWEEN_DATES);

                     if ((isset($ArrayFamilyLaundryRegistrations['LaundryRegistrationID']))
                         && (count($ArrayFamilyLaundryRegistrations['LaundryRegistrationID']) > 0))
                     {
                         // Yes, there are laundry registrations for this family
                         // We check if the family has other activated children for this school year
                         $ArrayChildren = dbSearchChild($DbCon, array('FamilyID' => $FamilyID, 'Activated' => TRUE,
                                                                      'SchoolYear' => array($SchoolYear)), "ChildID", 1, 0);

                         if ((isset($ArrayChildren['ChildID'])) && (count($ArrayChildren['ChildID']) == 0))
                         {
                             // The family hasn't other child for this school year : we have to update the laundry planning !
                             // We count the number of laundry registrations for each family for the current school year :
                             // we keep families with low conters
                             $ArrayLaundryRegistrations = getLaundryRegistrations($DbCon, $StartDate, $EndDate,
                                                                                  'LaundryRegistrationDate', NULL,
                                                                                  PLANNING_BETWEEN_DATES);

                             // We get too previous/next families in the laundry registrations list to avoid a family wash laundry
                             // for 2 weeks
                             $ArrayFamiliesToAvoid = array();
                             $ArrayFamiliesLaundryCounters = array();
                             $NbLaundryRegistrations = count($ArrayLaundryRegistrations['LaundryRegistrationID']);
                             foreach($ArrayLaundryRegistrations['LaundryRegistrationID'] as $lr => $CurrentLaundryID)
                             {
                                 if (isset($ArrayFamiliesLaundryCounters[$ArrayLaundryRegistrations['FamilyID'][$lr]]))
                                 {
                                     $ArrayFamiliesLaundryCounters[$ArrayLaundryRegistrations['FamilyID'][$lr]]++;
                                 }
                                 else
                                 {
                                     $ArrayFamiliesLaundryCounters[$ArrayLaundryRegistrations['FamilyID'][$lr]] = 1;
                                 }

                                 if (in_array($CurrentLaundryID, $ArrayFamilyLaundryRegistrations['LaundryRegistrationID']))
                                 {
                                     // We get the previous and next family
                                     if ($lr > 0)
                                     {
                                         $ArrayFamiliesToAvoid[] = $ArrayLaundryRegistrations['FamilyID'][$lr - 1];
                                     }

                                     if ($lr < $NbLaundryRegistrations - 2)
                                     {
                                         $ArrayFamiliesToAvoid[] = $ArrayLaundryRegistrations['FamilyID'][$lr + 1];
                                     }
                                 }
                             }

                             // We keep only families with 1 laundry registration and not in the list of families to avoid
                             foreach($ArrayFamiliesLaundryCounters as $CurrentFamilyID => $Nb)
                             {
                                 if (($Nb > 1) || (in_array($CurrentFamilyID, $ArrayFamiliesToAvoid)))
                                 {
                                     unset($ArrayFamiliesLaundryCounters[$CurrentFamilyID]);
                                 }
                             }

                             $ArrayPossibleFamilies = array_keys($ArrayFamiliesLaundryCounters);
                             shuffle($ArrayPossibleFamilies);

                             // We replace the family of the desactivated child by other possible families
                             $f = 0;
                             $ArraySelectedFamilies = array();
                             foreach($ArrayFamilyLaundryRegistrations['LaundryRegistrationID'] as $lr => $CurrentLaundryID)
                             {
                                 // We check if we can do the change of family
                                 if (isset($ArrayPossibleFamilies[$f]))
                                 {
                                     if (dbUpdateLaundryRegistration($DbCon, $CurrentLaundryID,
                                                                     $ArrayFamilyLaundryRegistrations['LaundryRegistrationDate'][$lr],
                                                                     $ArrayPossibleFamilies[$f]))
                                     {
                                         $ArraySelectedFamilies[$ArrayPossibleFamilies[$f]] = array($ArrayFamilyLaundryRegistrations['LaundryRegistrationDate'][$lr],
                                                                                                    $CurrentLaundryID);

                                         // Log event
                                         logEvent($DbCon, EVT_LAUNDRY, EVT_SERV_PLANNING, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                                                  $CurrentLaundryID, array('ChildDetails' => $RecordNewChild));
                                     }

                                     $f++;
                                 }
                             }

                             // We send an e-mail to selected families to wash laundry instead of the family of the desactivated child
                             $NotificationType = 'UpdatedLaundryPlanning';
                             if ((count($ArraySelectedFamilies) > 0)
                                 && (isset($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Template]))
                                 && (!empty($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Template]))
                                )
                             {
                                 $EmailSubject = $LANG_LAUNDRY_PLANNING_UPDATED_EMAIL_SUBJECT;

                                 if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_LAUNDRY_PLANNING]))
                                 {
                                     $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_LAUNDRY_PLANNING].$EmailSubject;
                                 }

                                 $LaundryUrl = $CONF_URL_SUPPORT."Canteen/LaundryPlanning.php?lYear=$SchoolYear";
                                 $LaundryLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                                 // We define the content of the mail
                                 $TemplateToUse = $CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Template];

                                 foreach($ArraySelectedFamilies as $CurrentFamilyID => $ArrayData)
                                 {
                                     $ReplaceInTemplate = array(
                                                                array(
                                                                      "{LaundryUrl}", "{LaundryLinkTip}", "{LaundryRegistrationDate}"
                                                                     ),
                                                                array(
                                                                      $LaundryUrl, $LaundryLinkTip,
                                                                      date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ArrayData[0]))
                                                                     )
                                                               );

                                     // We send the notification to concerned family : we get e-mails of family
                                     $MailingList = array();
                                     $RecordFamily = getTableRecordInfos($DbCon, "Families", $CurrentFamilyID);
                                     $MailingList["to"][] = $RecordFamily['FamilyMainEmail'];
                                     if (!empty($RecordFamily['FamilySecondEmail']))
                                     {
                                         $MailingList["to"][] = $RecordFamily['FamilySecondEmail'];
                                     }

                                     if ((isset($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Cc]))
                                         && (!empty($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Cc])))
                                     {
                                         $MailingList["cc"] = $CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Cc];
                                     }

                                     if ((isset($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Bcc]))
                                         && (!empty($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Bcc])))
                                     {
                                         $MailingList["bcc"] = $CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Bcc];
                                     }

                                     // DEBUG MODE
                                     if ($GLOBALS["CONF_MODE_DEBUG"])
                                     {
                                         if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                                         {
                                             // Without this test, there is a server mail error...
                                             $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS),
                                                                              $MailingList["to"]);
                                         }
                                     }

                                     // We send the e-mail
                                     $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                                     if ($bIsEmailSent)
                                     {
                                         $iNbMailsSent++;
                                     }
                                 }
                             }
                         }
                     }

                     unset($ArrayFamilyLaundryRegistrations, $ArrayFamiliesLaundryCounters, $ArrayFamiliesToAvoid, $ArrayPossibleFamilies,
                           $ArraySelectedFamilies);
                 }

                 // Check if the "Without pork" data is changed : if yes, we update canteen registrations after the current date
                 if (($MealType != $RecordOldChild['ChildWithoutPork']) && (is_null($DesactivationDate)))
                 {
                     // Compute the next date to order canteen registrations
                     $NextDate = getNextWorkingDay($DbCon, date('Y-m-d', strtotime("now")));

                     $DbCon->query("UPDATE CanteenRegistrations SET CanteenRegistrationWithoutPork = $MealType
                                    WHERE CanteenRegistrationForDate >= \"$NextDate\" AND ChildID = $ChildID");
                 }

                 // The child is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_CHILD_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($ChildID)."&Id=$ChildID"; // For the redirection
             }
             else
             {
                 // The child can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_CHILD;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if ($Firstname === '')
             {
                 // The firstname is empty
                 $ConfirmationSentence = $LANG_ERROR_CHILD_FIRSTNAME;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = $QUERY_STRING; // For the redirection
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
     // The supporter doesn't come from the UpdateChild.php page
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

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Canteen/UpdateChild.php?$UrlParameters', $CONF_TIME_LAG)"
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