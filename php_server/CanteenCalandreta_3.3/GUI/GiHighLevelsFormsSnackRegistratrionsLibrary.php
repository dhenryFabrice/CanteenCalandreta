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
 * of the snack.
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2015-06-16
 */


/**
 * Display the planning of snacks brought by families for a given school year, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-08-30 : allow to regerate the planning and display num week and a flag if no-normal date
 *
 * @since 2015-06-16
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SchoolYear           Integer               Concerned school year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view the planning of snacks
 */
 function displaySnackPlanningForm($DbConnection, $ProcessFormPage, $SchoolYear, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the planning
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
             openForm("FormViewPlanning", "post", "$ProcessFormPage", "", "");

             // Display the list of school years to change the planning to display
             openParagraph('toolbar');
             echo generateStyledPictureHyperlink($GLOBALS["CONF_PRINT_BULLET"], "javascript:PrintWebPage()", $GLOBALS["LANG_PRINT"], "PictureLink", "");
             closeParagraph();

             // Display the school years list : we use the registered start school year date in config.php
             openParagraph('toolbar');
             foreach($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'] as $Year => $Date)
             {
                 $Value = date('Y', strtotime($Date)).'-'.$Year;
                 $ArraySchoolYear[$Year] = $Value;
             }

             echo generateSelectField("lYear", array_keys($ArraySchoolYear), array_values($ArraySchoolYear), "$SchoolYear",
                                      "onChangeSnackPlanningYear(this.value)");
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
                 // We get the different classrooms
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
                             // We check if the number of families is right in relation with the number of dates in the planning
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

                 // We check if the number of families is right in relation with the number of dates in the planning
                 // (for the last column !)
                 $iNbDiff = $iNbPlanningDates - count($TabSnackPlanningData[$i]);
                 if ($iNbDiff > 0)
                 {
                     // Not enough families : we fill with empty values !
                     $TabSnackPlanningData[$iNbColumns - 1] = array_pad($TabSnackPlanningData[$iNbColumns - 1], $iNbPlanningDates, "-");
                 }

                 // We display the planning
                 displayStyledTable($ArrayClass, array_fill(0, count($ArrayClass), ''), '', $TabSnackPlanningData,
                                    'SnackPlanningTable', '', '');

                 // If allowed and current day before first day of school year, display the button to regenerate
                 // the planning of snacks
                 if ((in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE))) && ($CurrentDateStamp >= strtotime($StartDate))
                     && ($CurrentDateStamp <= strtotime($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'][$SchoolYear])))
                 {
                     openParagraph('toolbar');
                     insertInputField("bRegeneratePlanning", "submit", "", "", $GLOBALS["LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_REGENERATE_BUTTON_TIP"],
                                      $GLOBALS["LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_REGENERATE_BUTTON"]);
                     closeParagraph();
                 }
             }
             else
             {
                 // No snack registration found
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();

                 // If allowed, display the button to create the planning of snacks
                 if ($cUserAccess == FCT_ACT_CREATE)
                 {
                     openParagraph('toolbar');
                     insertInputField("bCreatePlanning", "submit", "", "", $GLOBALS["LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_GENERATE_BUTTON_TIP"],
                                      $GLOBALS["LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_GENERATE_BUTTON"]);
                     closeParagraph();
                 }
             }

             insertInputField("hidYear", "hidden", "", "", "", "$SchoolYear");  // Current selected school year
             closeForm();

             // Open a form to print the snack planning
             openForm("FormPrintAction", "post", "$ProcessFormPage?lYear=$SchoolYear", "", "");
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
 * Display the form to swap snack's week between 2 families for a given school year, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SchoolYear           Integer               Concerned school year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to swap snack's dates
 */
 function displaySwapSnackPlanningForm($DbConnection, $ProcessFormPage, $SchoolYear, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view the planning
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
             openForm("FormSwapSnackPlanning", "post", "$ProcessFormPage", "",
                      "VerificationSwapSnackPlanning('".$GLOBALS["LANG_ERROR_JS_MANDORY_FIELDS"]."')");

             // We get snack registrations for families for the given school year
             $CurrentDate = date('Y-m-d');
             $StartDate = getSchoolYearStartDate($SchoolYear);
             $EndDate = getSchoolYearEndDate($SchoolYear);

             // We keep only dates of snack after the current date
             $MinDate = date('Y-m-d', max(strtotime($CurrentDate), strtotime($StartDate)));
             $ArraySnackRegistrations = getSnackRegistrations($DbConnection, $MinDate, $EndDate,
                                                              'SnackRegistrationClass, SnackRegistrationDate, SnackRegistrationID',
                                                              NULL, PLANNING_BETWEEN_DATES);

             if ((isset($ArraySnackRegistrations['SnackRegistrationID'])) && (count($ArraySnackRegistrations['SnackRegistrationID']) > 0))
             {
                 openStyledFrame($GLOBALS['LANG_SUPPORT_ADMIN_SWAP_SNACK_PLANNING_PAGE_TITLE'], "Frame", "Frame", "DetailsNews");

                 // Group the snack registrations by classroom
                 if (isset($GLOBALS['CONF_CLASSROOMS'][$SchoolYear]))
                 {
                     $ArrayClassrooms = $GLOBALS['CONF_CLASSROOMS'][$SchoolYear];
                 }
                 else
                 {
                     // We use the last school year set
                     $iLast = count($GLOBALS['CONF_CLASSROOMS']) - 1;
                     $ArrayTmp = array_keys($GLOBALS['CONF_CLASSROOMS']);
                     $ArrayClassrooms = $GLOBALS['CONF_CLASSROOMS'][$ArrayTmp[$iLast]];

                     unset($ArrayTmp, $iLast);
                 }

                 // Create groups (classrooms)
                 $ArrayGroupedSnackRegistrations = array(0 => '');
                 foreach($ArrayClassrooms as $c => $CurrentClass)
                 {
                     if ($c > 0)
                     {
                         $ArrayGroupedSnackRegistrations[$CurrentClass] = array();
                     }
                 }

                 // Add registrations in groups
                 foreach($ArraySnackRegistrations['SnackRegistrationID'] as $sr => $CurrentRegistrationID)
                 {
                     // Display date and num of the week
                     $CurrentStamp = strtotime($ArraySnackRegistrations['SnackRegistrationDate'][$sr]);
                     $DisplayedDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], $CurrentStamp).' ('
                                      .ucfirst(substr($GLOBALS['LANG_WEEK'], 0, 1)).date('W', $CurrentStamp).')';

                     $iPos = $ArrayClassrooms[$ArraySnackRegistrations['SnackRegistrationClass'][$sr]];
                     $ArrayGroupedSnackRegistrations[$iPos][$CurrentRegistrationID] = "$DisplayedDate - "
                                                                                      .$ArraySnackRegistrations['FamilyLastname'][$sr];
                 }

                 $FirstFamily = generateOptGroupSelectField("lFirstSnackRegistrationID", $ArrayGroupedSnackRegistrations, 0, '');
                 $SecondFamily = generateOptGroupSelectField("lSecondSnackRegistrationID", $ArrayGroupedSnackRegistrations, 0, '');

                 // Display the form
                 echo "<table id=\"SwapPlanning\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_FAMILY']." 1*</td><td class=\"Value\">$FirstFamily</td>\n</tr>\n";
                 echo "<td class=\"Label\">".$GLOBALS["LANG_FAMILY"]." 2*</td><td class=\"Value\">$SecondFamily</td>\n</tr>\n";
                 echo "</table>\n";

                 closeStyledFrame();

                 // We display the buttons
                 echo "<table class=\"validation\">\n<tr>\n\t<td>";
                 insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                 echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                 insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                 echo "</td>\n</tr>\n</table>\n";
             }
             else
             {
                 // No snack registration found
                 openParagraph('InfoMsg');
                 displayBR(2);
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();
             }

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