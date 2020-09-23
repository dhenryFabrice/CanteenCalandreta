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
 * Interface module : XHTML Graphic components library used to print some web pages
 *
 * @author Christophe Javouhey
 * @version 3.5
 * @since 2012-02-03
 */


/**
 * Print the canteen synthesis of the selected week, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-11-07 : taken into account CANTEEN_REGISTRATION_DEFAULT_MEAL and CANTEEN_REGISTRATION_WITHOUT_PORK
 *
 * @since 2012-02-03
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Week                 Integer               Week to display [1..53]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the canteen synthesis
 */
 function printCanteenWeekSynthesis($DbConnection, $ProcessFormPage, $Week, $Year, $AccessRules = array())
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

             openParagraph('InfoMsg');
             displayStyledLinkText($GLOBALS['LANG_GO_BACK'], "$ProcessFormPage?lWeek=$Year-$Week", 'notprintable',
                                   $GLOBALS['LANG_GO_BACK']);
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
 * Print the canteen synthesis of the selected day, in the current web page, in the
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
 * @since 2012-02-14
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Day                  Integer               Day to display [1..31]
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the canteen synthesis
 */
 function printCanteenDaySynthesis($DbConnection, $ProcessFormPage, $Day, $Month, $Year, $TypeOfDisplay = 0, $AccessRules = array())
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
             // Display the header of the synthesis
             displayTitlePage($GLOBALS['LANG_SUPPORT_DAY_SYNTHESIS_PAGE_TITLE'], 2);

             openParagraph();

             switch($TypeOfDisplay)
             {
                 case 1:
                     // Display children don't eating to the canteen for the selected day
                     displayStyledText($GLOBALS['LANG_SUPPORT_DAY_SYNTHESIS_PAGE_INTRODUCTION_DONT_EATING']." "
                                       .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime("$Year-$Month-$Day")).".");
                     break;

                 case 0:
                 default:
                     // Display children eating to the canteen for the selected day
                     displayStyledText($GLOBALS['LANG_SUPPORT_DAY_SYNTHESIS_PAGE_INTRODUCTION']." "
                                       .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime("$Year-$Month-$Day")).".");
                     break;
             }
             closeParagraph();

             $SelectedDay = "$Year-$Month-$Day";

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
                 displayBR(1);
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

             openParagraph('InfoMsg');
             displayBR(1);
             displayStyledLinkText($GLOBALS['LANG_GO_BACK'], "$ProcessFormPage?lDay=$Year-$Month-$Day&amp;lDisplayType=$TypeOfDisplay",
                                   'notprintable', $GLOBALS['LANG_GO_BACK']);
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
 * Print the nursery synthesis of the selected day, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *    - 2020-03-16 : v1.1. Taken into account CONF_NURSERY_OTHER_TIMESLOTS
 *
 * @since 2017-09-21
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Day                  Integer               Day to display [1..31]
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the nursery synthesis
 */
 function printNurseryDaySynthesis($DbConnection, $ProcessFormPage, $Day, $Month, $Year, $TypeOfDisplay = 0, $AccessRules = array())
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
             // Display the header of the synthesis
             displayTitlePage($GLOBALS['LANG_SUPPORT_NURSERY_DAY_SYNTHESIS_PAGE_TITLE'], 2);

             openParagraph();

             switch($TypeOfDisplay)
             {
                 case 1:
                     // Display children don't registered at the nursery for the selected day
                     displayStyledText($GLOBALS['LANG_SUPPORT_NURSERY_DAY_SYNTHESIS_PAGE_INTRODUCTION_DONT_AT_NURSERY']." "
                                       .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime("$Year-$Month-$Day")).".");
                     break;

                 case 0:
                 default:
                     // Display children registered at the nursery for the selected day
                     displayStyledText($GLOBALS['LANG_SUPPORT_NURSERY_DAY_SYNTHESIS_PAGE_INTRODUCTION']." "
                                       .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime("$Year-$Month-$Day")).".");
                     break;
             }
             closeParagraph();

             $SelectedDay = "$Year-$Month-$Day";

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
                 displayBR(1);
                 echo "<table class=\"CanteenSynthesisTable\" cellspacing=\"0\">\n";

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
                 // No canteen registration for this day
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_SUPPORT_DAY_SYNTHESIS_PAGE_NO_REGISTRATION'];
                 closeParagraph();
             }

             openParagraph('InfoMsg');
             displayBR(1);
             displayStyledLinkText($GLOBALS['LANG_GO_BACK'], "$ProcessFormPage?lDay=$Year-$Month-$Day&amp;lDisplayType=$TypeOfDisplay",
                                   'notprintable', $GLOBALS['LANG_GO_BACK']);
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
 * Print the planning of snacks brought by families for a given school year, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-08-30 : display num week and a flag if no-normal date
 *
 * @since 2015-06-17
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SchoolYear           Integer               Concerned school year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the planning of snacks
 */
 function printSnackPlanning($DbConnection, $ProcessFormPage, $SchoolYear, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the planning of snacks
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
             // Display the header of the planning of snacks
             displayTitlePage($GLOBALS['LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_TITLE'], 2, "");

             openParagraph();
             displayStyledText($GLOBALS['LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_INTRODUCTION']." "
                               .date("Y", strtotime(getSchoolYearStartDate($SchoolYear)))
                               ."-".date("Y", strtotime(getSchoolYearEndDate($SchoolYear))));
             closeParagraph();

             // We get snack registrations for families for the given school year
             $StartDate = getSchoolYearStartDate($SchoolYear);
             $EndDate = getSchoolYearEndDate($SchoolYear);
             $ArraySnackRegistrations = getSnackRegistrations($DbConnection, $StartDate, $EndDate,
                                                              'SnackRegistrationClass, SnackRegistrationDate', NULL,
                                                              PLANNING_BETWEEN_DATES);

             if ((isset($ArraySnackRegistrations['SnackRegistrationID'])) && (count($ArraySnackRegistrations['SnackRegistrationID']) > 0))
             {
                 // Snack registrations found
                 // We get the different classes
                 $ArrayClass = array_values(array_unique($ArraySnackRegistrations['SnackRegistrationClass']));
                 foreach($ArrayClass as $c => $CurrentClass)
                 {
                     $ArrayClass[$c] = $GLOBALS['CONF_CLASSROOMS'][$SchoolYear][$CurrentClass];
                 }

                 array_unshift($ArrayClass, "");
                 $iNbColumns = count($ArrayClass);

                 // We get the different dates of the planning
                 $ArrayPlanningDates = array_values(array_unique($ArraySnackRegistrations['SnackRegistrationDate']));
                 $ArrayPositionsHolidays = array();
                 $ArrayPlanningDatesWithHolidays = array();
                 foreach($ArrayPlanningDates as $d => $CurrentDate)
                 {
                     $CurrentStamp = strtotime($CurrentDate);
                     $CurrentNumOfDay = (integer)date('N', $CurrentStamp);

                     if (($d > 0) && (getNbDaysBetween2Dates(strtotime($ArrayPlanningDates[$d - 1]), $CurrentStamp)) >= 9)
                     {
                         $ArrayPositionsHolidays[] = $CurrentDate;

                         // We add an empty cell in the table to "show" holidays
                         $ArrayPlanningDatesWithHolidays[] = '&nbsp;';
                     }

                     // Display date and num of the week
                     $DisplayedDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], $CurrentStamp).' ('
                                      .ucfirst(substr($GLOBALS['LANG_WEEK'], 0, 1)).date('W', $CurrentStamp).')';

                     // Display a flag for "no-normal" dates (not a monday or a not working day)
                     $ArraySchoolHolidays = getHolidays($DbConnection, $CurrentDate, $CurrentDate, 'HolidayStartDate', DATES_INCLUDED_IN_PLANNING);
                     if (($CurrentNumOfDay > 1) || (jour_ferie($CurrentStamp) === 0)
                         || (!$GLOBALS['CONF_CANTEEN_OPENED_WEEK_DAYS'][$CurrentNumOfDay - 1])
                         || ((isset($ArraySchoolHolidays['HolidayID'])) && (!empty($ArraySchoolHolidays['HolidayID']))))
                     {
                         $DisplayedDate .= ' !!';
                     }

                     $ArrayPlanningDatesWithHolidays[] = $DisplayedDate;
                 }

                 $iNbPlanningDates = count($ArrayPlanningDatesWithHolidays);

                 // We index the snack registrations by date
                 $CurrentDateStamp = strtotime(date('Y-m-d'));
                 $TabSnackPlanningData = array();
                 $TabSnackPlanningData[0] = $ArrayPlanningDatesWithHolidays;
                 $i = 0;
                 $PreviousClass = NULL;
                 foreach($ArraySnackRegistrations['SnackRegistrationID'] as $sr => $SnackRegistrationID)
                 {
                     if ($ArraySnackRegistrations['SnackRegistrationClass'][$sr] != $PreviousClass)
                     {
                         if (!is_null($PreviousClass))
                         {
                             // We check if the nomber of families is right in relation with the number of dates in the planning
                             $iNbDiff = $iNbPlanningDates - count($TabSnackPlanningData[$i]);
                             if ($iNbDiff > 0)
                             {
                                 // Not enough families : we fill with empty values !
                                 $TabSnackPlanningData[$i] = array_pad($TabSnackPlanningData[$i], $iNbPlanningDates, "-");
                             }
                         }

                         $PreviousClass = $ArraySnackRegistrations['SnackRegistrationClass'][$sr];
                         $i++;
                     }

                     if (in_array($ArraySnackRegistrations['SnackRegistrationDate'][$sr], $ArrayPositionsHolidays))
                     {
                         // The date is after holidays : we display a separation
                         $TabSnackPlanningData[$i][] = "&nbsp;";
                     }

                     // We check if the date is over (snack already brought by the family)
                     if (strtotime($ArraySnackRegistrations['SnackRegistrationDate'][$sr]) <= $CurrentDateStamp)
                     {
                         $TabSnackPlanningData[$i][] = generateStyledText($ArraySnackRegistrations['FamilyLastname'][$sr], 'done');
                     }
                     else
                     {
                         $TabSnackPlanningData[$i][] = $ArraySnackRegistrations['FamilyLastname'][$sr];
                     }
                 }

                 // We check if the nomber of families is right in relation with the number of dates in the planning
                 // (for the last column !)
                 $iNbDiff = $iNbPlanningDates - count($TabSnackPlanningData[$i]);
                 if ($iNbDiff > 0)
                 {
                     // Not enough families : we fill with empty values !
                     $TabSnackPlanningData[$iNbColumns - 1] = array_pad($TabSnackPlanningData[$iNbColumns - 1], $iNbPlanningDates, "-");
                 }

                 // We display the planning
                 displayStyledTable($ArrayClass, array_fill(0, count($ArrayClass), ''), '', $TabSnackPlanningData,
                                    'Data', '', '');
             }
             else
             {
                 // No snack registration found
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();
             }

             openParagraph('InfoMsg');
             displayStyledLinkText($GLOBALS['LANG_GO_BACK'], "$ProcessFormPage?lYear=$SchoolYear", 'notprintable',
                                   $GLOBALS['LANG_GO_BACK']);
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
 * Print the planning of laundry to wash by families for a given school year, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-08-29 : display num week and a flag if no-normal date
 *
 * @since 2015-06-19
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SchoolYear           Integer               Concerned school year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the planning of laundry
 */
 function printLaundryPlanning($DbConnection, $ProcessFormPage, $SchoolYear, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the planning of laundry
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
             // Display the header of the planning of laundry
             displayTitlePage($GLOBALS['LANG_SUPPORT_VIEW_LAUNDRY_PLANNING_PAGE_TITLE'], 2, "");

             openParagraph();
             displayStyledText($GLOBALS['LANG_SUPPORT_VIEW_LAUNDRY_PLANNING_PAGE_INTRODUCTION']." "
                               .date("Y", strtotime(getSchoolYearStartDate($SchoolYear)))
                               ."-".date("Y", strtotime(getSchoolYearEndDate($SchoolYear))));
             closeParagraph();

             $StartDate = getSchoolYearStartDate($SchoolYear);
             $EndDate = getSchoolYearEndDate($SchoolYear);
             $ArrayLaundryRegistrations = getLaundryRegistrations($DbConnection, $StartDate, $EndDate,
                                                                  'LaundryRegistrationDate, LaundryRegistrationID',
                                                                  NULL, PLANNING_BETWEEN_DATES);

             if ((isset($ArrayLaundryRegistrations['LaundryRegistrationID'])) && (count($ArrayLaundryRegistrations['LaundryRegistrationID']) > 0))
             {
                 // Laundry registrations found
                 $ArrayFamilies = array_fill(0, $GLOBALS['CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE'], $GLOBALS['LANG_FAMILY']);
                 foreach($ArrayFamilies as $f => $CurrentFamily)
                 {
                     $ArrayFamilies[$f] = $CurrentFamily." ".($f + 1);
                 }

                 array_unshift($ArrayFamilies, "");
                 $iNbColumns = $GLOBALS['CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE'] + 1;

                 // We get the different dates of the planning
                 $ArrayPlanningDates = array_values(array_unique($ArrayLaundryRegistrations['LaundryRegistrationDate']));
                 $ArrayPositionsHolidays = array();
                 $ArrayPlanningDatesWithHolidays = array();
                 foreach($ArrayPlanningDates as $d => $CurrentDate)
                 {
                     $CurrentStamp = strtotime($CurrentDate);
                     if (($d > 0) && (getNbDaysBetween2Dates(strtotime($ArrayPlanningDates[$d - 1]), $CurrentStamp)) >= 9)
                     {
                         $ArrayPositionsHolidays[] = $CurrentDate;

                         // We add an empty cell in the table to "show" holidays
                         $ArrayPlanningDatesWithHolidays[] = '&nbsp;';
                     }

                     // Display date and num of the week
                     $DisplayedDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], $CurrentStamp).' ('
                                      .ucfirst(substr($GLOBALS['LANG_WEEK'], 0, 1)).date('W', $CurrentStamp).')';

                     // Display a flag for "no-normal" dates
                     if (!in_array(date('N', $CurrentStamp), $GLOBALS['CONF_LAUNDRY_FOR_DAYS']))
                     {
                         $DisplayedDate .= ' !!';
                     }

                     $ArrayPlanningDatesWithHolidays[] = $DisplayedDate;
                 }

                 $iNbPlanningDates = count($ArrayPlanningDatesWithHolidays);

                 // We index the laundry registrations by date
                 $CurrentDateStamp = strtotime(date('Y-m-d'));
                 $TabLaundryPlanningData = array();
                 $TabLaundryPlanningData[0] = $ArrayPlanningDatesWithHolidays;

                 foreach($ArrayLaundryRegistrations['LaundryRegistrationID'] as $sl => $LaundryRegistrationID)
                 {
                     $i = ($sl % $GLOBALS['CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE']) + 1;

                     if (in_array($ArrayLaundryRegistrations['LaundryRegistrationDate'][$sl], $ArrayPositionsHolidays))
                     {
                         // The date is after holidays : we display a separation
                         $TabLaundryPlanningData[$i][] = "&nbsp;";
                     }

                     // We check if the date is over (laundry done by the family)
                     if (strtotime($ArrayLaundryRegistrations['LaundryRegistrationDate'][$sl]) <= $CurrentDateStamp)
                     {
                         $TabLaundryPlanningData[$i][] = generateStyledText($ArrayLaundryRegistrations['FamilyLastname'][$sl], 'done');
                     }
                     else
                     {
                         $TabLaundryPlanningData[$i][] = $ArrayLaundryRegistrations['FamilyLastname'][$sl];
                     }
                 }

                 // We check if the number of families is right in relation with the number of dates in the planning
                 for($i = 0; $i < $iNbColumns; $i++)
                 {
                     $iNbDiff = $iNbPlanningDates - count($TabLaundryPlanningData[$i]);
                     if ($iNbDiff > 0)
                     {
                         // Not enough families : we fill with empty values !
                         $TabLaundryPlanningData[$i] = array_pad($TabLaundryPlanningData[$i], $iNbPlanningDates, "-");
                     }
                 }

                 // We display the planning
                 displayStyledTable($ArrayFamilies, array_fill(0, count($ArrayFamilies), ''), '', $TabLaundryPlanningData,
                                    'Data', '', '');
             }
             else
             {
                 // No laundry registration found
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();
             }

             openParagraph('InfoMsg');
             displayStyledLinkText($GLOBALS['LANG_GO_BACK'], "$ProcessFormPage?lYear=$SchoolYear", 'notprintable',
                                   $GLOBALS['LANG_GO_BACK']);
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
 * Print the exit permissions for the selected day, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2015-09-21 : display the selected date
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *
 * @since 2015-07-15
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SelectedDate         Integer               Day to display (YYYY-MM-DD)
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view or edit exit permissions
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some exit permissions
 */
 function printExitPermissionsList($DbConnection, $ProcessFormPage, $SelectedDate, $AccessRules = array(), $ViewsRestrictions = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the exit permissions
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
             // Display the header of the exit permissions
             displayTitlePage($GLOBALS['LANG_SUPPORT_VIEW_EXIT_PERMISSIONS_PAGE_TITLE']
                              .' ('.date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($SelectedDate)).')', 2, "");

             openParagraph();
             displayStyledText($GLOBALS['LANG_SUPPORT_VIEW_EXIT_PERMISSIONS_PAGE_INTRODUCTION']);
             closeParagraph();

             // We check if the logged supporter can view all exit permissions or a limited view
             $RestrictionAccess = PLANNING_VIEWS_RESTRICTION_ALL;
             if ((!empty($ViewsRestrictions)) && (isset($ViewsRestrictions[$_SESSION['SupportMemberStateID']])))
             {
                 $RestrictionAccess = $ViewsRestrictions[$_SESSION['SupportMemberStateID']];
             }

             switch($RestrictionAccess)
             {
                 case PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN:
                     // View only the exit permissions of the children of the family
                     // Use the supporter lastname to find the family
                     $ArrayFamilies = dbSearchFamily($DbConnection, array("FamilyID" => $_SESSION['FamilyID']), "FamilyID DESC", 1, 1);

                     if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                     {
                         $FamilyID = $ArrayFamilies['FamilyID'][0];

                         // Get children of the family
                         $ArrayChildren = getFamilyChildren($DbConnection, $FamilyID, "ChildFirstname");
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

                             $StartDateStampTmp = strtotime($SelectedDate);
                             $EndDateStampTmp = strtotime($SelectedDate);
                             foreach($ArrayChildren['ChildID'] as $c => $CurrentChildID)
                             {
                                 $bKeepChild = FALSE;
                                 $SchoolDateStamp = strtotime($ArrayChildren['ChildSchoolDate'][$c]);
                                 if (($SchoolDateStamp <= $StartDateStampTmp)
                                     || (($SchoolDateStamp >= $StartDateStampTmp) && ($SchoolDateStamp <= $EndDateStampTmp)))
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
                                         if (($DesactivationDateStamp >= $StartDateStampTmp)
                                             || ($DesactivationDateStamp >= $EndDateStampTmp))
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
                     // View all exit permissions
                     $FamilyID = NULL;

                     $ArrayChildren = getChildrenListForNurseryPlanning($DbConnection, $SelectedDate, $SelectedDate,
                                                                        "ChildClass, FamilyLastname", FALSE, PLANNING_BETWEEN_DATES);
                     break;
             }

             $TabExitPermissionsCaptions = array($GLOBALS["LANG_CHILD"], $GLOBALS["LANG_EXIT_PERMISSION_HEADER_NAME"],
                                                 $GLOBALS["LANG_EXIT_PERMISSION_HEADER_AUTHORIZED_PERSON"],
                                                 $GLOBALS['LANG_EXIT_PERMISSION_HEADER_PARENT_SIGNATURE']);

             $TabExitPermissionsData = array();

             // We get exit permissions of the selected day
             $ArrayExitPermissions = getExitPermissions($DbConnection, $SelectedDate, $SelectedDate,
                                                        'ExitPermissionDate, FamilyLastname, ChildFirstname', $FamilyID,
                                                        PLANNING_BETWEEN_DATES, array());

             if ((isset($ArrayExitPermissions['ExitPermissionID'])) && (count($ArrayExitPermissions['ExitPermissionID']) > 0))
             {
                 foreach($ArrayExitPermissions['ExitPermissionID'] as $ep => $ExitPermissionID)
                 {
                     $TabExitPermissionsData[0][] = $ArrayExitPermissions['FamilyLastname'][$ep].' '
                                                    .$ArrayExitPermissions['ChildFirstname'][$ep];

                     $TabExitPermissionsData[1][] = stripslashes(nullFormatText($ArrayExitPermissions['ExitPermissionName'][$ep]));

                     if ($ArrayExitPermissions['ExitPermissionAuthorizedPerson'][$ep] == 0)
                     {
                         // Not authorized person
                         $TabExitPermissionsData[2][] = $GLOBALS['LANG_NO'];
                     }
                     else
                     {
                         // Authorized person
                         $TabExitPermissionsData[2][] = $GLOBALS['LANG_YES'];
                     }

                     // Signature of parent : empty up to now
                     $TabExitPermissionsData[3][] = "&nbsp;";
                 }

                 // We display the exit permissions list
                 displayStyledTable($TabExitPermissionsCaptions, array_fill(0, count($TabExitPermissionsCaptions), ''), '',
                                    $TabExitPermissionsData, 'Data', '', '');
             }
             else
             {
                 // No exit permission found
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();
             }

             openParagraph('InfoMsg');
             displayStyledLinkText($GLOBALS['LANG_GO_BACK'], "$ProcessFormPage?lDay=$SelectedDate", 'notprintable',
                                   $GLOBALS['LANG_GO_BACK']);
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
 * Print a bill
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2014-06-03 : use params to send translation of messages to the XSL template
 *
 * @since 2012-02-23
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $BillID               Integer               ID of the bill to print [1..n]
 * @param $BackUrl              String                Url of the page to print
 * @param $Filename             String                Path filename to save the result
 */
 function printDetailsBillForm($DbConnection, $BillID, $BackUrl = "GenerateMonthlyBills.php", $Filename = '')
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         if ($BillID > 0)
         {
             // Export to XML format
             // We get the content of the stylesheet
             $StyleSheetContent = getContentFile($GLOBALS['CONF_BILLS_PRINT_CSS_PATH'], 'rt');
             $XmlData = xmlOpenDocument($StyleSheetContent);

             // Add the current date
             $XmlData .= xmlTag('CurrentDate', date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"]));

             // Export the bill in XML
             $XmlData .= xmlBill($DbConnection, $BillID);

             // Add a link to go back to the page
             // We get the parameters of the GET form
             if (!empty($BackUrl))
             {
                 $Link = "$BackUrl?";
                 foreach($_GET as $i => $CurrentValue)
                 {
                     $Link .= "&amp;$i=$CurrentValue";
                 }
                 $XmlData .= xmlHyperlink($GLOBALS["LANG_GO_BACK"], $Link);
             }

             // Close the document
             $XmlData .= xmlCloseDocument();

             // To translate messages in the bill
             $ArrayLangParams = array(
                                      'lang' => $GLOBALS['CONF_LANG'],
                                      'preview-title' => $GLOBALS['LANG_BILL_PREVIEW_TITLE'],
                                      'family' => $GLOBALS['LANG_FAMILY'],
                                      'to' => ucfirst($GLOBALS['LANG_TO']),
                                      'for-date' => $GLOBALS['LANG_BILL_FOR_DATE'],
                                      'previous-months-balance' => $GLOBALS['LANG_BILL_PREVIOUS_MONTHS_BALANCE'],
                                      'advance-paid' => $GLOBALS['LANG_BILL_ADVANCE_PAID'],
                                      'monthly-contribution' => $GLOBALS['LANG_BILL_MONTHLY_CONTRIBUTION'],
                                      'canteen' => $GLOBALS['LANG_CANTEEN'],
                                      'nursery' => $GLOBALS['LANG_NURSERY'],
                                      'canteen-without-meal' => $GLOBALS['LANG_BILL_CANTEEN_WITHOUT_MEAL'],
                                      'sub-total' => $GLOBALS['LANG_BILL_SUB_TOTAL'],
                                      'total-to-pay' => $GLOBALS['LANG_BILL_TOTAL_TO_PAY']
                                     );

             switch($GLOBALS['CONF_PDF_LIB'])
             {
                 case PDF_LIB_FPDF:
                     // We use FPDF to generate the bill in PDF, so we don't convert XML to HTML
                     if (!empty($Filename))
                     {
                         saveToFile($Filename, 'wt', array($XmlData));
                     }
                     else
                     {
                         // Transform XML to XSL
                         xmlXslProcess($XmlData, $GLOBALS['CONF_BILLS_PRINT_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename, $ArrayLangParams);
                     }
                     break;

                 case PDF_LIB_WKHTMLTOPDF:
                 case PDF_LIB_DOMPDF:
                 default:
                     // Transform XML to XSL
                     xmlXslProcess($XmlData, $GLOBALS['CONF_BILLS_PRINT_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename, $ArrayLangParams);
                     break;
             }
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Print several bills
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2014-06-03 : use params to send translation of messages to the XSL template
 *
 * @since 2012-02-27
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ArrayBillsID         Array of Integers     ID of the bills to print
 * @param $BackUrl              String                Url of the page to print
 */
 function printDetailsSeveralBillsForm($DbConnection, $ArrayBillsID, $BackUrl = "GenerateMonthlyBills.php", $Filename = "")
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         if (!empty($ArrayBillsID))
         {
             // Export to XML format
             // We get the content of the stylesheet
             $StyleSheetContent = getContentFile($GLOBALS['CONF_BILLS_PRINT_CSS_PATH'], 'rt');
             $XmlData = xmlOpenDocument($StyleSheetContent);

             // Add the current date
             $XmlData .= xmlTag('CurrentDate', date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"]));

             // Export the bills in XML
             $XmlData .= "<listbills>\n";
             foreach($ArrayBillsID as $b => $BillID)
             {
                 $XmlData .= xmlBill($DbConnection, $BillID);
                 $XmlData .= "\n";
             }
             $XmlData .= "</listbills>\n";

             // Add a link to go back to the page
             // We get the parameters of the GET form
             if (!empty($BackUrl))
             {
                 $Link = "$BackUrl?";
                 foreach($_GET as $i => $CurrentValue)
                 {
                     $Link .= "&amp;$i=$CurrentValue";
                 }
                 $XmlData .= xmlHyperlink($GLOBALS["LANG_GO_BACK"], $Link);
             }

             // Close the document
             $XmlData .= xmlCloseDocument();

             // To translate messages in the bill
             $ArrayLangParams = array(
                                      'lang' => $GLOBALS['CONF_LANG'],
                                      'preview-title' => $GLOBALS['LANG_BILL_SEVERAL_BILLS_PREVIEW_TITLE'],
                                      'family' => $GLOBALS['LANG_FAMILY'],
                                      'to' => ucfirst($GLOBALS['LANG_TO']),
                                      'for-date' => $GLOBALS['LANG_BILL_FOR_DATE'],
                                      'previous-months-balance' => $GLOBALS['LANG_BILL_PREVIOUS_MONTHS_BALANCE'],
                                      'advance-paid' => $GLOBALS['LANG_BILL_ADVANCE_PAID'],
                                      'monthly-contribution' => $GLOBALS['LANG_BILL_MONTHLY_CONTRIBUTION'],
                                      'canteen' => $GLOBALS['LANG_CANTEEN'],
                                      'nursery' => $GLOBALS['LANG_NURSERY'],
                                      'canteen-without-meal' => $GLOBALS['LANG_BILL_CANTEEN_WITHOUT_MEAL'],
                                      'sub-total' => $GLOBALS['LANG_BILL_SUB_TOTAL'],
                                      'total-to-pay' => $GLOBALS['LANG_BILL_TOTAL_TO_PAY']
                                     );

             switch($GLOBALS['CONF_PDF_LIB'])
             {
                 case PDF_LIB_FPDF:
                     // We use FPDF to generate the bill in PDF, so we don't convert XML to HTML
                     saveToFile($Filename, 'wt', array($XmlData));
                     break;

                 case PDF_LIB_WKHTMLTOPDF:
                 case PDF_LIB_DOMPDF:
                 default:
                     // Transform XML to XSL
                     xmlXslProcess($XmlData, $GLOBALS['CONF_BILLS_PRINT_GLOBAL_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename, $ArrayLangParams);
                     break;
             }
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Print an annual bill
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
 *     - 2014-06-03 : use params to send translation of messages to the XSL template
 *
 * @since 2012-03-26
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ArrayData            Mixed array           Contains data to generate the annual bill of a family
 * @param $BackUrl              String                Url of the page to print
 * @param $Filename             String                Path filename to save the result
 */
 function printDetailsAnnualBillForm($DbConnection, $ArrayData, $BackUrl = "GenerateAnnualBills.php", $Filename = '')
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         if (!empty($ArrayData))
         {
             // Export to XML format
             // We get the content of the stylesheet
             $StyleSheetContent = getContentFile($GLOBALS['CONF_BILLS_PRINT_CSS_PATH'], 'rt');
             $XmlData = xmlOpenDocument($StyleSheetContent);

             // Add the current date
             $XmlData .= xmlTag('CurrentDate', date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"]));

             $XmlData .= xmlTag('FamilyLastname', $ArrayData['FamilyLastname']);
             $XmlData .= xmlTag('Year', $ArrayData['Year']);
             $XmlData .= xmlTag('FamilyBalance', $ArrayData['FamilyBalance']);

             // Export the bills of the year in XML
             $XmlData .= "<billslist>\n";
             foreach($ArrayData['BillID'] as $b => $BillID)
             {
                 $XmlData .= "<monthlybill>\n";

                 // Convert bill in XML
                 $XmlData .= xmlBill($DbConnection, $BillID);

                 // Convert bill payments in XML
                 $XmlData .= "\n<paymentsbill>\n";

                 $ArrayParams = array('BillID' => array($BillID));
                 $ArrayPayments = getFamilyPayments($DbConnection, $ArrayData['FamilyID'], $ArrayParams, 'PaymentDate');
                 if ((isset($ArrayPayments['PaymentID'])) && (!empty($ArrayPayments['PaymentID'])))
                 {
                     $ArrayKeys = array_keys($ArrayPayments);
                     foreach($ArrayPayments['PaymentID'] as $p => $PaymentID)
                     {
                         $XmlData .= "\t<payment>\n";
                         foreach($ArrayKeys as $k => $Key)
                         {
                             $Value = $ArrayPayments[$Key][$p];
                             $Key = strtolower($Key);
                             switch($Key)
                             {
                                 case 'paymentdate':
                                     if (empty($Value))
                                     {
                                         $Value = '';
                                     }
                                     else
                                     {
                                         $Value = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                                       strtotime($Value));
                                     }

                                     $XmlData .= "\t\t<$Key>$Value</$Key>\n";
                                     break;

                                 case 'paymentreceiptdate':
                                     if (empty($Value))
                                     {
                                         $Value = '';
                                     }
                                     else
                                     {
                                         $Value = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($Value));
                                     }

                                     $XmlData .= "\t\t<$Key>$Value</$Key>\n";
                                     break;

                                 case 'paymenttype':
                                     $Value = $GLOBALS['CONF_PAYMENTS_TYPES'][$Value];
                                     $XmlData .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                                     break;

                                 case 'paymentmode':
                                     $XmlData .= "\t\t<paymentmodeid>$Value</paymentmodeid>\n";
                                     $Value = $GLOBALS['CONF_PAYMENTS_MODES'][$Value];
                                     $XmlData .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                                     break;

                                 default:
                                     $XmlData .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                                     break;
                             }
                         }
                         $XmlData .= "\t</payment>\n";
                     }
                 }

                 $XmlData .= "</paymentsbill>\n";
                 $XmlData .= "</monthlybill>\n";
             }

             $XmlData .= "</billslist>\n";

             // Add a link to go back to the page
             // We get the parameters of the GET form
             if (!empty($BackUrl))
             {
                 $Link = "$BackUrl?";
                 foreach($_GET as $i => $CurrentValue)
                 {
                     $Link .= "&amp;$i=$CurrentValue";
                 }
                 $XmlData .= xmlHyperlink($GLOBALS["LANG_GO_BACK"], $Link);
             }

             // Close the document
             $XmlData .= xmlCloseDocument();

             // To translate messages in the bill
             $ArrayLangParams = array(
                                      'lang' => $GLOBALS['CONF_LANG'],
                                      'preview-title' => $GLOBALS['LANG_BILL_ANNUAL_PREVIEW_TITLE'],
                                      'for-family' => $GLOBALS['LANG_BILL_ANNUAL_FOR_FAMILY'],
                                      'to' => $GLOBALS['LANG_TO'],
                                      'family' => $GLOBALS['LANG_FAMILY'],
                                      'month' => ucfirst($GLOBALS['LANG_MONTH']),
                                      'payment-amount' => ucfirst($GLOBALS['LANG_PAYMENT_AMOUNT']),
                                      'payment-mode' => ucfirst($GLOBALS['LANG_BILL_ANNUAL_PAYMENT_MODE']),
                                      'bank' => ucfirst($GLOBALS['LANG_BANK']),
                                      'check-nb' => ucfirst($GLOBALS['LANG_PAYMENT_CHECK_NB']),
                                      'paid-amount' => ucfirst($GLOBALS['LANG_BILL_ANNUAL_PAID_AMOUNT']),
                                      'payment-date' => ucfirst($GLOBALS['LANG_DATE']),
                                      'balance' => ucfirst($GLOBALS['LANG_FAMILY_BALANCE']),
                                      'payment-unit' => $GLOBALS['CONF_PAYMENTS_UNIT'],
                                      'money' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_1']),
                                      'check' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_2']),
                                      'bank-transfert' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_3']),
                                      'credit-card' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_4']),
                                      'footer-part-1' => $GLOBALS['LANG_BILL_ANNUAL_FOOTER_MESSAGE_PART_ONE'],
                                      'footer-part-2' => $GLOBALS['LANG_BILL_ANNUAL_FOOTER_MESSAGE_PART_TWO']
                                     );

             switch($GLOBALS['CONF_PDF_LIB'])
             {
                 case PDF_LIB_FPDF:
                     // We use FPDF to generate the bill in PDF, so we don't convert XML to HTML
                     saveToFile($Filename, 'wt', array($XmlData));
                     break;

                 case PDF_LIB_WKHTMLTOPDF:
                 case PDF_LIB_DOMPDF:
                 default:
                     // Transform XML to XSL
                     xmlXslProcess($XmlData, $GLOBALS['CONF_BILLS_PRINT_ANNUAL_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename,
                                   $ArrayLangParams);
                     break;
             }
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Print several annual bills
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
 *     - 2014-06-03 : use params to send translation of messages to the XSL template
 *
 * @since 2012-03-28
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ArrayDataList        Mixed array           Contains data to generate the annual bills
 * @param $BackUrl              String                Url of the page to print
 * @param $Filename             String                Path filename to save the result
 */
 function printDetailsSeveralAnnualBillsForm($DbConnection, $ArrayDataList, $BackUrl = "GenerateAnnualBills.php", $Filename = '')
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         if (!empty($ArrayDataList))
         {
             // Export to XML format
             // We get the content of the stylesheet
             $StyleSheetContent = getContentFile($GLOBALS['CONF_BILLS_PRINT_CSS_PATH'], 'rt');
             $XmlData = xmlOpenDocument($StyleSheetContent);

             // Add the current date
             $XmlData .= xmlTag('CurrentDate', date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"]));
             $ArrayKeys = array_keys($ArrayDataList);
             $XmlData .= xmlTag('Year', $ArrayDataList[$ArrayKeys[0]]['Year']);
             unset($ArrayKeys);

             // Export all annual bills in XML
             $XmlData .= "<listannualbills>\n";

             foreach($ArrayDataList as $dl => $ArrayData)
             {
                 $XmlData .= "<annualbill>\n";
                 $XmlData .= xmlTag('FamilyLastname', $ArrayData['FamilyLastname']);
                 $XmlData .= xmlTag('FamilyBalance', $ArrayData['FamilyBalance']);

                 // Export the bills of the year in XML
                 $XmlData .= "<billslist>\n";
                 foreach($ArrayData['BillID'] as $b => $BillID)
                 {
                     $XmlData .= "<monthlybill>\n";

                     // Convert bill in XML
                     $XmlData .= xmlBill($DbConnection, $BillID);

                     // Convert bill payments in XML
                     $XmlData .= "\n<paymentsbill>\n";

                     $ArrayParams = array('BillID' => array($BillID));
                     $ArrayPayments = getFamilyPayments($DbConnection, $ArrayData['FamilyID'], $ArrayParams, 'PaymentDate');
                     if ((isset($ArrayPayments['PaymentID'])) && (!empty($ArrayPayments['PaymentID'])))
                     {
                         $ArrayKeys = array_keys($ArrayPayments);
                         foreach($ArrayPayments['PaymentID'] as $p => $PaymentID)
                         {
                             $XmlData .= "\t<payment>\n";
                             foreach($ArrayKeys as $k => $Key)
                             {
                                 $Value = $ArrayPayments[$Key][$p];
                                 $Key = strtolower($Key);
                                 switch($Key)
                                 {
                                     case 'paymentdate':
                                         if (empty($Value))
                                         {
                                             $Value = '';
                                         }
                                         else
                                         {
                                             $Value = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                                           strtotime($Value));
                                         }

                                         $XmlData .= "\t\t<$Key>$Value</$Key>\n";
                                         break;

                                     case 'paymentreceiptdate':
                                         if (empty($Value))
                                         {
                                             $Value = '';
                                         }
                                         else
                                         {
                                             $Value = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($Value));
                                         }

                                         $XmlData .= "\t\t<$Key>$Value</$Key>\n";
                                         break;

                                     case 'paymenttype':
                                         $Value = $GLOBALS['CONF_PAYMENTS_TYPES'][$Value];
                                         $XmlData .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                                         break;

                                     case 'paymentmode':
                                         $XmlData .= "\t\t<paymentmodeid>$Value</paymentmodeid>\n";
                                         $Value = $GLOBALS['CONF_PAYMENTS_MODES'][$Value];
                                         $XmlData .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                                         break;

                                     default:
                                         $XmlData .= "\t\t<$Key>".invFormatText($Value, 'XML')."</$Key>\n";
                                         break;
                                 }
                             }
                             $XmlData .= "\t</payment>\n";
                         }
                     }

                     $XmlData .= "</paymentsbill>\n";
                     $XmlData .= "</monthlybill>\n";
                 }

                 $XmlData .= "</billslist>\n";
                 $XmlData .= "</annualbill>\n";
             }

             $XmlData .= "</listannualbills>\n";

             // Add a link to go back to the page
             // We get the parameters of the GET form
             if (!empty($BackUrl))
             {
                 $Link = "$BackUrl?";
                 foreach($_GET as $i => $CurrentValue)
                 {
                     $Link .= "&amp;$i=$CurrentValue";
                 }
                 $XmlData .= xmlHyperlink($GLOBALS["LANG_GO_BACK"], $Link);
             }

             // Close the document
             $XmlData .= xmlCloseDocument();

             // To translate messages in the bill
             $ArrayLangParams = array(
                                      'lang' => $GLOBALS['CONF_LANG'],
                                      'preview-title' => $GLOBALS['LANG_BILL_ANNUAL_SEVERAL_BILLS_PREVIEW_TITLE'],
                                      'to' => $GLOBALS['LANG_TO'],
                                      'family' => $GLOBALS['LANG_FAMILY'],
                                      'month' => ucfirst($GLOBALS['LANG_MONTH']),
                                      'payment-amount' => ucfirst($GLOBALS['LANG_PAYMENT_AMOUNT']),
                                      'payment-mode' => ucfirst($GLOBALS['LANG_BILL_ANNUAL_PAYMENT_MODE']),
                                      'bank' => ucfirst($GLOBALS['LANG_BANK']),
                                      'check-nb' => ucfirst($GLOBALS['LANG_PAYMENT_CHECK_NB']),
                                      'paid-amount' => ucfirst($GLOBALS['LANG_BILL_ANNUAL_PAID_AMOUNT']),
                                      'payment-date' => ucfirst($GLOBALS['LANG_DATE']),
                                      'balance' => ucfirst($GLOBALS['LANG_FAMILY_BALANCE']),
                                      'payment-unit' => $GLOBALS['CONF_PAYMENTS_UNIT'],
                                      'money' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_1']),
                                      'check' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_2']),
                                      'bank-transfert' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_3']),
                                      'credit-card' => strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_4']),
                                      'footer-part-1' => $GLOBALS['LANG_BILL_ANNUAL_FOOTER_MESSAGE_PART_ONE'],
                                      'footer-part-2' => $GLOBALS['LANG_BILL_ANNUAL_FOOTER_MESSAGE_PART_TWO']
                                     );

             switch($GLOBALS['CONF_PDF_LIB'])
             {
                 case PDF_LIB_FPDF:
                     // We use FPDF to generate the bill in PDF, so we don't convert XML to HTML
                     saveToFile($Filename, 'wt', array($XmlData));
                     break;

                 case PDF_LIB_WKHTMLTOPDF:
                 case PDF_LIB_DOMPDF:
                 default:
                     // Transform XML to XSL
                     xmlXslProcess($XmlData, $GLOBALS['CONF_BILLS_PRINT_GLOBAL_ANNUAL_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename,
                                   $ArrayLangParams);
                     break;
             }
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Print a donation
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-08
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $DonationID           Integer               ID of the donation to print [1..n]
 * @param $BackUrl              String                Url of the page to print
 * @param $Filename             String                Path filename to save the result
 */
 function printDetailsDonationForm($DbConnection, $DonationID, $BackUrl = "GenerateTaxReceipts.php", $Filename = '')
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         if ($DonationID > 0)
         {
             // Export to XML format
             // We get the content of the stylesheet
             $StyleSheetContent = getContentFile($GLOBALS['CONF_BILLS_PRINT_CSS_PATH'], 'rt');
             $XmlData = xmlOpenDocument($StyleSheetContent);

             // Add the current date
             $CurrentDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"]);
             $CurrentStamp = strtotime(date('Y-m-d'));
             $Day = date('d', $CurrentStamp);
             $Month = date('m', $CurrentStamp);
             $Year = date('Y', $CurrentStamp);
             $XmlData .= xmlTag('CurrentDate', $CurrentDate, array('day' => $Day, "month" => $Month, "year" => $Year));

             // Export the bill in XML
             $XmlData .= xmlDonation($DbConnection, $DonationID);

             // Add a link to go back to the page
             // We get the parameters of the GET form
             if (!empty($BackUrl))
             {
                 $Link = "$BackUrl?";
                 foreach($_GET as $i => $CurrentValue)
                 {
                     $Link .= "&amp;$i=$CurrentValue";
                 }
                 $XmlData .= xmlHyperlink($GLOBALS["LANG_GO_BACK"], $Link);
             }

             // Close the document
             $XmlData .= xmlCloseDocument();

             // To translate messages in the donation
             $ArrayLangParams = array(
                                      'lang' => $GLOBALS['CONF_LANG']
                                     );

             switch($GLOBALS['CONF_PDF_LIB'])
             {
                 case PDF_LIB_FPDF:
                     // We use FPDF to generate the tax receipt of the donation in PDF, so we don't convert XML to HTML
                     if (!empty($Filename))
                     {
                         saveToFile($Filename, 'wt', array($XmlData));
                     }
                     else
                     {
                         // Transform XML to XSL
                         /*xmlXslProcess($XmlData, $GLOBALS['CONF_DONATION_TAX_RECEIPTS_PRINT_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename,
                                       $ArrayLangParams); */
                     }
                     break;

                 case PDF_LIB_WKHTMLTOPDF:
                 case PDF_LIB_DOMPDF:
                 default:
                     // Transform XML to XSL
                     /*xmlXslProcess($XmlData, $GLOBALS['CONF_DONATION_TAX_RECEIPTS_PRINT_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename,
                                   $ArrayLangParams); */
                     break;
             }
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Print several donations
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-16
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ArrayDonationsID     Array of Integers     ID of the donations to print
 * @param $BackUrl              String                Url of the page to print
 */
 function printDetailsSeveralDonationsForm($DbConnection, $ArrayDonationsID, $BackUrl = "GenerateTaxReceipts.php", $Filename = "")
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         if (!empty($ArrayDonationsID))
         {
             // Export to XML format
             // We get the content of the stylesheet
             $StyleSheetContent = getContentFile($GLOBALS['CONF_BILLS_PRINT_CSS_PATH'], 'rt');
             $XmlData = xmlOpenDocument($StyleSheetContent);

             // Add the current date
             $CurrentDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"]);
             $CurrentStamp = strtotime(date('Y-m-d'));
             $Day = date('d', $CurrentStamp);
             $Month = date('m', $CurrentStamp);
             $Year = date('Y', $CurrentStamp);
             $XmlData .= xmlTag('CurrentDate', $CurrentDate, array('day' => $Day, "month" => $Month, "year" => $Year));

             // Export the donations in XML
             $XmlData .= "<listdonations>\n";
             foreach($ArrayDonationsID as $b => $DonationID)
             {
                 $XmlData .= xmlDonation($DbConnection, $DonationID);
                 $XmlData .= "\n";
             }
             $XmlData .= "</listdonations>\n";

             // Add a link to go back to the page
             // We get the parameters of the GET form
             if (!empty($BackUrl))
             {
                 $Link = "$BackUrl?";
                 foreach($_GET as $i => $CurrentValue)
                 {
                     $Link .= "&amp;$i=$CurrentValue";
                 }
                 $XmlData .= xmlHyperlink($GLOBALS["LANG_GO_BACK"], $Link);
             }

             // Close the document
             $XmlData .= xmlCloseDocument();

             // To translate messages in the donation
             $ArrayLangParams = array(
                                      'lang' => $GLOBALS['CONF_LANG']
                                     );

             switch($GLOBALS['CONF_PDF_LIB'])
             {
                 case PDF_LIB_FPDF:
                     // We use FPDF to generate the bill in PDF, so we don't convert XML to HTML
                     saveToFile($Filename, 'wt', array($XmlData));
                     break;

                 case PDF_LIB_WKHTMLTOPDF:
                 case PDF_LIB_DOMPDF:
                 default:
                     // Transform XML to XSL
                     //xmlXslProcess($XmlData, $GLOBALS['CONF_BILLS_PRINT_GLOBAL_XSL_PATH'], XMLSTREAM_XSLFILE, $Filename, $ArrayLangParams);
                     break;
             }
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Convert a HTML file to PDF
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-06-08 : taken into account $ArrayParams
 *
 * @since 2012-02-24
 *
 * @param $SourceHtml             String                Path of the html file to convert
 * @param $DestPdf                String                Path of the result, a PDF file
 * @param $Orientation            String                Orientation of the paper
 * @param $DocumentType           Enum                  Type of document to convert (bill, annual bills...)
 * @param $ArrayParams            Mixed array           More parameters (ex : template, year...)
 *
 * @return Boolean                TRUE if the PDF file is created, FALSE otherwise
 */
 function html2pdf($SourceHtml, $DestPdf, $Orientation = 'portrait', $DocumentType = NULL, $ArrayParams = array())
 {
     $bResult = FALSE;
     switch($GLOBALS['CONF_PDF_LIB'])
     {
         case PDF_LIB_WKHTMLTOPDF:
             // We use the Wkhtmltopdf tool
             $bResult = html2pdfwkhtmltopdf($SourceHtml, $DestPdf, $Orientation);
             break;

         case PDF_LIB_FPDF:
             // We use the FPDF library
             $bResult = xml2pdffpdf($SourceHtml, $DestPdf, $Orientation, $DocumentType, $ArrayParams);
             break;

         case PDF_LIB_DOMPDF:
         default:
             // We use the Dompdf library
             $bResult = html2pdfdompdf($SourceHtml, $DestPdf, $Orientation);
             break;
     }

     return $bResult;
 }


/**
 * Convert a HTML file to PDF thanks to the WKHTMLTOPDF tool
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-24
 *
 * @param $SourceHtml             String                Path of the html file to convert
 * @param $DestPdf                String                Path of the result, a PDF file
 * @param $Orientation            String                Orientation of the paper
 *
 * @return Boolean                TRUE if the PDF file is created, FALSE otherwise
 */
 function html2pdfwkhtmltopdf($SourceHtml, $DestPdf, $Orientation = 'portrait')
 {
     if ((!empty($SourceHtml)) && (!empty($DestPdf)) && (file_exists($SourceHtml)))
     {
         $TmpSourcePath = str_replace(array("\\"), array('/'), $SourceHtml);
         $TmpDestPath = str_replace(array("\\"), array('/'), $DestPdf);
         switch($GLOBALS['CONF_OS'])
         {
             case APPL_OS_WINDOWS:
                 // For Windows
                 $sCmd = "".$GLOBALS['CONF_PDF_BIN_PATH_FOR_OS'][PDF_LIB_WKHTMLTOPDF][$GLOBALS['CONF_OS']];
                 $sCmd .= " --outline \"$TmpSourcePath\"";  // Source
                 $sCmd .= " \"$TmpDestPath\"";  // Dest
                 break;

             case APPL_OS_LINUX:
                 // For Linux
                 $sCmd = "\"".$GLOBALS['CONF_PDF_BIN_PATH_FOR_OS'][PDF_LIB_WKHTMLTOPDF][$GLOBALS['CONF_OS']]."\"";
                 $sCmd .= " --outline \"$TmpSourcePath\"";  // Source
                 $sCmd .= " \"$TmpDestPath\"";  // Dest
                 break;
         }

         shell_exec($sCmd);

         if (file_exists($DestPdf))
         {
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Convert some XML files types to PDF thanks to the FPDF library
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2014-06-05 : change the margins of the annual bill
 *     - 2016-06-08 : taken into account tax receipt of donation
 *
 * @since 2012-05-04
 *
 * @param $SourceHtml             String                Path of the html file to convert
 * @param $DestPdf                String                Path of the result, a PDF file
 * @param $Orientation            String                Orientation of the paper
 * @param $DocumentType           Enum                  Type of document to convert (bill, annual bills...)
 * @param $ArrayParams            Mixed array           More parameters (ex : template, year...)
 *
 * @return Boolean                TRUE if the PDF file is created, FALSE otherwise
 */
 function xml2pdffpdf($SourceHtml, $DestPdf, $Orientation = 'portrait', $DocumentType = NULL, $ArrayParams = array())
 {
     if ((!empty($SourceHtml)) && (!empty($DestPdf)) && (file_exists($SourceHtml)))
     {
         $TmpSourcePath = str_replace(array("\\"), array('/'), $SourceHtml);
         $TmpDestPath = str_replace(array("\\"), array('/'), $DestPdf);
         switch($GLOBALS['CONF_OS'])
         {
             case APPL_OS_WINDOWS:
             case APPL_OS_LINUX:
                 // For Windows and Linux
                 include_once($GLOBALS['CONF_PDF_BIN_PATH_FOR_OS'][PDF_LIB_FPDF][$GLOBALS['CONF_OS']]);
                 break;
         }

         // Get the content of the XML file
         $XmlDoc = simplexml_load_file($SourceHtml);

         switch($DocumentType)
         {
             case MONTHLY_BILL_DOCTYPE:
                 // Generate the PDF of a monthly bill
                 // Start the PDF
                 $pdf = new FPDF($Orientation, "cm", "A4");
                 $pdf->SetAutoPageBreak("auto", 2);
                 $pdf->SetFont("Arial", "", 11);
                 $pdf->SetTextColor(0, 0, 0);
                 $pdf->SetMargins(3.5, 1.3, 3.5);
                 $pdf->AddPage();
                 fpdfBill($pdf, $XmlDoc->bill[0]);
                 break;

             case ALL_MONTHLY_BILLS_DOCTYPE:
                 // Generate the PDF of all monthly bills
                 $pdf = new FPDF($Orientation, "cm", "A4");
                 $pdf->SetAutoPageBreak("auto", 2);
                 $pdf->SetFont("Arial", "", 11);
                 $pdf->SetTextColor(0, 0, 0);
                 $pdf->SetMargins(0.5, 1.3, 0.5);
                 $iListBillsSize = count($XmlDoc->listbills->bill);
                 for($b = 0; $b < $iListBillsSize; $b++)
                 {
                     $iMod = ($b + 1) % 4;
                     switch($iMod)
                     {
                         case 1:
                             $pdf->AddPage();
                             fpdfBill($pdf, $XmlDoc->listbills->bill[$b]);
                             break;

                         case 2;
                             fpdfBill($pdf, $XmlDoc->listbills->bill[$b], 14.5, NULL);
                             break;

                         case 3:
                             fpdfBill($pdf, $XmlDoc->listbills->bill[$b], NULL, 8.5 + 1.3);
                             break;

                         case 0:
                             fpdfBill($pdf, $XmlDoc->listbills->bill[$b], 14.5, 8.5 + 1.3);
                             break;
                     }
                 }
                 break;

             case ANNUAL_BILL_DOCTYPE:
                 // Generate the PDF of an annual bill
                 $pdf = new FPDF($Orientation, "cm", "A4");
                 $pdf->SetAutoPageBreak("auto", 2);
                 $pdf->SetFont("Arial", "", 11);
                 $pdf->SetTextColor(0, 0, 0);
                 $pdf->SetMargins(1.5, 1.3, 1.5);
                 fpdfAnnualBill($pdf, $XmlDoc, 2.0, 1.3);
                 break;

             case ALL_ANNUAL_BILLS_DOCTYPE:
                 // Generate the PDF of all annual bills
                 $pdf = new FPDF($Orientation, "cm", "A4");
                 $pdf->SetAutoPageBreak("auto", 2);
                 $pdf->SetFont("Arial", "", 11);
                 $pdf->SetTextColor(0, 0, 0);
                 $pdf->SetMargins(1.5, 1.3, 1.5);
                 $iListBillsSize = count($XmlDoc->listannualbills->annualbill);
                 $XmlYear = $XmlDoc->year;
                 $XmlCurrentDate = $XmlDoc->currentdate;
                 for($b = 0; $b < $iListBillsSize; $b++)
                 {
                     fpdfAnnualBill($pdf, $XmlDoc->listannualbills->annualbill[$b], 1.5, 1.3, $XmlYear, $XmlCurrentDate);
                 }
                 break;

             case DONATION_TAX_RECEIPT_DOCTYPE:
                 // Generate the PDF of a tax receipt of a donation
                 if (!empty($ArrayParams))
                 {
                     $pdf = new FPDI();
                     $pdf->SetFont("Arial", "", 11);
                     $pdf->SetTextColor(0, 0, 0);
                     $pdf->AddPage();
                     $pdf->SetSourceFile($GLOBALS['CONF_PRINT_TEMPLATES_DIRECTORY_HDD'].$ArrayParams[Template]);
                     $CurrentDate = $XmlDoc->currentdate->attributes()->year.'-'.$XmlDoc->currentdate->attributes()->month
                                    .'-'.$XmlDoc->currentdate->attributes()->day;

                     fpdfDonation($pdf, $XmlDoc->donation[0], $ArrayParams, NULL, NULL, $CurrentDate);
                 }
                 else
                 {
                     // Error
                     return FALSE;
                 }
                 break;

             case ALL_DONATION_TAX_RECEIPT_DOCTYPE:
                 // Generate the PDF containing several tax receipts of donations
                 if (!empty($ArrayParams))
                 {
                     $pdf = new FPDI();
                     $pdf->SetFont("Arial", "", 11);
                     $pdf->SetTextColor(0, 0, 0);
                     $pdf->AddPage();
                     $pdf->SetSourceFile($GLOBALS['CONF_PRINT_TEMPLATES_DIRECTORY_HDD'].$ArrayParams[Template]);
                     $CurrentDate = $XmlDoc->currentdate->attributes()->year.'-'.$XmlDoc->currentdate->attributes()->month
                                    .'-'.$XmlDoc->currentdate->attributes()->day;

                     $iListDonationsSize = count($XmlDoc->listdonations->donation);
                     for($d = 0; $d < $iListDonationsSize; $d++)
                     {
                         if ($d > 0)
                         {
                             $pdf->AddPage();
                         }

                         fpdfDonation($pdf, $XmlDoc->listdonations->donation[$d], $ArrayParams, NULL, NULL, $CurrentDate);
                     }
                 }
                 else
                 {
                     // Error
                     return FALSE;
                 }
                 break;

             default:
                 // Do nothing
                 break;
         }

         $pdf->Output($DestPdf, "F");

         if (file_exists($DestPdf))
         {
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Generate PDF data for a monthly bill, for the FPDF library
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2014-02-03 : taken into account BillNurseryNbDelays field
 *     - 2014-06-03 : use LANG consts to translate all messages of the bill
 *
 * @since 2012-05-04
 *
 * @param $pdf                    Reference             Reference on a FPDF object
 * @param $XmlData                String                Bill in XML format
 * @param $PosX                   Float                 Position X of the cursor
 * @param $PosY                   Float                 Position Y of the cursor
 */
 function fpdfBill(&$pdf, $XmlData, $PosX = NULL, $PosY = NULL)
 {
     if (!empty($XmlData))
     {
         $MarginBorder = 0.4;
         $BillWidth = 14;
         $BillHeight = 8.5;

         $x = $PosX;
         if (is_null($PosX))
         {
             $x = $pdf->GetX();
         }

         $y = $PosY;
         if (is_null($PosY))
         {
             $y = $pdf->GetY();
         }

         // Borders of the bill
         $pdf->Rect($x, $y, $BillWidth, $BillHeight);

         // Family name
         $pdf->SetFont("Arial", "B", 14);
         $sWidth = $pdf->GetStringWidth($GLOBALS['LANG_FAMILY'].' ');
         $pdf->Text($x + $MarginBorder, $y + 0.6, $GLOBALS['LANG_FAMILY'].' ');
         $pdf->SetFont("Arial", "B", 12);
         $pdf->Text($x + $MarginBorder + $sWidth, $y + 0.6, strtoupper($XmlData->detailsbill[0]->familylastname));

         // Date of the bill
         $pdf->SetFont("Arial", "", 14);
         $pdf->Text($x + 7.5, $y + 0.6, $GLOBALS['LANG_BILL_FOR_DATE']);
         $pdf->SetFont("Arial", "", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billfordate);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 0.6, $XmlData->detailsbill[0]->billfordate);

         // Date of the generation of the bill
         $pdf->SetFont("Arial", "", 14);
         $pdf->Text($x + $MarginBorder, $y + 1.6, ucfirst($GLOBALS['LANG_TO']));
         $pdf->SetFont("Arial", "", 12);
         $pdf->Text($x + 1.6, $y + 1.6, $XmlData->detailsbill[0]->billdate);

         // Previsous balance
         $pdf->SetFont("Arial", "", 10);
         $pdf->Text($x + $MarginBorder, $y + 2.2, $GLOBALS['LANG_BILL_PREVIOUS_MONTHS_BALANCE']);
         $pdf->SetFont("Arial", "", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billpreviousbalance);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 2.2, $XmlData->detailsbill[0]->billpreviousbalance);

         // Bill deposit
         $pdf->SetFont("Arial", "", 10);
         $pdf->Text($x + $MarginBorder, $y + 2.6, $GLOBALS['LANG_BILL_ADVANCE_PAID']);
         $pdf->SetFont("Arial", "", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billdeposit);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 2.6, $XmlData->detailsbill[0]->billdeposit);

         // Monthly contribution
         // We compute the max width of the labels in the bill
         $pdf->SetFont("Arial", "", 14);
         $sMaxLabelWidth = $pdf->GetStringWidth($GLOBALS['LANG_BILL_CANTEEN_WITHOUT_MEAL']);

         $pdf->Text($x + $MarginBorder, $y + 4, $GLOBALS['LANG_BILL_MONTHLY_CONTRIBUTION']);
         $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 0.2, $y + 4, $XmlData->detailsbill[0]->billmonthyear);
         $pdf->SetFont("Arial", "", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billmonthlycontribution);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 4, $XmlData->detailsbill[0]->billmonthlycontribution);

         // Canteen registrations
         $pdf->SetFont("Arial", "", 14);
         $pdf->Text($x + $MarginBorder, $y + 5.1, $GLOBALS["LANG_CANTEEN"]);
         $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 0.2, $y + 5.1, $XmlData->detailsbill[0]->billmonthyear);
         $sWidth = $pdf->GetStringWidth("(".$XmlData->detailsbill[0]->nbcanteenregistrations.")");
         $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 4.2 - ($sWidth / 2), $y + 5.1, "(".$XmlData->detailsbill[0]->nbcanteenregistrations.")");
         $pdf->SetFont("Arial", "", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billcanteenamount);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 5.1, $XmlData->detailsbill[0]->billcanteenamount);

         // Nursery registrations
         $pdf->SetFont("Arial", "", 14);
         $pdf->Text($x + $MarginBorder, $y + 5.7, $GLOBALS["LANG_NURSERY"]);
         $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 0.2, $y + 5.7, $XmlData->detailsbill[0]->billmonthyear);

         if ($XmlData->detailsbill[0]->nbnurserydelays > 0)
         {
             // There are some nursery delays
             $sWidth = $pdf->GetStringWidth("(".$XmlData->detailsbill[0]->nbnurseryregistrations." / ".$XmlData->detailsbill[0]->nbnurserydelays.")");
             $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 4.2 - ($sWidth / 2), $y + 5.7, "(".$XmlData->detailsbill[0]->nbnurseryregistrations." / ".$XmlData->detailsbill[0]->nbnurserydelays.")");
         }
         else
         {
             // No nursery delay
             $sWidth = $pdf->GetStringWidth("(".$XmlData->detailsbill[0]->nbnurseryregistrations.")");
             $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 4.2 - ($sWidth / 2), $y + 5.7, "(".$XmlData->detailsbill[0]->nbnurseryregistrations.")");
         }

         $pdf->SetFont("Arial", "", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billnurseryamount);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 5.7, $XmlData->detailsbill[0]->billnurseryamount);

         // Canteen without meals
         $pdf->SetFont("Arial", "", 14);
         $pdf->Text($x + $MarginBorder, $y + 6.3, $GLOBALS['LANG_BILL_CANTEEN_WITHOUT_MEAL']);
         $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 0.2, $y + 6.3, $XmlData->detailsbill[0]->billmonthyear);
         $sWidth = $pdf->GetStringWidth("(".$XmlData->detailsbill[0]->nbwithoutmeals.")");
         $pdf->Text($x + $MarginBorder + $sMaxLabelWidth + 4.2 - ($sWidth / 2), $y + 6.3, "(".$XmlData->detailsbill[0]->nbwithoutmeals.")");
         $pdf->SetFont("Arial", "", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billwithoutmealamount);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 6.3, $XmlData->detailsbill[0]->billwithoutmealamount);

         // Sub-total
         $pdf->SetFont("Arial", "", 12);
         $pdf->Text($x + 9.7, $y + 7, $GLOBALS['LANG_BILL_SUB_TOTAL']);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billsubtotalamount);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 7, $XmlData->detailsbill[0]->billsubtotalamount);

         // Total of the bill
         $pdf->Rect($x + $MarginBorder - 0.1, $y + 7.1, $BillWidth - 2 * $MarginBorder + 0.1 * 2, 1);
         $pdf->Rect($x + $MarginBorder - 0.08, $y + 7.11, $BillWidth - 2 * $MarginBorder + 0.1 * 2, 1);
         $pdf->SetFont("Arial", "", 14);
         $pdf->Text($x + $MarginBorder, $y + 7.75, $GLOBALS['LANG_BILL_TOTAL_TO_PAY']);
         $pdf->SetFont("Arial", "B", 12);
         $sWidth = $pdf->GetStringWidth($XmlData->detailsbill[0]->billtotalamount);
         $pdf->Text($x + $BillWidth - $sWidth - $MarginBorder, $y + 7.75, $XmlData->detailsbill[0]->billtotalamount);
     }
 }


/**
 * Generate PDF data for an annual bill, for the FPDF library
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
 *     - 2014-06-03 : use LANG consts to translate all messages of the bill and change
 *                    the width of the annual bill (17 -> 18)
 *
 * @since 2012-05-09
 *
 * @param $pdf                    Reference             Reference on a FPDF object
 * @param $XmlData                String                Annual bill in XML format
 * @param $PosX                   Float                 Position X of the cursor
 * @param $PosY                   Float                 Position Y of the cursor
 * @param $Year                   String                The year of the annual bill
 * @param $CurrentDate            String                Current date when the annual bill is generated
 */
 function fpdfAnnualBill(&$pdf, $XmlData, $PosX = NULL, $PosY = NULL, $Year = NULL, $CurrentDate = NULL)
 {
     if (!empty($XmlData))
     {
         $MarginBorder = 0.4;
         $ArrayMaxColsWidth = array(3, 2.5, 1.5, 3, 2.5, 3, 2.5);
         $BillWidth = array_sum($ArrayMaxColsWidth);
         $TableCellHeight = 0.8;

         $x = $PosX;
         if (is_null($PosX))
         {
             $x = $pdf->GetX();
         }

         $y = $PosY;
         if (is_null($PosY))
         {
             $y = $pdf->GetY();
         }

         $pdf->AddPage();

         // Family name
         $pdf->SetFont("Arial", "B", 14);
         $sWidth = $pdf->GetStringWidth($GLOBALS['LANG_FAMILY'].' ');
         $pdf->Text($x, $y + 0.6, $GLOBALS['LANG_FAMILY'].' ');
         $pdf->Text($x + $sWidth, $y + 0.6, strtoupper($XmlData->familylastname));

         // Year (center)
         if (is_null($Year))
         {
             $CurrentYear = $XmlData->year;
         }
         else
         {
             $CurrentYear = $Year;
         }

         $pdf->SetFont("Arial", "", 16);
         $pdf->Rect($x, $y + 1.1, $BillWidth, 1.0);
         $sWidth = $pdf->GetStringWidth($CurrentYear);
         $pdf->Text($x + ($BillWidth - $sWidth) / 2, $y + 1.8, $CurrentYear);

         // Create a data structure for the table to display
         // First, the header of the table
         $ArrayFPDFData = array(
                                array(ucfirst($GLOBALS['LANG_MONTH'])),
                                array(ucfirst($GLOBALS['LANG_PAYMENT_AMOUNT'])),
                                array(ucfirst($GLOBALS['LANG_BILL_ANNUAL_PAYMENT_MODE'])),
                                array(ucfirst($GLOBALS['LANG_BANK'])),
                                array(ucfirst($GLOBALS['LANG_PAYMENT_CHECK_NB'])),
                                array(ucfirst($GLOBALS['LANG_BILL_ANNUAL_PAID_AMOUNT'])),
                                array(ucfirst($GLOBALS['LANG_DATE']))
                               );

         // The bills and payments data
         $iNbBills = count($XmlData->billslist->monthlybill);
         for($b = 0; $b < $iNbBills; $b++)
         {
             // For each bill, one line for the previous balance
             if ($b == 0)
             {
                 $ArrayFPDFData[0][] = ucfirst($GLOBALS['LANG_FAMILY_BALANCE']);
             }
             else
             {
                 $ArrayFPDFData[0][] = "";
             }

             $ArrayFPDFData[1][] = $XmlData->billslist->monthlybill[$b]->bill[0]->detailsbill[0]->billpreviousbalance
                                   .' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
             $ArrayFPDFData[2][] = "";
             $ArrayFPDFData[3][] = "";
             $ArrayFPDFData[4][] = "";
             $ArrayFPDFData[5][] = "";
             $ArrayFPDFData[6][] = "";

             // The second line is for the current month (bill amount and payments)
             $ArrayFPDFData[0][] = $XmlData->billslist->monthlybill[$b]->bill[0]->detailsbill[0]->billmonthyear;
             $ArrayFPDFData[1][] = $XmlData->billslist->monthlybill[$b]->bill[0]->detailsbill[0]->billsubtotalamount
                                   .' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

             $iNbPayments = count($XmlData->billslist->monthlybill->paymentsbill->payment);
             $sPaymentMode = "";
             $sBankName = "";
             $sCheckNb = "";
             $sPaymentAmount = "";
             $sPaymentDate = "";
             for($p = 0; $p < $iNbPayments; $p++)
             {
                 // Payment mode
                 if (!empty($sPaymentMode))
                 {
                     $sPaymentMode .= "\n";
                 }

                 switch($XmlData->billslist->monthlybill->paymentsbill->payment[$p]->paymentmodeid)
                 {
                     case 0:
                         // Money
                         $sPaymentMode .= strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_1']);
                         break;

                     case 1:
                         // Check
                         $sPaymentMode .= strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_2']);
                         break;

                     case 2:
                         // Bank transfert
                         $sPaymentMode .= strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_3']);
                         break;

                     case 3:
                         // Credit card
                         $sPaymentMode .= strToUpper($GLOBALS['LANG_PAYMENT_MODE_SHORT_LABEL_4']);
                         break;
                 }

                 // Bank name
                 if (!empty($XmlData->billslist->monthlybill->paymentsbill->payment[$p]->bankacronym))
                 {
                     if (!empty($sBankName))
                     {
                         $sBankName .= "\n";
                     }

                     $sBankName .= $XmlData->billslist->monthlybill->paymentsbill->payment[$p]->bankacronym;
                 }

                 // Check number
                 if (!empty($XmlData->billslist->monthlybill->paymentsbill->payment[$p]->paymentchecknb))
                 {
                     if (!empty($sCheckNb))
                     {
                         $sCheckNb .= "\n";
                     }

                     $sCheckNb .= $XmlData->billslist->monthlybill->paymentsbill->payment[$p]->paymentchecknb;
                 }

                 // Payment amount
                 if (!empty($sPaymentAmount))
                 {
                     $sPaymentAmount .= "\n";
                 }

                 $sPaymentAmount .= $XmlData->billslist->monthlybill->paymentsbill->payment[$p]->paymentamount
                                    .' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                 // Payment date
                 if (!empty($sPaymentDate))
                 {
                     $sPaymentDate .= "\n";
                 }

                 $sPaymentDate .= $XmlData->billslist->monthlybill->paymentsbill->payment[$p]->paymentreceiptdate;
             }

             $ArrayFPDFData[2][] = $sPaymentMode;
             $ArrayFPDFData[3][] = $sBankName;
             $ArrayFPDFData[4][] = $sCheckNb;
             $ArrayFPDFData[5][] = $sPaymentAmount;
             $ArrayFPDFData[6][] = $sPaymentDate;
         }

         // At the end of the annuel bill, we display the current balance of the family
         $ArrayFPDFData[0][] = ucfirst($GLOBALS['LANG_FAMILY_BALANCE']);
         $ArrayFPDFData[1][] = $XmlData->familybalance.' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
         $ArrayFPDFData[2][] = "";
         $ArrayFPDFData[3][] = "";
         $ArrayFPDFData[4][] = "";
         $ArrayFPDFData[5][] = "";
         $ArrayFPDFData[6][] = "";

         // We define the width of each column
         $pdf->SetFont("Arial", "", 12);

         // Now, we can display the data in the FPDF table
         $fCellY = 3.4;
         $iNbCols = count($ArrayFPDFData);
         $iNbRows = count($ArrayFPDFData[0]);
         for($i = 0; $i < $iNbRows; $i++)
         {
             $pdf->SetY($fCellY);
             $iNbCellLines = 1;

             // Detect the height of the row
             for($j = 0; $j < $iNbCols; $j++)
             {
                 // Detect the nb of lines for the cell
                 $sWidth = $pdf->GetStringWidth($ArrayFPDFData[$j][$i]);
                 $iNbCellLines = max($iNbCellLines, ceil($sWidth / $ArrayMaxColsWidth[$j]));
             }

             for($j = 0; $j < $iNbCols; $j++)
             {
                 if ($i == 0)
                 {
                     $cAlign = 'L';
                 }
                 else
                 {
                     switch($j)
                     {
                         case 1:
                         case 4:
                         case 5:
                         case 6:
                             $cAlign = 'R';
                             break;

                         default:
                             $cAlign = 'L';
                             break;
                     }
                 }

                 $fCellX = $x;
                 for($p = 0; $p < $j; $p++)
                 {
                     $fCellX += $ArrayMaxColsWidth[$p];
                 }

                 $pdf->SetX($fCellX);

                 // Check if the date of the cell must be wrapped
                 $sCellData = $ArrayFPDFData[$j][$i];
                 $sWidth = $pdf->GetStringWidth($sCellData);
                 if (($sWidth > $ArrayMaxColsWidth[$j]) || (strpos($sCellData, "\n") !== FALSE))
                 {
                     // Yes, we must display the data on several lines (we must add \n)
                     $iNbChars = strlen($ArrayFPDFData[$j][$i]);
                     $iMaxNbCharsByLine = floor(($ArrayMaxColsWidth[$j] * $iNbChars) / $sWidth);
                     $sCellData = wordwrap($sCellData, $iMaxNbCharsByLine);

                     // We dispaly an empty cell
                     $pdf->Cell($ArrayMaxColsWidth[$j], $TableCellHeight * $iNbCellLines, "", 1, $cAlign, false);

                     // We display texts
                     $ArrayTexts = explode("\n", $sCellData);
                     $fTextStepY = ($TableCellHeight * $iNbCellLines) / count($ArrayTexts);
                     foreach($ArrayTexts as $t => $CurrText)
                     {
                         $fTextPosY = $fCellY - 0.15 + ($t + 1) * $fTextStepY;

                         switch($cAlign)
                         {
                             case 'R':
                                 $sWidth = $pdf->GetStringWidth($CurrText);
                                 $fTextPosX = $fCellX + $ArrayMaxColsWidth[$j] - $sWidth - 0.15;
                                 break;

                             case 'L':
                             default:
                                 $fTextPosX = $fCellX + 0.15;
                                 break;
                         }

                         $pdf->Text($fTextPosX, $fTextPosY, $CurrText);
                     }
                 }
                 else
                 {
                     $pdf->Cell($ArrayMaxColsWidth[$j], $TableCellHeight * $iNbCellLines, $sCellData, 1, 0, $cAlign, false);
                 }
             }

             $fCellY += $TableCellHeight * $iNbCellLines;
         }

         // At the end of the annual bill, we display a message
         $pdf->SetFont("Arial", "", 10);
         $pdf->SetY($fCellY);
         $fCellX = $x;
         $pdf->SetX($fCellX);

         if (is_null($CurrentDate))
         {
             $CurrentDate = $XmlData->currentdate;
         }

         $sMessage = $GLOBALS['LANG_BILL_ANNUAL_FOOTER_MESSAGE_PART_ONE']." $CurrentDate. ".$GLOBALS['LANG_BILL_ANNUAL_FOOTER_MESSAGE_PART_TWO'];
         $pdf->MultiCell($BillWidth, $TableCellHeight, $sMessage, 1, 'L', false);
     }
 }


/**
 * Generate PDF data for a tax receipt, for the FPDI library
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-08
 *
 * @param $pdf                    Reference             Reference on a FPDF object
 * @param $XmlData                String                Bill in XML format
 * @param $ArrayParams            Mixed array           Parameters of the tax receipt
 * @param $PosX                   Float                 Position X of the cursor
 * @param $PosY                   Float                 Position Y of the cursor
 * @param $CurrentDate            String                Current date when the tax receipt is generated
 */
 function fpdfDonation(&$pdf, $XmlData, $ArrayParams, $PosX = NULL, $PosY = NULL, $CurrentDate = NULL)
 {
     if ((!empty($XmlData)) && (!empty($ArrayParams)))
     {
         $x = $PosX;
         if (is_null($PosX))
         {
             $x = $pdf->GetX();
         }

         $y = $PosY;
         if (is_null($PosY))
         {
             $y = $pdf->GetY();
         }

         if (empty($CurrentDate))
         {
             $CurrentDate = date('Y-m-d');
         }

         // Current page : n°1
         $iCurrentPage = 1;
         $tplIdx = $pdf->importPage($iCurrentPage);
         $pdf->useTemplate($tplIdx);

         foreach($ArrayParams[Page] as $Page => $PageParameters)
         {
             if ($Page > $iCurrentPage)
             {
                 // We add a new page and we load the template
                 $iCurrentPage = $Page;

                 $pdf->AddPage();
                 $tplIdx = $pdf->importPage($iCurrentPage);
                 $pdf->useTemplate($tplIdx);
             }

             foreach($PageParameters as $pp => $CurrentPageParameters)
             {
                 switch($pp)
                 {
                     case Recipient:
                         // Infos about the recipient
                         foreach($CurrentPageParameters as $npp => $ParametersField)
                         {
                             if (strToLower($npp) == "reference")
                             {
                                 $pdf->Text($x + $ParametersField[PosX], $y + $ParametersField[PosY],
                                            $XmlData->detailsdonation[0]->donationreference);
                             }
                             else
                             {
                                 $pdf->Text($x + $ParametersField[PosX], $y + $ParametersField[PosY], $ParametersField[Text]);
                             }
                         }
                         break;

                     case Donator:
                         // Infos about the donator
                         foreach($CurrentPageParameters as $npp => $ParametersField)
                         {
                             // Initialization for the next field
                             $sText = '';
                             $sImage = '';

                             if (isset($ParametersField[PosX]))
                             {
                                 $PosX = $ParametersField[PosX];
                                 $PosY = $ParametersField[PosY];
                             }
                             else
                             {
                                 $PosX = 0;
                                 $PosY = 0;
                             }

                             switch(strTolower($npp))
                             {
                                 case 'lastname':
                                     // Lastname of the donator
                                     $sText = ucFirst($XmlData->detailsdonation[0]->donationlastname);
                                     if (mb_detect_encoding($sText) == 'UTF-8')
                                     {
                                         $sText = utf8_decode($sText);
                                     }
                                     break;

                                 case 'firstname':
                                     // Firstname of the donator
                                     $sText = ucFirst($XmlData->detailsdonation[0]->donationfirstname);
                                     if (mb_detect_encoding($sText) == 'UTF-8')
                                     {
                                         $sText = utf8_decode($sText);
                                     }
                                     break;

                                 case 'address':
                                     // Address of the donator (num of the street and street name)
                                     $sText = ucFirst($XmlData->detailsdonation[0]->donationaddress);
                                     if (mb_detect_encoding($sText) == 'UTF-8')
                                     {
                                         $sText = utf8_decode($sText);
                                     }
                                     break;

                                 case 'zipcode':
                                     // Zip code of the town of the donator
                                     $sText = $XmlData->detailsdonation[0]->towncode;
                                     if (mb_detect_encoding($sText) == 'UTF-8')
                                     {
                                         $sText = utf8_decode($sText);
                                     }
                                     break;

                                 case 'townname':
                                     // Town of the donator
                                     $sText = ucFirst($XmlData->detailsdonation[0]->townname);
                                     if (mb_detect_encoding($sText) == 'UTF-8')
                                     {
                                         $sText = utf8_decode($sText);
                                     }
                                     break;

                                 case 'amount':
                                     // Amount (value) of the donation
                                     $sText = str_replace(array('.'), array(','),
                                                          $XmlData->detailsdonation[0]->donationvalue);
                                     break;

                                 case 'amountinletters':
                                     // Amount (value) of the donation in letters
                                     $obj = new nuts($XmlData->detailsdonation[0]->donationvalue, $ArrayParams[Unit]);
                                     $sText = $obj->convert($ArrayParams[Language]);

                                     // Remove centimes ???
                                     if ((float)$XmlData->detailsdonation[0]->donationvalue - floor((float)$XmlData->detailsdonation[0]->donationvalue) == 0)
                                     {
                                         // Yes !
                                         $ArrayTmp = explode(',', $sText);
                                         $sText = $ArrayTmp[0];
                                         unset($ArrayTmp);
                                     }

                                     unset($obj);
                                     break;

                                 case 'receptiondateday':
                                     // Day of the date of the donation
                                     $sText = $XmlData->detailsdonation[0]->donationreceptiondate->attributes()->day;
                                     break;

                                 case 'receptiondatemonth':
                                     // Month of the date of the donation
                                     $sText = $XmlData->detailsdonation[0]->donationreceptiondate->attributes()->month;
                                     break;

                                 case 'receptiondateyear':
                                     // Year of the date of the donation
                                     $sText = $XmlData->detailsdonation[0]->donationreceptiondate->attributes()->year;
                                     break;

                                 case 'entity':
                                     // Entity of the donator : we search the right article
                                     foreach($ParametersField as $e => $Entity)
                                     {
                                         if (in_array($XmlData->detailsdonation[0]->donationentity, $Entity[Items]))
                                         {
                                             $sText = $ParametersField[$e][Text];
                                             $PosX = $ParametersField[$e][PosX];
                                             $PosY = $ParametersField[$e][PosY];
                                         }
                                     }
                                     break;

                                 case 'type':
                                     // Type of the donation (form donation)
                                     $sText = $ParametersField[Text];
                                     break;

                                 case 'nature':
                                     // Nature of the donation : we search the right nature
                                     foreach($ParametersField as $n => $Nature)
                                     {
                                         if (in_array($XmlData->detailsdonation[0]->donationnature, $Nature[Items]))
                                         {
                                             $sText = $ParametersField[$n][Text];
                                             $PosX = $ParametersField[$n][PosX];
                                             $PosY = $ParametersField[$n][PosY];
                                         }
                                     }
                                     break;

                                 case 'paymentmode':
                                     // Payment mode : we search the right payment mode
                                     foreach($ParametersField as $p => $PaymentMode)
                                     {
                                         if (in_array($XmlData->detailsdonation[0]->donationpaymentmode, $PaymentMode[Items]))
                                         {
                                             $sText = $ParametersField[$p][Text];
                                             $PosX = $ParametersField[$p][PosX];
                                             $PosY = $ParametersField[$p][PosY];
                                         }
                                     }
                                     break;

                                 case 'taxreceiptdateday':
                                     // Day of the current date of the tax receipt
                                     $sText = date('d', strtotime($CurrentDate));
                                     break;

                                 case 'taxreceiptdatemonth':
                                     // Month of the current date of the tax receipt
                                     $sText = date('m', strtotime($CurrentDate));
                                     break;

                                 case 'taxreceiptdateyear':
                                     // Year of the current date of the tax receipt
                                     $sText = date('Y', strtotime($CurrentDate));
                                     break;

                                 case 'signin':
                                     if (!empty($ParametersField[Text]))
                                     {
                                         // Text is a path to a picture !
                                         $sText = '';
                                         $sImage = $GLOBALS['CONF_PRINT_TEMPLATES_DIRECTORY_HDD'].$ParametersField[Text];
                                         $DimWidth = $ParametersField[DimWidth];
                                     }
                                     break;
                             }

                             if (!empty($sText))
                             {
                                 $pdf->Text($x + $PosX, $y + $PosY, $sText);
                             }
                             elseif (!empty($sImage))
                             {
                                 $pdf->Image($sImage, $x + $PosX, $y + $PosY, $DimWidth, 0);
                             }
                         }
                         break;
                 }
             }
         }
     }
 }


/**
 * Convert a HTML file to PDF thanks to the Dompdf library
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-24
 *
 * @param $SourceHtml             String                Path of the html file to convert
 * @param $DestPdf                String                Path of the result, a PDF file
 * @param $Orientation            String                Orientaiton of the paper
 *
 * @return Boolean                TRUE if the PDF file is created, FALSE otherwise
 */
 function html2pdfdompdf($SourceHtml, $DestPdf, $Orientation = 'portrait')
 {
     if ((!empty($SourceHtml)) && (!empty($DestPdf)) && (file_exists($SourceHtml)))
     {
         $TmpSourcePath = str_replace(array("\\"), array('/'), $SourceHtml);
         $TmpDestPath = str_replace(array("\\"), array('/'), $DestPdf);
         switch($GLOBALS['CONF_OS'])
         {
             case APPL_OS_WINDOWS:
             case APPL_OS_LINUX:
                 // For Windows and Linux
                 include_once($GLOBALS['CONF_PDF_BIN_PATH_FOR_OS'][PDF_LIB_DOMPDF][$GLOBALS['CONF_OS']]);

                 $DomPDF = new DOMPDF();
                 $DomPDF->load_html_file($SourceHtml);
                 $DomPDF->set_paper('A4', $Orientation);
                 $DomPDF->render();
                 $PDFContent = $DomPDF->output();
                 saveToFile($DestPdf, 'wb', array($PDFContent));
                 break;
         }

         if (file_exists($DestPdf))
         {
             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Print the payements synthesis of the selected month, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.4
 *     - 2012-10-01 : patch the bug of wrong number of activated children for the selected month and
 *                    wrong number of canteens if the canteen price changed after september
 *     - 2014-06-02 : replace an "integer" cast by a round() function and use the abs() function
 *     - 2015-01-16 : try to find the right price of canteen if not found in $CONF_CANTEEN_PRICES
 *     - 2017-11-07 : taken into account BillWithoutMealAmount and the right price
 *
 * @since 2012-03-26
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the payments synthesis
 */
 function printPaymentsynthesis($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array())
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
             // Display the header of the synthesis
             displayTitlePage($GLOBALS['LANG_SUPPORT_PAYMENTS_SYNTHESIS_PAGE_TITLE'], 2, "class=\"PaymentsSynthesis\"");

             openParagraph('PaymentsSynthesisHeader');
             displayStyledText($GLOBALS['LANG_SUPPORT_PAYMENTS_SYNTHESIS_PAGE_INTRODUCTION']." "
                               .$GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year.", "");
             closeParagraph();

             // Display the table containing concerned families with payments for this month
             // So we get bills of the selected month
             $StartDate = "$Year-$Month-01";
             $EndDate = date("Y-m-t", strtotime($StartDate));
             $SchoolYear = getSchoolYear($StartDate);
             $SchoolYearPrice = $SchoolYear;

             $ArrayBills = getBills($DbConnection, $StartDate, $EndDate, 'FamilyLastname', PLANNING_BETWEEN_DATES);
             if ((isset($ArrayBills['BillID'])) && (!empty($ArrayBills['BillID'])))
             {
                 // Captions of the table
                 $ArrayCaptions = array($GLOBALS['LANG_FAMILY'], $GLOBALS['LANG_NB_CHILDREN'], $GLOBALS['LANG_MONTHLY_CONTRIBUTION'],
                                        $GLOBALS['LANG_CANTEEN'], $GLOBALS['LANG_NURSERY'], $GLOBALS['LANG_BILL'],
                                        $GLOBALS['LANG_BILL_PREVIOUS_BALANCE'], $GLOBALS['LANG_FAMILY_BALANCE']);

                 // Data
                 $TabFamiliesData = array();
                 $bSchoolYearPriceChanged = FALSE;
                 foreach($ArrayBills['BillID'] as $b => $BillID)
                 {
                     // Family lastname
                     if (empty($DetailsPage))
                     {
                         $TabFamiliesData[0][] = $ArrayBills["FamilyLastname"][$b];
                     }
                     else
                     {
                         $TabFamiliesData[0][] = generateAowIDHyperlink($ArrayBills["FamilyLastname"][$b], $ArrayBills["FamilyID"][$b],
                                                                        $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                        "", "_blank");
                     }

                     // Get the number of children of the family
                     $iNbChildren = getNbdbSearchChild($DbConnection, array(
                                                                            'FamilyID' => $ArrayBills["FamilyID"][$b],
                                                                            'Activated' => TRUE,
                                                                            'SchoolYear' => array($SchoolYear)
                                                                           ));
                     $TabFamiliesData[1][] = $iNbChildren;

                     // Monthly contribution
                     $TabFamiliesData[2][] = $ArrayBills["BillMonthlyContribution"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                     // Nb canteen registrations and amount
                     // Get the number of canteen registrations
                     $iNbCanteenRegistrations = 0;
                     $iNbWithoutMeals = 0;
                     if (isset($GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice]))
                     {
                         $fPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][0] + $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                         $MinPrice = $fPrice;
                         $MaxPrice = $fPrice;

                         $fWithoutMealPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                         $MinWithoutMealPrice = $fWithoutMealPrice;
                         $MaxWithoutMealPrice = $fWithoutMealPrice;

                         $iNbCanteenRegistrations = $ArrayBills['BillCanteenAmount'][$b] / $fPrice;
                         $iNbWithoutMeals = $ArrayBills['BillWithoutMealAmount'][$b] / $fWithoutMealPrice;

                         // We do this verification only once time
                         // We use the round(x, 3) because with float, some results have the form x.yE-15
                         if ((!$bSchoolYearPriceChanged) && (round(abs($iNbCanteenRegistrations - round($iNbCanteenRegistrations)), 3) > 0))
                         {
                             // We must use the canteen price of the previous school year
                             $SchoolYearPrice--;
                             if (isset($GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice]))
                             {
                                 // We re-compute data for the bill
                                 $fPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][0] + $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                                 $MinPrice = $fPrice;
                                 $iNbCanteenRegistrations = $ArrayBills['BillCanteenAmount'][$b] / $fPrice;

                                 $fWithoutMealPrice = $GLOBALS['CONF_CANTEEN_PRICES'][$SchoolYearPrice][1];
                                 $iNbWithoutMeals = $ArrayBills['BillWithoutMealAmount'][$b] / $fWithoutMealPrice;
                             }

                             $bSchoolYearPriceChanged = TRUE;

                             if (round(abs($iNbCanteenRegistrations - round($iNbCanteenRegistrations)), 3) > 0)
                             {
                                 // Try to find the right price
                                 $fFoundPrice = findExactDivisor($ArrayBills['BillCanteenAmount'][$b], $MinPrice, $MaxPrice, 0.01);
                                 if ($fFoundPrice > 0)
                                 {
                                     // Right price found
                                     $bSchoolYearPriceChanged = FALSE;
                                     $SchoolYearPrice++;
                                     $fPrice = $fFoundPrice;

                                     $iNbCanteenRegistrations = $ArrayBills['BillCanteenAmount'][$b] / $fPrice;
                                 }

                                 $fWithoutMealFoundPrice = findExactDivisor($ArrayBills['BillWithoutMealAmount'][$b], $MinWithoutMealPrice,
                                                                            $MaxWithoutMealPrice, 0.01);
                                 if ($fWithoutMealFoundPrice > 0)
                                 {
                                     // Right price found
                                     $fWithoutMealPrice = $fWithoutMealFoundPrice;
                                     $iNbWithoutMeals = $ArrayBills['BillWithoutMealAmount'][$b] / $fWithoutMealPrice;
                                 }
                             }
                         }
                     }

                     $TabFamiliesData[3][] = generateStyledText($ArrayBills["BillCanteenAmount"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT']
                                                                ." ($iNbCanteenRegistrations)", "CanteenWithMeal")
                                             ." / ".$ArrayBills["BillWithoutMealAmount"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT']
                                             ." ($iNbWithoutMeals)";

                     // Nb nursery registrations and amount
                     $TabFamiliesData[4][] = $ArrayBills["BillNurseryAmount"][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                     // Total of the bill + "paid" flag
                     $Flag = '';
                     if ($ArrayBills["BillPaid"][$b] == 1)
                     {
                         // The bill is Paid
                         $Flag = " ".generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'], $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                     }

                     $TabFamiliesData[5][] = $ArrayBills['BillAmount'][$b].' '.$GLOBALS['CONF_PAYMENTS_UNIT'].$Flag;

                     // Balance of the family for this bill
                     $BalanceAmount = -1.00 * $ArrayBills['BillPreviousBalance'][$b];
                     $BalanceStyle = '';
                     if ($BalanceAmount < 0)
                     {
                         $BalanceStyle = 'NegativeBalance';
                     }
                     elseif ($BalanceAmount > 0)
                     {
                         $BalanceStyle = 'PositiveBalance';
                     }

                     $TabFamiliesData[6][] = generateStyledText(sprintf("%01.2f", $BalanceAmount)." ".$GLOBALS['CONF_PAYMENTS_UNIT'],
                                                                $BalanceStyle);

                     // Current balance of the family
                     $CurrentBalanceAmount = getTableFieldValue($DbConnection, 'Families', $ArrayBills["FamilyID"][$b],
                                                                'FamilyBalance');

                     $BalanceStyle = '';
                     if ($CurrentBalanceAmount < 0)
                     {
                         $BalanceStyle = 'NegativeBalance';
                     }
                     elseif ($CurrentBalanceAmount > 0)
                     {
                         $BalanceStyle = 'PositiveBalance';
                     }

                     // Get nb of not paid bills of the family
                     $ArrayNotPaidBills = getBills($DbConnection, NULL, NULL, 'BillID', NO_DATES,
                                                   array("FamilyID" => array($ArrayBills["FamilyID"][$b]),
                                                         "BillPaid" => array(0)));

                     $iNbNotPaidBills = 0;
                     $sNbNotPaidBillsText = '';
                     if ((isset($ArrayNotPaidBills['BillID'])) && (!empty($ArrayNotPaidBills['BillID'])))
                     {
                         $iNbNotPaidBills = count($ArrayNotPaidBills['BillID']);
                         $sNbNotPaidBillsText = " ($iNbNotPaidBills)";
                     }

                     $TabFamiliesData[7][] = generateStyledText("$CurrentBalanceAmount ".$GLOBALS['CONF_PAYMENTS_UNIT'], $BalanceStyle)
                                             .$sNbNotPaidBillsText;
                 }

                 displayStyledTable($ArrayCaptions, array_fill(0, count($ArrayCaptions), ''), '', $TabFamiliesData,
                                    'Data', 'Data', 'Data');
             }

             openParagraph('InfoMsg');
             displayStyledLinkText($GLOBALS['LANG_GO_BACK'], "$ProcessFormPage?lYearMonth=$Year-$Month", 'notprintable',
                                   $GLOBALS['LANG_GO_BACK']);
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