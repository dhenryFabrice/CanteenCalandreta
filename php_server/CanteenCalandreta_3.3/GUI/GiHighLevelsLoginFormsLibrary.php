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
 * Interface module : XHTML Graphic high level login/logout forms library
 *
 * @author Christophe Javouhey
 * @version 2.3
 * @since 2012-01-12
 */


/**
 * Display the login form with a login and a password in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2014-02-25 : display an achor to go directly to content
 *
 * @since 2012-01-12
 */
 function displayPwdLoginForm()
 {
     // Display the table (frame) where the form will take place
     openFrame($GLOBALS['LANG_LOGIN_FRAME_TITLE']);

     // Open the temporary form
     openForm('FormTmp', 'post', '');

     // Display the fields
     echo "<table class=\"Form\">\n<tr>\n\t<td id=\"LoginForm\">".$GLOBALS['LANG_LOGIN_NAME'].' : ';
     insertInputField('sLogin', 'text', '25', '15', $GLOBALS['LANG_LOGIN_NAME_TIP'], '', FALSE, FALSE, "onkeypress=\"LoginEnterKey(event, '".$GLOBALS['LANG_ERROR_JS_LOGIN_NAME']."', '".$GLOBALS['LANG_ERROR_JS_PASSWORD']."')\"");
     echo "</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS['LANG_PASSWORD'].' : ';
     insertInputField('sPassword', 'password', '25', '15', $GLOBALS['LANG_PASSWORD_TIP'], '', FALSE, FALSE, "onkeypress=\"LoginEnterKey(event, '".$GLOBALS['LANG_ERROR_JS_LOGIN_NAME']."', '".$GLOBALS['LANG_ERROR_JS_PASSWORD']."')\"");
     echo "</td>\n</tr>\n</table>\n";
     closeForm();
     displayBR(1);

     // Open the temporary form
     openForm('FormLogin', 'post', 'index.php', '', "VerificationIndexPage('".$GLOBALS['LANG_ERROR_JS_LOGIN_NAME']."', '".$GLOBALS['LANG_ERROR_JS_PASSWORD']."')");

     // Display the hidden fields
     insertInputField('hidEncLogin', 'hidden', '', '', '', '');
     insertInputField('hidEncPassword', 'hidden', '', '', '', '');

     // Display the buttons
     echo "<table class=\"validation\">\n<tr>\n\t<td>";
     insertInputField('bSubmit', 'submit', '', '', $GLOBALS['LANG_SUBMIT_BUTTON_TIP'], $GLOBALS['LANG_SUBMIT_BUTTON_CAPTION']);
     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
     insertInputField('bReset', 'reset', '', '', $GLOBALS['LANG_RESET_BUTTON_TIP'], $GLOBALS['LANG_RESET_BUTTON_CAPTION']);
     echo "</td>\n</tr>\n</table>\n";
     closeForm();
     closeFrame();
 }


/**
 * Display the login form for OpenID in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-02-04
 */
 function displayOpenIDLoginForm()
 {
     // Display the table (frame) where the form will take place
     openFrame($GLOBALS['LANG_LOGIN_OPENID_FRAME_TITLE']);

     openForm('FormLoginOpenID', 'post', 'OpenIdTryAuth.php', '', "VerificationOpenIDIndexPage('".$GLOBALS['LANG_ERROR_LOGIN_OPENID']."')");

     // Display the fields
     echo "<table class=\"Form\">\n<tr>\n\t<td>".$GLOBALS['LANG_OPENID'].' : ';
     insertInputField('openid_identifier', 'text', '255', '60', $GLOBALS['LANG_OPENID_TIP'], '');
     echo "</td>\n</tr>\n</table>\n";
     displayBR(1);

     // Display the buttons
     echo "<table class=\"validation\">\n<tr>\n\t<td>";
     insertInputField('bSubmit', 'submit', '', '', $GLOBALS['LANG_SUBMIT_BUTTON_TIP'], $GLOBALS['LANG_SUBMIT_BUTTON_CAPTION']);
     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
     insertInputField('bReset', 'reset', '', '', $GLOBALS['LANG_RESET_BUTTON_TIP'], $GLOBALS['LANG_RESET_BUTTON_CAPTION']);
     echo "</td>\n</tr>\n</table>\n";
     closeForm();
     closeFrame();
 }


/**
 * Display the diconnection form of the logged customer in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-12
 */
 function displayLogout()
 {
     if (isSet($_SESSION['SupportMemberID']))
     {
         // It's a supporter session
         $UserInfos = $_SESSION['SupportMemberLastname'].' '.$_SESSION['SupportMemberFirstname'];
     }
     else
     {
         $UserInfos = '';
     }

     // Destroy the session
     $Result = session_destroy();

     if ($Result)
     {
         // The session is destroyed
         $ConfirmationCaption = $GLOBALS['LANG_CONFIRMATION'];

         if ($UserInfos == '')
         {
             $ConfirmationSentence = $GLOBALS['LANG_CONFIRM_DISCONNECTION_USER'].'.';
         }
         else
         {
             $ConfirmationSentence = $GLOBALS['LANG_CONFIRM_DISCONNECTION_USER'].", $UserInfos.";
         }
         $ConfirmationStyle = 'ConfirmationMsg';
     }
     else
     {
         // The session isn't destroyed
         $ConfirmationCaption = $GLOBALS['LANG_ERROR'];

         if ($UserInfos == '')
         {
             $ConfirmationSentence = $GLOBALS['LANG_ERROR_DISCONNECTION_USER']."!";
         }
         else
         {
             $ConfirmationSentence = $GLOBALS['LANG_ERROR_DISCONNECTION_USER'].", $UserInfos!";
         }
         $ConfirmationStyle = 'ErrorMsg';
     }

     openFrame($ConfirmationCaption);
     displayStyledText($ConfirmationSentence, $ConfirmationStyle);
     closeFrame();
 }
?>