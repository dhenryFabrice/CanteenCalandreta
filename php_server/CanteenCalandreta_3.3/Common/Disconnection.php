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
 * Common module : Allow a user to destroy his session ; it's a disconnection
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : DB.php is loaded in DbLibrary.php
 *
 * @since 2012-01-10
 */

 // Delete the session of OpenID
 session_name('Clamshell');
 session_start();
 session_destroy();

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "support member" session or use the opened session
 session_start();

 // Define the Url used for the redirection
 $ArrayCurrentURL = explode("/", $_SERVER["HTTP_REFERER"]);
 $ArraySupportURL = explode("/", $CONF_URL_SUPPORT);

 // Support module
 $ArraySupportURLRewriting = array();
 foreach($CONF_REWRITING_URL_SUPPORT as $u => $CurrentUrl)
 {
     $ArrayTmp = explode("/", $CurrentUrl);
     $ArraySupportURLRewriting[] = strToLower($ArrayTmp[4]);
 }

 $sLcCurrentURL = strToLower($ArrayCurrentURL[4]);

 if (($sLcCurrentURL == strToLower($ArraySupportURL[4])) || ($sLcCurrentURL == "plugins"))
 {
     // The user came from the Support module
     $Url = $CONF_URL_SUPPORT."index.php";

     if ((isset($_SESSION["SupportMemberID"])) && ($_SESSION["SupportMemberID"] > 0))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Log the event
         logEvent($DbCon, EVT_SYSTEM, EVT_SERV_LOGIN, EVT_ACT_LOGOUT, $_SESSION["SupportMemberID"]);

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
 }
 else
 {
     $iPosUrl = array_search($sLcCurrentURL, $ArraySupportURLRewriting);
     if ($iPosUrl !== FALSE)
     {
         // The user came from the Support module (but thank to an url rewriting)
         $Url = $CONF_REWRITING_URL_SUPPORT[$iPosUrl]."index.php";

         // Connection to the database
         $DbCon = dbConnection();

         // Log the event
         logEvent($DbCon, EVT_SYSTEM, EVT_SERV_LOGIN, EVT_ACT_LOGOUT, $_SESSION["SupportMemberID"]);

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
 }

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array('../GUI/Styles/styles.css' => 'screen'),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      '',
                      "Redirection('$Url', $CONF_TIME_LAG)"
                     );
 openWebPage();

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displayMainMenu(array(), array(), array());

 // Content of the web page
 openArea('id="content"');

 displayLogout();

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

 // Footer of the application
 displayFooter($LANG_INTRANET_FOOTER);

 // Close the web page
 closeWebPage();

 closeGraphicInterface();
?>