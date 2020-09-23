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
 * Support module : list opened special days for the school
 *
 * @author Christophe javouhey
 * @version 3.0
 * @since 2016-10-26
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

 // To take into account the page of the opened special days to display
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

 // To take into account the order by field to sort the table of the opened special days
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
     // <<< OpenedSpecialDayDescription field >>>
     if (array_key_exists("sDescription", ${$ParamsPOST_GET}))
     {
         $Description = trim(strip_tags(${$ParamsPOST_GET}["sDescription"]));
         if (!empty($Description))
         {
             $TabParams["OpenedSpecialDayDescription"] = $Description;
         }
     }

     // <<< StartDate field >>>
     if (array_key_exists("startDate", ${$ParamsPOST_GET}))
     {
         $StartDate = nullFormatText(formatedDate2EngDate(trim(strip_tags(${$ParamsPOST_GET}["startDate"]))), "NULL");
         if (!empty($StartDate))
         {
             $TabParams["StartDate"] = $StartDate;
         }
     }

     // <<< EndDate field >>>
     if (array_key_exists("endDate", ${$ParamsPOST_GET}))
     {
         $EndDate = nullFormatText(formatedDate2EngDate(trim(strip_tags(${$ParamsPOST_GET}["endDate"]))), "NULL");
         if (!empty($EndDate))
         {
             $TabParams["EndDate"] = $EndDate;
         }
     }

     $TabParams["All"] = TRUE;
 }
 else
 {
     // First display : by default, opened special days of the current school year
     $SchoolYear = getSchoolYear(date('Y-m-d'));

     $TabParams["StartDate"] = getSchoolYearStartDate($SchoolYear);
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
                                '../../Common/JSCalendar/dynCalendar.css' => 'screen',
                                '../Styles_Support.css' => 'screen'
                               ),
                          array(
                                '../../Common/JSCalendar/browserSniffer.js',
                                '../../Common/JSCalendar/dynCalendar.js',
                                '../../Common/JSCalendar/UseCalendar.js',
                                '../../Common/JSSortFct/SortFct.js',
                                '../Verifications.js'
                               )
                         );
     openWebPage();

     // Display invisible link to go directly to content
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#OpenedSpecialDaysList', 'Accessibility');

     // Display the header of the application
     displayHeader($LANG_INTRANET_HEADER);

     // Display the main menu at the top of the web page
     displaySupportMainMenu(1);

     // Content of the web page
     openArea('id="content"');

     // Display the "parameters" contextual menu if the supporter isn't logged, an empty contextual menu otherwise
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Open the contextual menu area
         openArea('id="contextualmenu"');

         displaySupportMemberContextualMenu("admin", 1, Admin_OpenedSpecialDaysList);
         displaySupportMemberContextualMenu("parameters", 1, 0);

         // Display information about the logged user
         displayLoggedUser($_SESSION);

         // Close the <div> "contextualmenu"
         closeArea();

         openArea('id="page"');
     }

     // Display the informations, forms, etc. on the right of the web page
     displayTitlePage($LANG_SUPPORT_ADMIN_OPENED_SPECIAL_DAYS_LIST_PAGE_TITLE, 2);

     openParagraph();
     echo $LANG_SUPPORT_ADMIN_OPENED_SPECIAL_DAYS_LIST_PAGE_INTRODUCTION;
     closeParagraph();


     // Display a button to add a new opened special day
     openParagraph();
     echo generateStyledPictureHyperlink($GLOBALS["CONF_ADD_ICON"], "../Admin/AddOpenedSpecialDay.php?Cr=".md5('')."&amp;Id=",
                                         "$LANG_SUPPORT_ADMIN_OPENED_SPECIAL_DAYS_LIST_PAGE_ADD_OPENED_SPECIAL_DAY.", 'Affectation',
                                         '_blank')
          ." $LANG_SUPPORT_ADMIN_OPENED_SPECIAL_DAYS_LIST_PAGE_ADD_OPENED_SPECIAL_DAY.";
     closeParagraph();

     displaySearchOpenedSpecialDaysForm($DbCon, $TabParams, "OpenedSpecialDaysList.php", $Page, "SortFct", $OrderBy,
                                        "UpdateOpenedSpecialDay.php", $CONF_ACCESS_APPL_PAGES[FCT_ADMIN]);

     // Exporting?
     $HidOnExport = 0;
     if (isSet(${$ParamsPOST_GET}["hidOnExport"]))
     {
         $HidOnExport = ${$ParamsPOST_GET}["hidOnExport"];
     }

     if ($HidOnExport == 1)
     {
         exportSearchOpenedSpecialDaysForm($DbCon, $TabParams, ${$ParamsPOST_GET}["hidExportFilename"]);

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
     printSearchOpenedSpecialDaysForm($DbCon, $TabParams);

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
?>