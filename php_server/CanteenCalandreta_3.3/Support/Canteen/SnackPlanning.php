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
 * Support module : display a planning of snack registrations for the selected school year to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-08-30 : allow to regenerate the planning and patch a bug when a date of snack isn't available
 *                    (holyday). Add new rules : new families must be in the planning after the first vacations
 *                    and families with all children with suspension periods in a same classroom
 *                    can't be in the planning for these classroom.
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-06-15
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
     // We went to generate snack registrations for the selected school year
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
         dbDeleteSnackRegistration($DbCon, NULL, $StartDate, $EndDate);
     }

     // STEP 1 : we get families of activated children for each classroom for the selected school year
     // We get the first day of vacation included in the last month of school year to get the real end date of the school year
     $ArrayHolidays = getHolidays($DbCon, date('y-m-01', strtotime($EndDate)), $EndDate, 'HolidayStartDate', DATES_BETWEEN_PLANNING);
     if ((isset($ArrayHolidays['HolidayID'])) && (count($ArrayHolidays['HolidayID']) > 0))
     {
         $EndDate = date('Y-m-d', strtotime("1 day ago", strtotime($ArrayHolidays['HolidayStartDate'][0])));
     }

     unset($ArrayHolidays);

     // We get families ordered by classroom and for each classroom, random order
     $ArrayChildren = getChildrenListForCanteenPlanning($DbCon, $StartDate, $EndDate, 'ChildClass, RAND()', TRUE);
     if ((isset($ArrayChildren['ChildID'])) && (count($ArrayChildren['ChildID']) > 0))
     {
         // STEP 2 : We get mondays of weeks of the selected school year
         $SchoolYearSnackDates = getGraphicAxeXValuesStats($StartDate, $EndDate, "wd");

         // Now, we must remove holidays betwwen start date and end date
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
                 foreach($SchoolYearSnackDates as $d => $CurrentDate)
                 {
                     $CurrentStamp = strtotime($CurrentDate);
                     $StartWeekStamp = $CurrentStamp;
                     $EndWeekStamp = $CurrentStamp;

                     $CurrentNumOfDay = (integer)date('N', $CurrentStamp);
                     if ($CurrentNumOfDay > 1)
                     {
                         // We get the monday of the week
                         $StartWeekStamp = strtotime("last monday", $CurrentStamp);
                     }

                     if ($CurrentNumOfDay < 7)
                     {
                         // We get the sunday of the week
                         $EndWeekStamp = strtotime("next sunday", $CurrentStamp);
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
                             $CurrentStamp = strtotime("+1 day", $CurrentStamp);
                             $SchoolYearSnackDates[$d] = date('Y-m-d', $CurrentStamp);
                         }
                     } while (($bDateRemoved) && ($CurrentStamp >= $StartWeekStamp) && ($CurrentStamp <= $EndWeekStamp));

                     if ($bDateRemoved)
                     {
                         unset($SchoolYearSnackDates[$d]);
                     }
                     else
                     {
                         // We check if it's a monday
                         if ((integer)date('N', $CurrentStamp) > 1)
                         {
                             // We get the monday of the week
                             $SchoolYearSnackDates[$d] = date('Y-m-d', strtotime("last monday", $CurrentStamp));
                         }
                     }
                 }
             }
         }

         unset($ArraySchoolHolidays);

         // We re-index the dates
         $SchoolYearSnackDates = array_unique(array_values($SchoolYearSnackDates));
         $iNbSchoolYearSnackDates = count($SchoolYearSnackDates);

         $ArrayClassFamilies = array();
         foreach($ArrayChildren['ChildClass'] as $c => $CurrentClass)
         {
             $ArrayClassFamilies[$CurrentClass]['FamilyID'][] = $ArrayChildren['FamilyID'][$c];

             // Count the number of children in the classroom for each family
             if (isset($ArrayClassFamilies[$CurrentClass]['FamilyStats'][$ArrayChildren['FamilyID'][$c]]))
             {
                 $ArrayClassFamilies[$CurrentClass]['FamilyStats'][$ArrayChildren['FamilyID'][$c]]++;
             }
             else
             {
                 $ArrayClassFamilies[$CurrentClass]['FamilyStats'][$ArrayChildren['FamilyID'][$c]] = 1;
             }
         }

         // STEP 3 : for each class, we associate a family to a date of a school year week
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

         unset($ArrayNewFamilies);

         // Define the order of the families in the planning
         $ArrayClassFamiliesOrders = array();
         $ArrayClassrooms = array_keys($ArrayClassFamilies);
         $iNbClassroomDone = 0;

         foreach($ArrayClassFamilies as $Class => $CurrentFamilies)
         {
             // Moreover, new families with all children with a suspension period in the same classroom
             // must be removed from the planning
             $ArraySuspensionsFamilyID = array();
             $ArraySuspensionsFamilies = getSuspensionsChild($DbCon, NULL, TRUE, 'FamilyID',
                                                             array('FamilyID' => $ArrayNewFamilyID,
                                                                   'ChildClass' => array($Class)));

             if (isset($ArraySuspensionsFamilies['FamilyID']))
             {
                 $ArrayTmpNbSuspensionsChildrenFamilyID = array();
                 foreach($ArraySuspensionsFamilies['FamilyID'] as $csf => $cFamID)
                 {
                     // We get nb children by family concerned by suspension periods
                     if (isset($ArrayTmpNbSuspensionsChildrenFamilyID[$cFamID]))
                     {
                         if (!in_array($ArraySuspensionsFamilies['ChildID'][$csf], $ArrayTmpNbSuspensionsChildrenFamilyID[$cFamID]))
                         {
                             $ArrayTmpNbSuspensionsChildrenFamilyID[$cFamID][] = $ArraySuspensionsFamilies['ChildID'][$csf];
                         }
                     }
                     else
                     {
                         $ArrayTmpNbSuspensionsChildrenFamilyID[$cFamID][] = $ArraySuspensionsFamilies['ChildID'][$csf];
                     }
                 }

                 // We search the number of children of these family in these classroom ans school year.
                 // If same number, we can remove the family from the list of families for the planning
                 // for these classroom
                 foreach($ArrayTmpNbSuspensionsChildrenFamilyID as $csfid => $ArrayTmpChildren)
                 {
                     $ArrayTmpFamilyChildren = dbSearchChild($DbCon, array('FamilyID' => $csfid, 'ChildClass' => array($Class)),
                                                             "ChildID", 1, 0);

                     if (isset($ArrayTmpFamilyChildren['ChildID']))
                     {
                         if (count($ArrayTmpFamilyChildren['ChildID']) == count($ArrayTmpChildren))
                         {
                             // We can remove these family from the list of families for the planning for these classroom
                             $ArraySuspensionsFamilyID[] = $csfid;

                             $iSearchPos = array_search($csfid, $CurrentFamilies['FamilyID']);
                             if ($iSearchPos !== FALSE)
                             {
                                 unset($CurrentFamilies['FamilyID'][$iSearchPos],
                                       $CurrentFamilies['FamilyStats'][$csfid],
                                       $ArrayClassFamilies[$Class]['FamilyID'][$iSearchPos],
                                       $ArrayClassFamilies[$Class]['FamilyStats'][$csfid]);
                             }
                         }
                     }
                 }

                 unset($ArrayTmpNbSuspensionsChildrenFamilyID);
             }

             unset($ArraySuspensionsFamilies);

             if (!empty($ArraySuspensionsFamilyID))
             {
                 // Reindex the data
                 $CurrentFamilies['FamilyID'] = array_values($CurrentFamilies['FamilyID']);
                 $ArrayClassFamilies[$Class]['FamilyID'] = array_values($ArrayClassFamilies[$Class]['FamilyID']);
             }

             $iNbFamilies = count($CurrentFamilies['FamilyID']);
             $iNbTries = 0;
             $iNbClassroomDone++;

             do
             {
                 $bContinue = TRUE;

                 if ($iNbFamilies >= $iNbSchoolYearSnackDates)
                 {
                     // To many families for weeks of school year : we trunk
                     $ArraySelectedFamilies = array_slice($CurrentFamilies['FamilyID'], 0, $iNbSchoolYearSnackDates);
                     $ArrayClassFamiliesOrders[$Class] = array_combine($SchoolYearSnackDates, $ArraySelectedFamilies);
                 }
                 else
                 {
                     // No enough families : we re-use some families in relation with the number of children in the classroom
                     $ArraySelectedFamilies = $CurrentFamilies['FamilyID'];
                     $iNbMoreFamilies = 0;
                     $f = 0;
                     while(($iNbMoreFamilies < $iNbSchoolYearSnackDates - $iNbFamilies) && ($f < $iNbFamilies))
                     {
                         if ($ArrayClassFamilies[$Class]['FamilyStats'][$CurrentFamilies['FamilyID'][$f]] == 1)
                         {
                             // We keep this family because low number of children in the classroom
                             $ArraySelectedFamilies[] = $CurrentFamilies['FamilyID'][$f];
                             $iNbMoreFamilies++;
                         }

                         $f++;
                     }

                     $ArrayClassFamiliesOrders[$Class] = array_combine($SchoolYearSnackDates, $ArraySelectedFamilies);

                     // We check if all new families are after the first vacations : if not, we swap families
                     $MinDateNewFamiliesStamp = strtotime($SchoolYearSnackDates[$iFirstWeekForNewFamiliesIndex]);
                     $iPosFamily = 0;
                     foreach($ArrayClassFamiliesOrders[$Class] as $cdate => $cFamID)
                     {
                         if (strtotime($cdate) < $MinDateNewFamiliesStamp)
                         {
                             if (in_array($cFamID, $ArrayNewFamilyID))
                             {
                                 // We must swap the new family with an old family
                                 $iPosToSwap = $iFirstWeekForNewFamiliesIndex + 1;
                                 $bContinueSearch = TRUE;
                                 do
                                 {
                                     if (!in_array($ArraySelectedFamilies[$iPosToSwap], $ArrayNewFamilyID))
                                     {
                                         // Family found : we stop the search and swap the2 families in the list
                                         $ArraySelectedFamilies[$iPosFamily] = $ArraySelectedFamilies[$iPosToSwap];
                                         $ArraySelectedFamilies[$iPosToSwap] = $cFamID;

                                         $bContinueSearch = FALSE;
                                     }

                                     $iPosToSwap++;
                                 } while (($bContinueSearch) && ($iPosToSwap < $iNbFamilies));
                             }
                         }
                         else
                         {
                             // We can stop the search because dates are after the first vacations
                             break;
                         }

                         $iPosFamily++;
                     }

                     $ArrayClassFamiliesOrders[$Class] = array_combine($SchoolYearSnackDates, $ArraySelectedFamilies);
                 }

                 // STEP 4 : we check if a family brings snacks twice for a same date or 2 dates one after the other
                 for($f = 0; $f < $iNbSchoolYearSnackDates; $f++)
                 {
                     if (($f > 0) && ($ArraySelectedFamilies[$f - 1] == $ArraySelectedFamilies[$f]))
                     {
                         // Error : the family brings snacks 2 dates one after the other
                         $bContinue = FALSE;
                         shuffle($CurrentFamilies['FamilyID']);
                         unset($ArraySelectedFamilies);
                         break;
                     }
                     elseif (($f > 1) && ($ArraySelectedFamilies[$f - 2] == $ArraySelectedFamilies[$f]))
                     {
                         // Error : the family brings snacks at dates to close
                         $bContinue = FALSE;
                         shuffle($CurrentFamilies['FamilyID']);
                         unset($ArraySelectedFamilies);
                         break;
                     }
                     else
                     {
                         // We check if a family brings snack for several classrooms at the same date
                         $ArrayTmp = array();
                         foreach($ArrayClassrooms as $cl => $Classroom)
                         {
                             // Wet get the families who bring snacks dor the current date
                             if (isset($ArrayClassFamiliesOrders[$Classroom]))
                             {
                                 $ArrayTmp[] = $ArrayClassFamiliesOrders[$Classroom][$SchoolYearSnackDates[$f]];
                             }
                         }

                         $iNbTmp = count(array_unique($ArrayTmp));
                         if (($iNbTmp > 0) && ($iNbTmp < $iNbClassroomDone))
                         {
                             // Error : the family brings snacks the same date for at least 2 classrooms
                             $bContinue = FALSE;
                             shuffle($CurrentFamilies['FamilyID']);
                             unset($ArraySelectedFamilies);
                             break;
                         }

                         unset($iNbTmp, $ArrayTmp);
                     }
                 }

                 $iNbTries++;
             } while((!$bContinue) && ($iNbTries <= 30));
         }

         // STEP 5 : we save snacks registrations (planning) in the database
         $iNbRecordsToHave = count($ArrayClassrooms) * $iNbSchoolYearSnackDates;
         $iNbRecordsDone = 0;
         $iNbErrors = 0;
         foreach($ArrayClassFamiliesOrders as $Class => $PlanningClass)
         {
             foreach($PlanningClass as $Date => $FamilyID)
             {
                 $SnackRegistrationID = dbAddSnackRegistration($DbCon, $Date, $FamilyID, $Class);
                 if ($SnackRegistrationID > 0)
                 {
                     $iNbRecordsDone++;
                 }
                 else
                 {
                     $iNbErrors++;
                 }
             }
         }

         unset($ArrayClassFamiliesOrders, $ArrayChildren);

         // Log event
         logEvent($DbCon, EVT_SNACK, EVT_SERV_PLANNING, EVT_ACT_CREATE, $_SESSION['SupportMemberID'], 0);

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

 // We have to print the snack planning?
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

         displaySupportMemberContextualMenu("canteen", 1, Canteen_SnackPlanning);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_TITLE, 2);

     if (!is_null($bConfirmMsg))
     {
         if ($bConfirmMsg)
         {
             // Planning created
             openParagraph("ConfirmationMsg");
             displayStyledText($LANG_CONFIRM_SNACK_PLANNING_ADDED, "ShortConfirmMsg");
             closeParagraph();
         }
         else
         {
             // Planning created with errors
             openParagraph("ErrorMsg");
             displayStyledText($LANG_ERROR_ADD_SNACK_PLANNING, "ErrorMsg");
             closeParagraph();
         }
     }

     openParagraph();
     displayStyledText($LANG_SUPPORT_VIEW_SNACK_PLANNING_PAGE_INTRODUCTION." "
                       .date("Y", strtotime(getSchoolYearStartDate($SchoolYear)))
                       ."-".date("Y", strtotime(getSchoolYearEndDate($SchoolYear))));
     closeParagraph();

     // We display the planning of snacks
     displaySnackPlanningForm($DbCon, "SnackPlanning.php", $SchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_SNACK_PLANNING]);

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
     // Print the snack planning
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

     printSnackPlanning($DbCon, "SnackPlanning.php", $SchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_SNACK_PLANNING]);

     closeArea();
     closeWebPage();
     closeGraphicInterface();

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>