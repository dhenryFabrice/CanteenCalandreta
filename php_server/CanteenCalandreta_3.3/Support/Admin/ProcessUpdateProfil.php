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
 * Support module : process the update of a support member (profil). The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-10-24
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted support member ID
 // Crypted ID
 if (!empty($_GET["Cr"]))
 {
     $CryptedID = (string)strip_tags($_GET["Cr"]);
 }
 else
 {
     $CryptedID = "";
 }

 // No-crypted ID
 if (!empty($_GET["Id"]))
 {
     $Id = (string)strip_tags($_GET["Id"]);
 }
 else
 {
     $Id = "";
 }

 //################################ FORM PROCESSING ##########################
 $bIsEmailSent = FALSE;

 if (!empty($_POST["bSubmit"]))
 {
     if ((isSet($_SESSION["SupportMemberID"])) && (isAdmin()))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS'));

         $ContinueProcess = TRUE; // Used to check that the parameters are correct

         // We identify the support member
         if (isExistingSupportMember($DbCon, $Id))
         {
             // The support member exists
             $SupportMemberID = $Id;
         }
         else
         {
             // ERROR : the support member doesn't exist
             $ContinueProcess = FALSE;
         }

         $Lastname = trim(strip_tags($_POST["sLastname"]));
         if (empty($Lastname))
         {
             $ContinueProcess = FALSE;
         }

         $Firstname = trim(strip_tags($_POST["sFirstname"]));
         if (empty($Firstname))
         {
             $ContinueProcess = FALSE;
         }

         $Email = trim(strip_tags($_POST["sEmail"]));
         if (!isValideEmailAddress($Email))
         {
             // Wrong e-mail
             $ContinueProcess = FALSE;
         }

         $Phone = trim(strip_tags($_POST["sPhoneNumber"]));

         $UserStateID = existedPOSTFieldValue("lSupportMemberStateID", strip_tags($_POST["hidUserStateID"]));
         $FamilyID = existedPOSTFieldValue("lFamilyID", strip_tags($_POST["hidFamilyID"]));

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We update the supporter profil : auto-reactivation of the profil !
             $SupportMemberID = dbUpdateSupportMember($DbCon, $SupportMemberID, $Lastname, $Firstname, $Email, $UserStateID, $Phone, 1,
                                                      $FamilyID);
             if ($SupportMemberID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_PROFIL, EVT_SERV_PROFIL, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $_SESSION['SupportMemberID']);

                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_UPDATE_PROFIL;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($SupportMemberID)."&Id=$SupportMemberID"; // For the redirection
             }
             else
             {
                 // Wrong parameters
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_PROFIL;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if ((empty($Lastname)) || (empty($Firstname)) || (!isValideEmailAddress($Email)))
             {
                 // The lastname/firstname is empty or the e-mail address is wrong
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_PROFIL;
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
     // The supporter doesn't come from the UpdateSupportMember.php page
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Admin/UpdateSupportMember.php?$UrlParameters', $CONF_TIME_LAG)"
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