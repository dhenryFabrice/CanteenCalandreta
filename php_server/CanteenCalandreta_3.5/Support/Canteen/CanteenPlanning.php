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
 * Support module : display the canteen registrations to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.3
 *     - 2012-07-10 : taken into account CanteenRegistrationChildGrade and CanteenRegistrationChildClass fields
 *                    and display a confirm message when the planning is updated
 *     - 2013-01-25 : allow to change the type of view of the planning (month, week...) and taken into account
 *                    the new variable $CONF_CANTEEN_DEFAULT_VIEW_TYPES
 *     - 2013-11-28 : don't use hidden input fields to improve perfs to display planning
 *     - 2014-02-25 : add an invisible link to go directly to content
 *     - 2014-05-22 : taken into account the CRC of the planning and display the month for the "week" view
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2019-09-17 : display a waiting message when the page is loading
 *
 * @since 2012-01-28
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
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 // Get the SupportMemberID of the logged supporter to define the default view type of the planning
 $DefaultViewType = PLANNING_MONTH_VIEW;
 if (isSet($_SESSION["SupportMemberID"]))
 {
     $DefaultViewType = $CONF_CANTEEN_DEFAULT_VIEW_TYPES[$_SESSION["SupportMemberStateID"]];
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
     //--- To registre / unregistre children for the canteen ---
     $TodayDate = date('Y-m-d');
     $TodayDateStamp = strtotime($TodayDate);
     $TodayCurrentTime = strtotime(date('Y-m-d H:i:s'));
     $LimitEditDateStamp = strtotime(date('Y-m-t',
                                          strtotime("+".($GLOBALS['CONF_CANTEEN_NB_MONTHS_PLANNING_REGISTRATION'] - 1)." months")));

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

     // We check if the logged supporter can view all children or only its own children
     $RestrictionAccess = PLANNING_VIEWS_RESTRICTION_ALL;
     if ((!empty($CONF_CANTEEN_VIEWS_RESTRICTIONS)) && (isset($CONF_CANTEEN_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']])))
     {
         $RestrictionAccess = $CONF_CANTEEN_VIEWS_RESTRICTIONS[$_SESSION['SupportMemberStateID']];
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
             // View all canteen registrations
             $ArrayChildren = getChildrenListForCanteenPlanning($DbCon, $StartDate, $EndDate, "ChildID", FALSE, FALSE,
                                                                PLANNING_BETWEEN_DATES);

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

     // Get canteen registrations of each child
     $ArrayCanteenRegistrations = getCanteenRegistrations($DbCon, $StartDate, $EndDate, 'ChildID', $ArrayChildren['ChildID'],
                                                          FALSE, PLANNING_BETWEEN_DATES);

     // Map the canteen registrations to the children
     $DBPlanningCRC = 0;
     if (isset($ArrayCanteenRegistrations['CanteenRegistrationID']))
     {
         foreach($ArrayCanteenRegistrations['CanteenRegistrationID'] as $cr => $CanteenRegistrationID)
         {
             $ArrayChildren['CanteenRegistrations'][$ArrayPosChildren[$ArrayCanteenRegistrations['ChildID'][$cr]]][$ArrayCanteenRegistrations['CanteenRegistrationForDate'][$cr]] = array(
                                                                                                                                                                                          'CanteenRegistrationID' => $CanteenRegistrationID,
                                                                                                                                                                                          'CanteenRegistrationDate' => $ArrayCanteenRegistrations['CanteenRegistrationDate'][$cr]
                                                                                                                                                                                         );
             // Compute the CRC of the planning from the database
             $DBPlanningCRC = $DBPlanningCRC ^ $CanteenRegistrationID;
         }
     }

     unset($ArrayCanteenRegistrations);

     // We check if the CRC of the planning from the database is equal to the CRC coming from the "form" planning
     $DBPlanningCRC = md5($DBPlanningCRC);
     $FormPlanningCRC = 0;
     if (isset($_POST['hidPlanningCRC']))
     {
         $FormPlanningCRC = trim(strip_tags($_POST['hidPlanningCRC']));
     }

     // We treat the canteen registrations (add/update)
     if (isset($_POST['chkCanteenRegitration']))
     {
         // The supporter has checked / unchecked some days for some children
         foreach($_POST['chkCanteenRegitration'] as $cr => $sValue)
         {
             // We extract data from the value : Day#Class#ChildID#CanteenRegistrationID
             $ArrayValues = explode('#', $sValue);
             if (count($ArrayValues) == 4)
             {
                 $ForDate = $ArrayValues[0];
                 $ChildClass = $ArrayValues[1];
                 $ChildID = $ArrayValues[2];
                 $CanteenRegistrationID = $ArrayValues[3];

                 // We check if the planning can be edited
                 // First we check if the supporter is concerned by the retrictions delays
                 $bCanEdit = FALSE;
                 if (in_array($_SESSION['SupportMemberStateID'], $CONF_CANTEEN_DELAYS_RESTRICTIONS))
                 {
                     // The supporter is concerned by restrictions
                     // The supporter can't registre a child after x months
                     if ((strtotime($ForDate) >= $TodayDateStamp) && (strtotime($ForDate) <= $LimitEditDateStamp))
                     {
                         // The limit of nb of hours is over?
                         $iNbHours = floor((strtotime(date('Y-m-d 12:00:00', strtotime($ForDate))) - $TodayCurrentTime) / 3600);
                         if ($iNbHours >= $GLOBALS['CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION'])
                         {
                             $bCanEdit = TRUE;
                         }
                     }
                 }
                 else
                 {
                     $bCanEdit = TRUE;
                 }

                 if (($bCanEdit) && (empty($CanteenRegistrationID)))
                 {
                     // New canteen registration for the child
                     // We get the info about the pork for the child and we check if the logged supporter is a parent
                     // of the child or he is an admin
                     $AdminDate = NULL;
                     if ($ArrayChildren['FamilyID'][$ArrayPosChildren[$ChildID]] != $_SESSION['FamilyID'])
                     {
                         // The logged supporter is an admin or a user with a special access and
                         // allowed to modify childern canteen registrations
                         $AdminDate = $TodayDate;
                     }

                     $CanteenRegistrationID = dbAddCanteenRegistration($DbCon, $TodayDate, $ForDate, $ChildID,
                                                                       $ArrayChildren['ChildGrade'][$ArrayPosChildren[$ChildID]],
                                                                       $ArrayChildren['ChildClass'][$ArrayPosChildren[$ChildID]],
                                                                       $ArrayChildren['ChildWithoutPork'][$ArrayPosChildren[$ChildID]],
                                                                       1, $AdminDate);

                     if ($CanteenRegistrationID > 0)
                     {
                         $bConfirmMsg = TRUE;

                         // Log event
                         logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_ADD, $_SESSION['SupportMemberID'],
                                  $CanteenRegistrationID);
                     }
                 }
                 else
                 {
                     // The canteen registration exists in the CanteenRegistrations table
                     if (($bCanEdit) && (isExistingCanteenRegistration($DbCon, $CanteenRegistrationID)))
                     {
                         if (isset($ArrayChildren['CanteenRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]))
                         {
                             if ($ArrayChildren['CanteenRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]['CanteenRegistrationID'] == $CanteenRegistrationID)
                             {
                                 // Unset this canteen registration : it's treated !
                                 unset($ArrayChildren['CanteenRegistrations'][$ArrayPosChildren[$ChildID]][$ForDate]);
                             }
                         }
                     }
                 }
             }
         }
     }

     // We treat the canteen registrations (delete)
     if ((isset($ArrayChildren['CanteenRegistrations'])) && ($DBPlanningCRC == $FormPlanningCRC))
     {
         foreach($ArrayChildren['CanteenRegistrations'] as $cr => $ArrayRegistrations)
         {
             foreach($ArrayRegistrations as $ForDate => $Registration)
             {
                 // We check if the planning can be edited
                 // First we check if the supporter is concerned by the retrictions delays
                 $bCanEdit = FALSE;
                 if (in_array($_SESSION['SupportMemberStateID'], $CONF_CANTEEN_DELAYS_RESTRICTIONS))
                 {
                     // The supporter is concerned by restrictions
                     // The supporter can't registre a child after x months
                     if ((strtotime($ForDate) >= $TodayDateStamp) && (strtotime($ForDate) <= $LimitEditDateStamp))
                     {
                         // The limit of nb of hours is over?
                         $iNbHours = floor((strtotime(date('Y-m-d 12:00:00', strtotime($ForDate))) - $TodayCurrentTime) / 3600);
                         if ($iNbHours >= $GLOBALS['CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION'])
                         {
                             $bCanEdit = TRUE;
                         }
                     }
                 }
                 else
                 {
                     $bCanEdit = TRUE;
                 }

                 if ($bCanEdit)
                 {
                     // We create the record about the canteen registration to delete
                     $RecordCanteenRegistration = array(
                                                        'CanteenRegistrationID' => $Registration['CanteenRegistrationID'],
                                                        'CanteenRegistrationDate' => $Registration['CanteenRegistrationDate'],
                                                        'CanteenRegistrationForDate' => $ForDate,
                                                        'ChildID' => $ArrayChildren['ChildID'][$cr],
                                                        'CanteenRegistrationChildGrade' => $ArrayChildren['ChildGrade'][$cr],
                                                        'CanteenRegistrationChildClass' => $ArrayChildren['ChildClass'][$cr],
                                                        'CanteenRegistrationWithoutPork' => $ArrayChildren['ChildWithoutPork'][$cr]
                                                       );

                     // No, so we can delete the canteen registration, accept if it's a valided registration
                     if (dbDeleteCanteenRegistration($DbCon, $Registration['CanteenRegistrationID']))
                     {
                         $bConfirmMsg = TRUE;

                         // Log event
                         logEvent($DbCon, EVT_CANTEEN, EVT_SERV_PLANNING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'],
                                  $Registration['CanteenRegistrationID'], array('CanteenRegistrationDetails' => $RecordCanteenRegistration));
                     }
                 }
             }
         }
     }

     unset($ArrayChildren, $ArrayPosChildren);

     //------------------ Submit button ------------------------
     //---- To registre / unregistre "more meals" for days -----
     // We get and format data about more meals (with or without prok)
     $ArrayMoreMealsWithPork = array();
     $ArrayMoreMealsWithoutPork = array();
     $ArrayPOSTFields = array_keys($_POST);
     foreach($ArrayPOSTFields as $f => $Field)
     {
         if (stripos($Field, 'sMoreMealsWithoutPork') !== FALSE)
         {
             // We have found a field in relation with more meals without pork
             $ArrayTmp = explode('_', str_replace(array('sMoreMealsWithoutPork:'), array(''), $Field));
             $ArrayMoreMealsWithoutPork[] = array('Date' => $ArrayTmp[0], 'ID' => $ArrayTmp[1],
                                                  'Quantity' => trim(strip_tags($_POST[$Field])));
         }
         elseif (stripos($Field, 'sMoreMeals') !== FALSE)
         {
             // We have found a field in relation with more meals with pork
             $ArrayTmp = explode('_', str_replace(array('sMoreMeals:'), array(''), $Field));
             $ArrayMoreMealsWithPork[] = array('Date' => $ArrayTmp[0], 'ID' => $ArrayTmp[1],
                                               'Quantity' => trim(strip_tags($_POST[$Field])));
         }
     }

     unset($ArrayPOSTFields);

     if (!empty($ArrayMoreMealsWithPork))
     {
         foreach($ArrayMoreMealsWithPork as $m => $Data)
         {
             // Quantity of more meals with pork
             $Quantity = $Data['Quantity'];
             if (empty($Quantity))
             {
                 $Quantity = 0;
             }

             // Quantity of more meals without pork
             $QuantityWithoutPork = 0;
             if (isset($ArrayMoreMealsWithoutPork[$m]))
             {
                 $QuantityWithoutPork = $ArrayMoreMealsWithoutPork[$m]['Quantity'];
             }

             $iTotalMoreMealsQuantity = $Quantity + $QuantityWithoutPork;

             // ID of the "more meals" entry
             $MoreMealForDate = $Data['Date'];
             $MoreMealID = $Data['ID'];

             if (empty($MoreMealID))
             {
                 // New entry
                 if ((!empty($iTotalMoreMealsQuantity)) && (!empty($MoreMealForDate)))
                 {
                     dbAddMoreMeal($DbCon, date('Y-m-d'), $MoreMealForDate, $_SESSION['SupportMemberID'], $Quantity,
                                   $QuantityWithoutPork);

                     $bConfirmMsg = TRUE;
                 }
             }
             else
             {
                 if (empty($iTotalMoreMealsQuantity))
                 {
                     // Delete the "more meal" entry
                     dbDeleteMoreMeal($DbCon, $MoreMealID);

                     $bConfirmMsg = TRUE;
                 }
                 else
                 {
                     // Update the "more meal" entry
                     dbUpdateMoreMeal($DbCon, $MoreMealID, NULL, $MoreMealForDate, $_SESSION['SupportMemberID'], $Quantity,
                                      $QuantityWithoutPork);

                     $bConfirmMsg = TRUE;
                 }
             }
         }
     }

     unset($ArrayMoreMealsWithPork, $ArrayMoreMealsWithoutPork);
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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_CanteenPlanning);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_TITLE, 2);

 if (!is_null($bConfirmMsg))
 {
     if ($bConfirmMsg)
     {
         // Planning updated
         openParagraph("ConfirmationMsg");
         displayStyledText($LANG_CONFIRM_CANTEEN_PLANNING_UPDATED, "ShortConfirmMsg");
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
         displayStyledText($LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_INTRODUCTION." S$StartWeek-$StartYear - S".date('W-Y', $EndDayStamp)
                           .' ('.$CONF_PLANNING_MONTHS[$StartMonth - 1].')', "");
         closeParagraph();

         // We display the planning
         displayCanteenPlanningByWeeksForm($DbCon, "CanteenPlanning.php", $Week, $Year, $CONF_ACCESS_APPL_PAGES[FCT_CANTEEN_PLANNING],
                                           $CONF_CANTEEN_VIEWS_RESTRICTIONS);
         break;

     case PLANNING_MONTH_VIEW:
     default:
         // We display the planning by month
         openParagraph();
         displayStyledText($LANG_SUPPORT_VIEW_CANTEEN_PLANNING_PAGE_INTRODUCTION." ".$CONF_PLANNING_MONTHS[$Month - 1]." $Year.", "");
         closeParagraph();

         // We display the planning
         displayCanteenPlanningByMonthForm($DbCon, "CanteenPlanning.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_CANTEEN_PLANNING],
                                           $CONF_CANTEEN_VIEWS_RESTRICTIONS);
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