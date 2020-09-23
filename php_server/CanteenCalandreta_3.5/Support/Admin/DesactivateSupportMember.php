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
 * Support module : desactivate a support member profil (if allowed by user rights). The supporter must be logged to
 * desactivate the profil.
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
 if ((isSet($_SESSION["SupportMemberID"])) && (isAdmin()))
 {
     // the ID and the md5 crypted ID must be equal
     if (($Id != '') && (md5($Id) == $CryptedID))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES'));

         // We get the support member infos
         $RecordSupportMember = getSupportMemberInfos($DbCon, $Id);
         $SupportMemberID = 0;
         if (!empty($RecordSupportMember))
         {
             $SupportMemberID = $RecordSupportMember["SupportMemberID"];
         }

         $bCanDelete = FALSE;

         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if ((isset($CONF_ACCESS_APPL_PAGES[FCT_ADMIN][FCT_ACT_CREATE]))
             && (in_array($_SESSION["SupportMemberStateID"], $CONF_ACCESS_APPL_PAGES[FCT_ADMIN][FCT_ACT_CREATE])))
         {
             // Creation mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($CONF_ACCESS_APPL_PAGES[FCT_ADMIN][FCT_ACT_UPDATE]))
             && (in_array($_SESSION["SupportMemberStateID"], $CONF_ACCESS_APPL_PAGES[FCT_ADMIN][FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }

         switch($cUserAccess)
         {
             case FCT_ACT_CREATE:
             case FCT_ACT_UPDATE:
                 $bCanDelete = TRUE;
                 break;
         }

         // We desactivate the selected support member
         if ($bCanDelete)
         {
             // We update the supporter profil
             $id = dbUpdateSupportMember($DbCon, $SupportMemberID, $RecordSupportMember['SupportMemberLastname'],
                                         $RecordSupportMember['SupportMemberFirstname'], $RecordSupportMember['SupportMemberEmail'],
                                         $RecordSupportMember['SupportMemberStateID'], $RecordSupportMember['SupportMemberPhone'],
                                         0, $RecordSupportMember['FamilyID']);
             if ($id != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_PROFIL, EVT_SERV_PROFIL, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $SupportMemberID);

                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_DESACTIVATE_PROFIL;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Admin/SupportMembersList.php"; // For the redirection
             }
             else
             {
                 // Wrong parameters
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DESACTIVATE_PROFIL;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "Admin/SupportMembersList.php"; // For the redirection
             }
         }
         else
         {
             // Error : the user isn't allowed to desactivate a support member profil (because of wrong rights)
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "Admin/SupportMembersList.php"; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the support member ID is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_SUPPORTMEMBER_ID;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = "Admin/SupportMembersList.php"; // For the redirection
     }
 }
 else
 {
     // ERROR : the supporter isn't logged
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = "Admin/SupportMembersList.php"; // For the redirection
 }
 //################################ END FORM PROCESSING ##########################

 if ($UrlParameters == '')
 {
     // No redirection
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array('../Verifications.js'),
                          'WhitePage'
                         );
 }
 else
 {
     // Redirection to the details of the support members list
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                          'WhitePage',
                          "Redirection('".$CONF_ROOT_DIRECTORY."Support/$UrlParameters', $CONF_TIME_LAG)"
                         );
 }

 // Content of the web page
 openArea('id="content"');

 // the ID and the md5 crypted ID must be equal
 if (($Id != '') && (md5($Id) == $CryptedID))
 {
     openFrame($ConfirmationCaption);
     displayStyledText($ConfirmationSentence, $ConfirmationStyle);
     closeFrame();
 }
 else
 {
     // Error because the ID of the supportmember ID and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_SUPPORTMEMBER_ID, 'ErrorMsg');
     closeFrame();
 }

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