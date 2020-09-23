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
 * Interface module : XHTML Graphic high level forms library used to display the profil of a supporter
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2012-01-13
 */


/**
 * Display the profil of a supporter in the current row of the table of the web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 2.0
 *     - 2014-02-25 : display an achor to go directly to content
 *     - 2014-08-05 : 30 characters for the phone field (instead of 20)
 *     - 2015-10-12 : display the associated family
 *     - 2016-10-21 : access right of fields
 *
 * @since 2012-01-13
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $SupportMemberID      Integer               ID or the supporter
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update support members
 */
 function displaySupportMemberProfil($DbConnection, $SupportMemberID, $AccessRules = array())
 {
     if ($SupportMemberID > 0)
     {
         // The supporter must be allowed to access to support members data
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

         // Get data about the support member
         $DbResultSupportMember = $DbConnection->query("SELECT sm.SupportMemberLastname, sm.SupportMemberFirstname,
                                                       sm.SupportMemberPhone, sm.SupportMemberEmail, sm.SupportMemberStateID,
                                                       sms.SupportMemberStateName, sm.FamilyID, f.FamilyLastname
                                                       FROM SupportMembersStates sms, SupportMembers sm LEFT JOIN Families f ON
                                                       (sm.FamilyID = f.FamilyID) WHERE sm.SupportMemberID = $SupportMemberID
                                                       AND sm.SupportMemberStateID = sms.SupportMemberStateID");
         if (!DB::isError($DbResultSupportMember))
         {
             if ($DbResultSupportMember->numRows() != 0)
             {
                 $RecordSupportMember = $DbResultSupportMember->fetchRow(DB_FETCHMODE_ASSOC);

                 // Display the table (frame) where the form will take place
                 openFrame($GLOBALS["LANG_PROFIL_PAGE_REQUEST_FORM"]);

                 // Open a form
                 openForm("FormUpdateProfil", "post", "ProcessUpdateProfil.php?".$GLOBALS['QUERY_STRING'], "", "VerificationUserProfil('".$GLOBALS["LANG_ERROR_JS_LASTNAME"]."', '".$GLOBALS["LANG_ERROR_JS_FIRSTNAME"]."', '".$GLOBALS["LANG_ERROR_JS_EMAIL"]."')");

                 // Display the sign in form
                 echo "<table id=\"ProfilDetails\" class=\"Form\">\n<tr>\n\t<td>".$GLOBALS["LANG_LASTNAME"]."* : </td><td>";
                 insertInputField("sLastname", "text", "50", "15", $GLOBALS["LANG_LASTNAME_TIP"], $RecordSupportMember["SupportMemberLastname"]);
                 echo "</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS["LANG_FIRSTNAME"]."* : </td><td>";
                 insertInputField("sFirstname", "text", "25", "15", $GLOBALS["LANG_FIRSTNAME_TIP"], $RecordSupportMember["SupportMemberFirstname"]);
                 echo "</td>\n</tr>\n<tr>\n\t<td>".$GLOBALS["LANG_USER_STATUS"]." : </td><td>";

                 // <<< SupportMemberStateName SELECTFIELD >>>
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         // List of support members states
                         $ArraySupportMembersStates = getTableContent($DbConnection, 'SupportMembersStates', 'SupportMemberStateID');
                         $ArrayStateID = array();
                         $ArrayStateName = array();

                         if (isset($ArraySupportMembersStates['SupportMemberStateID']))
                         {
                             $ArrayStateID = $ArraySupportMembersStates['SupportMemberStateID'];
                             $ArrayStateName = $ArraySupportMembersStates['SupportMemberStateName'];

                             echo generateSelectField("lSupportMemberStateID", $ArrayStateID, $ArrayStateName,
                                                      $RecordSupportMember['SupportMemberStateID']);
                         }
                         break;

                     case FCT_ACT_PARTIAL_READ_ONLY:
                     case FCT_ACT_READ_ONLY:
                     default:
                         echo $RecordSupportMember["SupportMemberStateName"];
                         break;
                 }

                 echo "</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS["LANG_PHONE_NUMBER"]." : </td><td>";
                 insertInputField("sPhoneNumber", "text", "30", "15", $GLOBALS["LANG_PHONE_NUMBER_TIP"], $RecordSupportMember["SupportMemberPhone"]);

                 $FamilyLastname = '-';
                 $FamilyID = 0;
                 if (!empty($RecordSupportMember["FamilyID"]))
                 {
                     $FamilyLastname = $RecordSupportMember["FamilyLastname"];
                     $FamilyID = $RecordSupportMember["FamilyID"];
                 }
                 echo "</td>\n\t\t</tr>\n\t\t<tr>\n\t\t\t<td>".$GLOBALS["LANG_FAMILY"]." : </td><td colspan=\"4\">";

                 // <<< FamilyLastname SELECTFIELD >>>
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $ArraySupportMembersFamilies = getTableContent($DbConnection, 'Families', 'FamilyLastname');
                         $ArrayFamilyID = array(0);
                         $ArrayFamilyLastname = array("-");

                         if (isset($ArraySupportMembersFamilies['FamilyID']))
                         {
                             $ArrayFamilyID = array_merge($ArrayFamilyID, $ArraySupportMembersFamilies['FamilyID']);
                             $ArrayFamilyLastname = array_merge($ArrayFamilyLastname, $ArraySupportMembersFamilies['FamilyLastname']);

                             echo generateSelectField("lFamilyID", $ArrayFamilyID, $ArrayFamilyLastname,
                                                      $RecordSupportMember['FamilyID']);
                         }
                         break;

                     case FCT_ACT_PARTIAL_READ_ONLY:
                     case FCT_ACT_READ_ONLY:
                     default:
                         echo $FamilyLastname;
                         break;
                 }

                 echo "</td>\n\t\t</tr>\n\t\t<tr>\n\t\t\t<td>".$GLOBALS["LANG_E_MAIL"]."* : </td><td colspan=\"4\">";
                 insertInputField("sEmail", "text", "100", "51", $GLOBALS["LANG_E_MAIL_TIP"], $RecordSupportMember["SupportMemberEmail"]);
                 echo "</td>\n</tr>\n</table>";
                 displayBR(1);

                 // Display the hidden fields
                 insertInputField("hidUserStateID", "hidden", "", "", "", $RecordSupportMember["SupportMemberStateID"]);
                 insertInputField("hidFamilyID", "hidden", "", "", "", $FamilyID);

                 // Display the buttons
                 echo "<table class=\"validation\">\n<tr>\n\t<td>";
                 insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                 echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                 insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                 echo "</td>\n</tr>\n</table>\n";

                 closeForm();
                 closeFrame();
             }
         }
     }
 }


