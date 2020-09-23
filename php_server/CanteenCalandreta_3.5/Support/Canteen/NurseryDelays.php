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
 * Support module : record a nursery delay of a child. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2014-02-03
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

 if (!empty($_POST["lMonth"]))
 {
     $Month = (integer)strip_tags($_POST["lMonth"]);
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     $ParamsPOST_GET = "_GET";
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

 // Get the selected child
 $ChildID = NULL;
 if (!empty(${$ParamsPOST_GET}["lChildID"]))
 {
     $ChildID = ${$ParamsPOST_GET}["lChildID"];
 }

 // No message
 $bConfirmMsg = NULL;

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["bSubmit"]))
 {
     // Record the nursery delay for the selected nursery registration
     // We get selected values
     $ChildID = trim(strip_tags($_POST['lChildID']));
     $NurseryRegistrationID = trim(strip_tags($_POST['lNurseryRegistrationID']));

     if (isExistingNurseryRegistration($DbCon, $NurseryRegistrationID))
     {
         $RecordNurseryRegistration = getTableRecordInfos($DbCon, 'NurseryRegistrations', $NurseryRegistrationID);
         if ($ChildID == $RecordNurseryRegistration['ChildID'])
         {
             // We change just the "Islate" field value
             $NurseryRegistrationID = dbUpdateNurseryRegistration($DbCon, $NurseryRegistrationID, NULL,
                                                                  $RecordNurseryRegistration['NurseryRegistrationForDate'], $ChildID,
                                                                  NULL, NULL, NULL, NULL, NULL, NULL, 1);

             if ($NurseryRegistrationID > 0)
             {
                 $bConfirmMsg = TRUE;

                 // Log event
                 logEvent($DbCon, EVT_NURSERY, EVT_SERV_DELAY, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $NurseryRegistrationID);
             }
         }
     }

     // We reinit the selected child
     $ChildID = NULL;
 }
 //################################ END FORM PROCESSING ##########################

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
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#NurseryDelaysChildren', 'Accessibility');

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_NurseryDelays);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_VIEW_NURSERY_DELAYS_PAGE_TITLE, 2);

 if (!is_null($bConfirmMsg))
 {
     if ($bConfirmMsg)
     {
         // Nursery delay recorded
         openParagraph("ConfirmationMsg");
         displayStyledText($LANG_CONFIRM_NURSERY_DELAY_ADDED, "ShortConfirmMsg");
         closeParagraph();
     }
 }

 // We display the form to enter a nursery delay
 openParagraph();
 displayStyledText($LANG_SUPPORT_VIEW_NURSERY_DELAYS_PAGE_INTRODUCTION." <strong>".$CONF_PLANNING_MONTHS[$Month - 1]." $Year</strong>.", "");
 closeParagraph();

 displayNurseryDelayForm($DbCon, "NurseryDelays.php", $Month, $Year, $ChildID, $CONF_ACCESS_APPL_PAGES[FCT_NURSERY_PLANNING]);

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