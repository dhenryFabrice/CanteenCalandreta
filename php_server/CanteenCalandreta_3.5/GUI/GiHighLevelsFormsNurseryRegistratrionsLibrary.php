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
 * Interface module : XHTML Graphic high level forms library used to manage the planning
 * of the nursery.
 *
 * @author Christophe Javouhey
 * @version 3.5
 * @since 2012-02-14
 */


/**
 * Display the planning of the nursery of each child for a month, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-01-25
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update nursery registrations
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some nursery registratrions
 */
 function displayNurseryPlanningByMonthForm($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array(), $ViewsRestrictions = array())
 {
     // Compute start date and end date of the month
     $StartDate = sprintf("%04d-%02d-01", $Year, $Month);
     $EndDate = date("Y-m-t", strtotime($StartDate));
     $SelectedDate = $StartDate;

     displayNurseryPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, PLANNING_MONTH_VIEW,
                                $AccessRules, $ViewsRestrictions);
 }


/**
 * Display the planning of the nurseries of each child for a week, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2013-01-25
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Week                 Integer               Week to display [1..53]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update nursery registrations
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some nursery registratrions
 */
 function displayNurseryPlanningByWeeksForm($DbConnection, $ProcessFormPage, $Week, $Year, $AccessRules = array(), $ViewsRestrictions = array())
 {
     // Compute start date and end date of the month
     $StartDate = getFirstDayOfWeek($Week, $Year);

     // N weeks + 6 days (first day of week is a monday, so the last is a sunday)
     $EndDate = date("Y-m-d", strtotime('+6 days',
                                        strtotime('+'.($GLOBALS['CONF_PLANNING_WEEKS_TO_DISPLAY'] - 1).' week',
                                                  strtotime($StartDate))));
     $SelectedDate = $StartDate;

     displayNurseryPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, PLANNING_WEEKS_VIEW,
                                $AccessRules, $ViewsRestrictions);
 }


