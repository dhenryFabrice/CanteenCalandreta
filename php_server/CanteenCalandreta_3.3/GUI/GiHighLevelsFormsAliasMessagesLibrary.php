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
 * Interface module : XHTML Graphic high level forms library used to manage the alias and sent messages.
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-03-01
 */


/**
 * Display the form to submit a new alias or update an alias, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $AliasID                  String                ID of the alias to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view alias
 */
 function displayDetailsAliasForm($DbConnection, $AliasID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update an alias
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($AliasID))
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
             openForm("FormDetailsAlias", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationAlias('".$GLOBALS["LANG_ERROR_JS_ALIAS_NAME"]."', '"
                                           .$GLOBALS["LANG_ERROR_JS_ALIAS_MAILING_LIST"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_ALIAS"], "Frame", "Frame", "DetailsNews");

             // <<< Alias ID >>>
             if ($AliasID == 0)
             {
                 // Define default values to create the new alias
                 $Reference = "&nbsp;";
                 $AliasRecord = array(
                                      "AliasName" => '',
                                      "AliasDescription" => '',
                                      "AliasMailingList" => ''
                                     );
             }
             else
             {
                 if (isExistingAlias($DbConnection, $AliasID))
                 {
                     // We get the details of the alias
                     $AliasRecord = getTableRecordInfos($DbConnection, "Alias", $AliasID);
                     $Reference = $AliasID;
                 }
                 else
                 {
                     // Error, the alias doesn't exist
                     $AliasID = 0;
                     $Reference = "&nbsp;";
                 }
             }

             // <<< AliasName INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Name = generateInputField("sAliasName", "text", "50", "50", $GLOBALS["LANG_ALIAS_NAME_TIP"],
                                                $AliasRecord["AliasName"]);
                     break;
             }

             // <<< AliasDescription INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Description = generateInputField("sAliasDescription", "text", "255", "90", $GLOBALS["LANG_ALIAS_DESCRIPTION_TIP"],
                                                       $AliasRecord["AliasDescription"]);
                     break;
             }

             // <<< AliasMailingList TEXTAREA >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                         $MailingList = generateTextareaField("sAliasMailingList", 10, 60, $GLOBALS["LANG_ALIAS_MAILING_LIST_TIP"],
                                                              invFormatText($AliasRecord["AliasMailingList"]));
                         break;
             }

             // Display the form
             echo "<table id=\"AliasDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_ALIAS_NAME"]."*</td><td class=\"Value\">$Name</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_ALIAS_DESCRIPTION"]."</td><td class=\"Value\" colspan=\"3\">$Description</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_ALIAS_MAILING_LIST"]."*</td><td class=\"Value\" colspan=\"3\">$MailingList</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidAliasID", "hidden", "", "", "", $AliasID);
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
             // The supporter isn't allowed to create or update an alias
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
 * Display the form to search an alias in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-01
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some alias
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the alias found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the alias. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about an alias. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update alias
 * @param $$ArrayDiplayedFormFields    Array of Strings     Contains fieldnames of the form to display or not
 */
 function displaySearchAliasForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to alias list
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
             openForm("FormSearchAlias", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             if (empty($ArrayDiplayedFormFields))
             {
                 // Display all available fields of the form
                 $ArrayDiplayedFormFields = array(
                                                  "sAliasName" => TRUE,
                                                  "sAliasDescription" => TRUE,
                                                  "sAliasMailingList" => TRUE
                                                 );
             }

             $sAliasName = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sAliasName'])) && ($ArrayDiplayedFormFields['sAliasName']))
             {
                 // Alias name input text
                 $sAliasName = generateInputField("sAliasName", "text", "50", "25", $GLOBALS["LANG_ALIAS_NAME_TIP"],
                                                  stripslashes(strip_tags(existedPOSTFieldValue("sAliasName", stripslashes(existedGETFieldValue("sAliasName", ""))))));
             }

             $sAliasDescription = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sAliasDescription'])) && ($ArrayDiplayedFormFields['sAliasDescription']))
             {
                 // Alias description input text
                 $sAliasDescription = generateInputField("sAliasDescription", "text", "100", "25", $GLOBALS["LANG_ALIAS_DESCRIPTION_TIP"],
                                                         stripslashes(strip_tags(existedPOSTFieldValue("sAliasDescription", stripslashes(existedGETFieldValue("sAliasDescription", ""))))));
             }

             $sAliasMailingList = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sAliasMailingList'])) && ($ArrayDiplayedFormFields['sAliasMailingList']))
             {
                 // Alias mailing-list input text
                 $sAliasMailingList = generateInputField("sAliasMailingList", "text", "100", "80", $GLOBALS["LANG_ALIAS_MAILING_LIST_TIP"],
                                                         stripslashes(strip_tags(existedPOSTFieldValue("sAliasMailingList", stripslashes(existedGETFieldValue("sAliasMailingList", ""))))));
             }

             // Display the form
             echo "<table id=\"AliasList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_ALIAS"]."</td><td class=\"Value\">$sAliasName</td><td class=\"Label\">".$GLOBALS['LANG_ALIAS_DESCRIPTION']."</td><td class=\"Value\">$sAliasDescription</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_ALIAS_MAILING_LIST']."</td><td class=\"Value\" colspan=\"3\">$sAliasMailingList</td>\n</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_ALIAS_NAME"], $GLOBALS["LANG_ALIAS_DESCRIPTION"],
                                        $GLOBALS["LANG_ALIAS_MAILING_LIST"]);
                 $ArraySorts = array("AliasName", "AliasDescription", "AliasMailingList");

                 if ($bCanDelete)
                 {
                     // The supporter can delete alias : we add a column for this action
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
                     $StrOrderBy = "AliasName ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchAlias($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only alias of the page
                     $ArrayRecords = dbSearchAlias($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some alias found
                     foreach($ArrayRecords["AliasID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the alias name
                             $ArrayData[0][] = $ArrayRecords["AliasName"][$i];
                         }
                         else
                         {
                             // We display the alias name with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["AliasName"][$i], $ArrayRecords["AliasID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = $ArrayRecords["AliasDescription"][$i];
                         $ArrayData[2][] = $ArrayRecords["AliasMailingList"][$i];

                         // Hyperlink to delete the alias if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[3][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteAlias.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the alias found
                     $ArraySortedFields = array("1", "2", "3");
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
                     // No alias found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of alias
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
 * Display the list of available alias in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-11
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create, update or view alias
 */
 function displayDetailsAliasList($DbConnection, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to alias list
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
             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SUPPORT_HELP_ALIAS_PAGE_TITLE"], "Frame", "Frame", "DetailsNews");

             $ArrayAlias = dbSearchAlias($DbConnection, array(), "AliasName", 1, 0);
             if ((isset($ArrayAlias['AliasName'])) && (count($ArrayAlias['AliasName']) > 0))
             {
                 echo "<table cellspacing=\"0\" cellpadding=\"0\">\n";
                 foreach($ArrayAlias["AliasID"] as $i => $CurrentID)
                 {
                     echo "<tr>\n\t<td class=\"Label\">".$ArrayAlias["AliasName"][$i]."</td><td class=\"Value\">"
                          .$ArrayAlias["AliasDescription"][$i]."</td>\n</tr>\n";
                 }
                 echo "</table>\n";
             }

             closeStyledFrame();
         }
         else
         {
             // The supporter isn't allowed to view the list of alias
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
 * Display the form to send a message, in the current row of the table of the web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-10-21 : display checkbox to put in copy the author of a messsage and author
 *                    on read-only access
 *
 * @since 2016-03-01
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to send a message
 * @param $FileAccess               Boolean               Allow or not to send a file with a message
 */
 function displaySendMessageForm($DbConnection, $ProcessFormPage, $AccessRules = array(), $FileAccess = FALSE)
 {
     // The supporter must be logged
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to send a message
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Creation mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormSendMessage", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "multipart/form-data",
                      "VerificationSendMessage('".$GLOBALS["LANG_ERROR_JS_MESSAGE_AUTHOR"]."', '"
                                                 .$GLOBALS["LANG_ERROR_JS_MESSAGE_RECIPIENTS"]."', '"
                                                 .$GLOBALS["LANG_ERROR_JS_MESSAGE_SUBJECT"]."', '"
                                                 .$GLOBALS["LANG_ERROR_JS_MESSAGE_CONTENT"]."', '"
                                                 .implode("#", $GLOBALS["CONF_UPLOAD_ALLOWED_EXTENSIONS"])."', '"
                                                 .$GLOBALS["LANG_ERROR_JS_EXTENSION"]."', '"
                                                 .$GLOBALS["LANG_UPLOADING"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_MESSAGE"], "Frame", "Frame", "DetailsNews");

             // <<< Author INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sMessageAuthor = $_SESSION['SupportMemberLastname'];
                     if (!empty($_SESSION['SupportMemberFirstname']))
                     {
                         $sMessageAuthor .= " ".$_SESSION['SupportMemberFirstname'];
                     }

                     $sMessageAuthor .= " (".$_SESSION['SupportMemberStateName'].")";

                     $Author = generateInputField("sAuthor", "text", "50", "50", $GLOBALS["LANG_MESSAGE_AUTHOR_TIP"],
                                                  $sMessageAuthor, TRUE); // In read-only mode
                     break;
             }

             // <<< Recipients INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Recipients = generateInputField("sRecipients", "text", "255", "55", $GLOBALS["LANG_MESSAGE_RECIPIENTS_TIP"], "");

                     // Add a button to select recipients if autocompletion isn't usable by the loggued supporter
                     $Recipients .= ' '.$GLOBALS['LANG_OR'].' '."<table style=\"display: inline;\"><tr><td class=\"Action\">";
                     $Recipients .= generateStyledLinkText($GLOBALS["LANG_MESSAGE_SEARCH_RECIPIENT"], "javascript:openWindow('SearchMessageRecipients.php', 'SearchMessageRecipients', 450, 400)", "Action", "", "");
                     $Recipients .= "</td></tr></table>";

                     // Add a help buton to view the list of available alias
                     $Recipients .= ' '.generateStyledPictureHyperlink($GLOBALS["CONF_HELP_ICON"], "HelpAlias.php",
                                                                       $GLOBALS["LANG_MESSAGE_VIEW_ALIAS_LIST_TIP"], 'Affectation',
                                                                       '_blank');

                     // Area to list selected recipients
                     $Recipients .= "\n<div id=\"objRecipientsList\"></div>";
                     break;
             }

             // <<< Subject INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Subject = generateInputField("sSubject", "text", "100", "90", $GLOBALS["LANG_MESSAGE_SUBJECT_TIP"], "");
                     break;
             }

             // <<< Message TEXTAREA >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                         $Message = generateTextareaField("sMessage", 10, 60, $GLOBALS["LANG_MESSAGE_CONTENT_TIP"], "");
                         break;
             }

             // <<< Author in copy CHECKBOX >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                         $Checked = FALSE;
                         if ((isset($GLOBALS['CONF_EMAIL_SYSTEM_NOTIFICATIONS']['UserMessageEmail']))
                             && (((!empty($GLOBALS['CONF_EMAIL_SYSTEM_NOTIFICATIONS']['UserMessageEmail'][Bcc]))
                             && (in_array(TO_AUTHOR_MESSAGE, $GLOBALS['CONF_EMAIL_SYSTEM_NOTIFICATIONS']['UserMessageEmail'][Bcc])))
                             || ((!empty($GLOBALS['CONF_EMAIL_SYSTEM_NOTIFICATIONS']['UserMessageEmail'][Cc]))
                             && (in_array(TO_AUTHOR_MESSAGE, $GLOBALS['CONF_EMAIL_SYSTEM_NOTIFICATIONS']['UserMessageEmail'][Cc])))))
                         {
                             // The author must be put in copy of his message by default
                             $Checked = TRUE;
                         }

                         $AuthorInCopy = generateInputField("chkAuthorInCopy", "checkbox", "", "",
                                                             $GLOBALS["LANG_MESSAGE_AUTHOR_IN_COPY_TIP"], "authorincopy",
                                                             FALSE, $Checked)." ".$GLOBALS["LANG_YES"];
                         break;
             }


             // Display the form
             echo "<table id=\"MessageForm\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MESSAGE_AUTHOR"]."*</td><td class=\"Value\">$Author</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MESSAGE_RECIPIENTS"]."*</td><td class=\"Value\">$Recipients</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MESSAGE_SUBJECT"]."*</td><td class=\"Value\">$Subject</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MESSAGE_CONTENT"]."*</td><td class=\"Value\">$Message</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_MESSAGE_AUTHOR_IN_COPY"]."</td><td class=\"Value\">$AuthorInCopy</td>\n</tr>\n";

             // Area to send a file
             if ($FileAccess)
             {
                 // Display the allowed file extensions
                 $Extensions = "<span style=\"text-decoration: underline;\">".$GLOBALS["LANG_ALLOWED_FILES_EXTENSIONS"]."</span> : ".generateBR(1)
                               .implode(", ", $GLOBALS["CONF_UPLOAD_ALLOWED_EXTENSIONS"]).".";

                 $fMaxSize = $GLOBALS['CONF_UPLOAD_MESSAGE_FILES_MAXSIZE'] / (1024 * 1024);

                 $UploadFile = generateInputField("fFilename", "file", "", "50", $GLOBALS["LANG_FILENAME_TIP"], "")." $fMaxSize MB.";
                 $UploadFile .= generateInputField("MAX_FILE_SIZE", "hidden", "", "", "", $GLOBALS["CONF_UPLOAD_MESSAGE_FILES_MAXSIZE"]);
                 $UploadFile .= generateBR(2).generateStyledText($Extensions, "AllowedExtensions");

                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FILENAME"]."</td><td class=\"Value\">$UploadFile</td>\n</tr>\n";
             }

             echo "</table>\n";

             insertInputField("hidMessageRecipients", "hidden", "", "", "", "");
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
             // The supporter isn't allowed to send a message
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
 * Display the form to search a user in the current row of the table of the web page, in the graphic
 * interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-03-14
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 *                                                    allowing to find families, alias...
 * @param $ArrayParams          Array of Strings      Filter used to find families, alias...
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create, update or view alias
 */
 function displaySearchRecipientMessageForm($DbConnection, $ProcessFormPage, $ArrayParams, $AccessRules = array())
 {
     // The supporter must be logged
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to send a message
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Creation mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
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

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormSearchUser", "post", "$ProcessFormPage", '', '');

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             // <<< Name INPUTFIELD >>>
             $sName = generateInputField("sName", "text", "50", "20", $GLOBALS["LANG_SUPPORT_SEARCH_MESSAGE_RECIPIENTS_PAGE_NAME_TIP"],
                                         stripslashes(strip_tags(existedPOSTFieldValue("sName", stripslashes(existedGETFieldValue("sName", ""))))));

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SUPPORT_SEARCH_MESSAGE_RECIPIENTS_PAGE_NAME"]."</td><td class=\"Value\">$sName</td>\n</tr>\n</table>\n";

             // Display the hidden fields
             closeStyledFrame();

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();

             // The user has executed a search
             if ((isset($ArrayParams['Name'])) && (!empty($ArrayParams['Name'])))
             {
                 $ArrayRecipients = dbSearchMessageRecipients($DbConnection, $ArrayParams, "rName", 1, 0);

                 // There are some found entries (alias, families...)
                 if ((isset($ArrayRecipients['rName'])) && (count($ArrayRecipients['rName']) > 0))
                 {
                     $NbRecords = count($ArrayRecipients['rName']);
                     $ArrayCaptions = array($GLOBALS["LANG_LASTNAME"]." / ".$GLOBALS["LANG_ALIAS"]);
                     $ArraySorts = array("");
                     $ArrayData = array(
                                        0 => array()
                                       );

                     foreach($ArrayRecipients['rName'] as $n => $rName)
                     {
                         $sNameToDisplay = $rName." (".$ArrayRecipients['rStateName'][$n].")";
                         if (!in_array($sNameToDisplay, array_values($ArrayData[0])))
                         {
                             $ArrayData[0][] = generateStyledLinkText($sNameToDisplay,
                                                                      "javascript:SelectRecipientName('".$ArrayRecipients['rID'][$n]
                                                                      ."', '".addslashes($sNameToDisplay)."', '"
                                                                      .$GLOBALS['CONF_LANG']."')",
                                                                      "", $GLOBALS["LANG_SUPPORT_SEARCH_MESSAGE_RECIPIENTS_PAGE_NAME_INSTRUCTIONS"],
                                                                      "");
                         }
                     }

                     // Display the table which contains the found families, alias...
                     displayBR(2);
                     displayStyledTable($ArrayCaptions, $ArraySorts, '', $ArrayData, '', '', '', '', array(), 0,
                                        array(0 => "textLeft"));

                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NB_RECORDS_FOUND'].$NbRecords;
                     displayBR(2);
                     displayStyledText($GLOBALS["LANG_SUPPORT_SEARCH_MESSAGE_RECIPIENTS_PAGE_NAME_INSTRUCTIONS"], "Instructions");
                     closeParagraph();
                 }
                 else
                 {
                     // No family, alias... found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to send a message
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