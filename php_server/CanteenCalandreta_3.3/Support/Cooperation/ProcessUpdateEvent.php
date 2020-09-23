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
 * Support module : process the update of an event. Can send a message to registered families too.
 * The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2016-03-04 : send messages to families in "bcc"
 *     - 2016-09-09 : allow to send notifications to concerned families with a delay (jobs)
 *                    and load some configuration variables from database
 *     - 2018-02-09 : taken into account "Inhibition" parameter of $CONF_COOP_EVENT_NOTIFICATIONS
 *
 * @since 2013-04-05
 */

 // Include the graphic primitives library
  require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted event ID
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
 $bHasChanged = FALSE;
 $iNbJobsCreated = 0;

 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the event
         if (isExistingEvent($DbCon, $Id))
         {
             // The event exists
             $EventID = $Id;
         }
         else
         {
             // ERROR : the event doesn't exist
             $ContinueProcess = FALSE;
         }

         // We get the event type
         $TypeID = $_POST["lEventTypeID"];
         if ($TypeID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // Author
         $SupportMemberID = strip_tags($_POST['hidSupportMemberID']);

         // Parent event ID
         $ParentID = $_POST["lParentEventID"];

         $Title = strip_tags($_POST["sTitle"]);
         if (empty($Title))
         {
             $ContinueProcess = FALSE;
         }

         // We get the town
         $TownID = $_POST["lTownID"];
         if ($TownID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }

         // Start date
         $StartDate = nullFormatText(formatedDate2EngDate($_POST["startDate"]), "NULL");
         if (is_Null($StartDate))
         {
             $ContinueProcess = FALSE;
         }

         $StartTime = nullFormatText(strip_tags($_POST["hStartTime"]), "NULL");
         if (!empty($StartTime))
         {
             // To have hh:mm:ss
             $ArrayStartTime = explode(':', $StartTime);
             $StartTime = implode(':', $ArrayStartTime).str_repeat(":00", 3 - count($ArrayStartTime));
             unset($ArrayStartTime);
         }

         // End date
         $EndDate = nullFormatText(formatedDate2EngDate($_POST["endDate"]), "NULL");
         if (empty($EndDate))
         {
             $EndDate = $StartDate;
         }

         $EndTime = nullFormatText(strip_tags($_POST["hEndTime"]), "NULL");
         if (!empty($EndTime))
         {
             // To have hh:mm:ss
             $ArrayEndTime = explode(':', $EndTime);
             $EndTime = implode(':', $ArrayEndTime).str_repeat(":00", 3 - count($ArrayEndTime));
             unset($ArrayEndTime);
         }

         // We check if start date <= end date
         if (($ContinueProcess) && (strtotime($StartDate) > strtotime($EndDate)))
         {
             // Error : start date > end date
             $ContinueProcess = FALSE;
         }

         $MaxParticipants = nullFormatText(strip_tags($_POST["sNbMaxParticipants"]), "NULL");
         if ((is_null($MaxParticipants)) || ((integer)$MaxParticipants < 0))
         {
             $ContinueProcess = FALSE;
         }

         if ($CONF_COOP_EVENT_USE_REGISTRATION_CLOSING_DATE)
         {
             // The registration delay isn't a number of days but a date
             $RegistrationClosingDate = nullFormatText(formatedDate2EngDate($_POST["registrationClosingDate"]), "NULL");
             if (!empty($StartDate))
             {
                 if (empty($RegistrationClosingDate))
                 {
                     $RegistrationDelay = 0;
                 }
                 else
                 {
                     $RegistrationDelay = getNbDaysBetween2Dates(strtotime($RegistrationClosingDate), strtotime($StartDate), FALSE);
                 }
             }
         }
         else
         {
             // The registration delay is a number of days
             $RegistrationDelay = nullFormatText(strip_tags($_POST["sRegistrationDelay"]), "NULL");
         }

         if ((is_null($RegistrationDelay)) || ((integer)$RegistrationDelay < 0))
         {
             $ContinueProcess = FALSE;
         }

         $Description = formatText($_POST["sDescription"]);
         if (empty($Description))
         {
             $ContinueProcess = FALSE;
         }

         // We have to convert the closing date in english format (format used in the database)
         $ClosingDate = nullFormatText(formatedDate2EngDate($_POST["closingDate"]), "NULL");

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // Get the previous values of nb of members (with or without power)
             $RecordOldEvent = getTableRecordInfos($DbCon, "Events", $EventID);

             // Check if the max number of participants, in the case of a child event, is correct
             if (!empty($ParentID))
             {
                 // We get the tree of events from the parent event of the current event
                 $ArrayTreeEvents = getEventsTree($DbCon, $ParentID, array(), 'EventStartDate');
                 if (isset($ArrayTreeEvents['EventID']))
                 {
                     $ParentMaxParticipants = $ArrayTreeEvents['EventMaxParticipants'][0];

                     // Attention : the tree contains the parent too and the current event !
                     $SumMaxParticipantsChildEvents = max(0, array_sum($ArrayTreeEvents['EventMaxParticipants'])
                                                                       - $ParentMaxParticipants - $RecordOldEvent['EventMaxParticipants']);
                     if ($SumMaxParticipantsChildEvents + $MaxParticipants > $ParentMaxParticipants)
                     {
                         // We auto limit the nb max of participants for the current child event
                         $MaxParticipants = max(1, $ParentMaxParticipants - $SumMaxParticipantsChildEvents);
                     }
                 }

                 unset($ArrayTreeEvents);
             }

             // We get children of the current event
             $ArrayEventChildren = array();
             $ArrayTreeEvents = getEventsTree($DbCon, $EventID, array(), 'EventStartDate');
             if (isset($ArrayTreeEvents['EventID']))
             {
                 foreach($ArrayTreeEvents["EventID"] as $e => $CurrentEventID)
                 {
                     if ($CurrentEventID != $EventID)
                     {
                         // Keep the chield
                         $ArrayEventChildren[$CurrentEventID] = $ArrayTreeEvents["EventTitle"][$e];
                     }
                 }
             }

             unset($ArrayTreeEvents);

             // We can update the event with the new values
             $EventID = dbUpdateEvent($DbCon, $EventID, NULL, $SupportMemberID, $Title, $StartDate, $EndDate, $Description, $TypeID,
                                      $TownID, $MaxParticipants, $RegistrationDelay, $StartTime, $EndTime, $ClosingDate, $ParentID);
             if ($EventID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_EVENT, EVT_SERV_EVENT, EVT_ACT_UPDATE, $_SESSION['SupportMemberID'], $EventID);

                 // We check if the event was closed
                 if (!empty($ClosingDate))
                 {
                     // Yes, the event was closed : we must closed children too
                     foreach($ArrayEventChildren as $CurrentEventChildID => $CurrentEventChildTitle)
                     {
                         dbUpdateEvent($DbCon, $CurrentEventChildID, NULL, NULL, $CurrentEventChildTitle, NULL, NULL, NULL, NULL,
                                       NULL, NULL, NULL, NULL, NULL, $ClosingDate, NULL);
                     }
                 }

                 // The event is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_EVENT_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($EventID)."&Id=$EventID"; // For the redirection

                 // Check the important values have changed
                 $RecordEvent = getTableRecordInfos($DbCon, "Events", $EventID);
                 $ArrayImportantEventFields = array("EventStartDate");
                 foreach($ArrayImportantEventFields as $f => $Field)
                 {
                     if ($RecordEvent[$Field] !== $RecordOldEvent[$Field])
                     {
                         $bHasChanged = TRUE;
                         break;
                     }
                 }

                 // Check if we must send a notification because the event has changed (only for parent event and if there is
                 // at least one registered and activated family)
                 $ArrayRegisteredFamilies = dbSearchEventRegistration($DbCon, array("EventID" => $EventID,
                                                                                    "Activated" => TRUE), "FamilyLastname", 1, 0);

                 $PlannedDateStamp = NULL;

                 $bCanSendNotification = FALSE;
                 if ((empty($ParentID)) && ($bHasChanged) && (isset($ArrayRegisteredFamilies['EventRegistrationID']))
                     && (!empty($ArrayRegisteredFamilies['EventRegistrationID'])) && (isset($CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent']))
                     && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent'][Template])))
                 {
                     $bCanSendNotification = TRUE;

                     // We check if there is an inhibition
                     if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent'][Inhibition]))
                         && (in_array($TypeID, $CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent'][Inhibition])))
                     {
                         // No notification for this event type
                         $bCanSendNotification = FALSE;
                     }
                 }

                 // We can send a notification ?
                 if ($bCanSendNotification)
                 {
                     $EmailSubject = $LANG_UPDATED_EVENT_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                     }

                     $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($EventID)."&amp;Id=$EventID";
                     $EventLink = stripslashes($Title);
                     $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     $RecordTown = getTableRecordInfos($DbCon, 'Towns', $TownID);
                     $TownName = $RecordTown['TownName'];
                     $TownCode = $RecordTown['TownCode'];
                     unset($RecordTown);

                     // We define the content of the mail
                     $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_EVENT}", "{EventUrl}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                                      "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}",
                                                      "{OldEventStartDate}"
                                                     ),
                                                array(
                                                      $LANG_EVENT, $EventUrl, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName, $TownCode,
                                                      $LANG_EVENT_START_DATE,
                                                      date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($StartDate)),
                                                      date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordOldEvent['EventStartDate']))
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = array();
                     $MailingList["bcc"] = array();
                     foreach($CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent'][To] as $rt => $RecipientType)
                     {
                         $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType);
                         if (!empty($ArrayRecipients))
                         {
                             $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
                         }
                     }

                     if (!empty($CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent'][Cc]))
                     {
                         $MailingList["cc"] = array();
                         foreach($CONF_COOP_EVENT_NOTIFICATIONS['UpdatedEvent'][Cc] as $rt => $RecipientType)
                         {
                             $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType);
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

                 // Check if there is a message to send
                 $sMessageToSend = formatText($_POST["sMessage"]);
                 $ArrayRecipientsFamilies = $_POST['chkMsgFamilies'];

                 $bCanSendNotification = FALSE;
                 if ((!empty($sMessageToSend)) && (!empty($ArrayRecipientsFamilies)) &&
                     isset($CONF_COOP_EVENT_NOTIFICATIONS['CommunicationEvent'])
                     && (!empty($CONF_COOP_EVENT_NOTIFICATIONS['CommunicationEvent'][Template])))
                 {
                     $bCanSendNotification = TRUE;

                     // We check if there is an inhibition
                     if ((isset($CONF_COOP_EVENT_NOTIFICATIONS['CommunicationEvent'][Inhibition]))
                         && (in_array($TypeID, $CONF_COOP_EVENT_NOTIFICATIONS['CommunicationEvent'][Inhibition])))
                     {
                         // No notification for this event type
                         $bCanSendNotification = FALSE;
                     }
                 }

                 if ($bCanSendNotification)
                 {
                     // Yes !
                     $EmailSubject = $LANG_COMMUNICATION_EVENT_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
                     }

                     $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($EventID)."&amp;Id=$EventID";
                     $EventLink = stripslashes($Title);
                     $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     $RecordTown = getTableRecordInfos($DbCon, 'Towns', $TownID);
                     $TownName = $RecordTown['TownName'];
                     $TownCode = $RecordTown['TownCode'];
                     unset($RecordTown);

                     // We define the content of the mail
                     $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS['CommunicationEvent'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_EVENT}", "{EventUrl}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                                      "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}",
                                                      "{MessageToSend}"
                                                     ),
                                                array(
                                                      $LANG_EVENT, $EventUrl, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName, $TownCode,
                                                      $LANG_EVENT_START_DATE,
                                                      date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($StartDate)),
                                                      stripslashes(stripslashes($sMessageToSend))
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = array();
                     $MailingList["bcc"] = array();
                     foreach($ArrayRecipientsFamilies as $FamID)
                     {
                         $RecordFamily = getTableRecordInfos($DbCon, 'Families', $FamID);
                         $MailingList["bcc"][] = $RecordFamily['FamilyMainEmail'];
                         if (!empty($RecordFamily['FamilySecondEmail']))
                         {
                             $MailingList["bcc"][] = $RecordFamily['FamilySecondEmail'];
                         }
                     }

                     // E-mail of the author of the event
                     $RecordSupporter = getSupportMemberInfos($DbCon, $SupportMemberID);
                     $sReplyTo = $RecordSupporter['SupportMemberEmail'];
                     unset($RecordSupporter, $RecordFamily);

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

                         if (empty($PlannedDateStamp))
                         {
                             $PlannedDateStamp = strtotime("+1 min", strtotime("now"));
                         }
                         else
                         {
                             // We plan after the previous job
                             $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT][DelayBetween2Jobs] * 60;
                         }

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
                                                      ),
                                                 array(
                                                       "JobParameterName" => "reply-to",
                                                       "JobParameterValue" => $sReplyTo
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
                         $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate, array(),
                                                   "", $sReplyTo);
                     }
                 }
             }
             else
             {
                 // The event can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_EVENT;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if ($TypeID == 0)
             {
                 // No town
                 $ConfirmationSentence = $LANG_ERROR_EVENT_TYPE;
             }
             elseif (empty($Title))
             {
                 // The title is empty
                 $ConfirmationSentence = $LANG_ERROR_EVENT_TITLE;
             }
             elseif ($TownID == 0)
             {
                 // No town
                 $ConfirmationSentence = $LANG_ERROR_TOWN;
             }
             elseif (empty($StartDate))
             {
                 // No start date
                 $ConfirmationSentence = $LANG_ERROR_START_DATE;
             }
             elseif (strtotime($StartDate) > strtotime($EndDate))
             {
                 // Wrong start/end dates
                 $ConfirmationSentence = $LANG_ERROR_WRONG_START_END_DATES;
             }
             elseif ((!empty($MaxParticipants)) && ((integer)$MaxParticipants < 0))
             {
                 // Wrong nb max participants
                 $ConfirmationSentence = $LANG_ERROR_WRONG_EVENT_MAX_PARTICIPANTS;
             }
             elseif ((!empty($RegistrationDelay)) && ((integer)$RegistrationDelay < 0))
             {
                 // Wrong regitration delay
                 $ConfirmationSentence = $LANG_ERROR_WRONG_EVENT_REGISTRATION_DELAY;
             }
             elseif (empty($Description))
             {
                 // The description is empty
                 $ConfirmationSentence = $LANG_ERROR_EVENT_DESCRIPTION;
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
     // The supporter doesn't come from the UpdateEvent.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = $QUERY_STRING; // For the redirection
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/UpdateEvent.php?$UrlParameters', $CONF_TIME_LAG)"
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