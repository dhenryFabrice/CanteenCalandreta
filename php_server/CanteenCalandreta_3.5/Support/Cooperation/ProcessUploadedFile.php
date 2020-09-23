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
 * Support module : process the upload of a file and link it to the given object. The supporter must be logged.
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2019-11-20
 */

 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 //################################ FORM PROCESSING ##########################
 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         $ObjectType = trim(strip_tags($_POST["hidObjectType"]));
         $ObjectID = trim(strip_tags($_POST["hidObjectID"]));

         // Check if the object exists
         switch($ObjectType)
         {
             case OBJ_EVENT:
                 $HDDDirectory = $CONF_UPLOAD_EVENTS_FILES_DIRECTORY_HDD;
                 $UrlParameters = $CONF_ROOT_DIRECTORY."Support/Cooperation/UpdateEvent.php?Id=$ObjectID&amp;Cr=".md5($ObjectID);

                 if (!isExistingEvent($DbCon, $ObjectID))
                 {
                     // Error : the event dosne't exist
                     $ContinueProcess = FALSE;
                 }
                 break;

             default:
                 // Error : the object type isn't taken into account
                 $HDDDirectory = '';
                 $UrlParameters = '';
                 $ContinueProcess = FALSE;
                 break;
         }

         // We upload the file
         $UploadedFile = "";
         if ($_FILES["fFilename"]["name"] != "")
         {
             // We give a valide name to the uploaded file
             $_FILES["fFilename"]["name"] = formatFilename($_FILES["fFilename"]["name"]);

             // Check if the file owns an allowed extension
             if (isFileOwnsAllowedExtension($_FILES["fFilename"]["name"], $CONF_UPLOAD_ALLOWED_EXTENSIONS))
             {
                 if (is_uploaded_file($_FILES["fFilename"]["tmp_name"]))
                 {
                     $UploadedFile = $_FILES["fFilename"]["name"];

                     if ($_FILES["fFilename"]["size"] > $CONF_UPLOAD_UPLOADED_FILES_MAXSIZE)
                     {
                         // Error : file to big
                         $ContinueProcess = FALSE;
                     }
                 }
             }
             else
             {
                 // Error : file with a not allowed extension
                 $ContinueProcess = FALSE;
             }
         }

         $FileDescription = trim(strip_tags($_POST["sFileDescription"]));

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             if (!empty($UploadedFile))
             {
                 // We move the uploaded file in the right directory
                 @move_uploaded_file($_FILES["fFilename"]["tmp_name"], $HDDDirectory.$UploadedFile);
             }

             // We can create the new uploaded file
             $UploadedFileID = dbAddUploadedFile($DbCon, $UploadedFile, date('Y-m-d H:i:s'), $ObjectType, $ObjectID, $FileDescription);

             if ($UploadedFileID != 0)
             {
                 // Log event
                 logEvent($DbCon, EVT_UPLOADED_FILE, EVT_SERV_UPLOADED_FILE, EVT_ACT_ADD, $_SESSION['SupportMemberID'], $UploadedFileID);

                 // The uploaded file is added
                 $ConfirmationCaption = $LANG_CONFIRMATION;
                 $ConfirmationSentence = $LANG_CONFIRM_FILE_ADDED;
                 $ConfirmationStyle = "ConfirmationMsg";
             }
             else
             {
                 // The uploaded file can't be added
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_ADD_FILE;
                 $ConfirmationStyle = "ErrorMsg";
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($DocumentApprovalFile))
             {
                 // The filename is empty
                 $ConfirmationSentence = $LANG_ERROR_FILENAME;
             }
             else
             {
                 // ERROR : some parameters are empty strings
                 $ConfirmationSentence = $LANG_ERROR_WRONG_FIELDS;
             }

             $ConfirmationStyle = "ErrorMsg";
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
     }
 }
 else
 {
     // The supporter doesn't come from the AddUploadedFile.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
 }

 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../../GUI/Styles/styles.css' => 'screen',
                            '../Styles_Support.css' => 'screen'
                           ),
                      array($CONF_ROOT_DIRECTORY."Common/JSRedirection/Redirection.js"),
                      'WhitePage',
                      "Redirection('$UrlParameters', $CONF_TIME_LAG)"
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