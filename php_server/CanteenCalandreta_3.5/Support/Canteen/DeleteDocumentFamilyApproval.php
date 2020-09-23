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
 * Support module : delete a family approval of a document. The supporter must be logged to
 * delete the approval.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2019-05-10
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted document family approval ID
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
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // the ID and the md5 crypted ID must be equal
     if (($Id != '') && (md5($Id) == $CryptedID))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES'));

         if (isExistingDocumentFamilyApproval($DbCon, $Id))
         {
             // We get details about the document family approval
             $RecordDocFamilyApproval = getTableRecordInfos($DbCon, "DocumentsFamiliesApprovals", $Id);

             $bCanDelete = FALSE;

             $AccessRules = $CONF_ACCESS_APPL_PAGES[FCT_DOCUMENT_APPROVAL];
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $bCanDelete = TRUE;
             }
             elseif ((isset($AccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
             {
                 // Partial read mode : we check if the logged supporter is in the same family as the supporter who approved the document
                 $SupportMemberFamilyID = getSupportMemberFieldValue($DbCon, $RecordDocFamilyApproval['SupportMemberID'], 'FamilyID');
                 if ($SupportMemberFamilyID == $_SESSION['FamilyID'])
                 {
                     $bCanDelete = TRUE;
                 }
             }

             // We delete the selected document family approval
             if (($bCanDelete) && (dbDeleteDocumentFamilyApproval($DbCon, $Id)))
             {
                 // Log event
                 logEvent($DbCon, EVT_DOCUMENT_APPROVAL, EVT_SERV_DOCUMENT_FAMILY_APPROVAL, EVT_ACT_DELETE, $_SESSION['SupportMemberID'], $Id,
                          array('ExitDocumentFamilyApprovalDetails' => $RecordDocFamilyApproval));

                 // The document family approval is deleted
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_DOCUMENT_FAMILY_APPROVAL_DELETED;
                 $ConfirmationStyle = "ConfirmationMsg";
             }
             else
             {
                 // ERROR : the document family approval isn't deleted
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DELETE_DOCUMENT_FAMILY_APPROVAL;
                 $ConfirmationStyle = "ErrorMsg";
             }

             $UrlParameters = "Cr=".md5($RecordDocFamilyApproval['DocumentApprovalID'])."&Id=".$RecordDocFamilyApproval['DocumentApprovalID']; // For the redirection
         }
         else
         {
             // ERROR : the document family approval ID is wrong
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_WRONG_DOCUMENT_FAMILY_APPROVAL_ID;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = ""; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the document family approval is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_DOCUMENT_FAMILY_APPROVAL_ID;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = ""; // For the redirection
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
     // Redirection to the document to approve
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
     // Error because the ID of the document family approval and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_DOCUMENT_FAMILY_APPROVAL_ID, 'ErrorMsg');
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