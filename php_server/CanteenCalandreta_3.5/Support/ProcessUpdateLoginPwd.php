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
 * Support module : process the update of a supporter login and password
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2005-02-16 : redirect the user to the login page index.php if he isn't loggued
 *     - 2005-04-05 : taken into account the confirmation of the password
 *     - 2007-01-16 : new interface
 *     - 2010-03-05 : log events
 *     - 2016-03-01 : Param_Profil
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2004-08-20
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

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
         $SupportMemberID = $_SESSION["SupportMemberID"];

         // Connection to the database
         $DbCon = dbConnection();

         $NewLogin = strip_tags($_POST["hidEncLogin"]);
         $NewPassword = strip_tags($_POST["hidEncPassword"]);
         $ConfirmPassword = strip_tags($_POST["hidEncConfirmPassword"]);

         // Verification that the parameters are correct
         if (($NewLogin != "") && ($NewPassword != "") && ($NewPassword == $ConfirmPassword))
         {
             // We update the supporter login and password
             if (dbSetLoginPwdSupportMember($DbCon, $SupportMemberID, $NewLogin, $NewPassword))
             {
                 // Log event
                 logEvent($DbCon, EVT_PROFIL, EVT_SERV_LOGIN, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $_SESSION['SupportMemberID']);

                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_UPDATE_LOGIN_PWD;
                 $ConfirmationStyle = "ConfirmationMsg";
             }
             else
             {
                 // Wrong parameters
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_LOGIN_PWD;
                 $ConfirmationStyle = "ErrorMsg";
             }
         }
         else
         {
             // Wrong parameters
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_NO_LOGIN_PWD;
             $ConfirmationStyle = "ErrorMsg";
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // The supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";
     }
 }
 else
 {
     // The supporter doesn't come from the Profil.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            'Styles_Support.css' => 'screen'
                           ),
                      array('Verifications.js')
                     );
 openWebPage();

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu();

 // Content of the web page
 openArea('id="content"');

 // Display the "parameters" contextual menus if the supporter is logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("parameters", 0, Param_Profil);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
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