/**
 * Display the form to allow a supporter to update his login and his password in the current row of
 * the table of the web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-12-02 : add the SupportMemberID in a hidden input field to allow admin to
 *                    update a login/password of another support member
 *
 * @since 2012-01-13
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $SupportMemberID      Integer               ID or the supporter
 */
 function displaySupportMemberLoginPwd($DbConnection, $SupportMemberID)
 {
     if ($SupportMemberID > 0)
     {
         // Display the table (frame) where the form will take place
         openFrame($GLOBALS["LANG_UPDATE_LOGIN_PWD_FRAME_TITLE"]);

         // Open the temporary form
         openForm("FormLoginPwdTmp", "post", "");

         // Display the fields
         echo "<table class=\"Form\">\n<tr>\n\t<td>".$GLOBALS["LANG_LOGIN_NAME"]." : ";
         insertInputField("sLogin", "text", "25", "15", $GLOBALS["LANG_LOGIN_NAME_TIP"], "");
         echo "</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS["LANG_PASSWORD"]." : ";
         insertInputField("sPassword", "password", "25", "15", $GLOBALS["LANG_PASSWORD_TIP"], "");
         echo "<tr>\n\t<td>&nbsp;</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS["LANG_PASSWORD_CONFIRMATION"]." : ";
         insertInputField("sConfirmPassword", "password", "25", "15", $GLOBALS["LANG_PASSWORD_CONFIRMATION_TIP"], "");
         echo "</td>\n</tr>\n</table>\n";
         closeForm();
         displayBR(1);

         // Open the temporary form
         openForm("FormUpdateLoginPwd", "post", "ProcessUpdateLoginPwd.php", "", "VerificationUpdateLoginPwd('".$GLOBALS["LANG_ERROR_JS_LOGIN_NAME"]."', '".$GLOBALS["LANG_ERROR_JS_PASSWORD"]."', '".$GLOBALS["LANG_ERROR_JS_DIFF_PASSWORD"]."')");

         // Display the hidden fields
         insertInputField("hidEncLogin", "hidden", "", "", "", "");
         insertInputField("hidEncPassword", "hidden", "", "", "", "");
         insertInputField("hidEncConfirmPassword", "hidden", "", "", "", "");
         insertInputField("hidSupportMemberID", "hidden", "", "", "", "$SupportMemberID");

         // Display the buttons
         echo "<table class=\"validation\">\n<tr>\n\t<td>";
         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
         echo "</td>\n</tr>\n</table>\n";

         closeForm();
         closeFrame();
     }
 }


