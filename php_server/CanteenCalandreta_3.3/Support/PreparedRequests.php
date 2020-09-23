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
 * Support module : allow a logged supporter to execute one of his prepared requests
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2004-02-21
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // Connection to the database
 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon);

 $LaunchSearch = FALSE;

 // To take into account the page of the asks of work to display
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

 // To take into account the order by field to sort the table of the asks of work
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
         $OrderBy = 0;
     }
 }

 // Include the prepared requests parameters
 include_once("PreparedRequestsParameters.php");

 // Include the prepared requests functions
 include_once("PreparedRequestsFunctions.php");

 //################################ FORM PROCESSING ##########################
 $TabData = array();
 $NbRecords = 0;
 $bFunctionExists = TRUE;
 if ((!empty($_POST["bSubmit"])) || (array_key_exists("hidOrderByField", $_POST)) || ($LaunchSearch))
 {
     // We get the selected prepared request
     $PreparedRequestID = ${$ParamsPOST_GET}["hidPreparedRequestID"];

     // We get the function name to execute
     $ArrayKeys = array_keys($PREPARED_REQUESTS_PARAMETERS[$_SESSION["SupportMemberID"]]);
     $FunctionName = $PREPARED_REQUESTS_PARAMETERS[$_SESSION["SupportMemberID"]][$ArrayKeys[$PreparedRequestID]][Fctname];

     // We get the filednames to display
     $TabViewFieldnames = $PREPARED_REQUESTS_PARAMETERS[$_SESSION["SupportMemberID"]][$ArrayKeys[$PreparedRequestID]][Fieldnames];

     // We get the function parameters
     $TabParams = $PREPARED_REQUESTS_PARAMETERS[$_SESSION["SupportMemberID"]][$ArrayKeys[$PreparedRequestID]][Params];

     if (isset(${$ParamsPOST_GET}["hidMoreParameters"]))
     {
         // Add other parameters stored in $_POST to initial parameters of the prepared request
         $TabParams["MoreParameters"] = $_POST;
     }

     // Order by instruction
     $ArrayOrderBy = $PREPARED_REQUESTS_ALL_ORDER_BY_FIELDNAMES;
     if ((abs($OrderBy) <= count($ArrayOrderBy)) && ($OrderBy != 0))
     {
         $StrOrderBy = $ArrayOrderBy[abs($OrderBy) - 1];
         if ($OrderBy < 0)
         {
             $StrOrderBy .= " DESC";
         }
     }
     else
     {
         $StrOrderBy = "AowRef ASC";
     }

     // Check if the function exists
     if ($bFunctionExists = function_exists($FunctionName))
     {
         // Log event
         logEvent($DbCon, EVT_PROFIL, EVT_SERV_PREPARED_REQUEST, EVT_ACT_EXECUTE, $_SESSION['SupportMemberID'], 0, array('FuncName' => $FunctionName));

         $PreparedRequestResult = call_user_func($FunctionName, $DbCon, $TabParams, $StrOrderBy);
         $TabData = $PreparedRequestResult[0];
         $NbRecords = $PreparedRequestResult[1];

         if (count($PreparedRequestResult) > 2)
         {
             $HtmlCode = $PreparedRequestResult[2];
         }
         else
         {
             $HtmlCode = "";
         }
     }
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                     $LANG_INTRANET_NAME,
                     array(
                           '../GUI/Styles/styles.css' => 'screen',
                           'Styles_Support.css' => 'screen'
                          ),
                     array(
                           '../Common/JSSortFct/SortFct.js',
                           'Verifications.js'
                          )
                    );
 openWebPage();

 // Display invisible link to go directly to content
 displayStyledLinkText($LANG_GO_TO_CONTENT, '#PreparedRequestsList', 'Accessibility');

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu();

 // Content of the web page
 openArea('id="content"');

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("parameters", 0, Param_PreparedRequests);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_PREPARED_REQUESTS_PAGE_TITLE, 2);

 openParagraph();
 displayStyledText($LANG_SUPPORT_PREPARED_REQUESTS_PAGE_INTRODUCTION, '');
 closeParagraph();

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Get the number of prepared requests
     $NbPreparedRequests = 0;
     if (isSet($PREPARED_REQUESTS_PARAMETERS[$_SESSION["SupportMemberID"]]))
     {
         $NbPreparedRequests = count($PREPARED_REQUESTS_PARAMETERS[$_SESSION["SupportMemberID"]]);
     }

     if ($NbPreparedRequests > 0)
     {
         // The logged supporter has prepared requests
         displayPreparedRequestForm($DbCon, "PreparedRequests.php", "SortFct", $OrderBy, $Page, $NbRecords, array_keys($TabData), $TabData);

         if ((isSet($HtmlCode)) && ($HtmlCode != ''))
         {
             // HTML code generated by the prepared request
             echo $HtmlCode;
         }
     }
     else
     {
         // The logged supporter hasn't prepared requests
         openParagraph('InfoMsg');
         echo $GLOBALS["LANG_NO_PREPARED_REQUESTS"];
         closeParagraph();
     }

     if (!$bFunctionExists)
     {
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_PREPARED_FUNCTION_NOT_FOUND"];
         closeParagraph();
     }

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
 else
 {
     // The user isn't logged
     openParagraph('ErrorMsg');
     echo $LANG_ERROR_NOT_LOGGED;
     closeParagraph();
 }

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