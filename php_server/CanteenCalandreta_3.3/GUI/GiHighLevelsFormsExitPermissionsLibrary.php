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
 * Interface module : XHTML Graphic high level forms library used to manage the exit permissions of chlidren.
 *
 * @author Christophe Javouhey
 * @version 2.8
 * @since 2015-07-10
 */


/**
 * Display the exit permissions for the selected day, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2015-09-21 : if the current date isn't in the list of proposed dates, we select the first date in the list
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *
 * @since 2015-07-10
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SelectedDate         Integer               Day to display (YYYY-MM-DD)
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to view or edit exit permissions
 * @param $ViewsRestrictions    Array of Integers     List used to select only some support members
 *                                                    allowed to view some exit permissions
 */
 function displayExitPermissionsListForm($DbConnection, $ProcessFormPage, $SelectedDate, $AccessRules = array(), $ViewsRestrictions = array())
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
             openForm("FormExitPermissionsList", "post", "$ProcessFormPage", "", "");

             // Display the days list to change the exit permissions of the day to display
             openParagraph('toolbar');
             echo generateStyledPictureHyperlink($GLOBALS["CONF_PRINT_BULLET"], "javascript:PrintWebPage()", $GLOBALS["LANG_PRINT"], "PictureLink", "");
             closeParagraph();

             // Display the day list : we get the older date of the canteen registrations
             openParagraph('toolbar');

             // We generate the list of the days from the older day to the current day
             $CurrentSchoolYear = getSchoolYear($SelectedDate);
             $MinDate = getExitPermissionMinDate($DbConnection);
             if (empty($MinDate))
             {
                 $MinDate = date("Y-m-d");
             }
             else
             {
                 // Keep the min date (Mindate or current date)
                 if (strtotime($MinDate) > strtotime(date("Y-m-d")))
                 {
                     $MinDate = date("Y-m-d");
                 }
             }

             $MaxDate = getExitPermissionMaxDate($DbConnection);
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

             // We take into account the nb of days for which users are allowed to record exit permissions
             $MaxDate = date('Y-m-d', strtotime("+".$GLOBALS['CONF_EXIT_PERMISSIONS_NB_DAYS_REGISTRATION'].' days', strtotime($MaxDate)));

             $ArrayDays = array_keys(getPeriodIntervalsStats($MinDate, $MaxDate, "d"));
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

             // We get holidays for the considered period and remove date in holidays
             $ArraySchoolHolidays = getHolidays($DbConnection, $MinDate, $MaxDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
             if ((!isset($ArraySchoolHolidays['HolidayID'])) || ((isset($ArraySchoolHolidays['HolidayID']))
                 && (empty($ArraySchoolHolidays['HolidayID']))))
             {
                 $ArraySchoolHolidays = getHolidays($DbConnection, $MinDate, $MaxDate, 'HolidayStartDate', DATES_BETWEEN_PLANNING);
             }

             if (isset($ArraySchoolHolidays['HolidayID']))
             {
                 foreach($ArraySchoolHolidays['HolidayID'] as $h => $HolidayID)
                 {
                     $StartStamp = strtotime($ArraySchoolHolidays['HolidayStartDate'][$h]);
                     $EndStamp = strtotime($ArraySchoolHolidays['HolidayEndDate'][$h]);
                     foreach($ArrayDaysLabels as $CurrentDate => $LabelDate)
                     {
                         $CurrentStamp = strtotime($CurrentDate);
                         if (($CurrentStamp >= $StartStamp) && ($CurrentStamp <= $EndStamp))
                         {
                             // Holidays : we remove this date
                             unset($ArrayDaysLabels[$CurrentDate]);
                         }
                     }
                 }
             }

             unset($ArraySchoolHolidays);

             // We check if the selected date is in the $ArrayDaysLabels list
             $ArrayDates = array_keys($ArrayDaysLabels);
             $bPresent = FALSE;
             foreach($ArrayDates as $d => $CurrListDate)
             {
                 if ($SelectedDate == $CurrListDate)
                 {
                     $bPresent = TRUE;
                     break;
                 }
                 elseif ((strtotime($CurrListDate) > strtotime($SelectedDate)) && (!$bPresent))
                 {
                     // Selected date not found in previous dates of the list so we select the next date in the list
                     $SelectedDate = $CurrListDate;
                     break;
                 }
             }

             echo generateSelectField("lDay", $ArrayDates, array_values($ArrayDaysLabels), $SelectedDate,
                                      "onChangeExitPermissionsDay(this.value)");

             unset($ArrayDates);
             closeParagraph();

             // Display first exit permissions
             displayBR(2);

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

             $SelectedStamp = strtotime($SelectedDate);
             $CurrentStamp = strtotime(date('Y-m-d'));

             if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
             {
                 // Because cell for the "delete" button
                 $TabExitPermissionsCaptions[] = "&nbsp;";
             }

             if ((isset($ArrayExitPermissions['ExitPermissionID'])) && (count($ArrayExitPermissions['ExitPermissionID']) > 0))
             {
                 foreach($ArrayExitPermissions['ExitPermissionID'] as $ep => $ExitPermissionID)
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                         case FCT_ACT_CREATE:
                         case FCT_ACT_UPDATE:
                             $TabExitPermissionsData[0][] = generateCryptedHyperlink($ArrayExitPermissions['FamilyLastname'][$ep].' '
                                                                                     .$ArrayExitPermissions['ChildFirstname'][$ep],
                                                                                     $ArrayExitPermissions['ChildID'][$ep],
                                                                                     'UpdateChild.php',
                                                                                     $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                     '_blank');

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
                             break;
                     }

                     switch($cUserAccess)
                     {
                         case FCT_ACT_CREATE:
                         case FCT_ACT_UPDATE:
                             // We can delete this exit permission if not in the past
                             if ($CurrentStamp <= strtotime($ArrayExitPermissions['ExitPermissionDate'][$ep]))
                             {
                                 // We can delete the exit permission
                                 $TabExitPermissionsData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                               "DeleteExitPermission.php?Cr=".md5($ExitPermissionID)."&amp;Id=$ExitPermissionID",
                                                                                               $GLOBALS["LANG_DELETE"], 'Affectation');
                             }
                             else
                             {
                                 // We can't delete the exit permissions
                                 $TabExitPermissionsData[4][] = "&nbsp;";
                             }
                             break;
                     }
                 }
             }

             // Now, we display a line to add a new exit permission if the user is allowed...
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                     //... only if the selected date is today or after today and if the selected date can have
                     // exit permissions
                     if (($SelectedStamp >= $CurrentStamp) && (in_array($SelectedDate, array_keys($ArrayDaysLabels))))
                     {
                         // Display the list of activated children
                         $ChildrenList = "";
                         if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
                         {
                             $ArrayData = array(0 => '-');

                             // We group children by classroom
                             foreach($ArrayChildren['ChildID'] as $c => $CurrentChildID)
                             {
                                 $ArrayData[$GLOBALS['CONF_CLASSROOMS'][$CurrentSchoolYear][$ArrayChildren['ChildClass'][$c]]][$CurrentChildID] = $ArrayChildren['FamilyLastname'][$c].' '.$ArrayChildren['ChildFirstname'][$c];
                             }

                             $ItemSelected = 0;

                             $ChildrenList = generateOptGroupSelectField("lChildID", $ArrayData, $ItemSelected, '');

                             unset($ArrayData);
                         }
                         else
                         {
                             // No child
                             $ChildrenList = generateSelectField("lChildID", array(0), array("-"), 0);
                         }

                         $TabExitPermissionsData[0][] = $ChildrenList;

                         // Display the name of the authorized person
                         $TabExitPermissionsData[1][] = generateInputField("sLastname", "text", "100", "40",
                                                                           $GLOBALS["LANG_EXIT_PERMISSION_NAME_TIP"], '');

                         // Display the checkbox for the authorized person
                         $TabExitPermissionsData[2][] = generateInputField("chkAuthorizedPerson", "checkbox", "", "",
                                                                           $GLOBALS["LANG_EXIT_PERMISSION_AUTHORIZED_PERSON_TIP"], 1,
                                                                           FALSE, FALSE)." ".$GLOBALS["LANG_YES"];

                         // Signature of parent : empty up to now
                         $TabExitPermissionsData[3][] = "&nbsp;";

                         // For the delete button
                         $TabExitPermissionsData[4][] = "&nbsp;";
                     }
                     break;
             }

             if (empty($TabExitPermissionsData))
             {
                 // Fill the array with an empty row
                 foreach($TabExitPermissionsCaptions as $c => $Caption)
                 {
                     $TabExitPermissionsData[$c][] = "&nbsp;";
                 }
             }

             displayStyledTable($TabExitPermissionsCaptions, array_fill(0, count($TabExitPermissionsCaptions), ''), '',
                                $TabExitPermissionsData, 'PurposeParticipantsTable', '', '');

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                     if (($SelectedStamp >= $CurrentStamp) && (in_array($SelectedDate, array_keys($ArrayDaysLabels))))
                     {
                         // Display buttons only for users with creation rights
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                     }
                     break;
             }

             insertInputField("hidDay", "hidden", "", "", "", "$SelectedDate");  // Current selected day
             closeForm();

             // Open a form to print the day synthesis
             openForm("FormPrintAction", "post", "$ProcessFormPage?lDay=$SelectedDate", "", "");
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