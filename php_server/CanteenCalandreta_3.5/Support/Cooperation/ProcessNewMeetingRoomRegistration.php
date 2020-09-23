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
 * Support module : process the creation of a new meeting room registration. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-11-25
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

         // We get the values entered by the user
         // We get the selected meeting room
         $MeetingRoomID = strip_tags($_POST["lMeetingRoomID"]);
         if ($MeetingRoomID == 0)
         {
             // Error
             $ContinueProcess = FALSE;
         }
         else
         {
             if (isExistingMeetingRoom($DbCon, $MeetingRoomID))
             {
                 $RecordMeetingRoom = getTableRecordInfos($DbCon, 'MeetingRooms', $MeetingRoomID);
             }
             else
             {
                 // Error
                 $ContinueProcess = FALSE;
             }
         }

         // Author
         $SupportMemberID = strip_tags($_POST['hidSupportMemberID']);

         // Event ID
         $EventID = $_POST["lEventID"];

         // Title of the registration
         $Title = trim(strip_tags($_POST["sTitle"]));
         if (empty($Title))
         {
             $ContinueProcess = FALSE;
         }

         // Start date
         $StartDate = nullFormatText(formatedDate2EngDate($_POST["startDate"]), "NULL");
         if (is_Null($StartDate))
         {
             $ContinueProcess = FALSE;
         }

         // Start time and end time
         $StartTime = strip_tags($_POST["lStartTime"]);
         if (empty($StartTime))
         {
             // Error : no start time
             $ContinueProcess = FALSE;
         }

         $EndTime = strip_tags($_POST["lEndTime"]);
         if (empty($EndTime))
         {
             // Error : no end time
             $ContinueProcess = FALSE;
         }

         $EndDate = "$StartDate $EndTime:00";
         $StartDate = "$StartDate $StartTime:00";

         // We check if start date <= end date
         if (($ContinueProcess) && (strtotime($StartDate) > strtotime($EndDate)))
         {
             // Error : start date > end date
             $ContinueProcess = FALSE;
         }

         // We check the mailing-list
         $bWrongMailingList = FALSE;
         $MailingListToNotify = trim(strip_tags($_POST["sMailingList"]));
         $ArrayMails = array();
         if (!empty($MailingListToNotify))
         {
             $ArrayMails = explode(',', $MailingListToNotify);
             foreach($ArrayMails as $i => $CurrentMail)
             {
                 $CurrentMail = trim($CurrentMail);
                 $ArrayMails[$i] = $CurrentMail;

                 if (!isValideEmailAddress($CurrentMail))
                 {
                     $bWrongMailingList = TRUE;
                 }
             }
         }

         // We get the description
         $Description = formatText($_POST["sDescription"]);

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We can create the new meeting room registration
             $MeetingRoomRegistrationID = dbAddMeetingRoomRegistration($DbCon, date('Y-m-d H:i:s'), $SupportMemberID, $Title, $StartDate, $EndDate, $MeetingRoomID,
                                                                       $MailingListToNotify, $Description, $EventID);

             if ($MeetingRoomRegistrationID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_MEETING, EVT_SERV_MEETING, EVT_ACT_CREATE, $_SESSION['SupportMemberID'], $MeetingRoomRegistrationID);

                 // The meeting room registration is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = "$LANG_CONFIRM_MEETING_ROOM_REGISTRATION_ADDED ($MeetingRoomRegistrationID)";
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "UpdateMeetingRoomRegistration.php?Cr=".md5($MeetingRoomRegistrationID)."&Id=$MeetingRoomRegistrationID"; // For the redirection

                 $ArrayFormatTime = explode($GLOBALS['CONF_TIME_SEPARATOR'], $GLOBALS['CONF_TIME_DISPLAY_FORMAT']);
                 if (count($ArrayFormatTime) >= 2)
                 {
                     $FormatTime = $ArrayFormatTime[0].$GLOBALS['CONF_TIME_SEPARATOR'].$ArrayFormatTime[1];
                 }
                 else
                 {
                     $FormatTime = $ArrayFormatTime[0];
                 }

                 if (!empty($EventID))
                 {
                     // Get info about the linked event
                     $RecordEvent = getTableRecordInfos($DbCon, 'Events', $EventID);
                 }

                 // Check if a notification must be sent to the e-mail of the concerned meeting room
                 if ((!empty($RecordMeetingRoom['MeetingRoomEmail'])) && (isset($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyRoomEmail']))
                     && (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyRoomEmail'][Template])))
                 {
                     // Yes !
                     $EmailSubject = $LANG_NEW_MEETING_ROOM_REGISTRATION_TO_MEETING_ROOM_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING].$EmailSubject;
                     }

                     $MeetingRoomRegistrationUrl = $CONF_URL_SUPPORT."Cooperation/UpdateMeetingRoomRegistration.php?Cr=".md5($MeetingRoomRegistrationID)."&amp;Id=$MeetingRoomRegistrationID";
                     $MeetingRoomRegistrationLink = stripslashes($Title);
                     $MeetingRoomResgistrationLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     // Check if there is a linked event
                     $MeetingRoomRegistrationEvent = '';
                     if (!empty($EventID))
                     {
                         $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($EventID)."&amp;Id=$EventID";
                         $EventLink = stripslashes($RecordEvent['EventTitle']);
                         $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;
                         $MeetingRoomRegistrationEvent = "<p>$LANG_EVENT : <a href=\"$EventUrl\" title=\"$EventLinkTip\">$EventLink</a>.</p>";
                     }

                     // We define the content of the mail
                     $TemplateToUse = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_MEETING_ROOM_REGISTRATION}", "{MeetingRoomRegistrationUrl}", "{MeetingRoomResgistrationLinkTip}",
                                                      "{MeetingRoomRegistrationLink}", "{LANG_MEETING_ROOM_NAME}", "{MeetingRoomName}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_DATE}", "{MeetingRoomRegistrationStartDate}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_TIME}", "{MeetingRoomRegistrationStartTime}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_END_TIME}", "{MeetingRoomRegistrationEndTime}", "{MeetingRoomRegistrationTitle}",
                                                      "{MeetingRoomRegistrationEvent}"
                                                     ),
                                                array(
                                                      $LANG_MEETING_ROOM_REGISTRATION, $MeetingRoomRegistrationUrl, $MeetingRoomResgistrationLinkTip, $MeetingRoomRegistrationLink,
                                                      $LANG_MEETING_ROOM_NAME, $RecordMeetingRoom['MeetingRoomName'], $LANG_MEETING_ROOM_REGISTRATION_START_DATE,
                                                      date($CONF_DATE_DISPLAY_FORMAT, strtotime($StartDate)), $LANG_MEETING_ROOM_REGISTRATION_START_TIME,
                                                      date($FormatTime, strtotime($StartDate)), $LANG_MEETING_ROOM_REGISTRATION_END_TIME,
                                                      date($FormatTime, strtotime($EndDate)), stripslashes($Title), $MeetingRoomRegistrationEvent
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = array($RecordMeetingRoom['MeetingRoomEmail']);
                     $MailingList["cc"] = array();
                     if (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList'][Cc]))
                     {
                         $MailingList["cc"] = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList'][Cc];
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

                     // We can send the e-mail
                     $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                 }

                 // Check if a notification must be sent to the mailing-list
                 if ((!empty($MailingListToNotify)) && (isset($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList']))
                     && (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList'][Template])))
                 {
                     // Yes !
                     $EmailSubject = $LANG_NEW_MEETING_ROOM_REGISTRATION_TO_MAILING_LIST_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING].$EmailSubject;
                     }

                     $MeetingRoomRegistrationUrl = $CONF_URL_SUPPORT."Cooperation/UpdateMeetingRoomRegistration.php?Cr=".md5($MeetingRoomRegistrationID)."&amp;Id=$MeetingRoomRegistrationID";
                     $MeetingRoomRegistrationLink = stripslashes($Title);
                     $MeetingRoomResgistrationLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

                     // Check if there is a linked event
                     $MeetingRoomRegistrationEvent = '';
                     if (!empty($EventID))
                     {
                         $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($EventID)."&amp;Id=$EventID";
                         $EventLink = stripslashes($RecordEvent['EventTitle']);
                         $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;
                         $MeetingRoomRegistrationEvent = "<p>$LANG_EVENT : <a href=\"$EventUrl\" title=\"$EventLinkTip\">$EventLink</a>.</p>";
                     }

                     // We define the content of the mail
                     $TemplateToUse = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_MEETING_ROOM_REGISTRATION}", "{MeetingRoomRegistrationUrl}", "{MeetingRoomResgistrationLinkTip}",
                                                      "{MeetingRoomRegistrationLink}", "{LANG_MEETING_ROOM_NAME}", "{MeetingRoomName}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_DATE}", "{MeetingRoomRegistrationStartDate}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_TIME}", "{MeetingRoomRegistrationStartTime}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_END_TIME}", "{MeetingRoomRegistrationEndTime}", "{MeetingRoomRegistrationTitle}",
                                                      "{MeetingRoomRegistrationEvent}"
                                                     ),
                                                array(
                                                      $LANG_MEETING_ROOM_REGISTRATION, $MeetingRoomRegistrationUrl, $MeetingRoomResgistrationLinkTip, $MeetingRoomRegistrationLink,
                                                      $LANG_MEETING_ROOM_NAME, $RecordMeetingRoom['MeetingRoomName'], $LANG_MEETING_ROOM_REGISTRATION_START_DATE,
                                                      date($CONF_DATE_DISPLAY_FORMAT, strtotime($StartDate)), $LANG_MEETING_ROOM_REGISTRATION_START_TIME,
                                                      date($FormatTime, strtotime($StartDate)), $LANG_MEETING_ROOM_REGISTRATION_END_TIME,
                                                      date($FormatTime, strtotime($EndDate)), stripslashes($Title), $MeetingRoomRegistrationEvent
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = $ArrayMails;
                     $MailingList["cc"] = array();
                     if (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList'][Cc]))
                     {
                         $MailingList["cc"] = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['NewRegistrationNotifyMailingList'][Cc];
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

                     // We can send the e-mail
                     $bIsEmailSent = sendEmail($_SESSION, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
                 }
             }
             else
             {
                 // The metting room registration can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_MEETING_ROOM_REGISTRATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = 'CreateMeetingRoomRegistration.php?'.$QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if ($MeetingRoomID == 0)
             {
                 // No meeting room
                 $ConfirmationSentence = $LANG_ERROR_MEETING_ROOM_NAME;
             }
             elseif (empty($Title))
             {
                 // The title is empty
                 $ConfirmationSentence = $LANG_ERROR_MEETING_ROOM_TITLE;
             }
             elseif (empty($StartDate))
             {
                 // No start date
                 $ConfirmationSentence = $LANG_ERROR_MEETING_ROOM_START_DATE;
             }
             elseif (strtotime($StartDate) >= strtotime($EndDate))
             {
                 // Wrong start/end dates
                 $ConfirmationSentence = $LANG_ERROR_MEETING_ROOM_WRONG_START_END_TIMES;
             }
             elseif ($bWrongMailingList)
             {
                 // The mailing-list has at least one wrong e-mail address
                 $ConfirmationSentence = $LANG_ERROR_EVENT_DESCRIPTION;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = 'CreateMeetingRoomRegistration.php?'.$QUERY_STRING; // For the redirection
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
         $UrlParameters = 'CreateMeetingRoomRegistration.php?'.$QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the CreateMeetingRoomRegistration.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = 'CreateMeetingRoomRegistration.php?'.$QUERY_STRING; // For the redirection
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
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/Cooperation/$UrlParameters', $CONF_TIME_LAG)"
                     );
 openWebPage();

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu(1);

 // Content of the web page
 openArea('id="content"');

 // Display the "Cooperation" and the "parameters" contextual menus if the supporter isn't logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("cooperation", 1, Coop_CreateMeetingRoomRegistration);
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