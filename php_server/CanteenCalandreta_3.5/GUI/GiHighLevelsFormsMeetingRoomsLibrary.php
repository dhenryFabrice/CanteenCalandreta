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
 * Interface module : XHTML Graphic high level forms library used to manage the meeting rooms
 * and meeting rooms registrations.
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-11-22
 */


/**
 * Display the form to submit a new meeting room registration or update a meeitng room registration,
 * in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-22
 *
 * @param $DbConnection                 DB object             Object of the opened database connection
 * @param $MeetingRoomRegistrationID    String                ID of the meting room registration to display
 * @param $ProcessFormPage              String                URL of the page which will process the form
 * @param $ArrayCreationParams          Mixed array           Parameters to use to create the registration
 *                                                            (meeting room ID, start date, end date...)
 * @param $AccessRules                  Array of Integers     List used to select only some support members
 *                                                            allowed to create, update or view meeting room registration
 */
 function displayDetailsMeetingRoomRegistrationForm($DbConnection, $MeetingRoomRegistrationID, $ProcessFormPage, $AccessRules = array(), $ArrayCreationParams = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a meeting room registration
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($MeetingRoomRegistrationID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 if ($_SESSION["SupportMemberStateID"] == 5)
                 {
                     // For families, we check if only referents are allowed to create or update a meeting room registration
                     if ($GLOBALS['CONF_MEETING_REGISTRATIONS_ALLOW_REGISTRATIONS_FOR_REFERENTS_ONLY'])
                     {
                         if (IsWorkgroupReferent($DbConnection, $_SESSION['SupportMemberID']))
                         {
                             $cUserAccess = FCT_ACT_CREATE;
                         }
                     }
                     else
                     {
                         $cUserAccess = FCT_ACT_CREATE;
                     }
                 }
                 else
                 {
                     $cUserAccess = FCT_ACT_CREATE;
                 }
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 if ($_SESSION["SupportMemberStateID"] == 5)
                 {
                     // For families, we check if only referents are allowed to create or update a meeting room registration
                     if ($GLOBALS['CONF_MEETING_REGISTRATIONS_ALLOW_REGISTRATIONS_FOR_REFERENTS_ONLY'])
                     {
                         $bContinue = TRUE;
                         if (IsWorkgroupReferent($DbConnection, $_SESSION['SupportMemberID']))
                         {
                             if (isExistingMeetingRoomRegistration($DbConnection, $MeetingRoomRegistrationID))
                             {
                                 // We get the author of the meeting room regsitration
                                 $MeetingRoomRegistrationRecord = getTableRecordInfos($DbConnection, "MeetingRoomsRegistrations", $MeetingRoomRegistrationID);

                                 // Only admin and the author can update the meeting room registratiion
                                 if (($_SESSION['SupportMemberID'] == $MeetingRoomRegistrationRecord['SupportMemberID'])
                                     || ($_SESSION['SupportMemberStateID'] == 1))
                                 {
                                     $cUserAccess = FCT_ACT_UPDATE;
                                     $bContinue = FALSE;
                                 }
                             }
                         }

                         if (($bContinue) && (isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
                         {
                             // Read mode
                             $cUserAccess = FCT_ACT_READ_ONLY;
                             $bContinue = FALSE;
                         }
                         elseif (($bContinue) && (isset($AccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
                         {
                             // Partial read mode
                             $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
                             $bContinue = FALSE;
                         }
                     }
                     else
                     {
                         $cUserAccess = FCT_ACT_UPDATE;
                     }
                 }
                 else
                 {
                     $cUserAccess = FCT_ACT_UPDATE;
                 }
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
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsMeeting", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationMeetingRoomRegistration('".$GLOBALS["LANG_ERROR_JS_MEETING_ROOM_NAME"]."', '".$GLOBALS['LANG_ERROR_JS_MEETING_ROOM_TITLE']
                                                           ."', '".$GLOBALS["LANG_ERROR_JS_MEETING_ROOM_START_DATE"]."', '".$GLOBALS["LANG_ERROR_JS_MEETING_ROOM_WRONG_TIMES"]
                                                           ."', '".$GLOBALS["LANG_ERROR_JS_MEETING_ROOM_MAILING_LIST"]."')");

             // Display the table (frame) where the form will take place
             $MeetingRoomRegistrationTitle = $GLOBALS["LANG_MEETING_ROOM_REGISTRATION"];

             openStyledFrame($MeetingRoomRegistrationTitle, "Frame", "Frame", "DetailsObjectForm");

             // <<< MeetingRoomRegistrationID ID >>>
             if ($MeetingRoomRegistrationID == 0)
             {
                 // Define default values to create the new meeting room registration
                 $Reference = "&nbsp;";

                 $MeetingRoomID = 0;
                 if (isset($ArrayCreationParams['MeetingRoomID']))
                 {
                     $MeetingRoomID = $ArrayCreationParams['MeetingRoomID'];
                 }

                 $MeetingRoomRegistrationStartDate = date('Y-m-d H:i:s');
                 if (isset($ArrayCreationParams['MeetingRoomRegistrationStartDate']))
                 {
                     $MeetingRoomRegistrationStartDate = $ArrayCreationParams['MeetingRoomRegistrationStartDate'];
                 }

                 $MeetingRoomRegistrationEndDate = date('Y-m-d H:i:s', strtotime("+1 hour", strtotime($MeetingRoomRegistrationStartDate)));
                 if (isset($ArrayCreationParams['MeetingRoomID']))
                 {
                     $MeetingRoomRegistrationStartDate = $ArrayCreationParams['MeetingRoomRegistrationStartDate'];
                 }

                 $MeetingRoomRegistrationRecord = array(
                                                        "MeetingRoomRegistrationDate" => date('Y-m-d H:i:s'),
                                                        "MeetingRoomRegistrationTitle" => '',
                                                        "MeetingRoomRegistrationStartDate" => $MeetingRoomRegistrationStartDate,
                                                        "MeetingRoomRegistrationEndDate" => $MeetingRoomRegistrationEndDate,
                                                        "MeetingRoomRegistrationMailingList" => '',
                                                        "MeetingRoomRegistrationDescription" => '',
                                                        "MeetingRoomID" => $MeetingRoomID,
                                                        "SupportMemberID" => $_SESSION['SupportMemberID'],
                                                        "EventID" => 0
                                                       );

                 $SchoolYear = getSchoolYear(date('Y-m-d'));
                 $bClosed = FALSE;
             }
             else
             {
                 if (isExistingMeetingRoomRegistration($DbConnection, $MeetingRoomRegistrationID))
                 {
                     // We get the details of the meeting room registration
                     $Reference = $MeetingRoomRegistrationID;
                     $MeetingRoomRegistrationRecord = getTableRecordInfos($DbConnection, "MeetingRoomsRegistrations", $MeetingRoomRegistrationID);
                     $SchoolYear = getSchoolYear($MeetingRoomRegistrationRecord['MeetingRoomRegistrationStartDate']);

                     // We check if the donation is closed
                     $bClosed = isMeetingRoomRegistrationClosed($DbConnection, $MeetingRoomRegistrationID);
                 }
                 else
                 {
                     // Error, the meeting room registration doesn't exist
                     $Reference = "&nbsp;";
                     $MeetingRoomRegistrationID = 0;
                     $SchoolYear = getSchoolYear(date('Y-m-d'));
                     $bClosed = TRUE;
                 }
             }

             // Creation datetime of the meeting room registration
             $CreationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                  strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationDate"]));

             // We get infos about the author of the meeting room registration
             $ArrayInfosLoggedSupporter = getSupportMemberInfos($DbConnection, $MeetingRoomRegistrationRecord["SupportMemberID"]);
             $Author = $ArrayInfosLoggedSupporter["SupportMemberLastname"].' '.$ArrayInfosLoggedSupporter["SupportMemberFirstname"]
                       .' ('.getSupportMemberStateName($DbConnection, $ArrayInfosLoggedSupporter["SupportMemberStateID"]).')';
             $Author .= generateInputField("hidSupportMemberID", "hidden", "", "", "", $MeetingRoomRegistrationRecord["SupportMemberID"]);

             // <<< MeetingRoomID SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     // We get infos about the selected meeting room
                     if (empty($MeetingRoomRegistrationRecord['MeetingRoomID']))
                     {
                         $MeetingRoom = "-";
                     }
                     else
                     {
                         $ArrayInfosMeetingRooms = getTableRecordInfos($DbConnection, 'MeetingRooms', $MeetingRoomRegistrationRecord['MeetingRoomID']);
                         $MeetingRoom = $ArrayInfosMeetingRooms['MeetingRoomName'];
                         unset($ArrayInfosMeetingRooms);
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     // Generate the list of activated meeting rooms
                     if ($bClosed)
                     {
                         if (empty($MeetingRoomRegistrationRecord['MeetingRoomID']))
                         {
                             $MeetingRoom = "-";
                         }
                         else
                         {
                             $ArrayInfosMeetingRooms = getTableRecordInfos($DbConnection, 'MeetingRooms', $MeetingRoomRegistrationRecord['MeetingRoomID']);
                             $MeetingRoom = $ArrayInfosMeetingRooms['MeetingRoomName'];
                             unset($ArrayInfosMeetingRooms);
                         }
                     }
                     else
                     {
                         $ArrayMeetingRoomID = array(0);
                         $ArrayMeetingRoomName = array('-');

                         $ArrayMeetingRooms = dbSearchMeetingRoom($DbConnection, array("MeetingRoomActivated" => array(1)), "MeetingRoomName", 1, 0);

                         if ((isset($ArrayMeetingRooms['MeetingRoomID'])) && (!empty($ArrayMeetingRooms['MeetingRoomID'])))
                         {
                             $ArrayMeetingRoomID = array_merge($ArrayMeetingRoomID, $ArrayMeetingRooms['MeetingRoomID']);
                             $ArrayMeetingRoomName = array_merge($ArrayMeetingRoomName, $ArrayMeetingRooms['MeetingRoomName']);
                         }

                         $MeetingRoom = generateSelectField("lMeetingRoomID", $ArrayMeetingRoomID, $ArrayMeetingRoomName, $MeetingRoomRegistrationRecord['MeetingRoomID'], "");
                     }
                     break;
             }

             // <<< MeetingRoomRegistrationTitle INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Title = stripslashes($MeetingRoomRegistrationRecord["MeetingRoomRegistrationTitle"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $Title = stripslashes($MeetingRoomRegistrationRecord["MeetingRoomRegistrationTitle"]);
                     }
                     else
                     {
                         $Title = generateInputField("sTitle", "text", "100", "80", $GLOBALS["LANG_MEETING_ROOM_REGISTRATION_TITLE_TIP"],
                                                     $MeetingRoomRegistrationRecord["MeetingRoomRegistrationTitle"]);
                     }
                     break;
             }

             // <<< MeetingRoomRegistrationMailingList INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $MailingList = nullFormatText(stripslashes($MeetingRoomRegistrationRecord["MeetingRoomRegistrationMailingList"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $MailingList = nullFormatText(stripslashes($MeetingRoomRegistrationRecord["MeetingRoomRegistrationMailingList"]));
                     }
                     else
                     {
                         $MailingList = generateInputField("sMailingList", "text", "255", "80", $GLOBALS["LANG_MEETING_ROOM_REGISTRATION_MAILING_LIST_TIP"],
                                                           $MeetingRoomRegistrationRecord["MeetingRoomRegistrationMailingList"]);
                     }
                     break;
             }

             // <<< MeetingRoomRegistrationStartDate INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (!empty($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]))
                     {
                         $MeetingRoomRegistrationStartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                                  strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]));
                     }
                     else
                     {
                         $MeetingRoomRegistrationStartDate = '-';
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         if (!empty($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]))
                         {
                             $MeetingRoomRegistrationStartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                                      strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]));
                         }
                         else
                         {
                             $MeetingRoomRegistrationStartDate = '-';
                         }
                     }
                     else
                     {
                         if (empty($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]))
                         {
                             $MeetingRoomRegistrationStartDate = '';
                         }
                         else
                         {
                             $MeetingRoomRegistrationStartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                                      strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]));
                         }

                         $MeetingRoomRegistrationStartDate = generateInputField("startDate", "text", "10", "10",
                                                                                $GLOBALS["LANG_MEETING_ROOM_REGISTRATION_START_DATE_TIP"], $MeetingRoomRegistrationStartDate,
                                                                                TRUE);

                         // Insert the javascript to use the calendar component
                         $MeetingRoomRegistrationStartDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallback', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/', 'startDate', '', '".$GLOBALS["CONF_LANG"]."'); \n\t//-->\n</script>\n";
                     }
                     break;
             }

             // <<< StartTime and EndTime SELECTFIELD >>>
             $ArrayFormatTime = explode($GLOBALS['CONF_TIME_SEPARATOR'], $GLOBALS['CONF_TIME_DISPLAY_FORMAT']);
             if (count($ArrayFormatTime) >= 2)
             {
                 $FormatTime = $ArrayFormatTime[0].$GLOBALS['CONF_TIME_SEPARATOR'].$ArrayFormatTime[1];
             }
             else
             {
                 $FormatTime = $ArrayFormatTime[0];
             }

             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $StartTime = date($FormatTime, strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]));
                     $EndTime = date($FormatTime, strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationEndDate"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $StartTime = date($FormatTime, strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]));
                         $EndTime = date($FormatTime, strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationEndDate"]));
                     }
                     else
                     {
                         $StartTime = date($FormatTime, strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationStartDate"]));
                         $StartTime = getNearTimeSlot($StartTime, $GLOBALS['CONF_MEETING_REGISTRATIONS_TIME_SLOT_SIZE']);

                         $EndTime = date($FormatTime, strtotime($MeetingRoomRegistrationRecord["MeetingRoomRegistrationEndDate"]));
                         $EndTime = getNearTimeSlot($EndTime, $GLOBALS['CONF_MEETING_REGISTRATIONS_TIME_SLOT_SIZE']);

                         $ArrayTimes = generateTimeSlots('00:00:00', '23:00:00', $GLOBALS['CONF_MEETING_REGISTRATIONS_TIME_SLOT_SIZE']);
                         $StartTime = generateSelectField("lStartTime", $ArrayTimes, $ArrayTimes, $StartTime);
                         $EndTime = generateSelectField("lEndTime", $ArrayTimes, $ArrayTimes, $EndTime);
                     }
                     break;
             }

             // <<< MeetingRoomRegistrationDescription TEXTAREA >>>
             if ($bClosed)
             {
                 $Description = stripslashes(nullFormatText($MeetingRoomRegistrationRecord["MeetingRoomRegistrationDescription"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Description = stripslashes(nullFormatText($MeetingRoomRegistrationRecord["MeetingRoomRegistrationDescription"]));
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Description = generateTextareaField("sDescription", 10, 60, $GLOBALS["LANG_MEETING_ROOM_REGISTRATION_DESCRIPTION_TIP"],
                                                              invFormatText($MeetingRoomRegistrationRecord["MeetingRoomRegistrationDescription"]));
                         break;
                 }
             }

             // <<< Events SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (!empty($MeetingRoomRegistrationRecord["EventID"]))
                     {
                         $RecordEvent = getTableRecordInfos($DbConnection, "Events", $MeetingRoomRegistrationRecord["EventID"]);
                         $Event = $RecordEvent['EventTitle'];
                         unset($RecordEvent);
                     }
                     else
                     {
                         $Event = '-';
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         if (!empty($MeetingRoomRegistrationRecord["EventID"]))
                         {
                             $RecordEvent = getTableRecordInfos($DbConnection, "Events", $MeetingRoomRegistrationRecord["EventID"]);
                             $Event = $RecordEvent['EventTitle'];
                             unset($RecordEvent);
                         }
                         else
                         {
                             $Event = '-';
                         }
                     }
                     else
                     {
                         $ArrayEvents = dbSearchEvent($DbConnection, array(
                                                                           "SchoolYear" => array($SchoolYear),
                                                                           "Activated" => TRUE,
                                                                           "EventTypeCategory" => $GLOBALS['CONF_MEETING_REGISTRATIONS_ALLOWED_EVENT_CATEGORIES'],
                                                                           "ParentEvents" => TRUE
                                                                          ), "EventStartDate DESC", 1, 0);
                         $ArrayEventID = array(0);
                         $ArrayEventDescriptions = array('-');
                         if ((isset($ArrayEvents['EventID'])) && (!empty($ArrayEvents['EventID'])))
                         {
                             $ArrayEventID = array_merge($ArrayEventID, $ArrayEvents['EventID']);
                             $ArrayEventDescriptions = array_merge($ArrayEventDescriptions, $ArrayEvents['EventTitle']);
                         }

                         $Event = generateSelectField("lEventID", $ArrayEventID, $ArrayEventDescriptions, $MeetingRoomRegistrationRecord["EventID"], "");
                     }
                     break;
             }

             // Display the link to the event
             if (!empty($MeetingRoomRegistrationRecord["EventID"]))
             {
                 $Event .= " ".generateCryptedHyperlink($GLOBALS['LANG_TO_VIEW'], $MeetingRoomRegistrationRecord["EventID"],
                                                        $GLOBALS['CONF_URL_SUPPORT']."Cooperation/UpdateEvent.php", $GLOBALS['LANG_VIEW_DETAILS_INSTRUCTIONS'],
                                                        '', '_blank', '');
             }

             // Display the form
             echo "<table id=\"MeetingRoomRegistrationDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CREATION_DATE"]."</td><td class=\"Value\">$CreationDate, $Author</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MEETING_ROOM_NAME"]."*</td><td class=\"Value\" colspan=\"3\">$MeetingRoom</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MEETING_ROOM_REGISTRATION_TITLE"]."*</td><td class=\"Value\" colspan=\"3\">$Title</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\" rowspan=\"2\">".$GLOBALS["LANG_MEETING_ROOM_REGISTRATION_START_DATE"]."*</td><td class=\"Value\" rowspan=\"2\">$MeetingRoomRegistrationStartDate</td><td class=\"Label\">".$GLOBALS["LANG_MEETING_ROOM_REGISTRATION_START_TIME"]."*</td><td class=\"Value\">$StartTime</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MEETING_ROOM_REGISTRATION_END_TIME"]."*</td><td class=\"Value\">$EndTime</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MEETING_ROOM_REGISTRATION_MAILING_LIST"]."</td><td class=\"Value\" colspan=\"3\">$MailingList</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT"]."</td><td class=\"Value\" colspan=\"3\">$Event</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MEETING_ROOM_REGISTRATION_DESCRIPTION"]."</td><td class=\"Value\" colspan=\"3\">$Description</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidMeetingRoomRegistrationID", "hidden", "", "", "", $MeetingRoomRegistrationID);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if (!$bClosed)
                     {
                         // We display the buttons
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"],
                                          $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"],
                                          $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                     }
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a meeting room registration
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the planning of the meeting rooms registrations for each meeting room for a month, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-25
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Month                Integer               Month to display [1..12]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update nursery registrations
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update meeting rooms registrations
 */
 function displayMeetingRoomPlanningByMonthForm($DbConnection, $ProcessFormPage, $Month, $Year, $AccessRules = array())
 {
     // Compute start date and end date of the month
     $StartDate = sprintf("%04d-%02d-01", $Year, $Month);
     $EndDate = date("Y-m-t", strtotime($StartDate));
     $SelectedDate = $StartDate;

     displayMeetingRoomPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, PLANNING_MONTH_VIEW, $AccessRules);
 }


