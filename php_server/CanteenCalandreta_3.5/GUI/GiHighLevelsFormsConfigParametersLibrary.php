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
 * Interface module : XHTML Graphic high level forms library used to display config parameters
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-11-03
 */


/**
 * Display the form to submit a new config parameter or update a config parameter, in the current row
 * of the table of the web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-03
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $ConfigParameterID        String                ID of the config parameter to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view config parameters
 */
 function displayDetailsConfigParameterForm($DbConnection, $ConfigParameterID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         /// The supporter must be allowed to create or update a config parameter
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
             openForm("FormDetailsConfigParameter", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationConfigParameter('".$GLOBALS["LANG_ERROR_MANDORY_FIELDS"]."')");

             // <<< ConfigParameterID >>>
             if ($ConfigParameterID == 0)
             {
                 // Define default values to create the new config parameter
                 $Reference = "";
                 $ConfigParameterRecord = array(
                                                "ConfigParameterName" => '',
                                                "ConfigParameterType" => '',
                                                "ConfigParameterValue" => ''
                                               );
             }
             else
             {
                 if (isExistingConfigParameter($DbConnection, $ConfigParameterID))
                 {
                     // We get the details of the config parameter
                     $ConfigParameterRecord = getTableRecordInfos($DbConnection, "ConfigParameters", $ConfigParameterID);
                     $Reference = $ConfigParameterID;
                 }
                 else
                 {
                     // Error, the config parameter doesn't exist
                     $ConfigParameterID = 0;
                     $Reference = "";
                     $ConfigParameterRecord = array(
                                                    "ConfigParameterName" => '',
                                                    "ConfigParameterType" => '',
                                                    "ConfigParameterValue" => ''
                                                   );
                 }
             }

             // Display the table (frame) where the form will take place
             $FrameTitle = $GLOBALS["LANG_CONFIG_PARAMETER"];
             if (!empty($ConfigParameterID))
             {
                 $FrameTitle .= " ($Reference)";
             }

             openStyledFrame($FrameTitle, "Frame", "Frame", "DetailsNews");

             // <<< ConfigParameterName INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sStateName = stripslashes($ConfigParameterRecord["ConfigParameterName"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sParamName = generateInputField("sParamName", "text", "255", "70", $GLOBALS["LANG_CONFIG_PARAMETER_NAME_TIP"],
                                                      $ConfigParameterRecord["ConfigParameterName"]);
                     break;
             }

             // <<< ConfigParameterType INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sParamType = stripslashes($ConfigParameterRecord["ConfigParameterType"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sParamType = generateInputField("sParamType", "text", "10", "10", $GLOBALS["LANG_CONFIG_PARAMETER_TYPE_TIP"],
                                                      $ConfigParameterRecord["ConfigParameterType"]);
                     break;
             }

             // <<< ConfigParameterValue INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sParamValue = stripslashes(nullFormatText($ConfigParameterRecord["ConfigParameterValue"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sParamValue = generateTextareaField("sParamValue", 15, 70, $GLOBALS["LANG_CONFIG_PARAMETER_VALUE_TIP"],
                                                           invFormatText($ConfigParameterRecord["ConfigParameterValue"]));
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CONFIG_PARAMETER_NAME"]."*</td><td class=\"Value\">$sParamName</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_CONFIG_PARAMETER_TYPE"]."*</td><td class=\"Value\">$sParamType</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_CONFIG_PARAMETER_VALUE']."</td><td class=\"Value\">$sParamValue</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidConfigParameterID", "hidden", "", "", "", $ConfigParameterID);
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

             if ($ConfigParameterID > 0)
             {
                 // Display the config parameter like an array for Config.php
                 displayBR();
                 openParagraph('');
                 echo generateConfigParameterRender($DbConnection, $ConfigParameterID, $AccessRules);
                 closeParagraph();
             }
         }
         else
         {
             // The supporter isn't allowed to create or update a config parameter
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
 * Generate a representation of the parameter of configuration for the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-08
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $ConfigParameterID        String                ID of the config parameter to display
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view config parameters
 *
 * @return string                   A representation of the parameter of configuration in XHTML
 */
 function generateConfigParameterRender($DbConnection, $ConfigParameterID, $AccessRules = array())
 {
     $sOutput = '';

     if ((isSet($_SESSION["SupportMemberID"])) && ($ConfigParameterID > 0))
     {
         // The supporter must be allowed to access to config parameters list
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
             if (isExistingConfigParameter($DbConnection, $ConfigParameterID))
             {
                 // We get the details of the config parameter
                 $ConfigParameterRecord = getTableRecordInfos($DbConnection, "ConfigParameters", $ConfigParameterID);
                 loadDbConfigParameters($DbConnection, array($ConfigParameterRecord['ConfigParameterName']));

                 if (isset($ConfigParameterRecord['ConfigParameterName']))
                 {
                     // Display the associative array as a PHP variable (with usable PHP syntax)
                     $sOutput = generateStyledText($GLOBALS['LANG_CONFIG_PARAMETER_RENDER'], 'ConfigParameterRenderIntro');
                     $sOutput .= generateBR(1);

                     $sOutput .= '$'.$ConfigParameterRecord['ConfigParameterName']." = Array(<br />\n";
                     $sBlankPrefix = str_repeat("&nbsp;", strlen('$'.$ConfigParameterRecord['ConfigParameterName']." = Array("));
                     $sOutput .= generateArrayTree($GLOBALS[$ConfigParameterRecord['ConfigParameterName']], 1, 16, $sBlankPrefix);
                     $sOutput .= "$sBlankPrefix);";
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of config parameters
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

     return $sOutput;
 }


/**
 * Display the form to search a config parameter in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-11-03
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some config parameters
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the config parameters found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the config parameters. If < 0, DESC is used,
 *                                                          otherwise ASC is used
 * @param $DetailsPage                 String               URL of the page to display details about a config parameter.
 *                                                          This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update config parameters
 */
 function displaySearchConfigParametersForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to config parameters list
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
             openForm("FormSearchConfigParameter", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");


             // Display the form
             echo "<table id=\"ConfigParametersList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_REFERENCE"], $GLOBALS["LANG_CONFIG_PARAMETER_NAME"],
                                        $GLOBALS['LANG_CONFIG_PARAMETER_TYPE']);
                 $ArraySorts = array("ConfigParameterID", "ConfigParameterName", "ConfigParameterType");

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
                     $StrOrderBy = "ConfigParameterID";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchConfigParameters($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only config parameters of the page
                     $ArrayRecords = dbSearchConfigParameters($DbConnection, $TabParams, $StrOrderBy, $Page,
                                                              $GLOBALS["CONF_RECORDS_PER_PAGE"]);
                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some config parameters found
                     foreach($ArrayRecords["ConfigParameterID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the config parameter ID
                             $ArrayData[0][] = $ArrayRecords["ConfigParameterID"][$i];
                         }
                         else
                         {
                             // We display the config parameter ID with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["ConfigParameterID"][$i],
                                                                      $ArrayRecords["ConfigParameterID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = $ArrayRecords["ConfigParameterName"][$i];
                         $ArrayData[2][] = $ArrayRecords["ConfigParameterType"][$i];

                         // Hyperlink to delete the config parameter if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[3][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteConfigParameter.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the config parameters found
                     $ArraySortedFields = array("1", "2", "3");
                     if ($bCanDelete)
                     {
                         $ArrayCaptions[] = "";
                         $ArraySorts[] = "";
                         $ArraySortedFields[] = "";
                     }

                     displayStyledTable($ArrayCaptions, $ArraySortedFields, $SortFct, $ArrayData, '', '', '', '',
                                        array(), $OrderBy, array(0 => '', 1 => 'textLeft'));

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
                     // No config parameter found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of config parameters
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