/**
 * Display the planning of the nursery of each child, for a given start date and end date,
 * in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 4.2
 *     - 2012-07-10 : patch a bug about desactivated children and new children for an old period,
 *                    allow an overflow for the content of the planning
 *     - 2013-01-25 : generic function allowing display the planning for dates between a start date
 *                    and an end date
 *     - 2013-02-11 : patch a bug about desactivated children when user connected with an account concerned
 *                    by the PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN access restriction
 *     - 2013-06-26 : taken into account the new structure of the CONF_CLASSROOMS variable
 *                    (includes school year)
 *     - 2013-08-30 : patch a bug about school holidays not taken into account in $Holidays
 *     - 2013-10-25 : taken into account the opened special days
 *     - 2013-12-04 : remove hidden input fields to increase perfs to display the planning
 *     - 2014-02-03 : display with a different style nursery registration delays, display
 *                    title about AM and PM nurseries and add an anchor on the first child
 *     - 2014-05-22 : add a hidden field for CRC and get canteen registrations in the loop of each child
 *                    and not in the loop of each day of each child
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *     - 2017-09-06 : taken into account FCT_ACT_PARTIAL_READ_ONLY to allow users to registers children
 *                    in the nursery planning for next days
 *     - 2019-09-17 : v4.0. Display a waiting message when the page is loading
 *     - 2020-01-22 : v4.1. Taken into account CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION and
 *                    $CONF_NURSERY_OTHER_TIMESLOTS
 *
 * @since 2012-02-14
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $StartDate            Date                  Start date of the planning (YYYY-mm-dd format)
 * @param $EndDate              Date                  End date of the planning (YYYY-mm-dd format)
 * @param $SelectedDate         Date                  Selected date (YYYY-mm-dd format)
 * @param $ViewType             Integer               Type of view to display (month, week, day)
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update nursery registrations
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some nursery registrations
 */
 function displayNurseryPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, $ViewType = PLANNING_MONTH_VIEW, $AccessRules = array(), $ViewsRestrictions = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a nursery registration
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
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

         $Year = date('Y', strtotime($SelectedDate));
         $Month = date('m', strtotime($SelectedDate));
         $Day = date('d', strtotime($SelectedDate));
         $Week = date('W', strtotime($SelectedDate));
         $YearOfWeek = date('o', strtotime($SelectedDate));

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormViewPlanning", "post", "$ProcessFormPage", "", "");

             // Display the months list to change the planning to display
             openParagraph('toolbar');

             // <<<< Types of views SELECTFIELD >>>
             $ViewsList = generateSelectField("lView", array_keys($GLOBALS["CONF_PLANNING_VIEWS_TYPES"]), array_values($GLOBALS["CONF_PLANNING_VIEWS_TYPES"]),
                                              $ViewType, "onChangeSelectedPlanningView(this.value)");

             $GeneratedYears = range(2009, 2037);

             switch($ViewType)
             {
                 case PLANNING_WEEKS_VIEW:
                     // Caption to display in the planning, in relation with the selected view type
                     $PlanningViewTypeCaption = ucfirst($GLOBALS["LANG_WEEK"]{0})."$Week-$YearOfWeek";

                     // <<< Weeks SELECTFIELD >>>
                     $WeeksList = generateSelectField("lWeek", range(1, getNbWeeksOfYear("$YearOfWeek")), range(1, getNbWeeksOfYear("$YearOfWeek")),
                                                      $Week, "onChangeSelectedWeek(this.value)");

                     // <<< Year SELECTFIELD >>>
                     $YearsList = generateSelectField("lYear", $GeneratedYears, $GeneratedYears, $YearOfWeek, "onChangeSelectedYear(this.value)");

                     // Compute the previous week
                     $PreviousStamp = strtotime("-7 day", strtotime($StartDate));
                     $PreviousWeek = date('W', $PreviousStamp);
                     $PreviousYear = date('Y', $PreviousStamp);
                     if ($PreviousWeek == 1)
                     {
                         $PreviousYear = $Year;
                     }
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$PreviousWeek&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     // Display the year list to change the planning to display
                     echo " $ViewsList $WeeksList $YearsList ";

                     // Compute the next week
                     $NextStamp = strtotime("+7 day", strtotime($StartDate));
                     $NextWeek = date('W', $NextStamp);
                     $NextYear = date('Y', $NextStamp);
                     if ($NextWeek == 1)
                     {
                         $NextYear = $Year + 1;
                     }
                     displayStyledLinkText($GLOBALS["LANG_NEXT"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$NextWeek&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
                     break;


                 case PLANNING_MONTH_VIEW:
                 default:
                     // Caption to display in the planning, in relation with the selected view type
                     $PlanningViewTypeCaption = $GLOBALS["CONF_PLANNING_MONTHS"][$Month - 1];

                     // <<< Months SELECTFIELD >>>
                     $MonthsList = generateSelectField("lMonth", range(1, 12), $GLOBALS["CONF_PLANNING_MONTHS"], $Month,
                                                       "onChangeSelectedMonth(this.value)");

                     // <<< Year SELECTFIELD >>>
                     $YearsList = generateSelectField("lYear", $GeneratedYears, $GeneratedYears, $Year,
                                                      "onChangeSelectedYear(this.value)");

                     // Compute the previous month
                     if ($Month == 1)
                     {
                         $PreviousMonth = 12;
                         $PreviousYear = $Year - 1;
                     }
                     else
                     {
                         $PreviousMonth = $Month - 1;
                         $PreviousYear = $Year;
                     }
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$PreviousMonth&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     // Display the year list to change the planning to display
                     echo " $ViewsList $MonthsList $YearsList ";

                     // Compute the next month
                     if ($Month == 12)
                     {
                         $NextMonth = 1;
                         $NextYear = $Year + 1;
                     }
                     else
                     {
                         $NextMonth = $Month + 1;
                         $NextYear = $Year;
                     }
                     displayStyledLinkText($GLOBALS["LANG_NEXT"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$NextMonth&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
             }

             closeParagraph();

             displayBR(1);

             // We get the working days of the given month
             $StartMonth = date('m', strtotime($StartDate));
             $EndMonth = date('m', strtotime($EndDate));
             $EndYear = date('Y', strtotime($EndDate));
             $OpenedDays = jours_ouvres(
                                        range($StartMonth, (($EndYear - $Year) * 12) + $EndMonth),
                                        $Year
                                       );

             $Holidays = ferie(
                               range($StartMonth, (($EndYear - $Year) * 12) + $EndMonth),
                               $Year
                              );

             // Get the days of the period between start date and end date
             $Days = getRepeatedDates(strtotime($StartDate), strtotime($EndDate), REPEAT_DAILY, 1, FALSE);
             $NbDays = count($Days);

             // Get the opened special days (days normaly not opened to canteen registrations but they are)
             $ArrayOpenedSpecialDays = getOpenedSpecialDays($DbConnection, $StartDate, $EndDate, 'OpenedSpecialDayDate');
             if (empty($ArrayOpenedSpecialDays))
             {
                 // No opened special day for this period : we init just the key 'OpenedSpecialDayDate'
                 $ArrayOpenedSpecialDays['OpenedSpecialDayDate'] = array();
             }

             // Get school holidays
             $ArraySchoolHolidays = getHolidays($DbConnection, $StartDate, $EndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
             if ((!isset($ArraySchoolHolidays['HolidayID'])) || ((isset($ArraySchoolHolidays['HolidayID']))
                 && (empty($ArraySchoolHolidays['HolidayID']))))
             {
                 $ArraySchoolHolidays = getHolidays($DbConnection, $StartDate, $EndDate, 'HolidayStartDate', DATES_BETWEEN_PLANNING);
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
                             $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $Year);

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

             /* We get the nursery registrations for the current month to found the children.
                We get too all activated chidren to have the complete list of all children */

             // We check if the logged supporter can view all nursery registrations or a limited view
             $RestrictionAccess = PLANNING_VIEWS_RESTRICTION_ALL;
             if ((!empty($ViewsRestrictions)) && (isset($ViewsRestrictions[$_SESSION['SupportMemberStateID']])))
             {
                 $RestrictionAccess = $ViewsRestrictions[$_SESSION['SupportMemberStateID']];
             }

             $ArrayChildren = array(
                                    'ChildID' => array()
                                   );

             $UserAccessStyle = '';
             switch($RestrictionAccess)
             {
                 case PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN:
                     // View only the registrations of the children of the family
                     // Use the supporter lastname to find the family and children
                     $ArrayFamilies = dbSearchFamily($DbConnection, array("FamilyID" => $_SESSION['FamilyID']), "FamilyID DESC", 1, 1);

                     if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                     {
                         // Get children of the family
                         $ArrayChildren = getFamilyChildren($DbConnection, $ArrayFamilies['FamilyID'][0], "ChildClass");
                         if (isset($ArrayChildren['ChildID']))
                         {
                             // Check if the child is activated betwwen start date and end date
                             $ArrayChildrenTmp = array();
                             $ArrayChildrenKeys = array_keys($ArrayChildren);

                             // Init the tmp array
                             foreach($ArrayChildrenKeys as $k => $CurrentKey)
                             {
                                 $ArrayChildrenTmp[$CurrentKey] = array();
                             }

                             $StartDateStampTmp = strtotime($StartDate);
                             $EndDateStampTmp = strtotime($EndDate);
                             foreach($ArrayChildren['ChildID'] as $c => $CurrentChildID)
                             {
                                 $bKeepChild = FALSE;
                                 $SchoolDateStamp = strtotime($ArrayChildren['ChildSchoolDate'][$c]);
                                 if (($SchoolDateStamp <= $StartDateStampTmp) || (($SchoolDateStamp >= $StartDateStampTmp) && ($SchoolDateStamp <= $EndDateStampTmp)))
                                 {
                                     if (is_null($ArrayChildren['ChildDesactivationDate'][$c]))
                                     {
                                         // No desactivation date
                                         $bKeepChild = TRUE;
                                     }
                                     else
                                     {
                                         // Desactivation date : we chek if we must keep this child
                                         $DesactivationDateStamp = strtotime($ArrayChildren['ChildDesactivationDate'][$c]);
                                         if (($DesactivationDateStamp >= $StartDateStampTmp) || ($DesactivationDateStamp >= $EndDateStampTmp))
                                         {
                                             $bKeepChild = TRUE;
                                         }
                                     }
                                 }

                                 if ($bKeepChild)
                                 {
                                     // We keep this child : we copy its data
                                     foreach($ArrayChildrenKeys as $k => $CurrentKey)
                                     {
                                         $ArrayChildrenTmp[$CurrentKey][] = $ArrayChildren[$CurrentKey][$c];
                                     }
                                 }
                             }

                             $ArrayChildren = $ArrayChildrenTmp;
                             unset($ArrayChildrenTmp);
                         }
                     }
                     break;

                 case PLANNING_VIEWS_RESTRICTION_ALL:
                 default:
                     // View all nursery registrations
                     $ArrayChildren = getChildrenListForNurseryPlanning($DbConnection, $StartDate, $EndDate, "ChildClass, FamilyLastname",
                                                                        FALSE, PLANNING_BETWEEN_DATES);
                     $UserAccessStyle = 'Scroll';

                     // Display a waiting message when the page is loading
                     echo "<div id=\"WaitingLoadingPageMsg\" class=\"WaitingLoadingPageMsg\"><p>".$GLOBALS['LANG_WAITING_PAGE_LOADING']."</p></div>";
                     break;
             }


             // Get the right school year to use : we use the start date of the displayed planning
             $CurrentSchoolYear = getSchoolYear($StartDate);

             // We get the number of other nursery timeslots
             $iNbOtherTimeslots = 0;
             $ArrayOtherTimeslotsPatterns = array();
             if ((isset($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear]))
                 && (!empty($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear])))
             {
                 // This school year has some other timeslots (more than AM and PM timeslots)
                 $iNbOtherTimeslots = count($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear]);
                 $iPos = 0;
                 foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                 {
                     $ArrayOtherTimeslotsPatterns[$ots] = pow(2, $iPos);
                     $iPos++;
                 }
             }

             // Get capacities to supervise children if this option is activated
             $ArrayDatesCapacities = array();
             $ArrayCapacityGradesIndex = array();

             if ($GLOBALS['CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_USE_CAPACITIES'])
             {
                 foreach($GLOBALS['CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_CAPACITIES'] as $c => $CurrentCapacity)
                 {
                     foreach($CurrentCapacity['Grade'] as $g => $CurrentGrade)
                     {
                         $ArrayCapacityGradesIndex[$CurrentGrade] = $c;
                     }
                 }

                 $ArrayDatesCapacities = getNurseryRegistrationCapacities($DbConnection, $GLOBALS['CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_CAPACITIES'],
                                                                          $StartDate, $EndDate, $iNbOtherTimeslots);
             }

             // We display the caption of the planning : the month, the days and totals
             $iNbTimeslots = 2 + $iNbOtherTimeslots; // +2 beacuse AM and PM timeslots
             echo "<table id=\"NurseryPlanning\" class=\"Planning\" cellspacing=\"0\">\n<thead class=\"$UserAccessStyle\">\n";
             echo "<tr>\n\t<th class=\"PlanningMonthCaption\">$PlanningViewTypeCaption</th>";
             foreach($Days as $i => $CurrentDay)
             {
                 // Display the first letter of the day (monday -> M)
                 $Prefix = '';
                 $CurrentDayDate = $CurrentDay;
                 $NumCurrentDay = (integer)date('d', strtotime($CurrentDay));
                 $CurrentMonth = (integer)date('m', strtotime($CurrentDay));
                 $CurrentYear = date('Y', strtotime($CurrentDay));

                 // We compute the offset of the current day in the array of working days and holidays
                 // max() because for some years (ex : 2008), first day is in the previous year
                 $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $Year);

                 $iNumWeekDay = date('w', strtotime($CurrentDayDate));
                 if ($iNumWeekDay == 0)
                 {
                     // Sunday = 0 -> 7
                     $iNumWeekDay = 7;
                 }

                 if (in_array($iNumWeekDay, array(1, 2, 4, 5)))
                 {
                     $Prefix = StrToUpper(substr($GLOBALS['CONF_DAYS_OF_WEEK'][$iNumWeekDay - 1], 0, 1));
                 }

                 if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                     || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                     && ($GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][1])))
                 {
                     // Nursery opened
                     $StyleOtherTimeslotCaption = '';
                     if ($iNbOtherTimeslots > 0)
                     {
                         $iWidth = 23 * $iNbTimeslots;
                         $StyleOtherTimeslotCaption = " style=\"width: ".$iWidth."px !important; max-width: ".$iWidth."px !important; min-width: ".$iWidth."px !important;\"";
                     }

                     echo "<th class=\"PlanningCaptions\" colspan=\"$iNbTimeslots\" $StyleOtherTimeslotCaption>$Prefix ".sprintf("%02u", $NumCurrentDay)."</th>";
                 }
                 else
                 {
                     // Nursery not opened
                     $StyleOtherTimeslotCaption = '';
                     if ($iNbOtherTimeslots > 0)
                     {
                         $iWidth = 7 * $iNbTimeslots;
                         $StyleOtherTimeslotCaption = " style=\"width: ".$iWidth."px !important; max-width: ".$iWidth."px !important; min-width: ".$iWidth."px !important;\"";
                     }

                     echo "<th class=\"PlanningCaptionsHoliday\" colspan=\"$iNbTimeslots\" $StyleOtherTimeslotCaption>$Prefix ".sprintf("%02u", $NumCurrentDay)."</th>";
                 }
             }
             echo "<th class=\"PlanningTotalCaption\">".$GLOBALS['LANG_TOTAL']."</th>\n</tr>\n</thead>\n<tbody class=\"$UserAccessStyle\">\n";

             // We display the nursery registrations of each child
             $CurrentClass = NULL;
             $TodayDateStamp = strtotime(date('Y-m-d'));

             // To store nb nursery registrations for each day (AM is the first array and PM, the last array)
             $ArrayTotalsDaysOfMonth = array_fill(0, $iNbTimeslots, array());

             $bIsFirstChildOfPlanning = TRUE;
             $PlanningCRC = 0;

             foreach($ArrayChildren["ChildID"] as $i => $CurrentChildID)
             {
                 if ($CurrentClass != $ArrayChildren["ChildClass"][$i])
                 {
                     // We display the row to split the different class
                     echo "<tr>\n\t<td class=\"PlanningSplit\" colspan=\"".($iNbTimeslots * $NbDays + 2)."\"><strong>"
                          .$GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$ArrayChildren["ChildClass"][$i]]
                          ."</strong></td>\n</tr>\n";

                     // We update the current class
                     $CurrentClass = $ArrayChildren["ChildClass"][$i];

                     // We display the checkbox allowing to registre or unregistre children of a same class
                     echo "<tr>\n\t<td class=\"PlanningCaptions\">&nbsp;</td>\n";
                     foreach($Days as $j => $CurrentDay)
                     {
                         // We check if the day is a working day or if the canteen is opened
                         $CurrentDayDate = $CurrentDay;
                         $NumCurrentDay = (integer)date('d', strtotime($CurrentDay));
                         $CurrentMonth = (integer)date('m', strtotime($CurrentDay));
                         $CurrentYear = date('Y', strtotime($CurrentDay));

                         // We compute the offset of the current day in the array of working days and holidays
                         // max() because for some years (ex : 2008), first day is in the previous year
                         $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $Year);

                         $iNumWeekDay = date('w', strtotime($CurrentDayDate));
                         if ($iNumWeekDay == 0)
                         {
                             // Sunday = 0 -> 7
                             $iNumWeekDay = 7;
                         }

                         if (!isset($ArrayTotalsDaysOfMonth[0][$CurrentDayDate]))
                         {
                             // To count the nb of nursery registrations for this day, for AM
                             $ArrayTotalsDaysOfMonth[0][$CurrentDayDate] = 0;
                         }

                         if ($iNbOtherTimeslots > 0)
                         {
                             $iNumOtherTimeslot = 0;
                             foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                             {
                                 $iNumOtherTimeslot++;

                                 if (!isset($ArrayTotalsDaysOfMonth[$iNumOtherTimeslot][$CurrentDayDate]))
                                 {
                                     $ArrayTotalsDaysOfMonth[$iNumOtherTimeslot][$CurrentDayDate] = 0;
                                 }
                             }
                         }

                         if (!isset($ArrayTotalsDaysOfMonth[$iNbTimeslots - 1][$CurrentDayDate]))
                         {
                             // To count the nb of nursery registrations for this day, for PM
                             $ArrayTotalsDaysOfMonth[$iNbTimeslots - 1][$CurrentDayDate] = 0;
                         }

                         // For AM
                         if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                             || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                  && ($GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][0])))
                         {

                             $CheckboxAllAM = $GLOBALS['LANG_AM'].' ';
                             $CheckboxAllAM .= generateInputField("chkNurseryRegitrationAMClass_$CurrentClass"."_$j", "checkbox", 1, 1,
                                                                  $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_CLASS_CHECK_AM_TIP"],
                                                                  $CurrentClass, FALSE, FALSE,
                                                                  "onClick = \"checkClassNurseryPlanningAM('$CurrentClass', '$j', '$CurrentDayDate');\"");

                             echo "<td class=\"PlanningCaptionsAM\">$CheckboxAllAM</td>";
                         }
                         else
                         {
                             $CheckboxAllAM = "&nbsp;";
                             echo "<td class=\"PlanningCaptionsAMHoliday\">$CheckboxAllAM</td>";
                         }

                         // For other timeslots if activated
                         if ($iNbOtherTimeslots > 0)
                         {
                             foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                             {
                                 if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                                     || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                         && ($CurrentParamsOtherTimeslot['WeekDays'][$iNumWeekDay - 1])))
                                 {
                                     $CheckboxAllOtherTimeslot = str_replace(array('-'), array('<br />'), $CurrentParamsOtherTimeslot['Label']).' ';
                                     $CheckboxAllOtherTimeslot .= generateInputField("chkNurseryRegitrationOtherTimeslotClass_$CurrentClass"."_$j"."_$ots", "checkbox", 1, 1,
                                                                                     $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_CLASS_CHECK_OTHER_TIMESLOT_TIP"],
                                                                                     $CurrentClass, FALSE, FALSE,
                                                                                     "onClick = \"checkClassNurseryPlanningOtherTimeslot('$CurrentClass', '$j', '$CurrentDayDate', '$ots');\"");

                                     echo "<td class=\"PlanningCaptionsOtherTimeslot\">$CheckboxAllOtherTimeslot</td>";
                                 }
                                 else
                                 {
                                     $CheckboxAllOtherTimeslot = "&nbsp;";
                                     echo "<td class=\"PlanningCaptionsOtherTimeslotHoliday\">$CheckboxAllOtherTimeslot</td>";
                                 }
                             }
                         }

                         // For PM
                         if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                             || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                  && ($GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][1])))
                         {
                             $CheckboxAllPM = $GLOBALS['LANG_PM'].' ';
                             $CheckboxAllPM .= generateInputField("chkNurseryRegitrationPMClass_$CurrentClass"."_$j", "checkbox", 1, 1,
                                                                  $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_CLASS_CHECK_PM_TIP"],
                                                                  $CurrentClass, FALSE, FALSE,
                                                                  "onClick = \"checkClassNurseryPlanningPM('$CurrentClass', '$j', '$CurrentDayDate');\"");

                             echo "<td class=\"PlanningCaptionsPM\">$CheckboxAllPM</td>";
                         }
                         else
                         {
                             $CheckboxAllPM = "&nbsp;";
                             echo "<td class=\"PlanningCaptionsPMHoliday\">$CheckboxAllPM</td>";
                         }
                     }

                     // For the total
                     echo "<td class=\"PlanningCaptions\">&nbsp;</td>\n</tr>\n";
                 }

                 if ($bIsFirstChildOfPlanning)
                 {
                     // First child of the planning : we set an anchor
                     echo "<tr id=\"FirstChild\">\n\t<td class=\"PlanningSupporter\">".$ArrayChildren["FamilyLastname"][$i]." ".$ArrayChildren["ChildFirstname"][$i]."</td>";
                     $bIsFirstChildOfPlanning = FALSE;
                 }
                 else
                 {
                     echo "<tr>\n\t<td class=\"PlanningSupporter\">".$ArrayChildren["FamilyLastname"][$i]." ".$ArrayChildren["ChildFirstname"][$i]."</td>";
                 }

                 $iNbNurseryRegistrations = 0;

                 // We get nursery registrations of the child (AM and/or PM) for this period ([$StartDate ; $EndDate])";
                 $ArrayNurseryRegistrationsOfChild = getNurseryRegistrations($DbConnection, $StartDate, $EndDate,
                                                                             'NurseryRegistrationForDate', $CurrentChildID,
                                                                             PLANNING_BETWEEN_DATES);

                 foreach($Days as $j => $CurrentDay)
                 {
                     // We check if the supporter is here this day
                     $CurrentDayDate = $CurrentDay;
                     $NumCurrentDay = (integer)date('d', strtotime($CurrentDay));
                     $CurrentMonth = (integer)date('m', strtotime($CurrentDay));
                     $CurrentYear = date('Y', strtotime($CurrentDay));

                     // We compute the offset of the current day in the array of working days and holidays
                     // max() because for some years (ex : 2008), first day is in the previous year
                     $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $Year);

                     // We check if the child has a nursery registration for this day (AM and/or PM)
                     // We check if the child has a canteen registration for this day
                     $iPosCurrentDayDate = -1;
                     if (isset($ArrayNurseryRegistrationsOfChild['NurseryRegistrationID']))
                     {
                         $iPosCurrentDayDate = array_search($CurrentDayDate, $ArrayNurseryRegistrationsOfChild['NurseryRegistrationForDate']);
                         if ($iPosCurrentDayDate === FALSE)
                         {
                             $iPosCurrentDayDate = -1;
                         }
                         else
                         {
                             // Compute the CRC of the planning
                             $PlanningCRC = $PlanningCRC ^ $ArrayNurseryRegistrationsOfChild['NurseryRegistrationID'][$iPosCurrentDayDate];
                         }
                     }

                     // We check if the planning can be edited
                     // First we check if the supporter is concerned by the retrictions delays
                     $bCanEdit = FALSE;
                     $ArraybCanEditTimeslots = array();
                     if (in_array($_SESSION['SupportMemberStateID'], $GLOBALS['CONF_NURSERY_DELAYS_RESTRICTIONS']))
                     {
                         // The supporter is concerned by restrictions

                         // Take into account the access mode
                         switch($cUserAccess)
                         {
                             case FCT_ACT_CREATE:
                             case FCT_ACT_UPDATE:
                                 // The supporter can't registre a child after x days
                                 $MinEditDateStamp = strtotime(date('Y-m-d',
                                                                    strtotime($GLOBALS['CONF_NURSERY_UPDATE_DELAY_PLANNING_REGISTRATION']." days ago")));
                                 $LimitEditDateStamp = $TodayDateStamp;  // The limit to edit the planning is today (not after)
                                 if ((strtotime($CurrentDayDate) >= $MinEditDateStamp) && (strtotime($CurrentDayDate) <= $LimitEditDateStamp))
                                 {
                                     $bCanEdit = TRUE;
                                 }
                                 break;

                             case FCT_ACT_PARTIAL_READ_ONLY:
                                 $MinEditDateStamp = $TodayDateStamp;  // The limit to edit the planning is today and after (but not past)
                                 $LimitEditDateStamp = strtotime(date('Y-m-d',
                                                                      strtotime('+'.$GLOBALS['CONF_NURSERY_REGISTER_DELAY_PLANNING_REGISTRATION']." days")));

                                 if ($GLOBALS['CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION'] > 0)
                                 {
                                     // We add a delay before the planning can be edited
                                     $MinEditDateStamp = strtotime(date('Y-m-d',
                                                                        strtotime('+'.$GLOBALS['CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION']." days")));
                                 }

                                 if ((strtotime($CurrentDayDate) > $MinEditDateStamp) && (strtotime($CurrentDayDate) <= $LimitEditDateStamp))
                                 {
                                     $bCanEdit = TRUE;
                                 }

                                 if (($GLOBALS['CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_USE_CAPACITIES'])
                                     && (strtotime($CurrentDayDate) > $TodayDateStamp) && (strtotime($CurrentDayDate) <= $MinEditDateStamp))
                                 {
                                     // Check the capacity for this date and this grade
                                     if (isset($ArrayDatesCapacities[$CurrentDayDate]))
                                     {
                                         for($ts = 0; $ts < $iNbTimeslots; $ts++)
                                         {
                                             if ($ArrayDatesCapacities[$CurrentDayDate][$ts][$ArrayCapacityGradesIndex[$ArrayChildren["ChildGrade"][$i]]]['Available'] > 0)
                                             {
                                                 $ArraybCanEditTimeslots[] = TRUE;
                                             }
                                             else
                                             {
                                                 $ArraybCanEditTimeslots[] = FALSE;
                                             }
                                         }
                                     }
                                     else
                                     {
                                         // No capacity stat : so no nursery registration for this date : we can edit
                                         $bCanEdit = TRUE;
                                     }
                                 }
                                 break;

                             case FCT_ACT_READ_ONLY:
                                 $bCanEdit = FALSE;
                                 break;
                         }
                     }
                     else
                     {
                         $bCanEdit = TRUE;
                     }

                     // We check if the day is a working day or if the nursery is opened
                     $iNumWeekDay = date('w', strtotime($CurrentDayDate));
                     if ($iNumWeekDay == 0)
                     {
                         // Sunday = 0 -> 7
                         $iNumWeekDay = 7;
                     }

                     // We check if the current date isn't an opened special day
                     $iPosOpenedSpecialDay = array_search($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']);

                     $TitleAM = '';
                     $TitlePM = '';
                     if ((!in_array($NumCurrentDay, $OpenedDays[$Offset])) && ($iPosOpenedSpecialDay === FALSE))
                     {
                         // Holiday
                         $StyleAM = "PlanningHolidayAM";
                         $TitleAM = '';
                         $CellContentAM = '&nbsp;';

                         // We check if it's a known holiday
                         if (array_key_exists($NumCurrentDay, $Holidays[$Offset]))
                         {
                             $TitleAM = $Holidays[$Offset][$NumCurrentDay];
                         }

                         // For other timeslots if activated
                         $ArraybNurseryOpenedOtherTimeslots = array();
                         if ($iNbOtherTimeslots > 0)
                         {
                             foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                             {
                                 $ArraybNurseryOpenedOtherTimeslots[$ots] = array(
                                                                                  'Style' => "PlanningHolidayOtherTimeslot",
                                                                                  'Title' => $TitleAM,
                                                                                  'CellContent' => '&nbsp;'
                                                                                 );
                             }
                         }

                         $StylePM = "PlanningHolidayPM";
                         $TitlePM = $TitleAM;
                         $CellContentPM = '&nbsp;';
                     }
                     else
                     {
                         $bNurseryOpenedAM = TRUE;
                         $bNurseryOpenedPM = TRUE;

                         // For other timeslots if activated
                         $ArraybNurseryOpenedOtherTimeslots = array();
                         if ($iNbOtherTimeslots > 0)
                         {
                             foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                             {
                                 $ArraybNurseryOpenedOtherTimeslots[$ots] = array(
                                                                                  'Opened' => TRUE,
                                                                                  'Style' => '',
                                                                                  'Title' => '',
                                                                                  'CellContent' => '',
                                                                                  'Checked' => FALSE
                                                                                 );

                                 if ((!$CurrentParamsOtherTimeslot['WeekDays'][$iNumWeekDay - 1]) && ($iPosOpenedSpecialDay === FALSE))
                                 {
                                     // Nursery not opened for this other timeslot
                                     $ArraybNurseryOpenedOtherTimeslots[$ots] = array(
                                                                                      'Opened' => FALSE,
                                                                                      'Style' => "PlanningSupporterOtherOtherTimeslot",
                                                                                      'Title' => $GLOBALS['LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_NURSERY_NOT_OPENED_OTHER_TIMESLOT'],
                                                                                      'CellContent' => '&nbsp;',
                                                                                      'Checked' => FALSE
                                                                                     );
                                 }
                             }
                         }

                         if ((!$GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][0]) && ($iPosOpenedSpecialDay === FALSE))
                         {
                             // Nursery not opened for this AM
                             $StyleAM = "PlanningSupporterOtherAM";
                             $TitleAM = $GLOBALS['LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_NURSERY_NOT_OPENED_AM'];
                             $CellContentAM = '&nbsp;';

                             $bNurseryOpenedAM = FALSE;
                         }

                         if ((!$GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][1]) && ($iPosOpenedSpecialDay === FALSE))
                         {
                             // Nursery not opened for this PM
                             $StylePM = "PlanningSupporterOtherPM";
                             $TitlePM = $GLOBALS['LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_NURSERY_NOT_OPENED_PM'];
                             $CellContentPM = '&nbsp;';

                             $bNurseryOpenedPM = FALSE;
                         }

                         if ((isset($ArrayNurseryRegistrationsOfChild['NurseryRegistrationID'][$iPosCurrentDayDate]))
                             && (!empty($ArrayNurseryRegistrationsOfChild['NurseryRegistrationID'][$iPosCurrentDayDate])))
                         {
                             $Value = "$CurrentDayDate#$CurrentClass#$CurrentChildID#"
                                      .$ArrayNurseryRegistrationsOfChild['NurseryRegistrationID'][$iPosCurrentDayDate];  // Day#Class#ChildID#NurseryRegistrationID

                             if ($bNurseryOpenedAM)
                             {
                                 // The child used the nursery...
                                 if (empty($ArrayNurseryRegistrationsOfChild['NurseryRegistrationForAM'][$iPosCurrentDayDate]))
                                 {
                                     // ...but not for AM
                                     $StyleAM = 'PlanningWorkingDayAM';
                                     $bChecked = FALSE;
                                 }
                                 else
                                 {
                                     // ...for AM
                                     $StyleAM = 'PlanningSupporterFormationAM';
                                     $TitleAM = $GLOBALS['LANG_NURSERY_AM_CHECKED'];
                                     $bChecked = TRUE;

                                     $iNbNurseryRegistrations++;
                                     $ArrayTotalsDaysOfMonth[0][$CurrentDayDate]++;
                                 }

                                 if (($bCanEdit) || ((isset($ArraybCanEditTimeslots[0])) && ($ArraybCanEditTimeslots[0])))
                                 {
                                     $TitleAM = '';
                                     $CellContentAM = generateInputField("chkNurseryRegitrationAM[]", "checkbox", 1, 1,
                                                                         $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_REGISTRATION_CHECK_AM_TIP"],
                                                                         $Value, FALSE, $bChecked,
                                                                         "onClick = \"checkChildDayNurseryPlanningAM('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID');\"");
                                 }
                                 else
                                 {
                                     $CellContentAM = generateStyledPicture($GLOBALS['CONF_PLANNING_LOCKED_ICON'], $TitleAM, '');
                                 }
                             }

                             // For other timeslots if activated
                             if ($iNbOtherTimeslots > 0)
                             {
                                 $iNumOtherTimeslot = 0;
                                 foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                                 {
                                     if ((isset($ArraybNurseryOpenedOtherTimeslots[$ots])) && ($ArraybNurseryOpenedOtherTimeslots[$ots]))
                                     {
                                         if ($ArraybNurseryOpenedOtherTimeslots[$ots]['Opened'])
                                         {
                                             // The child used the nursery...
                                             $bOtherTimeslotChecked = $ArrayNurseryRegistrationsOfChild['NurseryRegistrationOtherTimeslots'][$iPosCurrentDayDate] & $ArrayOtherTimeslotsPatterns[$ots];
                                             if (empty($bOtherTimeslotChecked))
                                             {
                                                 // ...but not for this timeslot
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Style'] = 'PlanningWorkingDayOtherTimeslot';
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Checked'] = FALSE;
                                             }
                                             else
                                             {
                                                 // ...for this timeslot
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Style'] = 'PlanningSupporterFormationOtherTimeslot';
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Title'] = $GLOBALS['LANG_NURSERY_AM_CHECKED'];
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Checked'] = TRUE;

                                                 $iNbNurseryRegistrations++;
                                                 $ArrayTotalsDaysOfMonth[$iNumOtherTimeslot + 1][$CurrentDayDate]++;  // Because [0] is AM timeslot !
                                             }

                                             if (($bCanEdit) || ((isset($ArraybCanEditTimeslots[$iNumOtherTimeslot + 1])) && ($ArraybCanEditTimeslots[$iNumOtherTimeslot + 1])))
                                             {
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Title'] = '';
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['CellContent'] = generateInputField("chkNurseryRegitrationOtherTimeslot".$ots."[]", "checkbox", 1, 1,
                                                                                                                              $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_REGISTRATION_CHECK_OTHER_TIMESLOT_TIP"],
                                                                                                                              $Value, FALSE, $ArraybNurseryOpenedOtherTimeslots[$ots]['Checked'],
                                                                                                                              "onClick = \"checkChildDayNurseryPlanningOtherTimeslot('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID', '$ots');\"");
                                             }
                                             else
                                             {
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['CellContent'] = generateStyledPicture($GLOBALS['CONF_PLANNING_LOCKED_ICON'],
                                                                                                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Title'], '');
                                             }
                                         }
                                     }

                                     $iNumOtherTimeslot++;
                                 }
                             }

                             if ($bNurseryOpenedPM)
                             {
                                 // The child used the nursery...
                                 if (empty($ArrayNurseryRegistrationsOfChild['NurseryRegistrationForPM'][$iPosCurrentDayDate]))
                                 {
                                     // ...but not for PM
                                     $StylePM = 'PlanningWorkingDayPM';
                                     $bChecked = FALSE;
                                 }
                                 else
                                 {
                                     // ...for PM : we check if the nursery registration is late
                                     if (empty($ArrayNurseryRegistrationsOfChild['NurseryRegistrationIsLate'][$iPosCurrentDayDate]))
                                     {
                                         // No delay
                                         $StylePM = 'PlanningSupporterFormationPM';
                                     }
                                     else
                                     {
                                         // There is a delay
                                         $StylePM = 'PlanningNurseryDelayPM';
                                     }

                                     $TitlePM = $GLOBALS['LANG_NURSERY_PM_CHECKED'];
                                     $bChecked = TRUE;

                                     $iNbNurseryRegistrations++;
                                     $ArrayTotalsDaysOfMonth[$iNbTimeslots - 1][$CurrentDayDate]++;
                                 }

                                 if (($bCanEdit) || ((isset($ArraybCanEditTimeslots[$iNbTimeslots - 1])) && ($ArraybCanEditTimeslots[$iNbTimeslots - 1])))
                                 {
                                     $TitlePM = '';
                                     $CellContentPM = generateInputField("chkNurseryRegitrationPM[]", "checkbox", 1, 1,
                                                                         $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_REGISTRATION_CHECK_PM_TIP"],
                                                                         $Value, FALSE, $bChecked,
                                                                         "onClick = \"checkChildDayNurseryPlanningPM('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID');\"");
                                 }
                                 else
                                 {
                                     $CellContentPM = generateStyledPicture($GLOBALS['CONF_PLANNING_LOCKED_ICON'], $TitlePM, '');
                                 }
                             }
                         }
                         else
                         {
                             // No nursery registratiron for the child for this day (AM and PM)
                             $Value = "$CurrentDayDate#$CurrentClass#$CurrentChildID#0";  // Day#Class#ChildID#0
                             if ($bNurseryOpenedAM)
                             {
                                 if (($bCanEdit) || ((isset($ArraybCanEditTimeslots[0])) && ($ArraybCanEditTimeslots[0])))
                                 {
                                     $StyleAM = "PlanningWorkingDayAM";
                                     $TitleAM = "";
                                     $CellContentAM = generateInputField("chkNurseryRegitrationAM[]", "checkbox", 1, 1,
                                                                         $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_REGISTRATION_CHECK_AM_TIP"],
                                                                         $Value, FALSE, FALSE,
                                                                         "onClick = \"checkChildDayNurseryPlanningAM('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID');\"");
                                 }
                                 else
                                 {
                                     $StyleAM = 'AowPlnNotAvailableAM';
                                     $TitleAM = $GLOBALS["LANG_PLANNING_NOT_EDITABLE"];
                                     $CellContentAM = '&nbsp;';
                                 }
                             }

                             // For other timeslots if activated
                             if ($iNbOtherTimeslots > 0)
                             {
                                 $iNumOtherTimeslot = 0;
                                 foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                                 {
                                     if ((isset($ArraybNurseryOpenedOtherTimeslots[$ots])) && ($ArraybNurseryOpenedOtherTimeslots[$ots]))
                                     {
                                         if ($ArraybNurseryOpenedOtherTimeslots[$ots]['Opened'])
                                         {
                                             if (($bCanEdit) || ((isset($ArraybCanEditTimeslots[$iNumOtherTimeslot + 1])) && ($ArraybCanEditTimeslots[$iNumOtherTimeslot + 1])))
                                             {
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Style'] = "PlanningWorkingDayOtherTimeslot";
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Title'] = "";
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['CellContent'] = generateInputField("chkNurseryRegitrationOtherTimeslot".$ots."[]", "checkbox", 1, 1,
                                                                                                                              $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_REGISTRATION_CHECK_OTHER_TIMESLOT_TIP"],
                                                                                                                              $Value, FALSE, FALSE,
                                                                                                                              "onClick = \"checkChildDayNurseryPlanningOtherTimeslot('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID', '$ots');\"");
                                             }
                                             else
                                             {
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Style'] = 'AowPlnNotAvailableOtherTimeslot';
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['Title'] = $GLOBALS["LANG_PLANNING_NOT_EDITABLE"];
                                                 $ArraybNurseryOpenedOtherTimeslots[$ots]['CellContent'] = '&nbsp;';
                                             }
                                         }
                                     }

                                     $iNumOtherTimeslot++;
                                 }
                             }

                             if ($bNurseryOpenedPM)
                             {
                                 if (($bCanEdit) || ((isset($ArraybCanEditTimeslots[$iNbTimeslots - 1])) && ($ArraybCanEditTimeslots[$iNbTimeslots - 1])))
                                 {
                                     $StylePM = "PlanningWorkingDayPM";
                                     $TitlePM = "";
                                     $CellContentPM = generateInputField("chkNurseryRegitrationPM[]", "checkbox", 1, 1,
                                                                         $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_REGISTRATION_CHECK_PM_TIP"],
                                                                         $Value, FALSE, FALSE,
                                                                         "onClick = \"checkChildDayNurseryPlanningPM('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID');\"");
                                 }
                                 else
                                 {
                                     $StylePM = 'AowPlnNotAvailablePM';
                                     $TitlePM = $GLOBALS["LANG_PLANNING_NOT_EDITABLE"];
                                     $CellContentPM = '&nbsp;';
                                 }
                             }
                         }
                     }

                     echo "<td class=\"$StyleAM\" title=\"$TitleAM\">$CellContentAM</td>";

                     // For other timeslots if activated
                     if ($iNbOtherTimeslots > 0)
                     {
                         foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                         {
                             echo "<td class=\"".$ArraybNurseryOpenedOtherTimeslots[$ots]['Style']."\" title=\"".$ArraybNurseryOpenedOtherTimeslots[$ots]['Title']
                                  ."\">".$ArraybNurseryOpenedOtherTimeslots[$ots]['CellContent']."</td>";
                         }
                     }

                     echo "<td class=\"$StylePM\" title=\"$TitlePM\">$CellContentPM</td>";
                 }

                 // Total of nursery registrations for the child
                 echo "<td class=\"PlanningTotalChild\">$iNbNurseryRegistrations</td>\n</tr>\n";
             }

             // Display stats for the month
             echo "<tr>\n\t<td class=\"PlanningSplitMoreMeals\" colspan=\"".($iNbTimeslots * $NbDays + 2)."\">&nbsp;</td>\n</tr>\n";

             // Display total of each day
             echo "<tr>\n\t<td class=\"PlanningTotalCaption\">".$GLOBALS['LANG_TOTAL']."</td>";
             foreach($Days as $j => $CurrentDay)
             {
                 // We check if the day is a working day or if the nursery is opened
                 $CurrentDayDate = $CurrentDay;
                 $NumCurrentDay = (integer)date('d', strtotime($CurrentDay));
                 $CurrentMonth = (integer)date('m', strtotime($CurrentDay));
                 $CurrentYear = date('Y', strtotime($CurrentDay));

                 // We compute the offset of the current day in the array of working days and holidays
                 // max() because for some years (ex : 2008), first day is in the previous year
                 $Offset = $CurrentMonth + 12 * max(0, $CurrentYear - $Year);

                 $iNumWeekDay = date('w', strtotime($CurrentDayDate));
                 if ($iNumWeekDay == 0)
                 {
                     // Sunday = 0 -> 7
                     $iNumWeekDay = 7;
                 }

                 // We check if the current date isn't an opened special day
                 $iPosOpenedSpecialDay = array_search($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']);

                 if ((in_array($NumCurrentDay, $OpenedDays[$Offset])) || ($iPosOpenedSpecialDay !== FALSE))
                 {
                     // Stats for AM
                     if (($GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][0]) || ($iPosOpenedSpecialDay !== FALSE))
                     {
                         $QuantityAM = 0;
                         $StyleAM = "PlanningTotalDayAM";
                         if (isset($ArrayTotalsDaysOfMonth[0][$CurrentDayDate]))
                         {
                             $QuantityAM = $ArrayTotalsDaysOfMonth[0][$CurrentDayDate];
                         }
                     }
                     else
                     {
                         // Nursery not opened
                         $QuantityAM = "&nbsp;";
                         $StyleAM = "PlanningHolidayAM";
                     }

                     echo "<td class=\"$StyleAM\">$QuantityAM</td>";

                     // Stats for other timeslots if activated
                     if ($iNbOtherTimeslots > 0)
                     {
                         $iNumOtherTimeslot = 1;
                         foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                         {
                             if (($CurrentParamsOtherTimeslot['WeekDays'][$iNumWeekDay - 1]) || ($iPosOpenedSpecialDay !== FALSE))
                             {
                                 $QuantityOtherTimeslot = 0;
                                 $StyleOtherTimeslot = "PlanningTotalDayOtherTimeslot";
                                 if (isset($ArrayTotalsDaysOfMonth[$iNumOtherTimeslot][$CurrentDayDate]))
                                 {
                                     $QuantityOtherTimeslot = $ArrayTotalsDaysOfMonth[$iNumOtherTimeslot][$CurrentDayDate];
                                 }
                             }
                             else
                             {
                                 // Nursery not opened
                                 $QuantityOtherTimeslot = "&nbsp;";
                                 $StyleOtherTimeslot = "PlanningHolidayOtherTimeslot";
                             }

                             echo "<td class=\"$StyleOtherTimeslot\">$QuantityOtherTimeslot</td>";

                             $iNumOtherTimeslot++;
                         }
                     }

                     // Stats for PM
                     if (($GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][1]) || ($iPosOpenedSpecialDay !== FALSE))
                     {
                         $QuantityPM = 0;
                         $StylePM = "PlanningTotalDayPM";
                         if (isset($ArrayTotalsDaysOfMonth[$iNbTimeslots - 1][$CurrentDayDate]))
                         {
                             $QuantityPM = $ArrayTotalsDaysOfMonth[$iNbTimeslots - 1][$CurrentDayDate];
                         }
                     }
                     else
                     {
                         // Nursery not opened
                         $QuantityPM = "&nbsp;";
                         $StylePM = "PlanningHolidayPM";
                     }

                     echo "<td class=\"$StylePM\">$QuantityPM</td>";
                 }
                 else
                 {
                     echo "<td class=\"PlanningHolidayAM\">&nbsp;</td>";

                     // Stats for other timeslots if activated
                     if ($iNbOtherTimeslots > 0)
                     {
                         foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                         {
                             echo "<td class=\"PlanningHolidayOtherTimeslot\">&nbsp;</td>";
                         }
                     }

                     echo "<td class=\"PlanningHolidayPM\">&nbsp;</td>";
                 }
             }

             $iTotalNursery = 0;
             foreach($ArrayTotalsDaysOfMonth as $tdm => $CurrentTotalArray)
             {
                 $iTotalNursery += array_sum($ArrayTotalsDaysOfMonth[$tdm]);
             }
             echo "<td class=\"PlanningTotalMonth\">$iTotalNursery</td>\n</tr>\n";

             // Close the table
             echo "</tbody>\n</table>\n";

             // Display the toolbar
             openParagraph('toolbar');

             switch($ViewType)
             {
                 case PLANNING_WEEKS_VIEW:
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$PreviousWeek&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     echo str_repeat("&nbsp;", 4);

                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$NextWeek&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
                     break;


                 case PLANNING_MONTH_VIEW:
                 default:
                     // Display previous and next links for "month" view
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$PreviousMonth&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     echo str_repeat("&nbsp;", 4);

                     displayStyledLinkText($GLOBALS["LANG_NEXT"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$NextMonth&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
             }

             closeParagraph();

             // Hidden input field for CRC
             insertInputField("hidPlanningCRC", "hidden", "", "", "", md5($PlanningCRC));

             displayBR(2);

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();

             // Display the legends of the icons
             echo generateLegendsOfVisualIndicators(
                                                    array(
                                                          array("PlanningSupporterFormation", $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_CHILD_REGISTRED"]),
                                                          array("PlanningNurseryDelayPM", $GLOBALS['LANG_NURSERY_DELAY']),
                                                          array("NurseryPlanningCheckBoxError", $GLOBALS['LANG_NURSERY_NOT_SAVED']),
                                                          array("PlanningSupporterOther", $GLOBALS["LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_NURSERY_NOT_OPENED"]),
                                                          array("PlanningHoliday", $GLOBALS["LANG_HOLIDAY"]),
                                                          array("AowPlnNotAvailable", $GLOBALS["LANG_PLANNING_NOT_EDITABLE"])
                                                         ),
                                                    CSS_STYLE
                                                   );
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the for to record nursery delays of families for a month, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-02-03
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Concerned month [1..12]
 * @param $Year                 Integer               Concerned year
 * @param $ChildID              Integer               Concerned child by nursery delay [1..n]
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create nursery delays
 */
 function displayNurseryDelayForm($DbConnection, $ProcessFormPage, $Month, $Year, $ChildID = NULL, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a nursery registratrion
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE)))
         {
             // Open a form
             openForm("FormNurseryDelay", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "VerificationNurseryDelay('".$GLOBALS["LANG_ERROR_JS_NURSERY_DELAY_CHILD"]."', '".$GLOBALS['LANG_ERROR_JS_NURSERY_DELAY_REGISTRATION']."')");

             $StartDate = sprintf("%04d-%02d-01", $Year, $Month);
             $EndDate = date("Y-m-t", strtotime($StartDate));
             $CurrentSchoolYear = getSchoolYear($StartDate);

             // Display the months list to change the planning to display
             openParagraph('toolbar');

             $GeneratedYears = range(2009, 2037);

             // <<< Months SELECTFIELD >>>
             $MonthsList = generateSelectField("lMonth", range(1, 12), $GLOBALS["CONF_PLANNING_MONTHS"], $Month,
                                               "onChangeSelectedMonth(this.value)");

             // <<< Year SELECTFIELD >>>
             $YearsList = generateSelectField("lYear", $GeneratedYears, $GeneratedYears, $Year, "onChangeSelectedYear(this.value)");

             // Compute the previous month
             if ($Month == 1)
             {
                 $PreviousMonth = 12;
                 $PreviousYear = $Year - 1;
             }
             else
             {
                 $PreviousMonth = $Month - 1;
                 $PreviousYear = $Year;
             }
             displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lMonth=$PreviousMonth&amp;lYear=$PreviousYear",
                                   'prev', $GLOBALS["LANG_PREVIOUS"]);

             // Display the year list to change the nursery registration to get
             echo " $MonthsList $YearsList ";

             // Compute the next month
             if ($Month == 12)
             {
                 $NextMonth = 1;
                 $NextYear = $Year + 1;
             }
             else
             {
                 $NextMonth = $Month + 1;
                 $NextYear = $Year;
             }
             displayStyledLinkText($GLOBALS["LANG_NEXT"], "$ProcessFormPage?lMonth=$NextMonth&amp;lYear=$NextYear",
                                   'next', $GLOBALS["LANG_NEXT"]);

             closeParagraph();

             displayBR(1);

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_NURSERY_DELAY"], "Frame", "Frame", "DetailsNews");

             // Get children nursery registrations for the selected month/year
             $ArrayChildren = getChildrenListForNurseryPlanning($DbConnection, $StartDate, $EndDate, "ChildClass, FamilyLastname",
                                                                FALSE, PLANNING_BETWEEN_DATES);

             $ChildrenList = "";
             if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
             {
                 $ArrayData = array(0 => '-');

                 // We group children by classroom
                 foreach($ArrayChildren['ChildID'] as $c => $CurrentChildID)
                 {
                     $ArrayData[$GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$ArrayChildren['ChildClass'][$c]]][$CurrentChildID] = $ArrayChildren['FamilyLastname'][$c].' '.$ArrayChildren['ChildFirstname'][$c];
                 }

                 if (empty($ChildID))
                 {
                     $ItemSelected = 0;
                 }
                 else
                 {
                     $ItemSelected = $ChildID;
                 }

                 $ChildrenList = generateOptGroupSelectField("lChildID", $ArrayData, $ItemSelected, 'onChangeSelectedChild(this.value)');

                  unset($ArrayData);
             }
             else
             {
                 // No child
                 $ChildrenList = generateSelectField("lChildID", array(0), array("-"), 0);
             }

             // Get PM nursery registrations of the selected child
             if (empty($ChildID))
             {
                 // No child selected
                 $NurseryRegistrationsList = generateSelectField("lNurseryRegistrationID", array(0), array("-"), 0);
             }
             else
             {
                 // There is a child selected : we get his PM nursery registrations with no delay
                 $ArrayDates = array(0 => '-');
                 $ArrayNurseryRegistrations = getNurseryRegistrations($DbConnection, $StartDate, $EndDate, 'NurseryRegistrationForDate',
                                                                      $ChildID, PLANNING_BETWEEN_DATES,
                                                                      array(
                                                                            'ForPM' => array(1),
                                                                            'IsLate' => array(0)
                                                                           ));

                 if ((isset($ArrayNurseryRegistrations['NurseryRegistrationID'])) && (!empty($ArrayNurseryRegistrations['NurseryRegistrationID'])))
                 {
                     foreach($ArrayNurseryRegistrations['NurseryRegistrationID'] as $nr => $CurrentID)
                     {
                         $ArrayDates[$CurrentID] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                        strtotime($ArrayNurseryRegistrations['NurseryRegistrationForDate'][$nr]));
                     }
                 }

                 $NurseryRegistrationsList = generateSelectField("lNurseryRegistrationID", array_keys($ArrayDates),
                                                                 array_values($ArrayDates), 0);

                 unset($ArrayDates);
             }

             // Display the form
             echo "<table id=\"NurseryDelaysChildren\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CHILDREN"]."</td><td class=\"Value\">$ChildrenList</td><td class=\"Label\">".$GLOBALS["LANG_NURSERIES"]."</td><td class=\"Value\">$NurseryRegistrationsList</td>\n</tr>\n";
             echo "</table>\n";
             closeStyledFrame();

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();

             // We get nursery delays of the month
             $ArrayNurseryRegistrations = getNurseryRegistrations($DbConnection, $StartDate, $EndDate,
                                                                  'NurseryRegistrationForDate, FamilyLastname, ChildFirstname',
                                                                   NULL, PLANNING_BETWEEN_DATES,
                                                                   array(
                                                                         'ForPM' => array(1),
                                                                         'IsLate' => array(1)
                                                                        ));

             if ((isset($ArrayNurseryRegistrations['NurseryRegistrationID'])) && (!empty($ArrayNurseryRegistrations['NurseryRegistrationID'])))
             {
                 displayBR(2);
                 echo "<dl class=\"NurseryDelaysOfMonth\">\n<dt>".$GLOBALS['LANG_NURSERY_DELAYS_OF_MONTH']." :</dt>\n";

                 $PreviousDate = NULL;
                 foreach($ArrayNurseryRegistrations['NurseryRegistrationID'] as $nr => $CurrentID)
                 {
                     if ((!is_null($PreviousDate)) && ($PreviousDate != $ArrayNurseryRegistrations['NurseryRegistrationForDate'][$nr]))
                     {
                         // The date changes : we put a separator
                         echo "<dd>&nbsp;</dd>\n";
                     }

                     echo "<dd>".date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                      strtotime($ArrayNurseryRegistrations['NurseryRegistrationForDate'][$nr]))." : "
                                .$ArrayNurseryRegistrations['FamilyLastname'][$nr]." ".$ArrayNurseryRegistrations['ChildFirstname'][$nr]
                                ."</dd>\n";

                     $PreviousDate = $ArrayNurseryRegistrations['NurseryRegistrationForDate'][$nr];
                 }
                 echo "</dl>";
             }
         }
         else
         {
             // The supporter isn't allowed to create a nursery delay
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the nursery synthesis of the selected day, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *    - 2020-03-16 : v1.1. Taken into account CONF_NURSERY_OTHER_TIMESLOTS
 *
 * @since 2017-09-20
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Day                  Integer               Day to display [1..31]
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $TypeOfDisplay        Integer               Type of display : children registered or not at the nursery [0..n]
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the nursery synthesis
 */
 function displayNurseryDaySynthesisForm($DbConnection, $ProcessFormPage, $Day, $Month, $Year, $TypeOfDisplay = 0, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the synthesis
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             // Open a form
             openForm("FormViewSynthesis", "post", "$ProcessFormPage", "", "");

             // Display the days list to change the planning to display
             openParagraph('toolbar');
             echo generateStyledPictureHyperlink($GLOBALS["CONF_PRINT_BULLET"], "javascript:PrintWebPage()", $GLOBALS["LANG_PRINT"], "PictureLink", "");
             closeParagraph();

             // Display the day list : we get the older date of the nursery registrations
             openParagraph('toolbar');

             // We generate the list of the weeks from the older week to the current day
             $SelectedDay = "$Year-$Month-$Day";
             $MinDate = date("Y-m-d", strtotime(getNurseryRegistrationMinDate($DbConnection)));
             $MinDay = date("W", strtotime($MinDate));
             if ($MinDay == '')
             {
                 $MinDay = date("d");
             }
             $StartDate = date("Y-m-d", strtotime("$MinDay days", strtotime(date("Y", strtotime($MinDate))."-01-01")));

             $MaxDate = date("Y-m-d", strtotime(getNurseryRegistrationMaxDate($DbConnection)));
             if (empty($MaxDate))
             {
                 $MaxDate = date("Y-m-d");
             }
             else
             {
                 // Keep the max date (Maxdate or current date)
                 if (strtotime($MaxDate) < strtotime(date("Y-m-d")))
                 {
                     $MaxDate = date("Y-m-d");
                 }
             }

             $ArrayDays = array_keys(getPeriodIntervalsStats($StartDate, $MaxDate, "d"));
             $ArrayDaysSize = count($ArrayDays);
             $ArrayDaysLabels = array();
             for($i = 0 ; $i < $ArrayDaysSize ; $i++)
             {
                 $iNumWeekDay = date('w', strtotime($ArrayDays[$i]));
                 if ($iNumWeekDay == 0)
                 {
                     // Sunday = 0 -> 7
                     $iNumWeekDay = 7;
                 }

                 if ((jour_ferie(strtotime($ArrayDays[$i])) == NULL) && ($GLOBALS['CONF_NURSERY_OPENED_WEEK_DAYS'][$iNumWeekDay - 1][1]))
                 {
                     $ArrayDaysLabels[$ArrayDays[$i]] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($ArrayDays[$i]));
                 }
             }

             // Get the right school year to use : we use the start date of the displayed planning
             $CurrentSchoolYear = getSchoolYear($SelectedDay);

             // We get the number of other nursery timeslots
             $iNbOtherTimeslots = 0;
             $ArrayOtherTimeslotsPatterns = array();
             if ((isset($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear]))
                 && (!empty($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear])))
             {
                 // This school year has some other timeslots (more than AM and PM timeslots)
                 $iNbOtherTimeslots = count($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear]);
                 $iPos = 0;
                 foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                 {
                     $ArrayOtherTimeslotsPatterns[$ots] = pow(2, $iPos);
                     $iPos++;
                 }
             }

             echo generateSelectField("lDay", array_keys($ArrayDaysLabels), array_values($ArrayDaysLabels), $SelectedDay,
                                      "onChangeNurseryDaySynthesisDay(this.value)");

             // Display the list of displays : children registered at the nursery or not
             echo generateSelectField("lDisplayType", array(0, 1),
                                      array(
                                            $GLOBALS['LANG_SUPPORT_NURSERY_DAY_SYNTHESIS_PAGE_DISPLAY_LIST_AT_NURSERY_ITEM'],
                                            $GLOBALS['LANG_SUPPORT_NURSERY_DAY_SYNTHESIS_PAGE_DISPLAY_LIST_DONT_AT_NURSERY_ITEM']
                                           ), $TypeOfDisplay, "onChangeNurseryDaySynthesisDisplayType(this.value)");

             closeParagraph();

             switch($TypeOfDisplay)
             {
                 case 1:
                     // Display children don't registered at the nursery for the selected day
                     $ArrayNurseryRegistrations = getNotNurseryRegistrations($DbConnection, $SelectedDay, $SelectedDay,
                                                                             "ChildClass, FamilyLastname, ChildFirstname",
                                                                             NULL, DATES_INCLUDED_IN_PLANNING, array());
                     break;

                 case 0:
                 default:
                     // Display children registered at the nursery for the selected day
                     $ArrayNurseryRegistrations = getNurseryRegistrations($DbConnection, $SelectedDay, $SelectedDay,
                                                                          "ChildClass, FamilyLastname, ChildFirstname",
                                                                          NULL, PLANNING_BETWEEN_DATES, array());
                     break;
             }

             if ((isset($ArrayNurseryRegistrations['NurseryRegistrationID'])) && (count($ArrayNurseryRegistrations['NurseryRegistrationID']) > 0))
             {
                 displayBR(2);
                 echo "<table class=\"CanteenSynthesisTable\" cellspacing=\"0\">\n";

                 $PreviousClassroom = NULL;
                 foreach($ArrayNurseryRegistrations['NurseryRegistrationID'] as $cr => $CurrentID)
                 {
                     if ((is_null($PreviousClassroom)) || ($PreviousClassroom != $ArrayNurseryRegistrations['ChildClass'][$cr]))
                     {
                         // We display the name of the classroom
                         echo "<tr>\n\t<th class=\"Caption\" colspan=\"".(5 + $iNbOtherTimeslots)."\">"
                              .$GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$ArrayNurseryRegistrations['ChildClass'][$cr]]
                              ."</th>\n</tr>\n";

                         // Display the header of the table
                         echo "<tr>\n\t<th>".$GLOBALS['LANG_FAMILY_LASTNAME']."</th><th>".$GLOBALS['LANG_CHILD_FIRSTNAME']
                              ."</th><th>".$GLOBALS['LANG_CHILD_GRADE']."</th><th>".$GLOBALS['LANG_AM']."</th>";

                         if ($iNbOtherTimeslots > 0)
                         {
                             foreach($GLOBALS['CONF_NURSERY_OTHER_TIMESLOTS'][$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
                             {
                                 echo "<th>".$CurrentParamsOtherTimeslot['Label']."</th>";
                             }
                         }

                         echo "<th>".$GLOBALS['LANG_PM']."</th>\n</tr>\n";

                         $PreviousClassroom = $ArrayNurseryRegistrations['ChildClass'][$cr];
                     }

                     echo "<tr>\n\t<td>".$ArrayNurseryRegistrations['FamilyLastname'][$cr]."</td><td>"
                          .$ArrayNurseryRegistrations['ChildFirstname'][$cr]."</td><td>"
                          .$GLOBALS['CONF_GRADES'][$ArrayNurseryRegistrations['ChildGrade'][$cr]]."</td>";

                     if ($ArrayNurseryRegistrations['NurseryRegistrationForAM'][$cr] == 1)
                     {
                         echo "<td>".$GLOBALS['LANG_YES']."</td>";
                     }
                     else
                     {
                         echo "<td>&nbsp;</td>";
                     }

                     if ($iNbOtherTimeslots > 0)
                     {
                         foreach($ArrayOtherTimeslotsPatterns as $ots => $CurrentPattern)
                         {
                             if ($ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][$cr] & $CurrentPattern)
                             {
                                  echo "<td>".$GLOBALS['LANG_YES']."</td>";
                             }
                             else
                             {
                                 echo "<td>&nbsp;</td>";
                             }
                         }
                     }

                     if ($ArrayNurseryRegistrations['NurseryRegistrationForPM'][$cr] == 1)
                     {
                         echo "<td>".$GLOBALS['LANG_YES']."</td>";
                     }
                     else
                     {
                         echo "<td>&nbsp;</td>";
                     }

                     echo "\n</tr>\n";
                 }

                 echo "</table>\n";
             }
             else
             {
                 // No nursery registration for this day
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_SUPPORT_NURSERY_DAY_SYNTHESIS_PAGE_NO_REGISTRATION'];
                 closeParagraph();
             }

             insertInputField("hidDay", "hidden", "", "", "", "$Year-$Month-$Day");  // Current selected day
             insertInputField("hidDisplayType", "hidden", "", "", "", "$TypeOfDisplay");  // Current selected type of display
             closeForm();

             // Open a form to print the day synthesis
             openForm("FormPrintAction", "post", "$ProcessFormPage?lDay=$Year-$Month-$Day&amp;lDisplayType=$TypeOfDisplay", "", "");
             insertInputField("hidOnPrint", "hidden", "", "", "", "0");
             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }
?>