/**
 * Display the planning of the meeting rooms registrations for each meeting room for a week, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-25
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Week                 Integer               Week to display [1..53]
 * @param $Year                 Integer               Year to display
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update meeting rooms registrations
 */
 function displayMeetingRoomPlanningByWeeksForm($DbConnection, $ProcessFormPage, $Week, $Year, $AccessRules = array())
 {
     // Compute start date and end date of the month
     $StartDate = getFirstDayOfWeek($Week, $Year);

     // N weeks + 6 days (first day of week is a monday, so the last is a sunday)
     $EndDate = date("Y-m-d", strtotime('+6 days',
                                        strtotime('+'.($GLOBALS['CONF_MEETING_REGISTRATIONS_WEEKS_TO_DISPLAY'] - 1).' week',
                                                  strtotime($StartDate))));
     $SelectedDate = $StartDate;

     displayMeetingRoomPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, PLANNING_WEEKS_VIEW, $AccessRules);
 }


/**
 * Display the planning of the meeting rooms registrations for each meeting room, for a given start date and end date,
 * in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-11-25
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $StartDate            Date                  Start date of the planning (YYYY-mm-dd format)
 * @param $EndDate              Date                  End date of the planning (YYYY-mm-dd format)
 * @param $SelectedDate         Date                  Selected date (YYYY-mm-dd format)
 * @param $ViewType             Integer               Type of view to display (month, week, day)
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update meeting rooms registrations
 */
 function displayMeetingRoomPlanningForm($DbConnection, $ProcessFormPage, $StartDate, $EndDate, $SelectedDate, $ViewType = PLANNING_MONTH_VIEW, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a nursery registratrion
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             if ($_SESSION["SupportMemberStateID"] == 5)
             {
                 // For families, we check if only referents are allowed to create or update a meeting room registration
                 if ($GLOBALS['CONF_MEETING_REGISTRATIONS_ALLOW_REGISTRATIONS_FOR_REFERENTS_ONLY'])
                 {
                     if (IsWorkgroupReferent($DbConnection, $_SESSION["SupportMemberID"]))
                     {
                         $cUserAccess = FCT_ACT_CREATE;
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
                 }
                 else
                 {
                     $cUserAccess = FCT_ACT_CREATE;
                 }
             }
             else
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             if ($_SESSION["SupportMemberStateID"] == 5)
             {
                 // For families, we check if only referents are allowed to create or update a meeting room registration
                 if ($GLOBALS['CONF_MEETING_REGISTRATIONS_ALLOW_REGISTRATIONS_FOR_REFERENTS_ONLY'])
                 {
                     if (IsWorkgroupReferent($DbConnection, $_SESSION["SupportMemberID"]))
                     {
                         $cUserAccess = FCT_ACT_UPDATE;
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
                 }
                 else
                 {
                     $cUserAccess = FCT_ACT_UPDATE;
                 }
             }
             else
             {
                 $cUserAccess = FCT_ACT_UPDATE;
             }
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

         $Year = date('Y', strtotime($SelectedDate));
         $Month = date('m', strtotime($SelectedDate));
         $Day = date('d', strtotime($SelectedDate));
         $Week = date('W', strtotime($SelectedDate));
         $YearOfWeek = date('o', strtotime($SelectedDate));

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormViewPlanning", "post", "$ProcessFormPage", "", "");

             // Display the months list to change the planning to display
             openParagraph('toolbar');

             // <<<< Types of views SELECTFIELD >>>
             $ViewsList = generateSelectField("lView", array_keys($GLOBALS["CONF_MEETING_REGISTRATIONS_VIEWS_TYPES"]), array_values($GLOBALS["CONF_MEETING_REGISTRATIONS_VIEWS_TYPES"]),
                                              $ViewType, "onChangeSelectedPlanningView(this.value)");

             $GeneratedYears = range(2009, 2037);

             switch($ViewType)
             {
                 case PLANNING_WEEKS_VIEW:
                     // Caption to display in the planning, in relation with the selected view type
                     $PlanningViewTypeCaption = ucfirst($GLOBALS["LANG_WEEK"]{0})."$Week-$YearOfWeek";

                     // <<< Weeks SELECTFIELD >>>
                     $WeeksList = generateSelectField("lWeek", range(1, getNbWeeksOfYear("$YearOfWeek")), range(1, getNbWeeksOfYear("$YearOfWeek")),
                                                      $Week, "onChangeSelectedWeek(this.value)");

                     // <<< Year SELECTFIELD >>>
                     $YearsList = generateSelectField("lYear", $GeneratedYears, $GeneratedYears, $YearOfWeek, "onChangeSelectedYear(this.value)");

                     // Compute the previous week
                     $PreviousStamp = strtotime("-7 day", strtotime($StartDate));
                     $PreviousWeek = date('W', $PreviousStamp);
                     $PreviousYear = date('Y', $PreviousStamp);
                     if ($PreviousWeek == 1)
                     {
                         $PreviousYear = $Year;
                     }
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$PreviousWeek&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     // Display the year list to change the planning to display
                     echo " $ViewsList $WeeksList $YearsList ";

                     // Compute the next week
                     $NextStamp = strtotime("+7 day", strtotime($StartDate));
                     $NextWeek = date('W', $NextStamp);
                     $NextYear = date('Y', $NextStamp);
                     if ($NextWeek == 1)
                     {
                         $NextYear = $Year + 1;
                     }
                     displayStyledLinkText($GLOBALS["LANG_NEXT"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$NextWeek&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
                     break;


                 case PLANNING_MONTH_VIEW:
                 default:
                     // Caption to display in the planning, in relation with the selected view type
                     $PlanningViewTypeCaption = $GLOBALS["CONF_PLANNING_MONTHS"][$Month - 1];

                     // <<< Months SELECTFIELD >>>
                     $MonthsList = generateSelectField("lMonth", range(1, 12), $GLOBALS["CONF_PLANNING_MONTHS"], $Month,
                                                       "onChangeSelectedMonth(this.value)");

                     // <<< Year SELECTFIELD >>>
                     $YearsList = generateSelectField("lYear", $GeneratedYears, $GeneratedYears, $Year,
                                                      "onChangeSelectedYear(this.value)");

                     // Compute the previous month
                     if ($Month == 1)
                     {
                         $PreviousMonth = 12;
                         $PreviousYear = $Year - 1;
                     }
                     else
                     {
                         $PreviousMonth = $Month - 1;
                         $PreviousYear = $Year;
                     }
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$PreviousMonth&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     // Display the year list to change the planning to display
                     echo " $ViewsList $MonthsList $YearsList ";

                     // Compute the next month
                     if ($Month == 12)
                     {
                         $NextMonth = 1;
                         $NextYear = $Year + 1;
                     }
                     else
                     {
                         $NextMonth = $Month + 1;
                         $NextYear = $Year;
                     }
                     displayStyledLinkText($GLOBALS["LANG_NEXT"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$NextMonth&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
             }

             closeParagraph();

             displayBR(1);

             // Get the days of the period between start date and end date
             $Days = getRepeatedDates(strtotime($StartDate), strtotime($EndDate), REPEAT_DAILY, 1, FALSE);
             $NbDays = count($Days);

             // We get the meeting rooms registrations for the period
             $ArrayMeetingRoomsRegistrations = dbSearchMeetingRoomRegistration($DbConnection, array(
                                                                                                    "MeetingRoomRegistrationStartDate" => array($StartDate, '>='),
                                                                                                    "MeetingRoomRegistrationEndDate" => array($EndDate, '<=')
                                                                                                    ), "MeetingRoomRegistrationStartDate", 1, 0);

             // We generate the time slots
             // We get the min and max time slots
             $MinTimeSlot = '23:59';
             $MaxTimeSlot = '00:00';
             foreach($GLOBALS['CONF_MEETING_REGISTRATIONS_OPENED_HOURS_FOR_WEEK_DAYS'] as $ts => $CurrentTimeSlots)
             {
                 foreach($CurrentTimeSlots as $h => $TimeSlot)
                 {
                     $ArrayTmpTimeSlots = explode('-', $TimeSlot);
                     if (strtotime(date('Y-m-d '.$ArrayTmpTimeSlots[0].':00')) < strtotime(date("Y-m-d $MinTimeSlot:00")))
                     {
                         $MinTimeSlot = $ArrayTmpTimeSlots[0];
                     }

                     if (strtotime(date('Y-m-d '.$ArrayTmpTimeSlots[1].':00')) > strtotime(date("Y-m-d $MaxTimeSlot:00")))
                     {
                         $MaxTimeSlot = $ArrayTmpTimeSlots[1];
                     }
                 }
             }

             $ArrayTimeSlots = generateTimeSlots($MinTimeSlot.':00', $MaxTimeSlot.':00', $GLOBALS['CONF_MEETING_REGISTRATIONS_TIME_SLOT_SIZE']);

             // We create good structures to treat the data
             $ArrayFormatTime = explode($GLOBALS['CONF_TIME_SEPARATOR'], $GLOBALS['CONF_TIME_DISPLAY_FORMAT']);
             if (count($ArrayFormatTime) >= 2)
             {
                 $FormatTime = $ArrayFormatTime[0].$GLOBALS['CONF_TIME_SEPARATOR'].$ArrayFormatTime[1];
             }
             else
             {
                 $FormatTime = $ArrayFormatTime[0];
             }

             $ArrayDataByDay = array();
             if ((isset($ArrayMeetingRoomsRegistrations['MeetingRoomRegistrationID'])) && (!empty($ArrayMeetingRoomsRegistrations['MeetingRoomRegistrationID'])))
             {
                 foreach($ArrayMeetingRoomsRegistrations['MeetingRoomRegistrationID'] as $r => $CurrentMeetingRoomRegistrationID)
                 {
                     $MeetingRoomRegistrationStartDate = date('Y-m-d', strtotime($ArrayMeetingRoomsRegistrations['MeetingRoomRegistrationStartDate'][$r]));
                     if (!isset($ArrayDataByDay[$MeetingRoomRegistrationStartDate]))
                     {
                         // To know the number of different meetings rooms concerned by a registration for a day
                         $ArrayDataByDay[$MeetingRoomRegistrationStartDate] = array(
                                                                                    'MeetingRooms' => array(),
                                                                                    'Registrations' => array(),
                                                                                    'NbRegistrationsByTimeSlot' => array()
                                                                                   );
                     }

                     if (!in_array($ArrayMeetingRoomsRegistrations['MeetingRoomID'][$r], $ArrayDataByDay[$MeetingRoomRegistrationStartDate]['MeetingRooms']))
                     {
                         $ArrayDataByDay[$MeetingRoomRegistrationStartDate]['MeetingRooms'][] = $ArrayMeetingRoomsRegistrations['MeetingRoomID'][$r];
                     }

                     $StartTime = date($FormatTime, strtotime($ArrayMeetingRoomsRegistrations['MeetingRoomRegistrationStartDate'][$r]));
                     $EndTime = date($FormatTime, strtotime($ArrayMeetingRoomsRegistrations['MeetingRoomRegistrationEndDate'][$r]));
                     $ArrayTmpTS = generateTimeSlots($StartTime.':00', $EndTime.':00', $GLOBALS['CONF_MEETING_REGISTRATIONS_TIME_SLOT_SIZE']);

                     // Nb time slots - 1 for the rowspan because we must not to include the last time slot in the display !!!
                     // So, we delete the last time slot of the registration
                     unset($ArrayTmpTS[count($ArrayTmpTS) - 1]);

                     $ArrayDataByDay[$MeetingRoomRegistrationStartDate]['Registrations'][$CurrentMeetingRoomRegistrationID] = $ArrayTmpTS;

                     foreach($ArrayTmpTS as $ts => $CurrentTS)
                     {
                         if (!isset($ArrayDataByDay[$MeetingRoomRegistrationStartDate]['NbRegistrationsByTimeSlot'][$CurrentTS]))
                         {
                             $ArrayDataByDay[$MeetingRoomRegistrationStartDate]['NbRegistrationsByTimeSlot'][$CurrentTS] = 0;
                         }

                         $ArrayDataByDay[$MeetingRoomRegistrationStartDate]['NbRegistrationsByTimeSlot'][$CurrentTS]++;
                     }
                 }
             }

             // To know for each time slot the repartition of registrations in cells of the table
             if (!empty($ArrayDataByDay))
             {
                 foreach($ArrayDataByDay as $Day => $DataDay)
                 {
                     $ArrayDataByDay[$Day]['TimeSlotsRepartition'] = array();
                     $iNbRoomsForDay = count($ArrayDataByDay[$Day]['MeetingRooms']);
                     foreach($ArrayTimeSlots as $ts => $CurrentTS)
                     {
                         $ArrayDataByDay[$Day]['TimeSlotsRepartition'][$CurrentTS] = array_fill(0, $iNbRoomsForDay, 0);
                     }

                     foreach($ArrayDataByDay[$Day]['Registrations'] as $RegistrationID => $RegistrationTimeSlots)
                     {
                         // Position of the meeting room registration in the day
                         $iPos = array_search($RegistrationID, $ArrayMeetingRoomsRegistrations['MeetingRoomRegistrationID']);
                         $PositionInDay = ($iPos - 1) % $iNbRoomsForDay;

                         foreach($RegistrationTimeSlots as $ts => $CurrentTS)
                         {
                             $ArrayDataByDay[$Day]['TimeSlotsRepartition'][$CurrentTS][$PositionInDay] = $RegistrationID;
                         }
                     }
                 }
             }

             // Display the planning
             // First, we display the caption of the planning : the month and the days
             echo "<table id=\"MeetingsPlanning\" class=\"Planning\" cellspacing=\"0\">\n<thead>\n";
             echo "<tr>\n\t<th class=\"PlanningMonthCaption\">$PlanningViewTypeCaption</th>";
             foreach($Days as $i => $CurrentDay)
             {
                 // Display the first letter of the day (monday -> M)
                 $Prefix = '';
                 $CurrentDayStamp = strtotime($CurrentDay);
                 $NumCurrentDay = (integer)date('d', $CurrentDayStamp);
                 $CurrentMonth = (integer)date('m', $CurrentDayStamp);
                 $CurrentYear = date('Y', $CurrentDayStamp);

                 $iNumWeekDay = date('w', $CurrentDayStamp);
                 if ($iNumWeekDay == 0)
                 {
                     // Sunday = 0 -> 7
                     $iNumWeekDay = 7;
                 }

                 if ($iNumWeekDay <= 5)
                 {
                     $Prefix = StrToUpper(substr($GLOBALS['CONF_DAYS_OF_WEEK'][$iNumWeekDay - 1], 0, 1));
                 }

                 $sColSpan = "";
                 if ((isset($ArrayDataByDay[$CurrentDay])) && (count($ArrayDataByDay[$CurrentDay]['MeetingRooms']) > 1))
                 {
                     $sColSpan = " colspan=\"".count($ArrayDataByDay[$CurrentDay]['MeetingRooms'])."\"";
                 }

                 if (!empty($GLOBALS['CONF_MEETING_REGISTRATIONS_OPENED_HOURS_FOR_WEEK_DAYS'][$iNumWeekDay - 1]))
                 {
                     // Opened day
                     echo "<th class=\"PlanningCaptions\" $sColSpan>$Prefix ".sprintf("%02u", $NumCurrentDay)."</th>";
                 }
                 else
                 {
                     // Closed day
                     echo "<th class=\"PlanningCaptionsHoliday\" $sColSpan>$Prefix ".sprintf("%02u", $NumCurrentDay)."</th>";
                 }
             }

             // Next, we display the time slots for each day
             foreach($ArrayTimeSlots as $ts => $CurrentTimeSlot)
             {
                 // Display the time slots
                 $TimeSlotCaption = $CurrentTimeSlot;
                 if (isset($ArrayTimeSlots[$ts + 1]))
                 {
                     $TimeSlotCaption .= " - ".$ArrayTimeSlots[$ts + 1];
                 }

                 if ($ts == 0)
                 {
                     echo "<tr id=\"MeetingRoomsPlanning\">\n\t<td class=\"PlanningTimeSlot\">$TimeSlotCaption</td>";
                 }
                 else
                 {
                     echo "<tr>\n\t<td class=\"PlanningTimeSlot\">$TimeSlotCaption</td>";
                 }

                 // Display the meeting rooms registrations for each day of the period
                 foreach($Days as $i => $CurrentDay)
                 {
                     $sColSpan = "";
                     $iNbRoomsForDay = 0;
                     if ((isset($ArrayDataByDay[$CurrentDay])) && (count($ArrayDataByDay[$CurrentDay]['MeetingRooms']) > 1))
                     {
                         $iNbRoomsForDay = count($ArrayDataByDay[$CurrentDay]['MeetingRooms']);
                         $sColSpan = " colspan=\"$iNbRoomsForDay\"";
                     }

                     if ((isset($ArrayDataByDay[$CurrentDay])) && (count($ArrayDataByDay[$CurrentDay]['Registrations']) > 0))
                     {
                         $iNbTS = count($ArrayDataByDay[$CurrentDay]['TimeSlotsRepartition'][$CurrentTimeSlot]);
                         foreach($ArrayDataByDay[$CurrentDay]['TimeSlotsRepartition'][$CurrentTimeSlot] as $c => $RegistrationID)
                         {
                             $CurrentClass = "";
                             switch($iNbTS)
                             {
                                 case 1:
                                     // Only one meeting room so only one cell
                                     $CurrentClass = "PlanningBookedTimeSlot";
                                     if ($RegistrationID == 0)
                                     {
                                         $CurrentClass = "PlanningAvailableTimeSlot";
                                     }
                                     break;

                                 case 2:
                                     // 2 meetings rooms so 2 cells
                                     $CurrentClass = "PlanningBookedTimeSlot";
                                     $Suffix = "Right";

                                     if ($c == 0)
                                     {
                                         $Suffix = "Left";
                                     }

                                     if ($RegistrationID == 0)
                                     {
                                         $CurrentClass = "PlanningAvailableTimeSlot";
                                     }

                                     $CurrentClass .= $Suffix;
                                     break;

                                 default:
                                     // More than 2 meetings rooms so more than 2 cells
                                     $CurrentClass = "PlanningBookedTimeSlot";
                                     $Suffix = "Between";

                                     if ($c == 0)
                                     {
                                         // First cell
                                         $Suffix = "Left";
                                     }
                                     elseif ($c == $iNbTS - 1)
                                     {
                                         // Last cell
                                         $Suffix = "Right";
                                     }

                                     if ($RegistrationID == 0)
                                     {
                                         $CurrentClass = "PlanningAvailableTimeSlot";
                                     }

                                     $CurrentClass .= $Suffix;
                                     break;
                             }

                             if ($RegistrationID == 0)
                             {
                                 // No registration : available time slot
                                 echo "<td class=\"$CurrentClass\">&nbsp;</td>";
                             }
                             else
                             {
                                 if ($CurrentTimeSlot == $ArrayDataByDay[$CurrentDay]['Registrations'][$RegistrationID][0])
                                 {
                                     // Get details about the meeting book registration
                                     $CurrentMeetingRoomID = $ArrayDataByDay[$CurrentDay]['MeetingRooms'][$c];

                                     // We display the registration
                                     $CurrentStyle = "";
                                     if (isset($GLOBALS['CONF_MEETING_REGISTRATIONS_PLANNING_COLORS'][$CurrentMeetingRoomID]))
                                     {
                                         $CurrentStyle = "style=\"background: ".$GLOBALS['CONF_MEETING_REGISTRATIONS_PLANNING_COLORS'][$CurrentMeetingRoomID]['background-color']
                                                         ."; color:".$GLOBALS['CONF_MEETING_REGISTRATIONS_PLANNING_COLORS'][$CurrentMeetingRoomID]['text-color'].";\"";
                                     }

                                     // We can delete the registration ?
                                     $sDeleteButton = '';
                                     if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
                                     {
                                         $MRRegistrationRecord = getTableRecordInfos($DbConnection, "MeetingRoomsRegistrations", $RegistrationID);

                                         // Only admin and the author (is the registration isn't closed) can delete a meeting room registratiion
                                         if ((($_SESSION['SupportMemberID'] == $MRRegistrationRecord['SupportMemberID'])
                                              && (!isMeetingRoomRegistrationClosed($DbConnection, $RegistrationID)))
                                             || ($_SESSION['SupportMemberStateID'] == 1))
                                         {
                                             // The supporter is allowed to delete the registration
                                             $sDeleteButton = generateBR(2)
                                                              .generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                              "DeleteMeetingRoomRegistration.php?Cr=".md5($RegistrationID)."&amp;Id=$RegistrationID",
                                                                                               $GLOBALS["LANG_DELETE"], 'Affectation');
                                         }
                                     }

                                     echo "<td class=\"$CurrentClass\" $CurrentStyle rowspan=\"".count($ArrayDataByDay[$CurrentDay]['Registrations'][$RegistrationID])."\">"
                                          .generateCryptedHyperlink($MRRegistrationRecord['MeetingRoomRegistrationTitle'], $RegistrationID,
                                          "UpdateMeetingRoomRegistration.php", $GLOBALS['LANG_VIEW_DETAILS_INSTRUCTIONS'], '', '_blank')."$sDeleteButton</td>";
                                 }
                             }
                         }
                     }
                     else
                     {
                         echo "<td class=\"PlanningAvailableTimeSlot\" $sColSpan>&nbsp;</td>";
                     }
                 }

                 echo "</tr>\n";
             }

             // Close the table
             echo "</tbody>\n</table>\n";

             // Display the toolbar
             openParagraph('toolbar');

             switch($ViewType)
             {
                 case PLANNING_WEEKS_VIEW:
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$PreviousWeek&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     echo str_repeat("&nbsp;", 4);

                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_WEEKS_VIEW."&amp;lWeek=$NextWeek&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
                     break;


                 case PLANNING_MONTH_VIEW:
                 default:
                     // Display previous and next links for "month" view
                     displayStyledLinkText($GLOBALS["LANG_PREVIOUS"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$PreviousMonth&amp;lYear=$PreviousYear",
                                           'prev', $GLOBALS["LANG_PREVIOUS"]);

                     echo str_repeat("&nbsp;", 4);

                     displayStyledLinkText($GLOBALS["LANG_NEXT"], "$ProcessFormPage?lView=".PLANNING_MONTH_VIEW."&amp;lMonth=$NextMonth&amp;lYear=$NextYear",
                                           'next', $GLOBALS["LANG_NEXT"]);
             }

             closeParagraph();
             closeForm();

             // Display the legends of the colors
             displayBR(2);

             $ArrayLegends = array();
             foreach($GLOBALS['CONF_MEETING_REGISTRATIONS_PLANNING_COLORS'] as $mrID => $CurrentMeetingRoomStyle)
             {
                 if (isExistingMeetingRoom($DbConnection, $mrID))
                 {
                     $Style = "background: ".$CurrentMeetingRoomStyle['background-color']."; color:".$CurrentMeetingRoomStyle['text-color'].";";

                     // Get meeting room name
                     $ArrayLegends[] = array(
                                             getMeetingRoomName($DbConnection, $mrID),
                                             $Style
                                            );
                 }
             }

             echo generateLegendsOfVisualIndicators($ArrayLegends, DYN_CSS_STYLE);
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }
?>