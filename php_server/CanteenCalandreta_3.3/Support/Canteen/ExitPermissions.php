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
 * Support module : display exit permissions of children for the selected day to the logged supporter
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-07-10
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

 if (!empty($_POST["lDay"]))
 {
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     $ParamsPOST_GET = "_GET";
 }

 // To take into account the day to display
 if (!empty(${$ParamsPOST_GET}["lDay"]))
 {
     // We get the given day
     $ArrayYearMonthDay = explode("-", strip_tags(${$ParamsPOST_GET}["lDay"]));

     $Year = (integer)$ArrayYearMonthDay[0];
     if ($Year > date("Y"))
     {
         $Year = date("Y");
     }

     $Month = (integer)$ArrayYearMonthDay[1];
     if (($Month < 1) || ($Month > 12))
     {
         // Wrong month : we get the current month
         $Month = date("m");
     }

     $Day = (integer)$ArrayYearMonthDay[2];
     if (($Day < 1) || (($Month == date("m")) && ($Day > date("t"))))
     {
         // Wrong day : we get the current day
         $Day = date("d");
     }
 }
 else
 {
     // We get the current day
     $Year = date("Y");
     $Month = date("m");
     $Day = date("d");
 }

 // Patch for the day < 10 : the number of the day must be on 2 digits
 if ($Day < 10)
 {
     // The cast in integer must be done for php5
     $Day = "0".(integer)$Day;
 }

 // Patch for the month < 10 : the number of the day must be on 2 digits
 if ($Month < 10)
 {
     // The cast in integer must be done for php5
     $Month = "0".(integer)$Month;
 }

 $SelectedDate = "$Year-$Month-$Day";

 //################################ FORM PROCESSING ##########################
 $bConfirmMsg = NULL;
 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         $ContinueProcess = TRUE; // used to check that the parameters are correct

         $SelectedDate = strip_tags($_POST["hidDay"]);

         // We get the values entered by the user
         $ChildID = trim(strip_tags($_POST['lChildID']));

         $ExitPermissionName = trim(strip_tags($_POST["sLastname"]));
         if (empty($ExitPermissionName))
         {
             $ContinueProcess = FALSE;
         }

         // We check if the person is an authorized person
         $AuthorizedPerson = existedPOSTFieldValue("chkAuthorizedPerson", NULL);
         if (is_null($AuthorizedPerson))
         {
             $AuthorizedPerson = 0;
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             $ExitPermissionID = dbAddExitPermission($DbCon, $SelectedDate, $ChildID, $ExitPermissionName, $AuthorizedPerson);
             if ($ExitPermissionID > 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_EXIT_PERMISSION, EVT_SERV_PLANNING, EVT_ACT_CREATE, $_SESSION['SupportMemberID'],
                          $ExitPermissionID);

                 $bConfirmMsg = TRUE;
             }
             else
             {
                 // Error
                 $bConfirmMsg = FALSE;
             }
         }
         else
         {
             // Error
             $bConfirmMsg = FALSE;
         }
     }
 }
 //################################ END FORM PROCESSING ######################

 // We have to print the exit permissions?
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
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#lDay', 'Accessibility');

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

         displaySupportMemberContextualMenu("canteen", 1, Canteen_ExitPermissions);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_VIEW_EXIT_PERMISSIONS_PAGE_TITLE, 2);

     if (!is_null($bConfirmMsg))
     {
         if ($bConfirmMsg)
         {
             // Exit permission created
             openParagraph("ConfirmationMsg");
             displayStyledText($LANG_CONFIRM_EXIT_PERMISSION_ADDED, "ShortConfirmMsg");
             closeParagraph();
         }
         else
         {
             // Planning created with errors
             openParagraph("ErrorMsg");
             displayStyledText($LANG_ERROR_ADD_EXIT_PERMISSION, "ErrorMsg");
             closeParagraph();
         }
     }

     openParagraph();
     displayStyledText($LANG_SUPPORT_VIEW_EXIT_PERMISSIONS_PAGE_INTRODUCTION);
     closeParagraph();

     // We display the exit permissions list
     displayExitPermissionsListForm($DbCon, "ExitPermissions.php", $SelectedDate, $CONF_ACCESS_APPL_PAGES[FCT_EXIT_PERMISSION],
                                    $CONF_EXIT_PERMISSIONS_VIEWS_RESTRICTIONS);

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

     printExitPermissionsList($DbCon, "ExitPermissions.php", $SelectedDate, $CONF_ACCESS_APPL_PAGES[FCT_EXIT_PERMISSION],
                              $CONF_EXIT_PERMISSIONS_VIEWS_RESTRICTIONS);

     closeArea();
     closeWebPage();
     closeGraphicInterface();

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>