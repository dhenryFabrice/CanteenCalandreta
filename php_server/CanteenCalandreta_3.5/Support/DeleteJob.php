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
 * Support module : allow a supporter to delete a job from a table (view). The supporter must be logged
 * to delete the job.
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2017-09-27
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted job ID
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

 if (!empty($_GET["Return"]))
 {
     $BackPage = (string)strip_tags($_GET["Return"]);
     if ((!empty($_GET["RCr"])) && (!empty($_GET["RId"])))
     {
         $CryptedReturnID = (string)strip_tags($_GET["RCr"]);
         $ReturnId = (string)strip_tags($_GET["RId"]);
     }
     else
     {
         $BackPage = "MessagesJobsList.php";
     }
 }
 else
 {
     $BackPage = "MessagesJobsList.php";
 }

 //################################ FORM PROCESSING ##########################
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // the ID and the md5 crypted ID must be equal
     if (($Id != '') && (md5($Id) == $CryptedID))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // We get infos about the job
         $RecordJob = getTableRecordInfos($DbCon, "Jobs", $Id);

         // The supporter must be allowed to access to jobs list
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         $AccessRules = $CONF_ACCESS_APPL_PAGES[FCT_MESSAGE];
         $bCanDelete = FALSE;

         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
             $bCanDelete = TRUE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_UPDATE;
             $bCanDelete = TRUE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }
         elseif ((isset($AccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
         {
             // Partial read mode
             $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
         }

         if ($bCanDelete)
         {
             // We delete the selected job
             if (dbDeleteJob($DbCon, $Id))
             {
                 // The job is deleted
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_RECORD_DELETED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = $CONF_ROOT_DIRECTORY."Support/$BackPage?Cr=$CryptedReturnID&Id=$ReturnId"; // For the redirection
             }
             else
             {
                 // ERROR : the job isn't deleted
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DELETE_RECORD;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $CONF_ROOT_DIRECTORY."Support/$BackPage"; // For the redirection
             }
         }
         else
         {
             // ERROR : the job isn't deleted because the logged supporter isn't allowed
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_DELETE_RECORD_NOT_ALLOWED;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = $CONF_ROOT_DIRECTORY."Support/$BackPage"; // For the redirection
         }
     }
     else
     {
         // ERROR : the job ID is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_RECORD_ID;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = $CONF_ROOT_DIRECTORY."Support/$BackPage"; // For the redirection
     }
 }
 else
 {
     // ERROR : the supporter isn't logged
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = ""; // For the redirection
 }

 // Release the connection to the database
 dbDisconnection($DbCon);

 //################################ END FORM PROCESSING ##########################

 // Redirection to the view of jobs of the logged supporter
 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            'Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('$UrlParameters', $CONF_TIME_LAG)"
                     );

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
     // Error because the ID of the job ID and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_RECORD_ID, 'ErrorMsg');
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