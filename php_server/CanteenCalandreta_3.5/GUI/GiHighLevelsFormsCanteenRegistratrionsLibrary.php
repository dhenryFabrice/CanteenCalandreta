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
 * of the canteen.
 *
 * @author Christophe Javouhey
 * @version 3.5
 * @since 2012-01-28
 */


/**
 * Display the planning of the canteen of each child for a month, in the current web page, in the
 * graphic interface in XHTML
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
 *                                                    allowed to create or update canteen registrations
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some canteen registratrions
 */
 function displayCanteenPlanningByMonthForm($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array(), $ViewsRestrictions = array())
 {
     // Compute start date and end date of the month
     $StartDate = sprintf("%04d-%02d-01", $Year, $Month);
     $EndDate = date("Y-m-t", strtotime($StartDate));
     $SelectedDate = $StartDate;

     displayCanteenPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, PLANNING_MONTH_VIEW,
                                $AccessRules, $ViewsRestrictions);
 }


/**
 * Display the planning of the canteen of each child for a week, in the current web page, in the
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
 *                                                    allowed to create or update canteen registrations
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some canteen registratrions
 */
 function displayCanteenPlanningByWeeksForm($DbConnection, $ProcessFormPage, $Week, $Year, $AccessRules = array(), $ViewsRestrictions = array())
 {
     // Compute start date and end date of the month
     $StartDate = getFirstDayOfWeek($Week, $Year);

     // N weeks + 6 days (first day of week is a monday, so the last is a sunday)
     $EndDate = date("Y-m-d", strtotime('+6 days',
                                        strtotime('+'.($GLOBALS['CONF_PLANNING_WEEKS_TO_DISPLAY'] - 1).' week',
                                                  strtotime($StartDate))));
     $SelectedDate = $StartDate;

     displayCanteenPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, PLANNING_WEEKS_VIEW,
                                $AccessRules, $ViewsRestrictions);
 }


