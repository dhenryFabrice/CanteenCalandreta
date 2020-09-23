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
 * Support module : process the update of a supporter login and password by admin
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-10-28
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
     if ((isSet($_SESSION["SupportMemberID"])) && (isAdmin()))
     {
         $ContinueProcess = TRUE;

         // Connection to the database
         $DbCon = dbConnection();

         $SupportMemberID = trim(strip_tags($_POST["hidSupportMemberID"]));

         // We identify the support member
         if (!isExistingSupportMember($DbCon, $SupportMemberID))
         {
             // ERROR : the support member doesn't exist
             $ContinueProcess = FALSE;
         }

         $NewLogin = strip_tags($_POST["hidEncLogin"]);
         $NewPassword = strip_tags($_POST["hidEncPassword"]);
         $ConfirmPassword = strip_tags($_POST["hidEncConfirmPassword"]);

         if ((empty($NewLogin)) || (empty($NewPassword)) || ($NewPassword != $ConfirmPassword))
         {
             // ERROR : wrong login or wrong password
             $ContinueProcess = FALSE;
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We update the supporter login and password
             if (dbSetLoginPwdSupportMember($DbCon, $SupportMemberID, $NewLogin, $NewPassword))
             {
                 // Log event
                 logEvent($DbCon, EVT_PROFIL, EVT_SERV_LOGIN, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $SupportMemberID);

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

         $UrlParameters = "UpdateSupportMember.php?Cr=".md5($SupportMemberID)."&Id=$SupportMemberID";

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // The supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";

         $UrlParameters = 'SupportMembersList.php';
     }
 }
 else
 {
     // The supporter doesn't come from the UpdateSupportMember.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";

     $UrlParameters = 'SupportMembersList.php';
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Admin/$UrlParameters', $CONF_TIME_LAG)"
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