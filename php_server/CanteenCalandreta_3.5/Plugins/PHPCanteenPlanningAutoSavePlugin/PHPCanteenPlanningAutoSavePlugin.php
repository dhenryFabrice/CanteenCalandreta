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
 * PHP plugin planning canteen auto save module : when the user check/uncheck a checkbox
 * in the planning, the canteen registration is auto save/deleted in the database
 *
 * @author Christophe Javouhey
 * @version 3.5
 *     - 2013-12-02 : taken into account the new way to display the canteen planning (without hidden input fields)
 *     - 2014-01-02 : taken into account english language
 *     - 2014-01-09 : for the "delete" action, we add some "if" to check the content of variables
 *                    (must match with content database)
 *     - 2014-03-31 : taken into account Occitan language
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2020-02-26 : taken into account other timeslots of nursery registrations
 *
 * @since 2013-09-09
 */


 // Include Config.php because of the name of the session
 require '../../GUI/GraphicInterface.php';

 switch($CONF_LANG)
 {
     case 'fr':
         include_once('./Languages/PHPCanteenPlanningAutoSaveFrancais.lang.php');
         break;

     case 'oc':
         include_once('./Languages/PHPCanteenPlanningAutoSaveOccitan.lang.php');
         break;

     default:
         include_once('./Languages/PHPCanteenPlanningAutoSaveEnglish.lang.php');
         break;
 }

 session_start();

 $XmlData = '';
 $sAction = '';
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // We get the parameters
     if (array_key_exists('getDateOfWeek', $_GET))
     {
         $ArrayData = explode('|', strip_tags(trim($_GET['getDateOfWeek'])));
         if (count($ArrayData) == 3)
         {
             $Date = getDateOfYearWeekNumDay($ArrayData[1], $ArrayData[0], $ArrayData[2]);
             if (!empty($Date))
             {
                 $XmlData = xmlOpenDocument();
                 $XmlData .= xmlTag("Date", "", array('value' => $Date));
                 $XmlData .= xmlCloseDocument();
             }
         }
     }
     elseif ((array_key_exists('Action', $_GET)) && (array_key_exists('Param', $_GET)))
     {
         $sAction = strToLower(strip_tags(trim($_GET['Action'])));
         $sParam = strip_tags(trim($_GET['Param']));
         if ((!empty($sAction)) && (!empty($sParam)))
         {
             $DbCon = dbConnection();

             // Load all configuration variables from database
             loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                                  'CONF_CLASSROOMS',
                                                  'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                                  'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                                  'CONF_CANTEEN_PRICES',
                                                  'CONF_NURSERY_OTHER_TIMESLOTS',
                                                  'CONF_NURSERY_PRICES',
                                                  'CONF_NURSERY_DELAYS_PRICES'));

             $ArrayData = explode('|', $sParam);
             if (count($ArrayData) == 4)
             {
                 $Date = $ArrayData[0];
                 $Class = $ArrayData[1];
                 $ChildID = $ArrayData[2];
                 $CanteenRegistrationID = $ArrayData[3];

                 $AdminDate = NULL;
                 $bCanUpdateCanteenPlanning = TRUE;
                 $bCanteenPlanningUpdated = FALSE;

                 // We check if there are some other timeslots for the concerned school year
                 $CurrentSchoolYear = getSchoolYear($Date);
                 $iNbOtherTimeslots = 0;
                 $ArrayLinkedToCanteenPlanning = array();
                 if ((isset($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])) && (!empty($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])))
                 {
                     $iNbOtherTimeslots = count($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear]);

                     // We check if some other timeslots are linked to the canteen planning
                     foreach($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                     {
                         if ((isset($CurrentParamsOtherTimeslot['LinkedToCanteen'])) && ($CurrentParamsOtherTimeslot['LinkedToCanteen'] == 1))
                         {
                             $ArrayLinkedToCanteenPlanning[] = $ots;
                         }
                     }
                 }

                 // For delays
                 $TodayDateStamp = strtotime(date('Y-m-d'));

                 // The supporter must be allowed to create or update a nursery registration
                 $AccessRules = $CONF_ACCESS_APPL_PAGES[FCT_NURSERY_PLANNING];
                 $cUserAccess = FCT_ACT_NO_RIGHTS;
                 if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
                 {
                     // Creation mode
                     $cUserAccess = FCT_ACT_CREATE;
                 }
                 elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
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
                     // Read mode
                     $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
                 }

                 switch($sAction)
                 {
                     case 'delete':
                         // We delete the canteen registration of the child for the date
                         if (!empty($CanteenRegistrationID))
                         {
                             $RecordCanteenRegistration = getTableRecordInfos($DbCon, "CanteenRegistrations", $CanteenRegistrationID);
                             if (!empty($RecordCanteenRegistration))
                             {
                                 // We check if the sent parameters of the canteen registration match with values in the database
                                 // (same ID but same child, same date... ?)
                                 if (($Date == $RecordCanteenRegistration['CanteenRegistrationForDate'])
                                     && ($ChildID == $RecordCanteenRegistration['ChildID']))
                                 {
                                     // Parameters match : we can delete the canteen registration
                                     //**** IMPORTANT : no control about nursery delay to delete a canteen registration ! ****//
                                     if (dbDeleteCanteenRegistration($DbCon, $CanteenRegistrationID))
                                     {
                                         // Success
                                         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                         $iTypeMsg = 1;
                                         $ID = 0;
                                         $bCanteenPlanningUpdated = TRUE;

                                         // Log event
                                         logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                                  $CanteenRegistrationID, array('CanteenRegistrationDetails' => $RecordCanteenRegistration));

                                         // We search in which group the child is (in relation with his grade)
                                         $ArrayGroups = array_keys($CONF_GRADES_GROUPS);
                                         $iPosGroup = -1;
                                         foreach($ArrayGroups as $g => $Group)
                                         {
                                             if (in_array($RecordCanteenRegistration['CanteenRegistrationChildGrade'], $CONF_GRADES_GROUPS[$Group]))
                                             {
                                                 // Grade found in the group
                                                 $iPosGroup = $g + 1;

                                                 // Stop the search
                                                 break;
                                             }
                                         }

                                         $XmlData = xmlOpenDocument();
                                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID,
                                                                                    'group' => $iPosGroup,
                                                                                    'withoutpork' => $RecordCanteenRegistration['CanteenRegistrationWithoutPork'],
                                                                                    'action' => $sAction));
                                         $XmlData .= xmlCloseDocument();

                                         $CanteenRegistrationID = 0;
                                     }
                                 }
                                 else
                                 {
                                     // Parameters don't match
                                     $sMsg = '-';  // In order not to display msg error
                                     $iTypeMsg = 0;
                                     $ID = 0;
                                 }
                             }
                         }
                         break;

                     case 'register':
                         // We register to the canteen planning the child for the date
                         // First, we get info about the child
                         $RecordChild = getTableRecordInfos($DbCon, 'Children', $ChildID);
                         if (!empty($RecordChild))
                         {
                             // Check if the canteen registration already exists
                             if ((empty($CanteenRegistrationID)) || (!isExistingCanteenRegistration($DbCon, $CanteenRegistrationID)))
                             {
                                 // We check if the logged supporter is a parent of the child or he is an admin
                                 $FamilyLastname = getTableFieldValue($DbCon, 'Families', $RecordChild['FamilyID'], 'FamilyLastname');
                                 if ($RecordChild['FamilyID'] != $_SESSION['FamilyID'])
                                 {
                                     // The logged supporter is an admin or a user with a special access and
                                     // allowed to modify childern canteen registrations
                                     $AdminDate = date('Y-m-d');
                                 }

                                 // We check if there is a link with the canteen planning for this other timeslot
                                 if (!empty($ArrayLinkedToCanteenPlanning))
                                 {
                                     // Yes, there is a link : we check if the user is concerned by the delay
                                     if (in_array($_SESSION['SupportMemberStateID'], $CONF_NURSERY_DELAYS_RESTRICTIONS))
                                     {
                                         switch($cUserAccess)
                                         {
                                             case FCT_ACT_CREATE:
                                             case FCT_ACT_UPDATE:
                                                 // The supporter can't registre a child after x days
                                                 $MinEditDateStamp = strtotime(date('Y-m-d',
                                                                                    strtotime($CONF_NURSERY_UPDATE_DELAY_PLANNING_REGISTRATION." days ago")));
                                                 $LimitEditDateStamp = $TodayDateStamp;  // The limit to edit the planning is today (not after)
                                                 if ((strtotime($Date) < $MinEditDateStamp) || (strtotime($Date) > $LimitEditDateStamp))
                                                 {
                                                     $bCanUpdateCanteenPlanning = FALSE;
                                                 }
                                                 break;

                                             case FCT_ACT_PARTIAL_READ_ONLY:
                                                 $MinEditDateStamp = $TodayDateStamp;  // The limit to edit the planning is today and after (but not past)
                                                 $LimitEditDateStamp = strtotime(date('Y-m-d',
                                                                                      strtotime('+'.$CONF_NURSERY_REGISTER_DELAY_PLANNING_REGISTRATION." days")));

                                                 if ($CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION > 0)
                                                 {
                                                     // We add a delay before the planning can be edited
                                                     $MinEditDateStamp = strtotime(date('Y-m-d',
                                                                                        strtotime('+'.$CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION." days")));
                                                 }

                                                 if ((strtotime($Date) <= $MinEditDateStamp) || (strtotime($Date) > $LimitEditDateStamp))
                                                 {
                                                     $bCanUpdateCanteenPlanning = FALSE;
                                                 }
                                                 break;

                                             case FCT_ACT_READ_ONLY:
                                                 $bCanUpdateCanteenPlanning = FALSE;
                                                 break;
                                         }
                                     }
                                 }

                                 if ($bCanUpdateCanteenPlanning)
                                 {
                                     $ID = dbAddCanteenRegistration($DbCon, date('Y-m-d'), $Date, $ChildID, $RecordChild['ChildGrade'],
                                                                    $RecordChild['ChildClass'], $RecordChild['ChildWithoutPork'], 1,
                                                                    $AdminDate);
                                     if ($ID > 0)
                                     {
                                         // Success
                                         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                         $iTypeMsg = 1;
                                         $CanteenRegistrationID = $ID;
                                         $bCanteenPlanningUpdated = TRUE;

                                         // Log event
                                         logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $ID);
                                     }
                                     else
                                     {
                                         // Error
                                         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                                         $iTypeMsg = 0;
                                         $ID = 0;
                                     }
                                 }
                                 else
                                 {
                                     // Error of nursery delay
                                     $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_NURSERY_DELAY;
                                     $iTypeMsg = 0;
                                     $ID = 0;
                                 }
                             }
                             else
                             {
                                 // Do nothing
                                 $sMsg = "-";
                                 $iTypeMsg = 1;
                                 $ID = $CanteenRegistrationID;
                             }
                         }
                         else
                         {
                             // Error
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                             $iTypeMsg = 0;
                             $ID = 0;
                         }

                         // We search in which group the child is (in relation with his grade)
                         $ArrayGroups = array_keys($CONF_GRADES_GROUPS);
                         $iPosGroup = -1;
                         foreach($ArrayGroups as $g => $Group)
                         {
                            if (in_array($RecordChild['ChildGrade'], $CONF_GRADES_GROUPS[$Group]))
                            {
                                // Grade found in the group
                                $iPosGroup = $g + 1;

                                // Stop the search
                                break;
                            }
                         }

                         $XmlData = xmlOpenDocument();
                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'group' => $iPosGroup,
                                                                    'withoutpork' => $RecordChild['ChildWithoutPork'],
                                                                    'action' => $sAction));
                         $XmlData .= xmlCloseDocument();

                         unset($RecordChild);
                         break;
                 }

                 // Now we check if there are some other nursery timeslots linked to the canteen planning and if we must update the nursery planning
                 if ((!empty($ArrayLinkedToCanteenPlanning)) && ($bCanteenPlanningUpdated))
                 {
                     // Get infos about the canteen registration
                     if ((isset($CanteenRegistrationID)) && ($CanteenRegistrationID > 0))
                     {
                         $RecordCanteenRegistration = getTableRecordInfos($DbCon, 'CanteenRegistrations', $CanteenRegistrationID);
                     }

                     if (!empty($RecordCanteenRegistration))
                     {
                         // We create other nursery timeslots if necessary. First, we get the nursery registration for this date
                         $ArrayNurseryRegistrations = getNurseryRegistrations($DbCon, $RecordCanteenRegistration['CanteenRegistrationForDate'],
                                                                              $RecordCanteenRegistration['CanteenRegistrationForDate'], 'NurseryRegistrationForDate',
                                                                              $RecordCanteenRegistration['ChildID']);

                         if ((isset($ArrayNurseryRegistrations['NurseryRegistrationID'])) && (!empty($ArrayNurseryRegistrations['NurseryRegistrationID'])))
                         {
                             // There is an existing nursery registration for this date and this chid
                             $bCanDelete = FALSE;
                             $iValueToUpdate = 0;

                             if ($CanteenRegistrationID == 0)
                             {
                                 // We update the existing nursery registration removing concerned other nursery timeslots
                                 // If necessary, we delete the nursery registration
                                 if ((empty($ArrayNurseryRegistrations['NurseryRegistrationForAM'][0]))
                                     && (empty($ArrayNurseryRegistrations['NurseryRegistrationForPM'][0]))
                                     && (empty($ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0])))
                                 {
                                     $bCanDelete = TRUE;
                                 }
                                 else
                                 {
                                     // We have to uncheck concerned other nursery timeslots
                                     foreach($ArrayLinkedToCanteenPlanning as $t => $ots)
                                     {
                                         if ($ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0] & pow(2, $t))
                                         {
                                             $iValueToUpdate -= pow(2, $t);
                                         }
                                     }
                                 }
                             }
                             else
                             {
                                 // We check if concerned other timeslots are already checked
                                 foreach($ArrayLinkedToCanteenPlanning as $t => $ots)
                                 {
                                     if (($ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0] & pow(2, $t)) == 0)
                                     {
                                         $iValueToUpdate += pow(2, $t);
                                     }
                                 }
                             }

                             if ($bCanDelete)
                             {
                                 // We delete the nursery registration
                                 $RecordNurseryRegistration = getTableRecordInfos($DbCon, "NurseryRegistrations", $ArrayNurseryRegistrations['NurseryRegistrationID'][0]);
                                 if (dbDeleteNurseryRegistration($DbCon, $ArrayNurseryRegistrations['NurseryRegistrationID'][0]))
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                              $ArrayNurseryRegistrations['NurseryRegistrationID'][0], array('NurseryRegistrationDetails' => $RecordNurseryRegistration));
                                 }
                             }
                             else
                             {
                                 // We update the existing nursery registration with concerned other nursery timeslots
                                 if ($iValueToUpdate != 0)
                                 {
                                     // We must update the nursery registration
                                     $NurseryRegistrationOtherTimeslots = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0] + $iValueToUpdate;

                                     $NurseryRegistrationID = dbUpdateNurseryRegistration($DbCon, $ArrayNurseryRegistrations['NurseryRegistrationID'][0],
                                                                                          NULL, $ArrayNurseryRegistrations['NurseryRegistrationForDate'][0],
                                                                                          $ArrayNurseryRegistrations['ChildID'][0], $_SESSION['SupportMemberID'],
                                                                                          NULL, NULL, $ArrayNurseryRegistrations['ChildGrade'][0],
                                                                                          $ArrayNurseryRegistrations['ChildClass'][0],
                                                                                          $AdminDate, NULL, $NurseryRegistrationOtherTimeslots);

                                     if ($NurseryRegistrationID > 0)
                                     {
                                         // Log event
                                         logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $NurseryRegistrationID);
                                     }
                                 }
                             }
                         }
                         else
                         {
                             // No existing nursery registration for this date and this child
                             if ($CanteenRegistrationID > 0)
                             {
                                 // A canteen registration hasbeen added : we create a nursery registration
                                 $NurseryRegistrationOtherTimeslots = 0;
                                 foreach($ArrayLinkedToCanteenPlanning as $t => $ots)
                                 {
                                     $NurseryRegistrationOtherTimeslots += pow(2, $t);
                                 }

                                 $NurseryRegistrationID = dbAddNurseryRegistration($DbCon, date('Y-m-d'), $RecordCanteenRegistration['CanteenRegistrationForDate'],
                                                                                   $RecordCanteenRegistration['ChildID'], $_SESSION['SupportMemberID'], 0, 0,
                                                                                   $RecordCanteenRegistration['CanteenRegistrationChildGrade'],
                                                                                   $RecordCanteenRegistration['CanteenRegistrationChildClass'], $AdminDate, 0,
                                                                                   $NurseryRegistrationOtherTimeslots);

                                 if ($NurseryRegistrationID > 0)
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $NurseryRegistrationID);
                                 }
                             }
                         }
                     }
                 }
             }

             // Release the connection to the database
             dbDisconnection($DbCon);
         }
     }
     elseif ((array_key_exists('UpdateMoreMealsWithPork', $_GET)) && (array_key_exists('Param', $_GET)))
     {
         // Update the quantity of more meals with pork
         $iQuantity = strip_tags(trim($_GET['UpdateMoreMealsWithPork']));
         $sParam = strip_tags(trim($_GET['Param']));
         if (($iQuantity >= 0) && (!empty($sParam)))
         {
             $DbCon = dbConnection();

             // Load all configuration variables from database
             loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                                  'CONF_CLASSROOMS',
                                                  'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                                  'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                                  'CONF_CANTEEN_PRICES',
                                                  'CONF_NURSERY_PRICES',
                                                  'CONF_NURSERY_DELAYS_PRICES'));

             $ArrayTmpID = explode('_', $sParam);
             if (count($ArrayTmpID) == 2)
             {
                 $MoreMealForDate = $ArrayTmpID[0];
                 $MoreMealID = $ArrayTmpID[1];
             }

             unset($ArrayTmpID);

             $iOldQuantity = 0;
             if (empty($MoreMealID))
             {
                 // New entry
                 if (!empty($MoreMealForDate))
                 {
                     $ID = dbAddMoreMeal($DbCon, date('Y-m-d'), $MoreMealForDate, $_SESSION['SupportMemberID'], $iQuantity, 0);
                     if ($ID > 0)
                     {
                         // Success
                         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                         $iTypeMsg = 1;
                     }
                     else
                     {
                         // Error
                         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                         $iTypeMsg = 0;
                         $ID = 0;
                     }
                 }
                 else
                 {
                     // Error
                     $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                     $iTypeMsg = 0;
                     $ID = 0;
                 }
             }
             else
             {
                 // Get infos about more meals of the date
                 $RecordMoreMeal = getTableRecordInfos($DbCon, 'MoreMeals', $MoreMealID);
                 if (!empty($RecordMoreMeal))
                 {
                     $iOldQuantity = $RecordMoreMeal['MoreMealQuantity'];
                     if ($RecordMoreMeal['MoreMealWithoutPorkQuantity'] + $iQuantity > 0)
                     {
                         // Update the "more meal" entry
                         $ID = dbUpdateMoreMeal($DbCon, $MoreMealID, NULL, $MoreMealForDate, $_SESSION['SupportMemberID'], $iQuantity,
                                                NULL);
                         if ($ID > 0)
                         {
                             // Success
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                             $iTypeMsg = 1;
                         }
                         else
                         {
                             // Error
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                             $iTypeMsg = 0;
                             $ID = $MoreMealID;
                         }
                     }
                     else
                     {
                         // Delete the "more meal" entry
                         if (dbDeleteMoreMeal($DbCon, $MoreMealID))
                         {
                             // Success
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                             $iTypeMsg = 1;
                             $ID = 0;
                         }
                         else
                         {
                             // Error
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                             $iTypeMsg = 0;
                             $ID = $MoreMealID;
                         }
                     }
                 }
                 else
                 {
                     // Error
                     $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                     $iTypeMsg = 0;
                     $ID = $MoreMealID;
                 }
             }

             $XmlData = xmlOpenDocument();
             $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'group' => count(array_keys($CONF_GRADES_GROUPS)),
                                                        'withoutpork' => 0, 'oldquantity' => $iOldQuantity));
             $XmlData .= xmlCloseDocument();

             // Release the connection to the database
             dbDisconnection($DbCon);
         }
     }
     elseif ((array_key_exists('UpdateMoreMealsWithoutPork', $_GET)) && (array_key_exists('Param', $_GET)))
     {
         $iQuantity = strip_tags(trim($_GET['UpdateMoreMealsWithoutPork']));
         $sParam = strip_tags(trim($_GET['Param']));
         if (($iQuantity >= 0) && (!empty($sParam)))
         {
             $DbCon = dbConnection();

             // Load all configuration variables from database
             loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                                  'CONF_CLASSROOMS',
                                                  'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                                  'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                                  'CONF_CANTEEN_PRICES',
                                                  'CONF_NURSERY_PRICES',
                                                  'CONF_NURSERY_DELAYS_PRICES'));

             $ArrayTmpID = explode('_', $sParam);
             if (count($ArrayTmpID) == 2)
             {
                 $MoreMealForDate = $ArrayTmpID[0];
                 $MoreMealID = $ArrayTmpID[1];
             }

             unset($ArrayTmpID);

             $iOldQuantity = 0;
             if (empty($MoreMealID))
             {
                 // New entry
                 if (!empty($MoreMealForDate))
                 {
                     $ID = dbAddMoreMeal($DbCon, date('Y-m-d'), $MoreMealForDate, $_SESSION['SupportMemberID'], 0, $iQuantity);
                     if ($ID > 0)
                     {
                         // Success
                         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                         $iTypeMsg = 1;
                     }
                     else
                     {
                         // Error
                         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                         $iTypeMsg = 0;
                         $ID = 0;
                     }
                 }
                 else
                 {
                     // Error
                     $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                     $iTypeMsg = 0;
                     $ID = 0;
                 }
             }
             else
             {
                 // Get infos about more meals of the date
                 $RecordMoreMeal = getTableRecordInfos($DbCon, 'MoreMeals', $MoreMealID);
                 if (!empty($RecordMoreMeal))
                 {
                     $iOldQuantity = $RecordMoreMeal['MoreMealWithoutPorkQuantity'];
                     if ($RecordMoreMeal['MoreMealQuantity'] + $iQuantity > 0)
                     {
                         // Update the "more meal" entry
                         $ID = dbUpdateMoreMeal($DbCon, $MoreMealID, NULL, $MoreMealForDate, $_SESSION['SupportMemberID'], NULL,
                                                $iQuantity);
                         if ($ID > 0)
                         {
                             // Success
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                             $iTypeMsg = 1;
                         }
                         else
                         {
                             // Error
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                             $iTypeMsg = 0;
                             $ID = $MoreMealID;
                         }
                     }
                     else
                     {
                         // Delete the "more meal" entry
                         if (dbDeleteMoreMeal($DbCon, $MoreMealID))
                         {
                             // Success
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                             $iTypeMsg = 1;
                             $ID = 0;
                         }
                         else
                         {
                             // Error
                             $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                             $iTypeMsg = 0;
                             $ID = $MoreMealID;
                         }
                     }
                 }
                 else
                 {
                     // Error
                     $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                     $iTypeMsg = 0;
                     $ID = $MoreMealID;
                 }
             }

             $XmlData = xmlOpenDocument();
             $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'group' => 0, 'withoutpork' => 1,
                                                        'oldquantity' => $iOldQuantity));
             $XmlData .= xmlCloseDocument();

             // Release the connection to the database
             dbDisconnection($DbCon);
         }
     }
 }

 if (empty($XmlData))
 {
     // Error
     $XmlData = xmlOpenDocument();

     if (empty($sMsg))
     {
         $sMsg = $LANG_SUPPORT_PLANNING_CANTEEN_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
     }

     $XmlData .= xmlTag("Message", $sMsg, array('type' => 0, 'id' => 0, 'group' => -1, 'withoutpork' => -1, 'action' => $sAction));
     $XmlData .= xmlCloseDocument();
 }

 header("Content-type: application/xml; charset=".strtolower($CONF_CHARSET));
 echo $XmlData;
?>
