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
 * @version 3.0
 *     - 2014-01-02 : taken into account english language
 *     - 2014-01-10 : for the "delete" action, we add some "if" to check the content of variables
 *                    (must match with content database)
 *     - 2014-03-31 : taken into account Occitan language
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *     - 2016-11-02 : load some configuration variables from database
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
                                                  'CONF_NURSERY_PRICES',
                                                  'CONF_NURSERY_DELAYS_PRICES'));

             $ArrayData = explode('|', $sParam);

             if (count($ArrayData) == 5)
             {
                 $ForDate = $ArrayData[0];
                 $Class = $ArrayData[1];
                 $ChildID = $ArrayData[2];
                 $PeriodAMPM = $ArrayData[4];

                 // First, we check if the child has a nursery registration for this date
                 $ArrayNurseryRegistrations = getNurseryRegistrations($DbCon, $ForDate, $ForDate, 'NurseryRegistrationForDate',
                                                                      $ChildID, PLANNING_BETWEEN_DATES);

                 switch($sAction)
                 {
                     case 'delete':
                         // Update or delete the nursery registration for the child
                         if (!empty($ArrayNurseryRegistrations))
                         {
                             $NurseryRegistrationID = $ArrayNurseryRegistrations['NurseryRegistrationID'][0];
                             $bCanDelete = FALSE;
                             switch($PeriodAMPM)
                             {
                                 case 1:
                                     // AM is not checked
                                     $ForAM = 0;
                                     $ForPM = $ArrayNurseryRegistrations['NurseryRegistrationForPM'][0];
                                     if ($ForPM == 0)
                                     {
                                         $bCanDelete = TRUE;
                                     }
                                     break;

                                 case 2:
                                     // PM is not checked
                                     $ForAM = $ArrayNurseryRegistrations['NurseryRegistrationForAM'][0];
                                     $ForPM = 0;
                                     if ($ForAM == 0)
                                     {
                                         $bCanDelete = TRUE;
                                     }
                                     break;
                             }

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

                                     // Log event
                                     logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                              $NurseryRegistrationID, array('NurseryRegistrationDetails' => $RecordNurseryRegistration));

                                     $XmlData = xmlOpenDocument();
                                     $XmlData .= xmlTag("Message", $sMsg, array('type' => $iTypeMsg, 'id' => $ID, 'action' => $sAction));
                                     $XmlData .= xmlCloseDocument();
                                 }

                                 unset($RecordNurseryRegistration);
                             }
                             else
                             {
                                 // There is a change of nursery registration (for AM or PM)
                                 // We check if the logged supporter is an admin (so, not concerned by restrictions on delays)
                                 $AdminDate = NULL;
                                 if (!in_array($_SESSION['SupportMemberStateID'], $CONF_NURSERY_DELAYS_RESTRICTIONS))
                                 {
                                     // The logged supporter is an admin or a user with a special access and
                                     // allowed to modify childern nursery registrations
                                     $AdminDate = date('Y-m-d');
                                 }

                                 $ID = dbUpdateNurseryRegistration($DbCon, $NurseryRegistrationID, NULL, $ForDate,
                                                                   $ChildID, $_SESSION['SupportMemberID'], $ForAM, $ForPM,
                                                                   $ArrayNurseryRegistrations['ChildGrade'][0],
                                                                   $ArrayNurseryRegistrations['ChildClass'][0], $AdminDate);
                                 if ($ID > 0)
                                 {
                                     // Success
                                     $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                     $iTypeMsg = 1;

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
                         }
                         break;

                     case 'register':
                         // Update or add the nursery registration for the child
                         // We check if the logged supporter is an admin (so, not concerned by restrictions on delays)
                         $AdminDate = NULL;
                         if (!in_array($_SESSION['SupportMemberStateID'], $CONF_NURSERY_DELAYS_RESTRICTIONS))
                         {
                             // The logged supporter is an admin or a user with a special access and
                             // allowed to modify childern nursery registrations
                             $AdminDate = date('Y-m-d');
                         }

                         if (empty($ArrayNurseryRegistrations))
                         {
                             // Add the new nursery registration
                             switch($PeriodAMPM)
                             {
                                 case 1:
                                     // AM is checked
                                     $ForAM = 1;
                                     $ForPM = 0;
                                     break;

                                 case 2:
                                     // PM is checked
                                     $ForAM = 0;
                                     $ForPM = 1;
                                     break;
                             }

                             $RecordChild = getTableRecordInfos($DbCon, 'Children', $ChildID);
                             if (!empty($RecordChild))
                             {
                                 $ID = dbAddNurseryRegistration($DbCon, date('Y-m-d'), $ForDate, $ChildID, $_SESSION['SupportMemberID'],
                                                                $ForAM, $ForPM, $RecordChild['ChildGrade'], $RecordChild['ChildClass'],
                                                                $AdminDate);
                                 if ($ID > 0)
                                 {
                                     // Success
                                     $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                     $iTypeMsg = 1;

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
                             // There is a change of nursery registration (for AM or PM)
                             $NurseryRegistrationID = $ArrayNurseryRegistrations['NurseryRegistrationID'][0];
                             switch($PeriodAMPM)
                             {
                                 case 1:
                                     // AM is checked
                                     $ForAM = 1;
                                     $ForPM = $ArrayNurseryRegistrations['NurseryRegistrationForPM'][0];
                                     break;

                                 case 2:
                                     // PM is checked
                                     $ForAM = $ArrayNurseryRegistrations['NurseryRegistrationForAM'][0];
                                     $ForPM = 1;
                                     break;
                             }

                             $ID = dbUpdateNurseryRegistration($DbCon, $NurseryRegistrationID, NULL, $ForDate, $ChildID,
                                                               $_SESSION['SupportMemberID'], $ForAM, $ForPM,
                                                               $ArrayNurseryRegistrations['ChildGrade'][0],
                                                               $ArrayNurseryRegistrations['ChildClass'][0], $AdminDate);
                             if ($ID > 0)
                             {
                                 // Success
                                 $sMsg = $LANG_SUPPORT_PLANNING_NURSERY_AUTO_SAVE_PLUGIN_CONFIRM_PLANNING_UPDATED;
                                 $iTypeMsg = 1;

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
                         break;
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
