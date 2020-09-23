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
 * Support module : register the selected children to canteen for selected months of the current school year.
 * The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.5
 *     - 2016-11-02 : v3.0. Load some configuration variables from database
 *     - 2020-03-09 : v3.5. Taken into account $CONF_NURSERY_OTHER_TIMESLOTS because some other timeslots can
 *                    be linked to canteen registrations
 *
 * @since 2014-03-12
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Include the stats library
 include_once('../Stats/StatsLibrary.php');

 // Connection to the database
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

 $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));

 if (!empty($_POST["lmMonth"]))
 {
     $ArrayMonths = $_POST["lmMonth"];
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     if (!empty($_GET["lmMonth"]))
     {
         $ArrayMonths = $_GET["lmMonth"];
         $ParamsPOST_GET = "_GET";
     }
     else
     {
         $ArrayMonths = array();  // No month selected
         $ParamsPOST_GET = "_POST";
     }
 }

 // Get the selected children
 $ArrayChildID = array();
 if (!empty(${$ParamsPOST_GET}["lmChildID"]))
 {
     $ArrayChildID = ${$ParamsPOST_GET}["lmChildID"];
 }

 $iNbRegistrationsDone = 0;
 $iNbTotalRegistrationsToDo = 0;

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["bSubmit"]))
 {
     $TodayDate = date('Y-m-d');
     $SchoolYearStartDate = getSchoolYearStartDate($CurrentSchoolYear);
     $SchoolYearEndDate = getSchoolYearEndDate($CurrentSchoolYear);

     if ((!empty($ArrayMonths)) && (!empty($ArrayChildID)))
     {
         // Compute days opened to a canteen registration in relation with selected months
         $ArrayConcernedDays = array();

         // Check if nursery other timeslots are activated
         $iNbOtherTimeslots = 0;
         $ArrayLinkedToCanteenPlanning = array();
         $OtherTimeslotsPattern = 0;
         if ((isset($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])) && (!empty($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])))
         {
             $iNbOtherTimeslots = count($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear]);

             // We check if some other timeslots are linked to the canteen planning
             $iPos = 0;
             foreach($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
             {
                 if ((isset($CurrentParamsOtherTimeslot['LinkedToCanteen'])) && ($CurrentParamsOtherTimeslot['LinkedToCanteen'] == 1))
                 {
                     $ArrayLinkedToCanteenPlanning[] = $ots;
                     $OtherTimeslotsPattern += pow(2, $iPos);
                 }

                 $iPos++;
             }
         }

         foreach($ArrayMonths as $m => $YearMonth)
         {
             // We get the working days of the given month
             $StartDate = $YearMonth.'-01';
             $StartDate = date('Y-m-d', (max(strtotime($SchoolYearStartDate), strtotime($StartDate))));

             $EndDate = date('Y-m-t', strtotime($StartDate));
             $EndDate = date('Y-m-d', (min(strtotime($SchoolYearEndDate), strtotime($EndDate))));

             $StartMonth = date('m', strtotime($StartDate));
             $StartYear = date('Y', strtotime($StartDate));
             $EndMonth = date('m', strtotime($EndDate));
             $EndYear = date('Y', strtotime($EndDate));
             $OpenedDays = jours_ouvres(
                                        range($StartMonth, (($EndYear - $StartYear) * 12) + $EndMonth),
                                        $StartYear
                                       );

             $Holidays = ferie(
                               range($StartMonth, (($EndYear - $StartYear) * 12) + $EndMonth),
                               $StartYear
                              );

             // Get the days of the period between start date and end date
             $Days = getRepeatedDates(strtotime($StartDate), strtotime($EndDate), REPEAT_DAILY, 1, FALSE);
             $NbDays = count($Days);

             // Get the opened special days (days normaly not opened to canteen registrations but they are)
             $ArrayOpenedSpecialDays = getOpenedSpecialDays($DbCon, $StartDate, $EndDate, 'OpenedSpecialDayDate');
             if (empty($ArrayOpenedSpecialDays))
             {
                 // No opened special day for this period : we init just the key 'OpenedSpecialDayDate'
                 $ArrayOpenedSpecialDays['OpenedSpecialDayDate'] = array();
             }

             // Get school holidays
             $ArraySchoolHolidays = getHolidays($DbCon, $StartDate, $EndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
             if ((!isset($ArraySchoolHolidays['HolidayID'])) || ((isset($ArraySchoolHolidays['HolidayID']))
                 && (empty($ArraySchoolHolidays['HolidayID']))))
             {
                 $ArraySchoolHolidays = getHolidays($DbCon, $StartDate, $EndDate, 'HolidayStartDate', DATES_BETWEEN_PLANNING);
             }

             if (isset($ArraySchoolHolidays['HolidayID']))
             {
                 $StartTime = strtotime($StartDate);
                 $EndTime = strtotime($EndDate);

                 foreach($ArraySchoolHolidays['HolidayID'] as $h => $HolidayID)
                 {
                     $ArrayTmpDays = array_keys(getPeriodIntervalsStats($ArraySchoolHolidays['HolidayStartDate'][$h],
                                                                        $ArraySchoolHolidays['HolidayEndDate'][$h], 'd'));

                     foreach($ArrayTmpDays as $d => $CurrTmpDay)
                     {
                         $CurrTime = strtotime($CurrTmpDay);
                         if (($CurrTime >= $StartTime) && ($CurrTime <= $EndTime))
                         {
                             // This day is in the current month
                             $iDay = (integer)date('d', $CurrTime);
                             $CurrentMonth = (integer)date('m', $CurrTime);
                             $CurrentYear = date('Y', $CurrTime);

                             // We compute the offset of the current day in the array of working days and holidays
                             // max() because for some years (ex : 2008), first day is in the previous year
                             $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $StartYear);

                             if (!isset($Holidays[$Offset][$iDay]))
                             {
                                 $Holidays[$Offset][$iDay] = $ArraySchoolHolidays['HolidayDescription'][$h];

                                 if (isset($OpenedDays[$Offset][$iDay]))
                                 {
                                     unset($OpenedDays[$Offset][$iDay]);
                                 }
                             }
                         }
                     }

                     unset($ArrayTmpDays);
                 }
             }

             unset($ArraySchoolHolidays);

             foreach($Days as $j => $CurrentDayDate)
             {
                 $NumCurrentDay = (integer)date('d', strtotime($CurrentDayDate));
                 $CurrentMonth = (integer)date('m', strtotime($CurrentDayDate));
                 $CurrentYear = date('Y', strtotime($CurrentDayDate));

                 // We compute the offset of the current day in the array of working days and holidays
                 // max() because for some years (ex : 2008), first day is in the previous year
                 $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $StartYear);

                 // We check if the day is a working day or if the canteen is opened
                 $iNumWeekDay = date('w', strtotime($CurrentDayDate));
                 if ($iNumWeekDay == 0)
                 {
                     // Sunday = 0 -> 7
                     $iNumWeekDay = 7;
                 }

                 // We check if the current date isn't an opened special day
                 $iPosOpenedSpecialDay = array_search($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']);
                 if ((!in_array($NumCurrentDay, $OpenedDays[$Offset])) && ($iPosOpenedSpecialDay === FALSE))
                 {
                     // We don't keep this day
                 }
                 elseif ((!$GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1]) && ($iPosOpenedSpecialDay === FALSE))
                 {
                     // We don't keep this day because canteen not opened
                 }
                 else
                 {
                     // We keep this day
                     $ArrayConcernedDays[] = $CurrentDayDate;
                 }
             }
         }

         // We compute the total number of registrations to do
         $iNbTotalRegistrationsToDo = count($ArrayChildID) * count($ArrayConcernedDays);

         if (!empty($ArrayConcernedDays))
         {
             // Get info about selected children
             $ArrayChildren = dbSearchChild($DbCon, array('ChildID' => $ArrayChildID), "FamilyLastname, ChildFirstname", 1, 0);
             if (isset($ArrayChildren['ChildID']))
             {
                 // Register selected children to canteen for each day of selected months
                 foreach($ArrayChildren['ChildID'] as $c => $ChildID)
                 {
                     foreach($ArrayConcernedDays as $d => $ForDate)
                     {
                         $CanteenRegistrationID = dbAddCanteenRegistration($DbCon, $TodayDate, $ForDate, $ChildID,
                                                                           $ArrayChildren['ChildGrade'][$c],
                                                                           $ArrayChildren['ChildClass'][$c],
                                                                           $ArrayChildren['ChildWithoutPork'][$c],
                                                                           1, $TodayDate);
                         if ($CanteenRegistrationID > 0)
                         {
                             $iNbRegistrationsDone++;

                             // Log event
                             logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $CanteenRegistrationID);

                             // We check if we must add nursery other timeslots registrations
                             if ($iNbOtherTimeslots > 0)
                             {
                                 // First, we check if there is aloready a nursery registration for this day
                                 $NurseryRegistrationID = getExistingNurseryRegistrationForChildAndDate($DbCon, $ChildID, $ForDate);
                                 if ($NurseryRegistrationID == 0)
                                 {
                                     // We create the nursery registration
                                     $NurseryRegistrationID = dbAddNurseryRegistration($DbCon, $TodayDate, $ForDate, $ChildID, $_SESSION['SupportMemberID'],
                                                                                       0, 0, $ArrayChildren['ChildGrade'][$c], $ArrayChildren['ChildClass'][$c],
                                                                                       NULL, 0, $OtherTimeslotsPattern);

                                     if ($NurseryRegistrationID > 0)
                                     {
                                         // Log event
                                         logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $NurseryRegistrationID);
                                     }
                                 }
                                 else
                                 {
                                     // We update the existing nursery registration
                                     // First, we get details about this nursery registration
                                     $RecordNurseryRegistration = getTableRecordInfos($DbCon, "NurseryRegistrations", $NurseryRegistrationID);
                                     $iValueToUpdate = 0;

                                     // We check if concerned other timeslots are already checked
                                     foreach($ArrayLinkedToCanteenPlanning as $t => $ots)
                                     {
                                         if (($RecordNurseryRegistration['NurseryRegistrationOtherTimeslots'] & pow(2, $t)) == 0)
                                         {
                                             $iValueToUpdate += pow(2, $t);
                                         }
                                     }

                                     if ($iValueToUpdate != 0)
                                     {
                                         // We must update the nursery registration
                                         $NurseryRegistrationOtherTimeslots = $RecordNurseryRegistration['NurseryRegistrationOtherTimeslots'] + $iValueToUpdate;

                                         $NurseryRegistrationID = dbUpdateNurseryRegistration($DbCon, $NurseryRegistrationID, NULL, $ForDate, $ChildID,
                                                                                              $_SESSION['SupportMemberID'], NULL, NULL, $ArrayChildren['ChildGrade'][$c],
                                                                                              $ArrayChildren['ChildClass'][$c], NULL, NULL,
                                                                                              $NurseryRegistrationOtherTimeslots);

                                         if ($NurseryRegistrationID > 0)
                                         {
                                             // Log event
                                             logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $NurseryRegistrationID);
                                         }
                                     }
                                 }
                             }
                         }
                     }
                 }
             }
         }
     }

     // We reinit the selected child
     $ArrayChildID = array();
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array('../Verifications.js')
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#CanteenAnnualRegistrations', 'Accessibility');

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu(1);

 // Content of the web page
 openArea('id="content"');

 // Display the "Canteen" and the "parameters" contextual menus if the supporter isn't logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("canteen", 1, Canteen_CanteenAnnualRegistrations);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_CANTEEN_ANNUAL_REGISTRATIONS_PAGE_TITLE, 2);

 if ((!empty($iNbRegistrationsDone)) && (!empty($iNbTotalRegistrationsToDo)))
 {
     if ($iNbRegistrationsDone == $iNbTotalRegistrationsToDo)
     {
         // All canteen registrations recorded
         openParagraph("ConfirmationMsg");
         displayStyledText($LANG_CONFIRM_CANTEEN_ANNUAL_REGISTRATIONS_ADDED." ($iNbRegistrationsDone)", "ShortConfirmMsg");
         closeParagraph();
     }
     else
     {
         // Not all canteen registrations recorded
         openParagraph("ErrorMsg");
         displayStyledText($LANG_ERROR_ADD_CANTEEN_ANNUAL_REGISTRATIONS." ($iNbRegistrationsDone / $iNbTotalRegistrationsToDo)", "");
         closeParagraph();
     }

     displayBR(1);
 }

 // We display the form to select children and months
 openParagraph();
 displayStyledText($LANG_SUPPORT_CANTEEN_ANNUAL_REGISTRATIONS_PAGE_INTRODUCTION." (<strong>$CurrentSchoolYear</strong>).", "");
 closeParagraph();

 displayCanteenAnnualRegistrationsForm($DbCon, "CanteenAnnualRegistrations.php", $CurrentSchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_CANTEEN_PLANNING]);

 // Release the connection to the database
 dbDisconnection($DbCon);

 // To measure the execution script time
 if ($CONF_DISPLAY_EXECUTION_TIME_SCRIPT)
 {
     openParagraph('InfoMsg');
     initEndTime();
     displayExecutionScriptTime('ExecutionTime');
     closeParagraph();
 }

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Close the <div> "Page"
     closeArea();
 }

 // Close the <div> "content"
 closeArea();

 // Footer of the application
 displayFooter($LANG_INTRANET_FOOTER);

 // Close the web page
 closeWebPage();

 closeGraphicInterface();
?>