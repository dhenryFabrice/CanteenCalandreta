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
 * Support module : Home page of the support module
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2015-10-06 : taken into account the FamilyID field of SupportMembers table
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-01-10
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 switch($CONF_SESSIONS_TYPE)
 {
     case SESSION_TYPE_DB:
     case SESSION_TYPE_FILE:
         // Destroy the previous session
         // Because of a fatal error : try to delete an uninitialized session
         if ((isset($_SESSION)) && (!empty($_SESSION)))
         {
             session_destroy();
         }
         break;
 }

 // Create "supporter" session or use the opened "supporter" session
 if (!isSet($_SESSION))
 {
     session_start();
 }

 // No news to display : the supporter isn't logged
 $Msgs = '';
 $Focus = "document.forms[0].sLogin.focus()";
 $RedirectionUrl = '';

 //################################ FORM PROCESSING ##########################
 if ((!empty($_POST["hidEncLogin"])) || (!empty($_SESSION["OpenIdResponse"])))
 {
     // Connection to the database
     $DbCon = dbConnection();

     if (isset($_SESSION["PreviousUrl"]))
     {
         // Redirection because the user want to display another page
         $RedirectionUrl = $_SESSION["PreviousUrl"];

         unset($_SESSION["PreviousUrl"]);
     }

     $OpenIDResponse = NULL;
     if (isSet($_SESSION["OpenIdResponse"]))
     {
         require 'OpenIdCommon.php';
         $OpenIDResponse = unserialize($_SESSION["OpenIdResponse"]);
     }

     switch($CONF_SESSIONS_TYPE)
     {
         case SESSION_TYPE_DB:
         case SESSION_TYPE_FILE:
             // Create "supporter" session or use the opened "supporter" session
             if (!isset($_SESSION))
             {
                 // Because of a fatal error : Failed to initialize storage module: user
                 session_start();
             }
             break;

         default:
             // Destroy the previous session
             session_destroy();

             // Create "supporter" session or use the opened "supporter" session
             session_start();
             break;
     }

     // The support member is in the database and owns an activated profil?
     $bIsMD5 = TRUE;
     if (is_Null($OpenIDResponse))
     {
         // Auth thanks login/password in the Astres database
         $sLogin = strip_tags($_POST["hidEncLogin"]);
         $sPassword = strip_tags($_POST["hidEncPassword"]);

         // We check if the login and the password strings are MD5 strings
         if ((!isMD5($sLogin)) || (!isMD5($sPassword)))
         {
             // Not MD5 string
             $bIsMD5 = FALSE;
         }
         else
         {
             // Both are MD5 strings
             $DbResult = $DbCon->query("SELECT sm.SupportMemberID, sm.SupportMemberLastname, sm.SupportMemberFirstname,
                                       sm.SupportMemberPhone, sm.SupportMemberEmail, sm.SupportMemberStateID, sm.FamilyID,
                                       sms.SupportMemberStateName FROM SupportMembers sm, SupportMembersStates sms
                                       WHERE sm.SupportMemberLogin = '$sLogin' AND sm.SupportMemberPassword = '$sPassword'
                                       AND sm.SupportMemberActivated = 1 AND sm.SupportMemberStateID = sms.SupportMemberStateID");
         }
     }
     else
     {
         // Auth thanks to OpenID
         if ($OpenIDResponse->status == Auth_OpenID_SUCCESS)
         {
             // Good OpenID
             $sOpenIdUrl = $OpenIDResponse->getDisplayIdentifier();
         }
         else
         {
             // Error or wrong OpenID
             $sOpenIdUrl = 'xxxx';
         }

         $DbResult = $DbCon->query("SELECT sm.SupportMemberID, sm.SupportMemberLastname, sm.SupportMemberFirstname, sm.FamilyID,
                                   sm.SupportMemberPhone, sm.SupportMemberEmail, sm.SupportMemberStateID, sms.SupportMemberStateName
                                   FROM SupportMembers sm, SupportMembersStates sms WHERE sm.SupportMemberOpenIdUrl = '$sOpenIdUrl'
                                   AND sm.SupportMemberActivated = 1 AND sm.SupportMemberStateID = sms.SupportMemberStateID");
     }

     if (($bIsMD5) && (!DB::isError($DbResult)))
     {
         if ($DbResult->numRows() == 1)
         {
             $Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC);

             // He is allowed
             $LoginSentence = $Record["SupportMemberLastname"]." ".$Record["SupportMemberFirstname"]." (".$Record["SupportMemberStateName"].")";
             $ConfirmationStyle = "ConfirmationMsg";

             // Create session variables
             $_SESSION["SupportMemberID"] = $Record["SupportMemberID"];
             $_SESSION["SupportMemberLastname"] = $Record["SupportMemberLastname"];
             $_SESSION["SupportMemberFirstname"] = $Record["SupportMemberFirstname"];
             $_SESSION["SupportMemberPhone"] = $Record["SupportMemberPhone"];
             $_SESSION["SupportMemberEmail"] = $Record["SupportMemberEmail"];
             $_SESSION["SupportMemberStateID"] = $Record["SupportMemberStateID"];
             $_SESSION["SupportMemberStateName"] = $Record["SupportMemberStateName"];

             if (is_null($Record["FamilyID"]))
             {
                 // No family associated to this account
                 $_SESSION["FamilyID"] = -1;
             }
             else
             {
                 $_SESSION["FamilyID"] = $Record["FamilyID"];
             }

             if ($CONF_SESSIONS_TYPE == SESSION_TYPE_DB)
             {
                 // To force to store in database the session
                 session_write_close();
             }

             // Log the event
             logEvent($DbCon, EVT_SYSTEM, EVT_SERV_LOGIN, EVT_ACT_LOGIN, $_SESSION["SupportMemberID"]);

             // Release the connection to the database
             dbDisconnection($DbCon);

             // Redirection
             if ($RedirectionUrl == '')
             {
                 if ((isset($CONF_SUPPORT_URL_TO_DISPLAY_AFTER_LOGIN[$Record["SupportMemberStateID"]]))
                     && ($CONF_SUPPORT_URL_TO_DISPLAY_AFTER_LOGIN[$Record["SupportMemberStateID"]] != ''))
                 {
                     // We use the url of the support member state for the redirection
                     $RedirectionUrl = $CONF_SUPPORT_URL_TO_DISPLAY_AFTER_LOGIN[$Record["SupportMemberStateID"]];
                 }
                 else
                 {
                     // No url of redirection defined for this support member state : we use the default url
                     if ($CONF_SUPPORT_URL_TO_DISPLAY_AFTER_LOGIN['default'] == '')
                     {
                         // No default url, we use the default url
                         $RedirectionUrl = 'AowFollow/SubmittedAow.php';
                     }
                     else
                     {
                         $RedirectionUrl = $CONF_SUPPORT_URL_TO_DISPLAY_AFTER_LOGIN['default'];
                     }
                 }
             }

             header("location: $RedirectionUrl");
         }
         else
         {
             // The support member isn't in the database
             $LoginSentence = $LANG_ERROR_SUPPORT_MEMBER_LOGIN;
             $ConfirmationStyle = "ErrorMsg";

             // Log the event
             logEvent($DbCon, EVT_SYSTEM, EVT_SERV_LOGIN, EVT_ACT_LOGIN_FAILED);
         }
     }
     else
     {
         // Login or password entered aren't MD5 strings
         $LoginSentence = $LANG_ERROR_SUPPORT_MEMBER_LOGIN;
         $ConfirmationStyle = "ErrorMsg";

         // Log the event
         logEvent($DbCon, EVT_SYSTEM, EVT_SERV_LOGIN, EVT_ACT_LOGIN_FAILED);
     }

     // Release the connection to the database
     dbDisconnection($DbCon);
 }
 else
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // No focus on the login form
         $Focus = "";

         $LoginSentence = $_SESSION["SupportMemberLastname"]." ".$_SESSION["SupportMemberFirstname"]
                          ." (".$_SESSION["SupportMemberStateName"].")";
         $ConfirmationStyle = "ConfirmationMsg";

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         if (strpos($_SERVER["QUERY_STRING"], "openid.identity=&openid.mode=checkid_setup") === FALSE)
         {
             // No auth OpenID try failed
             $LoginSentence = "";
             $ConfirmationStyle = "ConfirmationMsg";
         }
         else
         {
             // Auth OpenID try but failed
             $LoginSentence = $LANG_ERROR_SUPPORT_MEMBER_LOGIN;
             $ConfirmationStyle = "ErrorMsg";
         }
     }
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            'Styles_Support.css' => 'screen'),
                      array('Verifications.js', '../Common/JSMD5/MD5.js', '../Common/JSRotateMsgs/Rotation.js'),
                      '',
                      "initMsgs('$Msgs');$Focus;"
                     );
 openWebPage();

 if (!isSet($_SESSION['SupportMemberID']))
 {
     // Display invisible link to go directly to content
     displayStyledLinkText($LANG_GO_TO_CONTENT, '#LoginForm', 'Accessibility');
 }

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu();

 // Content of the web page
 openArea('id="content"');

 // Display the "Canteen" and the "parameters" contextual menus if the supporter isn't logged, no contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu('canteen', 0, 0);
     displaySupportMemberContextualMenu('parameters', 0, 0);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
 displayTitlePage($LANG_SUPPORT_INDEX_PAGE_TITLE, 2);

 // Display messages
 if ($Msgs != '')
 {
     displayCommunicationArea();
 }

 openParagraph();
 displayStyledText($LANG_SUPPORT_INDEX_PAGE_PARAGRAPH_ONE, '');
 closeParagraph();

 displaySeparator($LANG_LOGIN);

 //################################ ACCESS CONTROL ##########################
 // Detect if the supporter is logged
 if ($LoginSentence != '')
 {
     openParagraph('InfoMsg');
     displayStyledText($LoginSentence, $ConfirmationStyle);
     closeParagraph();
 }

 if (!isSet($_SESSION['SupportMemberID']))
 {
     // The supporter isn't logged
     displayPwdLoginForm();

     if ($CONF_OPENID_USED)
     {
         // Allow OpenID auth
         displayOpenIDLoginForm();
     }
 }
 //################################ END ACCESS CONTROL ##########################

 openParagraph('intranet');
 displayStyledPicture('../GUI/Styles/internet.gif', '', '');
 closeParagraph();

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