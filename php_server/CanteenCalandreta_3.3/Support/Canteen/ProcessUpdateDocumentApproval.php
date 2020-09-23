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
 * Support module : process the update of a document approval. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2019-05-09
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted document approval ID
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
 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the document approval
         if (isExistingDocumentApproval($DbCon, $Id))
         {
             // The document approval exists
             $DocumentApprovalID = $Id;
         }
         else
         {
             // ERROR : the document approval doesn't exist
             $ContinueProcess = FALSE;
         }

         // We check if it's an update of the document to approve or a family approval to add
         if (isset($_POST['sDocumentFamilyApprovalComment']))
         {
             //**************** Family approval to add ****************//
             // We get the values entered by the user
             $DocumentFamilyApprovalComment = trim(strip_tags($_POST["sDocumentFamilyApprovalComment"]));

             if ($ContinueProcess)
             {
                 $DocumentFamilyApprovalID = dbAddDocumentFamilyApproval($DbCon, $DocumentApprovalID, $_SESSION['SupportMemberID'], date('Y-m-d H:i:s'),
                                                                         $DocumentFamilyApprovalComment);

                 if ($DocumentFamilyApprovalID != 0)
                 {
                     // Log event
                     logEvent($DbCon, EVT_DOCUMENT_APPROVAL, EVT_SERV_DOCUMENT_FAMILY_APPROVAL, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $DocumentFamilyApprovalID);

                     // The document family approval is added
                     $ConfirmationCaption = $LANG_CONFIRMATION;
                     $ConfirmationSentence = $LANG_CONFIRM_DOCUMENT_FAMILY_APPROVAL_ADDED;
                     $ConfirmationStyle = "ConfirmationMsg";
                     $UrlParameters = "Cr=".md5($DocumentApprovalID)."&Id=$DocumentApprovalID"; // For the redirection
                 }
                 else
                 {
                     // The document family approval can't be added
                     $ConfirmationCaption = $LANG_ERROR;
                     $ConfirmationSentence = $LANG_ERROR_ADD_DOCUMENT_FAMILY_APPROVAL;
                     $ConfirmationStyle = "ErrorMsg";
                     $UrlParameters = $QUERY_STRING; // For the redirection
                 }
             }
             else
             {
                 // Errors
                 $ConfirmationCaption = $LANG_ERROR;

                 if (empty($DocumentApprovalID))
                 {
                     // Wrong document approval ID
                     $ConfirmationSentence = $LANG_ERROR_WRONG_DOCUMENT_APPROVAL_ID;
                 }

                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             //**************** Document approval to update ****************//
             // We get the values entered by the user
             $CurrentDocumentApprovalFile = trim(strip_tags($_POST["hidDocumentApprovalFile"]));
             $Type = trim(strip_tags($_POST["lType"]));

             $DocumentApprovalName = trim(strip_tags($_POST["sDocumentApprovalName"]));
             if (empty($DocumentApprovalName))
             {
                 $ContinueProcess = FALSE;
             }

             // We upload the file
             $DocumentApprovalFile = "";
             if ($_FILES["fFilename"]["name"] != "")
             {
                 // We give a valide name to the uploaded file
                 $_FILES["fFilename"]["name"] = formatFilename($_FILES["fFilename"]["name"]);

                 // Check if the file owns an allowed extension
                 if (isFileOwnsAllowedExtension($_FILES["fFilename"]["name"], $CONF_UPLOAD_ALLOWED_EXTENSIONS))
                 {
                     if (is_uploaded_file($_FILES["fFilename"]["tmp_name"]))
                     {
                         $DocumentApprovalFile = $_FILES["fFilename"]["name"];

                         if ($_FILES["fFilename"]["size"] > $CONF_UPLOAD_DOCUMENTS_FILES_MAXSIZE)
                         {
                             // Error : file to big
                             $ContinueProcess = FALSE;
                         }
                     }
                 }
                 else
                 {
                     // Error : file with a not allowed extension
                     $ContinueProcess = FALSE;
                 }
             }
             else
             {
                 // No selected file : we keep the current file
                 if (empty($CurrentDocumentApprovalFile))
                 {
                     // Error : no file
                     $ContinueProcess = FALSE;
                 }
                 else
                 {
                     $DocumentApprovalFile = $CurrentDocumentApprovalFile;
                 }
             }

             // Verification that the parameters are correct
             if ($ContinueProcess)
             {
                 // Check if there is a new file
                 if ($CurrentDocumentApprovalFile != $DocumentApprovalFile)
                 {
                     // We move the uploaded file in the right directory
                     @move_uploaded_file($_FILES["fFilename"]["tmp_name"], $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD.$DocumentApprovalFile);
                     $UploadedFile = $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD.$DocumentApprovalFile;

                     // We delete the old document
                     if (file_exists($CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD.$CurrentDocumentApprovalFile))
                     {
                         unlink($CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD.$CurrentDocumentApprovalFile);
                     }
                 }

                 $DocumentApprovalID = dbUpdateDocumentApproval($DbCon, $DocumentApprovalID, NULL, $DocumentApprovalName, $DocumentApprovalFile, $Type);
                 if ($DocumentApprovalID != 0)
                 {
                     // Log event
                     logEvent($DbCon, EVT_DOCUMENT_APPROVAL, EVT_SERV_DOCUMENT_APPROVAL, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $DocumentApprovalID);

                     // The document approval is updated
                     $ConfirmationCaption = $LANG_CONFIRMATION;
                     $ConfirmationSentence = $LANG_CONFIRM_DOCUMENT_APPROVAL_UPDATED;
                     $ConfirmationStyle = "ConfirmationMsg";
                     $UrlParameters = "Cr=".md5($DocumentApprovalID)."&Id=$DocumentApprovalID"; // For the redirection
                 }
                 else
                 {
                     // The document approval can't be updated
                     $ConfirmationCaption = $LANG_ERROR;
                     $ConfirmationSentence = $LANG_ERROR_UPDATE_DOCUMENT_APPROVAL;
                     $ConfirmationStyle = "ErrorMsg";
                     $UrlParameters = $QUERY_STRING; // For the redirection
                 }
             }
             else
             {
                 // Errors
                 $ConfirmationCaption = $LANG_ERROR;

                 if (empty($DocumentApprovalID))
                 {
                     // Wrong document approval ID
                     $ConfirmationSentence = $LANG_ERROR_WRONG_DOCUMENT_APPROVAL_ID;
                 }
                 elseif (empty($DocumentApprovalName))
                 {
                     // The document name is empty
                     $ConfirmationSentence = $LANG_ERROR_DOCUMENT_APPROVAL_NAME;
                 }
                 elseif (empty($DocumentApprovalFile))
                 {
                     // The filename is empty
                     $ConfirmationSentence = $LANG_ERROR_DOCUMENT_APPROVAL_FILENAME;
                 }
                 else
                 {
                     // ERROR : some parameters are empty strings
                     $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
                 }

                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
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
     // The supporter doesn't come from the UpdateDocumentApproval.php page
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Canteen/UpdateDocumentApproval.php?$UrlParameters', $CONF_TIME_LAG)"
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