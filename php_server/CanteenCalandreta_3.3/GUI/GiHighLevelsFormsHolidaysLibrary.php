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
 * Interface module : XHTML Graphic high level forms library used to manage the holidays and
 * opened special days
 *
 * @author Christophe Javouhey
 * @version 3.0
 * @since 2016-10-25
 */


/**
 * Display the form to submit a new holiday or update a holiday, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-25
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $HolidayID                String                ID of the holiday to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view holidays
 */
 function displayDetailsHolidayForm($DbConnection, $HolidayID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         /// The supporter must be allowed to create or update a holiday
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($HolidayID))
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
             openForm("FormDetailsHoliday", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationHoliday('".$GLOBALS["LANG_ERROR_JS_START_DATE"]."', '"
                                             .$GLOBALS["LANG_ERROR_JS_WRONG_START_END_DATES"]."')");

             // <<< HolidayID >>>
             if ($HolidayID == 0)
             {
                 // Define default values to create the new holiday
                 $Reference = "";
                 $HolidayRecord = array(
                                        "HolidayStartDate" => '',
                                        "HolidayEndDate" => '',
                                        "HolidayDescription" => ''
                                       );
             }
             else
             {
                 if (isExistingHoliday($DbConnection, $HolidayID))
                 {
                     // We get the details of the holiday
                     $HolidayRecord = getTableRecordInfos($DbConnection, "Holidays", $HolidayID);
                     $Reference = $HolidayID;
                 }
                 else
                 {
                     // Error, the holiday doesn't exist
                     $HolidayID = 0;
                     $Reference = "";
                 }
             }

             // Display the table (frame) where the form will take place
             $FrameTitle = substr($GLOBALS["LANG_HOLIDAY"], 0, -1);
             if (!empty($HolidayID))
             {
                 $FrameTitle .= " ($Reference)";
             }

             openStyledFrame($FrameTitle, "Frame", "Frame", "DetailsNews");

             // <<< HolidayDescription INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sDescription = stripslashes($HolidayRecord["HolidayDescription"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sDescription = generateInputField("sDescription", "text", "255", "55", $GLOBALS["LANG_HOLIDAY_DESCRIPTION_TIP"],
                                                        $HolidayRecord["HolidayDescription"]);
                     break;
             }

             // <<< Start date INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (empty($HolidayRecord["HolidayStartDate"]))
                     {
                         $sStartDate = "-";
                     }
                     else
                     {
                         $sStartDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($HolidayRecord["HolidayStartDate"]));
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if (empty($HolidayRecord["HolidayStartDate"]))
                     {
                         $sStartDate = "";
                     }
                     else
                     {
                         $sStartDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($HolidayRecord["HolidayStartDate"]));
                     }

                     $sStartDate = generateInputField("startDate", "text", "10", "10", $GLOBALS["LANG_HOLIDAY_START_DATE_TIP"],
                                                      $sStartDate);
                     $sStartDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallbackStartDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                     break;
             }

             // <<< End date INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (empty($HolidayRecord["HolidayEndDate"]))
                     {
                         $sEndDate = "-";
                     }
                     else
                     {
                         $sEndDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($HolidayRecord["HolidayEndDate"]));
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if (empty($HolidayRecord["HolidayEndDate"]))
                     {
                         $sEndDate = "";
                     }
                     else
                     {
                         $sEndDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($HolidayRecord["HolidayEndDate"]));
                     }

                     $sEndDate = generateInputField("endDate", "text", "10", "10", $GLOBALS["LANG_HOLIDAY_END_DATE_TIP"],
                                                     $sEndDate);
                     $sEndDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t EndDateCalendar = new dynCalendar('EndDateCalendar', 'calendarCallbackEndDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_HOLIDAY_DESCRIPTION"]."</td><td class=\"Value\" colspan=\"3\">$sDescription</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_HOLIDAY_START_DATE']."*</td><td class=\"Value\">$sStartDate</td><td class=\"Label\">".$GLOBALS["LANG_HOLIDAY_END_DATE"]."*</td><td class=\"Value\">$sEndDate</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidHolidayID", "hidden", "", "", "", $HolidayID);
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
             // The supporter isn't allowed to create or update a holiday
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
 * Display the form to search a holiday in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-25
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some holidays
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the holidays found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the holidays. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about a holiday. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update holidays
 */
 function displaySearchHolidaysForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to holidays list
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
             openForm("FormSearchHoliday", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             // Description input text
             $sDefaultValue = stripslashes(strip_tags(existedPOSTFieldValue("sDescription",
                                                                            stripslashes(existedGETFieldValue("sDescription", "")))));
             if ((empty($sDefaultValue)) && (isset($TabParams['HolidayDescription'])) && (!empty($TabParams['HolidayDescription'])))
             {
                 $sDefaultValue = $TabParams['HolidayDescription'];
             }
             $sDescription = generateInputField("sDescription", "text", "50", "22", $GLOBALS["LANG_HOLIDAY_DESCRIPTION_TIP"], $sDefaultValue);

             // <<< Start date INPUTFIELD >>>
             $iDefaultSelectedValue = zeroFormatValue(existedPOSTFieldValue("lOperatorStartDate",
                                                                            existedGETFieldValue("lOperatorStartDate", 0)));
             $sDefaultDateValue = stripslashes(strip_tags(existedPOSTFieldValue("startDate",
                                                                                stripslashes(existedGETFieldValue("startDate", "")))));
             if ((empty($sDefaultDateValue)) && (isset($TabParams['HolidayStartDate'])) && (!empty($TabParams['HolidayStartDate'])))
             {
                 $sDefaultDateValue = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($TabParams['HolidayStartDate'][0]));
                 $iDefaultSelectedValue = array_search($TabParams['HolidayStartDate'][1], $GLOBALS["CONF_LOGICAL_OPERATORS"]);
             }

             $sStartDate = generateSelectField("lOperatorStartDate", array_keys($GLOBALS["CONF_LOGICAL_OPERATORS"]),
                                               $GLOBALS["CONF_LOGICAL_OPERATORS"], $iDefaultSelectedValue, "");
             $sStartDate .= "&nbsp;".generateInputField("startDate", "text", "10", "10", $GLOBALS["LANG_HOLIDAY_START_DATE_TIP"],
                                                        $sDefaultDateValue);
             $sStartDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallbackStartDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";

             // <<< End date INPUTFIELD >>>
             $iDefaultSelectedValue = zeroFormatValue(existedPOSTFieldValue("lOperatorEndDate",
                                                                            existedGETFieldValue("lOperatorEndDate", 0)));
             $sDefaultDateValue = stripslashes(strip_tags(existedPOSTFieldValue("endDate",
                                                                                stripslashes(existedGETFieldValue("endDate", "")))));
             if ((empty($sDefaultDateValue)) && (isset($TabParams['HolidayEndDate'])) && (!empty($TabParams['HolidayEndDate'])))
             {
                 $sDefaultDateValue = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($TabParams['HolidayEndDate'][0]));
                 $iDefaultSelectedValue = array_search($TabParams['HolidayEndDate'][1], $GLOBALS["CONF_LOGICAL_OPERATORS"]);
             }

             $sEndDate = generateSelectField("lOperatorEndDate", array_keys($GLOBALS["CONF_LOGICAL_OPERATORS"]),
                                             $GLOBALS["CONF_LOGICAL_OPERATORS"], $iDefaultSelectedValue, "");
             $sEndDate .= "&nbsp;".generateInputField("endDate", "text", "10", "10", $GLOBALS["LANG_HOLIDAY_END_DATE_TIP"],
                                                      $sDefaultDateValue);
             $sEndDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t EndDateCalendar = new dynCalendar('EndDateCalendar', 'calendarCallbackEndDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";

             // Display the form
             echo "<table id=\"HolidaysList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_HOLIDAY_DESCRIPTION"]."</td><td class=\"Value\">$sDescription</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_HOLIDAY_START_DATE']."</td><td class=\"Value\">$sStartDate</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_HOLIDAY_END_DATE"]."</td><td class=\"Value\">$sEndDate</td>\n</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_HOLIDAY"], $GLOBALS["LANG_HOLIDAY_START_DATE"],
                                        $GLOBALS["LANG_HOLIDAY_END_DATE"]);
                 $ArraySorts = array("HolidayDescription", "HolidayStartDate", "HolidayEndDate");


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
                     $StrOrderBy = "HolidayEndDate DESC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchHoliday($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only holidays of the page
                     $ArrayRecords = dbSearchHoliday($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some holidays found
                     foreach($ArrayRecords["HolidayID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the Holiday description
                             $ArrayData[0][] = $ArrayRecords["HolidayDescription"][$i];
                         }
                         else
                         {
                             // We display the holiday description with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["HolidayDescription"][$i], $ArrayRecords["HolidayID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($ArrayRecords["HolidayStartDate"][$i]));
                         $ArrayData[2][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($ArrayRecords["HolidayEndDate"][$i]));

                         // Hyperlink to delete the holidays if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[3][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteHoliday.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the holidays found
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
                     // No holiday found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of holidays
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
 * Display the form to submit a new opened sepcial day or update an opened special day, in the current row
 * of the table of the web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $OpenedSpecialDayID       String                ID of the opened special day to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view opened special days
 */
 function displayDetailsOpenedSpecialDayForm($DbConnection, $OpenedSpecialDayID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         /// The supporter must be allowed to create or update an opened special day
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($OpenedSpecialDayID))
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
             openForm("FormDetailsOpenedSpecialDay", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationOpenedSpecialDay('".$GLOBALS["LANG_ERROR_JS_START_DATE"]."')");

             // <<< OpenedSpecialDayID >>>
             if ($OpenedSpecialDayID == 0)
             {
                 // Define default values to create the new opened special day
                 $Reference = "";
                 $OpenedSpecialDayRecord = array(
                                                 "OpenedSpecialDayDate" => '',
                                                 "OpenedSpecialDayDescription" => ''
                                                );
             }
             else
             {
                 if (isExistingOpenedSpecialDay($DbConnection, $OpenedSpecialDayID))
                 {
                     // We get the details of the opened special day
                     $OpenedSpecialDayRecord = getTableRecordInfos($DbConnection, "OpenedSpecialDays", $OpenedSpecialDayID);
                     $Reference = $OpenedSpecialDayID;
                 }
                 else
                 {
                     // Error, the opened special day doesn't exist
                     $OpenedSpecialDayID = 0;
                     $Reference = "";
                 }
             }

             // Display the table (frame) where the form will take place
             $FrameTitle = substr($GLOBALS["LANG_OPENED_SPECIAL_DAY"], 0, -1);
             if (!empty($OpenedSpecialDayID))
             {
                 $FrameTitle .= " ($Reference)";
             }

             openStyledFrame($FrameTitle, "Frame", "Frame", "DetailsNews");

             // <<< OpenedSpecialDayDescription INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $sDescription = stripslashes($OpenedSpecialDayRecord["OpenedSpecialDayDescription"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $sDescription = generateInputField("sDescription", "text", "255", "25", $GLOBALS["LANG_OPENED_SPECIAL_DAY_DESCRIPTION_TIP"],
                                                        $OpenedSpecialDayRecord["OpenedSpecialDayDescription"]);
                     break;
             }

             // <<< Date INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (empty($OpenedSpecialDayRecord["OpenedSpecialDayDate"]))
                     {
                         $sDate = "-";
                     }
                     else
                     {
                         $sDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($OpenedSpecialDayRecord["OpenedSpecialDayDate"]));
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if (empty($OpenedSpecialDayRecord["OpenedSpecialDayDate"]))
                     {
                         $sDate = "";
                     }
                     else
                     {
                         $sDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($OpenedSpecialDayRecord["OpenedSpecialDayDate"]));
                     }

                     $sDate = generateInputField("startDate", "text", "10", "10", $GLOBALS["LANG_OPENED_SPECIAL_DAY_DATE_TIP"],
                                                      $sDate);
                     $sDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallbackStartDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_OPENED_SPECIAL_DAY_DESCRIPTION"]."</td><td class=\"Value\">$sDescription</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_OPENED_SPECIAL_DAY_DATE']."*</td><td class=\"Value\">$sDate</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidOpenedSpecialDayID", "hidden", "", "", "", $OpenedSpecialDayID);
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
             // The supporter isn't allowed to create or update an opened special day
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
 * Display the form to search an opened special day in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-26
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some opened special days
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the opened special days found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the opened special days. If < 0, DESC is used,
 *                                                          otherwise ASC is used
 * @param $DetailsPage                 String               URL of the page to display details about an opened special day.
 *                                                          This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update opened special days
 */
 function displaySearchOpenedSpecialDaysForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to opened special days list
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
             openForm("FormSearchOpenedSpecialDay", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             // Description input text
             $sDefaultValue = stripslashes(strip_tags(existedPOSTFieldValue("sDescription",
                                                                            stripslashes(existedGETFieldValue("sDescription", "")))));
             if ((empty($sDefaultValue)) && (isset($TabParams['OpenedSpecialDayDescription']))
                  && (!empty($TabParams['OpenedSpecialDayDescription'])))
             {
                 $sDefaultValue = $TabParams['OpenedSpecialDayDescription'];
             }
             $sDescription = generateInputField("sDescription", "text", "50", "22", $GLOBALS["LANG_OPENED_SPECIAL_DAY_DESCRIPTION_TIP"],
                                                $sDefaultValue);

             // <<< Start date INPUTFIELD >>>
             $sDefaultDateValue = stripslashes(strip_tags(existedPOSTFieldValue("startDate",
                                                                                stripslashes(existedGETFieldValue("startDate", "")))));
             if ((empty($sDefaultDateValue)) && (isset($TabParams['StartDate'])) && (!empty($TabParams['StartDate'])))
             {
                 $sDefaultDateValue = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($TabParams['StartDate']));
             }

             $sStartDate = generateInputField("startDate", "text", "10", "10", $GLOBALS["LANG_OPENED_SPECIAL_DAY_START_DATE_TIP"],
                                              $sDefaultDateValue);
             $sStartDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallbackStartDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";

             // <<< End date INPUTFIELD >>>
             $sDefaultDateValue = stripslashes(strip_tags(existedPOSTFieldValue("endDate",
                                                                                stripslashes(existedGETFieldValue("endDate", "")))));
             if ((empty($sDefaultDateValue)) && (isset($TabParams['EndDate'])) && (!empty($TabParams['EndDate'])))
             {
                 $sDefaultDateValue = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($TabParams['EndDate']));
             }

             $sEndDate = generateInputField("endDate", "text", "10", "10", $GLOBALS["LANG_OPENED_SPECIAL_DAY_END_DATE_TIP"],
                                             $sDefaultDateValue);
             $sEndDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t EndDateCalendar = new dynCalendar('EndDateCalendar', 'calendarCallbackEndDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";

             // Display the form
             echo "<table id=\"OpenedSpecialDaysList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_OPENED_SPECIAL_DAY_DESCRIPTION"]."</td><td class=\"Value\">$sDescription</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_OPENED_SPECIAL_DAY_START_DATE']."</td><td class=\"Value\">$sStartDate</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_OPENED_SPECIAL_DAY_END_DATE"]."</td><td class=\"Value\">$sEndDate</td>\n</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_OPENED_SPECIAL_DAY"], $GLOBALS["LANG_OPENED_SPECIAL_DAY_DATE"]);
                 $ArraySorts = array("OpenedSpecialDayDescription", "OpenedSpecialDayDate");

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
                     $StrOrderBy = "OpenedSpecialDayDate DESC";
                 }

                 // We launch the search
                 $SelectedStartDate = '';
                 $SelectedEndDate = '';
                 $SelectedDescription = '';

                 if (!isset($TabParams['StartDate']))
                 {
                     $SelectedStartDate = getOpenedSpecialDayMinDate($DbConnection);
                     if (empty($SelectedStartDate))
                     {
                         $SelectedStartDate = date('Y-m-d');
                     }
                 }
                 else
                 {
                     $SelectedStartDate = $TabParams['StartDate'];
                 }

                 if (!isset($TabParams['EndDate']))
                 {
                     $SelectedEndDate = getOpenedSpeciaDayMaxDate($DbConnection);
                     if (empty($SelectedEndDate))
                     {
                         $SelectedEndDate = date('Y-m-d');
                     }
                 }
                 else
                 {
                     $SelectedEndDate = $TabParams['EndDate'];
                 }

                 if (isset($TabParams['OpenedSpecialDayDescription']))
                 {
                     $SelectedDescription = $TabParams['OpenedSpecialDayDescription'];
                 }

                 $ArrayRecords = getOpenedSpecialDays($DbConnection, $SelectedStartDate, $SelectedEndDate, $StrOrderBy,
                                                      $SelectedDescription);
                 $NbRecords = 0;
                 if (isset($ArrayRecords['OpenedSpecialDayID']))
                 {
                     $NbRecords = count($ArrayRecords['OpenedSpecialDayID']);
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

                     // There are some opened special days found
                     foreach($ArrayRecords["OpenedSpecialDayID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the opened special day description
                             $ArrayData[0][] = $ArrayRecords["OpenedSpecialDayDescription"][$i];
                         }
                         else
                         {
                             // We display the opened special day description with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["OpenedSpecialDayDescription"][$i],
                                                                      $ArrayRecords["OpenedSpecialDayID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                strtotime($ArrayRecords["OpenedSpecialDayDate"][$i]));

                         // Hyperlink to delete the opened special day if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[2][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteOpenedSpecialDay.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the opened special days found
                     $ArraySortedFields = array("1", "2");
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
                     // No opened special day found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of opened special days
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