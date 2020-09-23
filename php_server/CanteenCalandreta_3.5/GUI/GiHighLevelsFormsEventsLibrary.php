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
 * Interface module : XHTML Graphic high level forms library used to manage the events and registrations to events.
 *
 * @author Christophe Javouhey
 * @version 3.5
 * @since 2013-04-04
 */


/**
 * Display the form to submit a new event or update an event, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.8
 *     - 2013-10-15 : display a progress bar for each child event in relation with the registered families
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table and patch a bug
 *                    to display start time and end time
 *     - 2015-12-09 : display an icon when the registration on an event isn't valided
 *     - 2016-06-20 : remove htmlspecialchars() function
 *     - 2016-09-09 : patch some display bugs about start time and end time, display a button
 *                    to add a new town, patch a bug about $EndDate
 *     - 2019-06-14 : taken into account $CONF_COOP_EVENT_USE_RANDOM_AUTO_FAMILIES_REGISTRATIONS
 *                    and display the number of registered families
 *     - 2019-11-08 : calendar has a unique callback function (calendarCallback) and has input field
 *                    and $CONF_LANG in parameter, allow to link uploaded files to the event
 *     - 2020-01-22 : add button to check/uncheck all checkboxes of the communication function
 *
 * @since 2013-04-04
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $EventID                  String                ID of the event to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view events
 * @param $ParentEventID            Integer               ID of the parent event (in the case of a new child event)
 */
 function displayDetailsEventForm($DbConnection, $EventID, $ProcessFormPage, $AccessRules = array(), $ParentEventID = NULL)
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update an event
         if (is_array($AccessRules))
         {
             $EventAccessRules = $AccessRules[0];
             $RegistrationAccessRules = $AccessRules[1];
         }
         else
         {
             $EventAccessRules = $AccessRules;
             $RegistrationAccessRules = null;
         }

         $cUserAccess = FCT_ACT_NO_RIGHTS;
         $cUserOtherAccess = FCT_ACT_NO_RIGHTS;
         if (empty($EventID))
         {
             // Creation mode
             if ((isset($EventAccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $EventAccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             // Access to the event
             if ((isset($EventAccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $EventAccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($EventAccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $EventAccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
             elseif ((isset($EventAccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $EventAccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
             {
                 // Partial read mode
                 $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
             }

             // Access to the registrations
             if ((isset($RegistrationAccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserOtherAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($RegistrationAccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserOtherAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($RegistrationAccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserOtherAccess = FCT_ACT_READ_ONLY;
             }
             elseif ((isset($RegistrationAccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
             {
                 // Partial read mode
                 $cUserOtherAccess = FCT_ACT_PARTIAL_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsEvent", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationEvent('".$GLOBALS["LANG_ERROR_JS_EVENT_TYPE"]."', '".$GLOBALS["LANG_ERROR_JS_EVENT_TITLE"]."', '"
                                         .$GLOBALS["LANG_ERROR_JS_TOWN"]."', '".$GLOBALS["LANG_ERROR_JS_START_DATE"]."', '"
                                         .$GLOBALS["LANG_ERROR_JS_WRONG_START_END_DATES"]."', '"
                                         .$GLOBALS["LANG_ERROR_JS_WRONG_EVENT_MAX_PARTICIPANTS"]."', '"
                                         .$GLOBALS["LANG_ERROR_JS_WRONG_EVENT_REGISTRATION_DELAY"]."', '"
                                         .$GLOBALS["LANG_ERROR_JS_EVENT_DESCRIPTION"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_EVENT"], "Frame", "Frame", "DetailsNews");

             // <<< Event ID >>>
             if ($EventID == 0)
             {
                 if ((!empty($ParentEventID)) && (isExistingEvent($DbConnection, $ParentEventID)))
                 {
                     $RecordParentEvent = getTableRecordInfos($DbConnection, 'Events', $ParentEventID);
                     $ArrayParentChildEvents = getEventsTree($DbConnection, $ParentEventID, array(),
                                                             'EventStartDate, EventStartTime, EventEndDate, EventEndTime, EventTitle');

                     if (isset($ArrayParentChildEvents['EventID']))
                     {
                         $EventTypeID = $RecordParentEvent["EventTypeID"];
                         $TownID = $RecordParentEvent["TownID"];
                         $StartDate = $RecordParentEvent["EventStartDate"];
                         $EndDate = "";
                         if (!empty($RecordParentEvent["EventEndDate"]))
                         {
                             $EndDate = $RecordParentEvent["EventEndDate"];
                         }

                         $EventMaxParticipants = max(1, $RecordParentEvent['EventMaxParticipants'] - (array_sum($ArrayParentChildEvents['EventMaxParticipants']) - $RecordParentEvent['EventMaxParticipants']));
                         $EventRegistrationDelay = $RecordParentEvent["EventRegistrationDelay"];
                     }

                     unset($RecordParentEvent, $ArrayParentChildEvents);
                 }
                 else
                 {
                     // Define default values to create the new event
                     $ParentEventID = 0;
                     $EventTypeID = 0;
                     $TownID = 0;
                     $StartDate = '';
                     $EndDate = '';
                     $EventMaxParticipants = $GLOBALS['CONF_COOP_EVENT_DEFAULT_MAX_FAMILIES'];
                     $EventRegistrationDelay = $GLOBALS['CONF_COOP_EVENT_DEFAULT_REGISTRATION_DELAY'];
                 }

                 $Reference = "&nbsp;";
                 $EventRecord = array(
                                      "EventDate" => date('Y-m-d H:i:s'),
                                      "EventTitle" => '',
                                      "EventStartDate" => $StartDate,
                                      "EventStartTime" => NULL,
                                      "EventEndDate" => $EndDate,
                                      "EventEndTime" => NULL,
                                      "EventDescription" => "",
                                      "EventMaxParticipants" => $EventMaxParticipants,
                                      "EventRegistrationDelay" => $EventRegistrationDelay,
                                      "EventClosingDate" => NULL,
                                      "EventTypeID" => $EventTypeID,
                                      "TownID" => $TownID,
                                      "SupportMemberID" => $_SESSION["SupportMemberID"],
                                      "ParentEventID" => $ParentEventID
                                     );

                 $bClosed = FALSE;
                 $bRegistrationsOpened = TRUE;

                 // Current school year
                 $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
             }
             else
             {
                 if (isExistingEvent($DbConnection, $EventID))
                 {
                     // We get the details of the event
                     $EventRecord = getTableRecordInfos($DbConnection, "Events", $EventID);
                     $Reference = "$EventID";

                     // We get the registered families to the event
                     $ArrayEventRegistrations = dbSearchEventRegistration($DbConnection, array("EventID" => $EventID), "FamilyLastname",
                                                                          1, 0);

                     // We check if the event is opened or close
                     $bClosed = isEventClosed($DbConnection, $EventID);

                     // We check if the registrations are opened (if the logged supporter is concerned by delays restrictions)
                     $bRegistrationsOpened = TRUE;
                     if (in_array($_SESSION['SupportMemberStateID'], $GLOBALS['CONF_COOP_EVENT_DELAYS_RESTRICTIONS']))
                     {
                         $InDays = getNbDaysBetween2Dates(strtotime(date('Y-m-d')), strtotime($EventRecord["EventStartDate"]), FALSE);
                         if ($InDays < $EventRecord["EventRegistrationDelay"])
                         {
                             // Registrations closed : delay to short
                             $bRegistrationsOpened = FALSE;
                         }
                         elseif ((isset($ArrayEventRegistrations['EventRegistrationID']))
                             && (count($ArrayEventRegistrations['EventRegistrationID']) >= $EventRecord["EventMaxParticipants"]))
                         {
                             // Registrations closed : too many registered families
                             $bRegistrationsOpened = FALSE;
                         }
                     }

                     // School year of the start date of the event
                     $CurrentSchoolYear = getSchoolYear($EventRecord["EventStartDate"]);
                 }
                 else
                 {
                     // Error, the event doesn't exist
                     $EventID = 0;
                     $Reference = "&nbsp;";
                     $bClosed = FALSE;
                     $bRegistrationsOpened = FALSE;

                     // Current school year
                     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
                 }
             }

             // Get the FamilyID of the logged user
             $LoggedFamilyID = $_SESSION['FamilyID'];
             $bCoopContributionOK = FALSE;
             if (!empty($LoggedFamilyID))
             {
                 // Check if the family respect number of contribution for this school year (current year or year of the event)
                 $bCoopContributionOK = dbFamilyCoopContribution($DbConnection, $LoggedFamilyID, $CurrentSchoolYear);
             }

             // We check if the logged user can delete his registration
             $bCanDeleteRegistration = FALSE;
             if (($bCoopContributionOK) && ($bRegistrationsOpened))
             {
                 $bCanDeleteRegistration = TRUE;
             }

             // We check if the logged user has a swap of registration in progress (as requestor or acceptor)
             $bSwapRegistrationInProgress = FALSE;
             $iNbSwapsAsRequestor = getNbdbSearchEventSwappedRegistration($DbConnection,
                                                                          array("RequestorFamilyID" => $LoggedFamilyID,
                                                                                "RequestorEventID" => $EventID,
                                                                                "Activated" => TRUE));

             $iNbSwapsAsAcceptor = getNbdbSearchEventSwappedRegistration($DbConnection,
                                                                         array("AcceptorFamilyID" => $LoggedFamilyID,
                                                                               "AcceptorEventID" => $EventID,
                                                                               "Activated" => TRUE));
             if ($iNbSwapsAsRequestor + $iNbSwapsAsAcceptor > 0)
             {
                 // Swap of registration in progress
                 $bSwapRegistrationInProgress = TRUE;
             }

             // Creation datetime of the event
             $CreationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                  strtotime($EventRecord["EventDate"]));

             // We get infos about the author of the event
             $ArrayInfosLoggedSupporter = getSupportMemberInfos($DbConnection, $EventRecord["SupportMemberID"]);
             $Author = $ArrayInfosLoggedSupporter["SupportMemberLastname"].' '.$ArrayInfosLoggedSupporter["SupportMemberFirstname"].' ('.getSupportMemberStateName($DbConnection, $ArrayInfosLoggedSupporter["SupportMemberStateID"]).')';
             $Author .= generateInputField("hidSupportMemberID", "hidden", "", "", "", $EventRecord["SupportMemberID"]);

             if ($EventID > 0)
             {
                 // Create the Towns list
                 if ($bClosed)
                 {
                     // We get infos about the selected town
                     $ArrayInfosTown = getTableRecordInfos($DbConnection, 'Towns', $EventRecord['TownID']);
                     $Town = $ArrayInfosTown['TownName'].' ('.$ArrayInfosTown['TownCode'].')';
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We get infos about the selected town
                             $ArrayInfosTown = getTableRecordInfos($DbConnection, 'Towns', $EventRecord['TownID']);
                             $Town = $ArrayInfosTown['TownName'].' ('.$ArrayInfosTown['TownCode'].')';
                             break;

                         case FCT_ACT_UPDATE:
                             $DbResultList = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
                             $Town = '&nbsp;';
                             if (!DB::isError($DbResultList))
                             {
                                 $ArrayTownID = array();
                                 $ArrayTownInfos = array();
                                 while($RecordList = $DbResultList->fetchRow(DB_FETCHMODE_ASSOC))
                                 {
                                     $ArrayTownID[] = $RecordList["TownID"];
                                     $ArrayTownInfos[] = $RecordList["TownName"].' ('.$RecordList["TownCode"].')';
                                 }

                                 $Town = generateSelectField("lTownID", $ArrayTownID, $ArrayTownInfos, $EventRecord['TownID']);

                                 // Display a button to add a new town
                                 $Town .= generateStyledPictureHyperlink($GLOBALS["CONF_ADD_ICON"], "../Canteen/AddTown.php?Cr=".md5('')."&amp;Id=",
                                                                         $GLOBALS["LANG_ADD_TOWN_TIP"], 'Affectation', '_blank');
                             }
                             break;
                     }
                 }

                 // Closing date
                 if ($bClosed)
                 {
                     $ClosingDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventClosingDate"]));
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             if (empty($EventRecord["EventClosingDate"]))
                             {
                                 $ClosingDate = '';
                             }
                             else
                             {
                                 $ClosingDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                     strtotime($EventRecord["EventClosingDate"]));
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             if (empty($EventRecord["EventClosingDate"]))
                             {
                                 $ClosingDate = '';
                             }
                             else
                             {
                                 $ClosingDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                     strtotime($EventRecord["EventClosingDate"]));
                             }

                             $ClosingDate = generateInputField("closingDate", "text", "10", "10",
                                                               $GLOBALS["LANG_EVENT_CLOSING_DATE_TIP"],
                                                               $ClosingDate, TRUE);

                             // Insert the javascript to use the calendar component
                             $ClosingDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t ClosingDateCalendar = new dynCalendar('ClosingDateCalendar', 'calendarCallback', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/', 'closingDate', '', '".$GLOBALS["CONF_LANG"]."'); \n\t//-->\n</script>\n";
                             break;
                     }
                 }

                 // We get the child events of the event
                 $ArrayChildEvents = getEventsTree($DbConnection, $EventID, array(),
                                                   'EventStartDate, EventStartTime, EventEndDate, EventEndTime, EventTitle');


                 // We define the captions of the child events table
                 $ChildEvents = '&nbsp;';
                 $TabChildrenCaptions = array($GLOBALS["LANG_EVENT_TITLE"]);

                 if ($bClosed)
                 {
                     // We transform the result to be displayed
                     if ((isset($ArrayChildEvents["EventID"])) && (count($ArrayChildEvents["EventID"]) > 0))
                     {
                         foreach($ArrayChildEvents["EventID"] as $i => $CurrentID)
                         {
                             if ($CurrentID != $EventID)
                             {
                                 // Compute the shift in the tree-view
                                 $Shift = str_repeat("&nbsp;", ($ArrayChildEvents["Level"][$i] - 1) * 8);

                                 // Check if the child event is desactivated
                                 $bChildDesactivated = FALSE;
                                 if (!empty($ArrayChildEvents["EventClosingDate"][$i]))
                                 {
                                     $bChildDesactivated = TRUE;
                                 }

                                 // Compute the proress of registrations
                                 $iMaxParticipants = $ArrayChildEvents["EventMaxParticipants"][$i];
                                 $iNbRegistrations = getNbdbSearchEventRegistration($DbConnection, array('EventID' => $CurrentID));
                                 $sProgressBar = generateProgressVisualIndicator(NULL, $iMaxParticipants, $iNbRegistrations,
                                                                                 max(0, $iMaxParticipants - $iNbRegistrations),
                                                                                 $GLOBALS["LANG_EVENT_REGISTERED_FAMILIES"]." : $iNbRegistrations / $iMaxParticipants.");

                                 if ($bChildDesactivated)
                                 {
                                     // Child event desactivated
                                     $TabChildrenData[0][] = $Shift.generateCryptedHyperlink($ArrayChildEvents["EventTitle"][$i],
                                                                                             $CurrentID, 'UpdateEvent.php',
                                                                                             $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                             'Desactivated', '_blank')." $sProgressBar";
                                 }
                                 else
                                 {
                                     // Child event activated
                                     $TabChildrenData[0][] = $Shift.generateCryptedHyperlink($ArrayChildEvents["EventTitle"][$i],
                                                                                             $CurrentID, 'UpdateEvent.php',
                                                                                             $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                             '_blank')." $sProgressBar";
                                 }
                             }
                             else
                             {
                                 unset($ArrayChildEvents["EventID"][$i]);
                             }
                         }
                     }

                     $ChildEvents = '&nbsp;';
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArrayChildEvents["EventID"])) && (count($ArrayChildEvents["EventID"]) > 0))
                             {
                                 foreach($ArrayChildEvents["EventID"] as $i => $CurrentID)
                                 {
                                     if ($CurrentID != $EventID)
                                     {
                                         // Compute the shift in the tree-view
                                         $Shift = str_repeat("&nbsp;", ($ArrayChildEvents["Level"][$i] - 1) * 8);

                                         // Check if the child event is desactivated
                                         $bChildDesactivated = FALSE;
                                         if (!empty($ArrayChildEvents["EventClosingDate"][$i]))
                                         {
                                             $bChildDesactivated = TRUE;
                                         }

                                         // Compute the proress of registrations
                                         $iMaxParticipants = $ArrayChildEvents["EventMaxParticipants"][$i];
                                         $iNbRegistrations = getNbdbSearchEventRegistration($DbConnection, array('EventID' => $CurrentID));
                                         $sProgressBar = generateProgressVisualIndicator(NULL, $iMaxParticipants, $iNbRegistrations,
                                                                                         max(0, $iMaxParticipants - $iNbRegistrations),
                                                                                         $GLOBALS["LANG_EVENT_REGISTERED_FAMILIES"]." : $iNbRegistrations / $iMaxParticipants.");

                                         if ($bChildDesactivated)
                                         {
                                             // Child event desactivated
                                             $TabChildrenData[0][] = $Shift.generateCryptedHyperlink($ArrayChildEvents["EventTitle"][$i],
                                                                                                     $CurrentID, 'UpdateEvent.php',
                                                                                                     $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                                     'Desactivated', '_blank')
                                                                                                     ." $sProgressBar";
                                         }
                                         else
                                         {
                                             // Child event activated
                                             $TabChildrenData[0][] = $Shift.generateCryptedHyperlink($ArrayChildEvents["EventTitle"][$i],
                                                                                                     $CurrentID, 'UpdateEvent.php',
                                                                                                     $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                     '_blank')." $sProgressBar";
                                         }
                                     }
                                     else
                                     {
                                         unset($ArrayChildEvents["EventID"][$i]);
                                     }
                                 }
                             }

                             $ChildEvents = '&nbsp;';
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             $TabChildrenCaptions[] = '&nbsp;';
                             if ((isset($ArrayChildEvents["EventID"])) && (count($ArrayChildEvents["EventID"]) > 0))
                             {
                                 foreach($ArrayChildEvents["EventID"] as $i => $CurrentID)
                                 {
                                     if ($CurrentID != $EventID)
                                     {
                                         // Compute the shift in the tree-view
                                         $Shift = str_repeat("&nbsp;", ($ArrayChildEvents["Level"][$i] - 1) * 8);

                                         // Check if the child event is desactivated
                                         $bChildDesactivated = FALSE;
                                         if (!empty($ArrayChildEvents["EventClosingDate"][$i]))
                                         {
                                             $bChildDesactivated = TRUE;
                                         }

                                         // Compute the proress of registrations
                                         $iMaxParticipants = $ArrayChildEvents["EventMaxParticipants"][$i];
                                         $iNbRegistrations = getNbdbSearchEventRegistration($DbConnection, array('EventID' => $CurrentID));
                                         $sProgressBar = generateProgressVisualIndicator(NULL, $iMaxParticipants, $iNbRegistrations,
                                                                                         max(0, $iMaxParticipants - $iNbRegistrations),
                                                                                         $GLOBALS["LANG_EVENT_REGISTERED_FAMILIES"]." : $iNbRegistrations / $iMaxParticipants.");

                                         if ($bChildDesactivated)
                                         {
                                             // Child event desactivated
                                             $TabChildrenData[0][] = $Shift.generateCryptedHyperlink($ArrayChildEvents["EventTitle"][$i],
                                                                                                     $CurrentID, 'UpdateEvent.php',
                                                                                                     $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                                     'Desactivated', '_blank')
                                                                                                     ." $sProgressBar";

                                             // We can't delete him
                                             $TabChildrenData[1][] = "&nbsp;";
                                         }
                                         else
                                         {
                                             // Child event activated
                                             $TabChildrenData[0][] = $Shift.generateCryptedHyperlink($ArrayChildEvents["EventTitle"][$i],
                                                                                                     $CurrentID, 'UpdateEvent.php',
                                                                                                     $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                                     '_blank')
                                                                                                     ." $sProgressBar";

                                             // We can delete him
                                             $TabChildrenData[1][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                    "DeleteEvent.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID&amp;Return=UpdateEvent.php&amp;RCr=".md5($EventID)."&amp;RId=$EventID",
                                                                                                    $GLOBALS["LANG_DELETE"], 'Affectation');
                                         }
                                     }
                                     else
                                     {
                                         unset($ArrayChildEvents["EventID"][$i]);
                                     }
                                 }
                             }

                             $ChildEvents = "<table><tr><td class=\"Action\">";
                             $ChildEvents .= generateCryptedHyperlink($GLOBALS["LANG_EVENT_ADD_CHILD_EVENT"], $EventID, 'AddSubEvent.php',
                                                                   $GLOBALS["LANG_EVENT_ADD_CHILD_EVENT_TIP"], '', '_blank');
                             $ChildEvents .= "</td></tr></table>";
                             break;
                     }
                 }

                 // We define the captions of the event registrations table (registrations get at the begining of this function)
                 $EventRegistrations = '&nbsp;';
                 $TabRegistrationsCaptions = array($GLOBALS["LANG_FAMILY_LASTNAME"], $GLOBALS["LANG_EVENT_REGISTRATION_COMMENT"]);

                 $iNbFamiliesRegistrations = 0;
                 if ((isset($ArrayEventRegistrations["EventRegistrationID"])) && (!empty($ArrayEventRegistrations["EventRegistrationID"])))
                 {
                     $iNbFamiliesRegistrations = count($ArrayEventRegistrations["EventRegistrationID"]);
                 }

                 if ($bClosed)
                 {
                     // We transform the result to be displayed
                     if ((isset($ArrayEventRegistrations["EventRegistrationID"])) && ($iNbFamiliesRegistrations > 0))
                     {
                         foreach($ArrayEventRegistrations["EventRegistrationID"] as $i => $CurrentID)
                         {

                             $sTmpLastname = generateCryptedHyperlink($ArrayEventRegistrations["FamilyLastname"][$i],
                                                                      $ArrayEventRegistrations["FamilyID"][$i],
                                                                      '../Canteen/UpdateFamily.php',
                                                                      $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                             // This registration is valided ?
                             if ($ArrayEventRegistrations['EventRegistrationValided'][$i] == 0)
                             {
                                 // No : we display an icon
                                 $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON"],
                                                                           $GLOBALS['LANG_EVENT_REGISTRATION_NOT_VALIDED_REGISTRATION'], "");
                             }

                             $TabRegistrationsData[0][] = $sTmpLastname.' '
                                                         .generateFamilyVisualIndicators($DbConnection,
                                                                                         $ArrayEventRegistrations["FamilyID"][$i],
                                                                                         DETAILS,
                                                                                         array(
                                                                                               'FamilyCoopContribution' => $CurrentSchoolYear
                                                                                              ));

                             $TabRegistrationsData[1][] = stripslashes(nullFormatText($ArrayEventRegistrations["EventRegistrationComment"][$i]));
                         }
                     }

                     $EventRegistrations = '&nbsp;';
                 }
                 else
                 {
                     // Get swaps of registrations in progress for this event
                     // First : swaps for which the event is requested
                     $ArrayRequestorSwappedRegistrations = dbSearchEventSwappedRegistration($DbConnection,
                                                                                            array(
                                                                                                  "RequestorEventID" => $EventID,
                                                                                                  "Activated" => TRUE
                                                                                                 ), "RequestorFamilyLastname", 1, 0);
                     // Next : swaps for which the event is accepted
                     $ArrayAcceptorSwappedRegistrations = dbSearchEventSwappedRegistration($DbConnection,
                                                                                           array(
                                                                                                 "AcceptorEventID" => $EventID,
                                                                                                 "Activated" => TRUE
                                                                                                ), "AcceptorFamilyLastname", 1, 0);

                     switch($cUserAccess)
                     {
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             // We transform the result to be displayed : we hide some data
                             $bLoggedUserRegistered = FALSE;
                             if ((isset($ArrayEventRegistrations["EventRegistrationID"])) && ($iNbFamiliesRegistrations > 0))
                             {
                                 if ($bCanDeleteRegistration)
                                 {
                                     $TabRegistrationsCaptions[] = '&nbsp;';
                                 }

                                 foreach($ArrayEventRegistrations["EventRegistrationID"] as $i => $CurrentID)
                                 {
                                     if ($ArrayEventRegistrations["FamilyID"][$i] == $LoggedFamilyID)
                                     {
                                         $bLoggedUserRegistered = TRUE;
                                         $sTmpLastname = generateCryptedHyperlink($ArrayEventRegistrations["FamilyLastname"][$i],
                                                                                  $ArrayEventRegistrations["FamilyID"][$i],
                                                                                  '../Canteen/UpdateFamily.php',
                                                                                  $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                                         // This registration is valided ?
                                         if ($ArrayEventRegistrations['EventRegistrationValided'][$i] == 0)
                                         {
                                             // No : we display an icon
                                             $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON"],
                                                                                        $GLOBALS['LANG_EVENT_REGISTRATION_NOT_VALIDED_REGISTRATION'], "");
                                         }

                                         // Display visual indicator about the family is a good contributor or not
                                         $sTmpLastname .= ' '.generateFamilyVisualIndicators($DbConnection,
                                                                                             $ArrayEventRegistrations["FamilyID"][$i],
                                                                                             DETAILS,
                                                                                             array(
                                                                                                   'FamilyCoopContribution' => $CurrentSchoolYear
                                                                                                  ));

                                         // Check if a swap is in progress for this family and for this event
                                         $iFamilyPos = FALSE;
                                         if (isset($ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID']))
                                         {
                                             $iFamilyPos = array_search($ArrayEventRegistrations["FamilyID"][$i],
                                                                        $ArrayRequestorSwappedRegistrations['RequestorFamilyID']);
                                             if ($iFamilyPos !== FALSE)
                                             {
                                                 // The family is the requestor of the swap
                                                 $sTmpLastname .= ' '.generateStyledPictureHyperlink($GLOBALS['CONF_EVENT_SWAP_IN_PROGRESS_ICON'],
                                                                                                     'UpdateSwapEventRegistration.php?Cr='.md5($ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos]).'&amp;Id='.$ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos],
                                                                                                     $GLOBALS["LANG_EVENT_SWAP_IN_PROGRESS_TIP"],
                                                                                                     '', '_blank');
                                             }
                                         }

                                         if ((!$iFamilyPos) && (isset($ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'])))
                                         {
                                             $iFamilyPos = array_search($ArrayEventRegistrations["FamilyID"][$i],
                                                                        $ArrayAcceptorSwappedRegistrations['AcceptorFamilyID']);
                                             if ($iFamilyPos !== FALSE)
                                             {
                                                 // The family is the acceptor of the swap
                                                 $sTmpLastname .= ' '.generateStyledPictureHyperlink($GLOBALS['CONF_EVENT_SWAP_IN_PROGRESS_ICON'],
                                                                                                     'UpdateSwapEventRegistration.php?Cr='.md5($ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos]).'&amp;Id='.$ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos],
                                                                                                     $GLOBALS["LANG_EVENT_SWAP_IN_PROGRESS_TIP"],
                                                                                                     '', '_blank');

                                             }
                                         }

                                         $TabRegistrationsData[0][] = $sTmpLastname;
                                         $TabRegistrationsData[1][] = stripslashes(nullFormatText($ArrayEventRegistrations["EventRegistrationComment"][$i]));

                                         if ($bCanDeleteRegistration)
                                         {
                                             // We can delete him
                                             $TabRegistrationsData[2][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                         "DeleteEventRegistration.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                                         $GLOBALS["LANG_DELETE"], 'Affectation');
                                         }
                                     }
                                     else
                                     {
                                         // We hide the name of the other registered families
                                         $TabRegistrationsData[0][] = EVENT_HIDDEN_FAMILY_DATA.' '
                                                                      .generateFamilyVisualIndicators($DbConnection,
                                                                                                      $ArrayEventRegistrations["FamilyID"][$i],
                                                                                                      DETAILS,
                                                                                                      array(
                                                                                                            'FamilyCoopContribution' => $CurrentSchoolYear
                                                                                                           )
                                                                                                     );

                                         $TabRegistrationsData[1][] = "-";
                                         if ($bCanDeleteRegistration)
                                         {
                                             $TabRegistrationsData[2][] = "&nbsp;";
                                         }
                                     }
                                 }
                             }

                             // Add or not the button to add a registration / add or not the button to swap a registration with another family
                             switch($cUserOtherAccess)
                             {
                                 case FCT_ACT_CREATE:
                                 case FCT_ACT_UPDATE:
                                     if (!$bLoggedUserRegistered)
                                     {
                                         /* The user has rights to add a registration :
                                          * - delay OK
                                          * - user not already registered for this event
                                          */
                                         if ($bRegistrationsOpened)
                                         {
                                             $EventRegistrations = "<table><tr><td class=\"Action\">";
                                             $EventRegistrations .= generateStyledLinkText($GLOBALS["LANG_EVENT_ADD_REGISTERED_FAMILY"],
                                                                                           "AddEventRegistration.php?Cr=".md5($EventID)."&amp;Id=$EventID&amp;FCr=".md5($LoggedFamilyID)."&amp;FId=$LoggedFamilyID",
                                                                                           '', $GLOBALS["LANG_EVENT_ADD_REGISTERED_FAMILY_TIP"],
                                                                                           '_blank');
                                             $EventRegistrations .= "</td></tr></table>";
                                         }
                                         else
                                         {
                                             // Registrations closed
                                             $EventRegistrations .= generateStyledText($GLOBALS['LANG_EVENT_REGISTRATIONS_CLOSED'],
                                                                                      'RegistrationsClosed');
                                         }
                                     }
                                     elseif (($bLoggedUserRegistered) && (!$bCoopContributionOK) && (!$bSwapRegistrationInProgress))
                                     {
                                         /* The user has rights to swap a registration :
                                          * - user already registered for this event
                                          * - user don't respect number of contributions
                                          * - user hasn't swap of registration in progress for this event
                                          */
                                          $EventRegistrations = "<table><tr><td class=\"Action\">";
                                          $EventRegistrations .= generateStyledLinkText($GLOBALS["LANG_EVENT_SWAP_REGISTERED_FAMILY"],
                                                                                        "AddSwapEventRegistration.php?Cr=".md5($EventID)."&amp;Id=$EventID&amp;FCr=".md5($LoggedFamilyID)."&amp;FId=$LoggedFamilyID",
                                                                                        '', $GLOBALS["LANG_EVENT_SWAP_REGISTERED_FAMILY_TIP"], '_blank');
                                          $EventRegistrations .= "</td></tr></table>";
                                     }
                                     else
                                     {
                                         $EventRegistrations = '&nbsp;';
                                     }
                                     break;

                                 default:
                                     $EventRegistrations = '&nbsp;';
                                     break;
                             }
                             break;

                         case FCT_ACT_READ_ONLY:
                             // We transform the result to be displayed
                             if ((isset($ArrayEventRegistrations["EventRegistrationID"])) && ($iNbFamiliesRegistrations > 0))
                             {
                                 foreach($ArrayEventRegistrations["EventRegistrationID"] as $i => $CurrentID)
                                 {
                                      $sTmpLastname = generateCryptedHyperlink($ArrayEventRegistrations["FamilyLastname"][$i],
                                                                               $ArrayEventRegistrations["FamilyID"][$i],
                                                                               '../Canteen/UpdateFamily.php',
                                                                               $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                                      // This registration is valided ?
                                      if ($ArrayEventRegistrations['EventRegistrationValided'][$i] == 0)
                                      {
                                          // No : we display an icon
                                          $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON"],
                                                                                     $GLOBALS['LANG_EVENT_REGISTRATION_NOT_VALIDED_REGISTRATION'], "");
                                      }

                                      // Display visual indicator about the family is a good contributor or not
                                      $sTmpLastname .= ' '.generateFamilyVisualIndicators($DbConnection,
                                                                                          $ArrayEventRegistrations["FamilyID"][$i],
                                                                                          DETAILS,
                                                                                          array(
                                                                                                'FamilyCoopContribution' => $CurrentSchoolYear
                                                                                               ));

                                      // Check if a swap is in progress for this family and for this event
                                     $iFamilyPos = FALSE;
                                     if (isset($ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID']))
                                     {
                                         $iFamilyPos = array_search($ArrayEventRegistrations["FamilyID"][$i],
                                                                    $ArrayRequestorSwappedRegistrations['RequestorFamilyID']);
                                         if ($iFamilyPos !== FALSE)
                                         {
                                             // The family is the requestor of the swap
                                             $sTmpLastname .= ' '.generateStyledPictureHyperlink($GLOBALS['CONF_EVENT_SWAP_IN_PROGRESS_ICON'],
                                                                                                 'UpdateSwapEventRegistration.php?Cr='.md5($ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos]).'&amp;Id='.$ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos],
                                                                                                 $GLOBALS["LANG_EVENT_SWAP_IN_PROGRESS_TIP"],
                                                                                                 '', '_blank');
                                         }
                                     }

                                     if ((!$iFamilyPos) && (isset($ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'])))
                                     {
                                         $iFamilyPos = array_search($ArrayEventRegistrations["FamilyID"][$i],
                                                                    $ArrayAcceptorSwappedRegistrations['AcceptorFamilyID']);
                                         if ($iFamilyPos !== FALSE)
                                         {
                                             // The family is the acceptor of the swap
                                             $sTmpLastname .= ' '.generateStyledPictureHyperlink($GLOBALS['CONF_EVENT_SWAP_IN_PROGRESS_ICON'],
                                                                                                 'UpdateSwapEventRegistration.php?Cr='.md5($ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos]).'&amp;Id='.$ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos],
                                                                                                 $GLOBALS["LANG_EVENT_SWAP_IN_PROGRESS_TIP"],
                                                                                                 '', '_blank');

                                         }
                                     }

                                     $TabRegistrationsData[0][] = $sTmpLastname;
                                     $TabRegistrationsData[1][] = stripslashes(nullFormatText($ArrayEventRegistrations["EventRegistrationComment"][$i]));
                                 }
                             }

                             $EventRegistrations = '&nbsp;';
                             break;

                         case FCT_ACT_UPDATE:
                             // We transform the result to be displayed
                             $TabRegistrationsCaptions[] = '&nbsp;';

                             if ((isset($ArrayEventRegistrations["EventRegistrationID"])) && ($iNbFamiliesRegistrations > 0))
                             {
                                 foreach($ArrayEventRegistrations["EventRegistrationID"] as $i => $CurrentID)
                                 {
                                     $sTmpLastname = generateCryptedHyperlink($ArrayEventRegistrations["FamilyLastname"][$i],
                                                                              $ArrayEventRegistrations["FamilyID"][$i],
                                                                              '../Canteen/UpdateFamily.php',
                                                                              $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                                     // This registration is valided ?
                                     if ($ArrayEventRegistrations['EventRegistrationValided'][$i] == 0)
                                     {
                                         // No : we display an icon
                                         $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON"],
                                                                                    $GLOBALS['LANG_EVENT_REGISTRATION_NOT_VALIDED_REGISTRATION'], "");
                                     }

                                     // Display visual indicator about the family is a good contributor or not
                                     $sTmpLastname .= ' '.generateFamilyVisualIndicators($DbConnection,
                                                                                         $ArrayEventRegistrations["FamilyID"][$i],
                                                                                         DETAILS,
                                                                                         array(
                                                                                               'FamilyCoopContribution' => $CurrentSchoolYear
                                                                                              ));

                                     // Check if a swap is in progress for this family and for this event
                                     $iFamilyPos = FALSE;
                                     if (isset($ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID']))
                                     {
                                         $iFamilyPos = array_search($ArrayEventRegistrations["FamilyID"][$i],
                                                                    $ArrayRequestorSwappedRegistrations['RequestorFamilyID']);
                                         if ($iFamilyPos !== FALSE)
                                         {
                                             // The family is the requestor of the swap
                                             $sTmpLastname .= ' '.generateStyledPictureHyperlink($GLOBALS['CONF_EVENT_SWAP_IN_PROGRESS_ICON'],
                                                                                                 'UpdateSwapEventRegistration.php?Cr='.md5($ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos]).'&amp;Id='.$ArrayRequestorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos],
                                                                                                 $GLOBALS["LANG_EVENT_SWAP_IN_PROGRESS_TIP"],
                                                                                                 '', '_blank');
                                         }
                                     }

                                     if ((!$iFamilyPos) && (isset($ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'])))
                                     {
                                         $iFamilyPos = array_search($ArrayEventRegistrations["FamilyID"][$i],
                                                                    $ArrayAcceptorSwappedRegistrations['AcceptorFamilyID']);
                                         if ($iFamilyPos !== FALSE)
                                         {
                                             // The family is the acceptor of the swap
                                             $sTmpLastname .= ' '.generateStyledPictureHyperlink($GLOBALS['CONF_EVENT_SWAP_IN_PROGRESS_ICON'],
                                                                                                 'UpdateSwapEventRegistration.php?Cr='.md5($ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos]).'&amp;Id='.$ArrayAcceptorSwappedRegistrations['EventSwappedRegistrationID'][$iFamilyPos],
                                                                                                 $GLOBALS["LANG_EVENT_SWAP_IN_PROGRESS_TIP"],
                                                                                                 '', '_blank');

                                         }
                                     }

                                     $TabRegistrationsData[0][] = $sTmpLastname;
                                     $TabRegistrationsData[1][] = stripslashes(nullFormatText($ArrayEventRegistrations["EventRegistrationComment"][$i]));

                                     // We can delete him
                                     $TabRegistrationsData[2][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                 "DeleteEventRegistration.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                                 $GLOBALS["LANG_DELETE"], 'Affectation');
                                 }
                             }

                             $EventRegistrations = "<table><tr><td class=\"Action\">";
                             $EventRegistrations .= generateCryptedHyperlink($GLOBALS["LANG_EVENT_ADD_REGISTERED_FAMILY"], $EventID, 'AddEventRegistration.php',
                                                                             $GLOBALS["LANG_EVENT_ADD_REGISTERED_FAMILY_TIP"], '', '_blank');
                             $EventRegistrations .= "</td></tr></table>";

                             // We check if this event type can use the automatic random selection to register families to the event
                             if (in_array($EventRecord['EventTypeID'], $GLOBALS['CONF_COOP_EVENT_USE_RANDOM_AUTO_FAMILIES_REGISTRATIONS']))
                             {
                                 if ($iNbFamiliesRegistrations < $EventRecord['EventMaxParticipants'])
                                 {
                                     $EventRegistrations .= "<table><tr><td class=\"Action\">";
                                     $EventRegistrations .= generateCryptedHyperlink($GLOBALS["LANG_EVENT_ADD_AUTO_RANDOM_REGISTRATIONS"], $EventID,
                                                                                     'ProcessAutoRandomEventRegistration.php',
                                                                                     $GLOBALS["LANG_EVENT_ADD_AUTO_RANDOM_REGISTRATIONS_TIP"], '', '');
                                     $EventRegistrations .= "</td></tr></table>";
                                 }
                             }
                             break;
                     }
                 }

                 // We get uploaded files of the event
                 $ArrayEventUploadedFiles = getUploadedFilesOfObject($DbConnection, OBJ_EVENT, $EventID);
                 $EventUploadedFiles  = '&nbsp;';

                 if ($bClosed)
                 {
                     if ((isset($ArrayEventUploadedFiles["UploadedFileID"])) && (count($ArrayEventUploadedFiles["UploadedFileID"]) > 0))
                     {
                         foreach($ArrayEventUploadedFiles["UploadedFileID"] as $i => $CurrentID)
                         {
                             $ArrayEventUploadedFiles['CanDelete'][] = FALSE;
                         }
                     }
                 }
                 else
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_READ_ONLY:
                         case FCT_ACT_PARTIAL_READ_ONLY:
                             if ((isset($ArrayEventUploadedFiles["UploadedFileID"])) && (count($ArrayEventUploadedFiles["UploadedFileID"]) > 0))
                             {
                                 foreach($ArrayEventUploadedFiles["UploadedFileID"] as $i => $CurrentID)
                                 {
                                     $ArrayEventUploadedFiles['CanDelete'][] = FALSE;
                                 }
                             }
                             break;

                         case FCT_ACT_UPDATE:
                             if ((isset($ArrayEventUploadedFiles["UploadedFileID"])) && (count($ArrayEventUploadedFiles["UploadedFileID"]) > 0))
                             {
                                 foreach($ArrayEventUploadedFiles["UploadedFileID"] as $i => $CurrentID)
                                 {
                                     $ArrayEventUploadedFiles['CanDelete'][] = TRUE;
                                 }
                             }

                             // Button to add a file to the event
                             $EventUploadedFiles = "<table><tr><td class=\"Action\">";
                             $EventUploadedFiles .= generateStyledLinkText($GLOBALS["LANG_ADD_FILE"], "AddUploadedFile.php?Type=".OBJ_EVENT."&amp;Id=$EventID&amp;Cr=".md5($EventID),
                                                                           $GLOBALS["LANG_ADD_FILE_TIP"], '', '_blank');
                             $EventUploadedFiles .= "</td></tr></table>";
                             break;
                     }
                 }
             }
             else
             {
                 // Create the Towns list
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                         $DbResultList = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
                         $Town = '&nbsp;';
                         if (!DB::isError($DbResultList))
                         {
                             $ArrayTownID = array(0);
                             $ArrayTownInfos = array('');
                             while($RecordList = $DbResultList->fetchRow(DB_FETCHMODE_ASSOC))
                             {
                                 $ArrayTownID[] = $RecordList["TownID"];
                                 $ArrayTownInfos[] = $RecordList["TownName"].' ('.$RecordList["TownCode"].')';
                             }

                             $Town = generateSelectField("lTownID", $ArrayTownID, $ArrayTownInfos, $EventRecord['TownID']);

                             // Display a button to add a new town
                             $Town .= generateStyledPictureHyperlink($GLOBALS["CONF_ADD_ICON"], "../Canteen/AddTown.php?Cr=".md5('')."&amp;Id=",
                                                                     $GLOBALS["LANG_ADD_TOWN_TIP"], 'Affectation', '_blank');
                         }
                         break;
                 }

                 // Closing date
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                         $ClosingDate = generateInputField("closingDate", "text", "10", "10",
                                                           $GLOBALS["LANG_EVENT_CLOSING_DATE_TIP"], '', TRUE);

                         // Insert the javascript to use the calendar component
                         $ClosingDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t ClosingDateCalendar = new dynCalendar('ClosingDateCalendar', 'calendarCallback', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/', 'closingDate', '', '".$GLOBALS["CONF_LANG"]."'); \n\t//-->\n</script>\n";
                         break;
                 }
             }

             // <<< ParentEvent SELECTFIELD >>>
             if ($bClosed)
             {
                 $RecordParentEvent = getTableRecordInfos($DbConnection, 'Events', $EventRecord['ParentEventID']);
                 $ParentEvent = "&nbsp;";
                 if (isset($RecordParentEvent['EventID']))
                 {
                     $ParentEvent = stripslashes($RecordParentEvent["EventTitle"]).' ('
                                    .date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordParentEvent["EventStartDate"])).')';

                     $ParentEvent = generateCryptedHyperlink($ParentEvent, $RecordParentEvent['EventID'], 'UpdateEvent.php',
                                                             $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $RecordParentEvent = getTableRecordInfos($DbConnection, 'Events', $EventRecord['ParentEventID']);
                         $ParentEvent = "&nbsp;";
                         if (isset($RecordParentEvent['EventID']))
                         {
                             $ParentEvent = stripslashes($RecordParentEvent["EventTitle"]).' ('
                                            .date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($RecordParentEvent["EventStartDate"]))
                                            .')';

                             $ParentEvent = generateCryptedHyperlink($ParentEvent, $RecordParentEvent['EventID'], 'UpdateEvent.php',
                                                                     $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $EventActivated = array();
                         if ($EventID == 0)
                         {
                             // If new event, parents are only activated events
                             $EventActivated = array(1);
                         }

                         $ArrayEvents = getEventsTree($DbConnection, NULL, $EventActivated, 'EventTitle');
                         $iParentPos = 0;
                         if (isset($ArrayEvents['EventID']))
                         {
                             foreach($ArrayEvents['EventTitle'] as $e => $CurrentTitle)
                             {
                                 $ArrayEvents['EventTitle'][$e] = str_repeat("&nbsp;", 6 * $ArrayEvents["Level"][$e])."$CurrentTitle ("
                                                                             .date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                                                   strtotime($ArrayEvents['EventStartDate'][$e])).')';
                             }

                             $ArrayEvents['EventID'] = array_merge(array(0), $ArrayEvents['EventID']);
                             $ArrayEvents['EventTitle'] = array_merge(array('-'), $ArrayEvents['EventTitle']);
                         }
                         else
                         {
                             $ArrayEvents['EventID'] = array(0);
                             $ArrayEvents['EventTitle'] = array("-");
                         }

                         $ParentEvent = generateSelectField("lParentEventID", $ArrayEvents['EventID'], $ArrayEvents['EventTitle'],
                                                            $EventRecord["ParentEventID"], '');

                         // If exists, display a link to the parent event
                         if (!empty($EventRecord["ParentEventID"]))
                         {
                             $ParentEvent .= "&nbsp;".generateCryptedHyperlink($GLOBALS['LANG_TO_VIEW'],
                                                                               $EventRecord["ParentEventID"], 'UpdateEvent.php',
                                                                               $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                               '_blank');
                         }
                         break;
                 }
             }

             // <<< EventType SELECTFIELD >>>
             if ($bClosed)
             {
                 $Type = getEventTypeName($DbConnection, $EventRecord["EventTypeID"]);
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Type = getEventTypeName($DbConnection, $EventRecord["EventTypeID"]);
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $ArrayEventTypes = getTableContent($DbConnection, "EventTypes", "EventTypeName");
                         $ArrayTypes = array();
                         if (isset($ArrayEventTypes['EventTypeID']))
                         {
                             // Group the event types by category
                             foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES'] as $c => $CurrentCat)
                             {
                                 $ArrayTypes[$CurrentCat] = array();
                             }

                             foreach($ArrayEventTypes['EventTypeID'] as $et => $CurrentTypeID)
                             {
                                 $ArrayTypes[$GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES'][$ArrayEventTypes['EventTypeCategory'][$et]]][$CurrentTypeID] = $ArrayEventTypes['EventTypeName'][$et];
                             }

                             if ($EventID == 0)
                             {
                                 // Add the null value for a new event
                                 $ArrayTypes = array_merge(array(0 => ''), $ArrayTypes);
                             }
                         }
                         else
                         {
                             $$ArrayTypes[0] = array('');
                         }

                         $Type = generateOptGroupSelectField("lEventTypeID", $ArrayTypes, $EventRecord["EventTypeID"], '');
                         break;
                 }
             }

             // <<< EventTitle INPUTFIELD >>>
             if ($bClosed)
             {
                 $Title = stripslashes($EventRecord["EventTitle"]);
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Title = stripslashes($EventRecord["EventTitle"]);
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Title = generateInputField("sTitle", "text", "100", "80", $GLOBALS["LANG_EVENT_TITLE_TIP"],
                                                     $EventRecord["EventTitle"]);
                         break;
                 }
             }

             // <<< StartDate INPUTFIELD >>>
             if ($bClosed)
             {
                 $StartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventStartDate"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $StartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventStartDate"]));
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $StartDate = $EventRecord["EventStartDate"];
                         if (!empty($StartDate))
                         {
                             $StartDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventStartDate"]));
                         }
                         $StartDate = generateInputField("startDate", "text", "10", "10",
                                                         $GLOBALS["LANG_EVENT_START_DATE_TIP"], $StartDate, TRUE);

                         // Insert the javascript to use the calendar component
                         $StartDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallback', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/', 'startDate', '', '".$GLOBALS["CONF_LANG"]."'); \n\t//-->\n</script>\n";
                         break;
                 }
             }

             // <<< Start time INPUTFIELD >>>
             if ($bClosed)
             {
                 if (empty($EventRecord["EventStartTime"]))
                 {
                     $StartTime = nullFormatText($EventRecord["EventStartTime"]);
                 }
                 else
                 {
                     $StartTime = date('H:i', strtotime($EventRecord["EventStartTime"]));
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         if (empty($EventRecord["EventStartTime"]))
                         {
                             $StartTime = nullFormatText($EventRecord["EventStartTime"]);
                         }
                         else
                         {
                             $StartTime = date('H:i', strtotime($EventRecord["EventStartTime"]));
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         if (empty($EventRecord["EventStartTime"]))
                         {
                             $StartTime = '';
                         }
                         else
                         {
                             $StartTime = date('H:i', strtotime($EventRecord["EventStartTime"]));
                         }

                         $StartTime = generateInputField("hStartTime", "text", "5", "5", $GLOBALS["LANG_EVENT_START_TIME_TIP"],
                                                         $StartTime);
                         break;
                 }
             }

             // <<< EndDate INPUTFIELD >>>
             if ($bClosed)
             {
                 $EndDate = $EventRecord["EventEndDate"];
                 if (empty($EndDate))
                 {
                     $EndDate = nullFormatText($EndDate);
                 }
                 else
                 {
                     $EndDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EndDate));
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $EndDate = $EventRecord["EventEndDate"];
                         if (empty($EndDate))
                         {
                             $EndDate = nullFormatText($EndDate);
                         }
                         else
                         {
                             $EndDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EndDate));
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $EndDate = $EventRecord["EventEndDate"];
                         if (!empty($EndDate))
                         {
                             $EndDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EndDate));
                         }

                         $EndDate = generateInputField("endDate", "text", "10", "10",
                                                       $GLOBALS["LANG_EVENT_END_DATE_TIP"], $EndDate, TRUE);

                         // Insert the javascript to use the calendar component
                         $EndDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t EndDateCalendar = new dynCalendar('EndDateCalendar', 'calendarCallback', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/', 'endDate', '', '".$GLOBALS["CONF_LANG"]."'); \n\t//-->\n</script>\n";
                         break;
                 }
             }

             // <<< End time INPUTFIELD >>>
             if ($bClosed)
             {
                 if (empty($EventRecord["EventEndTime"]))
                 {
                     $EndTime = nullFormatText($EventRecord["EventEndTime"]);
                 }
                 else
                 {
                     $EndTime = date('H:i', strtotime($EventRecord["EventEndTime"]));
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         if (empty($EventRecord["EventEndTime"]))
                         {
                             $EndTime = nullFormatText($EventRecord["EventEndTime"]);
                         }
                         else
                         {
                             $EndTime = date('H:i', strtotime($EventRecord["EventEndTime"]));
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         if (empty($EventRecord["EventEndTime"]))
                         {
                             $EndTime = '';
                         }
                         else
                         {
                             $EndTime = date('H:i', strtotime($EventRecord["EventEndTime"]));
                         }

                         $EndTime = generateInputField("hEndTime", "text", "5", "5", $GLOBALS["LANG_EVENT_END_TIME_TIP"], $EndTime);
                         break;
                 }
             }

             // <<< EventMaxParticipants INPUTFIELD >>>
             if ($bClosed)
             {
                 $NbMaxParticipants = $EventRecord["EventMaxParticipants"];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $NbMaxParticipants = $EventRecord["EventMaxParticipants"];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $NbMaxParticipants = generateInputField("sNbMaxParticipants", "text", "3", "3", $GLOBALS["LANG_EVENT_NB_MAX_PARTICIPANTS_TIP"],
                                                                 $EventRecord["EventMaxParticipants"]);
                         break;
                 }
             }

             // <<< EventRegistrationDelay INPUTFIELD >>>
             if ($bClosed)
             {
                 if ($GLOBALS['CONF_COOP_EVENT_USE_REGISTRATION_CLOSING_DATE'])
                 {
                     if (empty($EventRecord["EventRegistrationDelay"]))
                     {
                         $EventRecord["EventRegistrationDelay"] = 0;
                     }

                     if (empty($EventRecord["EventStartDate"]))
                     {
                         // No start date (creation mode)
                         $RegistrationDelay = '&nbsp;';
                     }
                     else
                     {
                         // Convert the delay (in days) in a date
                         $RegistrationDelay = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                   strtotime($EventRecord["EventRegistrationDelay"]." days ago",
                                                             strtotime($EventRecord["EventStartDate"])));
                     }
                 }
                 else
                 {
                     // Display the number of days
                     $RegistrationDelay = $EventRecord["EventRegistrationDelay"];
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         if ($GLOBALS['CONF_COOP_EVENT_USE_REGISTRATION_CLOSING_DATE'])
                         {
                             // Display the date
                             if (empty($EventRecord["EventRegistrationDelay"]))
                             {
                                 $EventRecord["EventRegistrationDelay"] = 0;
                             }

                             if (empty($EventRecord["EventStartDate"]))
                             {
                                 // No start date (creation mode)
                                 $RegistrationDelay = '&nbsp;';
                             }
                             else
                             {
                                 // Convert the delay (in days) in a date
                                 $RegistrationDelay = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                           strtotime($EventRecord["EventRegistrationDelay"]." days ago",
                                                                     strtotime($EventRecord["EventStartDate"])));
                             }
                         }
                         else
                         {
                             // Display the number of days
                             $RegistrationDelay = $EventRecord["EventRegistrationDelay"];
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $InputType = "text";
                         $RegistrationDelay = "";
                         if ($GLOBALS['CONF_COOP_EVENT_USE_REGISTRATION_CLOSING_DATE'])
                         {
                             // Display the date
                             $InputType = "hidden";
                             if (empty($EventRecord["EventRegistrationDelay"]))
                             {
                                 $EventRecord["EventRegistrationDelay"] = 0;
                             }

                             if (empty($EventRecord["EventStartDate"]))
                             {
                                 // No start date (creation mode)
                                 $RegistrationDelayDate = '';
                             }
                             else
                             {
                                 // Convert the delay (in days) in a date
                                 $RegistrationDelayDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                               strtotime($EventRecord["EventRegistrationDelay"]." days ago",
                                                                         strtotime($EventRecord["EventStartDate"])));
                             }

                             $RegistrationDelay = generateInputField("registrationClosingDate", "text", "10", "10",
                                                                     $GLOBALS["LANG_EVENT_REGISTRATION_CLOSIING_DATE_TIP"],
                                                                     $RegistrationDelayDate, TRUE);

                             // Insert the javascript to use the calendar component
                             $RegistrationDelay .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t RegistrationClosingDateCalendar = new dynCalendar('RegistrationClosingDateCalendar', 'calendarCallback', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/', 'registrationClosingDate', '', '".$GLOBALS["CONF_LANG"]."'); \n\t//-->\n</script>\n";
                         }

                         // Keep the number of days (if date displayed, text field in hidden mode)
                         $RegistrationDelay .= generateInputField("sRegistrationDelay", $InputType, "3", "3",
                                                                  $GLOBALS["LANG_EVENT_REGISTRATION_DELAY_TIP"],
                                                                  $EventRecord["EventRegistrationDelay"]);
                         break;
                 }
             }

             // <<< EventDescription TEXTAREA >>>
             if ($bClosed)
             {
                 $Description = stripslashes(nullFormatText($EventRecord["EventDescription"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         $Description = stripslashes(nullFormatText($EventRecord["EventDescription"]));
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Description = generateTextareaField("sDescription", 10, 60, $GLOBALS["LANG_EVENT_DESCRIPTION_TIP"],
                                                              invFormatText($EventRecord["EventDescription"]));
                         break;
                 }
             }

             // Display the form
             echo "<table id=\"EventDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CREATION_DATE"]."</td><td class=\"Value\">$CreationDate, $Author</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_PARENT"]."*</td><td class=\"Value\" colspan=\"3\">$ParentEvent</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_TYPE"]."*</td><td class=\"Value\" colspan=\"3\">$Type</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_TITLE"]."*</td><td class=\"Value\" colspan=\"3\">$Title</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_TOWN"]."*</td><td class=\"Value\" colspan=\"3\">$Town</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_START_DATE"]."*</td><td class=\"Value\">$StartDate</td><td class=\"Label\">".$GLOBALS["LANG_EVENT_END_DATE"]."</td><td class=\"Value\">$EndDate</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_START_TIME"]."</td><td class=\"Value\">$StartTime</td><td class=\"Label\">".$GLOBALS["LANG_EVENT_END_TIME"]."</td><td class=\"Value\">$EndTime</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_NB_MAX_PARTICIPANTS"]."*</td><td class=\"Value\">$NbMaxParticipants</td><td class=\"Label\">".$GLOBALS["LANG_EVENT_REGISTRATION_DELAY"]."*</td><td class=\"Value\">$RegistrationDelay</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_DESCRIPTION"]."*</td><td class=\"Value\" colspan=\"3\">$Description</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_CLOSING_DATE"]."</td><td class=\"Value\">$ClosingDate</td><td class=\"Label\">&nbsp;</td><td class=\"Value\">&nbsp;</td>\n</tr>\n";

             if ($EventID > 0)
             {
                 // Display events tree-view of the current event
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_CHILD_EVENTS"]."</td><td class=\"Value\" colspan=\"3\">";
                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayChildEvents["EventID"])) && (count($ArrayChildEvents["EventID"]) > 0))
                 {
                     displayStyledTable($TabChildrenCaptions, array_fill(0, count($TabChildrenCaptions), ''), '', $TabChildrenData,
                                        'PurposeParticipantsTable', '', '', '', array(), 0, array(0 => 'textLeft'));
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }
                 echo $ChildEvents;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";

                 // Display registered families
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_REGISTERED_FAMILIES"]."</td><td class=\"Value\" colspan=\"3\">";
                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayEventRegistrations["EventRegistrationID"])) && (count($ArrayEventRegistrations["EventRegistrationID"]) > 0))
                 {
                     displayStyledTable($TabRegistrationsCaptions, array_fill(0, count($TabRegistrationsCaptions), ''), '',
                                        $TabRegistrationsData, 'PurposeParticipantsTable', '', '', '', array(), 0, array(0 => 'textLeft'));
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }
                 echo $EventRegistrations;
                 echo "</td></tr>\n</table>";

                 echo $GLOBALS['LANG_NB_EVENT_REGISTRATIONS']." : $iNbFamiliesRegistrations";

                 echo "</td>\n</tr>\n";

                 // Display uploaded files of the event
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FILENAMES"]."</td><td class=\"Value\" colspan=\"3\">";
                 echo "<table>\n<tr>\n\t<td>";
                 displayUploadedFilesOfObject(OBJ_EVENT, $EventID, $ArrayEventUploadedFiles);
                 echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 echo $EventUploadedFiles;
                 echo "</td></tr>\n</table>";
                 echo "</td>\n</tr>\n";

                 // Display the communication system (by e-mail)
                 if (!$bClosed)
                 {
                     switch($cUserAccess)
                     {
                         case FCT_ACT_UPDATE:
                             echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_COMMUNICATION"]."</td><td class=\"Value\" colspan=\"3\">";
                             echo "<p>".$GLOBALS['LANG_EVENT_COMMUNICATION_INTRODUCTION']."<p>\n";
                             echo "<table class=\"event_communication\">\n<tr>\n\t<td>";
                             echo "<h4>".$GLOBALS['LANG_EVENT_COMMUNICATION_MESSAGE']."</h4>"
                                  .generateTextareaField("sMessage", 10, 60, $GLOBALS["LANG_EVENT_COMMUNICATION_MESSAGE_TIP"], "");
                             echo "</td><td class=\"recipients\">";
                             echo "<h4>".$GLOBALS['LANG_EVENT_COMMUNICATION_RECIPIENTS_FAMILIES']."</h4>";

                             // Button to check/uncheck all checkboxes of families
                             openParagraph('CheckAllButton');
                             echo generateStyledPictureHyperlink($GLOBALS['CONF_CHECK_ALL_ICON'], "javascript:CheckAllEventFamilies()",
                                                                 $GLOBALS["LANG_CHECK_ALL_TIP"], "");
                             closeParagraph();

                             if ((isset($ArrayEventRegistrations["EventRegistrationID"])) && (count($ArrayEventRegistrations["EventRegistrationID"]) > 0))
                             {
                                 // Display the list of registered families
                                 foreach($ArrayEventRegistrations["EventRegistrationID"] as $i => $CurrentID)
                                 {
                                     echo generateInputField("chkMsgFamilies[]", "checkbox", 1, 1,
                                                             $GLOBALS["LANG_EVENT_COMMUNICATION_RECIPIENTS_FAMILIES_TIP"],
                                                             $ArrayEventRegistrations["FamilyID"][$i], FALSE, FALSE)
                                                             .$ArrayEventRegistrations["FamilyLastname"][$i].generateBR(1);
                                 }
                             }

                             echo "</td></tr>\n</table>";
                             echo "</td>\n</tr>\n";
                             break;
                     }
                 }
             }

             echo "</table>\n";

             insertInputField("hidEventID", "hidden", "", "", "", $EventID);
             insertInputField("hidParentEventID", "hidden", "", "", "", $ParentEventID);
             closeStyledFrame();

             if (!$bClosed)
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         // We display the buttons
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                         break;
                 }
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update an event
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
 * Display the form to search an event in the current web  page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *
 * @since 2013-04-10
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some events
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the events found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the events. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about an event. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update events
 * @param $$ArrayDiplayedFormFields    Array of Strings     Contains fieldnames of the form to display or not
 */
 function displaySearchEventForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to events list
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             $bCanDelete = FALSE;          // Check if the supporter can delete an event
             $bCheckRegistration = FALSE;  // Check if a flag must be displayed on events for which the supporter is registered
             if (isset($GLOBALS['CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION["SupportMemberStateID"]]))
             {
                 switch($GLOBALS['CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION["SupportMemberStateID"]])
                 {
                     case EVENT_REGISTRATION_VIEWS_RESTRICTION_ALL:
                         // To delete an event, the supporter must have write access and the page musn't display closed events
                         if ((in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
                             && (!in_array(strtolower($ProcessFormPage), array("closedeventslist.php"))))
                         {
                             $bCanDelete = TRUE;
                         }
                         break;

                     case EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                         $bCheckRegistration = TRUE;
                         break;
                 }
             }

             // Open a form
             openForm("FormSearchEvent", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             if (empty($ArrayDiplayedFormFields))
             {
                 // Display all available fields of the form
                 $ArrayDiplayedFormFields = array(
                                                  "lSchoolYear" => true,
                                                  "lEventTypeID" => true,
                                                  "sLastname" => true,
                                                  "chkOpenedRegistrations" => true
                                                 );
             }

             // <<< School year SELECTFIELD >>>
             // Create the school years list
             $SchoolYear = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lSchoolYear'])) && ($ArrayDiplayedFormFields['lSchoolYear']))
             {
                 $ArraySchoolYear = array(0 => '');
                 foreach($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'] as $Year => $Date)
                 {
                     $Value = date('Y', strtotime($Date)).'-'.$Year;
                     $ArraySchoolYear[$Year] = $Value;
                 }

                 if ((isset($TabParams['SchoolYear'])) && (count($TabParams['SchoolYear']) > 0))
                 {
                     $SelectedItem = $TabParams['SchoolYear'][0];
                 }
                 else
                 {
                     $SelectedItem = 0;
                 }

                 $SchoolYear = generateSelectField("lSchoolYear", array_keys($ArraySchoolYear), array_values($ArraySchoolYear),
                                                   zeroFormatValue(existedPOSTFieldValue("lSchoolYear", existedGETFieldValue("lSchoolYear", $SelectedItem))));
             }

             // <<<< EventTypeID SELECTFIELD >>>>
             $EventTypes = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lEventTypeID'])) && ($ArrayDiplayedFormFields['lEventTypeID']))
             {
                 $ArrayTypes = array();
                 $EventCategoryCondition = '';
                 if ((isset($TabParams['EventTypeCategory'])) && (!empty($TabParams['EventTypeCategory'])))
                 {
                     $EventCategoryCondition = "WHERE EventTypeCategory IN ".constructSQLINString($TabParams['EventTypeCategory']);
                 }

                 $DbResult = $DbConnection->query("SELECT EventTypeID, EventTypeName, EventTypeCategory FROM EventTypes
                                                   $EventCategoryCondition ORDER BY EventTypeName");

                 if (!DB::isError($DbResult))
                 {
                     if ($DbResult->numRows() > 0)
                     {
                         $ArrayEventTypes = array(
                                                  "EventTypeID" => array(),
                                                  "EventTypeName" => array(),
                                                  "EventTypeCategory" => array()
                                                 );

                         while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                         {
                             $ArrayEventTypes["EventTypeID"][] = $Record["EventTypeID"];
                             $ArrayEventTypes["EventTypeName"][] = $Record["EventTypeName"];
                             $ArrayEventTypes["EventTypeCategory"][] = $Record["EventTypeCategory"];
                         }

                         // Group the event types by category
                         if ((isset($TabParams['EventTypeCategory'])) && (!empty($TabParams['EventTypeCategory'])))
                         {
                             foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES'] as $c => $CurrentCat)
                             {
                                 // We keep only some selected event categories
                                 if (in_array($c, $TabParams['EventTypeCategory']))
                                 {
                                     $ArrayTypes[$CurrentCat] = array();
                                 }
                             }
                         }
                         else
                         {
                             foreach($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES'] as $c => $CurrentCat)
                             {
                                 // We keep all event categories
                                 $ArrayTypes[$CurrentCat] = array();
                             }
                         }

                         foreach($ArrayEventTypes['EventTypeID'] as $et => $CurrentTypeID)
                         {
                             $ArrayTypes[$GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES'][$ArrayEventTypes['EventTypeCategory'][$et]]][$CurrentTypeID] = $ArrayEventTypes['EventTypeName'][$et];
                         }
                     }
                 }

                 if ((isset($TabParams['EventTypeID'])) && (count($TabParams['EventTypeID']) > 0))
                 {
                     $SelectedItem = $TabParams['EventTypeID'][0];
                 }
                 else
                 {
                     $SelectedItem = 0;
                 }

                 // Add the null value for no event type selection
                 $ArrayTypes = array_merge(array(0 => ''), $ArrayTypes);

                 $EventTypes = generateOptGroupSelectField("lEventTypeID", $ArrayTypes,
                                                           zeroFormatValue(existedPOSTFieldValue("lEventTypeID", existedGETFieldValue("lEventTypeID", $SelectedItem))), '');
             }

             $sLastname = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sLastname'])) && ($ArrayDiplayedFormFields['sLastname']))
             {
                 // Family lastname input text
                 $sLastname = generateInputField("sLastname", "text", "50", "13", $GLOBALS["LANG_FAMILY_LASTNAME_TIP"],
                                                 stripslashes(strip_tags(existedPOSTFieldValue("sLastname", stripslashes(existedGETFieldValue("sLastname", ""))))));
             }

             $OpenedRegistrations = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['chkOpenedRegistrations'])) && ($ArrayDiplayedFormFields['chkOpenedRegistrations']))
             {
                 // Only events with opened registrations checkbox
                 $Checked = FALSE;
                 if (existedPOSTFieldValue("chkOpenedRegistrations", existedGETFieldValue("chkOpenedRegistrations", "")) == "openedregistrations")
                 {
                     $Checked = TRUE;
                 }
                 $OpenedRegistrations = generateInputField("chkOpenedRegistrations", "checkbox", "", "",
                                                           $GLOBALS["LANG_OPENED_EVENT_REGISTRATIONS_TIP"], "openedregistrations",
                                                           FALSE, $Checked)." ".$GLOBALS["LANG_YES"];
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SCHOOL_YEAR"]."</td><td class=\"Value\">$SchoolYear</td><td class=\"Label\">".$GLOBALS["LANG_EVENT_TYPE"]."</td><td class=\"Value\">$EventTypes</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_LASTNAME']."</td><td class=\"Value\">$sLastname</td><td class=\"Label\">".$GLOBALS["LANG_OPENED_EVENT_REGISTRATIONS"]."</td><td class=\"Value\">$OpenedRegistrations</td>\n</tr>\n";
             echo "</table>\n";

             // Display the hidden fields
             insertInputField("hidOrderByField", "hidden", "", "", "", $OrderBy);
             insertInputField("hidOnPrint", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnPrint", existedGETFieldValue("hidOnPrint", ""))));
             insertInputField("hidOnExport", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnExport", existedGETFieldValue("hidOnExport", ""))));
             insertInputField("hidExportFilename", "hidden", "", "", "", existedPOSTFieldValue("hidExportFilename", existedGETFieldValue("hidExportFilename", "")));
             closeStyledFrame();

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();

             // The supporter has executed a search
             $NbTabParams = count($TabParams);
             if ($NbTabParams > 0)
             {
                 displayBR(2);

                 $ArrayCaptions = array($GLOBALS["LANG_EVENT_START_DATE"], $GLOBALS["LANG_EVENT_TYPE"], $GLOBALS["LANG_EVENT_TITLE"],
                                        $GLOBALS["LANG_NB_EVENT_REGISTRATIONS"]);
                 $ArraySorts = array("EventStartDate", "EventTypeID", "EventTitle", "NbRegistrations");

                 if ($bCanDelete)
                 {
                     // The supporter can delete events : we add a column for this action
                     $ArrayCaptions[] = '&nbsp;';
                     $ArraySorts[] = "";
                 }

                 // Order by instruction
                 if ((abs($OrderBy) <= count($ArraySorts)) && ($OrderBy != 0))
                 {
                     $StrOrderBy = $ArraySorts[abs($OrderBy) - 1];
                     if ($OrderBy < 0)
                     {
                         $StrOrderBy .= " DESC";
                     }
                 }
                 else
                 {
                     $StrOrderBy = "EventStartDate ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchEvent($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only events of the page
                     $ArrayRecords = dbSearchEvent($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     if ((array_key_exists("SchoolYear", $TabParams)) && (!empty($TabParams["SchoolYear"])))
                     {
                         // Get select
                         $SelectedSchoolYear = $TabParams["SchoolYear"][0];
                     }
                     else
                     {
                         // Get Current school year
                         $SelectedSchoolYear = getSchoolYear(date('Y-m-d'));
                     }

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // Get FamilyID of the logged supporter if we must check registrations on events
                     if ($bCheckRegistration)
                     {
                         $FamilyID = $_SESSION['FamilyID'];
                         $ArrayTmpParams = array(
                                                 "SchoolYear" => array($SelectedSchoolYear),
                                                 "FamilyID" => $FamilyID
                                                );

                         if ((array_key_exists("EventTypeCategory", $TabParams)) && (count($TabParams["EventTypeCategory"]) > 0))
                         {
                             $ArrayTmpParams["EventTypeCategory"] = $TabParams["EventTypeCategory"];
                         }

                         $ArrayEventRegistrations = dbSearchEventRegistration($DbConnection, $ArrayTmpParams, "EventID", 1, 0);
                         unset($ArrayTmpParams);
                     }

                     // There are some events found
                     foreach($ArrayRecords["EventID"] as $i => $CurrentValue)
                     {
                         $ArrayData[0][] = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ArrayRecords["EventStartDate"][$i]));
                         $ArrayData[1][] = $ArrayRecords["EventTypeName"][$i];

                         if (empty($DetailsPage))
                         {
                             // We display the event title
                             $ArrayData[2][] = $ArrayRecords["EventTitle"][$i];
                         }
                         else
                         {
                             // We display the event title with a hyperlink
                             $ArrayData[2][] = generateAowIDHyperlink($ArrayRecords["EventTitle"][$i], $ArrayRecords["EventID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $sNbRegistrations = $ArrayRecords["NbRegistrations"][$i];

                         // Get total registrations (child-events)
                         if ($bCheckRegistration)
                         {
                             list($iTotalNbRegistrations, $iNbRegisteredSelectedFamilies) = getNbEventRegistrationTree($DbConnection, $CurrentValue, array(), array($FamilyID));
                         }
                         else
                         {
                             list($iTotalNbRegistrations, $iNbRegisteredSelectedFamilies) = getNbEventRegistrationTree($DbConnection, $CurrentValue);
                         }

                         if (($iTotalNbRegistrations > 0) && ($sNbRegistrations != $iTotalNbRegistrations))
                         {
                             $sNbRegistrations .= " ($iTotalNbRegistrations)";
                         }

                         if (($bCheckRegistration) && (isset($ArrayEventRegistrations['EventID'])))
                         {
                             if ((in_array($CurrentValue, $ArrayEventRegistrations['EventID'])) || ($iNbRegisteredSelectedFamilies > 0))
                             {
                                 // The logged user is registered to this event
                                 $sNbRegistrations .= "&nbsp;".generateStyledPicture($GLOBALS['CONF_REGISTERED_ON_EVENT_ICON'], '', '');
                             }
                         }

                         $ArrayData[3][] = $sNbRegistrations;

                         // Hyperlink to delete the event if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteEvent.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the events found
                     $ArraySortedFields = array("1", "2", "3", "4");
                     if ($bCanDelete)
                     {
                         $ArraySortedFields[] = "";
                     }

                     displayStyledTable($ArrayCaptions, $ArraySortedFields, $SortFct, $ArrayData, '', '', '', '',
                                        array(), $OrderBy, array('textLeft', '', '', 'textLeft', ''), 'EventsList');

                     // Display the previous and next links
                     $NoPage = 0;
                     if ($Page <= 1)
                     {
                         $PreviousLink = '';
                     }
                     else
                     {
                         $NoPage = $Page - 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             // GET form
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['SchoolYear']))
                                 {
                                     $CurrentValue = $TabParams['SchoolYear'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $PreviousLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $PreviousLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }

                     if ($Page < ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]))
                     {
                         $NoPage = $Page + 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['SchoolYear']))
                                 {
                                     $CurrentValue = $TabParams['SchoolYear'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $NextLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $NextLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }
                     else
                     {
                         $NextLink = '';
                     }

                     displayPreviousNext("&nbsp;".$GLOBALS["LANG_PREVIOUS"], $PreviousLink, $GLOBALS["LANG_NEXT"]."&nbsp;", $NextLink,
                                         '', $Page, ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]));

                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NB_RECORDS_FOUND'].$NbRecords;
                     closeParagraph();

                     // Display the legends of the icons
                     if ($bCheckRegistration)
                     {
                         displayBR(1);
                         echo generateLegendsOfVisualIndicators(
                                                                array(
                                                                      array($GLOBALS['CONF_REGISTERED_ON_EVENT_ICON'], $GLOBALS["LANG_EVENT_REGISTERED_ON_EVENT"])
                                                                     ),
                                                                ICON
                                                               );
                     }
                 }
                 else
                 {
                     // No event found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of events
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to search families and their contributions to events in the current web  page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2015-12-09 : display an icon when the registration on an event isn't valided
 *
 * @since 2013-04-10
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some families
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the families found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the families. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about a family. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update events
 * @param $$ArrayDiplayedFormFields    Array of Strings     Contains fieldnames of the form to display or not
 */
 function displaySearchFamilyEventContributionForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to families list
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormSearchFamilyEventContribution", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             if (empty($ArrayDiplayedFormFields))
             {
                 // Display all available fields of the form
                 $ArrayDiplayedFormFields = array(
                                                  "lSchoolYear" => true,
                                                  "sLastname" => true,
                                                  "chkEventContributions" => true,
                                                  "chkPbEventContributions" => true
                                                 );
             }

             // <<< School year SELECTFIELD >>>
             // Create the school years list
             $SchoolYear = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lSchoolYear'])) && ($ArrayDiplayedFormFields['lSchoolYear']))
             {
                 $ArraySchoolYear = array(0 => '');
                 foreach($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'] as $Year => $Date)
                 {
                     $Value = date('Y', strtotime($Date)).'-'.$Year;
                     $ArraySchoolYear[$Year] = $Value;
                 }

                 if ((isset($TabParams['SchoolYear'])) && (count($TabParams['SchoolYear']) > 0))
                 {
                     $SelectedItem = $TabParams['SchoolYear'][0];
                 }
                 else
                 {
                     $SelectedItem = 0;
                 }

                 $SchoolYear = generateSelectField("lSchoolYear", array_keys($ArraySchoolYear), array_values($ArraySchoolYear),
                                                   zeroFormatValue(existedPOSTFieldValue("lSchoolYear", existedGETFieldValue("lSchoolYear", $SelectedItem))));
             }

             $sLastname = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sLastname'])) && ($ArrayDiplayedFormFields['sLastname']))
             {
                 // Family lastname input text
                 $sLastname = generateInputField("sLastname", "text", "100", "13", $GLOBALS["LANG_FAMILY_LASTNAME_TIP"],
                                                 stripslashes(strip_tags(existedPOSTFieldValue("sLastname", stripslashes(existedGETFieldValue("sLastname", ""))))));
                                             }

             $EventContributions = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['chkEventContributions'])) && ($ArrayDiplayedFormFields['chkEventContributions']))
             {
                 // Only families respecting event contributions
                 $Checked = FALSE;
                 if (existedPOSTFieldValue("chkEventContributions", existedGETFieldValue("chkEventContributions", "")) == "nopbeventcontribution")
                 {
                     $Checked = TRUE;
                 }
                 $EventContributions = generateInputField("chkEventContributions", "checkbox", "", "",
                                                            $GLOBALS["LANG_GOOD_EVENT_COOPERATION_TIP"], "nopbeventcontribution",
                                                            FALSE, $Checked)." ".$GLOBALS["LANG_YES"];
             }

             $PbEventContributions = '&nbsp;';
             $PbEventContributionDefaultValue = '';
             if ((isset($ArrayDiplayedFormFields['chkPbEventContributions'])) && ($ArrayDiplayedFormFields['chkPbEventContributions']))
             {
                 // Only families don't respect event contributions
                 $Checked = FALSE;
                 if ((isset($TabParams['FamilyPbCoopContribution'])) && (!empty($TabParams['FamilyPbCoopContribution'])))
                 {
                     // Checked by default
                     $PbEventContributionDefaultValue = "pbeventcontribution";
                 }

                 if (existedPOSTFieldValue("chkPbEventContributions", existedGETFieldValue("chkPbEventContributions", $PbEventContributionDefaultValue)) == "pbeventcontribution")
                 {
                     $Checked = TRUE;
                 }
                 $PbEventContributions = generateInputField("chkPbEventContributions", "checkbox", "", "",
                                                            $GLOBALS["LANG_TOO_LOW_EVENT_COOPERATION_TIP"], "pbeventcontribution",
                                                            FALSE, $Checked)." ".$GLOBALS["LANG_YES"];
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SCHOOL_YEAR"]."</td><td class=\"Value\">$SchoolYear</td><td class=\"Label\">".$GLOBALS["LANG_GOOD_EVENT_COOPERATION"]."</td><td class=\"Value\">$EventContributions</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_LASTNAME']."</td><td class=\"Value\">$sLastname</td><td class=\"Label\">".$GLOBALS["LANG_TOO_LOW_EVENT_COOPERATION"]."</td><td class=\"Value\">$PbEventContributions</td>\n</tr>\n";
             echo "</table>\n";

             // Display the hidden fields
             insertInputField("hidOrderByField", "hidden", "", "", "", $OrderBy);
             insertInputField("hidOnPrint", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnPrint", existedGETFieldValue("hidOnPrint", ""))));
             insertInputField("hidOnExport", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnExport", existedGETFieldValue("hidOnExport", ""))));
             insertInputField("hidExportFilename", "hidden", "", "", "", existedPOSTFieldValue("hidExportFilename", existedGETFieldValue("hidExportFilename", "")));
             closeStyledFrame();

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();

             // The supporter has executed a search
             $NbTabParams = count($TabParams);
             if ($NbTabParams > 0)
             {
                 displayBR(2);

                 $ArrayCaptions = array($GLOBALS["LANG_FAMILY"], $GLOBALS["LANG_EVENTS"], $GLOBALS["LANG_EVENT_COOPERATION"]);
                 $ArraySorts = array("FamilyLastname", "", "");

                 // Order by instruction
                 if ((abs($OrderBy) <= count($ArraySorts)) && ($OrderBy != 0))
                 {
                     $StrOrderBy = $ArraySorts[abs($OrderBy) - 1];
                     if ($OrderBy < 0)
                     {
                         $StrOrderBy .= " DESC";
                     }
                 }
                 else
                 {
                     $StrOrderBy = "FamilyLastname ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchFamily($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only families of the page
                     $ArrayRecords = dbSearchFamily($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);
                     if ((array_key_exists("SchoolYear", $TabParams)) && (!empty($TabParams["SchoolYear"])))
                     {
                         // Get select
                         $SelectedSchoolYear = $TabParams["SchoolYear"][0];
                     }
                     else
                     {
                         // Get Current school year
                         $SelectedSchoolYear = getSchoolYear(date('Y-m-d'));
                     }

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some families found
                     foreach($ArrayRecords["FamilyID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the family lastname
                             $ArrayData[0][] = $ArrayRecords["FamilyLastname"][$i];
                         }
                         else
                         {
                             // We display the family lastname with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["FamilyLastname"][$i], $ArrayRecords["FamilyID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         // Get events for which the family is registered
                         $ArrayEventRegistrations = dbSearchEventRegistration($DbConnection,
                                                                              array("FamilyID" => $CurrentValue,
                                                                                    "SchoolYear" => array($SelectedSchoolYear)),
                                                                              "EventStartDate", 1, 0);

                         $EventsList = array();
                         if (isset($ArrayEventRegistrations['EventRegistrationID']))
                         {
                             foreach($ArrayEventRegistrations['EventID'] as $er => $EventID)
                             {
                                 $sEventTmp = generateAowIDHyperlink($ArrayEventRegistrations['EventTitle'][$er], $EventID,
                                                                     'UpdateEvent.php', $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                     "", "_blank");

                                 // Check if the registration on this event ist taken into account
                                 if ($ArrayEventRegistrations['EventRegistrationValided'][$er] == 0)
                                 {
                                     // No : we display an icon
                                     $sEventTmp .= ' '.generateStyledPicture($GLOBALS["CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON"],
                                                                             $GLOBALS['LANG_EVENT_REGISTRATION_NOT_VALIDED_REGISTRATION'], "");
                                 }

                                 $EventsList[] = $sEventTmp;
                             }
                         }

                         $ArrayData[1][] = implode("<br />", $EventsList);
                         $ArrayData[2][] = generateFamilyVisualIndicators($DbConnection, $CurrentValue, DETAILS,
                                                                          array(
                                                                                'FamilyCoopContribution' => $SelectedSchoolYear
                                                                                )
                                                                         );
                     }

                     // Display the table which contains the families found
                     $ArraySortedFields = array("1", "", "");
                     displayStyledTable($ArrayCaptions, $ArraySortedFields, $SortFct, $ArrayData, '', '', '', '',
                                        array(), $OrderBy, array('textLeft', 'textLeft', ''), 'FamiliesList');

                     // Display the previous and next links
                     $NoPage = 0;
                     if ($Page <= 1)
                     {
                         $PreviousLink = '';
                     }
                     else
                     {
                         $NoPage = $Page - 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             // GET form
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['SchoolYear']))
                                 {
                                     $CurrentValue = $TabParams['SchoolYear'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $PreviousLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }

                                 if ((isset($TabParams['FamilyPbCoopContribution'])) && (!empty($TabParams['FamilyPbCoopContribution'])))
                                 {
                                     $PreviousLink .= "&amp;chkPbEventContributions=pbeventcontribution";
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $PreviousLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }

                     if ($Page < ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]))
                     {
                         $NoPage = $Page + 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['SchoolYear']))
                                 {
                                     $CurrentValue = $TabParams['SchoolYear'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $NextLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }

                                 if ((isset($TabParams['FamilyPbCoopContribution'])) && (!empty($TabParams['FamilyPbCoopContribution'])))
                                 {
                                     $NextLink .= "&amp;chkPbEventContributions=pbeventcontribution";
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $NextLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }
                     else
                     {
                         $NextLink = '';
                     }

                     displayPreviousNext("&nbsp;".$GLOBALS["LANG_PREVIOUS"], $PreviousLink, $GLOBALS["LANG_NEXT"]."&nbsp;", $NextLink,
                                         '', $Page, ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]));

                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NB_RECORDS_FOUND'].$NbRecords;
                     closeParagraph();

                     // Display the legends of the icons
                     displayBR(1);
                     echo generateLegendsOfVisualIndicators(
                                                            array(
                                                                  array($GLOBALS['CONF_EVENT_COOPERATION_OK_ICON'], $GLOBALS["LANG_GOOD_EVENT_COOPERATION"]),
                                                                  array($GLOBALS['CONF_EVENT_COOPERATION_NOK_ICON'], $GLOBALS["LANG_TOO_LOW_EVENT_COOPERATION"]),
                                                                  array($GLOBALS['CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON'], $GLOBALS["LANG_EVENT_REGISTRATION_NOT_VALIDED_REGISTRATION"])
                                                                 ),
                                                            ICON
                                                           );
                 }
                 else
                 {
                     // No family found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of events
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to submit a new event registration or update an event registration, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *    - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *
 * @since 2013-04-19
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $EventRegistrationID      String                ID of the event registration to display [0..n]
 * @param $EventID                  String                ID of the event concerned by the registration [1..n]
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create or update event registrations
 */
 function displayDetailsEventRegistrationForm($DbConnection, $EventRegistrationID, $EventID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to event registration
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsEventRegistration", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationEventRegistration('".$GLOBALS["LANG_ERROR_JS_EVENT_REGISTRATION_FAMILY"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_EVENT_REGISTRATION"], "Frame", "Frame", "DetailsNews");

             // Get the FamilyID of the logged user
             $LoggedFamilyID = $_SESSION['FamilyID'];

             // <<< Event registration ID >>>
             if ($EventRegistrationID == 0)
             {
                 switch($GLOBALS['CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                 {
                     case EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                         // Registration created by a family
                         $EventRegistrationValided = 1;
                         $FamilyID = $LoggedFamilyID;
                         break;

                     default:
                         $EventRegistrationValided = 0;  // Registration not validated (by default)
                         $FamilyID = 0;  // No selected family
                         break;
                 }

                 $Reference = "&nbsp;";
                 $EventRegistrationRecord = array(
                                                  "EventRegistrationDate" => date('Y-m-d H:i:s'),
                                                  "EventRegistrationValided" => $EventRegistrationValided,
                                                  "EventRegistrationComment" => '',
                                                  "SupportMemberID" => $_SESSION['SupportMemberID'],
                                                  "FamilyID" => $FamilyID,
                                                  "EventID" => $EventID
                                                 );

                 $bClosed = FALSE;
             }
             else
             {
                 if (isExistingEventRegistration($DbConnection, $EventRegistrationID))
                 {
                     // We get the details of the event registration
                     $EventRegistrationRecord = getTableRecordInfos($DbConnection, "EventRegistrations", $EventRegistrationID);
                     $Reference = $EventRegistrationID;

                     // An event registration isn't updatable
                     $bClosed =  TRUE;
                 }
                 else
                 {
                     // Error, the event registration doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             // Creation date and time of the registration for the event
             $CreationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                  strtotime($EventRegistrationRecord["EventRegistrationDate"]));

             // We get infos about the author of the event registration
             $ArrayInfosLoggedSupporter = getSupportMemberInfos($DbConnection, $EventRegistrationRecord["SupportMemberID"]);
             $Author = $ArrayInfosLoggedSupporter["SupportMemberLastname"].' '.$ArrayInfosLoggedSupporter["SupportMemberFirstname"]
                       .' ('.getSupportMemberStateName($DbConnection, $ArrayInfosLoggedSupporter["SupportMemberStateID"]).')';
             $Author .= generateInputField("hidSupportMemberID", "hidden", "", "", "", $EventRegistrationRecord["SupportMemberID"]);

             // We get infos about the concerned event
             $EventRecord = getTableRecordInfos($DbConnection, "Events", $EventRegistrationRecord["EventID"]);
             $TownRecord = getTableRecordInfos($DbConnection, 'Towns', $EventRecord['TownID']);
             $EventInfos = "<dl class=\"EventInfos\">\n<dt>".stripslashes($EventRecord['EventTitle'])."</dt>\n";
             $EventInfos .= "<dd>".$TownRecord['TownName']." (".$TownRecord['TownCode'].")</dd>\n";
             $EventInfos .= "<dd>".getEventTypeName($DbConnection, $EventRecord['EventTypeID'])."</dd>\n";
             $EventInfos .= "</dl>\n";

             $EventDescription = stripslashes(nullFormatText($EventRecord["EventDescription"]));

             $EventDates = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventStartDate"]));
             if (!empty($EventRecord["EventStartTime"]))
             {
                 // Start time
                 $EventDates .= " (".$EventRecord["EventStartTime"].")";
             }

             if (!empty($EventRecord["EventEndDate"]))
             {
                 $EventDates .= " -> ";
                 $EventDates .= date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventEndDate"]));
                 if (!empty($EventRecord["EventEndTime"]))
                 {
                     // End time
                     $EventDates .= " (".$EventRecord["EventEndTime"].")";
                 }
             }

             // School year of the start date of the event
             $CurrentSchoolYear = getSchoolYear($EventRecord["EventStartDate"]);

             // <<< Family ID SELECTFIELD >>>
             if ($bClosed)
             {
                 // Get the lastname of the family
                 $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $EventRegistrationRecord["FamilyID"]);
                 $Family = $FamilyRecord['FamilyLastname'];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         if ($LoggedFamilyID == $EventRegistrationRecord["FamilyID"])
                         {
                             // We display the lastname
                             $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $EventRegistrationRecord["FamilyID"]);
                             $Family = $FamilyRecord['FamilyLastname'];
                         }
                         else
                         {
                             // We hide the lastname
                             $Family = EVENT_HIDDEN_FAMILY_DATA;
                         }
                         break;

                     case FCT_ACT_READ_ONLY:
                         // Get the lastname of the family
                         $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $EventRegistrationRecord["FamilyID"]);
                         $Family = $FamilyRecord['FamilyLastname'];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         switch($GLOBALS['CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                         {
                             case EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                                 $ArrayFamilyID = array();
                                 $ArrayFamilyLastname = array();

                                 // Only the logged family
                                 $ArrayFamilies = dbSearchFamily($DbConnection, array("FamilyID" => $LoggedFamilyID,
                                                                                      "SchoolYear" => array($CurrentSchoolYear)),
                                                                 "FamilyLastname", 1, 0);
                                 break;

                             default:
                                 // Generate the list of activated families
                                 $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear)),
                                                                 "FamilyLastname", 1, 0);

                                 if ($EventRegistrationID > 0)
                                 {
                                     $ArrayFamilyID = array();
                                     $ArrayFamilyLastname = array();
                                 }
                                 else
                                 {
                                     $ArrayFamilyID = array(0);
                                     $ArrayFamilyLastname = array('');
                                 }
                                 break;
                         }

                         if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                         {
                             // Get families already registered to the event
                             $ArrayEventRegistrations = dbSearchEventRegistration($DbConnection, array('EventID' => $EventRegistrationRecord['EventID']),
                                                                                  "FamilyLastname", 1, 0);

                             if (isset($ArrayEventRegistrations['FamilyID']))
                             {
                                 // There are registrations for this event : we keep only not registered families on this event
                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                 {
                                     // We check if the family isn't already registered to the event
                                     if (!in_array($CurrentFamilyID, $ArrayEventRegistrations['FamilyID']))
                                     {
                                         $sLastname = $ArrayFamilies['FamilyLastname'][$f];
                                         if (!dbFamilyCoopContribution($DbConnection, $CurrentFamilyID, $CurrentSchoolYear))
                                         {
                                             // The family doesn't repect the number of event registrations for thsi school year
                                             $sLastname .= " *";
                                         }

                                         $ArrayFamilyID[] = $CurrentFamilyID;
                                         $ArrayFamilyLastname[] = $sLastname;
                                     }
                                 }
                             }
                             else
                             {
                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                 {
                                     // We check if the family isn't already registered to the event
                                     $sLastname = $ArrayFamilies['FamilyLastname'][$f];
                                     if (!dbFamilyCoopContribution($DbConnection, $CurrentFamilyID, $CurrentSchoolYear))
                                     {
                                         // The family doesn't repect the number of event registrations for thsi school year
                                         $sLastname .= " *";
                                     }

                                     $ArrayFamilyID[] = $CurrentFamilyID;
                                     $ArrayFamilyLastname[] = $sLastname;
                                 }
                             }
                         }

                         $Family = generateSelectField("lFamilyID", $ArrayFamilyID, $ArrayFamilyLastname,
                                                       $EventRegistrationRecord["FamilyID"], "");
                         break;
                 }
             }

             // <<< Valided registration CHECKBOX >>>
             if ($bClosed)
             {
                 $EventRegistrationValided = $GLOBALS["LANG_NO"];
                 if ($EventRegistrationRecord["EventRegistrationValided"] == 1)
                 {
                     $EventRegistrationValided = $GLOBALS["LANG_YES"];
                 }
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $EventRegistrationValided = $GLOBALS["LANG_NO"];
                         if ($EventRegistrationRecord["EventRegistrationValided"] == 1)
                         {
                             $EventRegistrationValided = $GLOBALS["LANG_YES"];
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         switch($GLOBALS['CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                         {
                             case EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                                 // The user can't change the value of the valided registration
                                 if ($EventRegistrationRecord["EventRegistrationValided"] == 1)
                                 {
                                     $Value = "valided";
                                     $EventRegistrationValided = $GLOBALS["LANG_YES"];
                                 }
                                 else
                                 {
                                     $Value = "";
                                     $EventRegistrationValided = $GLOBALS["LANG_NO"];
                                 }

                                 $EventRegistrationValided .= generateInputField("chkRegistrationValided", "hidden", "", "", "", $Value);
                                 break;

                             default:
                                 $Checked = FALSE;
                                 if ($EventRegistrationRecord["EventRegistrationValided"] == 1)
                                 {
                                     $Checked = TRUE;
                                 }

                                 $EventRegistrationValided = generateInputField("chkRegistrationValided", "checkbox", "", "",
                                                                                $GLOBALS["LANG_EVENT_REGISTRATION_VALIDED_REGISTRATION_TIP"],
                                                                                "valided", FALSE, $Checked)." ".$GLOBALS["LANG_YES"];
                                 break;
                         }


                         break;
                 }
             }

             // <<< EventRegistrationComment INPUTFIELD >>>
             if ($bClosed)
             {
                 $Comment = stripslashes(nullFormatText($EventRegistrationRecord["EventRegistrationComment"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                         $Comment = stripslashes(nullFormatText($EventRegistrationRecord["EventRegistrationComment"]));
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $Comment = generateInputField("sComment", "text", "255", "100", $GLOBALS["LANG_EVENT_REGISTRATION_COMMENT_TIP"],
                                                       $EventRegistrationRecord["EventRegistrationComment"]);
                         break;
                 }
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CREATION_DATE"]."</td><td class=\"Value\">$CreationDate, $Author</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_REGISTRATION_EVENT_DATES"]."</td><td class=\"Value\">$EventDates</td><td class=\"Label\">".$GLOBALS["LANG_EVENT"]."</td><td class=\"Value\">$EventInfos</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_DESCRIPTION"]."</td><td class=\"Value\" colspan=\"3\">$EventDescription</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY"]."*</td><td class=\"Value\">$Family</td><td class=\"Label\">".$GLOBALS["LANG_EVENT_REGISTRATION_VALIDED_REGISTRATION"]."</td><td class=\"Value\">$EventRegistrationValided</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_REGISTRATION_COMMENT"]."</td><td class=\"Value\" colspan=\"3\">$Comment</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidEventID", "hidden", "", "", "", $EventRegistrationRecord["EventID"]);
             closeStyledFrame();

             if (!$bClosed)
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         // We display the buttons
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                         break;
                 }
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update an event registration
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
 * Display the form to submit a new swap of event registration or update a swap of event registration,
 * in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *    - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *
 * @since 2013-05-14
 *
 * @param $DbConnection                  DB object             Object of the opened database connection
 * @param $EventSwappedRegistrationID    String                ID of the swap event registration to display [0..n]
 * @param $EventID                       String                ID of the event concerned by the swap of registration [1..n]
 * @param $FamilyID                      String                ID of the family concerned by the swap of registration [1..n]
 * @param $ProcessFormPage               String                URL of the page which will process the form
 * @param $AccessRules                   Array of Integers     List used to select only some support members
 *                                                             allowed to create or update swaps of event registrations
 */
 function displayDetailsSwapEventRegistrationForm($DbConnection, $EventSwappedRegistrationID, $EventID, $FamilyID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to swap of event registration
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsSwapEventRegistration", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationSwapEventRegistration('".$GLOBALS["LANG_ERROR_JS_SWAP_EVENT_REGISTRATION_REQUESTOR_FAMILY"]."', '"
                                                         .$GLOBALS["LANG_ERROR_JS_SWAP_EVENT_REGISTRATION_REQUESTOR_EVENT"]."', '"
                                                         .$GLOBALS["LANG_ERROR_JS_SWAP_EVENT_REGISTRATION_ACCEPTOR_FAMILY"]."', '"
                                                         .$GLOBALS["LANG_ERROR_JS_SWAP_EVENT_REGISTRATION_ACCEPTOR_EVENT"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SWAP_EVENT_REGISTRATION"], "Frame", "Frame", "DetailsNews");

             // Get the FamilyID of the logged user
             $LoggedFamilyID = $_SESSION['FamilyID'];
             $RequestorStyle = "";
             $AcceptorStyle = "";

             // <<< Swap event registration ID >>>
             if ($EventSwappedRegistrationID == 0)
             {
                 switch($GLOBALS['CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                 {
                     case EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                         // Swap of registration created by a family
                         $FamilyID = $LoggedFamilyID;
                         break;
                 }

                 $Reference = "&nbsp;";
                 $SwapEventRegistrationRecord = array(
                                                      "EventSwappedRegistrationDate" => date('Y-m-d H:i:s'),
                                                      "EventSwappedRegistrationClosingDate" => '',
                                                      "RequestorFamilyID" => $FamilyID,
                                                      "RequestorEventID" => $EventID,
                                                      "AcceptorFamilyID" => 0,
                                                      "AcceptorEventID" => 0,
                                                      "SupportMemberID" => $_SESSION['SupportMemberID']
                                                     );

                 $bClosed = FALSE;
                 $cUserRole = EVENT_SWAPPED_REGISTRATION_AUTHOR;
                 $RequestorStyle = "RequestorPartToFill";
                 $AcceptorStyle = "AcceptorPartToFill";
             }
             else
             {
                 if (isExistingEventSwappedRegistration($DbConnection, $EventSwappedRegistrationID))
                 {
                     // We get the details of the event registration
                     $SwapEventRegistrationRecord = getTableRecordInfos($DbConnection, "EventSwappedRegistrations",
                                                                        $EventSwappedRegistrationID);
                     $Reference = $EventSwappedRegistrationID;

                     // Check the role of the logged supporter
                     if (($_SESSION['SupportMemberID'] == $SwapEventRegistrationRecord['SupportMemberID'])
                         || ($_SESSION['SupportMemberStateID'] == 1))
                     {
                         // The supporter is the author of the swap of event registration (all rights to update)
                         // or he is an admin
                         $cUserRole = EVENT_SWAPPED_REGISTRATION_AUTHOR;
                     }
                     elseif ($LoggedFamilyID == $SwapEventRegistrationRecord['RequestorFamilyID'])
                     {
                         // The supporter is the requestor of the swap of event registraion
                         $cUserRole = EVENT_SWAPPED_REGISTRATION_REQUESTOR;
                     }
                     elseif ($LoggedFamilyID == $SwapEventRegistrationRecord['AcceptorFamilyID'])
                     {
                         // The supporter must accept the swap of event registration
                         $cUserRole = EVENT_SWAPPED_REGISTRATION_ACCEPTOR;
                     }
                     else
                     {
                         // Nothing, no role, no rights
                         $cUserRole = EVENT_SWAPPED_REGISTRATION_OTHER;
                     }

                     // Check if the swap is closed
                     $bClosed = FALSE;
                     if (!empty($SwapEventRegistrationRecord['EventSwappedRegistrationClosingDate']))
                     {
                         // There is a closing date, the swap of event registration is closed
                         $bClosed = TRUE;
                     }
                     else
                     {
                         // Define some styles for the form in relation with the role of the supporter
                         switch($cUserRole)
                         {
                             case EVENT_SWAPPED_REGISTRATION_AUTHOR:
                             case EVENT_SWAPPED_REGISTRATION_REQUESTOR:
                                 $RequestorStyle = "RequestorPartToFill";
                                 $AcceptorStyle = "AcceptorPartToFill";
                                 break;

                             case EVENT_SWAPPED_REGISTRATION_ACCEPTOR:
                                 $RequestorStyle = "RequestorPartToFill";
                                 break;
                         }
                     }
                 }
                 else
                 {
                     // Error, the swap of event registration doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             // Creation date and time of the swap of event registration
             $CreationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                  strtotime($SwapEventRegistrationRecord["EventSwappedRegistrationDate"]));

             // We get infos about the author of the swap of the event registration
             $ArrayInfosLoggedSupporter = getSupportMemberInfos($DbConnection, $SwapEventRegistrationRecord["SupportMemberID"]);
             if ($bClosed)
             {
                 $Author = $ArrayInfosLoggedSupporter["SupportMemberLastname"].' '.$ArrayInfosLoggedSupporter["SupportMemberFirstname"]
                           .' ('.getSupportMemberStateName($DbConnection, $ArrayInfosLoggedSupporter["SupportMemberStateID"]).')';
             }
             else
             {
                 switch($cUserRole)
                 {
                     case EVENT_SWAPPED_REGISTRATION_AUTHOR:
                     case EVENT_SWAPPED_REGISTRATION_REQUESTOR:
                         $Author = $ArrayInfosLoggedSupporter["SupportMemberLastname"].' '
                                   .$ArrayInfosLoggedSupporter["SupportMemberFirstname"].' ('
                                   .getSupportMemberStateName($DbConnection, $ArrayInfosLoggedSupporter["SupportMemberStateID"]).')';
                         break;

                     default:
                        // The author of the swap of event registration is hidden
                        $Author = EVENT_HIDDEN_FAMILY_DATA;
                        break;
                 }
             }

             $Author .= generateInputField("hidSupportMemberID", "hidden", "", "", "", $SwapEventRegistrationRecord["SupportMemberID"]);

             /******************************************************************************************
              *                     Part about event and family of the requestor                       *
              ******************************************************************************************/
             // We get infos about the concerned event (of the family who requests the swap)
             $EventRecord = getTableRecordInfos($DbConnection, "Events", $SwapEventRegistrationRecord['RequestorEventID']);
             $TownRecord = getTableRecordInfos($DbConnection, 'Towns', $EventRecord['TownID']);

             if ($bClosed)
             {
                 // Info about the requestor event in read mode
                 $RequestorEventInfos = "<dl class=\"EventInfos\">\n<dt>".stripslashes($EventRecord['EventTitle'])."</dt>\n";
                 $RequestorEventInfos .= "<dd>".$TownRecord['TownName']." (".$TownRecord['TownCode'].")</dd>\n";
                 $RequestorEventInfos .= "<dd>".getEventTypeName($DbConnection, $EventRecord['EventTypeID'])."</dd>\n";
                 $RequestorEventInfos .= "</dl>\n";

                 $RequestorEventDescription = stripslashes(nullFormatText($EventRecord["EventDescription"]));
             }
             else
             {
                 switch($cUserRole)
                 {
                     case EVENT_SWAPPED_REGISTRATION_ACCEPTOR:
                         // The logged supporter is the acceptor family : we display the opened events list
                         // We display the list of opened events
                         $ArrayEventID = array(0);
                         $ArrayEventInfos = array('');

                         $ArrayEvents = dbSearchEvent($DbConnection, array("EventStartDate" => array(date('Y-m-d'), '>'),
                                                                           "Activated" => TRUE), "EventStartDate", 1, 0);
                         if (isset($ArrayEvents['EventID']))
                         {
                             foreach($ArrayEvents['EventID'] as $e => $CurrentEventID)
                             {
                                 // We don't keep the event of the acceptor family
                                 if ($CurrentEventID !== $SwapEventRegistrationRecord["AcceptorEventID"])
                                 {
                                     $ArrayEventID[] = $CurrentEventID;
                                     $ArrayEventInfos[] = $ArrayEvents['EventTitle'][$e].' ('
                                                          .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($ArrayEvents['EventStartDate'][$e]))
                                                          .', '.$ArrayEvents['TownCode'][$e].' '.$ArrayEvents['TownName'][$e].')';
                                 }
                             }
                         }

                         unset($ArrayEvents);

                         $RequestorEventInfos = generateSelectField("lRequestorEventID", $ArrayEventID, $ArrayEventInfos, 0, "");

                         // We hide the description of the event
                         $RequestorEventDescription = EVENT_HIDDEN_FAMILY_DATA;
                         break;

                     default:
                         // Info about the requestor event in read mode
                         $RequestorEventInfos = "<dl class=\"EventInfos\">\n<dt>".stripslashes($EventRecord['EventTitle'])."</dt>\n";
                         $RequestorEventInfos .= "<dd>".$TownRecord['TownName']." (".$TownRecord['TownCode'].")</dd>\n";
                         $RequestorEventInfos .= "<dd>".getEventTypeName($DbConnection, $EventRecord['EventTypeID'])."</dd>\n";
                         $RequestorEventInfos .= "</dl>\n";

                         $RequestorEventDescription = stripslashes(nullFormatText($EventRecord["EventDescription"]));
                         break;
                 }
             }

             $RequestorEventDates = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventStartDate"]));
             if (!empty($EventRecord["EventStartTime"]))
             {
                 // Start time
                 $RequestorEventDates .= " (".$EventRecord["EventStartTime"].")";
             }

             if (!empty($EventRecord["EventEndDate"]))
             {
                 $RequestorEventDates .= " -> ";
                 $RequestorEventDates .= date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($EventRecord["EventEndDate"]));
                 if (!empty($EventRecord["EventEndTime"]))
                 {
                     // End time
                     $RequestorEventDates .= " (".$EventRecord["EventEndTime"].")";
                 }
             }

             // School year of the start date of the event
             $CurrentSchoolYear = getSchoolYear($EventRecord["EventStartDate"]);

             // <<< Requestor Family ID SELECTFIELD >>>
             if ($bClosed)
             {
                 // Get the lastname of the requestor family
                 $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $SwapEventRegistrationRecord["RequestorFamilyID"]);
                 $RequestorFamily = $FamilyRecord['FamilyLastname'];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         if ($LoggedFamilyID == $SwapEventRegistrationRecord["RequestorFamilyID"])
                         {
                             // We display the lastname
                             $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $LoggedFamilyID);
                             $RequestorFamily = $FamilyRecord['FamilyLastname'];
                         }
                         else
                         {
                             // We hide the lastname
                             $RequestorFamily = EVENT_HIDDEN_FAMILY_DATA;
                         }
                         break;

                     case FCT_ACT_READ_ONLY:
                         // Get the lastname of the family
                         $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $SwapEventRegistrationRecord["RequestorFamilyID"]);
                         $RequestorFamily = $FamilyRecord['FamilyLastname'];
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         switch($cUserRole)
                         {
                             case EVENT_SWAPPED_REGISTRATION_ACCEPTOR:
                                 // Generate the list of registered families
                                 $ArrayFamilyID = array(0);
                                 $ArrayFamilyLastname = array('');

                                 $ArrayEventRegistrations = dbSearchEventRegistration($DbConnection,
                                                                                      array('EventID' => $SwapEventRegistrationRecord["RequestorEventID"]),
                                                                                      "FamilyLastname", 1, 0);
                                 if (isset($ArrayEventRegistrations['FamilyID']))
                                 {
                                     $ArrayFamilyID = array_merge($ArrayFamilyID, $ArrayEventRegistrations['FamilyID']);
                                     $ArrayFamilyLastname = array_merge($ArrayFamilyLastname, $ArrayEventRegistrations['FamilyLastname']);
                                 }

                                 $RequestorFamily = generateSelectField("lRequestorFamilyID", $ArrayFamilyID, $ArrayFamilyLastname, 0, "");
                                 unset($ArrayEventRegistrations);
                                 break;

                             default:
                                 switch($GLOBALS['CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                                 {
                                     case EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                                         $ArrayFamilyID = array();
                                         $ArrayFamilyLastname = array();

                                         // Get families already registered to the event (of the requestor)
                                         // Only the logged family
                                         $ArrayEventRegistrations = dbSearchEventRegistration($DbConnection,
                                                                                              array('EventID' => $SwapEventRegistrationRecord["RequestorEventID"],
                                                                                                    'FamilyID' => $SwapEventRegistrationRecord["RequestorFamilyID"]),
                                                                                              "FamilyLastname", 1, 0);

                                         if (isset($ArrayEventRegistrations['FamilyID']))
                                         {
                                             $ArrayFamilies['FamilyID'] = array($ArrayEventRegistrations['FamilyID'][0]);
                                             $ArrayFamilies['FamilyLastname'] = array($ArrayEventRegistrations['FamilyLastname'][0]);
                                         }
                                         break;

                                     default:
                                         // Generate the list of registered families
                                         $ArrayEventRegistrations = dbSearchEventRegistration($DbConnection,
                                                                                              array('EventID' => $SwapEventRegistrationRecord["RequestorEventID"]),
                                                                                              "FamilyLastname", 1, 0);
                                         if (isset($ArrayEventRegistrations['FamilyID']))
                                         {
                                             foreach($ArrayEventRegistrations['FamilyID'] as $er => $CurrentFamilyID)
                                             {
                                                 $ArrayFamilies['FamilyID'][] = $CurrentFamilyID;
                                                 $ArrayFamilies['FamilyLastname'][] = $ArrayEventRegistrations['FamilyLastname'][$er];
                                             }
                                         }

                                         if ($EventSwappedRegistrationID > 0)
                                         {
                                             $ArrayFamilyID = array();
                                             $ArrayFamilyLastname = array();
                                         }
                                         else
                                         {
                                             $ArrayFamilyID = array(0);
                                             $ArrayFamilyLastname = array('');
                                         }
                                         break;
                                 }

                                 if (isset($ArrayFamilies['FamilyID']))
                                 {
                                     $ArrayFamilyID = array_merge($ArrayFamilyID, $ArrayFamilies['FamilyID']);
                                     $ArrayFamilyLastname = array_merge($ArrayFamilyLastname, $ArrayFamilies['FamilyLastname']);
                                 }

                                 $RequestorFamily = generateSelectField("lRequestorFamilyID", $ArrayFamilyID, $ArrayFamilyLastname,
                                                                        $SwapEventRegistrationRecord["RequestorFamilyID"], "");

                                 unset($ArrayFamilies);
                                 break;
                         }
                         break;
                 }
             }

             /******************************************************************************************
              *                     Part about event and family of the acceptor                        *
              ******************************************************************************************/
             // <<< Acceptor Event ID SELECTFIELD >>>
             if ($bClosed)
             {
                 // We get the event of the acceptor
                 $EventRecord = getTableRecordInfos($DbConnection, "Events", $SwapEventRegistrationRecord['AcceptorEventID']);
                 $TownRecord = getTableRecordInfos($DbConnection, 'Towns', $EventRecord['TownID']);
                 $AcceptorEventInfos = "<dl class=\"EventInfos\">\n<dt>".stripslashes($EventRecord['EventTitle'])."</dt>\n";
                 $AcceptorEventInfos .= "<dd>".$TownRecord['TownName']." (".$TownRecord['TownCode'].")</dd>\n";
                 $AcceptorEventInfos .= "<dd>".getEventTypeName($DbConnection, $EventRecord['EventTypeID'])."</dd>\n";
                 $AcceptorEventInfos .= "</dl>\n";
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_PARTIAL_READ_ONLY:
                     case FCT_ACT_READ_ONLY:
                         // We get the event of the acceptor
                         if (empty($SwapEventRegistrationRecord['AcceptorEventID']))
                         {
                             // No selected event
                             $AcceptorEventInfos = "&nbsp;";
                         }
                         else
                         {
                             $EventRecord = getTableRecordInfos($DbConnection, "Events", $SwapEventRegistrationRecord['AcceptorEventID']);
                             $TownRecord = getTableRecordInfos($DbConnection, 'Towns', $EventRecord['TownID']);
                             $AcceptorEventInfos = "<dl class=\"EventInfos\">\n<dt>".stripslashes($EventRecord['EventTitle'])."</dt>\n";
                             $AcceptorEventInfos .= "<dd>".$TownRecord['TownName']." (".$TownRecord['TownCode'].")</dd>\n";
                             $AcceptorEventInfos .= "<dd>".getEventTypeName($DbConnection, $EventRecord['EventTypeID'])."</dd>\n";
                             $AcceptorEventInfos .= "</dl>\n";
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         switch($cUserRole)
                         {
                             case EVENT_SWAPPED_REGISTRATION_ACCEPTOR:
                                 // The logged supporter is the acceptor family : we display only his event
                                 $RecordAcceptorEvent = getTableRecordInfos($DbConnection, 'Events',
                                                                            $SwapEventRegistrationRecord["AcceptorEventID"]);
                                 $RecordAcceptorTown = getTableRecordInfos($DbConnection, 'Towns', $RecordAcceptorEvent["TownID"]);

                                 $ArrayEventID = array($SwapEventRegistrationRecord["AcceptorEventID"]);
                                 $ArrayEventInfos = array($RecordAcceptorEvent['EventTitle'].' ('
                                                          .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($RecordAcceptorEvent['EventStartDate']))
                                                          .', '.$RecordAcceptorTown['TownCode'].' '.$RecordAcceptorTown['TownName'].')');

                                 unset($RecordAcceptorEvent, $RecordAcceptorTown);
                                 break;

                             default:
                                 // We display the list of opened events
                                 if ($EventSwappedRegistrationID > 0)
                                 {
                                     $ArrayEventID = array();
                                     $ArrayEventInfos = array();
                                 }
                                 else
                                 {
                                     $ArrayEventID = array(0);
                                     $ArrayEventInfos = array('');
                                 }

                                 $ArrayEvents = dbSearchEvent($DbConnection, array("EventStartDate" => array(date('Y-m-d'), '>'),
                                                                                   "Activated" => TRUE), "EventStartDate", 1, 0);
                                 if (isset($ArrayEvents['EventID']))
                                 {
                                     foreach($ArrayEvents['EventID'] as $e => $CurrentEventID)
                                     {
                                         if ($CurrentEventID != $SwapEventRegistrationRecord["RequestorEventID"])
                                         {
                                             $ArrayEventID[] = $CurrentEventID;
                                             $ArrayEventInfos[] = $ArrayEvents['EventTitle'][$e].' ('
                                                                  .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($ArrayEvents['EventStartDate'][$e]))
                                                                  .', '.$ArrayEvents['TownCode'][$e].' '.$ArrayEvents['TownName'][$e].')';
                                         }
                                     }
                                 }

                                 unset($ArrayEvents);
                                 break;
                         }

                         $AcceptorEventInfos = generateSelectField("lAcceptorEventID", $ArrayEventID, $ArrayEventInfos,
                                                                   $SwapEventRegistrationRecord["AcceptorEventID"], "");
                         break;
                 }
             }

             // <<< Acceptor Family ID SELECTFIELD >>>
             if ($bClosed)
             {
                 // Get the lastname of the acceptor family
                 $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $SwapEventRegistrationRecord["AcceptorFamilyID"]);
                 $AcceptorFamily = $FamilyRecord['FamilyLastname'];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_READ_ONLY:
                     case FCT_ACT_PARTIAL_READ_ONLY:
                         // Get the lastname of the family
                         if (empty($SwapEventRegistrationRecord["AcceptorFamilyID"]))
                         {
                             // No selected family
                             $AcceptorFamily = "&nbsp;";
                         }
                         else
                         {
                             $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $SwapEventRegistrationRecord["AcceptorFamilyID"]);
                             $AcceptorFamily = $FamilyRecord['FamilyLastname'];
                         }
                         break;

                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         switch($cUserRole)
                         {
                             case EVENT_SWAPPED_REGISTRATION_ACCEPTOR:
                                 // The logged supporter is the acceptor family : we display only his lastname
                                 $AcceptorLastname = getFamilyLastname($DbConnection, $SwapEventRegistrationRecord["AcceptorFamilyID"]);

                                 $ArrayFamilyID = array($SwapEventRegistrationRecord["AcceptorFamilyID"]);
                                 $ArrayFamilyLastname = array($AcceptorLastname);
                                 break;

                             default:
                                 // Generate the list of activated families
                                 $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear)),
                                                                 "FamilyLastname", 1, 0);

                                 if ($EventSwappedRegistrationID > 0)
                                 {
                                     $ArrayFamilyID = array();
                                     $ArrayFamilyLastname = array();
                                 }
                                 else
                                 {
                                     $ArrayFamilyID = array(0);
                                     $ArrayFamilyLastname = array('');
                                 }

                                 if (isset($ArrayFamilies['FamilyID']))
                                 {
                                     foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                     {
                                         if ($CurrentFamilyID !== $SwapEventRegistrationRecord["RequestorFamilyID"])
                                         {
                                             $ArrayFamilyID[] = $CurrentFamilyID;
                                             $ArrayFamilyLastname[] = $ArrayFamilies['FamilyLastname'][$f];
                                         }
                                     }
                                 }

                                 unset($ArrayFamilies);
                                 break;
                         }

                         $AcceptorFamily = generateSelectField("lAcceptorFamilyID", $ArrayFamilyID, $ArrayFamilyLastname,
                                                               $SwapEventRegistrationRecord["AcceptorFamilyID"], "");
                         break;
                 }
             }

             // Closing Date
             if ($bClosed)
             {
                 $ClosingDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'].' '.$GLOBALS['CONF_TIME_DISPLAY_FORMAT'],
                                     strtotime($SwapEventRegistrationRecord['EventSwappedRegistrationClosingDate']));
             }
             else
             {
                 // No closing date
                 $ClosingDate = "&nbsp;";
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CREATION_DATE"]."</td><td class=\"Value\">$CreationDate, $Author</td>\n</tr>\n";
             switch($cUserRole)
             {
                 case EVENT_SWAPPED_REGISTRATION_AUTHOR:
                 case EVENT_SWAPPED_REGISTRATION_REQUESTOR:
                 case EVENT_SWAPPED_REGISTRATION_ACCEPTOR:
                     echo "<tr>\n\t<td class=\"Label $RequestorStyle\" colspan=\"4\">".$GLOBALS["LANG_SWAP_EVENT_REGISTRATION_REQUESTOR_EVENT"]."</td>\n</tr>\n";
                     echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY"]."*</td><td class=\"Value\">$RequestorFamily</td><td class=\"Label\" rowspan=\"2\">".$GLOBALS["LANG_EVENT"]."</td><td class=\"Value\" rowspan=\"2\">$RequestorEventInfos</td>\n</tr>\n";
                     echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_REGISTRATION_EVENT_DATES"]."</td><td class=\"Value\">$RequestorEventDates</td>\n</tr>\n";
                     echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_DESCRIPTION"]."</td><td class=\"Value\" colspan=\"3\">$RequestorEventDescription</td>\n</tr>\n";
                     echo "<tr>\n\t<td class=\"Label $AcceptorStyle\" colspan=\"4\">".$GLOBALS["LANG_SWAP_EVENT_REGISTRATION_ACCEPTOR_EVENT"]."</td>\n</tr>\n";
                     echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY"]."*</td><td class=\"Value\">$AcceptorFamily</td><td class=\"Label\">".$GLOBALS["LANG_EVENT"]."</td><td class=\"Value\">$AcceptorEventInfos</td>\n</tr>\n";
                     break;
             }

             if ($bClosed)
             {
                 // Display the closing date of the swap of registration
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SWAP_EVENT_REGISTRATION_CLOSING_DATE"]."</td><td class=\"Value\">$ClosingDate</td><td class=\"Label\">&nbsp;</td><td class=\"Value\">&nbsp;</td>\n</tr>\n";
             }

             echo "</table>\n";

             insertInputField("hidEventSwappedRegistrationID", "hidden", "", "", "", $EventSwappedRegistrationID);
             insertInputField("hidEventID", "hidden", "", "", "", $SwapEventRegistrationRecord["RequestorEventID"]);
             insertInputField("hidFamilyID", "hidden", "", "", "", $SwapEventRegistrationRecord["RequestorFamilyID"]);
             closeStyledFrame();

             if (!$bClosed)
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         // We display the buttons
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                         break;
                 }
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a swap of event registration
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
 * Display the form to submit a new event type or update an event type, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-27
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $EventTypeID              String                ID of the event type to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view event types
 */
 function displayDetailsEventTypeForm($DbConnection, $EventTypeID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         /// The supporter must be allowed to create or update an event type
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($HolidayID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsEventType", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationEventType('".$GLOBALS["LANG_ERROR_JS_MANDORY_FIELDS"]."')");

             // <<< EventTypeID >>>
             if ($EventTypeID == 0)
             {
                 // Define default values to create the new event type
                 $Reference = "";
                 $EventTypeRecord = array(
                                          "EventTypeName" => '',
                                          "EventTypeCategory" => -1
                                         );
             }
             else
             {
                 if (isExistingEventType($DbConnection, $EventTypeID))
                 {
                     // We get the details of the event type
                     $EventTypeRecord = getTableRecordInfos($DbConnection, "EventTypes", $EventTypeID);
                     $Reference = $EventTypeID;
                 }
                 else
                 {
                     // Error, the event type doesn't exist
                     $EventTypeID = 0;
                     $Reference = "";
                 }
             }

             // Display the table (frame) where the form will take place
             $FrameTitle = substr($GLOBALS["LANG_EVENT_TYPE"], 0, -1);
             if (!empty($EventTypeID))
             {
                 $FrameTitle .= " ($Reference)";
             }

             openStyledFrame($FrameTitle, "Frame", "Frame", "DetailsNews");

             // <<< EventTypeName INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sEventTypeName = stripslashes($EventTypeRecord["EventTypeName"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sEventTypeName = generateInputField("sEventTypeName", "text", "25", "15", $GLOBALS["LANG_EVENT_TYPE_NAME_TIP"],
                                                          $EventTypeRecord["EventTypeName"]);
                     break;
             }

             // <<< EventTypeCategory SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sEventTypeCategory = "";
                     if (!empty($EventTypeID))
                     {
                         $sEventTypeCategory = $GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES'][$EventTypeRecord['EventTypeCategory']];
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $ArrayCategoryID = array_keys($GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES']);
                     $ArrayCategoryNames = $GLOBALS['CONF_COOP_EVENT_TYPE_CATEGORIES'];
                     if (empty($EventTypeID))
                     {
                         // New event type
                         $ArrayCategoryID = array_merge(array(-1), $ArrayCategoryID);
                         $ArrayCategoryNames = array_merge(array(""), $ArrayCategoryNames);
                     }

                     $sEventTypeCategory = generateSelectField("lEventTypeCategory", $ArrayCategoryID,
                                                               $ArrayCategoryNames, $EventTypeRecord['EventTypeCategory']);
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_TYPE_NAME"]."*</td><td class=\"Value\">$sEventTypeName</td><td class=\"Label\">".$GLOBALS["LANG_EVENT_TYPE_CATEGORY"]."*</td><td class=\"Value\">$sEventTypeCategory</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidEventTypeID", "hidden", "", "", "", $EventTypeID);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     // We display the buttons
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update an event type
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
 * Display the form to search an event type in the current web  page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-27
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some event types
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the event types found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the event types. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about an event type. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update event types
 */
 function displaySearchEventTypeForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to event types list
         $cUserAccess = FCT_ACT_NO_RIGHTS;
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormSearchEventType", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             // <<<< EventTypeCategory SELECTFIELD >>>>
             if ((isset($TabParams['EventTypeCategory'])) && (count($TabParams['EventTypeCategory']) > 0))
             {
                 $SelectedItem = $TabParams['EventTypeCategory'][0];
             }
             else
             {
                 $SelectedItem = -1;
             }

             $EventTypeCategories = generateSelectField("lEventTypeCategory", array_merge(array(-1), array_keys($GLOBALS["CONF_COOP_EVENT_TYPE_CATEGORIES"])),
                                                        array_merge(array(''), $GLOBALS["CONF_COOP_EVENT_TYPE_CATEGORIES"]),
                                                        zeroFormatValue(existedPOSTFieldValue("lEventTypeCategory",
                                                                                              existedGETFieldValue("lEventTypeCategory",
                                                                                                                   $SelectedItem))), '');

             // Family lastname input text
             $sEventTypeName = generateInputField("sEventTypeName", "text", "25", "13", $GLOBALS["LANG_EVENT_TYPE_NAME_TIP"],
                                                 stripslashes(strip_tags(existedPOSTFieldValue("sEventTypeName",
                                                                                               stripslashes(existedGETFieldValue("sEventTypeName", ""))))));

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_EVENT_TYPE_CATEGORY"]."</td><td class=\"Value\">$EventTypeCategories</td><td class=\"Label\">".$GLOBALS["LANG_EVENT_TYPE"]."</td><td class=\"Value\">$sEventTypeName</td>\n</tr>\n";
             echo "</table>\n";

             // Display the hidden fields
             insertInputField("hidOrderByField", "hidden", "", "", "", $OrderBy);
             insertInputField("hidOnPrint", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnPrint", existedGETFieldValue("hidOnPrint", ""))));
             insertInputField("hidOnExport", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnExport", existedGETFieldValue("hidOnExport", ""))));
             insertInputField("hidExportFilename", "hidden", "", "", "", existedPOSTFieldValue("hidExportFilename", existedGETFieldValue("hidExportFilename", "")));
             closeStyledFrame();

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();

             // The supporter has executed a search
             $NbTabParams = count($TabParams);
             if ($NbTabParams > 0)
             {
                 displayBR(2);

                 $ArrayCaptions = array($GLOBALS["LANG_EVENT_TYPE"], $GLOBALS["LANG_EVENT_TYPE_CATEGORY"]);
                 $ArraySorts = array("EventTypeName", "EventTypeCategory");

                 // Order by instruction
                 if ((abs($OrderBy) <= count($ArraySorts)) && ($OrderBy != 0))
                 {
                     $StrOrderBy = $ArraySorts[abs($OrderBy) - 1];
                     if ($OrderBy < 0)
                     {
                         $StrOrderBy .= " DESC";
                     }
                 }
                 else
                 {
                     $StrOrderBy = "EventTypeName ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchEventType($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only event types of the page
                     $ArrayRecords = dbSearchEventType($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some events found
                     foreach($ArrayRecords["EventTypeID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the event type
                             $ArrayData[0][] = $ArrayRecords["EventTypeName"][$i];
                         }
                         else
                         {
                             // We display the event type with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["EventTypeName"][$i], $ArrayRecords["EventTypeID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = $GLOBALS["CONF_COOP_EVENT_TYPE_CATEGORIES"][$ArrayRecords["EventTypeCategory"][$i]];

                         // Hyperlink to delete the event type if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[2][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteEventType.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the event types found
                     $ArraySortedFields = array("1", "2");
                     if ($bCanDelete)
                     {
                         $ArrayCaptions[] = '&nbsp;';
                         $ArraySorts[] = "";
                         $ArraySortedFields[] = "";
                     }

                     displayStyledTable($ArrayCaptions, $ArraySortedFields, $SortFct, $ArrayData, '', '', '', '',
                                        array(), $OrderBy, array());

                     // Display the previous and next links
                     $NoPage = 0;
                     if ($Page <= 1)
                     {
                         $PreviousLink = '';
                     }
                     else
                     {
                         $NoPage = $Page - 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             // GET form
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['SchoolYear']))
                                 {
                                     $CurrentValue = $TabParams['SchoolYear'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $PreviousLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $PreviousLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }

                     if ($Page < ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]))
                     {
                         $NoPage = $Page + 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['SchoolYear']))
                                 {
                                     $CurrentValue = $TabParams['SchoolYear'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $NextLink .= "&amp;lSchoolYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $NextLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }
                     else
                     {
                         $NextLink = '';
                     }

                     displayPreviousNext("&nbsp;".$GLOBALS["LANG_PREVIOUS"], $PreviousLink, $GLOBALS["LANG_NEXT"]."&nbsp;", $NextLink,
                                         '', $Page, ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]));

                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NB_RECORDS_FOUND'].$NbRecords;
                     closeParagraph();
                 }
                 else
                 {
                     // No event type found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of event types
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }
?>