/**
 * Display the result of a prepared request in the current row of the table of the web page, in the
 * graphic interface in XHTML
 *
 * @author STNA/7SQ
 * @version 2.2
 *     - 2006-03-07 : define undeclared variables
 *     - 2007-01-16 : new interface
 *     - 2010-11-04 : display the column sorted
 *     - 2014-02-25 : display an achor to go directly to content
 *
 * @since 2004-08-13
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form allowing to sort the table of asks of work
 * @param $SortFct              String                Javascript function used to sort the table
 * @param $OrderBy              Integer               n° Criteria used to sort the asks of work. If < 0, DESC is used, otherwise ASC is used
 * @param $Page                 Integer               Number of the Page to display [1..n]
 * @param $NbRecords            Integer               Number of records found [0..n]
 * @param $TabViewFieldnames    Array of Strings      Fieldnames to display
 * @param $TabData              Mixed array           Contains the data to display
 */
 function displayPreparedRequestForm($DbConnection, $ProcessFormPage, $SortFct = '', $OrderBy = 0, $Page = 1, $NbRecords = 0, $TabViewFieldnames = array("AowID"), $TabData = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Open a form
         openForm("FormPreparedRequest", "post", "$ProcessFormPage", "", "");

         // Display the table (frame) where the form will take place
         openStyledFrame($GLOBALS["LANG_PREPARED_REQUESTS"], "YourParametersFrame", "YourParametersFrame", "YourParametersFrame");

         $PreparedRequestsKeys = array_keys($GLOBALS["PREPARED_REQUESTS_PARAMETERS"][$_SESSION["SupportMemberID"]]);
         $PreparedRequestsList = generateSelectField("lPreparedRequest", array_keys($PreparedRequestsKeys), $PreparedRequestsKeys,
                                                     zeroFormatValue(existedPOSTFieldValue("hidPreparedRequestID", existedGETFieldValue("hidPreparedRequestID", 0))),
                                                     "onChangePreparedRequest(this.value)");
         $PreparedRequestsList .= generateInputField("hidPreparedRequestID", "hidden", "", "", "",
                                                     zeroFormatValue(existedPOSTFieldValue("hidPreparedRequestID", existedGETFieldValue("hidPreparedRequestID", 0))));

         // Display the form
         echo "<table id=\"PreparedRequestsList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"YourParametersLabel\">"
              .$GLOBALS["LANG_PREPARED_REQUESTS_LIST"]."</td><td class=\"Value\">$PreparedRequestsList</td>\n</tr>\n";
         echo "</table>\n";

         // Display the hidden fields
         insertInputField("hidOrderByField", "hidden", "", "", "", $OrderBy);
         insertInputField("hidOnPrint", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnPrint", existedGETFieldValue("hidOnPrint", 0))));
         insertInputField("hidOnExport", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnExport", existedGETFieldValue("hidOnExport", 0))));
         insertInputField("hidExportFilename", "hidden", "", "", "", existedPOSTFieldValue("hidExportFilename", existedGETFieldValue("hidExportFilename", 0)));
         closeStyledFrame();

         echo "<table class=\"validation\">\n<tr>\n\t<td>";
         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
         echo "</td>\n</tr>\n</table>\n";

         closeForm();

         // Display the result
         if ($NbRecords > 0)
         {
             $ArrayAllFieldnames = $GLOBALS["PREPARED_REQUESTS_ALL_FIELDNAMES"];
             $ArrayAllOrderByFieldnames = $GLOBALS["PREPARED_REQUESTS_ALL_ORDER_BY_FIELDNAMES"];
             $ArrayAllCaptions = $GLOBALS["PREPARED_REQUESTS_ALL_CAPTIONS"];
             $ArrayAllCaptionsSize = count($ArrayAllCaptions);

             // We build the captions and the array used to sort
             $ArrayCaptions = array();
             $ArraySorts = array();
             foreach($TabViewFieldnames as $CurrentValue)
             {
                 // We search the position of the each fieldname in the array containing all possible fieldnames
                 $Pos = array_search($CurrentValue, $ArrayAllFieldnames);
                 if ($Pos === FALSE)
                 {
                     // Fieldname not found
                     $ArrayCaptions[] = nullFormatText("$CurrentValue");
                     $ArraySorts[] = "";
                 }
                 else
                 {
                     // Fieldname found
                     $ArrayCaptions[] = $ArrayAllCaptions[$Pos];
                     $Pos++;     // To have a value in [1..n]
                     $ArraySorts[] = "$Pos";
                 }
             }

             // We put ia an array the data
             $TabDataKey = array_keys($TabData);
             foreach($TabData[$TabDataKey[0]] as $i => $CurrentValue)
             {
                 foreach($TabDataKey as $j => $CurrentKey)
                 {
                     // This field "must be displayed?
                     $Pos = array_search($CurrentKey, $TabViewFieldnames);
                     if ($Pos !== FALSE)
                     {
                         $ArrayData[$Pos][] = $TabData[$CurrentKey][$i];
                     }
                 }
             }

             // Display the table which contains the data of the logged supporter
             displayStyledTable($ArrayCaptions, $ArraySorts, $SortFct, $ArrayData, '', "DisplayYourParametersCaptions", '', '',
                                array(), $OrderBy);

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
                     $PreviousLink = "$ProcessFormPage?";
                     foreach($_GET as $i => $CurrentValue)
                     {
                         if ($i == "Pg")
                         {
                             $CurrentValue = $NoPage;
                         }
                         $PreviousLink .= "&amp;$i=$CurrentValue";
                     }
                 }
                 else
                 {
                     // POST form
                     $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                     foreach($_POST as $i => $CurrentValue)
                     {
                         $PreviousLink .= "&amp;$i=$CurrentValue";
                     }
                 }
             }

             if ($Page < ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]))
             {
                 $NoPage = $Page + 1;

                 // We get the parameters of the GET form or the POST form
                 if (count($_POST) == 0)
                 {
                     // GET form
                     $NextLink = "$ProcessFormPage?";
                     foreach($_GET as $i => $CurrentValue)
                     {
                         if ($i == "Pg")
                         {
                             $CurrentValue = $NoPage;
                         }
                         $NextLink .= "&amp;$i=$CurrentValue";
                     }
                 }
                 else
                 {
                     // POST form
                     $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                     foreach($_POST as $i => $CurrentValue)
                     {
                         $NextLink .= "&amp;$i=$CurrentValue";
                     }
                 }
             }
             else
             {
                 $NextLink = "";
             }
             displayPreviousNext("&nbsp;".$GLOBALS["LANG_PREVIOUS"], $PreviousLink, $GLOBALS["LANG_NEXT"]."&nbsp;", $NextLink,
                                 '', $Page, ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]));

             // Display the number of records found
             openParagraph('nbentriesfound');
             echo $GLOBALS["LANG_NB_RECORDS_FOUND"].$NbRecords;
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
 * Display the form to submit a new support member profil, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2015-10-09 : taken into account the FamilyID field of SupportMembers table
 *
 * @since 2014-08-07
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create support member profils
 */
 function displayCreateSupportMemberProfilForm($DbConnection, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a family
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE)))
         {
             // Display the table (frame) where the form will take place
             openFrame($GLOBALS["LANG_CREATE_PROFIL_PAGE_REQUEST_FORM"]);

             // Open a form
             openForm("FormCreateProfil", "post", $ProcessFormPage, "", "VerificationNewUserProfil('".$GLOBALS["LANG_ERROR_JS_LASTNAME"]."', '".$GLOBALS["LANG_ERROR_JS_FIRSTNAME"]."', '".$GLOBALS["LANG_ERROR_JS_STATE"]."', '".$GLOBALS["LANG_ERROR_JS_EMAIL"]."', '".$GLOBALS["LANG_ERROR_JS_LOGIN_NAME"]."', '".$GLOBALS["LANG_ERROR_JS_PASSWORD"]."', '".$GLOBALS["LANG_ERROR_JS_DIFF_PASSWORD"]."')");

             // Display the sign in form
             echo "<table id=\"NewProfil\" class=\"Form\">\n<tr>\n\t<td>".$GLOBALS["LANG_LASTNAME"]."* : </td><td>";
             insertInputField("sLastname", "text", "50", "15", $GLOBALS["LANG_LASTNAME_TIP"], '');
             echo "</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS["LANG_FIRSTNAME"]."* : </td><td>";
             insertInputField("sFirstname", "text", "25", "15", $GLOBALS["LANG_FIRSTNAME_TIP"], '');
             echo "</td>\n</tr>\n<tr>\n\t<td>".$GLOBALS["LANG_USER_STATUS"]."* : </td><td>";

             // List of support members states
             $ArraySupportMembersStates = getTableContent($DbConnection, 'SupportMembersStates', 'SupportMemberStateID');
             $ArrayStateID = array(0);
             $ArrayStateName = array('-');

             if (isset($ArraySupportMembersStates['SupportMemberStateID']))
             {
                 $ArrayStateID = array_merge($ArrayStateID, $ArraySupportMembersStates['SupportMemberStateID']);
                 $ArrayStateName = array_merge($ArrayStateName, $ArraySupportMembersStates['SupportMemberStateName']);

                 echo generateSelectField("lSupportMemberStateID", $ArrayStateID, $ArrayStateName, 0);
             }

             echo "</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS["LANG_PHONE_NUMBER"]." : </td><td>";
             insertInputField("sPhoneNumber", "text", "30", "15", $GLOBALS["LANG_PHONE_NUMBER_TIP"], '');

             // List of the activated families
             $ArrayFamilies = dbSearchFamily($DbConnection, array('SchoolYear' => array(getSchoolYear(date('Y-m-d'))),
                                                                  'ActivatedChildren' => TRUE),
                                             "FamilyLastname", 1, 0);

             echo "</td>\n\t\t</tr>\n\t\t<tr>\n\t\t\t<td>".$GLOBALS["LANG_FAMILY"]." : </td><td colspan=\"4\">";

             $ArrayFamilyID = array(0);
             $ArrayFamilyLastname = array('-');

             if (isset($ArrayFamilies['FamilyID']))
             {
                 $ArrayFamilyID = array_merge($ArrayFamilyID, $ArrayFamilies['FamilyID']);
                 $ArrayFamilyLastname = array_merge($ArrayFamilyLastname, $ArrayFamilies['FamilyLastname']);

                 echo generateSelectField("lFamilyID", $ArrayFamilyID, $ArrayFamilyLastname, 0);
             }

             echo "</td>\n\t\t</tr>\n\t\t<tr>\n\t\t\t<td>".$GLOBALS["LANG_E_MAIL"]."* : </td><td colspan=\"4\">";
             insertInputField("sEmail", "text", "100", "65", $GLOBALS["LANG_E_MAIL_TIP"], '');
             echo "</td>\n</tr>\n";

             echo "<tr>\n\t<td>".$GLOBALS["LANG_LOGIN_NAME"]."* : </td><td>";
             insertInputField("sLogin", "text", "32", "15", $GLOBALS["LANG_LOGIN_NAME_TIP"], "");
             echo "</td><td class=\"AowFormSpace\"></td><td>".$GLOBALS["LANG_PASSWORD"]."* : </td><td>";
             insertInputField("sPassword", "password", "32", "15", $GLOBALS["LANG_PASSWORD_TIP"], "");
             echo "</td><tr>\n\t<td>&nbsp;</td><td>&nbsp;</td><td class=\"AowFormSpace\"></td><td>"
                  .$GLOBALS["LANG_PASSWORD_CONFIRMATION"]."* : </td><td>";
             insertInputField("sConfirmPassword", "password", "32", "15", $GLOBALS["LANG_PASSWORD_CONFIRMATION_TIP"], "");
             echo "</td>\n\t\t</tr>\n\t\t<tr>\n\t\t\t<td>".$GLOBALS["LANG_WEB_SERVICE_KEY"]." : </td><td colspan=\"4\">";
             insertInputField("sWebServiceKey", "text", "100", "65", $GLOBALS["LANG_WEB_SERVICE_KEY_TIP"], '');
             echo "</td>\n\t\t</tr>\n\t\t<tr>\n\t\t\t<td>".$GLOBALS["LANG_CREATE_PROFIL_PAGE_SEND_MAIL"]." : </td><td colspan=\"4\">";
             insertInputField("chkSendMail", "checkbox", "", "", $GLOBALS["LANG_CREATE_PROFIL_PAGE_SEND_MAIL_TIP"], "sendmail",
                              FALSE, TRUE);
             echo "</td>\n</tr>\n</table>\n";

             insertInputField("hidLogin", "hidden", "", "", "", "");
             insertInputField("hidPassword", "hidden", "", "", "", "");

             displayBR(1);

             // Display the buttons
             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();
             closeFrame();
         }
         else
         {
             // The supporter isn't allowed to create a support member profil
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
 * Display the form to search a support member (profil) in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-10-03 : display warning for not desactivated families but without activated children
 *                    for the current school year
 *
 * @since 2016-03-01
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some alias
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the support members found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the support members. If < 0, DESC is used,
 *                                                          otherwise ASC is used
 * @param $DetailsPage                 String               URL of the page to display details about a suppotr member. This string
 *                                                          can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update support members
 */
 function displaySearchSupportMembersForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to support members list
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
             openForm("FormSearchSuppportMembers", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             // Lastname input text
             $sSupportMemberLastname = generateInputField("sSupportMemberLastname", "text", "50", "15", $GLOBALS["LANG_LASTNAME_TIP"],
                                                          stripslashes(strip_tags(existedPOSTFieldValue("sSupportMemberLastname",
                                                                                                        stripslashes(existedGETFieldValue("sSupportMemberLastname", ""))))));

             $sSupportMemberEmail = generateInputField("sSupportMemberEmail", "text", "50", "15", $GLOBALS["LANG_E_MAIL_TIP"],
                                                       stripslashes(strip_tags(existedPOSTFieldValue("sSupportMemberEmail",
                                                                                                     stripslashes(existedGETFieldValue("sSupportMemberEmail", ""))))));

             // List of support members states
             $ArraySupportMembersStates = getTableContent($DbConnection, 'SupportMembersStates', 'SupportMemberStateID');
             $ArrayStateID = array(0);
             $ArrayStateName = array('-');

             if (isset($ArraySupportMembersStates['SupportMemberStateID']))
             {
                 $ArrayStateID = array_merge($ArrayStateID, $ArraySupportMembersStates['SupportMemberStateID']);
                 $ArrayStateName = array_merge($ArrayStateName, $ArraySupportMembersStates['SupportMemberStateName']);
             }

             if ((isset($TabParams['SupportMemberStateID'])) && (count($TabParams['SupportMemberStateID']) > 0))
             {
                 $SelectedItem = $TabParams['SupportMemberStateID'][0];
             }
             else
             {
                 $SelectedItem = 0;
             }

             $sSupportMemberStateID = generateSelectField("lSupportMemberStateID", $ArrayStateID, $ArrayStateName,
                                                          zeroFormatValue(existedPOSTFieldValue("lSupportMemberStateID",
                                                                                                existedGETFieldValue("lSupportMemberStateID",
                                                                                                $SelectedItem))));

             // Support member activated
             $Checked = FALSE;
             if ((existedPOSTFieldValue("chkActivated", existedGETFieldValue("chkActivated", "")) == "activated")
                 || ((isset($TabParams['SupportMemberActivated'])) && (count($TabParams['SupportMemberActivated']) == 1)
                      && (in_array(1, $TabParams['SupportMemberActivated']))))
             {
                 $Checked = TRUE;
             }
             $sSupportMemberActivated = generateInputField("chkActivated", "checkbox", "", "", $GLOBALS["LANG_ACTIVATED"].".",
                                                           "activated", FALSE, $Checked)." ".$GLOBALS["LANG_YES"];

             // Display the form
             echo "<table id=\"SupportMembersList\" cellspacing=\"0\" cellpadding=\"0\">\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_LASTNAME"]."</td><td class=\"Value\">$sSupportMemberLastname</td><td class=\"Label\">".$GLOBALS['LANG_E_MAIL']."</td><td class=\"Value\">$sSupportMemberEmail</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_USER_STATUS"]."</td><td class=\"Value\">$sSupportMemberStateID</td><td class=\"Label\">".$GLOBALS['LANG_ACTIVATED']."</td><td class=\"Value\">$sSupportMemberActivated</td>\n</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_REFERENCE"], $GLOBALS["LANG_LASTNAME"], $GLOBALS["LANG_PHONE_NUMBER"],
                                        $GLOBALS["LANG_E_MAIL"], $GLOBALS['LANG_USER_STATUS']);
                 $ArraySorts = array("SupportMemberID", "SupportMemberLastname", "SupportMemberPhone", "SupportMemberEmail",
                                     "SupportMemberStateID");

                 if ($bCanDelete)
                 {
                     // The supporter can delete support members : we add a column for this action
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
                     $StrOrderBy = "SupportMemberLastname ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchSupportMember($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only support members of the page
                     $ArrayRecords = dbSearchSupportMember($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some support members found
                     $CurrentDateStamp = strtotime(date('Y-m-d'));
                     foreach($ArrayRecords["SupportMemberID"] as $i => $CurrentValue)
                     {
                         // Check if the support member is activated
                         if ($ArrayRecords["SupportMemberActivated"][$i] == 1)
                         {
                             if (empty($DetailsPage))
                             {
                                 // We display the ID
                                 $ArrayData[0][] = $ArrayRecords["SupportMemberID"][$i];
                             }
                             else
                             {
                                 // We display the ID with a hyperlink
                                 $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["SupportMemberID"][$i],
                                                                          $ArrayRecords["SupportMemberID"][$i],
                                                                          $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                          "", "_blank");
                             }

                             // Check the last connection date if set
                             $SupportMemberName = $ArrayRecords["SupportMemberLastname"][$i].' '.$ArrayRecords["SupportMemberFirstname"][$i];
                             if ((isset($ArrayRecords["SupportMemberLastConnection"]))
                                 && (!empty($ArrayRecords["SupportMemberLastConnection"][$i])))
                             {
                                 $iNbDays = getNbDaysBetween2Dates($CurrentDateStamp,
                                                                   strtotime($ArrayRecords["SupportMemberLastConnection"][$i]),
                                                                   FALSE);

                                 // To display the last connection date
                                 $sLastConnectionDate = $GLOBALS['LANG_LAST_CONNECTION']." : "
                                                        .date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                              strtotime($ArrayRecords["SupportMemberLastConnection"][$i]));

                                 $SupportMemberName = generateStyledText($SupportMemberName, '', $sLastConnectionDate);

                                 if (abs($iNbDays) > 400)
                                 {
                                     // The support member can be desactivated. No connection since 400 days !
                                     $SupportMemberName .= " ".generateStyledPicture($GLOBALS['CONF_WARNING_ICON'], $sLastConnectionDate." !!");
                                 }
                             }

                             $ArrayData[1][] = $SupportMemberName;
                             $ArrayData[2][] = $ArrayRecords["SupportMemberPhone"][$i];
                             $ArrayData[3][] = $ArrayRecords["SupportMemberEmail"][$i];

                             $CurrentSupportMemberStateName = '';
                             if (empty($ArrayRecords["FamilyID"][$i]))
                             {
                                 // We display the status name
                                 $CurrentSupportMemberStateName = $ArrayRecords["SupportMemberStateName"][$i];
                             }
                             else
                             {
                                 // Get activated children of the family
                                 $iNbActivatedChildren = getNbdbSearchChild($DbConnection,
                                                                            array('FamilyID' => $ArrayRecords["FamilyID"][$i],
                                                                                  'Activated' => TRUE), '', 1, 0);

                                 // We display the family lastname associated to the support member
                                 $FamilyStyle = '';
                                 $sWarning = '';
                                 if (!empty($ArrayRecords["FamilyDesactivationDate"][$i]))
                                 {
                                     // Not activated family
                                     $FamilyStyle = 'Desactivated';

                                 }
                                 elseif ($iNbActivatedChildren == 0)
                                 {
                                     // Family not descativated but should !
                                     $sWarning = " ".generateStyledPicture($GLOBALS['CONF_WARNING_ICON'],
                                                                           $GLOBALS['LANG_FAMILY_DESACTIVATED']);
                                 }

                                 $CurrentSupportMemberStateName = generateCryptedHyperlink($ArrayRecords["FamilyLastname"][$i],
                                                                                           $ArrayRecords["FamilyID"][$i],
                                                                                           '../Canteen/UpdateFamily.php',
                                                                                           $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                           $FamilyStyle, '_blank').$sWarning;
                             }

                             $ArrayData[4][] = $CurrentSupportMemberStateName;

                             // Hyperlink to desactivate the support member if allowed
                             if ($bCanDelete)
                             {
                                 $ArrayData[5][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                  "DesactivateSupportMember.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                                  $GLOBALS["LANG_DESACTIVATION"].'.', 'Affectation');
                             }
                         }
                         else
                         {
                             // Support member desactivated
                             if (empty($DetailsPage))
                             {
                                 // We display the ID
                                 $ArrayData[0][] = $ArrayRecords["SupportMemberID"][$i];
                             }
                             else
                             {
                                 // We display the ID with a hyperlink
                                 $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["SupportMemberID"][$i],
                                                                          $ArrayRecords["SupportMemberID"][$i],
                                                                          $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                          "Desactivated", "_blank");
                             }

                             $ArrayData[1][] = generateStyledText($ArrayRecords["SupportMemberLastname"][$i].' '
                                                                  .$ArrayRecords["SupportMemberFirstname"][$i], 'Desactivated');
                             $ArrayData[2][] = generateStyledText($ArrayRecords["SupportMemberPhone"][$i], 'Desactivated');
                             $ArrayData[3][] = generateStyledText($ArrayRecords["SupportMemberEmail"][$i], 'Desactivated');

                             $CurrentSupportMemberStateName = '';
                             if (empty($ArrayRecords["FamilyID"][$i]))
                             {
                                 // We display the status name
                                 $CurrentSupportMemberStateName = generateStyledText($ArrayRecords["SupportMemberStateName"][$i],
                                                                                     'Desactivated');
                             }
                             else
                             {
                                 // We display the family lastname associated to the support member
                                 $CurrentSupportMemberStateName = generateCryptedHyperlink($ArrayRecords["FamilyLastname"][$i],
                                                                                           $ArrayRecords["FamilyID"][$i],
                                                                                           '../Canteen/UpdateFamily.php',
                                                                                           $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                                           'Desactivated', '_blank');
                             }
                             $ArrayData[4][] = $CurrentSupportMemberStateName;

                             // Hyperlink to reactivate the support member if allowed
                             if ($bCanDelete)
                             {
                                 $ArrayData[5][] = generateStyledPictureHyperlink($GLOBALS["CONF_ACTIVATION_ICON"],
                                                                                  "ReactivateSupportMember.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                                  $GLOBALS["LANG_ACTIVATION"].'.', 'Affectation');
                             }
                         }
                     }

                     // Display the table which contains the support members found
                     $ArraySortedFields = array("1", "2", "3", "4", "5");
                     if ($bCanDelete)
                     {
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

                                 if (isset($TabParams['SupportMemberActivated']))
                                 {
                                     $CurrentValue = $TabParams['SupportMemberActivated'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $PreviousLink .= "&amp;chkActivated=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
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

                                 if (isset($TabParams['SupportMemberActivated']))
                                 {
                                     $CurrentValue = $TabParams['SupportMemberActivated'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $NextLink .= "&amp;chkActivated=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
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

                     $ArrayLegendsOfVisualIndicators = array(
                                                             array($GLOBALS['CONF_WARNING_ICON'], $GLOBALS["LANG_SUPPORT_ADMIN_SUPPORTMEMBERS_LIST_PAGE_WARNING_LEGEND"]),
                                                            );

                     echo generateLegendsOfVisualIndicators($ArrayLegendsOfVisualIndicators, ICON);
                 }
                 else
                 {
                     // No support members found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of support members
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
 * Display the form to submit a new support member state or update a support member state, in the current row
 * of the table of the web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $SupportMemberStateID     String                ID of the support member state to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view support member states
 */
 function displayDetailsSupportMemberStateForm($DbConnection, $SupportMemberStateID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         /// The supporter must be allowed to create or update a support member state
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($SupportMemberStateID))
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
             openForm("FormDetailsSupportMemberState", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationSupportMemberState('".$GLOBALS["LANG_ERROR_MANDORY_FIELDS"]."')");

             // <<< SupportMemberStateID >>>
             if ($SupportMemberStateID == 0)
             {
                 // Define default values to create the new support member state
                 $Reference = "";
                 $SupportMemberStateRecord = array(
                                                 "SupportMemberStateName" => '',
                                                 "SupportMemberStateDescription" => ''
                                                );
             }
             else
             {
                 if (isExistingSupportMemberState($DbConnection, $SupportMemberStateID))
                 {
                     // We get the details of the support member state
                     $SupportMemberStateRecord = getTableRecordInfos($DbConnection, "SupportMembersStates", $SupportMemberStateID);
                     $Reference = $SupportMemberStateID;
                 }
                 else
                 {
                     // Error, the support member state doesn't exist
                     $SupportMemberStateID = 0;
                     $Reference = "";
                 }
             }

             // Display the table (frame) where the form will take place
             $FrameTitle = $GLOBALS["LANG_USER_STATUS"];
             if (!empty($SupportMemberStateID))
             {
                 $FrameTitle .= " ($Reference)";
             }

             openStyledFrame($FrameTitle, "Frame", "Frame", "DetailsNews");

             // <<< SupportMemberStateName INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sStateName = stripslashes($SupportMemberStateRecord["SupportMemberStateName"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sStateName = generateInputField("sStateName", "text", "20", "20", "",
                                                      $SupportMemberStateRecord["SupportMemberStateName"]);
                     break;
             }

             // <<< SupportMemberStateDescription INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sDescription = nullFormatText(stripslashes($SupportMemberStateRecord["SupportMemberStateDescription"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sDescription = generateInputField("sDescription", "text", "255", "35", "",
                                                        $SupportMemberStateRecord["SupportMemberStateDescription"]);
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_USER_STATUS"]."*</td><td class=\"Value\">$sStateName</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_COMMENT']."</td><td class=\"Value\">$sDescription</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidSupportMemberStateID", "hidden", "", "", "", $SupportMemberStateID);
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
             // The supporter isn't allowed to create or update a support member state
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
 * Display the form to search a support member state in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some support members states
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the support members states found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the support members states. If < 0, DESC is used,
 *                                                          otherwise ASC is used
 * @param $DetailsPage                 String               URL of the page to display details about a support member state.
 *                                                          This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update support members states
 */
 function displaySearchSupportMembersStatesForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to support members states list
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
             openForm("FormSearchSupportMemberState", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");


             // Display the form
             echo "<table id=\"SupportMembersStatesList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_REFERENCE"], $GLOBALS["LANG_USER_STATUS"], $GLOBALS['LANG_COMMENT']);
                 $ArraySorts = array("SupportMemberStateID", "SupportMemberStateName", "SupportMemberStateDescription");

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
                     $StrOrderBy = "SupportMemberStateID";
                 }

                 // We launch the search


                 $ArrayRecords = getAllSupportMembersStatesInfos($DbConnection, $StrOrderBy);
                 $NbRecords = 0;
                 if (isset($ArrayRecords['SupportMemberStateID']))
                 {
                     $NbRecords = count($ArrayRecords['SupportMemberStateID']);
                 }

                 if ($NbRecords > 0)
                 {
                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some support members states found
                     foreach($ArrayRecords["SupportMemberStateID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the support member state ID
                             $ArrayData[0][] = $ArrayRecords["SupportMemberStateID"][$i];
                         }
                         else
                         {
                             // We display the support member state ID with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["SupportMemberStateID"][$i],
                                                                      $ArrayRecords["SupportMemberStateID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = $ArrayRecords["SupportMemberStateName"][$i];
                         $ArrayData[2][] = $ArrayRecords["SupportMemberStateDescription"][$i];

                         // Hyperlink to delete the support member state if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[3][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteSupportMemberState.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the support member state found
                     $ArraySortedFields = array("1", "2", "3");
                     if ($bCanDelete)
                     {
                         $ArrayCaptions[] = "";
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
                     // No support member state found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of support members states
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