/**
 * Display the planning of the canteen of each child, for a given start date and end date,
 * in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 3.6
 *     - 2012-07-10 : patch a bug about desactivated children and new children for an old period,
 *                    allow an overflow for the content of the planning
 *     - 2013-01-25 : generic function allowing display the planning for dates between a start date
 *                    and an end date
 *     - 2013-02-11 : patch a bug about desactivated children when user connected with an account concerned
 *                    by the PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN access restriction
 *     - 2013-06-21 : taken into account the new structure of the CONF_CLASSROOMS variable
 *                    (includes school year)
 *     - 2013-08-30 : patch a bug about school holidays not taken into account in $Holidays
 *     - 2013-10-25 : taken into account the opened special days
 *     - 2013-11-28 : remove hidden input fields to increase perfs to display the planning
 *     - 2014-02-25 : add an anchor on the first child
 *     - 2014-05-22 : add a hidden field for CRC and get canteen registrations in the loop of each child
 *                    and not in the loop of each day of each child
 *     - 2015-02-09 : display a warning for days nb canteen registrations > $CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS
 *     - 2015-10-06 : display meals without pork and taken into account the FamilyID field of SupportMembers table
 *     - 2016-06-20 : remove htmlspecialchars() function
 *     - 2017-11-07 : taken into account $CONF_MEAL_TYPES and display stats about packed lunches
 *     - 2019-09-17 : display a waiting message when the page is loading
 *
 * @since 2012-01-28
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $StartDate            Date                  Start date of the planning (YYYY-mm-dd format)
 * @param $EndDate              Date                  End date of the planning (YYYY-mm-dd format)
 * @param $SelectedDate         Date                  Selected date (YYYY-mm-dd format)
 * @param $ViewType             Integer               Type of view to display (month, week, day)
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update canteen registrations
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some canteen registratrions
 */
 function displayCanteenPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, $ViewType = PLANNING_MONTH_VIEW, $AccessRules = array(), $ViewsRestrictions = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a canteen registratrion
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

         $Year = date('Y', strtotime($SelectedDate));
         $Month = date('m', strtotime($SelectedDate));
         $Day = date('d', strtotime($SelectedDate));
         $Week = date('W', strtotime($SelectedDate));
         $YearOfWeek = date('o', strtotime($SelectedDate));

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
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
                     $GeneratedYears = range(2009, 2037);
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

             /* We get the canteen registrations for the current period (week, month...) to found the children.
                We get too all activated chidren to have the complete list of all children */

             // We check if the logged supporter can view all canteen registrations or a limited view
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
                             // Check if the child is activated between start date and end date
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
                     // View all canteen registrations
                     $ArrayChildren = getChildrenListForCanteenPlanning($DbConnection, $StartDate, $EndDate, "ChildClass, FamilyLastname",
                                                                        FALSE, FALSE, PLANNING_BETWEEN_DATES);
                     $UserAccessStyle = 'Scroll';

                     // Display a waiting message when the page is loading
                     echo "<div id=\"WaitingLoadingPageMsg\" class=\"WaitingLoadingPageMsg\"><p>".$GLOBALS['LANG_WAITING_PAGE_LOADING']."</p></div>";
                     break;
             }

             // Get days for which the number of canteen registrations is > $CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS
             $ArrayWarningCanteenRegistrations = array();
             if ($GLOBALS['CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS'] > 0)
             {
                 $ArrayWarningCanteenRegistrations = getNbCanteenRegistrations($DbConnection, $StartDate, $EndDate,
                                                                               array(GROUP_BY_FOR_DATE_BY_DAY), NULL, FALSE,
                                                                               PLANNING_BETWEEN_DATES,
                                                                               array("NbCanteenregistrations" => array(">=", $GLOBALS['CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS'])));
             }

             // We display the caption of the planning : the caption of the period (month, week...), the days and totals
             echo "<table id=\"CanteenPlanning\" class=\"Planning\" cellspacing=\"0\">\n<thead class=\"$UserAccessStyle\">\n";
             echo "<tr>\n\t<th class=\"PlanningMonthCaption\">$PlanningViewTypeCaption</th>";
             foreach($Days as $i => $CurrentDay)
             {
                 // Display the first letter of the day (monday -> M)
                 $Prefix = '';
                 $CurrentDayDate = $CurrentDay;
                 $NumCurrentDay = (integer)date('d', strtotime($CurrentDay));
                 $CurrentMonth = (integer)date('m', strtotime($CurrentDay));
                 $CurrentYear = date('Y', strtotime($CurrentDay));
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

                 // Check if the day has a number of canteen registrations over the warning limit
                 if ((isset($ArrayWarningCanteenRegistrations['ForDayDate'])) && (in_array($CurrentDayDate, $ArrayWarningCanteenRegistrations['ForDayDate'])))
                 {
                     // There is too many canteen registrations for this day : display a warning
                     echo "<th class=\"PlanningCaptionsWarning\" title=\"".$GLOBALS['LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_WARNING_NB_CANTEENS_TIP']
                          ."\">$Prefix ".sprintf("%02u", $NumCurrentDay)." !!</th>";
                 }
                 else
                 {
                     // No too many canteen registrations for this day
                     echo "<th class=\"PlanningCaptions\">$Prefix ".sprintf("%02u", $NumCurrentDay)."</th>";
                 }
             }
             echo "<th class=\"PlanningTotalCaption\">".$GLOBALS['LANG_TOTAL']."</th>\n</tr></thead>\n<tbody class=\"$UserAccessStyle\">\n";

             // We display the canteen registrations of each child
             $CurrentClass = NULL;
             $TodayDateStamp = strtotime(date('Y-m-d'));
             $TodayCurrentTime = strtotime(date('Y-m-d H:i:s'));
             $LimitEditDateStamp = strtotime(date('Y-m-t',
                                             strtotime("+".($GLOBALS['CONF_CANTEEN_NB_MONTHS_PLANNING_REGISTRATION'] - 1)." months")));

             // Get the right school year to use : we use the start date of the displayed planning
             $CurrentSchoolYear = getSchoolYear($StartDate);
             $bIsFirstChildOfPlanning = TRUE;
             $PlanningCRC = 0;

             foreach($ArrayChildren["ChildID"] as $i => $CurrentChildID)
             {
                 $CurrentWithoutPork = $ArrayChildren["ChildWithoutPork"][$i];

                 if ($CurrentClass != $ArrayChildren["ChildClass"][$i])
                 {
                     // We display the row to split the different class
                     echo "<tr>\n\t<td class=\"PlanningSplit\" colspan=\"".($NbDays + 2)."\"><strong>"
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

                         if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                             || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                  && ($GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1])))
                         {
                             // This day can have canteen registrations
                             $CheckboxAll = generateInputField("chkCanteenRegitrationClass_$CurrentClass"."_$j", "checkbox", 1, 1,
                                                               $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_CLASS_CHECK_TIP"],
                                                               $CurrentClass, FALSE, FALSE,
                                                               "onClick = \"checkClassCanteenPlanning('$CurrentClass', '$j', '$CurrentDayDate');\"");
                         }
                         else
                         {
                             // This day can't have canteen registrations
                             $CheckboxAll = "&nbsp;";
                         }

                         echo "<td class=\"PlanningCaptions\">$CheckboxAll</td>";
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

                 $iNbCanteenRegistrations = 0;

                 // We check if the child has a canteen registration for this day
                 $ArrayCanteenRegistrationsOfChild = getCanteenRegistrations($DbConnection, $StartDate, $EndDate,
                                                                             'CanteenRegistrationForDate', $CurrentChildID,
                                                                              FALSE, PLANNING_BETWEEN_DATES);

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

                     // We check if the child has a canteen registration for this day
                     $iPosCurrentDayDate = -1;
                     if (isset($ArrayCanteenRegistrationsOfChild['CanteenRegistrationID']))
                     {
                         $iPosCurrentDayDate = array_search($CurrentDayDate, $ArrayCanteenRegistrationsOfChild['CanteenRegistrationForDate']);
                         if ($iPosCurrentDayDate === FALSE)
                         {
                             $iPosCurrentDayDate = -1;
                         }
                         else
                         {
                             // Compute the CRC of the planning
                             $PlanningCRC = $PlanningCRC ^ $ArrayCanteenRegistrationsOfChild['CanteenRegistrationID'][$iPosCurrentDayDate];
                         }
                     }

                     // We check if the planning can be edited
                     // First we check if the supporter is concerned by the retrictions delays
                     $bCanEdit = FALSE;
                     if (in_array($_SESSION['SupportMemberStateID'], $GLOBALS['CONF_CANTEEN_DELAYS_RESTRICTIONS']))
                     {
                         // The supporter is concerned by restrictions
                         // The supporter can't registre a child after x months
                         if ((strtotime($CurrentDayDate) >= $TodayDateStamp) && (strtotime($CurrentDayDate) <= $LimitEditDateStamp))
                         {
                             // The limit of nb of hours is over?
                             $iNbHours = floor((strtotime(date('Y-m-d 12:00:00', strtotime($CurrentDayDate))) - $TodayCurrentTime) / 3600);
                             if ($iNbHours >= $GLOBALS['CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION'])
                             {
                                 $bCanEdit = TRUE;

                                 // Take into account the access mode
                                 switch($cUserAccess)
                                 {
                                     case FCT_ACT_READ_ONLY:
                                         $bCanEdit = FALSE;
                                         break;
                                 }
                             }
                         }
                     }
                     else
                     {
                         $bCanEdit = TRUE;
                     }

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
                         // Holiday
                         $Style = "PlanningHoliday";
                         $Title = '';
                         $CellContent = '&nbsp;';

                         // We check if it's a known holiday
                         if (array_key_exists($NumCurrentDay, $Holidays[$Offset]))
                         {
                             $Title = $Holidays[$Offset][$NumCurrentDay];
                         }
                     }
                     elseif ((!$GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1]) && ($iPosOpenedSpecialDay === FALSE))
                     {
                         // Canteen not opened for this day
                         $Style = "PlanningSupporterOther";
                         $Title = $GLOBALS['LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_CANTEEN_NOT_OPENED'];
                         $CellContent = '&nbsp;';
                     }
                     elseif ((isset($ArrayCanteenRegistrationsOfChild['CanteenRegistrationID'][$iPosCurrentDayDate]))
                             && (!empty($ArrayCanteenRegistrationsOfChild['CanteenRegistrationID'][$iPosCurrentDayDate])))
                     {
                         // The child eats at the canteen (he has a canteen registration for this day)
                         $Value = "$CurrentDayDate#$CurrentClass#$CurrentChildID#"
                                  .$ArrayCanteenRegistrationsOfChild['CanteenRegistrationID'][$iPosCurrentDayDate];  // Day#Class#ChildID#CanteenRegistrationID

                         // Display the info if the canteen registration is with or without pork
                         switch($ArrayCanteenRegistrationsOfChild['CanteenRegistrationWithoutPork'][$iPosCurrentDayDate])
                         {
                             case CANTEEN_REGISTRATION_WITHOUT_PORK:
                                 $Style = 'PlanningSupporterFormationNoPork';
                                 $Title = $GLOBALS['LANG_MEAL_WITHOUT_PORK'];
                                 break;

                             case CANTEEN_REGISTRATION_PACKED_LUNCH:
                                 $Style = 'PlanningSupporterFormationPackedLunch';
                                 $Title = $GLOBALS['LANG_MEAL_PACKED_LUNCH'];
                                 break;

                             case CANTEEN_REGISTRATION_DEFAULT_MEAL:
                             default:
                                 $Style = 'PlanningSupporterFormation';
                                 $Title = $GLOBALS['LANG_MEAL_WITH_PORK'];
                                 break;
                         }

                         if ($bCanEdit)
                         {
                             $CellContent = generateInputField("chkCanteenRegitration[]", "checkbox", 1, 1,
                                                               $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_REGISTRATION_CHECK_TIP"],
                                                               $Value, FALSE, TRUE,
                                                               "onClick = \"checkChildDayCanteenPlanning('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID', '$CurrentWithoutPork');\"");
                         }
                         else
                         {
                             $CellContent = generateStyledPicture($GLOBALS['CONF_PLANNING_LOCKED_ICON'], $Title, '');
                         }

                         $iNbCanteenRegistrations++;
                     }
                     else
                     {
                         // No canteen registration for the child for this day
                         $Value = "$CurrentDayDate#$CurrentClass#$CurrentChildID#0";  // Day#Class#ChildID#0
                         if ($bCanEdit)
                         {
                             $Style = "PlanningWorkingDay";
                             $Title = "";
                             $CellContent = generateInputField("chkCanteenRegitration[]", "checkbox", 1, 1,
                                                               $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_REGISTRATION_CHECK_TIP"],
                                                               $Value, FALSE, FALSE,
                                                               "onClick = \"checkChildDayCanteenPlanning('$CurrentClass', '$j', '$CurrentDayDate', '$CurrentChildID', '$CurrentWithoutPork');\"");
                         }
                         else
                         {
                             $Style = 'AowPlnNotAvailable';
                             $Title = $GLOBALS["LANG_PLANNING_NOT_EDITABLE"];
                             $CellContent = '&nbsp;';
                         }
                     }

                     echo "<td class=\"$Style\" title=\"$Title\">$CellContent</td>";
                 }

                 // Total of canteen registrations for the child
                 echo "<td class=\"PlanningTotalChild\">$iNbCanteenRegistrations</td>\n</tr>\n";
             }

             // We display a separator then the number of more meals for the day
             switch($RestrictionAccess)
             {
                 case PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN:
                     // Display nothing
                     break;

                 case PLANNING_VIEWS_RESTRICTION_ALL:
                     echo "<tr>\n\t<td class=\"PlanningSplitMoreMeals\" colspan=\"".($NbDays + 2)."\">&nbsp;</td>\n</tr>\n";

                     $sMoreMealsHTML = "<tr>\n\t<td class=\"PlanningMoreMealsCaption\">"
                                       .$GLOBALS['LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_MORE_MEALS']."</td>";
                     $sMoreMealsWithoutPorkHTML = "<tr>\n\t<td class=\"PlanningMoreMealsCaption\">"
                                                  .$GLOBALS['LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_MORE_MEALS_WITHOUT_PORK']."</td>";

                     $ArrayTotalsDaysOfMonth = array();

                     $iNbTotalMoreMeals = 0;
                     $iNbTotalMoreMealsWithoutPork = 0;
                     $ArrayMoreMeals = getMoreMeals($DbConnection, $StartDate, $EndDate);
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

                         $ArrayTotalsDaysOfMonth[$CurrentDayDate] = 0;

                         if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                             || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                  && ($GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1])))
                         {
                             $Quantity = '';
                             $QuantityWithoutPork = '';
                             $MoreMealID = 0;

                             // There is a "more meal" entry for this day?
                             $iPos = FALSE;
                             if (isset($ArrayMoreMeals['MoreMealForDate']))
                             {
                                 $iPos = array_search($CurrentDayDate, $ArrayMoreMeals['MoreMealForDate']);
                             }

                             if ($iPos !== FALSE)
                             {
                                 // Yes, we get the number of meals
                                 $Quantity = $ArrayMoreMeals['MoreMealQuantity'][$iPos];
                                 $QuantityWithoutPork = $ArrayMoreMeals['MoreMealWithoutPorkQuantity'][$iPos];
                                 $MoreMealID = $ArrayMoreMeals['MoreMealID'][$iPos];

                                 // For stats displayed after
                                 $iNbTotalMoreMeals += $Quantity;
                                 $iNbTotalMoreMealsWithoutPork += $QuantityWithoutPork;
                                 $ArrayTotalsDaysOfMonth[$CurrentDayDate] += $Quantity + $QuantityWithoutPork;
                             }

                             // With pork
                             $MoreMeals = generateInputField("sMoreMeals:".$CurrentDayDate."_".$MoreMealID, "text", "2", "2",
                                                             $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_MORE_MEALS_TIP"],
                                                             $Quantity);

                             $sMoreMealsHTML .= "<td class=\"PlanningMoreMeals\">$MoreMeals</td>";

                             // Without pork
                             $MoreMealsWithoutPork = generateInputField("sMoreMealsWithoutPork:".$CurrentDayDate."_".$MoreMealID, "text", "2", "2",
                                                                        $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_MORE_MEALS_WITHOUT_PORK_TIP"],
                                                                        $QuantityWithoutPork);

                             $sMoreMealsWithoutPorkHTML .= "<td class=\"PlanningMoreMeals\">$MoreMealsWithoutPork</td>";
                         }
                         else
                         {
                             $sMoreMealsHTML .= "<td class=\"PlanningHoliday\">&nbsp;</td>";
                             $sMoreMealsWithoutPorkHTML .= "<td class=\"PlanningHoliday\">&nbsp;</td>";
                         }
                     }

                     $sMoreMealsHTML .= "<td class=\"PlanningTotalMoreMeals\">$iNbTotalMoreMeals</td>\n</tr>\n";
                     $sMoreMealsWithoutPorkHTML .= "<td class=\"PlanningTotalMoreMeals\">$iNbTotalMoreMealsWithoutPork</td>\n</tr>\n";

                     echo $sMoreMealsHTML;
                     echo $sMoreMealsWithoutPorkHTML;

                     // Display stats for the month
                     echo "<tr>\n\t<td class=\"PlanningSplitMoreMeals\" colspan=\"".($NbDays + 2)."\">&nbsp;</td>\n</tr>\n";

                     // Stat about meals without pork
                     $iTotal = 0;
                     echo "<tr>\n\t<td class=\"PlanningMoreMealsCaption\">".$GLOBALS['LANG_MEAL_WITHOUT_PORK']."</td>";
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

                         if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                             || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                  && ($GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1])))
                         {
                             $Quantity = 0;

                             // We check if there are canteen registrations without pork for this day
                             $ArrayStatsParams = array(
                                                       'CanteenRegistrationWithoutPork' => array(CANTEEN_REGISTRATION_WITHOUT_PORK)
                                                      );
                             $ArrayCanteenRegistrationsOfDay = getCanteenRegistrations($DbConnection, $CurrentDayDate, $CurrentDayDate,
                                                                                       'CanteenRegistrationForDate', NULL, FALSE,
                                                                                       PLANNING_BETWEEN_DATES, $ArrayStatsParams);

                             if (!empty($ArrayCanteenRegistrationsOfDay))
                             {
                                 $Quantity = count($ArrayCanteenRegistrationsOfDay['CanteenRegistrationID']);
                                 $iTotal += $Quantity;
                                 $ArrayTotalsDaysOfMonth[$CurrentDayDate] += $Quantity;
                             }

                             echo "<td class=\"PlanningMoreMeals\">$Quantity</td>";
                         }
                         else
                         {
                             echo "<td class=\"PlanningHoliday\">&nbsp;</td>";
                         }
                     }

                     echo "<td class=\"PlanningTotalMoreMeals\">$iTotal</td>\n</tr>\n";

                     // Stat about packed lunches
                     $iTotal = 0;
                     echo "<tr>\n\t<td class=\"PlanningMoreMealsCaption\">".$GLOBALS['LANG_MEAL_PACKED_LUNCH']."</td>";
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

                         if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                             || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                  && ($GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1])))
                         {
                             $Quantity = 0;

                             // We check if there are canteen registrations with packed lunch for this day
                             $ArrayStatsParams = array(
                                                       'CanteenRegistrationWithoutPork' => array(CANTEEN_REGISTRATION_PACKED_LUNCH)
                                                      );
                             $ArrayCanteenRegistrationsOfDay = getCanteenRegistrations($DbConnection, $CurrentDayDate, $CurrentDayDate,
                                                                                       'CanteenRegistrationForDate', NULL, FALSE,
                                                                                       PLANNING_BETWEEN_DATES, $ArrayStatsParams);

                             if (!empty($ArrayCanteenRegistrationsOfDay))
                             {
                                 $Quantity = count($ArrayCanteenRegistrationsOfDay['CanteenRegistrationID']);
                                 $iTotal += $Quantity;
                                 $ArrayTotalsDaysOfMonth[$CurrentDayDate] += $Quantity;
                             }

                             echo "<td class=\"PlanningMoreMeals\">$Quantity</td>";
                         }
                         else
                         {
                             echo "<td class=\"PlanningHoliday\">&nbsp;</td>";
                         }
                     }

                     echo "<td class=\"PlanningTotalMoreMeals\">$iTotal</td>\n</tr>\n";

                     // Stats for groups of grades
                     foreach($GLOBALS['CONF_GRADES_GROUPS'] as $Label => $ArrayGradeID)
                     {
                         $iTotal = 0;
                         echo "<tr>\n\t<td class=\"PlanningMoreMealsCaption\">$Label</td>";
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

                             if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                                 || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                     && ($GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1])))
                             {
                                 $Quantity = 0;

                                 // We check if there are canteen registrations without pork for this day
                                 $ArrayStatsParams = array(
                                                           'CanteenRegistrationWithoutPork' => array(CANTEEN_REGISTRATION_DEFAULT_MEAL),
                                                           'ChildGrade' => $ArrayGradeID
                                                          );
                                 $ArrayCanteenRegistrationsOfDay = getCanteenRegistrations($DbConnection, $CurrentDayDate, $CurrentDayDate,
                                                                                           'CanteenRegistrationForDate', NULL, FALSE,
                                                                                           PLANNING_BETWEEN_DATES, $ArrayStatsParams);

                                 if (!empty($ArrayCanteenRegistrationsOfDay))
                                 {
                                     $Quantity = count($ArrayCanteenRegistrationsOfDay['CanteenRegistrationID']);
                                     $iTotal += $Quantity;
                                     $ArrayTotalsDaysOfMonth[$CurrentDayDate] += $Quantity;
                                 }

                                 echo "<td class=\"PlanningMoreMeals\">$Quantity</td>";
                             }
                             else
                             {
                                 echo "<td class=\"PlanningHoliday\">&nbsp;</td>";
                             }
                         }

                         echo "<td class=\"PlanningTotalMoreMeals\">$iTotal</td>\n</tr>\n";
                     }

                     // Display total of each day
                     echo "<tr>\n\t<td class=\"PlanningTotalCaption\">".$GLOBALS['LANG_TOTAL']."</td>";
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

                         if ((in_array($CurrentDayDate, $ArrayOpenedSpecialDays['OpenedSpecialDayDate']))
                             || ((in_array($NumCurrentDay, $OpenedDays[$Offset]))
                                  && ($GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1])))
                         {
                             $Quantity = 0;
                             if (isset($ArrayTotalsDaysOfMonth[$CurrentDayDate]))
                             {
                                 $Quantity = $ArrayTotalsDaysOfMonth[$CurrentDayDate];
                             }

                             // Check if the day has a number of canteen registrations over the warning limit
                             if ((isset($ArrayWarningCanteenRegistrations['ForDayDate'])) && (in_array($CurrentDayDate, $ArrayWarningCanteenRegistrations['ForDayDate'])))
                             {
                                 // There is too many canteen registrations for this day : display a warning
                                 echo "<td class=\"PlanningTotalDayWarning\" title=\"".$GLOBALS['LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_WARNING_NB_CANTEENS_TIP']
                                 ."\">$Quantity</td>";
                             }
                             else
                             {
                                 // Not too many centeen registrations for this day
                                 echo "<td class=\"PlanningTotalDay\">$Quantity</td>";
                             }
                         }
                         else
                         {
                             echo "<td class=\"PlanningHoliday\">&nbsp;</td>";
                         }
                     }

                     echo "<td class=\"PlanningTotalMonth\">".array_sum($ArrayTotalsDaysOfMonth)."</td>\n</tr>\n";
                     break;
             }

             // Close the table
             echo "</tbody></table>\n";

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
                                                          array("PlanningSupporterFormation", $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_CHILD_REGISTRED"]),
                                                          array("PlanningSupporterFormationNoPork", $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_CHILD_REGISTRED"].' ('.$GLOBALS["LANG_MEAL_WITHOUT_PORK"].')'),
                                                          array("PlanningSupporterFormationPackedLunch", $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_CHILD_REGISTRED"].' ('.$GLOBALS["LANG_MEAL_PACKED_LUNCH"].')'),
                                                          array("PlanningSupporterOther", $GLOBALS["LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_CANTEEN_NOT_OPENED"]),
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
 * Display the canteen synthesis of the selected week, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-11-07 : taken into account CANTEEN_REGISTRATION_DEFAULT_MEAL and CANTEEN_REGISTRATION_WITHOUT_PORK
 *
 * @since 2012-01-28
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Week                 Integer               Week to display [1..53]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the canteen synthesis
 */
 function displayCanteenWeekSynthesisForm($DbConnection, $ProcessFormPage, $Week, $Year, $AccessRules = array())
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

             // Display the weeks list to change the planning to display
             openParagraph('toolbar');
             echo generateStyledPictureHyperlink($GLOBALS["CONF_PRINT_BULLET"], "javascript:PrintWebPage()", $GLOBALS["LANG_PRINT"], "PictureLink", "");
             closeParagraph();

             // Display the weeks list : we get the older date of the canteen registrations
             openParagraph('toolbar');
             $MinDate = date("Y-m-d", strtotime(getCanteenRegistrationMinDate($DbConnection)));
             $MinWeek = date("W", strtotime($MinDate));
             if ($MinWeek == '')
             {
                 $MinWeek = date("W");
             }

             $MaxDate = date("Y-m-d", strtotime(getCanteenRegistrationMaxDate($DbConnection)));
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

             // We generate the list of the weeks from the older week to the current week
             $StartDate = date("Y-m-d", strtotime("$MinWeek weeks", strtotime(date("Y", strtotime($MinDate))."-01-01")));
             $ArrayWeeks = array_keys(getPeriodIntervalsStats($StartDate, $MaxDate, "w"));
             $ArrayWeeksSize = count($ArrayWeeks);
             $ArrayWeeksLabels = array();
             for($i = 0 ; $i < $ArrayWeeksSize ; $i++)
             {
                 $ArrayWeeksLabels[] = $ArrayWeeks[$i];
             }

             echo generateSelectField("lWeek", $ArrayWeeksLabels, $ArrayWeeksLabels, "$Year-$Week",
                                      "onChangeCanteenWeekSynthesisWeek(this.value)");
             closeParagraph();

             // Display the header of the synthesis
             displayTitlePage($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_TITLE'], 2, "class=\"CanteenSynthesis\"");
             openParagraph('CanteenSynthesisHeader');
             displayStyledText($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_CONTACT'], "");
             displayBR(3);
             displayStyledText($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FOR'], "");
             displayBR(2);
             displayStyledText($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FOR_NAME'], "");
             displayBR(1);
             displayStyledText($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FOR_CONTACT'], "");
             closeParagraph();

             displayTitlePage($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_ORDER_TITLE'], 3, "class=\"CanteenSynthesisOrderTitle\"");

             // We get canteen registrations for this week and year
             $StartDate = getFirstDayOfWeek($Week, $Year);
             $ArrayDaysOfWeek = array($StartDate);
             for($d = 1; $d <= 4; $d++)
             {
                 $ArrayDaysOfWeek[] = date('Y-m-d', strtotime("+$d days", strtotime($StartDate)));
             }

             echo "<table class=\"CanteenSynthesisTable\" cellspacing=\"0\">\n<tr>\n";
             echo "\t<td colspan=\"6\">".ucfirst($GLOBALS['LANG_WEEK'])." ...$Week... "
                  .$GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_FROM']." "
                  .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($StartDate))
                  ." ".$GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_TO']." "
                  .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime("+4 days", strtotime($StartDate)))."</td>\n</tr>\n";

             echo "<tr>\n\t<th>".$GLOBALS['LANG_CHILD_GRADE']."</th>";
             foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
             {
                 // Display the name of the day
                 $iNumWeekDay = date('w', strtotime($CurrentDayDate));
                 if ($iNumWeekDay == 0)
                 {
                     // Sunday = 0 -> 7
                     $iNumWeekDay = 7;
                 }

                 echo "<th>".$GLOBALS['CONF_DAYS_OF_WEEK'][$iNumWeekDay - 1]."</th>";
             }
             echo "</tr>\n";

             // Stats for groups of grades
             $iGroup = 0;
             $ArrayUseCorrections = array();
             foreach($GLOBALS['CONF_GRADES_GROUPS'] as $Label => $ArrayGradeID)
             {
                 // First, with pork
                 echo "<tr>\n\t<td>$Label</td>";
                 foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
                 {
                     $ArrayUseCorrections[$iGroup][$d][0] = FALSE;

                     // We check if there are canteen registrations without pork for this day
                     $ArrayStatsParams = array(
                                               'CanteenRegistrationWithoutPork' => array(CANTEEN_REGISTRATION_DEFAULT_MEAL),
                                               'ChildGrade' => $ArrayGradeID
                                              );
                     $ArrayCanteenRegistrationsOfDay = getCanteenRegistrations($DbConnection, $CurrentDayDate, $CurrentDayDate,
                                                                               'CanteenRegistrationForDate', NULL, FALSE,
                                                                               PLANNING_BETWEEN_DATES, $ArrayStatsParams);

                     $Quantity = 0;
                     $bUpdated = FALSE;
                     if (!empty($ArrayCanteenRegistrationsOfDay))
                     {
                         $Quantity = count($ArrayCanteenRegistrationsOfDay['CanteenRegistrationID']);

                         // We check if the quantity has changed
                         foreach($ArrayCanteenRegistrationsOfDay['CanteenRegistrationAdminDate'] as $a => $AdminDate)
                         {
                             if (!empty($AdminDate))
                             {
                                 $bUpdated = TRUE;;
                             }
                         }
                     }

                     // We use the correction of the quantity (if set)
                     if (isset($GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][0]))
                     {
                         if ($Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][0] > 0)
                         {
                             if (($iGroup == 0) || (($iGroup > 0) && ($ArrayUseCorrections[$iGroup - 1][$d][0])))
                             {
                                 $Quantity = max(0, $Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][0]);
                                 $ArrayUseCorrections[$iGroup][$d][0] = TRUE;
                             }
                         }
                     }

                     // We check if the "more meals" (with pork) must be dispatch on this group
                     if ($Label == $GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP'])
                     {
                         // yes, we must add the "more meals" quantities to the quantity of this group
                         $ArrayMoreMeals = getMoreMeals($DbConnection, $CurrentDayDate, $CurrentDayDate);
                         if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealQuantity'])))
                         {
                             $Quantity += array_sum($ArrayMoreMeals['MoreMealQuantity']);
                         }
                     }

                     if ($bUpdated)
                     {
                         echo "<td class=\"CanteenSynthesisQuantityUpdated\">$Quantity</td>";
                     }
                     else
                     {
                         echo "<td>$Quantity</td>";
                     }
                 }
                 echo "</tr>\n";

                 // Next, without pork
                 echo "<tr>\n\t<td>$Label / ".$GLOBALS['LANG_MEAL_WITHOUT_PORK']."</td>";
                 foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
                 {
                     $ArrayUseCorrections[$iGroup][$d][1] = FALSE;

                     // We check if there are canteen registrations without pork for this day
                     $ArrayStatsParams = array(
                                               'CanteenRegistrationWithoutPork' => array(CANTEEN_REGISTRATION_WITHOUT_PORK),
                                               'ChildGrade' => $ArrayGradeID
                                              );
                     $ArrayCanteenRegistrationsOfDay = getCanteenRegistrations($DbConnection, $CurrentDayDate, $CurrentDayDate,
                                                                               'CanteenRegistrationForDate', NULL, FALSE,
                                                                               PLANNING_BETWEEN_DATES, $ArrayStatsParams);

                     $Quantity = 0;
                     $bUpdated = FALSE;
                     if (!empty($ArrayCanteenRegistrationsOfDay))
                     {
                         $Quantity = count($ArrayCanteenRegistrationsOfDay['CanteenRegistrationID']);

                         // We check if the quantity has changed
                         foreach($ArrayCanteenRegistrationsOfDay['CanteenRegistrationAdminDate'] as $a => $AdminDate)
                         {
                             if (!empty($AdminDate))
                             {
                                 $bUpdated = TRUE;;
                             }
                         }
                     }

                     // We use the correction of the quantity (if set)
                     if (isset($GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][1]))
                     {
                         if ($Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][1] > 0)
                         {
                             if (($iGroup == 0) || (($iGroup > 0) && ($ArrayUseCorrections[$iGroup - 1][$d][1])))
                             {
                                 $Quantity = max(0, $Quantity + $GLOBALS['CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS'][$Label][1]);
                                 $ArrayUseCorrections[$iGroup][$d][1] = TRUE;
                             }
                         }
                     }

                     // We check if the "more meals" (without pork) must be dispatch on this group
                     if ($Label == $GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP'])
                     {
                         // yes, we must add the "more meals" quantities to the quantity of this group
                         $ArrayMoreMeals = getMoreMeals($DbConnection, $CurrentDayDate, $CurrentDayDate);
                         if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealWithoutPorkQuantity'])))
                         {
                             $Quantity += array_sum($ArrayMoreMeals['MoreMealWithoutPorkQuantity']);
                         }
                     }

                     if ($bUpdated)
                     {
                         echo "<td class=\"CanteenSynthesisQuantityUpdated\">$Quantity</td>";
                     }
                     else
                     {
                         echo "<td>$Quantity</td>";
                     }
                 }

                 $iGroup++;
                 echo "</tr>\n";
             }

             // Adults with pork
             echo "<tr>\n\t<td>".$GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_ADULTS']."</td>";
             foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
             {
                 $Quantity = 0;

                 // Get "more meals" for this day (only if these quantities aren't dispatched on one of the previous groups)
                 if (empty($GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP']))
                 {
                     $ArrayMoreMeals = getMoreMeals($DbConnection, $CurrentDayDate, $CurrentDayDate);
                     if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealQuantity'])))
                     {
                         $Quantity = array_sum($ArrayMoreMeals['MoreMealQuantity']);
                     }
                 }

                 echo "<td>".nullFormatText($Quantity, 'XHTML')."</td>";
             }
             echo "</tr>\n";

             // Adults without pork : not used up to now
             echo "<tr>\n\t<td>".$GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_ADULTS']." / "
                  .$GLOBALS['LANG_MEAL_WITHOUT_PORK']."</td>";
             foreach($ArrayDaysOfWeek as $d => $CurrentDayDate)
             {
                 $Quantity = 0;

                 // Get "more meals" without pork for this day (only if these quantities aren't dispatched on one of the previous groups)
                 if (empty($GLOBALS['CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP']))
                 {
                     $ArrayMoreMeals = getMoreMeals($DbConnection, $CurrentDayDate, $CurrentDayDate);
                     if ((isset($ArrayMoreMeals)) && (!empty($ArrayMoreMeals['MoreMealWithoutPorkQuantity'])))
                     {
                         $Quantity = array_sum($ArrayMoreMeals['MoreMealWithoutPorkQuantity']);
                     }
                 }

                 echo "<td>".nullFormatText($Quantity, 'XHTML')."</td>";
             }

             echo "</tr>\n</table>\n";

             openParagraph('CanteenSynthesisFooter');
             displayStyledText($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_CONCLUSION'], "");
             displayBR(2);
             displayStyledText($GLOBALS['LANG_SUPPORT_WEEK_SYNTHESIS_PAGE_SYNTHESIS_WARNING'], "CanteenSynthesisWarning");
             closeParagraph();

             insertInputField("hidYearWeek", "hidden", "", "", "", "$Year-$Week");  // Current selected year-week
             closeForm();

             // Open a form to print the week synthesis
             openForm("FormPrintAction", "post", "$ProcessFormPage?lWeek=$Year-$Week", "", "");
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


/**
 * Display the canteen synthesis of the selected day, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.5
 *     - 2012-10-17 : allow to display children eating to the canteen or children don't
 *                    eating to the canteen
 *     - 2013-06-21 : taken into account the new structure of the CONF_CLASSROOMS variable
 *                    (includes school year)
 *     - 2014-02-06 : patch a bug about to get the school year with the result of
 *                    getNotCanteenRegistrations()
 *     - 2018-04-10 : display the content of the CanteenRegistrationWithoutPork field
 *     - 2020-06-29 : patch a bug about displayed CanteenRegistrationWithoutPork field
 *                    when it's missing
 *
 * @since 2012-02-03
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Day                  Integer               Day to display [1..31]
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $TypeOfDisplay        Integer               Type of display : chlidren eating or not to the canteen [0..n]
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the canteen synthesis
 */
 function displayCanteenDaySynthesisForm($DbConnection, $ProcessFormPage, $Day, $Month, $Year, $TypeOfDisplay = 0, $AccessRules = array())
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

             // Display the day list : we get the older date of the canteen registrations
             openParagraph('toolbar');

             // We generate the list of the weeks from the older week to the current day
             $SelectedDay = "$Year-$Month-$Day";
             $MinDate = date("Y-m-d", strtotime(getCanteenRegistrationMinDate($DbConnection)));
             $MinDay = date("W", strtotime($MinDate));
             if ($MinDay == '')
             {
                 $MinDay = date("d");
             }
             $StartDate = date("Y-m-d", strtotime("$MinDay days", strtotime(date("Y", strtotime($MinDate))."-01-01")));

             $MaxDate = date("Y-m-d", strtotime(getCanteenRegistrationMaxDate($DbConnection)));
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

                 if ((jour_ferie(strtotime($ArrayDays[$i])) == NULL) && ($GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$iNumWeekDay - 1]))
                 {
                     $ArrayDaysLabels[$ArrayDays[$i]] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($ArrayDays[$i]));
                 }
             }

             echo generateSelectField("lDay", array_keys($ArrayDaysLabels), array_values($ArrayDaysLabels), $SelectedDay,
                                      "onChangeCanteenDaySynthesisDay(this.value)");

             // Display the list of displays : children eating to the canteen or not
             echo generateSelectField("lDisplayType", array(0, 1),
                                      array(
                                            $GLOBALS['LANG_SUPPORT_DAY_SYNTHESIS_PAGE_DISPLAY_LIST_EAT_ITEM'],
                                            $GLOBALS['LANG_SUPPORT_DAY_SYNTHESIS_PAGE_DISPLAY_LIST_DONT_EAT_ITEM']
                                           ), $TypeOfDisplay, "onChangeCanteenDaySynthesisDisplayType(this.value)");

             closeParagraph();

             switch($TypeOfDisplay)
             {
                 case 1:
                     // Display children don't eating to the canteen for the selected day
                     $ArrayCanteenRegistrations = getNotCanteenRegistrations($DbConnection, $SelectedDay, $SelectedDay,
                                                                             "ChildClass, FamilyLastname, ChildFirstname",
                                                                             NULL, DATES_INCLUDED_IN_PLANNING, array());
                     break;

                 case 0:
                 default:
                     // Display children eating to the canteen for the selected day
                     $ArrayCanteenRegistrations = getCanteenRegistrations($DbConnection, $SelectedDay, $SelectedDay,
                                                                          "ChildClass, FamilyLastname, ChildFirstname",
                                                                          NULL, FALSE, PLANNING_BETWEEN_DATES, array());
                     break;
             }

             if ((isset($ArrayCanteenRegistrations['CanteenRegistrationID'])) && (count($ArrayCanteenRegistrations['CanteenRegistrationID']) > 0))
             {
                 displayBR(2);
                 echo "<table class=\"CanteenSynthesisTable\" cellspacing=\"0\">\n";

                 // Get the right school year
                 $CurrentSchoolYear = getSchoolYear($SelectedDay);

                 $PreviousClassroom = NULL;
                 foreach($ArrayCanteenRegistrations['CanteenRegistrationID'] as $cr => $CurrentID)
                 {
                     if ((is_null($PreviousClassroom)) || ($PreviousClassroom != $ArrayCanteenRegistrations['ChildClass'][$cr]))
                     {
                         // We display the name of the classroom
                         echo "<tr>\n\t<th class=\"Caption\" colspan=\"4\">"
                              .$GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$ArrayCanteenRegistrations['ChildClass'][$cr]]
                              ."</th>\n</tr>\n";

                         // Display the header of the table
                         echo "<tr>\n\t<th>".$GLOBALS['LANG_FAMILY_LASTNAME']."</th><th>".$GLOBALS['LANG_CHILD_FIRSTNAME']
                              ."</th><th>".$GLOBALS['LANG_CHILD_GRADE']."</th><th>"
                              .$GLOBALS["LANG_MEAL_WITHOUT_PORK"]." / ".$GLOBALS["LANG_MEAL_PACKED_LUNCH"]."</th>\n</tr>\n";

                         $PreviousClassroom = $ArrayCanteenRegistrations['ChildClass'][$cr];
                     }

                     echo "<tr>\n\t<td>".$ArrayCanteenRegistrations['FamilyLastname'][$cr]."</td><td>"
                          .$ArrayCanteenRegistrations['ChildFirstname'][$cr]."</td><td>"
                          .$GLOBALS['CONF_GRADES'][$ArrayCanteenRegistrations['ChildGrade'][$cr]]."</td><td>";

                     if (isset($ArrayCanteenRegistrations['CanteenRegistrationWithoutPork'][$cr]))
                     {
                         echo $GLOBALS['CONF_MEAL_TYPES'][$ArrayCanteenRegistrations['CanteenRegistrationWithoutPork'][$cr]];
                     }
                     else
                     {
                         echo "&nbsp;";
                     }

                     echo "</td>\n</tr>\n";
                 }

                 echo "</table>\n";
             }
             else
             {
                 // No canteen registration for this day
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_SUPPORT_DAY_SYNTHESIS_PAGE_NO_REGISTRATION'];
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


/**
 * Display the form allowing to register selected children for selected months of a given school year,
 * in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-02-12
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SchoolYear           Year                  The concerned school year (YYYY)
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update canteen registrations
 */
 function displayCanteenAnnualRegistrationsForm($DbConnection, $ProcessFormPage, $SchoolYear, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a canteen registratrion
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
             openForm("CanteenAnnualRegistrations", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SUPPORT_CANTEEN_ANNUAL_REGISTRATIONS_PAGE_FORM_TITLE"], "Frame", "Frame", "DetailsNews");

             // Get activated children for the selected school year
             $CurrentDate = date('Y-m-d');

             $StartDate = getSchoolYearStartDate($SchoolYear);
             $StartDate = date('Y-m-d', (max(strtotime($CurrentDate), strtotime($StartDate))));
             $EndDate = getSchoolYearEndDate($SchoolYear);

             $ArrayChildren = getChildrenListForCanteenPlanning($DbConnection, $StartDate, $EndDate, "ChildClass, FamilyLastname",
                                                                FALSE, FALSE, PLANNING_BETWEEN_DATES);

             $ChildrenList = "";
             if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
             {
                 $ArrayData = array();

                 // We group children by classroom
                 foreach($ArrayChildren['ChildID'] as $c => $CurrentChildID)
                 {
                     $ArrayData[$GLOBALS['CONF_CLASSROOMS'][$SchoolYear][$ArrayChildren['ChildClass'][$c]]][$CurrentChildID] = $ArrayChildren['FamilyLastname'][$c].' '.$ArrayChildren['ChildFirstname'][$c];
                 }

                 $ItemsSelected = array();
                 $ChildrenList = generateOptGroupMultipleSelectField("lmChildID", $ArrayData, 10, $ItemsSelected);

                  unset($ArrayData);
             }
             else
             {
                 // No child
                 $ChildrenList = generateOptGroupMultipleSelectField("lmChildID", array('-'), 2, array());
             }

             // Generate months of the school year
             $ArrayMonths = getPeriodIntervalsStats($StartDate, $EndDate, 'm');
             foreach($ArrayMonths as $ym => $Value)
             {
                 $ArrayTmp = explode('-', $ym);
                 $ArrayMonths[$ym] = $GLOBALS['CONF_PLANNING_MONTHS'][(integer)$ArrayTmp[1] - 1].' '.$ArrayTmp[0];
             }

             $ItemsSelected = array();

             if (empty($ArrayMonths))
             {
                 $MonthsList = generateMultipleSelectField("lmMonth", array(0), array('-'), 2, $ItemsSelected);
             }
             else
             {
                 $MonthsList = generateMultipleSelectField("lmMonth", array_keys($ArrayMonths), array_values($ArrayMonths),
                                                           min(count($ArrayMonths), 5), $ItemsSelected);
             }

             // Display the form
             echo "<table id=\"CanteenAnnualRegistrations\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CHILDREN"]."</td><td class=\"Value\">$ChildrenList</td><td class=\"Label\">".ucfirst($GLOBALS["LANG_MONTHS"])."</td><td class=\"Value\">$MonthsList</td>\n</tr>\n";
             echo "</table>\n";
             closeStyledFrame();

             displayBR(1);

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

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