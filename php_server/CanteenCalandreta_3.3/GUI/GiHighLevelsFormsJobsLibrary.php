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
 * Interface module : XHTML Graphic high level forms library used to manage the jobs.
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2017-09-22
 */


/**
 * Display the form to update a job, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-09-22
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $JobID                    String                ID of the job to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to update or view jobs
 */
 function displayDetailsJobForm($DbConnection, $JobID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to update a job
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($JobID))
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
             openForm("FormDetailsJob", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationJob()");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_JOB"], "Frame", "Frame", "DetailsNews");

             // <<< Job ID >>>
             if ($JobID == 0)
             {
                 // Define default values to create the new job
                 $Reference = "&nbsp;";
                 $JobRecord = array(
                                      "JobPlannedDate" => date('Y-m-d H:i:s', strtotime("+1 hour")),
                                      "JobExecutionDate" => '',
                                      "JobType" => 0,
                                      "JobNbTries" => 0,
                                      "JobResult" => '',
                                      "SupportMemberID" => $_SESSION["SupportMemberID"]
                                     );
             }
             else
             {
                 if (isExistingJob($DbConnection, $JobID))
                 {
                     // We get the details of the alias
                     $JobRecord = getTableRecordInfos($DbConnection, "Jobs", $JobID);
                     $Reference = $JobID;
                 }
                 else
                 {
                     // Error, the job doesn't exist
                     $JobID = 0;
                     $Reference = "&nbsp;";
                 }
             }

             // Check if the job is done
             $bWellExecuted = FALSE;
             if ((!empty($JobRecord['JobExecutionDate'])) && (!empty($JobRecord['JobResult'])))
             {
                 // Execution date and result are set
                 $bWellExecuted = TRUE;
             }

             // We get infos about the author of the job
             $ArrayInfosSupporter = getSupportMemberInfos($DbConnection, $JobRecord["SupportMemberID"]);
             $Author = $ArrayInfosSupporter["SupportMemberLastname"].' '.$ArrayInfosSupporter["SupportMemberFirstname"]
                       .' ('.getSupportMemberStateName($DbConnection, $ArrayInfosSupporter["SupportMemberStateID"]).')';
             $Author .= generateInputField("hidSupportMemberID", "hidden", "", "", "", $JobRecord["SupportMemberID"]);

             // Type of job
             $Type = "&nbsp;";
             switch($JobRecord["JobType"])
             {
                 case JOB_EMAIL:
                     $Type = "JOB_EMAIL";
                     break;
             }

             // <<< Planned date INPUTFIELD >>>
             if ($bWellExecuted)
             {
                 // Job executed
                 $PlannedDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                     strtotime($JobRecord["JobPlannedDate"]));
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $PlannedDateValue = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($JobRecord["JobPlannedDate"]));
                         $PlannedTimeValue = date($GLOBALS["CONF_TIME_DISPLAY_FORMAT"], strtotime($JobRecord["JobPlannedDate"]));
                         $PlannedDate = generateInputField("startDate", "text", "10", "10", $GLOBALS["LANG_JOB_PLANNED_DATE_TIP"],
                                                           $PlannedDateValue);
                         $PlannedDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallbackStartDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";

                         $PlannedDate .= generateBR(1).generateInputField("sPlannedDateTime", "text", "8", "8", $GLOBALS["LANG_JOB_NB_TRIES_TIP"],
                                                                          $PlannedTimeValue);
                         break;

                     default:
                         $PlannedDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                             strtotime($JobRecord["JobPlannedDate"]));
                         break;
                 }
             }

             // Execution date
             $ExecutionDate = '';
             if (!empty($JobRecord["JobExecutionDate"]))
             {
                 $ExecutionDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                       strtotime($JobRecord["JobExecutionDate"]));
             }

             // <<< NbTries INPUTFIELD >>>
             if ($bWellExecuted)
             {
                 // Job executed
                 $NbTries = $JobRecord["JobNbTries"];
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $NbTries = generateInputField("sNbTries", "text", "4", "4", $GLOBALS["LANG_JOB_NB_TRIES_TIP"],
                                                       $JobRecord["JobNbTries"]);
                         break;

                     default:
                         $NbTries = $JobRecord["JobNbTries"];
                         break;
                 }
             }

             // <<< job result INPUTFIELD >>>
             if ($bWellExecuted)
             {
                 // Job executed
                 $JobResult = stripslashes($JobRecord["JobResult"]);
             }
             else
             {
                 switch($cUserAccess)
                 {
                     case FCT_ACT_CREATE:
                     case FCT_ACT_UPDATE:
                         $JobResult = generateInputField("sJobResult", "text", "255", "50", $GLOBALS["LANG_JOB_RESULT"],
                                                         $JobRecord["JobResult"]);
                         break;

                     default:
                         $JobResult = stripslashes($JobRecord["JobResult"]);
                         break;
                 }
             }

             // Display the form
             echo "<table id=\"JobDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_SUPPORTER"]."</td><td class=\"Value\">$Author</td><td class=\"Label\">".$GLOBALS["LANG_JOB_TYPE"]."</td><td class=\"Value\">$Type</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_JOB_PLANNED_DATE"]."</td><td class=\"Value\">$PlannedDate</td><td class=\"Label\">".$GLOBALS["LANG_JOB_EXECUTION_DATE"]."</td><td class=\"Value\">$ExecutionDate</td><td class=\"Label\">".$GLOBALS["LANG_JOB_NB_TRIES"]."</td><td class=\"Value\">$NbTries</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_JOB_RESULT"]."</td><td class=\"Value\" colspan=\"5\">$JobResult</td>\n</tr>\n";

             if ($JobID > 0)
             {
                 // Display parameters of the job
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"6\">&nbsp;</td>\n</tr>\n";

                 // First, we get parameters and values
                 $ArrayJobParameters = dbSearchJobs($DbConnection, array("JobID" => $JobID), "JobParameterID", 1, 0);
                 if ((isset($ArrayJobParameters["JobID"])) && (count($ArrayJobParameters["JobID"]) > 0))
                 {
                     foreach($ArrayJobParameters["JobParameters"][0] as $j => $JobParams)
                     {
                         $ParamName = $JobParams["JobParameterName"];

                         switch($JobRecord["JobType"])
                         {
                             case JOB_EMAIL:
                                 $ParamValue = $JobParams["JobParameterValue"];

                                 switch(strToLower($ParamName))
                                 {
                                     case 'mailinglist':
                                         $ParamValue = unserialize(base64_decode($ParamValue));

                                         switch($cUserAccess)
                                         {
                                             case FCT_ACT_CREATE:
                                             case FCT_ACT_UPDATE:
                                                 // Display e-mail addresses
                                                 $sTmp = '<dl>';
                                                 foreach($ParamValue as $Key => $KeyValues)
                                                 {
                                                     $sTmp .= "<dt>$Key</dt>";
                                                     $sTmp .= "<dd>".implode('<br />', $KeyValues)."</dd>";
                                                 }

                                                 $ParamValue = "$sTmp</dl>\n";
                                                 break;

                                             default:
                                                 // Just display nb of e-mail addresses
                                                 $sTmp = '<ul>';
                                                 foreach($ParamValue as $Key => $KeyValues)
                                                 {
                                                     $sTmp .= "<li>$Key : ".count($KeyValues)."</li>\n";
                                                 }

                                                 $ParamValue = "$sTmp</ul>\n";
                                                 break;
                                         }
                                         break;

                                     case 'replace-in-template':
                                         $ParamValue = unserialize(base64_decode($ParamValue));
                                         $sTmp = '<dl>';
                                         foreach($ParamValue as $Key => $KeyValues)
                                         {
                                             $sTmp .= "<dt>$Key</dt>";
                                             $sTmp .= "<dd>".implode('<br />', $KeyValues)."</dd>";
                                         }

                                         $ParamValue = "$sTmp</dl>\n";
                                         break;
                                 }
                                 break;
                         }

                         echo "<tr>\n\t<td class=\"Label\">$ParamName</td><td class=\"Value JobParameterValue\" colspan=\"5\">$ParamValue</td>\n</tr>\n";
                     }
                 }
             }

             echo "</table>\n";

             insertInputField("hidJobID", "hidden", "", "", "", $JobID);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if (!$bWellExecuted)
                     {
                         // We display the buttons
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                         echo "</td>\n</tr>\n</table>\n";
                     }
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a job
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
 * Display the form to search a job in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2017-09-22
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some jobs
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the jobs found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the jobs. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about a job. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update jobs
 * @param $$ArrayDiplayedFormFields    Array of Strings     Contains fieldnames of the form to display or not
 */
 function displaySearchJobsForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to jobs list
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         $bCanDelete = FALSE;

         if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_UPDATE;
             $bCanDelete = TRUE;
         }
         elseif ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
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
             openForm("FormSearchJobs", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             if (empty($ArrayDiplayedFormFields))
             {
                 // Display all available fields of the form
                 $ArrayDiplayedFormFields = array(
                                                  "sSupportMemberLastname" => TRUE,
                                                  "sJobType" => TRUE,
                                                  "sJobPlanningDate" => TRUE,
                                                  "sJobResult" => TRUE
                                                 );
             }

             $sSupportMemberLastname = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sSupportMemberLastname'])) && ($ArrayDiplayedFormFields['sSupportMemberLastname']))
             {
                 // Support member lastname input text
                 $sSupportMemberLastname = generateInputField("sSupportMemberLastname", "text", "50", "25", $GLOBALS["LANG_LASTNAME_TIP"],
                                                               stripslashes(strip_tags(existedPOSTFieldValue("sSupportMemberLastname",
                                                                                                             stripslashes(existedGETFieldValue("sSupportMemberLastname", ""))))));
             }

             // List of job types
             $sJobType = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sJobType'])) && ($ArrayDiplayedFormFields['sJobType']))
             {
                 $ArrayJobTypes = array(0, JOB_EMAIL);
                 $ArrayJobTypeNames = array('-', 'JOB_EMAIL');

                 if ((isset($TabParams['JobType'])) && (count($TabParams['JobType']) > 0))
                 {
                     $SelectedItem = $TabParams['JobType'][0];
                 }
                 else
                 {
                     $SelectedItem = 0;
                 }

                 $sJobType = generateSelectField("lJobType", $ArrayJobTypes, $ArrayJobTypeNames,
                                                 zeroFormatValue(existedPOSTFieldValue("lJobType",
                                                                                       existedGETFieldValue("lJobType",
                                                                                       $SelectedItem))));
             }

             // Job planning date
             $sJobPlanningDate = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sJobPlanningDate'])) && ($ArrayDiplayedFormFields['sJobPlanningDate']))
             {
                 // <<< Start date INPUTFIELD >>>
                 $iDefaultSelectedValue = zeroFormatValue(existedPOSTFieldValue("lOperatorStartDate",
                                                                                existedGETFieldValue("lOperatorStartDate", 0)));
                 $sDefaultDateValue = stripslashes(strip_tags(existedPOSTFieldValue("startDate",
                                                                                    stripslashes(existedGETFieldValue("startDate", "")))));
                 if ((empty($sDefaultDateValue)) && (isset($TabParams['JobPlannedStartDate'])) && (!empty($TabParams['JobPlannedStartDate'])))
                 {
                     $sDefaultDateValue = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($TabParams['JobPlannedStartDate'][0]));
                     $iDefaultSelectedValue = array_search($TabParams['JobPlannedStartDate'][1], $GLOBALS["CONF_LOGICAL_OPERATORS"]);
                 }

                 $sStartDate = generateSelectField("lOperatorStartDate", array_keys($GLOBALS["CONF_LOGICAL_OPERATORS"]),
                                                   $GLOBALS["CONF_LOGICAL_OPERATORS"], $iDefaultSelectedValue, "");
                 $sStartDate .= "&nbsp;".generateInputField("startDate", "text", "10", "10", $GLOBALS["LANG_START_DATE_TIP"],
                                                            $sDefaultDateValue);
                 $sStartDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t StartDateCalendar = new dynCalendar('StartDateCalendar', 'calendarCallbackStartDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";

                 // <<< End date INPUTFIELD >>>
                 $iDefaultSelectedValue = zeroFormatValue(existedPOSTFieldValue("lOperatorEndDate",
                                                                                existedGETFieldValue("lOperatorEndDate", 0)));
                 $sDefaultDateValue = stripslashes(strip_tags(existedPOSTFieldValue("endDate",
                                                                                    stripslashes(existedGETFieldValue("endDate", "")))));
                 if ((empty($sDefaultDateValue)) && (isset($TabParams['JobPlannedEndDate'])) && (!empty($TabParams['JobPlannedEndDate'])))
                 {
                     $sDefaultDateValue = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($TabParams['HolidayEndDate'][0]));
                     $iDefaultSelectedValue = array_search($TabParams['JobPlannedEndDate'][1], $GLOBALS["CONF_LOGICAL_OPERATORS"]);
                 }

                 $sEndDate = generateSelectField("lOperatorEndDate", array_keys($GLOBALS["CONF_LOGICAL_OPERATORS"]),
                                                 $GLOBALS["CONF_LOGICAL_OPERATORS"], $iDefaultSelectedValue, "");
                 $sEndDate .= "&nbsp;".generateInputField("endDate", "text", "10", "10", $GLOBALS["LANG_END_DATE_TIP"],
                                                          $sDefaultDateValue);
                 $sEndDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t EndDateCalendar = new dynCalendar('EndDateCalendar', 'calendarCallbackEndDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";

                 $sJobPlanningDate = "$sStartDate<br />\n$sEndDate";
             }

             // Job result
             $sJobResult = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sJobResult'])) && ($ArrayDiplayedFormFields['sJobResult']))
             {
                 $ArrayResults = array(-1, 0, 1);
                 $ArrayResultNames = array('-', $GLOBALS["LANG_NO"], $GLOBALS["LANG_YES"]);

                 if ((isset($TabParams['JobResult'])) && (count($TabParams['JobResult']) > 0))
                 {
                     $SelectedItem = $TabParams['JobResult'][0];
                 }
                 else
                 {
                     $SelectedItem = -1;
                 }

                 $sJobResult = generateSelectField("lJobResult", $ArrayResults, $ArrayResultNames,
                                                   zeroFormatValue(existedPOSTFieldValue("lJobResult",
                                                                                         existedGETFieldValue("lJobResult",
                                                                                         $SelectedItem))));
             }

             // Display the form
             echo "<table id=\"JobsList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_SUPPORTER"]."</td><td class=\"Value\">$sSupportMemberLastname</td><td class=\"Label\">".$GLOBALS['LANG_JOB_TYPE']."</td><td class=\"Value\">$sJobType</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_JOB_PLANNED_DATE']."</td><td class=\"Value\">$sJobPlanningDate</td><td class=\"Label\">".$GLOBALS['LANG_JOB_RESULT']."</td><td class=\"Value\">$sJobResult</td>\n</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_JOB"], $GLOBALS["LANG_JOB_TYPE"], $GLOBALS["LANG_JOB_PLANNED_DATE"],
                                        $GLOBALS["LANG_JOB_EXECUTION_DATE"], $GLOBALS["LANG_JOB_NB_TRIES"],
                                        $GLOBALS["LANG_JOB_RESULT"], $GLOBALS["LANG_SUPPORTER"]);
                 $ArraySorts = array("JobID", "JobType", "JobPlannedDate", "JobExecutionDate", "JobNbTries", "JobResult",
                                     "SupportMemberLastname");

                 if ($bCanDelete)
                 {
                     // The supporter can delete jobs : we add a column for this action
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
                     $StrOrderBy = "JobPlannedDate ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchJobs($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only jobs of the page
                     $ArrayRecords = dbSearchJobs($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some jobs found
                     foreach($ArrayRecords["JobID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the  job ID
                             $ArrayData[0][] = $CurrentValue;
                         }
                         else
                         {
                             // We display the job ID with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($CurrentValue, $CurrentValue, $DetailsPage,
                                                                      $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], "", "_blank");
                         }

                         switch($ArrayRecords["JobType"][$i])
                         {
                             case JOB_EMAIL:
                                 $ArrayData[1][] = 'JOB_EMAIL';
                                 break;

                             default:
                                 $ArrayData[1][] = '-';
                                 break;
                         }

                         $PlannedDate = '&nbsp;';
                         if (!empty($ArrayRecords["JobPlannedDate"][$i]))
                         {
                             $PlannedDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'].' '.$GLOBALS['CONF_TIME_DISPLAY_FORMAT'],
                                                 strtotime($ArrayRecords["JobPlannedDate"][$i]));
                         }

                         $ArrayData[2][] = $PlannedDate;

                         $ExecutionDate = '&nbsp;';
                         if (!empty($ArrayRecords["JobExecutionDate"][$i]))
                         {
                             $ExecutionDate = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'].' '.$GLOBALS['CONF_TIME_DISPLAY_FORMAT'],
                                                   strtotime($ArrayRecords["JobExecutionDate"][$i]));
                         }

                         $ArrayData[3][] = $ExecutionDate;
                         $ArrayData[4][] = $ArrayRecords["JobNbTries"][$i];
                         $JobResult = $GLOBALS['LANG_NO'];
                         if ($ArrayRecords["JobResult"][$i] == 1)
                         {
                             $JobResult = $GLOBALS['LANG_YES'];
                         }

                         $ArrayData[5][] = $JobResult;
                         $ArrayData[6][] = $ArrayRecords["SupportMemberLastname"][$i].' '.$ArrayRecords["SupportMemberFirstname"][$i];

                         // Hyperlink to delete the job if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[7][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteJob.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the jobs found
                     $ArraySortedFields = array("1", "2", "3", "4", "5", "6", "7");
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
                     // No job found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of jobs
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