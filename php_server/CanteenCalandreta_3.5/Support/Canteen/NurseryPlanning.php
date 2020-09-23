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
 * Support module : display the nursery registrations to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.5
 *     - 2012-07-10 : taken into account NurseryRegistrationChildGrade and NurseryRegistrationChildClass fields
 *                    and display a confirm message when the planning is updated
 *     - 2013-01-25 : allow to change the type of view of the planning (month, week...) and taken into account
 *                    the new variable $CONF_NURSERY_DEFAULT_VIEW_TYPES
 *     - 2013-12-04 : don't use hidden input fields to improve perfs to display planning
 *     - 2014-02-06 : remove one wrong parameter (second FALSE) to the getChildrenListForNurseryPlanning() function,
 *                    add an invisible link to go directly to content
 *     - 2014-05-22 : taken into account the CRC of the planning and the month for the "week" view
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2019-09-17 : v3.4. Display a waiting message when the page is loading
 *     - 2020-02-18 : v3.5. Taken into account $CONF_NURSERY_OTHER_TIMESLOTS
 *
 * @since 2012-02-14
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

 // Get the SupportMemberID of the logged supporter to define the default view type of the planning
 $DefaultViewType = PLANNING_MONTH_VIEW;
 if (isSet($_SESSION["SupportMemberID"]))
 {
     $DefaultViewType = $CONF_NURSERY_DEFAULT_VIEW_TYPES[$_SESSION["SupportMemberStateID"]];
 }

 // To take into account the type of view, month and the year to display
 if (!empty($_POST["lView"]))
 {
     $TypeView = (integer)strip_tags($_POST["lView"]);
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     if (!empty($_GET["lView"]))
     {
         $TypeView = (integer)strip_tags($_GET["lView"]);
         $ParamsPOST_GET = "_GET";
     }
     else
     {
         $TypeView = $DefaultViewType;  // Default view : in relation with the supportmember state ID
         $ParamsPOST_GET = "_GET";
     }
 }

 if ($TypeView < 1)
 {
     $TypeView = $DefaultViewType;  // Default view : in relation with the supportmember state ID
 }

 if (!empty($_POST["lMonth"]))
 {
     $Month = (integer)strip_tags($_POST["lMonth"]);
 }
 else
 {
     if (!empty($_GET["lMonth"]))
     {
         $Month = (integer)strip_tags($_GET["lMonth"]);
     }
     else
     {
         $Month = (integer)(date("m"));  // Current month
     }
 }

 if ($Month < 1)
 {
     $Month = (integer)(date("m"));
 }

 if ($Month > 12)
 {
     $Month = (integer)(date("m"));
 }

 if (!empty($_POST["lWeek"]))
 {
     $Week = (integer)strip_tags($_POST["lWeek"]);
 }
 else
 {
     if (!empty($_GET["lWeek"]))
     {
         $Week = (integer)strip_tags($_GET["lWeek"]);
     }
     else
     {
         $Week = (integer)(date("W"));  // Current week
     }
 }

 if ($Week < 1)
 {
     $Week = (integer)(date("W"));
 }

 if (!empty(${$ParamsPOST_GET}["lYear"]))
 {
     $Year = (integer)strip_tags(${$ParamsPOST_GET}["lYear"]);
     if ($Year < 2003)
     {
         $Year = (integer)(date("Y"));
     }

     if ($Year > 2037)
     {
         $Year = (integer)(date("Y"));
     }
 }
 else
 {
     $Year = (integer)(date("Y")); // Current year
 }

 $NbWeekOfYear = getNbWeeksOfYear($Year);
 if ($Week > $NbWeekOfYear)
 {
     $Week = $NbWeekOfYear;
 }

 // No message
 $bConfirmMsg = NULL;

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["bSubmit"]))
 {
     $bConfirmMsg = FALSE;

     //------------------ Submit button ------------------------
     //--- To registre / unregistre children for the nursery ---
     // The supporter has checked / unchecked some days for some children
     $TodayDate = date('Y-m-d');
     $TodayDateStamp = strtotime($TodayDate);
     $MinEditDateStamp = strtotime(date('Y-m-d', strtotime($CONF_NURSERY_UPDATE_DELAY_PLANNING_REGISTRATION." days ago")));
     $LimitEditDateStamp = $TodayDateStamp;  // The limit to edit the planning is today (not after)

     // First, we get infos about children of the planning (familyname, grade, ...)
     switch($TypeView)
     {
         case PLANNING_WEEKS_VIEW:
             $StartDate = getFirstDayOfWeek($Week, $Year);

             // N weeks + 6 days (first day of week is a monday, so the last is a sunday)
             $EndDate = date("Y-m-d", strtotime('+6 days',
                                                strtotime('+'.($CONF_PLANNING_WEEKS_TO_DISPLAY - 1).' week',
                                                          strtotime($StartDate))));
             break;

         case PLANNING_MONTH_VIEW:
         default:
             $StartDate = sprintf("%04d-%02d-01", $Year, $Month);
             $EndDate = date("Y-m-t", strtotime($StartDate));
             break;
     }

     // We check if the logged supporter can view all nursery registrations or a limited view
     $RestrictionAccess = PLANNING_VIEWS_RESTRICTION_ALL;
     if ((!empty($CONF_NURSERY_VIEWS_RESTRICTIONS)) && (isset($CONF_NURSERY_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']])))
     {
         $RestrictionAccess = $CONF_NURSERY_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']];
     }

     $ArrayChildren = array(
                            'ChildID' => array()
                           );

     switch($RestrictionAccess)
     {
         case PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN:
             // View only the registrations of the children of the family
             // Use the supporter lastname to find the family and children
             $FamilyID = $_SESSION['FamilyID'];
             if ($FamilyID > 0)
             {
                 // Get children of the family
                 $ArrayChildren = getFamilyChildren($DbCon, $FamilyID, "ChildID");
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
             $ArrayChildren = getChildrenListForNurseryPlanning($DbCon, $StartDate, $EndDate, "ChildID", FALSE, PLANNING_BETWEEN_DATES);

             break;
     }

     // Create an index to have the position of each child in the array
     $ArrayPosChildren = array();
     foreach($ArrayChildren['ChildID'] as $c => $CurrChildID)
     {
         if (!array_key_exists($CurrChildID, $ArrayPosChildren))
         {
             $iPosChild = array_search($CurrChildID, $ArrayChildren['ChildID']);
             if ($iPosChild !== FALSE)
             {
                 $ArrayPosChildren[$CurrChildID] = $iPosChild;
             }
         }
     }

     // We get the number of other nursery timeslots
     $CurrentSchoolYear = getSchoolYear($StartDate);
     $iNbOtherTimeslots = 0;
     $ArrayOtherTimeslotsPatterns = array();
     if ((isset($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear]))
                 && (!empty($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear])))
     {
         // This school year has som other timeslots (more than AM and PM timeslots)
         $iNbOtherTimeslots = count($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear]);
         $iPos = 0;
         foreach($CONF_NURSERY_OTHER_TIMESLOTS[$CurrentSchoolYear] as $ots => $CurrentParamsOtherTimeslot)
         {
            $ArrayOtherTimeslotsPatterns[$ots] = pow(2, $iPos);
            $iPos++;
         }
     }

     // Get nursery registrations of each child
     $ArrayNurseryRegistrations = getNurseryRegistrations($DbCon, $StartDate, $EndDate, 'ChildID', $ArrayChildren['ChildID'],
                                                          PLANNING_BETWEEN_DATES);

     // Map the nursery registrations to the children
     $DBPlanningCRC = 0;
     if (isset($ArrayNurseryRegistrations['NurseryRegistrationID']))
     {
         foreach($ArrayNurseryRegistrations['NurseryRegistrationID'] as $nr => $NurseryRegistrationID)
         {
             $ArrayChildren['NurseryRegistrations'][$ArrayPosChildren[$ArrayNurseryRegistrations['ChildID'][$nr]]][$ArrayNurseryRegistrations['NurseryRegistrationForDate'][$nr]] = array(
                                                                                                                                                                                          'NurseryRegistrationID' => $NurseryRegistrationID,
                                                                                                                                                                                          'NurseryRegistrationDate' => $ArrayNurseryRegistrations['NurseryRegistrationDate'][$nr],
                                                                                                                                                                                          'NurseryRegistrationForAM' => $ArrayNurseryRegistrations['NurseryRegistrationForAM'][$nr],
                                                                                                                                                                                          'NurseryRegistrationForPM' => $ArrayNurseryRegistrations['NurseryRegistrationForPM'][$nr],
                                                                                                                                                                                          'NurseryRegistrationOtherTimeslots' => $ArrayNurseryRegistrations['NurseryRegistrationOtherTimeslots'][$nr]
                                                                                                                                                                                         );
             // Compute the CRC of the planning from the database
             $DBPlanningCRC = $DBPlanningCRC ^ $NurseryRegistrationID;
         }
     }

     unset($ArrayNurseryRegistrations);

     // We check if the CRC of the planning from the database is equal to the CRC coming from the "form" planning
     $DBPlanningCRC = md5($DBPlanningCRC);
     $FormPlanningCRC = 0;
     if (isset($_POST['hidPlanningCRC']))
     {
         $FormPlanningCRC = trim(strip_tags($_POST['hidPlanningCRC']));
     }

     // We concat $_POST of AM and PM in 1 variable (easiest to treat)
     $ArrayPOST = array();
     if (isset($_POST['chkNurseryRegitrationAM']))
     {
         foreach($_POST['chkNurseryRegitrationAM'] as $p => $ChkValue)
         {
             $ArrayPOST[$ChkValue] = array('AM' => 1, 'PM' => 0, 'OtherTimeslots' => 0);
         }
     }

     if (isset($_POST['chkNurseryRegitrationPM']))
     {
         foreach($_POST['chkNurseryRegitrationPM'] as $p => $ChkValue)
         {
             if (isset($ArrayPOST[$ChkValue]))
             {
                 // The AM value already exists (previous foreach)
                 $ArrayPOST[$ChkValue]['PM'] = 1;
             }
             else
             {
                 $ArrayPOST[$ChkValue] = array('AM' => 0, 'PM' => 1, 'OtherTimeslots' => 0);
             }
         }
     }

     if ($iNbOtherTimeslots > 0)
     {
         foreach($ArrayOtherTimeslotsPatterns as $ots => $CurrentPattern)
         {
             foreach($_POST['chkNurseryRegitrationOtherTimeslot'.$ots] as $p => $ChkValue)
             {
                 if (isset($ArrayPOST[$ChkValue]))
                 {
                     // The AM and PM values already exist (previous foreach)
                     $ArrayPOST[$ChkValue]['OtherTimeslots'] += $CurrentPattern;
                 }
                 else
                 {
                     $ArrayPOST[$ChkValue] = array('AM' => 0, 'PM' => 1, 'OtherTimeslots' => $CurrentPattern);
                 }
             }
         }
     }

     // Now, we treat the form (checkbox)
     if (!empty($ArrayPOST))
     {
         // There are checkbox to treat
         foreach($ArrayPOST as $sValue => $AMPMOtherTimeslots)
         {
             // We extract data from the value : Day#Class#ChildID#NurseryRegistrationID
             $ArrayValues = explode('#', $sValue);
             if (count($ArrayValues) == 4)
             {
                 $ForDate = $ArrayValues[0];
                 $ChildClass = $ArrayValues[1];
                 $ChildID = $ArrayValues[2];
                 $NurseryRegistrationID = $ArrayValues[3];

                 // We check if the planning can be edited
                 // First we check if the supporter is concerned by the retrictions delays
                 $bCanEdit = FALSE;
                 if (in_array($_SESSION['SupportMemberStateID'], $GLOBALS['CONF_NURSERY_DELAYS_RESTRICTIONS']))
                 {
                     // The supporter is concerned by restrictions
                     // The supporter can't registre a child after x days
                     if ((strtotime($ForDate) >= $MinEditDateStamp) && (strtotime($ForDate) <= $LimitEditDateStamp))
                     {
                         $bCanEdit = TRUE;
                     }
                 }
                 else
                 {
                     $bCanEdit = TRUE;
                 }

                 if (($bCanEdit) && (empty($NurseryRegistrationID)))
                 {
                     // New nursery registration (only if the checkbox is checked)
                     // Yes, the day (AM and/or PM) is checked for the child
                     // We check if the logged supporter is an admin (so, not concerned by restrictions on delays)
                     $AdminDate = NULL;
                     if (!in_array($_SESSION['SupportMemberStateID'], $CONF_NURSERY_DELAYS_RESTRICTIONS))
                     {
                         // The logged supporter is an admin or a user with a special access and
                         // allowed to modify childern nursery registrations
                         $AdminDate = $TodayDate;
                     }

                     $NurseryRegistrationID = dbAddNurseryRegistration($DbCon, $TodayDate, $ForDate, $ChildID,
                                                                       $_SESSION['SupportMemberID'], $AMPMOtherTimeslots['AM'], $AMPMOtherTimeslots['PM'],
                                                                       $ArrayChildren['ChildGrade'][$ArrayPosChildren[$ChildID]],
                                                                       $ArrayChildren['ChildClass'][$ArrayPosChildren[$ChildID]],
                                                                       $AdminDate, 0, $AMPMOtherTimeslots['OtherTimeslots']);

                     if ($NurseryRegistrationID > 0)
                     {
                         $bConfirmMsg = TRUE;

                         // Log event
                         logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'],
                                  $NurseryRegistrationID);
                     }
                 }
                 else
                 {
                     // The nursery registration exists in the NurseryRegistrations table
                     if (($bCanEdit) && (isExistingNurseryRegistration($DbCon, $NurseryRegistrationID)))
                     {
                         if (($AMPMOtherTimeslots['AM'] == 1) || ($AMPMOtherTimeslots['PM'] == 1) || ($AMPMOtherTimeslots['OtherTimeslots'] > 0))
                         {
                             $InitialNurseryRegistrationID = $NurseryRegistrationID;

                             // We check if the logged supporter is an admin (so, not concerned by restrictions on delays)
                             $AdminDate = NULL;
                             if (!in_array($_SESSION['SupportMemberStateID'], $CONF_NURSERY_DELAYS_RESTRICTIONS))
                             {
                                 // The logged supporter is an admin or a user with a special access and
                                 // allowed to modify childern nursery registrations
                                 $AdminDate = $TodayDate;
                             }

                             // We check if the nursery registration is really updated
                             if (($ArrayChildren['NurseryRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]['NurseryRegistrationForAM'] != $AMPMOtherTimeslots['AM'])
                                 || ($ArrayChildren['NurseryRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]['NurseryRegistrationForPM'] != $AMPMOtherTimeslots['PM'])
                                 || ($ArrayChildren['NurseryRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]['NurseryRegistrationOtherTimeslots'] != $AMPMOtherTimeslots['OtherTimeslots'])
                                )
                             {
                                 // Yes, it's an update
                                 $NurseryRegistrationID = dbUpdateNurseryRegistration($DbCon, $NurseryRegistrationID, NULL, $ForDate,
                                                                                      $ChildID, $_SESSION['SupportMemberID'], $AMPMOtherTimeslots['AM'],
                                                                                      $AMPMOtherTimeslots['PM'], $ArrayChildren['ChildGrade'][$ArrayPosChildren[$ChildID]],
                                                                                      $ArrayChildren['ChildClass'][$ArrayPosChildren[$ChildID]],
                                                                                      $AdminDate, NULL, $AMPMOtherTimeslots['OtherTimeslots']);

                                 if ($NurseryRegistrationID > 0)
                                 {
                                     $bConfirmMsg = TRUE;

                                     // Log event
                                     logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'],
                                              $NurseryRegistrationID);
                                 }
                             }

                             if (isset($ArrayChildren['NurseryRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]))
                             {
                                 if ($ArrayChildren['NurseryRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]['NurseryRegistrationID'] == $InitialNurseryRegistrationID)
                                 {
                                     // Unset this nursery registration : it's treated !
                                     unset($ArrayChildren['NurseryRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]);
                                 }
                             }
                         }
                     }
                 }
             }
         }
     }

     unset($ArrayPOST);

     // We treat the nursery registrations (delete)
     if ((isset($ArrayChildren['NurseryRegistrations'])) && ($DBPlanningCRC == $FormPlanningCRC))
     {
         foreach($ArrayChildren['NurseryRegistrations'] as $nr => $ArrayRegistrations)
         {
             foreach($ArrayRegistrations as $ForDate => $Registration)
             {
                 // We check if the planning can be edited
                 // First we check if the supporter is concerned by the retrictions delays
                 $bCanEdit = FALSE;
                 if (in_array($_SESSION['SupportMemberStateID'], $GLOBALS['CONF_NURSERY_DELAYS_RESTRICTIONS']))
                 {
                     // The supporter is concerned by restrictions
                     // The supporter can't registre a child after x days
                     if ((strtotime($ForDate) >= $MinEditDateStamp) && (strtotime($ForDate) <= $LimitEditDateStamp))
                     {
                         $bCanEdit = TRUE;
                     }
                 }
                 else
                 {
                     $bCanEdit = TRUE;
                 }

                 if ($bCanEdit)
                 {
                     // We create the record about the nursery registration to delete
                     $RecordNurseryRegistration = array(
                                                        'NurseryRegistrationID' => $Registration['NurseryRegistrationID'],
                                                        'NurseryRegistrationDate' => $Registration['NurseryRegistrationDate'],
                                                        'NurseryRegistrationForDate' => $ForDate,
                                                        'ChildID' => $ArrayChildren['ChildID'][$nr],
                                                        'NurseryRegistrationChildGrade' => $ArrayChildren['ChildGrade'][$nr],
                                                        'NurseryRegistrationChildClass' => $ArrayChildren['ChildClass'][$nr],
                                                        'NurseryRegistrationForAM' => $Registration['NurseryRegistrationForAM'],
                                                        'NurseryRegistrationForPM' => $Registration['NurseryRegistrationForPM'],
                                                        'NurseryRegistrationOtherTimeslots' => $Registration['NurseryRegistrationOtherTimeslots']
                                                       );

                     if (dbDeleteNurseryRegistration($DbCon, $Registration['NurseryRegistrationID']))
                     {
                         $bConfirmMsg = TRUE;

                         // Log event
                         logEvent($DbCon, EVT_NURSERY, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                  $NurseryRegistrationID, array('NurseryRegistrationDetails' => $RecordNurseryRegistration));
                     }
                 }
             }
         }
     }
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array('../Verifications.js'),
                      '',
                      "WaitingPageLoadedManager('WaitingLoadingPageMsg', ".$_SESSION['SupportMemberStateID'].", '1|2|3|4|6')"
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#FirstChild', 'Accessibility');

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_NurseryPlanning);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_TITLE, 2);

 if (!is_null($bConfirmMsg))
 {
     if ($bConfirmMsg)
     {
         // Planning updated
         openParagraph("ConfirmationMsg");
         displayStyledText($LANG_CONFIRM_NURSERY_PLANNING_UPDATED, "ShortConfirmMsg");
         closeParagraph();
     }
 }

 switch($TypeView)
 {
     case PLANNING_WEEKS_VIEW:
         // We display the planning by several weeks
         // We compute the first day of the selected week
         $FirstDayStamp = strtotime(getFirstDayOfWeek($Week, $Year));
         $EndDayStamp = strtotime('+'.($CONF_PLANNING_WEEKS_TO_DISPLAY - 1).' week', $FirstDayStamp);
         $StartWeek = date('W', $FirstDayStamp);
         $StartMonth = date('m', $FirstDayStamp);
         $StartYear = date('o', $FirstDayStamp);

         openParagraph();
         displayStyledText($LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_INTRODUCTION." S$StartWeek-$StartYear - S".date('W-Y', $EndDayStamp)
                           .' ('.$CONF_PLANNING_MONTHS[$StartMonth - 1].')', "");
         closeParagraph();

         // We display the planning
         displayNurseryPlanningByWeeksForm($DbCon, "NurseryPlanning.php", $Week, $Year, $CONF_ACCESS_APPL_PAGES[FCT_NURSERY_PLANNING],
                                           $CONF_NURSERY_VIEWS_RESTRICTIONS);
         break;

     case PLANNING_MONTH_VIEW:
     default:
         // We display the planning by month
         openParagraph();
         displayStyledText($LANG_SUPPORT_VIEW_NURSERY_PLANNING_PAGE_INTRODUCTION." ".$CONF_PLANNING_MONTHS[$Month - 1]." $Year.", "");
         closeParagraph();

         // We display the planning
         displayNurseryPlanningByMonthForm($DbCon, "NurseryPlanning.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_NURSERY_PLANNING],
                                           $CONF_NURSERY_VIEWS_RESTRICTIONS);
         break;
 }

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