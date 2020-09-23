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
 * Support module : process the creation of a new child. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2016-11-02 : load some configuration variables from database
 *     - 2017-11-07 : without pork becomes meal type
 *
 * @since 2012-01-24
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
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

         $ContinueProcess = TRUE; // Used to check that the parameters are correct

         // We get the ID of the family of the child
         $FamilyID = $_POST["hidFamilyID"];
         if ($FamilyID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // We get the values entered by the user
         $Firstname = trim(strip_tags($_POST["sFirstname"]));
         if (empty($Firstname))
         {
             $ContinueProcess = FALSE;
         }

         $Grade = strip_tags($_POST["lGrade"]);
         $Class = strip_tags($_POST["lClass"]);
         $MealType = strip_tags($_POST["lMealType"]);

         // We have to convert the desactivation date in english format (format used in the database)
         $DesactivationDate = nullFormatText(formatedDate2EngDate($_POST["desactivationDate"]), "NULL");

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             $ChildID = dbAddChild($DbCon, date('Y-m-d'), $Firstname, $FamilyID, $Grade, $Class, $MealType, $DesactivationDate);

             if ($ChildID != 0)
             {
                 // Add an entry in the history of the child
                 $SchoolYear = getSchoolYear(date('Y-m-d'));
                 dbAddHistoLevelChild($DbCon, $ChildID, $SchoolYear, $Grade, $Class, $MealType);

                 // Log event
                 logEvent($DbCon, EVT_FAMILY, EVT_SERV_CHILD, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $ChildID);

                 // The child is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_CHILD_ADDED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($ChildID)."&Id=$ChildID"; // For the redirection
             }
             else
             {
                 // The child can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_CHILD;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($Firstname))
             {
                 // The firstname is empty
                 $ConfirmationSentence = $LANG_ERROR_CHILD_FIRSTNAME;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = $QUERY_STRING; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = $QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the AddChild.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = $QUERY_STRING; // For the redirection
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Canteen/UpdateChild.php?$UrlParameters', $CONF_TIME_LAG)"
                     );

 // Content of the web page
 openArea('id="content"');

 openFrame($ConfirmationCaption);
 displayStyledText($ConfirmationSentence, $ConfirmationStyle);
 closeFrame();

 // To measure the execution script time
 if ($CONF_DISPLAY_EXECUTION_TIME_SCRIPT)
 {
     openParagraph('InfoMsg');
     initEndTime();
     displayExecutionScriptTime('ExecutionTime');
     closeParagraph();
 }

 // Close the <div> "content"
 closeArea();

 closeGraphicInterface();
?>