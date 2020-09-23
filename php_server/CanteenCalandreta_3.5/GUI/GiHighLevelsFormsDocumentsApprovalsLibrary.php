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
 * Interface module : XHTML Graphic high level forms library used to manage the documents approvals and families' approvals.
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-05-07
 */


/**
 * Display the form to submit a new document approval or update a document approval, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2019-11-08 : patch the display of the date when the document is in update mode
 *
 * @since 2019-05-07
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $DocumentApprovalID       String                ID of the document approval to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to add, update or view documents approvals
 */
 function displayDetailsDocumentApprovalForm($DbConnection, $DocumentApprovalID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to add (create) or update a document approval
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         if (empty($DocumentApprovalID))
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
             openForm("FormDetailsDocument", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "multipart/form-data",
                      "VerificationDocumentApproval('".$GLOBALS["LANG_ERROR_JS_DOCUMENT_APPROVAL_NAME"]."', '"
                                                    .$GLOBALS["LANG_ERROR_JS_DOCUMENT_APPROVAL_FILENAME"]."', '".implode("#", $GLOBALS['CONF_UPLOAD_ALLOWED_EXTENSIONS'])
                                                    ."', '".$GLOBALS["LANG_ERROR_JS_EXTENSION"]."', '".$GLOBALS["LANG_UPLOADING"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_DOCUMENT_APPROVAL"], "Frame", "Frame", "DetailsNews");

             // <<< Document Approval ID >>>
             if ($DocumentApprovalID == 0)
             {
                 // Define default values to create the new document approval
                 $Reference = "&nbsp;";

                 $DocumentApprovalTypeDefaultValue = 0;
                 if (isset($GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DocumentApprovalType']))
                 {
                     $DocumentApprovalTypeDefaultValue = $GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DocumentApprovalType'];
                 }

                 $DocumentRecord = array(
                                         "DocumentApprovalType" => $DocumentApprovalTypeDefaultValue,
                                         "DocumentApprovalDate" => date('Y-m-d H:i:s'),
                                         "DocumentApprovalName" => '',
                                         "DocumentApprovalFile" => ''
                                        );
             }
             else
             {
                 if (isExistingDocumentApproval($DbConnection, $DocumentApprovalID))
                 {
                     // We get the details of the document approval
                     $DocumentRecord = getTableRecordInfos($DbConnection, "DocumentsApprovals", $DocumentApprovalID);
                     $Reference = $DocumentApprovalID;

                     // We get the families' approvals of the current document
                     $ArrayFamiliesApprovals = getFamiliesApprovalsOfDocumentApproval($DbConnection, $DocumentApprovalID, 'FamilyLastname');
                 }
             }

             // We define the captions of the families' approvals table
             $FamiliesApprovals = '&nbsp;';
             $TabFamiliesApprovalsCaptions = array($GLOBALS["LANG_FAMILY_LASTNAME"], $GLOBALS['LANG_DOCUMENT_FAMILY_APPROVAL_BY'], ucfirst($GLOBALS["LANG_DATE"]),
                                                   $GLOBALS['LANG_DOCUMENT_FAMILY_APPROVAL_COMMENT']);

             switch($cUserAccess)
             {
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $bDisplayApprovals = TRUE;

                     $sFamilyLastname = '&nbsp;';
                     if ((isset($_SESSION['FamilyID'])) && ($_SESSION['FamilyID'] > 0))
                     {
                         $sFamilyLastname = generateCryptedHyperlink(getTableFieldValue($DbConnection, 'Families', $_SESSION['FamilyID'], 'FamilyLastname'),
                                                                     $_SESSION['FamilyID'], 'UpdateFamily.php', $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');
                     }

                     $TabFamiliesApprovalsData[0][] = $sFamilyLastname;

                     // Check if the family has approved the doucment
                     $iPosFamily = array_search($_SESSION['FamilyID'], $ArrayFamiliesApprovals['FamilyID']);
                     if ($iPosFamily === FALSE)
                     {
                         // The family hasn't approved the document
                         $TabFamiliesApprovalsData[1][] = "&nbsp;";
                         $TabFamiliesApprovalsData[2][] = "&nbsp;";

                         // Field to enter a comment
                         $TabFamiliesApprovalsData[3][] = generateInputField("sDocumentFamilyApprovalComment", "text", "255", "50",
                                                                             $GLOBALS["LANG_DOCUMENT_FAMILY_APPROVAL_COMMENT_TIP"], '');
                     }
                     else
                     {
                         // The family has approved the document
                         $TabFamiliesApprovalsCaptions[] = '&nbsp;';
                         $TabFamiliesApprovalsData[1][] = $ArrayFamiliesApprovals["SupportMemberLastname"][$iPosFamily].' '
                                                          .$ArrayFamiliesApprovals["SupportMemberFirstname"][$iPosFamily];
                         $TabFamiliesApprovalsData[2][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'].' '.$GLOBALS['CONF_TIME_DISPLAY_FORMAT'],
                                                                   strtotime($ArrayFamiliesApprovals["DocumentFamilyApprovalDate"][$iPosFamily]));

                         $TabFamiliesApprovalsData[3][] = nullFormatText($ArrayFamiliesApprovals["DocumentFamilyApprovalComment"][$iPosFamily]);

                         // We can delete him
                         $TabFamiliesApprovalsData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                         "DeleteDocumentFamilyApproval.php?Cr="
                                                                                         .md5($ArrayFamiliesApprovals["DocumentFamilyApprovalID"][$iPosFamily])
                                                                                         ."&amp;Id=".$ArrayFamiliesApprovals["DocumentFamilyApprovalID"][$iPosFamily],
                                                                                         $GLOBALS["LANG_DELETE"], 'Affectation');
                     }

                     break;

                 case FCT_ACT_READ_ONLY:
                     $bDisplayApprovals = FALSE;
                     break;

                 case FCT_ACT_UPDATE:
                     $bDisplayApprovals = TRUE;
                     $TabFamiliesApprovalsCaptions[] = '&nbsp;';

                     if ((isset($ArrayFamiliesApprovals["DocumentFamilyApprovalID"]))
                         && (count($ArrayFamiliesApprovals["DocumentFamilyApprovalID"]) > 0))
                     {
                         foreach($ArrayFamiliesApprovals["DocumentFamilyApprovalID"] as $i => $CurrentID)
                         {
                             $sFamilyLastname = '&nbsp;';

                             // We check if the suport member is linked to a family
                             if ($ArrayFamiliesApprovals["FamilyID"][$i] > 0)
                             {
                                 $sFamilyLastname = generateCryptedHyperlink($ArrayFamiliesApprovals["FamilyLastname"][$i], $ArrayFamiliesApprovals["FamilyID"][$i],
                                                                             'UpdateFamily.php', $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');
                             }

                             $TabFamiliesApprovalsData[0][] = $sFamilyLastname;
                             $TabFamiliesApprovalsData[1][] = $ArrayFamiliesApprovals["SupportMemberLastname"][$i].' '.$ArrayFamiliesApprovals["SupportMemberFirstname"][$i];
                             $TabFamiliesApprovalsData[2][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'].' '.$GLOBALS['CONF_TIME_DISPLAY_FORMAT'],
                                                                   strtotime($ArrayFamiliesApprovals["DocumentFamilyApprovalDate"][$i]));

                             $TabFamiliesApprovalsData[3][] = nullFormatText($ArrayFamiliesApprovals["DocumentFamilyApprovalComment"][$i]);

                             // We can delete him
                             $TabFamiliesApprovalsData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                             "DeleteDocumentFamilyApproval.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                             $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }
                     break;
             }

             // <<< DocumentApprovalType SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Type = $GLOBALS['CONF_DOCUMENTS_APPROVALS_TYPES'][$DocumentRecord['DocumentApprovalType']];
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Type = generateSelectField("lType", array_keys($GLOBALS['CONF_DOCUMENTS_APPROVALS_TYPES']),
                                                 array_values($GLOBALS['CONF_DOCUMENTS_APPROVALS_TYPES']), $DocumentRecord['DocumentApprovalType']);
                     break;
             }

             // <<< DocumentApprovalName INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Name = stripslashes($DocumentRecord["DocumentApprovalName"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Name = generateInputField("sDocumentApprovalName", "text", "255", "70", $GLOBALS["LANG_DOCUMENT_APPROVAL_NAME_TIP"],
                                                $DocumentRecord["DocumentApprovalName"]);
                     break;
             }

             // <<< DocumentApprovalFile INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     // Check if there is a linked file
                     if (!empty($DocumentRecord["DocumentApprovalFile"]))
                     {
                         $File = generateStyledLinkText($DocumentRecord["DocumentApprovalFile"],
                                                        $GLOBALS['CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY'].$DocumentRecord["DocumentApprovalFile"], '',
                                                        $GLOBALS['LANG_VIEW_FILE_TIP'], '_blank');
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $File = generateInputField("fFilename", "file", "", "70", $GLOBALS["LANG_FILENAME_TIP"], "");
                     $File .= generateInputField("MAX_FILE_SIZE", "hidden", "", "", "", $GLOBALS["CONF_UPLOAD_DOCUMENTS_FILES_MAXSIZE"]);
                     $File .= generateInputField("hidDocumentApprovalFile", "hidden", "", "", "", $DocumentRecord["DocumentApprovalFile"]);

                     // Check if there is a linked file
                     if (!empty($DocumentRecord["DocumentApprovalFile"]))
                     {
                         $File .= " ".generateStyledLinkText($DocumentRecord["DocumentApprovalFile"],
                                                             $GLOBALS['CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY'].$DocumentRecord["DocumentApprovalFile"], '',
                                                             $GLOBALS['LANG_VIEW_FILE_TIP'], '_blank');
                     }

                     // Display the allowed file extensions
                     $File .= generateBR(2).generateStyledText($GLOBALS["LANG_ALLOWED_FILES_EXTENSIONS"]." : ".implode(", ", $GLOBALS['CONF_UPLOAD_ALLOWED_EXTENSIONS'])
                              .".", "AllowedExtensions");
                     break;
             }

             // Display the form
             echo "<table id=\"DocumentDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_DOCUMENT_APPROVAL_TYPE"]."</td><td class=\"Value\">$Type</td><td class=\"Label\">".ucfirst($GLOBALS["LANG_DATE"])."</td><td class=\"Value\">".date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'].' '.$GLOBALS['CONF_TIME_DISPLAY_FORMAT'], strtotime($DocumentRecord["DocumentApprovalDate"]))."</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DOCUMENT_APPROVAL_NAME"]."*</td><td class=\"Value\" colspan=\"5\">$Name</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FILENAME"]."*</td><td class=\"Value\" colspan=\"5\">$File</td>\n</tr>\n";

             if ($DocumentApprovalID > 0)
             {
                 // Display families' approvals about the document
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"6\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DOCUMENT_APPROVAL_VALIDATIONS"]."</td><td class=\"Value\" colspan=\"5\">";
                 echo "<table>\n<tr>\n\t<td>";
                 if (($bDisplayApprovals) && (isset($TabFamiliesApprovalsData))
                     && (count($TabFamiliesApprovalsData) > 0))
                 {
                     displayStyledTable($TabFamiliesApprovalsCaptions, array_fill(0, count($TabFamiliesApprovalsCaptions), ''), '',
                                        $TabFamiliesApprovalsData, 'PurposeParticipantsTable', '', '', '', array(), 0, array(0 => 'textLeft', 1 => 'textLeft'));
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }
                 echo $FamiliesApprovals;

                 echo "</td></tr>\n</table>";

                 if ((isset($ArrayFamiliesApprovals["DocumentFamilyApprovalID"]))
                     && (count($ArrayFamiliesApprovals["DocumentFamilyApprovalID"]) > 0))
                 {
                     // Display the number of validations
                     echo $GLOBALS['LANG_NB_VALIDATIONS'].' : '.count($ArrayFamiliesApprovals["DocumentFamilyApprovalID"]);
                 }

                 echo "</td>\n</tr>\n";
             }

             echo "</table>\n";

             insertInputField("hidDocumentApprovalID", "hidden", "", "", "", $DocumentApprovalID);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                 case FCT_ACT_PARTIAL_READ_ONLY:
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
             // The supporter isn't allowed to create or update a document approval
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
 * Display the form to search a document approval in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-05-09
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some workgroups
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the documents approvals found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the documents approvals. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about a document approval. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update documents approvals
 * @param $$ArrayDiplayedFormFields    Array of Strings     Contains fieldnames of the form to display or not
 */
 function displaySearchDocumentApprovalForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to documents approvals list
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         $bCanDelete = FALSE;          // Check if the supporter can delete a document approval
         $bCheckApproval = FALSE;      // To check if the logged supporter has approved the document

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
             $bCheckApproval = TRUE;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormSearchDocumentApproval", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             if (empty($ArrayDiplayedFormFields))
             {
                 // Display all available fields of the form
                 $ArrayDiplayedFormFields = array(
                                                  "lSchoolYear" => TRUE,
                                                  "sDocumentApprovalName" => TRUE,
                                                  "lDocumentApprovalType" => TRUE
                                                 );
             }

             $SchoolYear = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lSchoolYear'])) && ($ArrayDiplayedFormFields['lSchoolYear']))
             {
                 // <<< School year SELECTFIELD >>>
                 // Create the school years list
                 $ArraySchoolYear = array(0 => '');
                 foreach($GLOBALS['CONF_SCHOOL_YEAR_START_DATES'] as $Year => $Date)
                 {
                     $Value = date('Y', strtotime($Date)).'-'.$Year;
                     $ArraySchoolYear[$Year] = $Value;
                 }

                 if ((isset($TabParams['SchoolYear'])) && (count($TabParams['SchoolYear']) > 0))
                 {
                     $SelectedItem = $TabParams['SchoolYear'][0];
                 }
                 else
                 {
                     // Default value : no item selected
                     $SelectedItem = 0;
                 }

                 $SchoolYear = generateSelectField("lSchoolYear", array_keys($ArraySchoolYear), array_values($ArraySchoolYear),
                                                   zeroFormatValue(existedPOSTFieldValue("lSchoolYear",
                                                                                         existedGETFieldValue("lSchoolYear", $SelectedItem))));
             }

             $sDocumentApprovalName = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sDocumentApprovalName'])) && ($ArrayDiplayedFormFields['sDocumentApprovalName']))
             {
                 // Document approval name input text
                 $sDocumentApprovalName = generateInputField("sDocumentApprovalName", "text", "255", "25", $GLOBALS["LANG_DOCUMENT_APPROVAL_NAME_TIP"],
                                                             stripslashes(strip_tags(existedPOSTFieldValue("sDocumentApprovalName",
                                                                                                           stripslashes(existedGETFieldValue("sDocumentApprovalName", ""))))));
             }

             $DocumentApprovalType = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lDocumentApprovalType'])) && ($ArrayDiplayedFormFields['lDocumentApprovalType']))
             {
                 $ArrayDocumentApprovalTypesID = array_merge(array(-1), array_keys($GLOBALS["CONF_DOCUMENTS_APPROVALS_TYPES"]));
                 $ArrayDocumentApprovalTypes = array_merge(array(""), array_values($GLOBALS["CONF_DOCUMENTS_APPROVALS_TYPES"]));

                 if ((isset($TabParams['DocumentApprovalType'])) && (count($TabParams['DocumentApprovalType']) > 0))
                 {
                     $SelectedItem = $TabParams['DocumentApprovalType'][0];
                 }
                 else
                 {
                     // Default value : no item selected
                     $SelectedItem = -1;
                 }

                 $DocumentApprovalType = generateSelectField("lDocumentApprovalType", $ArrayDocumentApprovalTypesID, $ArrayDocumentApprovalTypes,
                                                             zeroFormatValue(existedPOSTFieldValue("lDocumentApprovalType",
                                                                                                   existedGETFieldValue("lDocumentApprovalType",
                                                                                                                        $SelectedItem))));
             }

             // Display the form
             echo "<table id=\"DocumentsApprovalsList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DOCUMENT_APPROVAL_TYPE"]."</td><td class=\"Value\">$DocumentApprovalType</td><td class=\"Label\">".$GLOBALS["LANG_SCHOOL_YEAR"]."</td><td class=\"Value\">$SchoolYear</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_DOCUMENT_APPROVAL_NAME']."</td><td class=\"Value\">$sDocumentApprovalName</td><td class=\"Label\">&nbsp;</td><td class=\"Value\">&nbsp;</td>\n</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_DOCUMENT_APPROVAL_NAME"], $GLOBALS["LANG_DOCUMENT_APPROVAL_TYPE"], ucfirst($GLOBALS["LANG_DATE"]),
                                        $GLOBALS["LANG_NB_VALIDATIONS"]);
                 $ArraySorts = array("DocumentApprovalName", "DocumentApprovalType", "DocumentApprovalDate", "NbApprovals");

                 if ($bCanDelete)
                 {
                     // The supporter can delete documents approvals : we add a column for this action
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
                     $StrOrderBy = "DocumentApprovalName ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchDocumentApproval($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only documents approvals of the page
                     $ArrayRecords = dbSearchDocumentApproval($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some documents approvals found
                     foreach($ArrayRecords["DocumentApprovalID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the document approval name
                             $ArrayData[0][] = $ArrayRecords["DocumentApprovalName"][$i];
                         }
                         else
                         {
                             // We display the document approval with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["DocumentApprovalName"][$i], $ArrayRecords["DocumentApprovalID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = $GLOBALS['CONF_DOCUMENTS_APPROVALS_TYPES'][$ArrayRecords["DocumentApprovalType"][$i]];
                         $ArrayData[2][] = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($ArrayRecords["DocumentApprovalDate"][$i]));

                         $sNbApprovals = $ArrayRecords["NbApprovals"][$i];

                         if ($bCheckApproval)
                         {
                             // Get documents approved by the loggued user for his family
                             $ArrayFamilyApprovals = getDocumentsApprovalsOfFamily($DbConnection, $_SESSION['FamilyID']);

                             if (isset($ArrayFamilyApprovals['DocumentFamilyApprovalID']))
                             {
                                 if (in_array($CurrentValue, $ArrayFamilyApprovals['DocumentApprovalID']))
                                 {
                                     // The logged user has approved the document
                                     $sNbApprovals .= "&nbsp;".generateStyledPicture($GLOBALS['CONF_DOCUMENT_APPROVED_ICON'], '', '');
                                 }
                             }
                         }

                         $ArrayData[3][] = $sNbApprovals;

                         // Hyperlink to delete the document approval if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteDocumentApproval.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the documents approvals found
                     $ArraySortedFields = array("1", "2", "3", "4");
                     if ($bCanDelete)
                     {
                         $ArraySortedFields[] = "";
                     }

                     displayStyledTable($ArrayCaptions, $ArraySortedFields, $SortFct, $ArrayData, '', '', '', '',
                                        array(), $OrderBy, array('', '', '', '', ''));

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

                     // Display the legends of the icons
                     if ($bCheckApproval)
                     {
                         displayBR(1);
                         echo generateLegendsOfVisualIndicators(
                                                                array(
                                                                      array($GLOBALS['CONF_DOCUMENT_APPROVED_ICON'], $GLOBALS["LANG_DOCUMENT_FAMILY_APPROVAL_APPROVED"])
                                                                     ),
                                                                ICON
                                                               );
                     }
                 }
                 else
                 {
                     // No document approval found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of documents approvals
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