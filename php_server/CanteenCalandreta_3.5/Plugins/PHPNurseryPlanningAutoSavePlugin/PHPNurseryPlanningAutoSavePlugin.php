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
 * PHP plugin planning nursery auto save module : when the user check/uncheck a checkbox
 * in the planning, the nursery registration is auto save/deleted in the database
 *
 * @author Christophe Javouhey
 * @version 3.5
 *     - 2014-01-02 : taken into account english language
 *     - 2014-01-10 : for the "delete" action, we add some "if" to check the content of variables
 *                    (must match with content database)
 *     - 2014-03-31 : taken into account Occitan language
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2020-02-20 : taken into account other timeslots
 *
 * @since 2013-09-12
 */


 // Include Config.php because of the name of the session
 require '../../GUI/GraphicInterface.php';

 switch($CONF_LANG)
 {
     case 'fr':
         include_once('./Languages/PHPNurseryPlanningAutoSaveFrancais.lang.php');
         break;

     case 'oc':
         include_once('./Languages/PHPNurseryPlanningAutoSaveOccitan.lang.php');
         break;

     default:
         include_once('./Languages/PHPNurseryPlanningAutoSaveEnglish.lang.php');
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
     elseif (array_key_exists('getOtherTimeslots', $_GET))
     {
         $ArrayData = explode('-', strip_tags(trim($_GET['getOtherTimeslots'])));
         if (count($ArrayData) == 2)
         {
             // Month or week as second parameter ?
             if (substr($ArrayData[1], 0, 1) == 'W')
             {
                 // It's a week : we get the first day of the week
                 $Date = getFirstDayOfWeek(substr($ArrayData[1], 1), $ArrayData[0]);
             }
             else
             {
                 // It's a month
                 $Date = $ArrayData[0].'-'.$ArrayData[1].'-01';
             }

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

             $CurrentSchoolYear = getSchoolYear($Date);

             $XmlData = xmlOpenDocument();

             if ((isset($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])) && (!empty($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])))
             {
                 foreach($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear] as $ots => $CurrentParams)
                 {
                     $XmlData .= xmlTag("OtherTimeslot", "", array('id' => $ots,
                                                                   'check-canteen' => $CurrentParams['CheckCanteen'],
                                                                   'linked2canteen' => $CurrentParams['LinkedToCanteen'],
                                                                   'check-nursery' => implode(',', $CurrentParams['CheckNursery'])));
                 }
             }

             $XmlData .= xmlCloseDocument();

             // Release the connection to the database
             dbDisconnection($DbCon);
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

             if (count($ArrayData) == 5)
             {
                 $ForDate = $ArrayData[0];
                 $Class = $ArrayData[1];
                 $ChildID = $ArrayData[2];
                 $PeriodAMPMOtherTimeslot = $ArrayData[4];

                 // First, we check if the child has a nursery registration for this date
                 $AdminDate = NULL;
                 $bCanUpdateNurseryPlanning = TRUE;
                 $bNurseryPlanningUpdated = FALSE;
                 $bNurseryRegistrationIsChanged = FALSE;

                 $ArrayNurseryRegistrations = getNurseryRegistrations($DbCon, $ForDate, $ForDate, 'NurseryRegistrationForDate',
                                                                      $ChildID, PLANNING_BETWEEN_DATES);

                 // We check if there are some other timeslots for the concerned school year
                 $CurrentSchoolYear = getSchoolYear($ForDate);
                 $iNbOtherTimeslots = 0;
                 $ArrayLinkedToCanteenPlanning = array();
                 if ((isset($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])) && (!empty($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])))
                 {
                     $iNbOtherTimeslots = count($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear]);

                     // We check if some other timeslots are linked to the canteen planning
                     foreach($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                     {
                         if ((isset($CurrentParamsOtherTimeslot['CheckCanteen'])) && ($CurrentParamsOtherTimeslot['CheckCanteen'] == 1))
                         {
                             $ArrayLinkedToCanteenPlanning[] = $ots;
                         }
                     }
                 }

                 // For delays
                 $TodayDateStamp = strtotime(date('Y-m-d'));
                 $TodayCurrentTime = strtotime(date('Y-m-d H:i:s'));
                 $LimitEditDateStamp = strtotime(date('Y-m-t',
                                                      strtotime("+".($CONF_CANTEEN_NB_MONTHS_PLANNING_REGISTRATION - 1)." months")));

                 switch($sAction)
                 {
                     case 'delete':
                         // Update or delete the nursery registration for the child
                         if (!empty($ArrayNurseryRegistrations))
                         {
                             $NurseryRegistrationID = $ArrayNurseryRegistrations['NurseryRegistrationID'][0];
                             $bCanDelete = FALSE;

                             if ($PeriodAMPMOtherTimeslot == 1)
                             {
                                 // AM is not checked
                                 $ForAM = 0;
                                 $ForPM = $ArrayNurseryRegistrations['NurseryRegistrationForPM'][0];
                                 $ForOtherTimeslot = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0];
                                 if (($ForPM == 0) && (empty($ForOtherTimeslot)))
                                 {
                                     $bCanDelete = TRUE;
                                 }

                                 if ($ArrayNurseryRegistrations['NurseryRegistrationForAM'][0] != $ForAM)
                                 {
                                     // The value has changed
                                     $bNurseryRegistrationIsChanged = TRUE;
                                 }
                             }
                             elseif ($PeriodAMPMOtherTimeslot == 2 + $iNbOtherTimeslots)
                             {
                                 // PM is not checked
                                 $ForAM = $ArrayNurseryRegistrations['NurseryRegistrationForAM'][0];
                                 $ForPM = 0;
                                 $ForOtherTimeslot = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0];
                                 if (($ForAM == 0) && (empty($ForOtherTimeslot)))
                                 {
                                     $bCanDelete = TRUE;
                                 }

                                 if ($ArrayNurseryRegistrations['NurseryRegistrationForPM'][0] != $ForPM)
                                 {
                                     // The value has changed
                                     $bNurseryRegistrationIsChanged = TRUE;
                                 }
                             }
                             elseif (($PeriodAMPMOtherTimeslot > 1) && ($PeriodAMPMOtherTimeslot < 2 + $iNbOtherTimeslots))
                             {
                                 // Other timeslot is not checked
                                 $ForAM = $ArrayNurseryRegistrations['NurseryRegistrationForAM'][0];
                                 $ForPM = $ArrayNurseryRegistrations['NurseryRegistrationForPM'][0];
                                 $ForOtherTimeslot = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0];

                                 if (($ForOtherTimeslot & pow(2, $PeriodAMPMOtherTimeslot - 2)) > 0)
                                 {
                                     $ForOtherTimeslot -= pow(2, $PeriodAMPMOtherTimeslot - 2);
                                     $bNurseryRegistrationIsChanged = TRUE;
                                 }

                                 if (($ForAM == 0) && ($ForPM == 0) && (empty($ForOtherTimeslot)))
                                 {
                                     $bCanDelete = TRUE;
                                 }

                                 // We check if there is a link with the canteen planning for this other timeslot
                                 if ((!empty($ArrayLinkedToCanteenPlanning)) && (isset($ArrayLinkedToCanteenPlanning[$PeriodAMPMOtherTimeslot - 2])))
                                 {
                                     // Yes, there is a link : we check if the user is concerned by the delay
                                     if (in_array($_SESSION['SupportMemberStateID'], $CONF_CANTEEN_DELAYS_RESTRICTIONS))
                                     {
                                         // Yes, the user is concerned by the delay : we check if the delay is OK
                                         $iNbHours = floor((strtotime(date('Y-m-d 12:00:00', strtotime($ForDate))) - $TodayCurrentTime) / 3600);
                                         if ((strtotime($ForDate) < $TodayDateStamp) || (strtotime($ForDate) > $LimitEditDateStamp)
                                             || ($iNbHours < $CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION))
                                         {
                                             $bCanUpdateNurseryPlanning = FALSE;
                                         }
                                     }
                                 }
                             }

                             if ($bCanUpdateNurseryPlanning)
                             {
                                 if ($bCanDelete)
                                 {
                                     // No nursery registration for AM and PM for this date and this child
                                     $RecordNurseryRegistration = getTableRecordInfos($DbCon, "NurseryRegistrations", $NurseryRegistrationID);
                                     if (dbDeleteNurseryRegistration($DbCon, $NurseryRegistrationID))
                                     {
                                         // Success
                                         $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                         $iTypeMsg = 1;
                                         $ID = 0;
                                         $bNurseryPlanningUpdated = TRUE;

                                         // Log event
                                         logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                                  $NurseryRegistrationID, array('NurseryRegistrationDetails' => $RecordNurseryRegistration));

                                         $XmlData = xmlOpenDocument();
                                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                         $XmlData .= xmlCloseDocument();

                                         $NurseryRegistrationID = 0;
                                     }
                                 }
                                 else
                                 {
                                     if ($bNurseryRegistrationIsChanged)
                                     {
                                         // There is a change of nursery registration (for AM, PM or other timeslots)
                                         // We check if the logged supporter is an admin (so, not concerned by restrictions on delays)
                                         if (!in_array($_SESSION['SupportMemberStateID'], $CONF_NURSERY_DELAYS_RESTRICTIONS))
                                         {
                                             // The logged supporter is an admin or a user with a special access and
                                             // allowed to modify childern nursery registrations
                                             $AdminDate = date('Y-m-d');
                                         }

                                         $ID = dbUpdateNurseryRegistration($DbCon, $NurseryRegistrationID, NULL, $ForDate,
                                                                           $ChildID, $_SESSION['SupportMemberID'], $ForAM, $ForPM,
                                                                           $ArrayNurseryRegistrations['ChildGrade'][0],
                                                                           $ArrayNurseryRegistrations['ChildClass'][0], $AdminDate, NULL, $ForOtherTimeslot);
                                         if ($ID > 0)
                                         {
                                             // Success
                                             $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                             $iTypeMsg = 1;
                                             $bNurseryPlanningUpdated = TRUE;

                                             // Log event
                                             logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $ID);

                                             $XmlData = xmlOpenDocument();
                                             $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                             $XmlData .= xmlCloseDocument();
                                         }
                                         else
                                         {
                                             // Error
                                             $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                                             $iTypeMsg = 0;
                                             $ID = $ArrayData[3];  // Keep the same ID got in input

                                             $XmlData = xmlOpenDocument();
                                             $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                             $XmlData .= xmlCloseDocument();
                                         }
                                     }
                                     else
                                     {
                                         // Do nothing because no update needed
                                         $sMsg = "-";
                                         $iTypeMsg = 1;
                                         $ID = 0;

                                         $XmlData = xmlOpenDocument();
                                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                         $XmlData .= xmlCloseDocument();
                                     }
                                 }
                             }
                             else
                             {
                                 // Error of canteen delay
                                 $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_ERROR_CANTEEN_DELAY;
                                 $iTypeMsg = 0;
                                 $ID = $ArrayData[3];  // Keep the same ID got in input

                                 $XmlData = xmlOpenDocument();
                                 $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                 $XmlData .= xmlCloseDocument();
                             }
                         }
                         else
                         {
                             // Do nothing because nothing to delete
                             $sMsg = "-";
                             $iTypeMsg = 1;
                             $ID = 0;

                             $XmlData = xmlOpenDocument();
                             $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                             $XmlData .= xmlCloseDocument();
                         }
                         break;

                     case 'register':
                         // Update or add the nursery registration for the child
                         // We check if the logged supporter is an admin (so, not concerned by restrictions on delays)
                         if (!in_array($_SESSION['SupportMemberStateID'], $CONF_NURSERY_DELAYS_RESTRICTIONS))
                         {
                             // The logged supporter is an admin or a user with a special access and
                             // allowed to modify childern nursery registrations
                             $AdminDate = date('Y-m-d');
                         }

                         if (empty($ArrayNurseryRegistrations))
                         {
                             // Add the new nursery registration
                             if ($PeriodAMPMOtherTimeslot == 1)
                             {
                                 // AM is checked
                                 $ForAM = 1;
                                 $ForPM = 0;
                                 $ForOtherTimeslot = NULL;
                             }
                             elseif ($PeriodAMPMOtherTimeslot == 2 + $iNbOtherTimeslots)
                             {
                                 // PM is checked
                                 $ForAM = 0;
                                 $ForPM = 1;
                                 $ForOtherTimeslot = NULL;
                             }
                             elseif (($PeriodAMPMOtherTimeslot > 1) && ($PeriodAMPMOtherTimeslot < 2 + $iNbOtherTimeslots))
                             {
                                 // Other timeslot is checked
                                 $ForAM = 0;
                                 $ForPM = 0;
                                 $ForOtherTimeslot = pow(2, $PeriodAMPMOtherTimeslot - 2);

                                 // We check if there is a link with the canteen planning for this other timeslot
                                 if ((!empty($ArrayLinkedToCanteenPlanning)) && (isset($ArrayLinkedToCanteenPlanning[$PeriodAMPMOtherTimeslot - 2])))
                                 {
                                     // Yes, there is a link : we check if the user is concerned by the delay
                                     if (in_array($_SESSION['SupportMemberStateID'], $CONF_CANTEEN_DELAYS_RESTRICTIONS))
                                     {
                                         // Yes, the user is concerned by the delay : we check if the delay is OK
                                         $iNbHours = floor((strtotime(date('Y-m-d 12:00:00', strtotime($ForDate))) - $TodayCurrentTime) / 3600);
                                         if ((strtotime($ForDate) < $TodayDateStamp) || (strtotime($ForDate) > $LimitEditDateStamp)
                                             || ($iNbHours < $CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION))
                                         {
                                             $bCanUpdateNurseryPlanning = FALSE;
                                         }
                                     }
                                 }
                             }

                             $RecordChild = getTableRecordInfos($DbCon, 'Children', $ChildID);
                             if (!empty($RecordChild))
                             {
                                 if ($bCanUpdateNurseryPlanning)
                                 {
                                     $ID = dbAddNurseryRegistration($DbCon, date('Y-m-d'), $ForDate, $ChildID, $_SESSION['SupportMemberID'],
                                                                    $ForAM, $ForPM, $RecordChild['ChildGrade'], $RecordChild['ChildClass'],
                                                                    $AdminDate, 0, $ForOtherTimeslot);
                                     if ($ID > 0)
                                     {
                                         // Success
                                         $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                         $iTypeMsg = 1;
                                         $NurseryRegistrationID = $ID;
                                         $bNurseryPlanningUpdated = TRUE;

                                         // Log event
                                         logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $ID);

                                         $XmlData = xmlOpenDocument();
                                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                         $XmlData .= xmlCloseDocument();
                                     }
                                     else
                                     {
                                         // Error
                                         $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                                         $iTypeMsg = 0;
                                         $ID = 0;

                                         $XmlData = xmlOpenDocument();
                                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                         $XmlData .= xmlCloseDocument();
                                     }
                                 }
                                 else
                                 {
                                     // Error of canteen delay
                                     $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_ERROR_CANTEEN_DELAY;
                                     $iTypeMsg = 0;
                                     $ID = 0;

                                     $XmlData = xmlOpenDocument();
                                     $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                     $XmlData .= xmlCloseDocument();
                                 }
                             }
                             else
                             {
                                 // Do nothing
                                 $sMsg = "-";
                                 $iTypeMsg = 1;
                                 $ID = $ArrayData[3];  // Keep the same ID got in input

                                 $XmlData = xmlOpenDocument();
                                 $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                 $XmlData .= xmlCloseDocument();
                             }

                             unset($RecordChild);
                         }
                         else
                         {
                             // Update an existing nursery registration
                             // There is a change of nursery registration (for AM, PM or other timeslot)
                             $NurseryRegistrationID = $ArrayNurseryRegistrations['NurseryRegistrationID'][0];

                             if ($PeriodAMPMOtherTimeslot == 1)
                             {
                                 // AM is checked
                                 $ForAM = 1;
                                 $ForPM = $ArrayNurseryRegistrations['NurseryRegistrationForPM'][0];
                                 $ForOtherTimeslot = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0];

                                 if ($ArrayNurseryRegistrations['NurseryRegistrationForAM'][0] != $ForAM)
                                 {
                                     // The value has changed
                                     $bNurseryRegistrationIsChanged = TRUE;
                                 }
                             }
                             elseif ($PeriodAMPMOtherTimeslot == 2 + $iNbOtherTimeslots)
                             {
                                 // PM is checked
                                 $ForAM = $ArrayNurseryRegistrations['NurseryRegistrationForAM'][0];
                                 $ForPM = 1;
                                 $ForOtherTimeslot = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0];

                                 if ($ArrayNurseryRegistrations['NurseryRegistrationForPM'][0] != $ForPM)
                                 {
                                     // The value has changed
                                     $bNurseryRegistrationIsChanged = TRUE;
                                 }
                             }
                             elseif (($PeriodAMPMOtherTimeslot > 1) && ($PeriodAMPMOtherTimeslot < 2 + $iNbOtherTimeslots))
                             {
                                 // Other timeslot is checked
                                 $ForAM = $ArrayNurseryRegistrations['NurseryRegistrationForAM'][0];
                                 $ForPM = $ArrayNurseryRegistrations['NurseryRegistrationForPM'][0];
                                 $ForOtherTimeslot = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0];

                                 if (($ForOtherTimeslot & pow(2, $PeriodAMPMOtherTimeslot - 2)) == 0)
                                 {
                                     // The other timeslot isn't already checked : we check it
                                     $ForOtherTimeslot = $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][0] + pow(2, $PeriodAMPMOtherTimeslot - 2);
                                     $bNurseryRegistrationIsChanged = TRUE;
                                 }

                                 // We check if there is a link with the canteen planning for this other timeslot
                                 if ((!empty($ArrayLinkedToCanteenPlanning)) && (isset($ArrayLinkedToCanteenPlanning[$PeriodAMPMOtherTimeslot - 2])))
                                 {
                                     // Yes, there is a link : we check if the user is concerned by the delay
                                     if (in_array($_SESSION['SupportMemberStateID'], $CONF_CANTEEN_DELAYS_RESTRICTIONS))
                                     {
                                         // Yes, the user is concerned by the delay : we check if the delay is OK
                                         $iNbHours = floor((strtotime(date('Y-m-d 12:00:00', strtotime($ForDate))) - $TodayCurrentTime) / 3600);
                                         if ((strtotime($ForDate) < $TodayDateStamp) || (strtotime($ForDate) > $LimitEditDateStamp)
                                             || ($iNbHours < $CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION))
                                         {
                                             $bCanUpdateNurseryPlanning = FALSE;
                                         }
                                     }
                                 }
                             }

                             if ($bCanUpdateNurseryPlanning)
                             {
                                 // We update only if a value has changed to avoid pb of quantities updates
                                 if ($bNurseryRegistrationIsChanged)
                                 {
                                     $ID = dbUpdateNurseryRegistration($DbCon, $NurseryRegistrationID, NULL, $ForDate, $ChildID,
                                                                       $_SESSION['SupportMemberID'], $ForAM, $ForPM,
                                                                       $ArrayNurseryRegistrations['ChildGrade'][0],
                                                                       $ArrayNurseryRegistrations['ChildClass'][0], $AdminDate, NULL, $ForOtherTimeslot);
                                     if ($ID > 0)
                                     {
                                         // Success
                                         $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                         $iTypeMsg = 1;
                                         $bNurseryPlanningUpdated = TRUE;

                                         // Log event
                                         logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $ID);

                                         $XmlData = xmlOpenDocument();
                                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                         $XmlData .= xmlCloseDocument();
                                     }
                                     else
                                     {
                                         // Error
                                         $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
                                         $iTypeMsg = 0;
                                         $ID = $ArrayData[3];  // Keep the same ID got in input

                                         $XmlData = xmlOpenDocument();
                                         $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                         $XmlData .= xmlCloseDocument();
                                     }
                                 }
                                 else
                                 {
                                     // Do nothing
                                     $sMsg = "-";
                                     $iTypeMsg = 1;
                                     $ID = $ArrayData[3];  // Keep the same ID got in input

                                     $XmlData = xmlOpenDocument();
                                     $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                     $XmlData .= xmlCloseDocument();
                                 }
                             }
                             else
                             {
                                 // Error of canteen delay
                                 $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_ERROR_CANTEEN_DELAY;
                                 $iTypeMsg = 0;
                                 $ID = $ArrayData[3];  // Keep the same ID got in input

                                 $XmlData = xmlOpenDocument();
                                 $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                 $XmlData .= xmlCloseDocument();
                             }
                         }
                         break;
                 }

                 // Now we check if there are some other timeslots linked to the canteen planning and if we must update the canteen planning
                 if ((!empty($ArrayLinkedToCanteenPlanning)) && ($bNurseryPlanningUpdated))
                 {
                     // Get infos about the nursery registration
                     if ((isset($NurseryRegistrationID)) && ($NurseryRegistrationID > 0))
                     {
                         $RecordNurseryRegistration = getTableRecordInfos($DbCon, 'NurseryRegistrations', $NurseryRegistrationID);
                     }

                     if (!empty($RecordNurseryRegistration))
                     {
                         if ((isset($bCanDelete)) && ($bCanDelete))
                         {
                             // The nursery registration must be reste for AM, PM and other timeslots
                             $RecordNurseryRegistration['NurseryRegistrationForAM'] = 0;
                             $RecordNurseryRegistration['NurseryRegistrationForPM'] = 0;
                             $RecordNurseryRegistration['NurseryRegistrationOtherTimeslots'] = 0;
                         }

                         // We check if in this nursery registration, some other timeslots are checked and need a canteen registration
                         $bMustUpdateCanteenPlanning = FALSE;
                         foreach($ArrayLinkedToCanteenPlanning as $t => $ots)
                         {
                             if ($RecordNurseryRegistration['NurseryRegistrationOtherTimeslots'] & pow(2, $t))
                             {
                                 $bMustUpdateCanteenPlanning = TRUE;
                                 break;
                             }
                         }

                         // We get the canteen registration for this day : if not exists, we create it
                         $iNb = 0;
                         $ArrayCanteenRegistrations = getCanteenRegistrations($DbCon, $RecordNurseryRegistration['NurseryRegistrationForDate'],
                                                                              $RecordNurseryRegistration['NurseryRegistrationForDate'], 'CanteenRegistrationForDate',
                                                                              $RecordNurseryRegistration['ChildID']);

                         if ((isset($ArrayCanteenRegistrations['CanteenRegistrationID'])) && (!empty($ArrayCanteenRegistrations['CanteenRegistrationID'])))
                         {
                             $iNb = count($ArrayCanteenRegistrations['CanteenRegistrationID']);
                         }

                         // Get info about the concerned child
                         $RecordChild = getTableRecordInfos($DbCon, "Children", $RecordNurseryRegistration['ChildID']);

                         if ($bMustUpdateCanteenPlanning)
                         {
                             if (empty($iNb))
                             {
                                 // No canteen regsitration : we have to create a canteen registration for this date
                                 $CanteenRegistrationID = dbAddCanteenRegistration($DbCon, date('Y-m-d'), $ForDate, $RecordChild['ChildID'], $RecordChild['ChildGrade'],
                                                                                   $RecordChild['ChildClass'], $RecordChild['ChildWithoutPork'], 1, $AdminDate);

                                 if ($CanteenRegistrationID > 0)
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $CanteenRegistrationID);
                                 }
                             }
                         }
                         else
                         {
                             if ($iNb == 1)
                             {
                                 // We must delete this canteen registration because no other timeslot linked to canteen planning
                                 $RecordCanteenRegistration = getTableRecordInfos($DbCon, "CanteenRegistrations", $ArrayCanteenRegistrations['CanteenRegistrationID'][0]);
                                 if (dbDeleteCanteenRegistration($DbCon, $ArrayCanteenRegistrations['CanteenRegistrationID'][0]))
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                              $ArrayCanteenRegistrations['CanteenRegistrationID'][0], array('CanteenRegistrationDetails' => $RecordCanteenRegistration));
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
 }

 if (empty($XmlData))
 {
     // Error
     $XmlData = xmlOpenDocument();

     if (empty($sMsg))
     {
         $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_ERROR_UPDATE_PLANNING;
     }

     $XmlData .= xmlTag("Message", $sMsg, array('type' => 0, 'id' => 0, 'action' => $sAction));
     $XmlData .= xmlCloseDocument();
 }

 header("Content-type: application/xml; charset=".strtolower($CONF_CHARSET));
 echo $XmlData;
?>
