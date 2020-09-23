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
 * Support module : display the meeting rooms registrations planning to the logged supporter
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

 // Include the stats library
 include_once('../Stats/StatsLibrary.php');

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES'));

 // Get the SupportMemberID of the logged supporter to define the default view type of the planning
 $DefaultViewType = PLANNING_MONTH_VIEW;
 if (isSet($_SESSION["SupportMemberID"]))
 {
     $DefaultViewType = $CONF_MEETING_REGISTRATIONS_DEFAULT_VIEW_TYPES[$_SESSION["SupportMemberStateID"]];
 }

 // To take into account the type of view, month and the year to display
 if (!empty($_POST["lView"]))
 {
     $TypeView = (integer)strip_tags($_POST["lView"]);
     $ParamsPOST_GET = "_POST";
 }
 else
 {
     if (!empty($_GET["lView"]))
     {
         $TypeView = (integer)strip_tags($_GET["lView"]);
         $ParamsPOST_GET = "_GET";
     }
     else
     {
         $TypeView = $DefaultViewType;  // Default view : in relation with the supportmember state ID
         $ParamsPOST_GET = "_GET";
     }
 }

 if ($TypeView < 1)
 {
     $TypeView = $DefaultViewType;  // Default view : in relation with the supportmember state ID
 }

 if (!empty($_POST["lMonth"]))
 {
     $Month = (integer)strip_tags($_POST["lMonth"]);
 }
 else
 {
     if (!empty($_GET["lMonth"]))
     {
         $Month = (integer)strip_tags($_GET["lMonth"]);
     }
     else
     {
         $Month = (integer)(date("m"));  // Current month
     }
 }

 if ($Month < 1)
 {
     $Month = (integer)(date("m"));
 }

 if ($Month > 12)
 {
     $Month = (integer)(date("m"));
 }

 if (!empty($_POST["lWeek"]))
 {
     $Week = (integer)strip_tags($_POST["lWeek"]);
 }
 else
 {
     if (!empty($_GET["lWeek"]))
     {
         $Week = (integer)strip_tags($_GET["lWeek"]);
     }
     else
     {
         $Week = (integer)(date("W"));  // Current week
     }
 }

 if ($Week < 1)
 {
     $Week = (integer)(date("W"));
 }

 if (!empty(${$ParamsPOST_GET}["lYear"]))
 {
     $Year = (integer)strip_tags(${$ParamsPOST_GET}["lYear"]);
     if ($Year < 2003)
     {
         $Year = (integer)(date("Y"));
     }

     if ($Year > 2037)
     {
         $Year = (integer)(date("Y"));
     }
 }
 else
 {
     $Year = (integer)(date("Y")); // Current year
 }

 $NbWeekOfYear = getNbWeeksOfYear($Year);
 if ($Week > $NbWeekOfYear)
 {
     $Week = $NbWeekOfYear;
 }

 //################################ FORM PROCESSING ##########################


 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array('../Verifications.js'),
                      '',
                      ""
                     );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#MeetingRoomsPlanning', 'Accessibility');

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

     displaySupportMemberContextualMenu("cooperation", 1, Coop_MeetingRoomsPlanning);
     displaySupportMemberContextualMenu("parameters", 1, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_MEETING_ROOMS_PLANNING_PAGE_TITLE, 2);

 switch($TypeView)
 {
     case PLANNING_WEEKS_VIEW:
         // We display the planning by several weeks
         // We compute the first day of the selected week
         $FirstDayStamp = strtotime(getFirstDayOfWeek($Week, $Year));
         $EndDayStamp = strtotime('+'.($CONF_PLANNING_WEEKS_TO_DISPLAY - 1).' week', $FirstDayStamp);
         $StartWeek = date('W', $FirstDayStamp);
         $StartMonth = date('m', $FirstDayStamp);
         $StartYear = date('o', $FirstDayStamp);

         openParagraph();
         displayStyledText($LANG_SUPPORT_MEETING_ROOMS_PLANNING_PAGE_INTRODUCTION." S$StartWeek-$StartYear - S".date('W-Y', $EndDayStamp)
                           .' ('.$CONF_PLANNING_MONTHS[$StartMonth - 1].')', "");
         closeParagraph();

         // We display the planning
         displayMeetingRoomPlanningByWeeksForm($DbCon, "MeetingRoomsPlanning.php", $Week, $Year, $CONF_ACCESS_APPL_PAGES[FCT_MEETING]);
         break;

     case PLANNING_MONTH_VIEW:
     default:
         // We display the planning by month
         openParagraph();
         displayStyledText($LANG_SUPPORT_MEETING_ROOMS_PLANNING_PAGE_INTRODUCTION." ".$CONF_PLANNING_MONTHS[$Month - 1]." $Year.", "");
         closeParagraph();

         // We display the planning
         displayMeetingRoomPlanningByMonthForm($DbCon, "MeetingRoomsPlanning.php", $Month, $Year, $CONF_ACCESS_APPL_PAGES[FCT_MEETING]);
         break;
 }

 // Release the connection to the database
 dbDisconnection($DbCon);

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