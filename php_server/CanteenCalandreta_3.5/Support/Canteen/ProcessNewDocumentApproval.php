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
 * Support module : process the creation of a new document approval. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2019-05-07
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
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

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

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             if (!empty($DocumentApprovalFile))
             {
                 // We move the uploaded file in the right directory
                 @move_uploaded_file($_FILES["fFilename"]["tmp_name"], $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD.$DocumentApprovalFile);
                 $UploadedFile = $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD.$DocumentApprovalFile;
             }

             // We can create the new document approval
             $DocumentApprovalID = dbAddDocumentApproval($DbCon, date('Y-m-d H:i:s'), $DocumentApprovalName, $DocumentApprovalFile, $Type);

             if ($DocumentApprovalID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_DOCUMENT_APPROVAL, EVT_SERV_DOCUMENT_APPROVAL, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $DocumentApprovalID);

                 // The document approval is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = "$LANG_CONFIRM_DOCUMENT_APPROVAL_ADDED ($DocumentApprovalID)";
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "UpdateDocumentApproval.php?Cr=".md5($DocumentApprovalID)."&Id=$DocumentApprovalID"; // For the redirection

                 // We check if if we must send a notification
                 if ((isset($CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS['NewDocument'])) && (isset($CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS['NewDocument'][Template]))
                     && (!empty($CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS['NewDocument'][Template])))
                 {
                     $EmailSubject = $LANG_NEW_DOCUMENT_APPROVAL_EMAIL_SUBJECT;
                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_DOCUMENT_APPROVAL]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_DOCUMENT_APPROVAL].$EmailSubject;
                     }

                     $DocumentUrl = $CONF_URL_SUPPORT."Canteen/UpdateDocumentApproval.php?Cr=".md5($DocumentApprovalID)."&amp;Id=$DocumentApprovalID";
                     $DocumentLink = stripslashes($DocumentApprovalName);
                     $DocumentLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     // We define the content of the mail
                     $TemplateToUse = $CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS['NewDocument'][Template];
                     $ReplaceInTemplate = array(
                                            array(
                                                  "{LANG_DOCUMENT_APPROVAL}", "{LANG_DOCUMENT_APPROVAL_TYPE}", "{LANG_DATE}", "{DocumentUrl}", "{DocumentLinkTip}",
                                                  "{DocumentLink}", "{DocumentApprovalType}", "{DocumentApprovalDate}"
                                                 ),
                                            array(
                                                  $LANG_DOCUMENT_APPROVAL, $LANG_DOCUMENT_APPROVAL_TYPE, ucfirst($LANG_DATE), $DocumentUrl, $DocumentLinkTip,
                                                  $DocumentLink, $CONF_DOCUMENTS_APPROVALS_TYPES[$Type], date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"])
                                                 )
                                           );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = array();

                     // We get e-mail addresses of all activated families for the current school year
                     $TabParams = array(
                                        "SchoolYear" => array(getSchoolYear(date('Y-m-d'))),
                                        "ActivatedChildren" => TRUE
                                       );

                     $ArrayFamilies = dbSearchFamily($DbCon, $TabParams, "FamilyLastname", 1, 0);

                     if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                     {
                         foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                         {
                             $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];

                             if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                             {
                                 $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                             }
                         }
                     }

                     unset($ArrayFamilies);

                     if ((isset($CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS['NewDocument'][Cc]))
                         && (!empty($CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS['NewDocument'][Cc]))
                        )
                     {
                         foreach($CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS['NewDocument'][Cc] as $b => $CurrentCc)
                         {
                             $MailingList["cc"][] = $CurrentCc;
                         }
                     }

                     // DEBUG MODE
                     if ($GLOBALS["CONF_MODE_DEBUG"])
                     {
                         if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                         {
                             // Without this test, there is a server mail error...
                             $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                         }
                     }

                     // We send the e-mail : now or after ?
                     if ((isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL])) && (isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DOCUMENT_APPROVAL]))
                         && (count($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DOCUMENT_APPROVAL]) == 2))
                     {
                         // The message is delayed (job)
                         $bIsEmailSent = FALSE;

                         $ArrayBccRecipients = array_chunk($MailingList["bcc"], $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DOCUMENT_APPROVAL][JobSize]);
                         $PlannedDateStamp = strtotime("+1 min", strtotime("now"));

                         $ArrayJobParams = array(
                                                 array(
                                                       "JobParameterName" => "subject",
                                                       "JobParameterValue" => $EmailSubject
                                                      ),
                                                 array(
                                                       "JobParameterName" => "template-name",
                                                       "JobParameterValue" => $TemplateToUse
                                                      ),
                                                 array(
                                                       "JobParameterName" => "replace-in-template",
                                                       "JobParameterValue" => base64_encode(serialize($ReplaceInTemplate))
                                                      )
                                                );

                         $iNbJobsCreated = 0;
                         $CurrentMainlingList = array();
                         foreach($ArrayBccRecipients as $r => $CurrentRecipients)
                         {
                             if ($r == 0)
                             {
                                 // To and CC only for the first job
                                 if (isset($MailingList["to"]))
                                 {
                                     $CurrentMainlingList['to'] = $MailingList["to"];
                                 }

                                 if (isset($MailingList["cc"]))
                                 {
                                     $CurrentMainlingList['cc'] = $MailingList["cc"];
                                 }
                             }
                             elseif ($r == 1)
                             {
                                 // To delete To and CC
                                 unset($CurrentMainlingList);
                             }

                             // Define recipients
                             $CurrentMainlingList['bcc'] = $CurrentRecipients;

                             // Create the job to send a delayed e-mail
                             $JobID = dbAddJob($DbCon, $_SESSION['SupportMemberID'], JOB_EMAIL,
                                               date('Y-m-d H:i:s', $PlannedDateStamp), NULL, 0, NULL,
                                               array_merge($ArrayJobParams,
                                                           array(array("JobParameterName" => "mailinglist",
                                                                       "JobParameterValue" => base64_encode(serialize($CurrentMainlingList)))))
                                              );

                             if ($JobID > 0)
                             {
                                 $iNbJobsCreated++;

                                 // Compute date/time for the next job
                                 $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_DOCUMENT_APPROVAL][DelayBetween2Jobs] * 60;

                                 $bIsEmailSent = TRUE;
                             }
                         }

                         unset($ArrayBccRecipients, $ArrayJobParams);
                     }
                     else
                     {
                         // We can send the e-mail
                         $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                     }
                 }
             }
             else
             {
                 // The document approval can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_DOCUMENT_APPROVAL;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = 'AddDocumentApproval.php?'.$QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($DocumentApprovalName))
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
             $UrlParameters = 'AddDocumentApproval.php?'.$QUERY_STRING; // For the redirection
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
         $UrlParameters = 'AddDocumentApproval.php?'.$QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the AddDocumentApproval.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = 'AddDocumentApproval.php?'.$QUERY_STRING; // For the redirection
 }

 if ($bIsEmailSent)
 {
     // A notification is sent
     $ConfirmationSentence .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
 }

 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      '',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Canteen/$UrlParameters', $CONF_TIME_LAG)"
                     );
 openWebPage();

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

     displaySupportMemberContextualMenu("canteen", 1, Canteen_AddDocumentApproval);
     displaySupportMemberContextualMenu("parameters", 1, 0);

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