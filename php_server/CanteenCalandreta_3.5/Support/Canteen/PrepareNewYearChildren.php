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
 * Support module : display the form to prepare activated children to the next school year
 * to the logged supporter (ex : change of grade and classroom)
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2014-08-06
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

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

 //################################ FORM PROCESSING ##########################
 $sErrorMsg = '';
 $sConfirmationMsg = '';
 $iNbUpdatedChildren = 0;
 $iNbNotUpdatedChildren = 0;
 $iNbDesactivatedChildren = 0;

 // Compute the next school year and the desactivation date for the children who leave the school
 $CurrentDate = date('Y-m-d');
 $CurrentStamp = strtotime($CurrentDate);
 $SchoolYear = getSchoolYear($CurrentDate);
 $StartDateSchoolYear = getSchoolYearStartDate($SchoolYear);
 if (isset($CONF_SCHOOL_YEAR_START_DATES[$SchoolYear]))
 {
     if (($CurrentStamp >= strtotime($StartDateSchoolYear)) && ($CurrentStamp <= strtotime($CONF_SCHOOL_YEAR_START_DATES[$SchoolYear])))
     {
         // StartDateSchoolYear is several days before the real start date of the school (in general, 1 month before)
         $NextSchoolYear = $SchoolYear;
         $DesactivationDate = getSchoolYearEndDate($SchoolYear - 1);
     }
     else
     {
         $NextSchoolYear = $SchoolYear + 1;
         $DesactivationDate = getSchoolYearEndDate($SchoolYear);
     }
 }
 else
 {
     $NextSchoolYear = $SchoolYear;
     $DesactivationDate = getSchoolYearEndDate($SchoolYear - 1);
 }

 if (!empty($_POST["bSubmit"]))
 {
     // Treat the form
     if (!empty($_POST))
     {
         // Compute association grade / classroom
         $ArrayGradesClassrooms = array();
         if (isset($CONF_CLASSROOMS[$NextSchoolYear]))
         {
             $iNbClassrooms = count($CONF_CLASSROOMS[$NextSchoolYear]);
             foreach($CONF_GRADES as $g => $Grade)
             {
                 // Search the grade in classrooms
                 $i = 0;
                 $bFound = FALSE;
                 while(($i < $iNbClassrooms) && (!$bFound))
                 {
                     if (stripos($CONF_CLASSROOMS[$NextSchoolYear][$i], $Grade) !== FALSE)
                     {
                         // Grade found : we stop the search
                         $bFound = TRUE;
                         $ArrayGradesClassrooms[$g] = $i;
                     }

                     $i++;
                 }
             }
         }

         // We must have the association grade / classroom
         if (!empty($ArrayGradesClassrooms))
         {
             foreach($_POST as $Key => $Value)
             {
                 // Extract ChildID (ex : radChild_10)
                 $ChildID = 0;
                 $ArrayTmp = explode('_', $Key);

                 if (count($ArrayTmp) == 2)
                 {
                     $ChildID = $ArrayTmp[1];
                 }

                 if ($ChildID > 0)
                 {
                     // Get info about the child
                     $ChildRecord = getTableRecordInfos($DbCon, 'Children', $ChildID);
                     if (isset($ChildRecord['ChildID']))
                     {
                         switch($Value)
                         {
                             case 1:
                                 // The child go to the next grade
                                 $ChildGrade = $ChildRecord['ChildGrade'] + 1;
                                 $ChildClass = $ArrayGradesClassrooms[$ChildGrade];

                                 $ChildID = dbUpdateChild($DbCon, $ChildID, NULL, $ChildRecord['ChildFirstname'], $ChildRecord['FamilyID'],
                                                          $ChildGrade, $ChildClass, NULL, NULL);

                                 if ($ChildID > 0)
                                 {
                                     $HistoChildID = dbAddHistoLevelChild($DbCon, $ChildID, $NextSchoolYear, $ChildGrade, $ChildClass,
                                                                          $ChildRecord['ChildWithoutPork']);

                                     // Log event
                                     logEvent($DbCon, EVT_FAMILY, EVT_SERV_CHILD, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $ChildID);

                                     $iNbUpdatedChildren++;
                                 }
                                 break;

                             case 2:
                                 // The child stay in the same grade (but the classroom can change)
                                 $ChildGrade = $ChildRecord['ChildGrade'];
                                 $ChildClass = $ArrayGradesClassrooms[$ChildGrade];

                                 $ChildID = dbUpdateChild($DbCon, $ChildID, NULL, $ChildRecord['ChildFirstname'], $ChildRecord['FamilyID'],
                                                          $ChildGrade, $ChildClass, NULL, NULL);

                                 if ($ChildID > 0)
                                 {
                                     $HistoChildID = dbAddHistoLevelChild($DbCon, $ChildID, $NextSchoolYear, $ChildGrade, $ChildClass,
                                                                          $ChildRecord['ChildWithoutPork']);

                                     // Log event
                                     logEvent($DbCon, EVT_FAMILY, EVT_SERV_CHILD, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $ChildID);

                                     $iNbNotUpdatedChildren++;
                                 }
                                 break;

                             case 3:
                                 // The child leaves the school : he is desactivated
                                 $ChildID = dbUpdateChild($DbCon, $ChildID, NULL, $ChildRecord['ChildFirstname'], $ChildRecord['FamilyID'],
                                                          NULL, NULL, NULL, $DesactivationDate);

                                 if ($ChildID > 0)
                                 {
                                     // Log event
                                     logEvent($DbCon, EVT_FAMILY, EVT_SERV_CHILD, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $ChildID);

                                     $iNbDesactivatedChildren++;
                                 }
                                 break;
                         }
                     }
                 }
             }
         }
     }

     $sConfirmationMsg = $LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_NB_TREATED_CHILDREN
                         ."<strong>$iNbUpdatedChildren</strong> / <strong>$iNbNotUpdatedChildren</strong> / <strong>$iNbDesactivatedChildren</strong>\n";
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array()
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#ChildrenList', 'Accessibility');

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_PrepareNewYearChildren);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_TITLE, 2);

 // Check if there is an error message to display
 if (!empty($sErrorMsg))
 {
     openParagraph('ErrorMsg');
     echo $sErrorMsg;
     closeParagraph();
 }
 elseif (!empty($sConfirmationMsg))
 {
     openParagraph('ConfirmationMsg');
     displayStyledText($sConfirmationMsg, 'ShortConfirmMsg');
     closeParagraph();
 }

 // We prepare children to the next school year
 openParagraph();
 displayStyledText($LANG_SUPPORT_PREPARE_NEW_YEAR_CHILDREN_PAGE_INTRODUCTION." <strong>".($NextSchoolYear - 1)."-$NextSchoolYear</strong>.", "");
 closeParagraph();

 // We display the form to prepare children to the next school year
 displayPrepareNewYearChildrenForm($DbCon, "PrepareNewYearChildren.php", $NextSchoolYear, $CONF_ACCESS_APPL_PAGES[FCT_FAMILY]);

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