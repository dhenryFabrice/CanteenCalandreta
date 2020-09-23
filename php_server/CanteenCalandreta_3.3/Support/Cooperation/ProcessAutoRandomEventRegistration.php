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
 * Support module : process the creation of several new event registrations by auto randodom
 * selecting families. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2019-06-17
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
 $bIsEmailSent = FALSE;
 $iNbJobsCreated = 0;

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Connection to the database
     $DbCon = dbConnection();

     // Get the event ID
     $EventID = strip_tags(trim($_GET['Id']));
     $CryptedID = strip_tags(trim($_GET['Cr']));

     if ((md5($EventID) == $CryptedID) && (isExistingEvent($DbCon, $EventID)))
     {
         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS'));

         $ContinueProcess = false; // used to check that the parameters are correct

         $SupportMemberID = $_SESSION['SupportMemberID'];

         // Get infos about the event : we get the nb max participants
         $RecordEvent = getTableRecordInfos($DbCon, 'Events', $EventID);

         // We have to select several families not registered in other events of the same type during the current school year
         $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
         $ArraySameTypesEvents = dbSearchEvent($DbCon, array('EventChildrenIncluded' => TRUE,
                                                             'EventTypeID' => array($RecordEvent['EventTypeID']),
                                                             'SchoolYear' => array($CurrentSchoolYear)), "EventStartDate", 1, 0);

         $ArrayRegisteredFamilies = array();
         $ArraySelectedFamilies = array();
         $iNbRegisteredFamiliesOnCurrentEvent = 0;
         if ((isset($ArraySameTypesEvents['EventID'])) && (!empty($ArraySameTypesEvents['EventID'])))
         {
             // We get families registered in events of same type
             $ArrayEventRegistrations = dbSearchEventRegistration($DbCon, array('EventID' => $ArraySameTypesEvents['EventID']), "EventRegistrationID", 1, 0);
             if ((isset($ArrayEventRegistrations['EventRegistrationID'])) && (!empty($ArrayEventRegistrations['EventRegistrationID'])))
             {
                 foreach($ArrayEventRegistrations['EventRegistrationID'] as $er => $EventRegistrationID)
                 {
                     if (!in_array($ArrayEventRegistrations['FamilyID'][$er], $ArrayRegisteredFamilies))
                     {
                         $ArrayRegisteredFamilies[] = $ArrayEventRegistrations['FamilyID'][$er];
                     }

                     if ($ArrayEventRegistrations['EventID'][$er] == $EventID)
                     {
                         $iNbRegisteredFamiliesOnCurrentEvent++;
                     }
                 }
             }
         }

         // Now, we get activated families
         $ArrayActivatedFamilies = dbSearchFamily($DbCon, array('SchoolYear' => array($CurrentSchoolYear),
                                                                'ActivatedChildren' => TRUE), "FamilyLastname", 1, 0);
         if ((isset($ArrayActivatedFamilies['FamilyID'])) && (!empty($ArrayActivatedFamilies['FamilyID'])))
         {
             $ArraySelectedFamilies = array_diff($ArrayActivatedFamilies['FamilyID'], $ArrayRegisteredFamilies);

             // Random shuffle families
             shuffle($ArraySelectedFamilies);

             // We keep only the nb max of participants of the event
             $ArraySelectedFamilies = array_slice($ArraySelectedFamilies, 0, max(0, $RecordEvent['EventMaxParticipants'] - $iNbRegisteredFamiliesOnCurrentEvent));
         }

         $bCanSendNotification = FALSE;
         if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'])) && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Template])))
         {
             $bCanSendNotification = TRUE;

             // We check if there is an inhibition
             if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Inhibition]))
                 && (in_array($RecordEvent['EventTypeID'], $CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Inhibition])))
             {
                 // No notification for this event type
                 $bCanSendNotification = FALSE;
             }
             else
             {
                 // There is a notification : we set the content of the e-mail
                 $EmailSubject = $LANG_NEW_EVENT_REGISTRATION_EMAIL_SUBJECT;

                 if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                 {
                     $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                 }

                 $EventLink = stripslashes($RecordEvent['EventTitle']);
                 $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                 $RecordTown = getTableRecordInfos($DbCon, 'Towns', $RecordEvent['TownID']);
                 $TownName = $RecordTown['TownName'];
                 $TownCode = $RecordTown['TownCode'];
                 unset($RecordTown);

                 // We define the content of the mail
                 $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Template];
                 $ReplaceInTemplate = array(
                                            array(
                                                  "{LANG_EVENT}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                                  "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}"
                                                 ),
                                            array(
                                                  $LANG_EVENT, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName, $TownCode,
                                                  $LANG_EVENT_START_DATE,
                                                  date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordEvent['EventStartDate']))
                                                 )
                                           );
             }
         }

         // Verification that the parameters are correct
         if (!empty($ArraySelectedFamilies))
         {
             $iNbErrors = 0;
             foreach($ArraySelectedFamilies as $f => $FamilyID)
             {
                 $EventRegistrationID = dbAddEventRegistration($DbCon, date('Y-m-d H:i:s'), $EventID, $FamilyID, $SupportMemberID, 1,
                                                               $LANG_EVENT_ADD_AUTO_RANDOM_REGISTRATION_COMMENT);

                 if ($EventRegistrationID != 0)
                 {
                     // Log event
                     logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT_REGISTRATION, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $EventRegistrationID);

                     // We can send a notification ?
                     if ($bCanSendNotification)
                     {
                         // Yes
                         $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEventRegistration.php?Cr=".md5($EventRegistrationID)."&amp;Id=$EventRegistrationID";
                         $ReplaceInTemplate[0][] = "{EventUrl}";
                         $ReplaceInTemplate[1][] = $EventUrl;

                         // Get the recipients of the e-mail notification
                         $MailingList["to"] = array();
                         $MailingList["bcc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][To] as $rt => $RecipientType)
                         {
                             $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType, $FamilyID);
                             if (!empty($ArrayRecipients))
                             {
                                 $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                             }
                         }

                         if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Cc]))
                         {
                             $MailingList["cc"] = array();
                             foreach($CONF_COOP_EVENT_NOTIFICATIONS['FamilyRegisteredEvent'][Cc] as $rt => $RecipientType)
                             {
                                 $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType, $FamilyID);
                                 if (!empty($ArrayRecipients))
                                 {
                                     $MailingList["cc"] = array_merge($MailingList["cc"], $ArrayRecipients);
                                 }
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
                         if ((isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL])) && (isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT]))
                             && (count($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT]) == 2))
                         {
                             // The message is delayed (job)
                             $bIsEmailSent = FALSE;

                             $ArrayBccRecipients = array_chunk($MailingList["bcc"], $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT][JobSize]);
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
                                     $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT][DelayBetween2Jobs] * 60;

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
                     $iNbErrors++;
                 }
             }

             if ($iNbErrors == 0)
             {
                 // The event registration is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_EVENT_REGISTRATION_ADDED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "UpdateEvent.php?Cr=".md5($EventID)."&Id=$EventID"; // For the redirection
             }
             else
             {
                 // The event registration can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_EVENT_REGISTRATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "UpdateEvent.php?".$QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($EventID))
             {
                 // The event is empty
                 $ConfirmationSentence = $LANG_ERROR_EVENT_REGISTRATION_EVENT;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "UpdateEvent.php?".$QUERY_STRING; // For the redirection
         }
     }
     else
     {
         // ERROR : wrong event ID
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_VIEW_EVENT_REGISTRATION;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = "UpdateEvent.php?".$QUERY_STRING; // For the redirection
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
     $UrlParameters = "UpdateEvent.php?".$QUERY_STRING; // For the redirection
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
                      'WhitePage',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/$UrlParameters', $CONF_TIME_LAG)"
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