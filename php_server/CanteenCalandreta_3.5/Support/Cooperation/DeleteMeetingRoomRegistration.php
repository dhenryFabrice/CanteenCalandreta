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
 * Support module : delete a meeting room registration (if allowed by user rights). The supporter must be logged to
 * delete the meeting room registration.
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-11-27
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted meeting room registration ID
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

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // the ID and the md5 crypted ID must be equal
     if (($Id != '') && (md5($Id) == $CryptedID))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS'));

         // We get infos about the meeting room registration
         $RecordMeetingRoomRegistration = getTableRecordInfos($DbCon, "MeetingRoomsRegistrations", $Id);
         $MeetingRoomRegistrationID = 0;
         $MeetingRoomID = 0;
         $SupportMemberID = 0;
         if (!empty($RecordMeetingRoomRegistration))
         {
             $MeetingRoomRegistrationID = $Id;
             $MeetingRoomID = $RecordMeetingRoomRegistration["MeetingRoomID"];
             $SupportMemberID = $RecordMeetingRoomRegistration["SupportMemberID"];
         }

         $bCanDelete = FALSE;
         $AccessRules = $CONF_ACCESS_APPL_PAGES[FCT_MEETING];

         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], array_merge($AccessRules[FCT_ACT_CREATE], $AccessRules[FCT_ACT_UPDATE]))))
         {
             if ($_SESSION["SupportMemberStateID"] == 5)
             {
                 // For families, we check if only referents are allowed to create or update a meeting room registration
                 if ($CONF_MEETING_REGISTRATIONS_ALLOW_REGISTRATIONS_FOR_REFERENTS_ONLY)
                 {
                     if (IsWorkgroupReferent($DbCon, $_SESSION['SupportMemberID']))
                     {
                         $bCanDelete = TRUE;
                     }
                 }
                 else
                 {
                     $bCanDelete = TRUE;
                 }
             }
             else
             {
                 $bCanDelete = TRUE;
             }
         }

         if (($bCanDelete) && (isExistingMeetingRoomRegistration($DbCon, $MeetingRoomRegistrationID)))
         {
             // Only admin and the author can update the meeting room registratiion
             if ((($_SESSION['SupportMemberID'] == $SupportMemberID) && (!isMeetingRoomRegistrationClosed($DbCon, $MeetingRoomRegistrationID)))
                 || ($_SESSION['SupportMemberStateID'] == 1))
             {
                 $bCanDelete = TRUE;
             }
             else
             {
                 $bCanDelete = FALSE;
             }
         }
         else
         {
             $bCanDelete = FALSE;
         }

         // We delete the selected meeting room registration
         if ($bCanDelete)
         {
             // Yes, we can delete this meeting room registration
             if (dbDeleteMeetingRoomRegistration($DbCon, $MeetingRoomRegistrationID))
             {
                 // Log event
                 logEvent($DbCon, EVT_MEETING, EVT_SERV_MEETING, EVT_ACT_DELETE, $_SESSION['SupportMemberID'], $MeetingRoomRegistrationID,
                          array('MeetingRoomRegistrationDetails' => $RecordMeetingRoomRegistration));

                 // The meeting room registration is deleted
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_MEETING_ROOM_REGISTRATION_DELETED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "MeetingRoomsPlanning.php"; // For the redirection

                 $RecordMeetingRoom = getTableRecordInfos($DbCon, "MeetingRooms", $MeetingRoomID);

                 // Check if a notification must be sent to the e-mail of the concerned meeting room
                 if ((!empty($RecordMeetingRoom['MeetingRoomEmail'])) && (isset($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyRoomEmail']))
                     && (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyRoomEmail'][Template])))
                 {
                     // Yes !
                     $EmailSubject = $LANG_MEETING_ROOM_REGISTRATION_DELETED_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING].$EmailSubject;
                     }

                     // We define the content of the mail
                     $TemplateToUse = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyRoomEmail'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_MEETING_ROOM_REGISTRATION}", "{LANG_MEETING_ROOM_NAME}", "{MeetingRoomName}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_DATE}", "{MeetingRoomRegistrationStartDate}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_TIME}", "{MeetingRoomRegistrationStartTime}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_END_TIME}", "{MeetingRoomRegistrationEndTime}", "{MeetingRoomRegistrationTitle}"
                                                     ),
                                                array(
                                                      $LANG_MEETING_ROOM_REGISTRATION, $LANG_MEETING_ROOM_NAME, $RecordMeetingRoom['MeetingRoomName'],
                                                      $LANG_MEETING_ROOM_REGISTRATION_START_DATE,
                                                      date($CONF_DATE_DISPLAY_FORMAT, strtotime($RecordMeetingRoomRegistration['MeetingRoomRegistrationStartDate'])),
                                                      $LANG_MEETING_ROOM_REGISTRATION_START_TIME,
                                                      date($FormatTime, strtotime($RecordMeetingRoomRegistration['MeetingRoomRegistrationStartDate'])),
                                                      $LANG_MEETING_ROOM_REGISTRATION_END_TIME,
                                                      date($FormatTime, strtotime($RecordMeetingRoomRegistration['MeetingRoomRegistrationEndDate'])),
                                                      stripslashes($RecordMeetingRoomRegistration['MeetingRoomRegistrationTitle'])
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $MailingList["to"] = array($RecordMeetingRoom['MeetingRoomEmail']);
                     $MailingList["cc"] = array();
                     if (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyRoomEmail'][Cc]))
                     {
                         $MailingList["cc"] = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyRoomEmail'][Cc];
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
                 if ((!empty($RecordMeetingRoomRegistration['MeetingRoomRegistrationMailingList']))
                     && (isset($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyMailingList']))
                     && (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyMailingList'][Template])))
                 {
                     // Yes !
                     $EmailSubject = $LANG_MEETING_ROOM_REGISTRATION_DELETED_EMAIL_SUBJECT;

                     if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING]))
                     {
                         $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MEETING].$EmailSubject;
                     }

                     // We define the content of the mail
                     $TemplateToUse = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyMailingList'][Template];
                     $ReplaceInTemplate = array(
                                                array(
                                                      "{LANG_MEETING_ROOM_REGISTRATION}", "{LANG_MEETING_ROOM_NAME}", "{MeetingRoomName}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_DATE}", "{MeetingRoomRegistrationStartDate}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_START_TIME}", "{MeetingRoomRegistrationStartTime}",
                                                      "{LANG_MEETING_ROOM_REGISTRATION_END_TIME}", "{MeetingRoomRegistrationEndTime}", "{MeetingRoomRegistrationTitle}"
                                                     ),
                                                array(
                                                      $LANG_MEETING_ROOM_REGISTRATION, $LANG_MEETING_ROOM_NAME, $RecordMeetingRoom['MeetingRoomName'],
                                                      $LANG_MEETING_ROOM_REGISTRATION_START_DATE,
                                                      date($CONF_DATE_DISPLAY_FORMAT, strtotime($RecordMeetingRoomRegistration['MeetingRoomRegistrationStartDate'])),
                                                      $LANG_MEETING_ROOM_REGISTRATION_START_TIME,
                                                      date($FormatTime, strtotime($RecordMeetingRoomRegistration['MeetingRoomRegistrationStartDate'])),
                                                      $LANG_MEETING_ROOM_REGISTRATION_END_TIME,
                                                      date($FormatTime, strtotime($RecordMeetingRoomRegistration['MeetingRoomRegistrationEndDate'])),
                                                      stripslashes($RecordMeetingRoomRegistration['MeetingRoomRegistrationTitle'])
                                                     )
                                               );

                     // Get the recipients of the e-mail notification
                     $ArrayMails = explode(',', $RecordMeetingRoomRegistration['MeetingRoomRegistrationMailingList']);
                     foreach($RecordMeetingRoomRegistration['MeetingRoomRegistrationMailingList'] as $m => $CurrentMail)
                     {
                         $ArrayMails[$m] = trim($CurrentMail);
                     }

                     $MailingList["to"] = $ArrayMails;
                     $MailingList["cc"] = array();
                     if (!empty($CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyMailingList'][Cc]))
                     {
                         $MailingList["cc"] = $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS['DeleteRegistrationNotifyMailingList'][Cc];
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
                 // ERROR : the meeting room registration isn't deleted
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_DELETE_MEETING_ROOM_REGISTRATION;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = "MeetingRoomsPlanning.php"; // For the redirection
             }
         }
         else
         {
             // Error : the user isn't allowed to delete the meeting room registration (because of wrong rights)
             $ConfirmationCaption = $LANG_ERROR;
             $ConfirmationSentence = $LANG_ERROR_DELETE_MEETING_ROOM_REGISTRATION_NOT_ALLOWED;
             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = "MeetingRoomsPlanning.php"; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the meeting room registration ID is wrong
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_WRONG_MEETING_ROOM_REGISTRATION_ID;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = "MeetingRoomsPlanning.php"; // For the redirection
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

 if ($bIsEmailSent)
 {
     // A notification is sent
     $ConfirmationSentence .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
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
     // Redirection to the planning of meeting rooms or to the meeting room registration details (if error)
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
     // Error because the ID of the meeting room registration ID and the crypted ID don't match
     openFrame($LANG_ERROR);
     displayStyledText($LANG_ERROR_WRONG_MEETING_ROOM_REGISTRATION_ID, 'ErrorMsg');
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