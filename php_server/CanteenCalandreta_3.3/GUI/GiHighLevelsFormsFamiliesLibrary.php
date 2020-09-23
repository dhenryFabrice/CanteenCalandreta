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
 * Interface module : XHTML Graphic high level forms library used to manage the families and children.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2012-01-12
 */


/**
 * Display the form to submit a new family or update a family, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 3.2
 *     - 2012-07-10 : change the way to display desactivated children, patch the pb when then
 *                    desactivation date is empty and taken into account the
 *                    FamilySpecialAnnualContribution field, patch the bug about school year
 *     - 2013-01-29 : display for concerned payments of bills the not used amount (in tool-tip) and
 *                    taken into account the new field "PaymentReceiptDate"
 *     - 2013-06-21 : taken into account the new structure of the CONF_CLASSROOMS variable
 *                    (includes school year)
 *     - 2013-09-18 : taken into account the FCT_ACT_PARTIAL_READ_ONLY access right and the mode of
 *                    monthly contribution
 *     - 2013-11-22 : display the % of the bill paid
 *     - 2013-12-16 : taken into account the fields "BillPaidAmount" and "PaymentUsedAmount" to display
 *                    the % of the bill paid and the not used amount of payements (optimizations)
 *     - 2014-02-25 : display an achor to go directly to content and taken into account $CONF_NB_FAMILY_BILLS
 *                    to limit the number of bills to display (except there are more not paid bills)
 *     - 2014-05-27 : add a button to view payments history of the family
 *     - 2015-01-16 : check the next bill paid exists ($ArrayBills["BillPaid"][$i + 1]) and display the number
 *                    of canteen registrations after the current date for each child
 *     - 2015-10-21 : display the number of canteen registrations with and without pork
 *     - 2016-05-10 : taken into account of the new values in $CONF_MONTHLY_CONTRIBUTION_MODES (about coefficients
 *                    of families), add a button to add new towns and remove htmlspecialchars() function and
 *                    add a button to delete a payment
 *     - 2017-09-21 : taken into account FCT_ACT_UPDATE_OLD_USER access for old families. They can update
 *                    only some data even the desactivation date is set. Patch a bug about wrong school year
 *                    about generateFamilyVisualIndicators() for desactivated families, display a link
 *                    to reactivate a desactivated family, display discounts, taken into account $CONF_MEAL_TYPES
 *                    to display nb canteen registrations of each child after the current date, display
 *                    payments not linked to bills
 *     - 2019-01-21 : taken into account FamilyMainEmailInCommittee and FamilySecondEmailInCommittee fields
 *     - 2019-05-10 : display documents approvals for the family and display users profils linked to the family,
 *                    taken into account the FamilyAnnualContributionBalance field
 *
 * @since 2012-01-16
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $FamilyID                 String                ID of the family to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update families
 */
 function displayDetailsFamilyForm($DbConnection, $FamilyID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a family
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($FamilyID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
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
                 // Partial read mode
                 $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
             }
             elseif ((isset($AccessRules[FCT_ACT_UPDATE_OLD_USER])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE_OLD_USER])))
             {
                 // Update old user mode (for old families)
                 $cUserAccess = FCT_ACT_UPDATE_OLD_USER;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY,
                                          FCT_ACT_UPDATE_OLD_USER)))
         {
             // Open a form
             openForm("FormDetailsFamily", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "VerificationFamily('".$GLOBALS["LANG_ERROR_JS_FAMILY_LASTNAME"]."', '".$GLOBALS["LANG_ERROR_JS_FAMILY_MAIN_EMAIL"]."', '".$GLOBALS["LANG_ERROR_JS_FAMILY_SECOND_EMAIL"]."', '".$GLOBALS["LANG_ERROR_JS_FAMILY_WRONG_NB_MEMBERS"]."', '".$GLOBALS["LANG_ERROR_JS_TOWN"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_FAMILY"], "Frame", "Frame", "DetailsNews");

             // <<< Family ID >>>
             if ($FamilyID == 0)
             {
                 $Reference = "&nbsp;";
                 $FamilyRecord = array(
                                       "FamilyDate" => date('Y-m-d'),
                                       "FamilyLastname" => '',
                                       "FamilyMainEmail" => '',
                                       "FamilyMainEmailContactAllowed" => 0,
                                       "FamilyMainEmailInCommittee" => 0,
                                       "FamilySecondEmail" => '',
                                       "FamilySecondEmailContactAllowed" => 0,
                                       "FamilySecondEmailInCommittee" => 0,
                                       "FamilyDesactivationDate" => NULL,
                                       "TownID" => 0,
                                       "FamilyNbMembers" => '1',
                                       "FamilyNbPoweredMembers" => '0',
                                       "FamilySpecialAnnualContribution" => 0,
                                       "FamilyMonthlyContributionMode" => 0,
                                       "FamilyAnnualContributionBalance" => '0.00',
                                       "FamilyBalance" => '0.00',
                                       "FamilyComment" => ""
                                      );

                 $bClosed = FALSE;
             }
             else
             {
                 if (isExistingFamily($DbConnection, $FamilyID))
                 {
                     // We get the details of the family
                     $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $FamilyID);
                     $Reference = "$FamilyID";

                     // We check if the family is opened or close
                     $bClosed = isFamilyClosed($DbConnection, $FamilyID);
                 }
                 else
                 {
                     // Error, the family doesn't exist
                     $FamilyID = 0;
                     $Reference = "&nbsp;";
                     $bClosed = FALSE;
                 }
             }

             $CreationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($FamilyRecord["FamilyDate"]));
             $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));

             if ($FamilyID > 0)
             {
                 // Create the Towns list
                 if ($bClosed)
                 {
                     // We get infos about the selected town
                     switch($cUserAccess)
                     {
                         case FCT_ACT_UPDATE_OLD_USER:
                             // The old family can update the town
                             $DbResultList = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
                             $Town = '&nbsp;';
                             if (!DB::isError($DbResultList))
                             {
                                 $ArrayTownID = array();
                                 $ArrayTownInfos = array();
                                 while($RecordList = $DbResultList->fetchRow(DB_FETCHMODE_ASSOC))
                                 {
                                     $ArrayTownID[] = $RecordList["TownID"];
                                     $ArrayTownInfos[] = $RecordList["TownName"].' ('.$RecordList["TownCode"].')';
                                 }

                                 $Town = generateSelectField("lTownID", $ArrayTownID, $ArrayTownInfos, $FamilyRecord['TownID']);
                             }
                             break;

                         default:
                             $ArrayInfosTown = getTableRecordInfos($DbConnection, 'Towns', $FamilyRecord['TownID']);
                             $Town = $ArrayInfosTown['TownName'].' ('.$ArrayInfosTown['TownCode'].')';
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                             // We get infos about the selected town
                             $ArrayInfosTown = getTableRecordInfos($DbConnection, 'Towns', $FamilyRecord['TownID']);
                             $Town = $ArrayInfosTown['TownName'].' ('.$ArrayInfosTown['TownCode'].')';
                             break;

                         case FCT_ACT_PARTIAL_READ_ONLY:
                             $DbResultList = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
                             $Town = '&nbsp;';
                             if (!DB::isError($DbResultList))
                             {
                                 $ArrayTownID = array();
                                 $ArrayTownInfos = array();
                                 while($RecordList = $DbResultList->fetchRow(DB_FETCHMODE_ASSOC))
                                 {
                                     $ArrayTownID[] = $RecordList["TownID"];
                                     $ArrayTownInfos[] = $RecordList["TownName"].' ('.$RecordList["TownCode"].')';
                                 }

                                 $Town = generateSelectField("lTownID", $ArrayTownID, $ArrayTownInfos, $FamilyRecord['TownID']);
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             $DbResultList = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
                             $Town = '&nbsp;';
                             if (!DB::isError($DbResultList))
                             {
                                 $ArrayTownID = array();
                                 $ArrayTownInfos = array();
                                 while($RecordList = $DbResultList->fetchRow(DB_FETCHMODE_ASSOC))
                                 {
                                     $ArrayTownID[] = $RecordList["TownID"];
                                     $ArrayTownInfos[] = $RecordList["TownName"].' ('.$RecordList["TownCode"].')';
                                 }

                                 $Town = generateSelectField("lTownID", $ArrayTownID, $ArrayTownInfos, $FamilyRecord['TownID']);

                                 // Display a button to add a new town
                                 $Town .= generateStyledPictureHyperlink($GLOBALS["CONF_ADD_ICON"], "../Canteen/AddTown.php?Cr=".md5('')."&amp;Id=",
                                                                         $GLOBALS["LANG_ADD_TOWN_TIP"], 'Affectation', '_blank');
                             }
                             break;
                     }
                 }

                 // Desactivation date
                 if ($bClosed)
                 {
                     $DesactivationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                               strtotime($FamilyRecord["FamilyDesactivationDate"]));

                     switch($cUserAccess)
                     {
                         case FCT_ACT_UPDATE:
                             // Display link to reactivate the family
                             $DesactivationDate .= ' '.generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                      "ReactivateFamily.php?Cr=".md5($FamilyID)."&amp;Id=$FamilyID",
                                                                                      $GLOBALS["LANG_ACTIVATION"], 'Affectation');
                             break;
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             if (empty($FamilyRecord["FamilyDesactivationDate"]))
                             {
                                 $DesactivationDate = '';
                             }
                             else
                             {
                                 $DesactivationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                           strtotime($FamilyRecord["FamilyDesactivationDate"]));
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             if (empty($FamilyRecord["FamilyDesactivationDate"]))
                             {
                                 $DesactivationDateValue = '';
                             }
                             else
                             {
                                 $DesactivationDateValue = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                                strtotime($FamilyRecord["FamilyDesactivationDate"]));
                             }

                             $DesactivationDate = generateInputField("desactivationDate", "text", "10", "10",
                                                                     $GLOBALS["LANG_FAMILY_DESACTIVATION_DATE_TIP"],
                                                                     $DesactivationDateValue, TRUE);

                             // Insert the javascript to use the calendar component
                             $DesactivationDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t DesactivationDateCalendar = new dynCalendar('DesactivationDateCalendar', 'calendarCallbackDesactivationDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                             break;
                     }
                 }

                 // We get the children of the family
                 $ArrayChildren = getFamilyChildren($DbConnection, $FamilyID, 'ChildSchoolDate');

                 // We get the max date of canteen registrations
                 $CanteenRegistrationMaxDate = getCanteenRegistrationMaxDate($DbConnection);

                 // We define the captions of the children table
                 $CurrentDateTime = strtotime(date('Y-m-d'));
                 $Children = '&nbsp;';
                 $TabChildrenCaptions = array($GLOBALS["LANG_CHILD_FIRSTNAME"], $GLOBALS["LANG_CHILD_GRADE"].' / '
                                              .$GLOBALS["LANG_CHILD_CLASS"], $GLOBALS["LANG_MEAL_WITHOUT_PORK"],
                                              $GLOBALS['LANG_FAMILY_NB_CANTEENS_AFTER']
                                              ." ".date($GLOBALS['CONF_DATE_DISPLAY_FORMAT']));

                 if ($bClosed)
                 {
                     // We transform the result to be displayed
                     if ((isset($ArrayChildren["ChildID"])) && (count($ArrayChildren["ChildID"]) > 0))
                     {
                         foreach($ArrayChildren["ChildID"] as $i => $CurrentID)
                         {
                             // Get nb canteen registrations after the current date
                             $ArrayCanteenRegistrations = getCanteenRegistrations($DbConnection, date('Y-m-d', strtotime("+1 day")),
                                                                                  $CanteenRegistrationMaxDate,
                                                                                  'CanteenRegistrationForDate', $CurrentID);

                             $iNbCanteenRegistrationsAfterCurrentDate = 0;
                             if (isset($ArrayCanteenRegistrations['CanteenRegistrationID']))
                             {
                                 $ArrayNbCanteenRegistrationsByPrefs = array();
                                 foreach($GLOBALS['CONF_MEAL_TYPES'] as $mt => $CurrentMealType)
                                 {
                                     $ArrayNbCanteenRegistrationsByPrefs[$mt] = 0;
                                 }

                                 foreach($ArrayCanteenRegistrations['CanteenRegistrationID'] as $crbp => $CurrentCanteenRegistrationID)
                                 {
                                     $ArrayNbCanteenRegistrationsByPrefs[$ArrayCanteenRegistrations['CanteenRegistrationWithoutPork'][$crbp]]++;
                                 }

                                 $iNbCanteenRegistrationsAfterCurrentDate = "";
                                 foreach($GLOBALS['CONF_MEAL_TYPES'] as $mt => $CurrentMealType)
                                 {
                                     if (!empty($iNbCanteenRegistrationsAfterCurrentDate))
                                     {
                                         $iNbCanteenRegistrationsAfterCurrentDate .= generateBR(1);
                                     }

                                     $iNbCanteenRegistrationsAfterCurrentDate .= $CurrentMealType." : ".$ArrayNbCanteenRegistrationsByPrefs[$mt];
                                 }
                             }

                             $bChildDesactivated = FALSE;
                             if ((!empty($ArrayChildren["ChildDesactivationDate"][$i]))
                                 && (strtotime($ArrayChildren["ChildDesactivationDate"][$i]) <= $CurrentDateTime))
                             {
                                 $bChildDesactivated = TRUE;
                             }

                             if ($bChildDesactivated)
                             {
                                 // Child desactivated
                                 $ChildSchoolYear = getSchoolYear($ArrayChildren["ChildDesactivationDate"][$i]);
                                 $TabChildrenData[0][] = generateCryptedHyperlink($ArrayChildren["ChildFirstname"][$i], $CurrentID,
                                                                                  'UpdateChild.php',
                                                                                  $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                  'Desactivated', '_blank');

                                 $TabChildrenData[1][] = generateStyledText($GLOBALS["CONF_GRADES"][$ArrayChildren["ChildGrade"][$i]].' / '
                                                                            .$GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ArrayChildren["ChildClass"][$i]],
                                                                            "Desactivated");

                                 if ($ArrayChildren["ChildWithoutPork"][$i] == 0)
                                 {
                                     $TabChildrenData[2][] = generateStyledText($GLOBALS["LANG_NO"], "Desactivated");
                                 }
                                 else
                                 {
                                     $TabChildrenData[2][] = generateStyledText($GLOBALS["LANG_YES"], "Desactivated");
                                 }

                                 $TabChildrenData[3][] = generateStyledText($iNbCanteenRegistrationsAfterCurrentDate, "Desactivated");
                             }
                             else
                             {
                                 // Child activated but family desactivated
                                 $ChildSchoolYear = getSchoolYear($FamilyRecord['FamilyDesactivationDate']);
                                 $TabChildrenData[0][] = generateCryptedHyperlink($ArrayChildren["ChildFirstname"][$i], $CurrentID,
                                                                                 'UpdateChild.php', $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                  '', '_blank');

                                 $TabChildrenData[1][] = $GLOBALS["CONF_GRADES"][$ArrayChildren["ChildGrade"][$i]].' / '
                                                         .$GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ArrayChildren["ChildClass"][$i]];

                                 if ($ArrayChildren["ChildWithoutPork"][$i] == 0)
                                 {
                                     $TabChildrenData[2][] = $GLOBALS["LANG_NO"];
                                 }
                                 else
                                 {
                                     $TabChildrenData[2][] = $GLOBALS["LANG_YES"];
                                 }

                                 $TabChildrenData[3][] = $iNbCanteenRegistrationsAfterCurrentDate;
                             }
                         }
                     }

                     $Children = '&nbsp;';
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArrayChildren["ChildID"])) && (count($ArrayChildren["ChildID"]) > 0))
                             {
                                 foreach($ArrayChildren["ChildID"] as $i => $CurrentID)
                                 {
                                     // Get nb canteen registrations after the current date
                                     $ArrayCanteenRegistrations = getCanteenRegistrations($DbConnection, date('Y-m-d', strtotime("+1 day")),
                                                                                          $CanteenRegistrationMaxDate,
                                                                                          'CanteenRegistrationForDate', $CurrentID);

                                     $iNbCanteenRegistrationsAfterCurrentDate = 0;
                                     if (isset($ArrayCanteenRegistrations['CanteenRegistrationID']))
                                     {
                                         $ArrayNbCanteenRegistrationsByPrefs = array();
                                         foreach($GLOBALS['CONF_MEAL_TYPES'] as $mt => $CurrentMealType)
                                         {
                                             $ArrayNbCanteenRegistrationsByPrefs[$mt] = 0;
                                         }

                                         foreach($ArrayCanteenRegistrations['CanteenRegistrationID'] as $crbp => $CurrentCanteenRegistrationID)
                                         {
                                             $ArrayNbCanteenRegistrationsByPrefs[$ArrayCanteenRegistrations['CanteenRegistrationWithoutPork'][$crbp]]++;
                                         }

                                         $iNbCanteenRegistrationsAfterCurrentDate = "";
                                         foreach($GLOBALS['CONF_MEAL_TYPES'] as $mt => $CurrentMealType)
                                         {
                                             if (!empty($iNbCanteenRegistrationsAfterCurrentDate))
                                             {
                                                 $iNbCanteenRegistrationsAfterCurrentDate .= generateBR(1);
                                             }

                                             $iNbCanteenRegistrationsAfterCurrentDate .= $CurrentMealType." : ".$ArrayNbCanteenRegistrationsByPrefs[$mt];
                                         }
                                     }

                                     $bChildDesactivated = FALSE;
                                     if ((!empty($ArrayChildren["ChildDesactivationDate"][$i]))
                                         && (strtotime($ArrayChildren["ChildDesactivationDate"][$i]) <= $CurrentDateTime))
                                     {
                                         $bChildDesactivated = TRUE;
                                     }

                                     if ($bChildDesactivated)
                                     {
                                         // Child desactivated
                                         $ChildSchoolYear = getSchoolYear($ArrayChildren["ChildDesactivationDate"][$i]);
                                         $TabChildrenData[0][] = generateCryptedHyperlink($ArrayChildren["ChildFirstname"][$i], $CurrentID,
                                                                                          'UpdateChild.php',
                                                                                          $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                          'Desactivated', '_blank');

                                         $TabChildrenData[1][] = generateStyledText($GLOBALS["CONF_GRADES"][$ArrayChildren["ChildGrade"][$i]].' / '
                                                                                    .$GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ArrayChildren["ChildClass"][$i]],
                                                                                    "Desactivated");

                                         if ($ArrayChildren["ChildWithoutPork"][$i] == 0)
                                         {
                                             $TabChildrenData[2][] = generateStyledText($GLOBALS["LANG_NO"], "Desactivated");
                                         }
                                         else
                                         {
                                             $TabChildrenData[2][] = generateStyledText($GLOBALS["LANG_YES"], "Desactivated");
                                         }

                                         $TabChildrenData[3][] = generateStyledText($iNbCanteenRegistrationsAfterCurrentDate, "Desactivated");
                                     }
                                     else
                                     {
                                         // Child activated
                                         $ChildSchoolYear = getSchoolYear(date('Y-m-d'));
                                         $TabChildrenData[0][] = generateCryptedHyperlink($ArrayChildren["ChildFirstname"][$i], $CurrentID,
                                                                                          'UpdateChild.php',
                                                                                          $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                          '_blank');

                                         $TabChildrenData[1][] = $GLOBALS["CONF_GRADES"][$ArrayChildren["ChildGrade"][$i]].' / '
                                                                 .$GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ArrayChildren["ChildClass"][$i]];

                                         if ($ArrayChildren["ChildWithoutPork"][$i] == 0)
                                         {
                                             $TabChildrenData[2][] = $GLOBALS["LANG_NO"];
                                         }
                                         else
                                         {
                                             $TabChildrenData[2][] = $GLOBALS["LANG_YES"];
                                         }

                                         $TabChildrenData[3][] = $iNbCanteenRegistrationsAfterCurrentDate;
                                     }
                                 }
                             }

                             $Children = '&nbsp;';
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             $TabChildrenCaptions[] = '&nbsp;';

                             if ((isset($ArrayChildren["ChildID"])) && (count($ArrayChildren["ChildID"]) > 0))
                             {
                                 foreach($ArrayChildren["ChildID"] as $i => $CurrentID)
                                 {
                                     // Get nb canteen registrations after the current date
                                     $ArrayCanteenRegistrations = getCanteenRegistrations($DbConnection, date('Y-m-d', strtotime("+1 day")),
                                                                                          $CanteenRegistrationMaxDate,
                                                                                          'CanteenRegistrationForDate', $CurrentID);

                                     $iNbCanteenRegistrationsAfterCurrentDate = 0;
                                     if (isset($ArrayCanteenRegistrations['CanteenRegistrationID']))
                                     {
                                         $ArrayNbCanteenRegistrationsByPrefs = array();
                                         foreach($GLOBALS['CONF_MEAL_TYPES'] as $mt => $CurrentMealType)
                                         {
                                             $ArrayNbCanteenRegistrationsByPrefs[$mt] = 0;
                                         }

                                         foreach($ArrayCanteenRegistrations['CanteenRegistrationID'] as $crbp => $CurrentCanteenRegistrationID)
                                         {
                                             $ArrayNbCanteenRegistrationsByPrefs[$ArrayCanteenRegistrations['CanteenRegistrationWithoutPork'][$crbp]]++;
                                         }

                                         $iNbCanteenRegistrationsAfterCurrentDate = "";
                                         foreach($GLOBALS['CONF_MEAL_TYPES'] as $mt => $CurrentMealType)
                                         {
                                             if (!empty($iNbCanteenRegistrationsAfterCurrentDate))
                                             {
                                                 $iNbCanteenRegistrationsAfterCurrentDate .= generateBR(1);
                                             }

                                             $iNbCanteenRegistrationsAfterCurrentDate .= $CurrentMealType." : ".$ArrayNbCanteenRegistrationsByPrefs[$mt];
                                         }
                                     }

                                     // Check if the child is desactivated
                                     $bChildDesactivated = FALSE;
                                     if ((!empty($ArrayChildren["ChildDesactivationDate"][$i]))
                                         && (strtotime($ArrayChildren["ChildDesactivationDate"][$i]) <= $CurrentDateTime))
                                     {
                                         $bChildDesactivated = TRUE;
                                     }

                                     if ($bChildDesactivated)
                                     {
                                         // Child desactivated
                                         $ChildSchoolYear = getSchoolYear($ArrayChildren["ChildDesactivationDate"][$i]);
                                         $TabChildrenData[0][] = generateCryptedHyperlink($ArrayChildren["ChildFirstname"][$i], $CurrentID,
                                                                                          'UpdateChild.php',
                                                                                          $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                          'Desactivated', '_blank');

                                         $TabChildrenData[1][] = generateStyledText($GLOBALS["CONF_GRADES"][$ArrayChildren["ChildGrade"][$i]].' / '
                                                                                    .$GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ArrayChildren["ChildClass"][$i]],
                                                                                    "Desactivated");

                                         if ($ArrayChildren["ChildWithoutPork"][$i] == 0)
                                         {
                                             $TabChildrenData[2][] = generateStyledText($GLOBALS["LANG_NO"], "Desactivated");
                                         }
                                         else
                                         {
                                             $TabChildrenData[2][] = generateStyledText($GLOBALS["LANG_YES"], "Desactivated");
                                         }

                                         $TabChildrenData[3][] = generateStyledText($iNbCanteenRegistrationsAfterCurrentDate,
                                                                                    "Desactivated");

                                         // We can't delete him
                                         $TabChildrenData[4][] = "&nbsp;";
                                     }
                                     else
                                     {
                                         // Child activated
                                         $ChildSchoolYear = getSchoolYear(date('Y-m-d'));
                                         $TabChildrenData[0][] = generateCryptedHyperlink($ArrayChildren["ChildFirstname"][$i], $CurrentID,
                                                                                          'UpdateChild.php',
                                                                                          $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                          '_blank');

                                         $TabChildrenData[1][] = $GLOBALS["CONF_GRADES"][$ArrayChildren["ChildGrade"][$i]].' / '
                                                                 .$GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ArrayChildren["ChildClass"][$i]];

                                         if ($ArrayChildren["ChildWithoutPork"][$i] == 0)
                                         {
                                             $TabChildrenData[2][] = $GLOBALS["LANG_NO"];
                                         }
                                         else
                                         {
                                             $TabChildrenData[2][] = $GLOBALS["LANG_YES"];
                                         }

                                         $TabChildrenData[3][] = $iNbCanteenRegistrationsAfterCurrentDate;

                                         // We can delete him
                                         $TabChildrenData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                "DeleteChild.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                                $GLOBALS["LANG_DELETE"], 'Affectation');
                                     }
                                 }
                             }

                             $Children = "<table><tr><td class=\"Action\">";
                             $Children .= generateCryptedHyperlink($GLOBALS["LANG_FAMILY_ADD_CHILD"], $FamilyID, 'AddChild.php',
                                                                   $GLOBALS["LANG_FAMILY_ADD_CHILD_TIP"], '', '_blank');
                             $Children .= "</td></tr></table>";
                             break;
                     }
                 }

                 // We get the payments of the family
                 // First, payments for school years contributions
                 $ArrayPayments = getFamilyPayments($DbConnection, $FamilyID, array('PaymentType' => array(0)), 'PaymentDate DESC');

                 // We define the captions of the payments table
                 $Payments = '&nbsp;';
                 $TabPaymentsCaptions = array($GLOBALS["LANG_SCHOOL_YEAR"], $GLOBALS["LANG_PAYMENT_DATE"], $GLOBALS["LANG_BANK"],
                                              $GLOBALS["LANG_PAYMENT_CHECK_NB"], $GLOBALS["LANG_PAYMENT_AMOUNT"]);

                 if ($bClosed)
                 {
                     if ((isset($ArrayPayments["PaymentID"])) && (count($ArrayPayments["PaymentID"]) > 0))
                     {
                         foreach($ArrayPayments["PaymentID"] as $i => $CurrentID)
                         {
                             $SchoolYear = getSchoolYear($ArrayPayments["PaymentReceiptDate"][$i]);
                             $TabPaymentsData[0][] = ($SchoolYear - 1)."-".$SchoolYear;
                             $TabPaymentsData[1][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                   strtotime($ArrayPayments["PaymentReceiptDate"][$i])),
                                                                              $CurrentID, 'UpdatePayment.php',
                                                                              $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                              '_blank');
                             $Bank = '-';
                             if (!empty($ArrayPayments["BankID"][$i]))
                             {
                                 $Bank = $ArrayPayments["BankName"][$i];
                             }
                             $TabPaymentsData[2][] = generateCryptedHyperlink($Bank, $ArrayPayments["BankID"][$i],
                                                                              'UpdateBank.php',
                                                                              $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                              '_blank');

                             $CheckNb = '-';
                             if (!empty($ArrayPayments["PaymentCheckNb"][$i]))
                             {
                                 $CheckNb = $ArrayPayments["PaymentCheckNb"][$i];
                             }
                             $TabPaymentsData[3][] = $CheckNb;

                             $TabPaymentsData[4][] = $ArrayPayments["PaymentAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
                         }
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArrayPayments["PaymentID"])) && (count($ArrayPayments["PaymentID"]) > 0))
                             {
                                 foreach($ArrayPayments["PaymentID"] as $i => $CurrentID)
                                 {
                                     $SchoolYear = getSchoolYear($ArrayPayments["PaymentReceiptDate"][$i]);
                                     $TabPaymentsData[0][] = ($SchoolYear - 1)."-".$SchoolYear;
                                     $TabPaymentsData[1][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                           strtotime($ArrayPayments["PaymentReceiptDate"][$i])),
                                                                                      $CurrentID, 'UpdatePayment.php',
                                                                                      $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                      '_blank');
                                     $Bank = '-';
                                     if (!empty($ArrayPayments["BankID"][$i]))
                                     {
                                         $Bank = $ArrayPayments["BankName"][$i];
                                     }
                                     $TabPaymentsData[2][] = generateCryptedHyperlink($Bank, $ArrayPayments["BankID"][$i],
                                                                                      'UpdateBank.php',
                                                                                      $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                      '_blank');

                                     $CheckNb = '-';
                                     if (!empty($ArrayPayments["PaymentCheckNb"][$i]))
                                     {
                                         $CheckNb = $ArrayPayments["PaymentCheckNb"][$i];
                                     }
                                     $TabPaymentsData[3][] = $CheckNb;

                                     $TabPaymentsData[4][] = $ArrayPayments["PaymentAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
                                 }
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             $TabPaymentsCaptions[] = '&nbsp;';

                             if ((isset($ArrayPayments["PaymentID"])) && (count($ArrayPayments["PaymentID"]) > 0))
                             {
                                 foreach($ArrayPayments["PaymentID"] as $i => $CurrentID)
                                 {
                                     $SchoolYear = getSchoolYear($ArrayPayments["PaymentReceiptDate"][$i]);
                                     $TabPaymentsData[0][] = ($SchoolYear - 1)."-".$SchoolYear;
                                     $TabPaymentsData[1][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                           strtotime($ArrayPayments["PaymentReceiptDate"][$i])),
                                                                                      $CurrentID, 'UpdatePayment.php',
                                                                                      $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                      '_blank');
                                     $Bank = '-';
                                     if (!empty($ArrayPayments["BankID"][$i]))
                                     {
                                         $Bank = $ArrayPayments["BankName"][$i];
                                     }
                                     $TabPaymentsData[2][] = generateCryptedHyperlink($Bank, $ArrayPayments["BankID"][$i],
                                                                                      'UpdateBank.php',
                                                                                      $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                      '_blank');

                                     $CheckNb = '-';
                                     if (!empty($ArrayPayments["PaymentCheckNb"][$i]))
                                     {
                                         $CheckNb = $ArrayPayments["PaymentCheckNb"][$i];
                                     }
                                     $TabPaymentsData[3][] = $CheckNb;

                                     $TabPaymentsData[4][] = $ArrayPayments["PaymentAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                                     $TabPaymentsData[5][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                            "DeletePayment.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                            $GLOBALS["LANG_DELETE"], 'Affectation');
                                 }
                             }

                             $Payments = "<table><tr><td class=\"Action\">";
                             $Payments .= generateStyledLinkText($GLOBALS["LANG_FAMILY_ADD_PAYMENT"],
                                                                 "AddPayment.php?Cr=".md5($FamilyID)."&amp;Id=$FamilyID&amp;CrType=".md5(0)."&amp;Type=0",
                                                                 '', $GLOBALS["LANG_FAMILY_ADD_PAYMENT_TIP"], '_blank');
                             $Payments .= "</td></tr></table>";
                             break;
                     }
                 }

                 // Next, payments not linked to bills
                 $ArrayPaymentsWithoutBills = getFamilyPayments($DbConnection, $FamilyID, array('PaymentType' => array(1),
                                                                                                'WithoutBillID' => TRUE
                                                                                               ), 'PaymentDate ASC');

                 // We define the captions of the payments table
                 $TabPaymentsWithoutBillsCaptions = array($GLOBALS["LANG_PAYMENT_DATE"], $GLOBALS["LANG_BANK"],
                                                          $GLOBALS["LANG_PAYMENT_CHECK_NB"], $GLOBALS["LANG_PAYMENT_AMOUNT"]);

                 if ($bClosed)
                 {
                     if ((isset($ArrayPaymentsWithoutBills["PaymentID"])) && (count($ArrayPaymentsWithoutBills["PaymentID"]) > 0))
                     {
                         foreach($ArrayPaymentsWithoutBills["PaymentID"] as $i => $CurrentID)
                         {
                             $TabPaymentsWithoutBillsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                               strtotime($ArrayPaymentsWithoutBills["PaymentReceiptDate"][$i])),
                                                                                          $CurrentID, 'UpdatePayment.php',
                                                                                          $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                          '_blank');
                             $Bank = '-';
                             if (!empty($ArrayPaymentsWithoutBills["BankID"][$i]))
                             {
                                 $Bank = $ArrayPaymentsWithoutBills["BankName"][$i];
                             }
                             $TabPaymentsWithoutBillsData[1][] = generateCryptedHyperlink($Bank, $ArrayPaymentsWithoutBills["BankID"][$i],
                                                                                          'UpdateBank.php',
                                                                                          $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                          '_blank');

                             $CheckNb = '-';
                             if (!empty($ArrayPaymentsWithoutBills["PaymentCheckNb"][$i]))
                             {
                                 $CheckNb = $ArrayPaymentsWithoutBills["PaymentCheckNb"][$i];
                             }
                             $TabPaymentsWithoutBillsData[2][] = $CheckNb;

                             $TabPaymentsWithoutBillsData[3][] = $ArrayPayments["PaymentAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
                         }
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArrayPaymentsWithoutBills["PaymentID"])) && (count($ArrayPaymentsWithoutBills["PaymentID"]) > 0))
                             {
                                 foreach($ArrayPaymentsWithoutBills["PaymentID"] as $i => $CurrentID)
                                 {
                                     $TabPaymentsWithoutBillsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                                       strtotime($ArrayPaymentsWithoutBills["PaymentReceiptDate"][$i])),
                                                                                                  $CurrentID, 'UpdatePayment.php',
                                                                                                  $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                  '_blank');
                                     $Bank = '-';
                                     if (!empty($ArrayPaymentsWithoutBills["BankID"][$i]))
                                     {
                                         $Bank = $ArrayPaymentsWithoutBills["BankName"][$i];
                                     }
                                     $TabPaymentsWithoutBillsData[1][] = generateCryptedHyperlink($Bank,
                                                                                                  $ArrayPaymentsWithoutBills["BankID"][$i],
                                                                                                  'UpdateBank.php',
                                                                                                  $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                  '_blank');

                                     $CheckNb = '-';
                                     if (!empty($ArrayPaymentsWithoutBills["PaymentCheckNb"][$i]))
                                     {
                                         $CheckNb = $ArrayPaymentsWithoutBills["PaymentCheckNb"][$i];
                                     }
                                     $TabPaymentsWithoutBillsData[2][] = $CheckNb;

                                     $TabPaymentsWithoutBillsData[3][] = $ArrayPaymentsWithoutBills["PaymentAmount"][$i]
                                                                         .' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
                                 }
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             $TabPaymentsWithoutBillsCaptions[] = '&nbsp;';

                             if ((isset($ArrayPaymentsWithoutBills["PaymentID"])) && (count($ArrayPaymentsWithoutBills["PaymentID"]) > 0))
                             {
                                 foreach($ArrayPaymentsWithoutBills["PaymentID"] as $i => $CurrentID)
                                 {
                                     $TabPaymentsWithoutBillsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                                       strtotime($ArrayPaymentsWithoutBills["PaymentReceiptDate"][$i])),
                                                                                                  $CurrentID, 'UpdatePayment.php',
                                                                                                  $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                  '_blank');
                                     $Bank = '-';
                                     if (!empty($ArrayPaymentsWithoutBills["BankID"][$i]))
                                     {
                                         $Bank = $ArrayPaymentsWithoutBills["BankName"][$i];
                                     }
                                     $TabPaymentsWithoutBillsData[1][] = generateCryptedHyperlink($Bank,
                                                                                                  $ArrayPaymentsWithoutBills["BankID"][$i],
                                                                                                  'UpdateBank.php',
                                                                                                  $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                  '_blank');

                                     $CheckNb = '-';
                                     if (!empty($ArrayPaymentsWithoutBills["PaymentCheckNb"][$i]))
                                     {
                                         $CheckNb = $ArrayPaymentsWithoutBills["PaymentCheckNb"][$i];
                                     }
                                     $TabPaymentsWithoutBillsData[2][] = $CheckNb;

                                     $TabPaymentsWithoutBillsData[3][] = $ArrayPaymentsWithoutBills["PaymentAmount"][$i]
                                                                         .' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                                     $TabPaymentsWithoutBillsData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                        "DeletePayment.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                                        $GLOBALS["LANG_DELETE"], 'Affectation');
                                 }
                             }
                             break;
                     }
                 }

                 // Next, bills of the family
                 $ArrayBills = getBills($DbConnection, NULL, NULL, 'BillForDate DESC', NO_DATES, array("FamilyID" => array($FamilyID)));

                 // Button to view payments history
                 $Bills = "<table><tr><td class=\"Action\">";
                 $Bills .= generateCryptedHyperlink($GLOBALS["LANG_FAMILY_BILLS_VIEW_HISTORY"], $FamilyID, 'ViewPaymentsHistoryFamily.php',
                                                    $GLOBALS["LANG_FAMILY_BILLS_VIEW_HISTORY_TIP"], '', '_blank');
                 $Bills .= "</td></tr></table>";

                 $TabBillsCaptions = array($GLOBALS["LANG_FAMILY_BILL_FOR_DATE"], $GLOBALS["LANG_FAMILY_BILL_AMOUNT"],
                                           $GLOBALS["LANG_FAMILY_BILL_PAYMENTS"], $GLOBALS['LANG_DOWNLOAD']);

                 $iNbBillsToDisplay = 0;
                 if ($bClosed)
                 {
                     if ((isset($ArrayBills["BillID"])) && (count($ArrayBills["BillID"]) > 0))
                     {
                         $PreviousPaymentID = 0;
                         foreach($ArrayBills["BillID"] as $i => $CurrentID)
                         {
                             // To view the detail of the bill
                             $Month = date("m", strtotime($ArrayBills["BillForDate"][$i]));
                             $Year = date("Y", strtotime($ArrayBills["BillForDate"][$i]));

                             $BillCaption = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";
                             $TabBillsData[0][] = generateCryptedHyperlink($BillCaption, $CurrentID, 'ViewBill.php',
                                                                           $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                             // Amount of the current bill
                             $BillAmountCellContent = $ArrayBills["BillAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                             // Get payments of the bill
                             $ArrayBillPayments = getFamilyPayments($DbConnection, $FamilyID, array("BillID" => array($CurrentID)),
                                                                    'PaymentDate DESC, PaymentID DESC');

                             if ($ArrayBills["BillPaid"][$i] == 1)
                             {
                                 // The bill is Paid
                                 $PaymentsList = generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'],
                                                                       $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                             }
                             else
                             {
                                 // The bill is not paid : we add a link to add a payement for this bill
                                 $PaymentsList = '';

                                 // Compute the % paid of the bill
                                 if ((isset($ArrayBillPayments["PaymentBillPartAmount"])) && (count($ArrayBillPayments["PaymentBillPartAmount"]) > 0))
                                 {
                                     // Bill amount
                                     $TmpCurrBillAmount = $ArrayBills["BillAmount"][$i];
                                     if (isFirstBillOfFamily($DbConnection, $CurrentID))
                                     {
                                         // It's the first bill in the system for this family : the amount of
                                         // this bill is the amount + previous amount
                                         $TmpCurrBillAmount += $ArrayBills["BillPreviousBalance"][$i];
                                     }

                                     // Compute the progress bar
                                     $sProgressBar = generateProgressVisualIndicator(NULL, $TmpCurrBillAmount,
                                                                                     $ArrayBills["BillPaidAmount"][$i],
                                                                                     max(0, $TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i]),
                                                                                     $GLOBALS['LANG_FAMILY_BILL_NOT_PAID_AMOUNT_TIP']." "
                                                                                     .($TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i])
                                                                                     ." ".$GLOBALS['CONF_PAYMENTS_UNIT']);

                                     $BillAmountCellContent .= generateBR(2)."$sProgressBar";
                                 }
                             }

                             $TabBillsData[1][] = $BillAmountCellContent;

                             if ((isset($ArrayBillPayments["PaymentID"])) && (count($ArrayBillPayments["PaymentID"]) > 0))
                             {
                                 foreach($ArrayBillPayments["PaymentID"] as $p => $CurrentPaymentID)
                                 {
                                     $PaymentsList .= generateBR(1);
                                     $PaymentsList .= generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                    strtotime($ArrayBillPayments["PaymentReceiptDate"][$p])),
                                                                               $CurrentPaymentID, 'UpdatePayment.php',
                                                                               $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                                     // We display on the last use of the payment the remained amount
                                     $fNotUsedAmount = round($ArrayBillPayments["PaymentAmount"][$p]
                                                             - $ArrayBillPayments["PaymentUsedAmount"][$p], 2);
                                     if (($fNotUsedAmount > 0) && ($CurrentPaymentID != $PreviousPaymentID))
                                     {
                                         // Payment not totaly used
                                         $PaymentsList .= " <span class=\"PaymentNotTotalyUsed\" title=\""
                                                           .$GLOBALS['LANG_FAMILY_PAYMENT_NOT_TOTALY_USED']." $fNotUsedAmount "
                                                           .$GLOBALS['CONF_PAYMENTS_UNIT']."\">(".$ArrayBillPayments["PaymentAmount"][$p].' '
                                                           .$GLOBALS['CONF_PAYMENTS_UNIT'].")</span>";
                                     }
                                     else
                                     {
                                         // Payment totaly used
                                         $PaymentsList .= ' ('.$ArrayBillPayments["PaymentAmount"][$p].' '
                                                          .$GLOBALS['CONF_PAYMENTS_UNIT'].')';
                                     }

                                     // Save the current payment ID as the previous payment ID for the next loop
                                     $PreviousPaymentID = $CurrentPaymentID;
                                 }
                             }

                             $TabBillsData[2][] = $PaymentsList;

                             // To download the bill
                             $TabBillsData[3][] = generateCryptedHyperlink($GLOBALS['LANG_DOWNLOAD'], $CurrentID, 'DownloadBill.php',
                                                                           $GLOBALS["LANG_DOWNLOAD"], '', '_blank');

                             // Go on to display next bill ?
                             $iNbBillsToDisplay++;
                             if ($GLOBALS['CONF_NB_FAMILY_BILLS'] > 0)
                             {
                                 // We display only the number of bills defined in CONF_NB_FAMILY_BILLS, except if there are
                                 // more not paid bills...
                                 // Check if the next bill is paid
                                 if ((isset($ArrayBills["BillPaid"][$i + 1])) && ($ArrayBills["BillPaid"][$i + 1] == 1) && ($iNbBillsToDisplay >= $GLOBALS['CONF_NB_FAMILY_BILLS']))
                                 {
                                     // We stop to display bills
                                     break;
                                 }
                             }
                         }
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArrayBills["BillID"])) && (count($ArrayBills["BillID"]) > 0))
                             {
                                 $PreviousPaymentID = 0;
                                 foreach($ArrayBills["BillID"] as $i => $CurrentID)
                                 {
                                     // To view the detail of the bill
                                     $Month = date("m", strtotime($ArrayBills["BillForDate"][$i]));
                                     $Year = date("Y", strtotime($ArrayBills["BillForDate"][$i]));

                                     $BillCaption = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";
                                     $TabBillsData[0][] = generateCryptedHyperlink($BillCaption, $CurrentID, 'ViewBill.php',
                                                                                   $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                   '_blank');

                                     // Amount of the current bill
                                     $BillAmountCellContent = $ArrayBills["BillAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                                     // Get payments of the bill
                                     $ArrayBillPayments = getFamilyPayments($DbConnection, $FamilyID, array("BillID" => array($CurrentID)),
                                                                            'PaymentDate DESC, PaymentID DESC');

                                     if ($ArrayBills["BillPaid"][$i] == 1)
                                     {
                                         // The bill is Paid
                                         $PaymentsList = generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'],
                                                                               $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                                     }
                                     else
                                     {
                                         // The bill is not paid : we add a link to add a payement for this bill
                                         $PaymentsList = '';

                                         // Compute the % paid of the bill
                                         if ((isset($ArrayBillPayments["PaymentBillPartAmount"])) && (count($ArrayBillPayments["PaymentBillPartAmount"]) > 0))
                                         {
                                             // Bill amount
                                             $TmpCurrBillAmount = $ArrayBills["BillAmount"][$i];
                                             if (isFirstBillOfFamily($DbConnection, $CurrentID))
                                             {
                                                 // It's the first bill in the system for this family : the amount of
                                                 // this bill is the amount + previous amount
                                                 $TmpCurrBillAmount += $ArrayBills["BillPreviousBalance"][$i];
                                             }

                                             // Compute the progress bar
                                             $sProgressBar = generateProgressVisualIndicator(NULL, $TmpCurrBillAmount,
                                                                                             $ArrayBills["BillPaidAmount"][$i],
                                                                                             max(0, $TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i]),
                                                                                             $GLOBALS['LANG_FAMILY_BILL_NOT_PAID_AMOUNT_TIP']." "
                                                                                             .($TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i])
                                                                                             ." ".$GLOBALS['CONF_PAYMENTS_UNIT']);

                                             $BillAmountCellContent .= generateBR(2)."$sProgressBar";
                                         }
                                     }

                                     $TabBillsData[1][] = $BillAmountCellContent;

                                     if ((isset($ArrayBillPayments["PaymentID"])) && (count($ArrayBillPayments["PaymentID"]) > 0))
                                     {
                                         foreach($ArrayBillPayments["PaymentID"] as $p => $CurrentPaymentID)
                                         {
                                             $PaymentsList .= generateBR(1);
                                             $PaymentsList .= generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                            strtotime($ArrayBillPayments["PaymentReceiptDate"][$p])),
                                                                                       $CurrentPaymentID, 'UpdatePayment.php',
                                                                                       $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                       '_blank');

                                             // We display on the last use of the payment the remained amount
                                             $fNotUsedAmount = round($ArrayBillPayments["PaymentAmount"][$p]
                                                                     - $ArrayBillPayments["PaymentUsedAmount"][$p], 2);
                                             if (($fNotUsedAmount > 0) && ($CurrentPaymentID != $PreviousPaymentID))
                                             {
                                                 // Payment not totaly used
                                                 $PaymentsList .= " <span class=\"PaymentNotTotalyUsed\" title=\""
                                                                  .$GLOBALS['LANG_FAMILY_PAYMENT_NOT_TOTALY_USED']." $fNotUsedAmount "
                                                                  .$GLOBALS['CONF_PAYMENTS_UNIT']."\">("
                                                                  .$ArrayBillPayments["PaymentAmount"][$p].' '
                                                                  .$GLOBALS['CONF_PAYMENTS_UNIT'].")</span>";
                                             }
                                             else
                                             {
                                                 // Payment totaly used
                                                 $PaymentsList .= ' ('.$ArrayBillPayments["PaymentAmount"][$p].' '
                                                                  .$GLOBALS['CONF_PAYMENTS_UNIT'].')';
                                             }

                                             // Save the current payment ID as the previous payment ID for the next loop
                                             $PreviousPaymentID = $CurrentPaymentID;
                                         }
                                     }

                                     $TabBillsData[2][] = $PaymentsList;

                                     // To download the bill
                                     $TabBillsData[3][] = generateCryptedHyperlink($GLOBALS['LANG_DOWNLOAD'], $CurrentID, 'DownloadBill.php',
                                                                                   $GLOBALS["LANG_DOWNLOAD"], '',
                                                                                   '_blank');

                                     // Go on to display next bill ?
                                     $iNbBillsToDisplay++;
                                     if ($GLOBALS['CONF_NB_FAMILY_BILLS'] > 0)
                                     {
                                         // We display only the number of bills defined in CONF_NB_FAMILY_BILLS, except if there are
                                         // more not paid bills...
                                         // Check if the next bill is paid
                                         if ((isset($ArrayBills["BillPaid"][$i + 1])) && ($ArrayBills["BillPaid"][$i + 1] == 1) && ($iNbBillsToDisplay >= $GLOBALS['CONF_NB_FAMILY_BILLS']))
                                         {
                                             // We stop to display bills
                                             break;
                                         }
                                     }
                                 }
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             if ((isset($ArrayBills["BillID"])) && (count($ArrayBills["BillID"]) > 0))
                             {
                                 $PreviousPaymentID = 0;
                                 foreach($ArrayBills["BillID"] as $i => $CurrentID)
                                 {
                                     // To view the detail of the bill
                                     $Month = date("m", strtotime($ArrayBills["BillForDate"][$i]));
                                     $Year = date("Y", strtotime($ArrayBills["BillForDate"][$i]));

                                     $BillCaption = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";
                                     $TabBillsData[0][] = generateCryptedHyperlink($BillCaption, $CurrentID, 'ViewBill.php',
                                                                                   $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                   '_blank');

                                     // Amount of the current bill
                                     $BillAmountCellContent = $ArrayBills["BillAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                                     // Get payments of the bill
                                     $ArrayBillPayments = getFamilyPayments($DbConnection, $FamilyID, array("BillID" => array($CurrentID)),
                                                                            'PaymentDate DESC, PaymentID DESC');

                                     if ($ArrayBills["BillPaid"][$i] == 1)
                                     {
                                         // The bill is Paid
                                         $PaymentsList = generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'],
                                                                               $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                                     }
                                     else
                                     {
                                         // The bill is not paid : we add a link to add a payement for this bill
                                         $PaymentsList = generateStyledLinkText($GLOBALS["LANG_FAMILY_ADD_PAYMENT"],
                                                                                "AddPayment.php?Cr=".md5($FamilyID)."&amp;Id=$FamilyID&amp;CrType=".md5(1)."&amp;Type=1&amp;CrBillID=".md5($CurrentID)."&amp;BillID=$CurrentID",
                                                                                '', $GLOBALS["LANG_FAMILY_ADD_PAYMENT_TIP"], '_blank');

                                         $PaymentsList .= generateBR(1);

                                         // Compute the % paid of the bill
                                         if ((isset($ArrayBillPayments["PaymentBillPartAmount"])) && (count($ArrayBillPayments["PaymentBillPartAmount"]) > 0))
                                         {
                                             // Bill amount
                                             $TmpCurrBillAmount = $ArrayBills["BillAmount"][$i];
                                             if (isFirstBillOfFamily($DbConnection, $CurrentID))
                                             {
                                                 // It's the first bill in the system for this family : the amount of
                                                 // this bill is the amount + previous amount
                                                 $TmpCurrBillAmount += $ArrayBills["BillPreviousBalance"][$i];
                                             }

                                             // Compute the progress bar
                                             $sProgressBar = generateProgressVisualIndicator(NULL, $TmpCurrBillAmount,
                                                                                             $ArrayBills["BillPaidAmount"][$i],
                                                                                             max(0, $TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i]),
                                                                                             $GLOBALS['LANG_FAMILY_BILL_NOT_PAID_AMOUNT_TIP']." "
                                                                                             .($TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i])
                                                                                             ." ".$GLOBALS['CONF_PAYMENTS_UNIT']);

                                             $BillAmountCellContent .= generateBR(2)."$sProgressBar";
                                         }
                                     }

                                     $TabBillsData[1][] = $BillAmountCellContent;

                                     if ((isset($ArrayBillPayments["PaymentID"])) && (count($ArrayBillPayments["PaymentID"]) > 0))
                                     {
                                         foreach($ArrayBillPayments["PaymentID"] as $p => $CurrentPaymentID)
                                         {
                                             $PaymentsList .= generateBR(1);
                                             $PaymentsList .= generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                            strtotime($ArrayBillPayments["PaymentReceiptDate"][$p])),
                                                                                       $CurrentPaymentID, 'UpdatePayment.php',
                                                                                       $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                       '_blank');

                                             // We display on the last use of the payment the remained amount
                                             $fNotUsedAmount = round($ArrayBillPayments["PaymentAmount"][$p]
                                                                     - $ArrayBillPayments["PaymentUsedAmount"][$p], 2);
                                             if (($fNotUsedAmount > 0) && ($CurrentPaymentID != $PreviousPaymentID))
                                             {
                                                 // Payment not totaly used
                                                 $PaymentsList .= " <span class=\"PaymentNotTotalyUsed\" title=\""
                                                                  .$GLOBALS['LANG_FAMILY_PAYMENT_NOT_TOTALY_USED']." $fNotUsedAmount "
                                                                  .$GLOBALS['CONF_PAYMENTS_UNIT']."\">("
                                                                  .$ArrayBillPayments["PaymentAmount"][$p].' '
                                                                  .$GLOBALS['CONF_PAYMENTS_UNIT'].")</span>";
                                             }
                                             else
                                             {
                                                 // Payment totaly used
                                                 $PaymentsList .= ' ('.$ArrayBillPayments["PaymentAmount"][$p].' '
                                                                  .$GLOBALS['CONF_PAYMENTS_UNIT'].')';
                                             }

                                             // We display the button to delete the payment
                                             $PaymentsList .= ' '.generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                 "DeletePayment.php?Cr=".md5($CurrentPaymentID)."&amp;Id=$CurrentPaymentID",
                                                                                                 $GLOBALS["LANG_DELETE"], 'Affectation');

                                             // Save the current payment ID as the previous payment ID for the next loop
                                             $PreviousPaymentID = $CurrentPaymentID;
                                         }
                                     }

                                     $TabBillsData[2][] = $PaymentsList;

                                     // To download the bill
                                     $TabBillsData[3][] = generateCryptedHyperlink($GLOBALS['LANG_DOWNLOAD'], $CurrentID, 'DownloadBill.php',
                                                                                   $GLOBALS["LANG_DOWNLOAD"], '',
                                                                                   '_blank');

                                     // Go on to display next bill ?
                                     $iNbBillsToDisplay++;
                                     if ($GLOBALS['CONF_NB_FAMILY_BILLS'] > 0)
                                     {
                                         // We display only the number of bills defined in CONF_NB_FAMILY_BILLS, except if there are
                                         // more not paid bills...
                                         // Check if the next bill is paid
                                         if ((isset($ArrayBills["BillPaid"][$i + 1])) && ($ArrayBills["BillPaid"][$i + 1] == 1) && ($iNbBillsToDisplay >= $GLOBALS['CONF_NB_FAMILY_BILLS']))
                                         {
                                             // We stop to display bills
                                             break;
                                         }
                                     }
                                 }
                             }
                             break;
                     }
                 }

                 // We get the discounts of the family
                 $ArrayDiscounts = getFamilyDiscounts($DbConnection, $FamilyID, array(), 'DiscountFamilyDate DESC');

                 // We define the captions of the discounts table
                 $Discounts = '&nbsp;';
                 $TabDiscountsCaptions = array($GLOBALS["LANG_DISCOUNT_DATE"], $GLOBALS["LANG_DISCOUNT_TYPE"],
                                               $GLOBALS["LANG_DISCOUNT_REASON_TYPE"], $GLOBALS["LANG_DISCOUNT_AMOUNT"],
                                               $GLOBALS["LANG_DISCOUNT_REASON"]);

                 if ($bClosed)
                 {
                     // We transform the result to be displayed
                     if ((isset($ArrayDiscounts["DiscountFamilyID"])) && (count($ArrayDiscounts["DiscountFamilyID"]) > 0))
                     {
                         foreach($ArrayDiscounts["DiscountFamilyID"] as $i => $CurrentID)
                         {
                             $TabDiscountsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                    strtotime($ArrayDiscounts["DiscountFamilyDate"][$i])),
                                                                               $CurrentID, 'UpdateDiscountFamily.php',
                                                                               $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                               '_blank');

                             $TabDiscountsData[1][] = $GLOBALS['CONF_DISCOUNTS_FAMILIES_TYPES'][$ArrayDiscounts["DiscountFamilyType"][$i]];
                             $TabDiscountsData[2][] = $GLOBALS['CONF_DISCOUNTS_FAMILIES_REASON_TYPES'][$ArrayDiscounts["DiscountFamilyReasonType"][$i]];
                             $TabDiscountsData[3][] = $ArrayDiscounts["DiscountFamilyAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                             $sReason = '-';
                             if (!empty($ArrayDiscounts["DiscountFamilyReason"][$i]))
                             {
                                 $sReason = $ArrayDiscounts["DiscountFamilyReason"][$i];
                             }
                             $TabDiscountsData[4][] = $sReason;
                         }
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArrayDiscounts["DiscountFamilyID"])) && (count($ArrayDiscounts["DiscountFamilyID"]) > 0))
                             {
                                 foreach($ArrayDiscounts["DiscountFamilyID"] as $i => $CurrentID)
                                 {
                                     $TabDiscountsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                            strtotime($ArrayDiscounts["DiscountFamilyDate"][$i])),
                                                                                       $CurrentID, 'UpdateDiscountFamily.php',
                                                                                       $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                       '_blank');

                                     $TabDiscountsData[1][] = $GLOBALS['CONF_DISCOUNTS_FAMILIES_TYPES'][$ArrayDiscounts["DiscountFamilyType"][$i]];
                                     $TabDiscountsData[2][] = $GLOBALS['CONF_DISCOUNTS_FAMILIES_REASON_TYPES'][$ArrayDiscounts["DiscountFamilyReasonType"][$i]];
                                     $TabDiscountsData[3][] = $ArrayDiscounts["DiscountFamilyAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                                     $sReason = '-';
                                     if (!empty($ArrayDiscounts["DiscountFamilyReason"][$i]))
                                     {
                                         $sReason = $ArrayDiscounts["DiscountFamilyReason"][$i];
                                     }
                                     $TabDiscountsData[4][] = $sReason;
                                 }
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             $TabDiscountsCaptions[] = '&nbsp;';

                             if ((isset($ArrayDiscounts["DiscountFamilyID"])) && (count($ArrayDiscounts["DiscountFamilyID"]) > 0))
                             {
                                 foreach($ArrayDiscounts["DiscountFamilyID"] as $i => $CurrentID)
                                 {
                                     $TabDiscountsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                            strtotime($ArrayDiscounts["DiscountFamilyDate"][$i])),
                                                                                       $CurrentID, 'UpdateDiscountFamily.php',
                                                                                       $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                       '_blank');

                                     $TabDiscountsData[1][] = $GLOBALS['CONF_DISCOUNTS_FAMILIES_TYPES'][$ArrayDiscounts["DiscountFamilyType"][$i]];
                                     $TabDiscountsData[2][] = $GLOBALS['CONF_DISCOUNTS_FAMILIES_REASON_TYPES'][$ArrayDiscounts["DiscountFamilyReasonType"][$i]];
                                     $TabDiscountsData[3][] = $ArrayDiscounts["DiscountFamilyAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                                     $sReason = '-';
                                     if (!empty($ArrayDiscounts["DiscountFamilyReason"][$i]))
                                     {
                                         $sReason = $ArrayDiscounts["DiscountFamilyReason"][$i];
                                     }
                                     $TabDiscountsData[4][] = $sReason;

                                     $TabDiscountsData[5][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                            "DeleteDiscountFamily.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                            $GLOBALS["LANG_DELETE"], 'Affectation');
                                 }
                             }

                             $Discounts = "<table><tr><td class=\"Action\">";
                             $Discounts .= generateStyledLinkText($GLOBALS["LANG_FAMILY_ADD_DISCOUNT"],
                                                                 "AddDiscountFamily.php?Cr=".md5($FamilyID)."&amp;Id=$FamilyID",
                                                                 '', $GLOBALS["LANG_FAMILY_ADD_DISCOUNT_TIP"], '_blank');
                             $Discounts .= "</td></tr></table>";
                             break;
                     }
                 }

                 // We get documents to approve or approved by the family while the family was activated
                 // We define the captions of the documents approvals table
                 $DocumentsApprovals = '&nbsp;';
                 $TabDocumentsApprovalsCaptions  = array(ucfirst($GLOBALS["LANG_DATE"]), $GLOBALS["LANG_DOCUMENT_APPROVAL_NAME"], $GLOBALS["LANG_DOCUMENT_APPROVAL_TYPE"],
                                                         ucfirst($GLOBALS["LANG_DATE"]), $GLOBALS["LANG_DOCUMENT_FAMILY_APPROVAL_BY"],
                                                         $GLOBALS["LANG_DOCUMENT_FAMILY_APPROVAL_COMMENT"]);

                 if ($bClosed)
                 {
                     $ArrayParams = array(
                                          'StartDate' => array('>=', $FamilyRecord['FamilyDate']),
                                          'EndDate' => array('<=', $FamilyRecord['FamilyDesactivationDate'])
                                         );
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                         case FCT_ACT_UPDATE:
                             $ArrayParams = array(
                                                  'StartDate' => array('>=', $FamilyRecord['FamilyDate'])
                                                 );

                             $ArrayDocumentsApprovals = dbSearchDocumentApproval($DbConnection, $ArrayParams, "DocumentApprovalDate DESC", 1, 0);
                             if ((isset($ArrayDocumentsApprovals['DocumentApprovalID'])) && (!empty($ArrayDocumentsApprovals['DocumentApprovalID'])))
                             {
                                 // We get approvals of the family
                                 $ArrayFamilyApprovals = getDocumentsApprovalsOfFamily($DbConnection, $FamilyID);

                                 foreach($ArrayDocumentsApprovals['DocumentApprovalID'] as $i => $CurrentID)
                                 {
                                     // We check if the family has approved the document
                                     $iDocPos = array_search($CurrentID, $ArrayFamilyApprovals['DocumentApprovalID']);

                                     $TabDocumentsApprovalsData[0][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                            strtotime($ArrayDocumentsApprovals["DocumentApprovalDate"][$i]));

                                     if ($iDocPos === FALSE)
                                     {
                                         // Document not approved by the family
                                         $TabDocumentsApprovalsData[1][] = generateCryptedHyperlink($ArrayDocumentsApprovals["DocumentApprovalName"][$i],
                                                                                                    $CurrentID, 'UpdateDocumentApproval.php',
                                                                                                    $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                    '_blank');

                                         $TabDocumentsApprovalsData[2][] = $GLOBALS['CONF_DOCUMENTS_APPROVALS_TYPES'][$ArrayDocumentsApprovals["DocumentApprovalType"][$i]];

                                         $TabDocumentsApprovalsData[3][] = "&nbsp;";
                                         $TabDocumentsApprovalsData[4][] = "&nbsp;";
                                         $TabDocumentsApprovalsData[5][] = "&nbsp;";
                                     }
                                     else
                                     {
                                         $TabDocumentsApprovalsData[1][] = generateCryptedHyperlink($ArrayDocumentsApprovals["DocumentApprovalName"][$i],
                                                                                                    $CurrentID, 'UpdateDocumentApproval.php',
                                                                                                    $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                    '_blank')
                                                                           ." ".generateStyledPicture($GLOBALS['CONF_DOCUMENT_APPROVED_ICON'],
                                                                                                      $GLOBALS['LANG_DOCUMENT_FAMILY_APPROVAL_APPROVED'], '');

                                         $TabDocumentsApprovalsData[2][] = $GLOBALS['CONF_DOCUMENTS_APPROVALS_TYPES'][$ArrayDocumentsApprovals["DocumentApprovalType"][$i]];

                                         $TabDocumentsApprovalsData[3][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'].' '.$GLOBALS['CONF_TIME_DISPLAY_FORMAT'],
                                                                            strtotime($ArrayFamilyApprovals["DocumentFamilyApprovalDate"][$iDocPos]));

                                         $TabDocumentsApprovalsData[4][] = $ArrayFamilyApprovals["SupportMemberLastname"][$iDocPos].' '
                                                                           .$ArrayFamilyApprovals["SupportMemberFirstname"][$iDocPos];
                                         $TabDocumentsApprovalsData[5][] = nullFormatText($ArrayFamilyApprovals["DocumentFamilyApprovalComment"][$iDocPos]);
                                     }
                                 }
                             }
                             break;
                     }
                 }
             }
             else
             {
                 // Create the Towns list
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                         $DbResultList = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
                         $Town = '&nbsp;';
                         if (!DB::isError($DbResultList))
                         {
                             $ArrayTownID = array(0);
                             $ArrayTownInfos = array('');
                             while($RecordList = $DbResultList->fetchRow(DB_FETCHMODE_ASSOC))
                             {
                                 $ArrayTownID[] = $RecordList["TownID"];
                                 $ArrayTownInfos[] = $RecordList["TownName"].' ('.$RecordList["TownCode"].')';
                             }

                             $Town = generateSelectField("lTownID", $ArrayTownID, $ArrayTownInfos, $FamilyRecord['TownID']);

                             // Display a button to add a new town
                             $Town .= generateStyledPictureHyperlink($GLOBALS["CONF_ADD_ICON"], "AddTown.php?Cr=".md5('')."&amp;Id=",
                                                                     $GLOBALS["LANG_ADD_TOWN_TIP"], 'Affectation', '_blank');
                         }
                         break;
                 }

                 // Desactivation date
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                         $DesactivationDate = generateInputField("desactivationDate", "text", "10", "10",
                                                                 $GLOBALS["LANG_FAMILY_DESACTIVATION_DATE_TIP"], '', TRUE);

                         // Insert the javascript to use the calendar component
                         $DesactivationDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t DesactivationDateCalendar = new dynCalendar('DesactivationDateCalendar', 'calendarCallbackDesactivationDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                         break;
                 }
             }

             // <<< FamilyLastname INPUTFIELD >>>
             if ($bClosed)
             {
                 $Lastname = stripslashes($FamilyRecord["FamilyLastname"]);
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Lastname = stripslashes($FamilyRecord["FamilyLastname"]);
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Lastname = generateInputField("sLastname", "text", "100", "50", $GLOBALS["LANG_FAMILY_LASTNAME_TIP"],
                                                        $FamilyRecord["FamilyLastname"]);
                         break;
                 }
             }

             // <<< FamilyMainEmail INPUTFIELD >>>
             if ($bClosed)
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_UPDATE_OLD_USER:
                         // The old family can update the main e-mail address
                         $MainEmail = generateInputField("sMainEmail", "text", "100", "70", $GLOBALS["LANG_FAMILY_MAIN_EMAIL_TIP"],
                                                         $FamilyRecord["FamilyMainEmail"]);

                         $Checked = FALSE;
                         if (!empty($FamilyRecord["FamilyMainEmailContactAllowed"]))
                         {
                             $Checked = TRUE;
                         }

                         $MainEmail .= " ".generateInputField("chkMainEmailContactAllowed", "checkbox", "", "",
                                                              $GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED_TIP"], 1,
                                                              FALSE, $Checked)." ".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"];
                         break;

                     default:
                         $MainEmail = stripslashes($FamilyRecord["FamilyMainEmail"]);

                         if (!empty($FamilyRecord["FamilyMainEmailContactAllowed"]))
                         {
                             $MainEmail .= " <em>(".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"].")</em>";
                         }
                         break;
                 }

                 if (!empty($FamilyRecord["FamilyMainEmailContactAllowed"]))
                 {
                     $MainEmail .= generateBR(1)." <em>".$GLOBALS['LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE']."</em>";
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $MainEmail = stripslashes(nullFormatText($FamilyRecord["FamilyMainEmail"]));

                         if (!empty($FamilyRecord["FamilyMainEmailContactAllowed"]))
                         {
                             $MainEmail .= " <em>(".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"].")</em>";
                         }

                         if (!empty($FamilyRecord["FamilyMainEmailInCommittee"]))
                         {
                             $MainEmail .= generateBR(1)."<em>".$GLOBALS['LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE']."</em>";
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $MainEmail = generateInputField("sMainEmail", "text", "100", "70", $GLOBALS["LANG_FAMILY_MAIN_EMAIL_TIP"],
                                                         $FamilyRecord["FamilyMainEmail"]);

                         if (!empty($FamilyRecord["FamilyMainEmailContactAllowed"]))
                         {
                             $MainEmail .= " <em>(".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"].")</em>";
                         }

                         $Checked = FALSE;
                         if (!empty($FamilyRecord["FamilyMainEmailInCommittee"]))
                         {
                             $Checked = TRUE;
                         }

                         $MainEmail .= generateBR(1).generateInputField("chkMainEmailInCommittee", "checkbox", "", "",
                                                                        $GLOBALS["LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE_TIP"], 1,
                                                                        FALSE, $Checked)." ".$GLOBALS["LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE"];
                         break;

                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $MainEmail = generateInputField("sMainEmail", "text", "100", "70", $GLOBALS["LANG_FAMILY_MAIN_EMAIL_TIP"],
                                                         $FamilyRecord["FamilyMainEmail"]);

                         $Checked = FALSE;
                         if (!empty($FamilyRecord["FamilyMainEmailContactAllowed"]))
                         {
                             $Checked = TRUE;
                         }

                         $MainEmail .= " ".generateInputField("chkMainEmailContactAllowed", "checkbox", "", "",
                                                              $GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED_TIP"], 1,
                                                              FALSE, $Checked)." ".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"];

                         if (!empty($FamilyRecord["FamilyMainEmailInCommittee"]))
                         {
                             $MainEmail .= generateBR(1)."<em>".$GLOBALS['LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE']."</em>";
                         }
                         break;
                 }
             }

             // <<< FamilySecondEmail INPUTFIELD >>>
             if ($bClosed)
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_UPDATE_OLD_USER:
                         // The old family can update the second e-mail address
                         $SecondEmail = generateInputField("sSecondEmail", "text", "100", "70", $GLOBALS["LANG_FAMILY_SECOND_EMAIL_TIP"],
                                                           $FamilyRecord["FamilySecondEmail"]);

                         $Checked = FALSE;
                         if (!empty($FamilyRecord["FamilySecondEmailContactAllowed"]))
                         {
                             $Checked = TRUE;
                         }

                         $SecondEmail .= " ".generateInputField("chkSecondEmailContactAllowed", "checkbox", "", "",
                                                                $GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED_TIP"], 1,
                                                                FALSE, $Checked)." ".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"];
                         break;

                     default:
                         $SecondEmail = stripslashes(nullFormatText($FamilyRecord["FamilySecondEmail"]));

                         if (!empty($FamilyRecord["FamilySecondEmailContactAllowed"]))
                         {
                             $SecondEmail .= " <em>(".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"].")</em>";
                         }
                         break;
                 }

                 if (!empty($FamilyRecord["FamilySecondEmailInCommittee"]))
                 {
                     $SecondEmail .= generateBR(1)."<em>".$GLOBALS['LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE']."</em>";
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $SecondEmail = stripslashes(nullFormatText($FamilyRecord["FamilySecondEmail"]));

                         if (!empty($FamilyRecord["FamilySecondEmailContactAllowed"]))
                         {
                             $SecondEmail .= " <em>(".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"].")</em>";
                         }

                         if (!empty($FamilyRecord["FamilySecondEmailInCommittee"]))
                         {
                             $SecondEmail .= generateBR(1)."<em>".$GLOBALS['LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE']."</em>";
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $SecondEmail = generateInputField("sSecondEmail", "text", "100", "70", $GLOBALS["LANG_FAMILY_SECOND_EMAIL_TIP"],
                                                           $FamilyRecord["FamilySecondEmail"]);

                         if (!empty($FamilyRecord["FamilySecondEmailContactAllowed"]))
                         {
                             $SecondEmail .= " <em>(".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"].")</em>";
                         }

                         $Checked = FALSE;
                         if (!empty($FamilyRecord["FamilySecondEmailInCommittee"]))
                         {
                             $Checked = TRUE;
                         }

                         $SecondEmail .= generateBR(1).generateInputField("chkSecondEmailInCommittee", "checkbox", "", "",
                                                                          $GLOBALS["LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE_TIP"], 1,
                                                                          FALSE, $Checked)." ".$GLOBALS["LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE"];
                         break;

                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $SecondEmail = generateInputField("sSecondEmail", "text", "100", "70", $GLOBALS["LANG_FAMILY_SECOND_EMAIL_TIP"],
                                                           $FamilyRecord["FamilySecondEmail"]);

                         $Checked = FALSE;
                         if (!empty($FamilyRecord["FamilySecondEmailContactAllowed"]))
                         {
                             $Checked = TRUE;
                         }

                         $SecondEmail .= " ".generateInputField("chkSecondEmailContactAllowed", "checkbox", "", "",
                                                                $GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED_TIP"], 1,
                                                                FALSE, $Checked)." ".$GLOBALS["LANG_FAMILY_EMAIL_CONTACT_ALLOWED"];

                         if (!empty($FamilyRecord["FamilySecondEmailInCommittee"]))
                         {
                             $SecondEmail .= generateBR(1)."<em>".$GLOBALS['LANG_FAMILY_EMAIL_MEMBER_IN_COMMITTEE']."</em>";
                         }
                         break;
                 }
             }

             // <<< FamilySpecialAnnualContribution CHECKBOX >>>
             if ($bClosed)
             {
                 $SpecialAnnualContribution = $GLOBALS["LANG_NO"];
                 if (!empty($FamilyRecord["FamilySpecialAnnualContribution"]))
                 {
                     $SpecialAnnualContribution = $GLOBALS["LANG_YES"];
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $SpecialAnnualContribution = $GLOBALS["LANG_NO"];
                         if (!empty($FamilyRecord["FamilySpecialAnnualContribution"]))
                         {
                             $SpecialAnnualContribution = $GLOBALS["LANG_YES"];
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Checked = FALSE;
                         if (!empty($FamilyRecord["FamilySpecialAnnualContribution"]))
                         {
                             $Checked = TRUE;
                         }

                         $SpecialAnnualContribution = generateInputField("chkSpecialAnnualContribution", "checkbox", "", "",
                                                                         $GLOBALS["LANG_FAMILY_SPECIAL_ANNUAL_CONTRIBUTION_TIP"], 1,
                                                                         FALSE, $Checked)." ".$GLOBALS["LANG_YES"];
                         break;
                 }
             }

             // <<< FamilyMonthlyContributionMode SELECTFIELD >>>
             if ($bClosed)
             {
                 $MonthlyContribMode = $GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES'][$FamilyRecord['FamilyMonthlyContributionMode']];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $MonthlyContribMode = $GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES'][$FamilyRecord['FamilyMonthlyContributionMode']];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         // Generate the list of monthly contribution modes only available for the current school year
                         $ArrayMonthlyContribModes = array();
                         $ArrayMonthlyContribLabels = array();
                         $AvailableMonthlyContributionModes = array();
                         if ((isset($GLOBALS['CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS'][$CurrentSchoolYear]))
                             && (!empty($GLOBALS['CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS'][$CurrentSchoolYear])))
                         {
                             $AvailableMonthlyContributionModes = array_keys($GLOBALS['CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS'][$CurrentSchoolYear]);
                         }
                         else
                         {
                             // All modes are available
                             $AvailableMonthlyContributionModes = array_keys($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES']);
                         }

                         foreach($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES'] as $ModeID => $ModeLabel)
                         {
                            // We keep the mode if avilable or if the current mode of the family isn't available
                            if ((in_array($ModeID, $AvailableMonthlyContributionModes))
                                || (($ModeID == $FamilyRecord['FamilyMonthlyContributionMode'])
                                    && (!in_array($FamilyRecord['FamilyMonthlyContributionMode'], $AvailableMonthlyContributionModes))))
                            {
                                $ArrayMonthlyContribModes[] = $ModeID;
                                $ArrayMonthlyContribLabels[] = $ModeLabel;
                            }
                         }

                         $MonthlyContribMode = generateSelectField("lMonthlyContributionMode", $ArrayMonthlyContribModes,
                                                                   $ArrayMonthlyContribLabels,
                                                                   $FamilyRecord['FamilyMonthlyContributionMode']);
                         break;
                 }
             }

             // <<< FamilyNbMembers INPUTFIELD >>>
             if ($bClosed)
             {
                 $NbMembers = $FamilyRecord["FamilyNbMembers"];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $NbMembers = $FamilyRecord["FamilyNbMembers"];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $NbMembers = generateInputField("sNbMembers", "text", "3", "3", $GLOBALS["LANG_FAMILY_NB_MEMBERS_TIP"],
                                                         $FamilyRecord["FamilyNbMembers"]);
                         break;
                 }
             }

             // <<< FamilyNbPoweredMembers INPUTFIELD >>>
             if ($bClosed)
             {
                 $NbPoweredMembers = $FamilyRecord["FamilyNbPoweredMembers"];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $NbPoweredMembers = $FamilyRecord["FamilyNbPoweredMembers"];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $NbPoweredMembers = generateInputField("sNbPoweredMembers", "text", "3", "3",
                                                                $GLOBALS["LANG_FAMILY_NB_POWERED_MEMBERS_TIP"],
                                                                $FamilyRecord["FamilyNbPoweredMembers"]);
                         break;
                 }
             }

             // <<< FamilyComment TEXTAREA >>>
             if ($bClosed)
             {
                 $Comment = stripslashes(nullFormatText($FamilyRecord["FamilyComment"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Comment = stripslashes(nullFormatText($FamilyRecord["FamilyComment"]));
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Comment = generateTextareaField("sComment", 10, 60, $GLOBALS["LANG_COMMENT_TIP"],
                                                          invFormatText($FamilyRecord["FamilyComment"]));
                         break;
                 }
             }

             // <<< FamilyAnnualContributionBalance >>>
             $AnnualContributionBalance = $FamilyRecord["FamilyAnnualContributionBalance"];
             $AnnualContributionBalanceStyle = '';
             if ($AnnualContributionBalance < 0)
             {
                 // Display a warning
                 $AnnualContributionBalanceStyle = 'NegativeBalance';
             }
             $AnnualContributionBalance .= ' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

             // <<< FamilyBalance >>>
             $Balance = $FamilyRecord["FamilyBalance"];
             $BalanceStyle = '';
             if ($Balance < 0)
             {
                 // Display a warning
                 $BalanceStyle = 'NegativeBalance';
             }
             $Balance .= ' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

             // Get support members linked to the family
             $SupportMembersList = "&nbsp;";
             $ArraySupportMembers = dbSearchSupportMember($DbConnection, array('FamilyID' => array($FamilyID)), "SupportMemberLastname, SupportMemberFirstname", 1, 0);
             if ((isset($ArraySupportMembers['SupportMemberID'])) && (!empty($ArraySupportMembers['SupportMemberID'])))
             {
                 $SupportMembersList = "<ul>\n";

                 foreach($ArraySupportMembers['SupportMemberID'] as $s => $CurrentID)
                 {
                     $SupportMemberStyle = "";
                     if ($ArraySupportMembers["SupportMemberActivated"][$s] == 0)
                     {
                         $SupportMemberStyle = "Desactivated";
                     }


                     $SupportMembersList .= "<li>".generateStyledText($ArraySupportMembers['SupportMemberLastname'][$s].' '.$ArraySupportMembers['SupportMemberFirstname'][$s]
                                            ." (".$ArraySupportMembers['SupportMemberStateName'][$s].")", $SupportMemberStyle)."</li>\n";
                 }

                 $SupportMembersList .= "</ul>\n";
             }

             // Display the form
             echo "<table id=\"FamilyDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CREATION_DATE"]."</td><td class=\"Value\">$CreationDate</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_LASTNAME"]."*</td><td class=\"Value\" colspan=\"3\">$Lastname</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_TOWN"]."*</td><td class=\"Value\" colspan=\"3\">$Town</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_MAIN_EMAIL"]."*</td><td class=\"Value\" colspan=\"3\">$MainEmail</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_SECOND_EMAIL"]."</td><td class=\"Value\" colspan=\"3\">$SecondEmail</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_SPECIAL_ANNUAL_CONTRIBUTION"]."</td><td class=\"Value\">$SpecialAnnualContribution</td><td class=\"Label\">".$GLOBALS["LANG_FAMILY_MONTHLY_CONTRIBUTION_MODE"]."</td><td class=\"Value\">$MonthlyContribMode</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_NB_MEMBERS"]."*</td><td class=\"Value\">$NbMembers</td><td class=\"Label\">".$GLOBALS["LANG_FAMILY_NB_POWERED_MEMBERS"]."</td><td class=\"Value\">$NbPoweredMembers</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_USER_STATUS_S"]."</td><td class=\"Value\" colspan=\"3\">$SupportMembersList</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_COMMENT"]."</td><td class=\"Value\" colspan=\"3\">$Comment</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_BALANCE"]."</td><td class=\"Value\"><strong class=\"$AnnualContributionBalanceStyle\">$AnnualContributionBalance</strong> / <strong class=\"$BalanceStyle\">$Balance</strong></td><td class=\"Label\">".$GLOBALS["LANG_FAMILY_DESACTIVATION_DATE"]."</td><td class=\"Value\">$DesactivationDate</td>\n</tr>\n";

             if ($FamilyID > 0)
             {
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_CHILDREN"]."</td><td class=\"Value\" colspan=\"3\">";

                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayChildren["ChildID"])) && (count($ArrayChildren["ChildID"]) > 0))
                 {
                     displayStyledTable($TabChildrenCaptions, array_fill(0, count($TabChildrenCaptions), ''), '', $TabChildrenData,
                                        'PurposeParticipantsTable', '', '');
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }
                 echo $Children;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";

                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_PAYMENTS"]."</td><td class=\"Value\" colspan=\"3\">";

                 echo "<table>\n<tr>\n\t<td>";

                 // Display visual indicators
                 $VisualIndicatorsSchoolYear = date('Y-m-d');
                 if (!empty($FamilyRecord['FamilyDesactivationDate']))
                 {
                     // For a closed family, we use her last school year
                     $VisualIndicatorsSchoolYear = $FamilyRecord['FamilyDesactivationDate'];
                 }

                 $sVisualIndicators = " ".generateFamilyVisualIndicators($DbConnection, $FamilyID, TABLE,
                                                                     array(
                                                                           'PbAnnualContributionPayments' => getSchoolYear($VisualIndicatorsSchoolYear)
                                                                          ));

                 echo "<h3>".$GLOBALS['LANG_FAMILY_PAYMENTS_ANNUAL_CONTRIBUTIONS']."$sVisualIndicators</h3>\n";
                 if ((isset($ArrayPayments["PaymentID"])) && (count($ArrayPayments["PaymentID"]) > 0))
                 {
                     displayStyledTable($TabPaymentsCaptions, array_fill(0, count($TabPaymentsCaptions), ''), '', $TabPaymentsData,
                                        'PurposeParticipantsTable', '', '');
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }

                 echo $Payments;
                 echo "</td></tr>\n</table>";

                 // Display payments not linked to bills if exist
                 if ((isset($ArrayPaymentsWithoutBills["PaymentID"])) && (count($ArrayPaymentsWithoutBills["PaymentID"]) > 0))
                 {
                     echo "<table>\n<tr>\n\t<td>";
                     echo "<h3>".$GLOBALS['LANG_FAMILY_PAYMENTS_NOT_LINKED_TO_BILLS']."&nbsp;"
                          .generateStyledPicture($GLOBALS['CONF_WARNING_ICON'])."</h3>\n";

                     displayStyledTable($TabPaymentsWithoutBillsCaptions, array_fill(0, count($TabPaymentsWithoutBillsCaptions), ''),
                                        '', $TabPaymentsWithoutBillsData, 'PurposeParticipantsTable', '', '');
                     echo "</td>\n";
                     echo "</td>\n</tr>\n";
                     echo "</td></tr>\n</table>";
                 }

                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY_BILLS"]."</td><td class=\"Value\" colspan=\"3\">";

                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayBills["BillID"])) && (count($ArrayBills["BillID"]) > 0))
                 {
                     displayStyledTable($TabBillsCaptions, array_fill(0, count($TabBillsCaptions), ''), '', $TabBillsData,
                                        'PurposeParticipantsTable', '', '');
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }

                 echo $Bills;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";

                 // Display discounts
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DISCOUNTS"]."</td><td class=\"Value\" colspan=\"3\">";

                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayDiscounts["DiscountFamilyID"])) && (count($ArrayDiscounts["DiscountFamilyID"]) > 0))
                 {
                     displayStyledTable($TabDiscountsCaptions, array_fill(0, count($TabDiscountsCaptions), ''), '', $TabDiscountsData,
                                        'PurposeParticipantsTable', '', '');
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }

                 echo $Discounts;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";

                 // Display documents approvals
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DOCUMENT_APPROVAL_VALIDATIONS"]."</td><td class=\"Value\" colspan=\"3\">";

                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayDocumentsApprovals["DocumentApprovalID"])) && (count($ArrayDocumentsApprovals["DocumentApprovalID"]) > 0))
                 {
                     displayStyledTable($TabDocumentsApprovalsCaptions, array_fill(0, count($TabDocumentsApprovalsCaptions), ''), '', $TabDocumentsApprovalsData,
                                        'PurposeParticipantsTable', '', '');
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }

                 echo $DocumentsApprovals;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";
             }

             echo "</table>\n";

             insertInputField("hidFamilyID", "hidden", "", "", "", $FamilyID);
             closeStyledFrame();

             switch($cUserAccess)
             {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         if (!$bClosed)
                         {
                             // We display the buttons
                             echo "<table class=\"validation\">\n<tr>\n\t<td>";
                             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                             echo "</td>\n</tr>\n</table>\n";
                         }
                         break;

                     case FCT_ACT_UPDATE_OLD_USER:
                         // We display the buttons to allow old families to update some data
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                         break;

             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a family
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
 * Display the payments of bills of a gien family, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-05-27
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $FamilyID                 String                ID of the family of the child [1..n]
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update families
 */
 function displayDetailsBillsPaymentsHistoryFamilyForm($DbConnection, $FamilyID, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to view payments of the family
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Update mode
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
             // Partial read mode
             $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             if (isExistingFamily($DbConnection, $FamilyID))
             {
                 // We get the details of the family
                 $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $FamilyID);
             }
             else
             {
                 // Error, the family doesn't exist
                 $FamilyID = 0;
             }

             if (!empty($FamilyRecord))
             {
                 // Display the table (frame) where the form will take place
                 openStyledFrame($GLOBALS["LANG_FAMILY_BILLS"]." ".$FamilyRecord['FamilyLastname'], "Frame", "Frame", "DetailsNews");

                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         // Bills of the family
                         $ArrayBills = getBills($DbConnection, NULL, NULL, 'BillForDate DESC', NO_DATES, array("FamilyID" => array($FamilyID)));
                         $TabBillsCaptions = array($GLOBALS["LANG_FAMILY_BILL_FOR_DATE"], $GLOBALS["LANG_FAMILY_BILL_AMOUNT"],
                                                   $GLOBALS["LANG_FAMILY_BILL_PAYMENTS"], $GLOBALS['LANG_DOWNLOAD']);


                         if ((isset($ArrayBills["BillID"])) && (count($ArrayBills["BillID"]) > 0))
                         {
                             $PreviousPaymentID = 0;
                             foreach($ArrayBills["BillID"] as $i => $CurrentID)
                             {
                                 // To view the detail of the bill
                                 $Month = date("m", strtotime($ArrayBills["BillForDate"][$i]));
                                 $Year = date("Y", strtotime($ArrayBills["BillForDate"][$i]));

                                 $BillCaption = $GLOBALS['CONF_PLANNING_MONTHS'][$Month - 1]." $Year";
                                 $TabBillsData[0][] = generateCryptedHyperlink($BillCaption, $CurrentID, 'ViewBill.php',
                                                                               $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                                 // Amount of the current bill
                                 $BillAmountCellContent = $ArrayBills["BillAmount"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];

                                 // Get payments of the bill
                                 $ArrayBillPayments = getFamilyPayments($DbConnection, $FamilyID, array("BillID" => array($CurrentID)),
                                                                        'PaymentDate DESC, PaymentID DESC');

                                 if ($ArrayBills["BillPaid"][$i] == 1)
                                 {
                                     // The bill is Paid
                                     $PaymentsList = generateStyledPicture($GLOBALS['CONF_BILL_PAID_ICON'],
                                                                           $GLOBALS['LANG_FAMILY_BILL_PAID_TIP'], '');
                                 }
                                 else
                                 {
                                     // The bill is not paid : we add a link to add a payement for this bill
                                     $PaymentsList = '';

                                     // Compute the % paid of the bill
                                     if ((isset($ArrayBillPayments["PaymentBillPartAmount"])) && (count($ArrayBillPayments["PaymentBillPartAmount"]) > 0))
                                     {
                                         // Bill amount
                                         $TmpCurrBillAmount = $ArrayBills["BillAmount"][$i];
                                         if (isFirstBillOfFamily($DbConnection, $CurrentID))
                                         {
                                             // It's the first bill in the system for this family : the amount of
                                             // this bill is the amount + previous amount
                                             $TmpCurrBillAmount += $ArrayBills["BillPreviousBalance"][$i];
                                         }

                                         // Compute the progress bar
                                         $sProgressBar = generateProgressVisualIndicator(NULL, $TmpCurrBillAmount,
                                                                                         $ArrayBills["BillPaidAmount"][$i],
                                                                                         max(0, $TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i]),
                                                                                         $GLOBALS['LANG_FAMILY_BILL_NOT_PAID_AMOUNT_TIP']." "
                                                                                         .($TmpCurrBillAmount - $ArrayBills["BillPaidAmount"][$i])
                                                                                         ." ".$GLOBALS['CONF_PAYMENTS_UNIT']);

                                         $BillAmountCellContent .= generateBR(2)."$sProgressBar";
                                     }
                                 }

                                 $TabBillsData[1][] = $BillAmountCellContent;

                                 if ((isset($ArrayBillPayments["PaymentID"])) && (count($ArrayBillPayments["PaymentID"]) > 0))
                                 {
                                     foreach($ArrayBillPayments["PaymentID"] as $p => $CurrentPaymentID)
                                     {
                                         $PaymentsList .= generateBR(1);
                                         $PaymentsList .= generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                        strtotime($ArrayBillPayments["PaymentReceiptDate"][$p])),
                                                                                   $CurrentPaymentID, 'UpdatePayment.php',
                                                                                   $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                                         // We display on the last use of the payment the remained amount
                                         $fNotUsedAmount = round($ArrayBillPayments["PaymentAmount"][$p]
                                                             - $ArrayBillPayments["PaymentUsedAmount"][$p], 2);
                                         if (($fNotUsedAmount > 0) && ($CurrentPaymentID != $PreviousPaymentID))
                                         {
                                             // Payment not totaly used
                                             $PaymentsList .= " <span class=\"PaymentNotTotalyUsed\" title=\""
                                                               .$GLOBALS['LANG_FAMILY_PAYMENT_NOT_TOTALY_USED']." $fNotUsedAmount "
                                                               .$GLOBALS['CONF_PAYMENTS_UNIT']."\">(".$ArrayBillPayments["PaymentAmount"][$p].' '
                                                               .$GLOBALS['CONF_PAYMENTS_UNIT'].")</span>";
                                         }
                                         else
                                         {
                                             // Payment totaly used
                                             $PaymentsList .= ' ('.$ArrayBillPayments["PaymentAmount"][$p].' '
                                                             .$GLOBALS['CONF_PAYMENTS_UNIT'].')';
                                         }

                                         // Save the current payment ID as the previous payment ID for the next loop
                                         $PreviousPaymentID = $CurrentPaymentID;
                                     }
                                 }

                                 $TabBillsData[2][] = $PaymentsList;

                                 // To download the bill
                                 $TabBillsData[3][] = generateCryptedHyperlink($GLOBALS['LANG_DOWNLOAD'], $CurrentID, 'DownloadBill.php',
                                                                               $GLOBALS["LANG_DOWNLOAD"], '', '_blank');
                             }
                         }
                         break;
                 }

                 echo "<table id=\"FamilyDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Value\">";
                 if ((isset($ArrayBills["BillID"])) && (count($ArrayBills["BillID"]) > 0))
                 {
                     displayStyledTable($TabBillsCaptions, array_fill(0, count($TabBillsCaptions), ''), '', $TabBillsData,
                                        'PurposeParticipantsTable', '', '');

                 }

                 echo "</td>\n<tr>\n</table>\n";

                 closeStyledFrame();
             }
         }
         else
         {
             // The supporter isn't allowed to view details of payments of a family
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
 * Display the form to search a family in the current web  page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.7
 *     - 2012-10-01 : allow to search on the "family lastname" criteria
 *     - 2013-04-10 : don't allow to order by "Nb children"
 *     - 2013-10-09 : display an icon when the family don't use the default monthly contribution mode and
 *                    allow to search on the monthly contribution mode criteria and town ID criteria
 *     - 2014-01-31 : display an icon for families with a not totally used payment
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2015-01-19 : round to 2 digits the not used amount of payments
 *     - 2016-05-10 : taken into account new values of $CONF_MONTHLY_CONTRIBUTION_MODES_ICONS (about coefficients
 *                    of families)
 *
 * @since 2012-01-20
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $TabParams            Array of Strings      search criterion used to find some families
 * @param $ProcessFormPage      String                URL of the page which will process the form allowing to find and to sort
 *                                                    the table of the families found
 * @param $Page                 Integer               Number of the Page to display [1..n]
 * @param $SortFct              String                Javascript function used to sort the table
 * @param $OrderBy              Integer               n° Criteria used to sort the families. If < 0, DESC is used, otherwise ASC is used
 * @param $DetailsPage          String                URL of the page to display details about a family. This string can be empty
 */
 function displaySearchFamilyForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '')
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Open a form
         openForm("FormSearchFamily", "post", "$ProcessFormPage", "", "");

         // Display the table (frame) where the form will take place
         openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

         // <<< School year SELECTFIELD >>>
         // Create the school years list
         $ArraySchoolYear = array(0 => '');
         foreach($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'] as $Year => $Date)
         {
             $Value = date('Y', strtotime($Date)).'-'.$Year;
             $ArraySchoolYear[$Year] = $Value;
         }

         if ((isset($TabParams['SchoolYear'])) && (count($TabParams['SchoolYear']) > 0))
         {
             $SelectedItem = $TabParams['SchoolYear'][0];
         }
         else
         {
             // Default value : no item selected
             $SelectedItem = 0;
         }

         $SchoolYear = generateSelectField("lSchoolYear", array_keys($ArraySchoolYear), array_values($ArraySchoolYear),
                                           zeroFormatValue(existedPOSTFieldValue("lSchoolYear",
                                                                                 existedGETFieldValue("lSchoolYear", $SelectedItem))));

         // Family lastname input text
         $sLastname = generateInputField("sLastname", "text", "100", "13", $GLOBALS["LANG_FAMILY_LASTNAME_TIP"],
                                         stripslashes(strip_tags(existedPOSTFieldValue("sLastname",
                                                                                       stripslashes(existedGETFieldValue("sLastname", ""))))));

         // <<< Monthly contribution mode SELECTFIELD >>>
         // Create the contributions modes list
         $ArrayMonthlyContribModes = array(-1 => '');
         foreach($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES'] as $m => $Mode)
         {
             $ArrayMonthlyContribModes[$m] = $Mode;
         }

         if ((isset($TabParams['FamilyMonthlyContributionMode'])) && (count($TabParams['FamilyMonthlyContributionMode']) > 0))
         {
             $SelectedItem = $TabParams['FamilyMonthlyContributionMode'][0];
         }
         else
         {
             // Default value : no item selected
             $SelectedItem = -1;
         }

         $MonthlyContribMode = generateSelectField("lMonthlyContributionMode", array_keys($ArrayMonthlyContribModes),
                                                   array_values($ArrayMonthlyContribModes),
                                                   zeroFormatValue(existedPOSTFieldValue("lMonthlyContributionMode",
                                                                                         existedGETFieldValue("lMonthlyContributionMode",
                                                                                                              $SelectedItem))));

         // <<<< TownID SELECTFIELD >>>>
         $Towns = '&nbsp;';
         $ArrayTowns = array();
         $DbResult = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
         if (!DB::isError($DbResult))
         {
             if ($DbResult->numRows() > 0)
             {
                 $ArrayTowns = array(
                                     "TownID" => array(0),
                                     "TownName" => array('')
                                    );

                 while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                 {
                     $ArrayTowns["TownID"][] = $Record["TownID"];
                     $ArrayTowns["TownName"][] = $Record["TownName"]." (".$Record["TownCode"].")";
                 }

                 if ((isset($TabParams['TownID'])) && (count($TabParams['TownID']) > 0))
                 {
                     $SelectedItem = $TabParams['TownID'][0];
                 }
                 else
                 {
                     $SelectedItem = 0;
                 }

                 $Towns = generateSelectField("lTownID", $ArrayTowns['TownID'], $ArrayTowns['TownName'],
                                              zeroFormatValue(existedPOSTFieldValue("lTownID",
                                                                                    existedGETFieldValue("lTownID", $SelectedItem))));
             }
         }

         // Pb annual contribution payments checkbox
         $Checked = FALSE;
         if (existedPOSTFieldValue("chkPbAnnualContributionPayments", existedGETFieldValue("chkPbAnnualContributionPayments", "")) == "pbpayments")
         {
             $Checked = TRUE;
         }
         $PbAnnualContributionPayments = generateInputField("chkPbAnnualContributionPayments", "checkbox", "", "",
                                                            $GLOBALS["LANG_PB_ANNUAL_CONTRIBUTION_PAYMENTS_TIP"], "pbpayments",
                                                            FALSE, $Checked)." ".$GLOBALS["LANG_YES"];

         // Pb payments checkbox
         $Checked = FALSE;
         if (existedPOSTFieldValue("chkPbPayments", existedGETFieldValue("chkPbPayments", "")) == "pbpayments")
         {
             $Checked = TRUE;
         }
         $PbPayments = generateInputField("chkPbPayments", "checkbox", "", "", $GLOBALS["LANG_PB_PAYMENTS_TIP"], "pbpayments",
                                          FALSE, $Checked)." ".$GLOBALS["LANG_YES"];

         // Display the form
         echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SCHOOL_YEAR"]."</td><td class=\"Value\">$SchoolYear</td><td class=\"Label\">".$GLOBALS["LANG_PB_PAYMENTS"]."</td><td class=\"Value\">$PbPayments</td>\n</tr>\n";
         echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_LASTNAME']."</td><td class=\"Value\">$sLastname</td><td class=\"Label\">".$GLOBALS["LANG_PB_ANNUAL_CONTRIBUTION_PAYMENTS"]."</td><td class=\"Value\">$PbAnnualContributionPayments</td>\n</tr>\n";
         echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_FAMILY_MONTHLY_CONTRIBUTION_MODE']."</td><td class=\"Value\">$MonthlyContribMode</td><td class=\"Label\">".$GLOBALS['LANG_TOWN']."</td><td class=\"Value\">$Towns</td>\n</tr>\n";
         echo "</table>\n";

         // Display the hidden fields
         insertInputField("hidOrderByField", "hidden", "", "", "", $OrderBy);
         insertInputField("hidOnPrint", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnPrint", existedGETFieldValue("hidOnPrint", ""))));
         insertInputField("hidOnExport", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnExport", existedGETFieldValue("hidOnExport", ""))));
         insertInputField("hidExportFilename", "hidden", "", "", "", existedPOSTFieldValue("hidExportFilename", existedGETFieldValue("hidExportFilename", "")));
         closeStyledFrame();

         echo "<table class=\"validation\">\n<tr>\n\t<td>";
         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
         echo "</td>\n</tr>\n</table>\n";

         closeForm();

         // The supporter has executed a search
         $NbTabParams = count($TabParams);
         if ($NbTabParams > 0)
         {
             displayBR(2);

             $ArrayCaptions = array($GLOBALS["LANG_FAMILY_LASTNAME"], $GLOBALS["LANG_NB_CHILDREN"], $GLOBALS["LANG_TOWN"],
                                    $GLOBALS["LANG_E_MAIL"], $GLOBALS["LANG_FAMILY_BALANCE"]);
             $ArraySorts = array("FamilyLastname", "NbChildren", "TownName", "FamilyMainEmail", "FamilyBalance");

             // Order by instruction
             if ((abs($OrderBy) <= count($ArraySorts)) && ($OrderBy != 0))
             {
                 $StrOrderBy = $ArraySorts[abs($OrderBy) - 1];
                 if ($OrderBy < 0)
                 {
                     $StrOrderBy .= " DESC";
                 }
             }
             else
             {
                 $StrOrderBy = "FamilyLastname ASC";
             }

             // We launch the search
             $NbRecords = getNbdbSearchFamily($DbConnection, $TabParams);
             if ($NbRecords > 0)
             {
                 // To get only families of the page
                 $ArrayRecords = dbSearchFamily($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                 // To get families of all pages (to compute nb members)
                 $ArrayTotalRecords = dbSearchFamily($DbConnection, $TabParams, $StrOrderBy, 1, 0);

                 if ((array_key_exists("SchoolYear", $TabParams)) && (!empty($TabParams["SchoolYear"])))
                 {
                     // Get select
                     $SelectedSchoolYear = $TabParams["SchoolYear"][0];
                 }
                 else
                 {
                     // Get Current school year
                     $SelectedSchoolYear = getSchoolYear(date('Y-m-d'));
                 }

                 // We get the not totally used payements
                 $ArrayNotUsedPayments = getPaymentsNotUsed($DbConnection, NULL, array(), 'FamilyID');

                 /*openParagraph('toolbar');
                 displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                 echo "&nbsp;&nbsp;";
                 displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                 echo "&nbsp;&nbsp;";
                 displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                 closeParagraph(); */

                 // There are some families found
                 $NbMembers = array_sum($ArrayTotalRecords['FamilyNbMembers']);
                 $NbPoweredMembers = array_sum($ArrayTotalRecords['FamilyNbPoweredMembers']);

                 foreach($ArrayRecords["FamilyID"] as $i => $CurrentValue)
                 {
                     if ($DetailsPage == '')
                     {
                         // We display the lastname
                         $sLastname = $ArrayRecords["FamilyLastname"][$i];
                     }
                     else
                     {
                         // We display the reference with a hyperlink
                         $sLastname = generateAowIDHyperlink($ArrayRecords["FamilyLastname"][$i], $ArrayRecords["FamilyID"][$i],
                                                             $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                             "", "_blank");
                     }

                     $ArrayData[0][] = $sLastname;

                     $ArrayData[1][] = $ArrayRecords["NbChildren"][$i];
                     $ArrayData[2][] = $ArrayRecords["TownName"][$i];

                     $Email = $ArrayRecords["FamilyMainEmail"][$i];
                     if (!empty($ArrayRecords["FamilySecondEmail"][$i]))
                     {
                         $Email .= " / ".$ArrayRecords["FamilySecondEmail"][$i];
                     }
                     $ArrayData[3][] = $Email;

                     $Balance = $ArrayRecords["FamilyBalance"][$i];
                     $BalanceStyle = '';
                     if ($Balance < 0)
                     {
                         // Display a warning
                         $BalanceStyle = 'NegativeBalance';
                     }

                     // Current balance of the family and visual indicators
                     $sPaymentNotUsedIndicator = "";
                     if (isset($ArrayNotUsedPayments[$CurrentValue]))
                     {
                         // The family has at least one not totally used payment
                         // Compute the not used amount
                         $fNotUsedAmount = round(array_sum($ArrayNotUsedPayments[$CurrentValue]['PaymentAmount'])
                                           - array_sum($ArrayNotUsedPayments[$CurrentValue]['PaymentUsedAmount']), 2);

                         $sPaymentNotUsedIndicator = generateStyledPicture($GLOBALS['CONF_PAYMENT_NOT_USED_ICON'],
                                                                           $fNotUsedAmount.' '.$GLOBALS['CONF_PAYMENTS_UNIT'], "");
                     }

                     $ArrayData[4][] = generateStyledText("$Balance ".$GLOBALS['CONF_PAYMENTS_UNIT'], $BalanceStyle)
                                       .generateFamilyVisualIndicators($DbConnection, $CurrentValue, TABLE,
                                                                       array(
                                                                             'PbAnnualContributionPayments' => $SelectedSchoolYear,
                                                                             'FamilyMonthlyContributionMode' => $SelectedSchoolYear
                                                                            )).$sPaymentNotUsedIndicator;
                 }

                 // Display the table which contains the families found
                 displayStyledTable($ArrayCaptions, array("1", "", "3", "4", "5"), $SortFct, $ArrayData, '', '', '', '',
                                    array(), $OrderBy, array('textLeft', '', '', 'textLeft', ''), 'FamiliesList');

                 // Display the previous and next links
                 $NoPage = 0;
                 if ($Page <= 1)
                 {
                     $PreviousLink = '';
                 }
                 else
                 {
                     $NoPage = $Page - 1;

                     // We get the parameters of the GET form or the POST form
                     if (count($_POST) == 0)
                     {
                         // GET form
                         if (count($_GET) == 0)
                         {
                             // No form submitted
                             $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             if (isset($TabParams['SchoolYear']))
                             {
                                 $CurrentValue = $TabParams['SchoolYear'];
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }
                                 $PreviousLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                         else
                         {
                             // GET form
                             $PreviousLink = "$ProcessFormPage?";
                             foreach($_GET as $i => $CurrentValue)
                             {
                                 if ($i == "Pg")
                                 {
                                     $CurrentValue = $NoPage;
                                 }
                                 $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }
                     else
                     {
                         // POST form
                         $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                         foreach($_POST as $i => $CurrentValue)
                         {
                             if (is_array($CurrentValue))
                             {
                                 // The value is an array
                                 $CurrentValue = implode("_", $CurrentValue);
                             }

                             $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                         }
                     }
                 }

                 if ($Page < ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]))
                 {
                     $NoPage = $Page + 1;

                     // We get the parameters of the GET form or the POST form
                     if (count($_POST) == 0)
                     {
                         if (count($_GET) == 0)
                         {
                             // No form submitted
                             $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             if (isset($TabParams['SchoolYear']))
                             {
                                 $CurrentValue = $TabParams['SchoolYear'];
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }
                                 $NextLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                         else
                         {
                             // GET form
                             $NextLink = "$ProcessFormPage?";
                             foreach($_GET as $i => $CurrentValue)
                             {
                                 if ($i == "Pg")
                                 {
                                     $CurrentValue = $NoPage;
                                 }
                                 $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }
                     else
                     {
                         // POST form
                         $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                         foreach($_POST as $i => $CurrentValue)
                         {
                             if (is_array($CurrentValue))
                             {
                                 // The value is an array
                                 $CurrentValue = implode("_", $CurrentValue);
                             }

                             $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                         }
                     }
                 }
                 else
                 {
                     $NextLink = '';
                 }

                 displayPreviousNext("&nbsp;".$GLOBALS["LANG_PREVIOUS"], $PreviousLink, $GLOBALS["LANG_NEXT"]."&nbsp;", $NextLink,
                                     '', $Page, ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]));

                 openParagraph('nbentriesfound');
                 echo $GLOBALS['LANG_NB_RECORDS_FOUND'].$NbRecords.generateBR(1);
                 echo $GLOBALS['LANG_FAMILY_NB_MEMBERS']." : <em>$NbMembers</em> / ".$GLOBALS['LANG_FAMILY_NB_POWERED_MEMBERS']
                      ." : <em>$NbPoweredMembers</em>".generateBR(1);
                 echo "<strong>".$GLOBALS['LANG_TOTAL']." : ".($NbMembers + $NbPoweredMembers)."</strong>";
                 closeParagraph();

                 // Display the legends of the icons
                 displayBR(1);

                 $ArrayLegendsOfVisualIndicators = array(
                                                         array($GLOBALS['CONF_ANNUAL_CONTRIBUTION_NOT_PAID_ICON'], $GLOBALS["LANG_PB_ANNUAL_CONTRIBUTION_PAYMENTS"].'.'),
                                                         array($GLOBALS['CONF_PAYMENT_NOT_USED_ICON'], $GLOBALS['LANG_PAYMENT_NOT_USED_TIP'])
                                                        );

                 // Take into account monthly contribution modes except the first (default mode)
                 $iNbModes = count($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES']);
                 for($i = 1; $i < $iNbModes; $i++)
                 {
                     // Get icon and label if exist
                     if (isset($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES_ICONS'][$i]))
                     {
                         $ArrayLegendsOfVisualIndicators[] = array($GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES_ICONS'][$i],
                                                                   $GLOBALS['CONF_MONTHLY_CONTRIBUTION_MODES'][$i]);
                     }
                 }

                 echo generateLegendsOfVisualIndicators($ArrayLegendsOfVisualIndicators, ICON);
             }
             else
             {
                 // No family found
                 openParagraph('nbentriesfound');
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();
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
 * Display the form to submit a new child or update a child, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.5
 *     - 2012-07-12 : only buttons for "create" and "update" access patch the pb when then
 *                    desactivation date is empty
 *     - 2013-06-21 : taken into account the new structure of the CONF_CLASSROOMS variable
 *                    (includes school year)
 *     - 2014-03-12 : taken into account FCT_ACT_PARTIAL_READ_ONLY access right
 *     - 2015-06-18 : "Firstname" and "without pork" fields can be updated with
 *                    FCT_ACT_PARTIAL_READ_ONLY access right
 *     - 2016-06-20 : remove htmlspecialchars() function
 *     - 2017-09-21 : taken into account ChildEmail field (to contact child after
 *                    he left school) and checkbox for meal without pork becomes
 *                    a dropdown list about meal types
 *
 * @since 2012-01-24
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $ChildID                  String                ID of the child to display [0..n]
 * @param $FamilyID                 String                ID of the family of the child [1..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update children
 */
 function displayDetailsChildForm($DbConnection, $ChildID, $FamilyID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a child
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($ChildID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
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
                 // Partial read mode
                 $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
             }
             elseif ((isset($AccessRules[FCT_ACT_UPDATE_OLD_USER])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE_OLD_USER])))
             {
                 // Update old user mode (for old families)
                 $cUserAccess = FCT_ACT_UPDATE_OLD_USER;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY,
                                          FCT_ACT_UPDATE_OLD_USER)))
         {
             // Open a form
             openForm("FormDetailsChild", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "VerificationChild('".$GLOBALS["LANG_ERROR_JS_CHILD_FIRSTNAME"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_CHILD"], "Frame", "Frame", "DetailsNews");

             // <<< Child ID >>>
             if ($ChildID == 0)
             {
                 $Reference = "&nbsp;";
                 $ChildRecord = array(
                                      "ChildFirstname" => '',
                                      "ChildSchoolDate" => date('Y-m-d'),
                                      "ChildGrade" => 0,
                                      "ChildClass" => 0,
                                      "ChildWithoutPork" => 0,
                                      "ChildDesactivationDate" => NULL,
                                      "ChildEmail" => '',
                                      "FamilyID" => $FamilyID
                                     );

                 $bClosed = FALSE;
             }
             else
             {
                 if (isExistingChild($DbConnection, $ChildID))
                 {
                     // We get the details of the child
                     $ChildRecord = getTableRecordInfos($DbConnection, "Children", $ChildID);
                     $Reference = $ChildID;

                     // We check if the child is opened or close
                     $bClosed = isChildClosed($DbConnection, $ChildRecord["ChildID"]);
                 }
                 else
                 {
                     // Error, the child doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             $CreationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ChildRecord["ChildSchoolDate"]));

             // Get the lastname of the child
             $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $ChildRecord["FamilyID"]);
             $Lastname = "&nbsp;";
             if (!empty($FamilyRecord))
             {
                 $Lastname = $FamilyRecord['FamilyLastname'];
             }

             // <<< Child firstname INPUTFIELD >>>
             if ($bClosed)
             {
                 $Firstname = stripslashes($ChildRecord["ChildFirstname"]);
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                          $Firstname = stripslashes($ChildRecord["ChildFirstname"]);
                          break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Firstname = generateInputField("sFirstname", "text", "50", "25", $GLOBALS["LANG_CHILD_FIRSTNAME_TIP"],
                                                         $ChildRecord["ChildFirstname"]);
                         break;
                 }
             }

             // <<< Child grade SELECTFIELD >>>
             if ($bClosed)
             {
                 $Grade = $GLOBALS["CONF_GRADES"][$ChildRecord["ChildGrade"]];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Grade = $GLOBALS["CONF_GRADES"][$ChildRecord["ChildGrade"]];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Grade = generateSelectField("lGrade", array_keys($GLOBALS["CONF_GRADES"]), $GLOBALS["CONF_GRADES"],
                                                      $ChildRecord["ChildGrade"], "");
                         break;
                 }
             }

             // <<< Child class SELECTFIELD >>>
             if ($bClosed)
             {
                 $ChildSchoolYear = getSchoolYear($ChildRecord['ChildDesactivationDate']);
                 $Class = $GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ChildRecord["ChildClass"]];
             }
             else
             {
                 $ChildSchoolYear = getSchoolYear(date('Y-m-d'));
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Class = $GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear][$ChildRecord["ChildClass"]];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Class = generateSelectField("lClass", array_keys($GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear]),
                                                      $GLOBALS["CONF_CLASSROOMS"][$ChildSchoolYear], $ChildRecord["ChildClass"], "");
                         break;
                 }
             }

             // <<< Meal type SELECTFIELD >>>
             if ($bClosed)
             {
                 $MealType = $GLOBALS["CONF_MEAL_TYPES"][$ChildRecord["ChildWithoutPork"]];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $MealType = $GLOBALS["CONF_MEAL_TYPES"][$ChildRecord["ChildWithoutPork"]];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $MealType = generateSelectField("lMealType", array_keys($GLOBALS["CONF_MEAL_TYPES"]), $GLOBALS["CONF_MEAL_TYPES"],
                                                         $ChildRecord["ChildWithoutPork"], "");
                         break;
                 }
             }

             // Desactivation date
             if ($bClosed)
             {
                 $DesactivationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ChildRecord["ChildDesactivationDate"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         if (empty($ChildRecord["ChildDesactivationDate"]))
                         {
                             $DesactivationDate = '';
                         }
                         else
                         {
                             $DesactivationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                       strtotime($ChildRecord["ChildDesactivationDate"]));
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         if (empty($ChildRecord["ChildDesactivationDate"]))
                         {
                             $DesactivationDateValue = '';
                         }
                         else
                         {
                             $DesactivationDateValue = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                            strtotime($ChildRecord["ChildDesactivationDate"]));
                         }

                         $DesactivationDate = generateInputField("desactivationDate", "text", "10", "10",
                                                                 $GLOBALS["LANG_CHILD_DESACTIVATION_DATE_TIP"],
                                                                 $DesactivationDateValue, TRUE);

                         // Insert the javascript to use the calendar component
                         $DesactivationDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t DesactivationDateCalendar = new dynCalendar('DesactivationDateCalendar', 'calendarCallbackDesactivationDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                         break;
                 }
             }

             // <<< Child e-mail INPUTFIELD >>>
             if ($bClosed)
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_UPDATE_OLD_USER:
                         // The old family can update the child's e-mail address
                         $Email = generateInputField("sEmail", "text", "100", "70", $GLOBALS["LANG_CHILD_EMAIL_TIP"],
                                                     $ChildRecord["ChildEmail"]);
                         break;

                     default:
                         $Email = stripslashes($ChildRecord["ChildEmail"]);
                         break;
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                     case FCT_ACT_READ_ONLY:
                          $Email = stripslashes($ChildRecord["ChildEmail"]);
                          break;


                     case FCT_ACT_PARTIAL_READ_ONLY:
                     case FCT_ACT_UPDATE_OLD_USER:
                         $Email = generateInputField("sEmail", "text", "100", "70", $GLOBALS["LANG_CHILD_EMAIL_TIP"],
                                                     $ChildRecord["ChildEmail"]);
                         break;
                 }
             }


             if ($ChildID > 0)
             {
                 // We get the history of the child
                 $ArrayHistory = getHistoLevelsChild($DbConnection, $ChildID, 'HistoLevelChildYear DESC');

                 // We define the captions of the history table
                 $History = '&nbsp;';
                 $TabHistoryCaptions = array($GLOBALS["LANG_YEAR"], $GLOBALS["LANG_CHILD_GRADE"].' / '
                                             .$GLOBALS["LANG_CHILD_CLASS"], $GLOBALS["LANG_MEAL_WITHOUT_PORK"]." / "
                                             .$GLOBALS["LANG_MEAL_PACKED_LUNCH"]);

                 // We transform the result to be displayed
                 if ((isset($ArrayHistory["HistoLevelChildID"])) && (count($ArrayHistory["HistoLevelChildID"]) > 0))
                 {
                     foreach($ArrayHistory["HistoLevelChildID"] as $i => $CurrentID)
                     {
                         $TabHistoryData[0][] = $ArrayHistory["HistoLevelChildYear"][$i];

                         $TabHistoryData[1][] = $GLOBALS["CONF_GRADES"][$ArrayHistory["HistoLevelChildGrade"][$i]].' / '
                                                .$GLOBALS["CONF_CLASSROOMS"][$ArrayHistory["HistoLevelChildYear"][$i]][$ArrayHistory["HistoLevelChildClass"][$i]];

                         $TabHistoryData[2][] = $GLOBALS["CONF_MEAL_TYPES"][$ArrayHistory["HistoLevelChildWithoutPork"][$i]];
                     }
                 }

                 // We get the suspensions of the child
                 $ArraySuspensions = getSuspensionsChild($DbConnection, $ChildID, FALSE, 'SuspensionStartDate DESC');
                 $Suspensions = '&nbsp;';
                 $TabSuspensionsCaptions = array($GLOBALS["LANG_SUSPENSION_START_DATE"], $GLOBALS["LANG_SUSPENSION_END_DATE"],
                                                 $GLOBALS["LANG_SUSPENSION_REASON"]);

                 if ($bClosed)
                 {
                     if ((isset($ArraySuspensions["SuspensionID"])) && (count($ArraySuspensions["SuspensionID"]) > 0))
                     {
                         foreach($ArraySuspensions["SuspensionID"] as $i => $CurrentID)
                         {
                             $TabSuspensionsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                      strtotime($ArraySuspensions["SuspensionStartDate"][$i])),
                                                                                 $CurrentID, 'UpdateChildSuspension.php',
                                                                                 $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                 '_blank');
                             $EndDate = '-';
                             if (!empty($ArraySuspensions["SuspensionEndDate"][$i]))
                             {
                                 $EndDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                 strtotime($ArraySuspensions["SuspensionEndDate"][$i]));
                             }
                             $TabSuspensionsData[1][] = $EndDate;

                             $Reason = '-';
                             if (!empty($ArraySuspensions["SuspensionReason"][$i]))
                             {
                                 $Reason = $ArraySuspensions["SuspensionReason"][$i];
                             }
                             $TabSuspensionsData[2][] = $Reason;
                         }
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArraySuspensions["SuspensionID"])) && (count($ArraySuspensions["SuspensionID"]) > 0))
                             {
                                 foreach($ArraySuspensions["SuspensionID"] as $i => $CurrentID)
                                 {
                                     $TabSuspensionsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                              strtotime($ArraySuspensions["SuspensionStartDate"][$i])),
                                                                                         $CurrentID, 'UpdateChildSuspension.php',
                                                                                         $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                         '_blank');
                                     $EndDate = '-';
                                     if (!empty($ArraySuspensions["SuspensionEndDate"][$i]))
                                     {
                                         $EndDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                         strtotime($ArraySuspensions["SuspensionEndDate"][$i]));
                                     }
                                     $TabSuspensionsData[1][] = $EndDate;

                                     $Reason = '-';
                                     if (!empty($ArraySuspensions["SuspensionReason"][$i]))
                                     {
                                         $Reason = $ArraySuspensions["SuspensionReason"][$i];
                                     }
                                     $TabSuspensionsData[2][] = $Reason;
                                 }
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             if ((isset($ArraySuspensions["SuspensionID"])) && (count($ArraySuspensions["SuspensionID"]) > 0))
                             {
                                 foreach($ArraySuspensions["SuspensionID"] as $i => $CurrentID)
                                 {
                                     $TabSuspensionsData[0][] = generateCryptedHyperlink(date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                                                              strtotime($ArraySuspensions["SuspensionStartDate"][$i])),
                                                                                         $CurrentID, 'UpdateChildSuspension.php',
                                                                                         $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                         '_blank');
                                     $EndDate = '-';
                                     if (!empty($ArraySuspensions["SuspensionEndDate"][$i]))
                                     {
                                         $EndDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                         strtotime($ArraySuspensions["SuspensionEndDate"][$i]));
                                     }
                                     $TabSuspensionsData[1][] = $EndDate;

                                     $Reason = '-';
                                     if (!empty($ArraySuspensions["SuspensionReason"][$i]))
                                     {
                                         $Reason = $ArraySuspensions["SuspensionReason"][$i];
                                     }
                                     $TabSuspensionsData[2][] = $Reason;
                                 }
                             }

                             // Check if there is an opened suspension for the child
                             $ArrayOpenedSuspensions = getSuspensionsChild($DbConnection, $ChildID, TRUE, 'SuspensionStartDate DESC');
                             if ((isset($ArrayOpenedSuspensions["SuspensionID"])) && (empty($ArrayOpenedSuspensions["SuspensionID"])))
                             {
                                 // No opened suspension, we can display the button to add a suspension for the child
                                 $Suspensions = "<table><tr><td class=\"Action\">";
                                 $Suspensions .= generateStyledLinkText($GLOBALS["LANG_CHILD_ADD_SUSPENSION"],
                                                                        "AddChildSuspension.php?Cr=".md5($ChildID)."&amp;Id=$ChildID",
                                                                        '', $GLOBALS["LANG_CHILD_ADD_SUSPENSION_TIP"], '_blank');
                                 $Suspensions .= "</td></tr></table>";
                             }
                             break;
                     }
                 }
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CHILD_FIRSTNAME"]."*</td><td class=\"Value\">$Firstname</td><td class=\"Label\">".$GLOBALS["LANG_FAMILY_LASTNAME"]."</td><td class=\"Value\">$Lastname</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CREATION_DATE"]."</td><td class=\"Value\">$CreationDate</td><td class=\"Label\">".$GLOBALS["LANG_CHILD_GRADE"]."</td><td class=\"Value\">$Grade</td><td class=\"Label\">".$GLOBALS["LANG_CHILD_CLASS"]."</td><td class=\"Value\">$Class</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MEAL_WITHOUT_PORK"]." / ".$GLOBALS["LANG_MEAL_PACKED_LUNCH"]."</td><td class=\"Value\">$MealType</td><td class=\"Label\">".$GLOBALS["LANG_CHILD_DESACTIVATION_DATE"]."</td><td class=\"Value\">$DesactivationDate</td><td class=\"Label\">&nbsp;</td><td class=\"Value\">&nbsp;</td>\n</tr>\n";

             if ($ChildID > 0)
             {
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CHILD_EMAIL"]."</td><td class=\"Value\" colspan=\"5\">$Email</td></tr>\n";
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"6\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CHILD_HISTORY"]."</td><td class=\"Value\" colspan=\"5\">";

                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayHistory["HistoLevelChildID"])) && (count($ArrayHistory["HistoLevelChildID"]) > 0))
                 {
                     displayStyledTable($TabHistoryCaptions, array_fill(0, count($TabHistoryCaptions), ''), '', $TabHistoryData,
                                        'PurposeParticipantsTable', '', '');
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }
                 echo $History;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";

                 echo "<tr>\n\t<td class=\"Label\" colspan=\"6\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CHILD_SUSPENSIONS"]."</td><td class=\"Value\" colspan=\"5\">";

                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArraySuspensions["SuspensionID"])) && (count($ArraySuspensions["SuspensionID"]) > 0))
                 {
                     displayStyledTable($TabSuspensionsCaptions, array_fill(0, count($TabSuspensionsCaptions), ''), '',
                                        $TabSuspensionsData, 'PurposeParticipantsTable', '', '');
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }
                 echo $Suspensions;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";
             }

             echo "</table>\n";

             insertInputField("hidFamilyID", "hidden", "", "", "", $ChildRecord["FamilyID"]);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (!$bClosed)
                     {
                         // We display the buttons
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                     }
                     break;

                 case FCT_ACT_UPDATE_OLD_USER:
                     // We display the buttons to allow old families to update some data
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a child
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
 * Display the form to submit a new suspension or update a suspension for a child, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-02-20
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $SuspensionID             String                ID of the suspension to display [0..n]
 * @param $ChildID                  String                ID of the Child of the suspension [1..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update suspensions for a child
 */
 function displayDetailsChildSuspensionForm($DbConnection, $SuspensionID, $ChildID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a suspension for a child
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($ChildID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsSuspension", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "VerificationSuspension('".$GLOBALS["LANG_ERROR_JS_CHILD_SUSPENSION"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SUSPENSION"], "Frame", "Frame", "DetailsNews");

             // <<< Suspension ID >>>
             if ($SuspensionID == 0)
             {
                 $Reference = "&nbsp;";
                 $SuspensionRecord = array(
                                           "SuspensionStartDate" => date('Y-m-d'),
                                           "SuspensionEndDate" => NULL,
                                           "SuspensionReason" => '',
                                           "ChildID" => $ChildID
                                          );

                 $bClosed = FALSE;
             }
             else
             {
                 if (isExistingSuspension($DbConnection, $SuspensionID))
                 {
                     // We get the details of the suspension of the child
                     $SuspensionRecord = getTableRecordInfos($DbConnection, "Suspensions", $SuspensionID);
                     $Reference = $SuspensionID;

                     // We check if the child is opened or close
                     $bClosed = isChildClosed($DbConnection, $SuspensionRecord["ChildID"]);
                     if (!empty($SuspensionRecord["SuspensionEndDate"]))
                     {
                         $bClosed = TRUE;
                     }
                 }
                 else
                 {
                     // Error, the child suspension doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             // Get the firstname of the child
             $ChildRecord = getTableRecordInfos($DbConnection, "Children", $SuspensionRecord["ChildID"]);
             $Firstname = "&nbsp;";
             if (!empty($ChildRecord))
             {
                 $Firstname = $ChildRecord['ChildFirstname'];
             }

             // Get the lastname of the child
             $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $ChildRecord["FamilyID"]);
             $Lastname = "&nbsp;";
             if (!empty($FamilyRecord))
             {
                 $Lastname = $FamilyRecord['FamilyLastname'];
             }

             // Start date
             if ($bClosed)
             {
                 $StartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($SuspensionRecord["SuspensionStartDate"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $StartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($SuspensionRecord["SuspensionStartDate"]));
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $StartDateValue = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                strtotime($SuspensionRecord["SuspensionStartDate"]));

                         $StartDate = generateInputField("startDate", "text", "10", "10", $GLOBALS["LANG_SUSPENSION_START_DATE_TIP"],
                                                         $StartDateValue, TRUE);

                         // Insert the javascript to use the calendar component
                         $StartDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallbackStartDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                         break;
                 }
             }

             // End date
             if ($bClosed)
             {
                 $EndDate = '&nbsp;';
                 if (!empty($SuspensionRecord["SuspensionEndDate"]))
                 {
                     $EndDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($SuspensionRecord["SuspensionEndDate"]));
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $EndDate = '&nbsp;';
                         if (!empty($SuspensionRecord["SuspensionEndDate"]))
                         {
                             $EndDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($SuspensionRecord["SuspensionEndDate"]));
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         if (empty($SuspensionRecord["SuspensionEndDate"]))
                         {
                             $EndDateValue = '';
                         }
                         else
                         {
                             $EndDateValue = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                            strtotime($SuspensionRecord["SuspensionEndDate"]));
                         }

                         $EndDate = generateInputField("endDate", "text", "10", "10", $GLOBALS["LANG_SUSPENSION_END_DATE_TIP"],
                                                       $EndDateValue, TRUE);

                         // Insert the javascript to use the calendar component
                         $EndDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t EndDateCalendar = new dynCalendar('EndDateCalendar', 'calendarCallbackEndDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                         break;
                 }
             }

             // <<< Reason INPUTFIELD >>>
             if ($bClosed)
             {
                 $Reason = nullFormatText(stripslashes($SuspensionRecord["SuspensionReason"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $Firstname = stripslashes($ChildRecord["ChildFirstname"]);
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Reason = generateInputField("sReason", "text", "255", "100", $GLOBALS["LANG_SUSPENSION_REASON_TIP"],
                                                      $SuspensionRecord["SuspensionReason"]);
                         break;
                 }
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CHILD"]."</td><td class=\"Value\">$Lastname $Firstname</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SUSPENSION_START_DATE"]."*</td><td class=\"Value\">$StartDate</td><td class=\"Label\">".$GLOBALS["LANG_SUSPENSION_END_DATE"]."</td><td class=\"Value\">$EndDate</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SUSPENSION_REASON"]."</td><td class=\"Value\" colspan=\"3\">$Reason</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidChildID", "hidden", "", "", "", $SuspensionRecord["ChildID"]);
             closeStyledFrame();

             if (!$bClosed)
             {
                 echo "<table class=\"validation\">\n<tr>\n\t<td>";
                 insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                 echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                 insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                 echo "</td>\n</tr>\n</table>\n";
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a suspension of a child
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
 * Display the form to prepare families to the next school year, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-08-05
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SchoolYear           Integer               Concerned school year
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to update families
 */
 function displayPrepareNewYearFamiliesForm($DbConnection, $ProcessFormPage, $SchoolYear, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to update families
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Update mode
         if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             $cUserAccess = FCT_ACT_UPDATE;
         }

         if (in_array($cUserAccess, array(FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormPrepareFamilies", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "");

             // Get activated families
             $ArrayFamilies = dbSearchFamily($DbConnection, array('SchoolYear' => array($SchoolYear), 'Activated' => TRUE),
                                             "FamilyLastname", 1, 0);

             if ((isset($ArrayFamilies['FamilyID'])) && (count($ArrayFamilies['FamilyID']) > 0))
             {
                 // Display the table (frame) where the form will take place
                 openStyledFrame($GLOBALS["LANG_FAMILIES"], "Frame", "Frame", "DetailsNews");

                 // Display the form and activated families
                 echo "<table id=\"FamiliesList\" cellspacing=\"0\" cellpadding=\"0\">\n";

                 foreach($ArrayFamilies['FamilyID'] as $f => $FamilyID)
                 {
                     echo "\t<tr>\n\t\t<td class=\"Label\">".$ArrayFamilies['FamilyLastname'][$f]." <em>(";

                     // Display the nb of children
                     if ($ArrayFamilies['NbChildren'][$f] == 1)
                     {
                         echo strtolower($GLOBALS['LANG_CHILD']);
                     }
                     else
                     {
                         echo strtolower($GLOBALS['LANG_CHILDREN']);
                     }

                     echo " : ".$ArrayFamilies['NbChildren'][$f].")</em></td><td class=\"Value\">"
                          .generateInputField("chkFamily[]", 'checkbox', 1, 1, '', $FamilyID, FALSE, TRUE, '', '')."</td>\n\t</tr>\n";
                 }

                 echo "</table>\n";
                 closeStyledFrame();

                 openParagraph('nbentriesfound');
                 echo $GLOBALS['LANG_NB_RECORDS_FOUND'].'<strong>'.count($ArrayFamilies['FamilyID']).'</strong>';
                 closeParagraph();

                 echo "<table class=\"validation\">\n<tr>\n\t<td>";
                 insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                 echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                 insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                 echo "</td>\n</tr>\n</table>\n";
             }
             else
             {
                 // No family found
                 openParagraph('ErrorMsg');
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to update families
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
 * Display the form to prepare children to the next school year, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-08-06
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $SchoolYear           Integer               Concerned school year
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to update children
 */
 function displayPrepareNewYearChildrenForm($DbConnection, $ProcessFormPage, $SchoolYear, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to update children
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Update mode
         if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             $cUserAccess = FCT_ACT_UPDATE;
         }

         if (in_array($cUserAccess, array(FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormPrepareChildren", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "", "");

             // Get activated children
             // Compute grades to select (don't keep the first grade because it isn't a grade)
             $ArrayGrades = array_keys($GLOBALS['CONF_GRADES']);
             array_shift($ArrayGrades);

             $ArrayChildren = dbSearchChild($DbConnection, array(
                                                                 "ChildGrade" => $ArrayGrades,
                                                                 "Activated" => TRUE
                                                                ), "ChildGrade, FamilyLastname, ChildFirstname", 1, 0);

             if ((isset($ArrayChildren['ChildID'])) && (count($ArrayChildren['ChildID']) > 0))
             {
                 $PreviousGrade = NULL;
                 $iNbGrades = 0;
                 $MaxGrade = $ArrayGrades[count($ArrayGrades) - 1];

                 foreach($ArrayChildren['ChildID'] as $c => $ChildID)
                 {
                     if ($PreviousGrade != $ArrayChildren['ChildGrade'][$c])
                     {
                         if (!is_null($PreviousGrade))
                         {
                             echo "</table>\n";
                             echo "</div>\n";
                             closeStyledFrame();
                         }

                         // Display the table (frame) where the form will take place
                         openStyledFrame($GLOBALS["CONF_GRADES"][$ArrayChildren['ChildGrade'][$c]], "Frame", "Frame", "DetailsNews");
                         echo "<div class=\"table\">";

                         // Display the form and activated children
                         if ($iNbGrades == 0)
                         {
                             echo "<table id=\"ChildrenList\" cellspacing=\"0\" cellpadding=\"0\">\n";
                         }
                         else
                         {
                             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n";
                         }

                         // Display captions of the table
                         echo "\t<tr>\n\t\t<th>".$GLOBALS['LANG_CHILDREN']."</th><th>"
                              .$GLOBALS['LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_GO_ON']."</th><th>"
                              .$GLOBALS['LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_STAY']."</th><th>"
                              .$GLOBALS['LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_LEAVE']."</th>\n\t</tr>\n";

                         $iNbGrades++;
                         $PreviousGrade = $ArrayChildren['ChildGrade'][$c];
                     }

                     // Compute which radio buton must be checked
                     $GoOnChecked = TRUE;
                     $LeaveChecked = FALSE;
                     if ($ArrayChildren['ChildGrade'][$c] == $MaxGrade)
                     {
                         // The child in the max grade, so he leaves the school
                         $GoOnChecked = FALSE;
                         $LeaveChecked = TRUE;
                     }

                     echo "\t<tr>\n\t\t<td class=\"Label\">".$ArrayChildren['FamilyLastname'][$c]." "
                          .$ArrayChildren['ChildFirstname'][$c]."</td><td class=\"Value\">";

                     if ($LeaveChecked)
                     {
                         // The child is in the last grade so he can't go to a next grade of the current school
                         // So he can only stay in the same grade or he leaves the school
                         echo "&nbsp;";
                     }
                     else
                     {
                         echo generateInputField("radChild_$ChildID", 'radio', 1, 1,
                                              $GLOBALS['LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_GO_ON_TIP'], 1, FALSE,
                                              $GoOnChecked, '', '');
                     }

                     echo "</td><td class=\"Value\">".generateInputField("radChild_$ChildID", 'radio', 1, 1,
                          $GLOBALS['LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_STAY_TIP'], 2, FALSE, FALSE, '', '')
                          ."</td><td class=\"Value\">".generateInputField("radChild_$ChildID", 'radio', 1, 1,
                                                                          $GLOBALS['LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_LEAVE_TIP'],
                                                                          3, FALSE, $LeaveChecked, '', '')
                          ."</td>\n\t</tr>\n";
                 }

                 echo "</table>\n";
                 closeStyledFrame();

                 openParagraph('nbentriesfound');
                 echo $GLOBALS['LANG_NB_RECORDS_FOUND'].'<strong>'.count($ArrayChildren['ChildID']).'</strong>';
                 closeParagraph();

                 echo "<table class=\"validation\">\n<tr>\n\t<td>";
                 insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                 echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                 insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                 echo "</td>\n</tr>\n</table>\n";
             }
             else
             {
                 // No child found
                 openParagraph('ErrorMsg');
                 echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                 closeParagraph();
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to update children
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
 * Display the form to submit a new bank or update a town, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-02
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $TownID                   String                ID of the town to display [0..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update towns
 */
 function displayDetailsTownForm($DbConnection, $TownID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a town
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($BankID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsTown", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationTown('".$GLOBALS["LANG_ERROR_JS_TOWN_NAME"]."', '".$GLOBALS["LANG_ERROR_JS_TOWN_CODE"]."')");

             // <<< Town ID >>>
             if ($TownID == 0)
             {
                 $Reference = "&nbsp;";
                 $TownRecord = array(
                                     "TownName" => "",
                                     "TownCode" => ""
                                    );
             }
             else
             {
                 if (isExistingTown($DbConnection, $TownID))
                 {
                     // We get the details of the town
                     $TownRecord = getTableRecordInfos($DbConnection, "Towns", $TownID);
                     $Reference = $TownID;
                 }
                 else
                 {
                     // Error, the town doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_TOWN"], "Frame", "Frame", "DetailsNews");

             // <<< Town name INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $Name = stripslashes($TownRecord["TownName"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Name = generateInputField("sName", "text", "50", "30", $GLOBALS["LANG_TOWN_NAME_TIP"],
                                                $TownRecord["TownName"]);
                     break;
             }

             // <<< Town code INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $Code = stripslashes($TownRecord["TownCode"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Code = generateInputField("sCode", "text", "5", "5", $GLOBALS["LANG_TOWN_ZIP_CODE_TIP"],
                                                $TownRecord["TownCode"]);
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_ZIP_CODE"]."*</td><td class=\"Value\">$Code</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_TOWN_NAME"]."*</td><td class=\"Value\" colspan=\"3\">$Name</td>\n</tr>\n";
             echo "</table>\n";

             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";

                     // Display the link to create a new town
                     if (!empty($TownID))
                     {
                         echo generateBR(2);
                         openParagraph('InfoMsg');
                         echo generateCryptedHyperlink($GLOBALS['LANG_SUPPORT_UPDATE_TOWN_PAGE_CREATE_TOWN'], '', 'AddTown.php',
                                                       $GLOBALS["LANG_SUPPORT_UPDATE_TOWN_PAGE_CREATE_TOWN_TIP"], '', '_blank');
                         closeParagraph();
                     }
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a town
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