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
 * Support module : process the update of a job. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2016-03-07
 */

 // Include the graphic primitives library
  require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 // To take into account the crypted and no-crypted job ID
 // Crypted ID
 if (!empty($_GET["Cr"]))
 {
     $CryptedID = (string)strip_tags($_GET["Cr"]);
 }
 else
 {
     $CryptedID = "";
 }

 // No-crypted ID
 if (!empty($_GET["Id"]))
 {
     $Id = (string)strip_tags($_GET["Id"]);
 }
 else
 {
     $Id = "";
 }

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         // We identify the job
         if (isExistingJob($DbCon, $Id))
         {
             // The job exists
             $JobID = $Id;
         }
         else
         {
             // ERROR : the job doesn't exist
             $ContinueProcess = FALSE;
         }

         $SupportMemberID = NULL;
         $JobType = NULL;
         $JobExecutionDate = NULL;

         // We have to convert the planned date in english format (format used in the database)
         $PlannedDateValue = existedPOSTFieldValue("startDate", NULL);
         if (!is_Null($PlannedDateValue))
         {
             $PlannedDateValue = nullFormatText(formatedDate2EngDate($_POST["startDate"]), "NULL");
             if (empty($PlannedDateValue))
             {
                 // Error : planned date must be set
                 $ContinueProcess = FALSE;
             }
         }

         // We get the time of the planned date too
         $PlannedDateTimeValue = existedPOSTFieldValue("sPlannedDateTime", NULL);
         if (!is_Null($PlannedDateTimeValue))
         {
             $PlannedDateTimeValue = trim(strip_tags($_POST["sPlannedDateTime"]));
             if (empty($PlannedDateTimeValue))
             {
                 $ArrayPlannedTime = date('H:i:s', strtotime("+1 hour"));
             }
             else
             {
                 // To have hh:mm:ss
                 $ArrayPlannedTime = explode(':', $PlannedDateTimeValue);
                 $PlannedDateTimeValue = implode(':', $ArrayPlannedTime).str_repeat(":00", 3 - count($ArrayPlannedTime));
                 unset($ArrayPlannedTime);
             }
         }

         $JobPlannedDate = $PlannedDateValue.' '.$PlannedDateTimeValue;

         // We get the number of tries of the job
         $JobNbTries = existedPOSTFieldValue("sNbTries", NULL);
         if (!is_Null($JobNbTries))
         {
             $JobNbTries = nullFormatText(strip_tags($_POST["sNbTries"]), "NULL");
             if (is_Null($JobNbTries))
             {
                 // By default, 1 try
                 $JobNbTries = 1;
             }
             else
             {
                 // The number of tries must be an integer >= 0
                 if ((integer)$JobNbTries < 0)
                 {
                     $ContinueProcess = FALSE;
                 }
             }
         }

         // We get the result of the job
         $JobResult = existedPOSTFieldValue("sJobResult", NULL);
         if (!is_Null($JobResult))
         {
             $JobResult = trim(strip_tags($_POST["sJobResult"]));
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             // We can update the job with the new values
             $JobID = dbUpdateJob($DbCon, $JobID, $SupportMemberID, $JobType, $JobPlannedDate, $JobExecutionDate, $JobNbTries,
                                  $JobResult);
             if ($JobID != 0)
             {
                 // The job is updated
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_RECORD_UPDATED;
                 $ConfirmationStyle = "ConfirmationMsg";
                 $UrlParameters = "Cr=".md5($JobID)."&Id=$JobID"; // For the redirection
             }
             else
             {
                 // The job can't be updated
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_UPDATE_RECORD;
                 $ConfirmationStyle = "ErrorMsg";
                 $UrlParameters = $QUERY_STRING; // For the redirection
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             // ERROR : some parameters are empty strings
             $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;

             $ConfirmationStyle = "ErrorMsg";
             $UrlParameters = $QUERY_STRING; // For the redirection
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
     else
     {
         // ERROR : the supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";
         $UrlParameters = $QUERY_STRING; // For the redirection
     }
 }
 else
 {
     // The supporter doesn't come from the UpdateJob.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
     $UrlParameters = $QUERY_STRING; // For the redirection
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            'Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('".$CONF_ROOT_DIRECTORY."Support/UpdateJob.php?$UrlParameters', $CONF_TIME_LAG)"
                     );

 // Content of the web page
 openArea('id="content"');

 openFrame($ConfirmationCaption);
 displayStyledText($ConfirmationSentence, $ConfirmationStyle);
 closeFrame();

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

 closeGraphicInterface();
?>