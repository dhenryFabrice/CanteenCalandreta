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
 * Support module : list all events not closed
 *
 * @author Christophe javouhey
 * @version 3.4
 * @since 2019-11-28
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 $LaunchSearch = FALSE;

 // To take into account the page of the events to display
 if (!empty($_GET["Pg"]))
 {
     $Page = (integer)strip_tags($_GET["Pg"]);
     $ParamsPOST_GET = "_GET";
     $LaunchSearch = TRUE;
 }
 else
 {
     $Page = 1;
     $ParamsPOST_GET = "_POST";
 }

 // To take into account the order by field to sort the table of the events
 if (!empty($_POST["hidOrderByField"]))
 {
     $OrderBy = $_POST["hidOrderByField"];
 }
 else
 {
     if (!empty($_GET["Ob"]))
     {
         $OrderBy = (integer)strip_tags($_GET["Ob"]);
     }
     else
     {
         $OrderBy = existedGETFieldValue("hidOrderByField", 0);
     }
 }

 //################################ FORM PROCESSING ##########################
 // We define the params structure containing the search criterion
 $TabParams = array();

 if ((!empty($_POST["bSubmit"])) || (array_key_exists("hidOrderByField", $_POST)) || ($LaunchSearch))
 {
     // <<< School year field >>>
     $SchoolYear = ${$ParamsPOST_GET}["lSchoolYear"];
     if ($SchoolYear != 0)
     {
         $TabParams["SchoolYear"] = array($SchoolYear);
     }

     // <<< Event type field >>>
     $EventTypeID = ${$ParamsPOST_GET}["lEventTypeID"];
     if ($EventTypeID > 0)
     {
         $TabParams["EventTypeID"] = array($EventTypeID);
     }

     // <<< Family lastname field >>>
     if (array_key_exists("sLastname", ${$ParamsPOST_GET}))
     {
         $Lastname = strip_tags(${$ParamsPOST_GET}["sLastname"]);
         if (!empty($Lastname))
         {
             $TabParams["FamilyLastname"] = $Lastname;
         }
     }

     // Only events with opened registrations
     if (array_key_exists("chkOpenedRegistrations", ${$ParamsPOST_GET}))
     {
         $TabParams["OpenedRegistrations"] = 1;
     }

     // Only activated events
     $TabParams["Activated"] = TRUE;

     // Only parent events
     $TabParams["ParentEvents"] = TRUE;

     // Include event children in the search but display only parent events
     $TabParams["EventChildrenIncluded"] = TRUE;

     // To launch the search
     $TabParams["All"] = TRUE;
 }
 else
 {
     // First display
     // Current school year
     $TabParams["SchoolYear"] = array(getSchoolYear(date('Y-m-d')));
     $TabParams["Activated"] = TRUE;
     $TabParams["ParentEvents"] = TRUE;
     $TabParams["All"] = TRUE;
 }
 //################################ END FORM PROCESSING ##########################

 $HidOnPrint = 0;
 if (isSet(${$ParamsPOST_GET}["hidOnPrint"]))
 {
     $HidOnPrint = ${$ParamsPOST_GET}["hidOnPrint"];
 }

 if ($HidOnPrint == 0)
 {
     // Display the search form and the result
     initGraphicInterface(
                          $LANG_INTRANET_NAME,
                          array(
                                '../../GUI/Styles/styles.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array(
                                '../../Common/JSSortFct/SortFct.js',
                                '../Verifications.js'
                               )
                         );
     openWebPage();

     // Display invisible link to go directly to content
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#EventsList', 'Accessibility');

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

         displaySupportMemberContextualMenu("cooperation", 1, Coop_AllEventsList);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_ALL_EVENTS_LIST_PAGE_TITLE, 2);

     openParagraph();
     echo $LANG_SUPPORT_ALL_EVENTS_LIST_PAGE_INTRODUCTION;
     closeParagraph();

     // Define which form fields are displayed
     switch($_SESSION['SupportMemberStateID'])
     {
         case 1:
         case 6:
         case 7:
             $ArrayDisplayedFormFields = array(); // All fields
             break;

         default:
             $ArrayDisplayedFormFields = array(
                                               "lSchoolYear" => true,
                                               "lEventTypeID" => true,
                                               "sLastname" => false,
                                               "chkOpenedRegistrations" => true
                                              );
             break;
     }

     displaySearchEventForm($DbCon, $TabParams, "AllEventsList.php", $Page, "SortFct", $OrderBy, "UpdateEvent.php",
                            $CONF_ACCESS_APPL_PAGES[FCT_EVENT], $ArrayDisplayedFormFields);

     // Exporting?
     $HidOnExport = 0;
     if (isSet(${$ParamsPOST_GET}["hidOnExport"]))
     {
         $HidOnExport = ${$ParamsPOST_GET}["hidOnExport"];
     }

     if ($HidOnExport == 1)
     {
         exportSearchEventForm($DbCon, $TabParams, ${$ParamsPOST_GET}["hidExportFilename"]);

         openParagraph('InfoMsg');
         displayStyledLinkText($LANG_DOWNLOAD, $CONF_EXPORT_DIRECTORY.${$ParamsPOST_GET}["hidExportFilename"], '',
                               $LANG_DOWNLOAD_EXPORT_TIP, '_blank');
         closeParagraph();
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
 }
 else
 {
     // Print the web page
     printSearchEventForm($DbCon, $TabParams);

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>