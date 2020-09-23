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
 * Support module : display a planning of laundry registrations for the selected school year to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-08-29 : allow to regenerate the planning and patch a bug when a date of laundry isn't available
 *                    (holyday). Add new rules : new families must be in the planning after the first vacations
 *                    and families with all children with suspension periods can't be in the planning.
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-06-19
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Include the stats library
 include_once('../Stats/StatsLibrary.php');

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 if (!empty($_POST["lYear"]))
 {
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     $ParamsPOST_GET = "_GET";
 }

 // Get the current school year
 $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));

 // To take into account the school year to display
 if (!empty(${$ParamsPOST_GET}["lYear"]))
 {
     // We get the given school year
     $SchoolYear = (integer)strip_tags(${$ParamsPOST_GET}["lYear"]);
 }
 else
 {
     // We get the current school year
     $SchoolYear = $CurrentSchoolYear;
 }

 //################################ FORM PROCESSING ##########################
 $bConfirmMsg = NULL;

 if ((!empty($_POST["bCreatePlanning"])) || (!empty($_POST["bRegeneratePlanning"])))
 {
     // We went to generate laundry registrations for the selected school year
     $StartDate = getSchoolYearStartDate($SchoolYear, TRUE);
     if ((integer)date('N', strtotime($StartDate)) > 1)
     {
         // We get the monday of the week
         $StartDate = date('Y-m-d', strtotime("last monday", strtotime($StartDate)));
     }
     $EndDate = getSchoolYearEndDate($SchoolYear);

     if (!empty($_POST["bRegeneratePlanning"]))
     {
         // STEP 0 : delete the previous planning
         dbDeleteLaundryRegistration($DbCon, NULL, $StartDate, $EndDate);
     }

     // STEP 1 : we get families of activated children for each classroom for the selected school year
     // We get the first day of vacation included in the last month of school year to get the real end date of the school year
     $ArrayHolidays = getHolidays($DbCon, date('y-m-01', strtotime($EndDate)), $EndDate, 'HolidayStartDate', DATES_BETWEEN_PLANNING);
     if ((isset($ArrayHolidays['HolidayID'])) && (count($ArrayHolidays['HolidayID']) > 0))
     {
         $EndDate = date('Y-m-d', strtotime("1 day ago", strtotime($ArrayHolidays['HolidayStartDate'][0])));
     }

     unset($ArrayHolidays);

     // We get families with a random order
     $ArrayChildren = getChildrenListForCanteenPlanning($DbCon, $StartDate, $EndDate, 'RAND()', TRUE);
     if ((isset($ArrayChildren['ChildID'])) && (count($ArrayChildren['ChildID']) > 0))
     {
         // STEP 2 : We get num days of weeks of the selected school year
         $TmpSchoolYearLaundryDates = array();
         foreach($CONF_LAUNDRY_FOR_DAYS as $d => $CurrentNumDay)
         {
             $TmpStartDate = date('Y-m-d', strtotime("+".($CurrentNumDay - 1)." days", strtotime($StartDate)));
             $TmpSchoolYearLaundryDates[] = getGraphicAxeXValuesStats($TmpStartDate, $EndDate, "wd");
         }

         $SchoolYearLaundryDates = array();
         $iNbDates = count($TmpSchoolYearLaundryDates[0]);
         for($d = 0; $d < $iNbDates; $d++)
         {
             foreach($CONF_LAUNDRY_FOR_DAYS as $n => $CurrentNumDay)
             {
                 $SchoolYearLaundryDates[] = $TmpSchoolYearLaundryDates[$n][$d];
             }
         }

         unset($TmpSchoolYearLaundryDates, $iNbDates);

         // Now, we must remove holidays between start date and end date
         $TmpStartDate = getSchoolYearStartDate($SchoolYear, TRUE);
         $ArraySchoolHolidays = getHolidays($DbCon, $TmpStartDate, $EndDate, 'HolidayStartDate', PLANNING_BETWEEN_DATES);
         if ((!isset($ArraySchoolHolidays['HolidayID'])) || ((isset($ArraySchoolHolidays['HolidayID']))
             && (empty($ArraySchoolHolidays['HolidayID']))))
         {
             $ArraySchoolHolidays = getHolidays($DbCon, $TmpStartDate, $EndDate, 'HolidayStartDate', DATES_BETWEEN_PLANNING);
         }

         $iFirstWeekForNewFamiliesIndex = NULL;

         if (isset($ArraySchoolHolidays['HolidayID']))
         {
             foreach($ArraySchoolHolidays['HolidayID'] as $h => $HolidayID)
             {
                 $StartStamp = strtotime($ArraySchoolHolidays['HolidayStartDate'][$h]);
                 $EndStamp = strtotime($ArraySchoolHolidays['HolidayEndDate'][$h]);
                 foreach($SchoolYearLaundryDates as $d => $CurrentDate)
                 {
                     $CurrentStamp = strtotime($CurrentDate);
                     $StartWeekStamp = $CurrentStamp;
                     if ((integer)date('N', $CurrentStamp) > 1)
                     {
                         // We get the monday of the week
                         $StartWeekStamp = strtotime("last monday", $CurrentStamp);
                     }

                     do
                     {
                         $bDateRemoved = FALSE;
                         if (($CurrentStamp >= $StartStamp) && ($CurrentStamp <= $EndStamp))
                         {
                             // Holidays : we remove this date
                             $bDateRemoved = TRUE;

                             if (is_null($iFirstWeekForNewFamiliesIndex))
                             {
                                 // The right date for new families is the next date (so +1)
                                 $iFirstWeekForNewFamiliesIndex = $d + 1;
                             }
                         }
                         elseif ((jour_ferie($CurrentStamp) === 0)
                                 || (!$CONF_CANTEEN_OPENED_WEEK_DAYS[(integer)date('N', $CurrentStamp) - 1]))
                         {
                             // It's not a working day
                             $bDateRemoved = TRUE;
                         }

                         if ($bDateRemoved)
                         {
                             // We try to find another date before
                             $CurrentStamp = strtotime("1 day ago", $CurrentStamp);
                             $SchoolYearLaundryDates[$d] = date('Y-m-d', $CurrentStamp);
                         }
                     } while (($bDateRemoved) && ($CurrentStamp >= $StartWeekStamp));

                     if ($bDateRemoved)
                     {
                         unset($SchoolYearLaundryDates[$d]);
                     }
                 }
             }
         }

         unset($ArraySchoolHolidays);

         // We re-index the dates
         $SchoolYearLaundryDates = array_unique(array_values($SchoolYearLaundryDates));
         $iNbSchoolYearLaundryDates = count($SchoolYearLaundryDates);

         // STEP 3 : for each date of a school year week, we associate several families (in relation with
         // $CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE) : each family is unique
         // First, we search new families because they can't be in the first part of the planning (period between
         // the first week of the school year and the week before the first vacations !!
         $ArrayNewFamilyID = array();
         $StartDateNewFamilies = getSchoolYearStartDate($SchoolYear);
         $EndDateNewFamilies = getSchoolYearEndDate($SchoolYear);
         $ArrayNewFamilies = dbSearchFamily($DbCon, array("SchoolYear" => array($SchoolYear),
                                                          "FamilyDate" => array(">=", $StartDateNewFamilies, "<=", $EndDateNewFamilies)),
                                            "FamilyID", 1, 0);

         if (isset($ArrayNewFamilies['FamilyID']))
         {
             $ArrayNewFamilyID = $ArrayNewFamilies['FamilyID'];
         }

         // Moreover, new families with all children with a suspension period must be removed from the planning
         $ArraySuspensionsFamilyID = array();
         $ArraySuspensionsFamilies = getSuspensionsChild($DbCon, NULL, TRUE, 'FamilyID', array('FamilyID' => $ArrayNewFamilyID));
         if (isset($ArraySuspensionsFamilies['FamilyID']))
         {
             // We count number of children by families
             $ArrayNbChildrenByFamily = array();
             foreach($ArrayChildren['FamilyID'] as $scf => $CurrentChildFamilyID)
             {
                 if (isset($ArrayNbChildrenByFamily[$CurrentChildFamilyID]))
                 {
                     if (!in_array($ArrayChildren['ChildID'][$scf], $ArrayNbChildrenByFamily[$CurrentChildFamilyID]))
                     {
                         $ArrayNbChildrenByFamily[$CurrentChildFamilyID][] = $ArrayChildren['ChildID'][$scf];
                     }
                 }
                 else
                 {
                     $ArrayNbChildrenByFamily[$CurrentChildFamilyID][] = $ArrayChildren['ChildID'][$scf];
                 }
             }

             // Get ID of children for each family concerned by a period of suspension
             foreach($ArraySuspensionsFamilies['FamilyID'] as $scf => $CurrentSuspensionFamilyID)
             {
                 if (isset($ArraySuspensionsFamilyID[$CurrentSuspensionFamilyID]))
                 {
                     if (!in_array($ArraySuspensionsFamilies['ChildID'][$scf], $ArraySuspensionsFamilyID[$CurrentSuspensionFamilyID]))
                     {
                         $ArraySuspensionsFamilyID[$CurrentSuspensionFamilyID][] = $ArraySuspensionsFamilies['ChildID'][$scf];
                     }
                 }
                 else
                 {
                     $ArraySuspensionsFamilyID[$CurrentSuspensionFamilyID][] = $ArraySuspensionsFamilies['ChildID'][$scf];
                 }
             }

             // We check if all children of a family are concerned by suspensions : if yes, we can remove these family
             // of the list of families for the planning
             foreach($ArraySuspensionsFamilyID as $fid => $CurrentChildren)
             {
                 if (count($ArrayNbChildrenByFamily[$fid]) > count($CurrentChildren))
                 {
                     // These family can be kept for the list of families for the planning
                     unset($ArraySuspensionsFamilyID[$fid]);
                 }
             }

             // Families to remove form the list of families for the planning
             $ArraySuspensionsFamilyID = array_keys($ArraySuspensionsFamilyID);

             unset($ArrayNbChildrenByFamily);
         }

         unset($ArrayNewFamilies, $ArraySuspensionsFamilies);

         // Define the order of the families in the planning
         $ArrayFamiliesOrders = array();
         $ArrayFamilies = array_values(array_unique($ArrayChildren['FamilyID']));
         $iNbFamilies = count($ArrayFamilies);
         $iNbTries = 0;

         do
         {
             $bContinue = TRUE;
             $iPos = 0;

             foreach($SchoolYearLaundryDates as $d => $CurrentDate)
             {
                 for($f = 0; $f < $CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE; $f++)
                 {
                     // We check if the family must be removed from the planning because all children ahave a suspension period
                     if (in_array($ArrayFamilies[$iPos], $ArraySuspensionsFamilyID))
                     {
                         // Go to the next family
                         $iPos++;
                     }

                     // We check if the current family is a new family and the current date is after the first vacations
                     if ((in_array($ArrayFamilies[$iPos], $ArrayNewFamilyID)) && ($d < $iFirstWeekForNewFamiliesIndex))
                     {
                         // We swap the place in the list with the first no-new family
                         $iPosToSwap = ($iFirstWeekForNewFamiliesIndex + 2) * $CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE;
                         $bContinueSearch = TRUE;
                         do
                         {
                             if (!in_array($ArrayFamilies[$iPosToSwap], $ArrayNewFamilyID))
                             {
                                 // Family found : we stop the search and swap the2 families in the list
                                 $iTmpID = $ArrayFamilies[$iPos];
                                 $ArrayFamilies[$iPos] = $ArrayFamilies[$iPosToSwap];
                                 $ArrayFamilies[$iPosToSwap] = $iTmpID;

                                 $bContinueSearch = FALSE;
                             }

                             $iPosToSwap++;
                         } while (($bContinueSearch) && ($iPosToSwap < $iNbFamilies));
                     }

                     $ArrayFamiliesOrders[$CurrentDate][] = $ArrayFamilies[$iPos];
                     $iPos++;
                     if ($iPos >= $iNbFamilies)
                     {
                         $iPos = 0;
                     }
                 }
             }

             // STEP 4 : we check if a family does laundries twice for a same date or 2 dates one after the other
             $ArrayDates = array_keys($ArrayFamiliesOrders);
             for($f = 0; $f < $iNbSchoolYearLaundryDates; $f++)
             {
                 if (($f > 0) && ($ArrayFamiliesOrders[$ArrayDates[$f - 1]] == $ArrayFamiliesOrders[$ArrayDates[$f]]))
                 {
                     // Error : the family does laundries 2 dates one after the other
                     $bContinue = FALSE;
                     shuffle($ArrayFamilies);
                     $ArrayFamiliesOrders = array();
                     break;
                 }
                 elseif (($f > 1) && ($ArrayFamiliesOrders[$ArrayDates[$f - 2]] == $ArrayFamiliesOrders[$ArrayDates[$f]]))
                 {
                     // Error : the family does laundries at dates too close
                     $bContinue = FALSE;
                     shuffle($ArrayFamilies);
                     $ArrayFamiliesOrders = array();
                     break;
                 }
             }

             unset($ArrayDates);
         } while((!$bContinue) && ($iNbTries <= 30));

         // STEP 5 : we save laundry registrations (planning) in the database
         $iNbRecordsToHave = $iNbSchoolYearLaundryDates * $CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE;
         $iNbRecordsDone = 0;
         $iNbErrors = 0;
         foreach($ArrayFamiliesOrders as $Date => $CurrentFamilies)
         {
             foreach($CurrentFamilies as $f => $FamilyID)
             {
                 $LaundryRegistrationID = dbAddLaundryRegistration($DbCon, $Date, $FamilyID);
                 if ($LaundryRegistrationID > 0)
                 {
                     $iNbRecordsDone++;
                 }
                 else
                 {
                     $iNbErrors++;
                 }
             }
         }

         unset($ArrayFamiliesOrders, $ArrayChildren);

         // Log event
         logEvent($DbCon, EVT_LAUNDRY, EVT_SERV_PLANNING, EVT_ACT_CREATE, $_SESSION['SupportMemberID'], 0);

         if ($iNbRecordsDone == $iNbRecordsToHave)
         {
             // All is OK
             $bConfirmMsg = TRUE;
         }
         else
         {
             // There are some errors
             $bConfirmMsg = FALSE;
         }
     }
 }
 //################################ END FORM PROCESSING ##########################

 // We have to print the laundry planning?
 $bOnPrint = FALSE;
 if (!empty($_POST["hidOnPrint"]))
 {
     if ($_POST["hidOnPrint"] == 1)
     {
         $bOnPrint = TRUE;
     }
 }

 if (!$bOnPrint)
 {
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
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#lYear', 'Accessibility');

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

         displaySupportMemberContextualMenu("canteen", 1, Canteen_LaundryPlanning);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_VIEW_LAUNDRY_PLANNING_PAGE_TITLE, 2);

     if (!is_null($bConfirmMsg))
     {
         if ($bConfirmMsg)
         {
             // Planning created
             openParagraph("ConfirmationMsg");
             displayStyledText($LANG_CONFIRM_LAUNDRY_PLANNING_ADDED, "ShortConfirmMsg");
             closeParagraph();
         }
         else
         {
             // Planning created with errors
             openParagraph("ErrorMsg");
             displayStyledText($LANG_ERROR_ADD_LAUNDRY_PLANNING, "ErrorMsg");
             closeParagraph();
         }
     }

     openParagraph();
     displayStyledText($LANG_SUPPORT_VIEW_LAUNDRY_PLANNING_PAGE_INTRODUCTION." "
                       .date("Y", strtotime(getSchoolYearStartDate($SchoolYear)))
                       ."-".date("Y", strtotime(getSchoolYearEndDate($SchoolYear))));
     closeParagraph();

     // We display the planning of laundry
     displayLaundryPlanningForm($DbCon, "LaundryPlanning.php", $SchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_LAUNDRY_PLANNING]);

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
 }
 else
 {
     // Print the laundry planning
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen',
                                '../../Templates/PrintStyles.css' => 'print'
                               ),
                          array()
                         );

     openWebPage();
     openArea('id="content"');

     printLaundryPlanning($DbCon, "LaundryPlanning.php", $SchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_LAUNDRY_PLANNING]);

     closeArea();
     closeWebPage();
     closeGraphicInterface();

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>