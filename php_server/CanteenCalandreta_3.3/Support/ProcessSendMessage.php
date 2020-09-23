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
 * Support module : process to send a message by a supporter
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2016-09-09 : patch a bug about To and CC with jobs and taken into account Bcc and
 *                    author in copy checkbox and load some configuration variables from database
 *     - 2017-10-02 : taken into account ChildEmail, FamilyMainEmailContactAllowed and
 *                    FamilySecondEmailContactAllowed fields when e-mail is send to a previous
 *                    school year and patch a bug to get right families for last grade of a given
 *                    school year, taken into account new oldfamilies alias, taken into account
 *                    towns to contact families
 *     - 2019-01-21 : taken into account internal alias "*@committee" to contact members of the committee
 *
 * @since 2016-03-04
 */

 // Include the graphic primitives library
 require '../GUI/GraphicInterface.php';

 // To measure the execution script time
 initStartTime();

 // Create "supporter" session or use the opened "supporter" session
 session_start();

 // Redirect the user to the login page index.php if he isn't loggued
 setRedirectionToLoginPage();

 //################################ FORM PROCESSING ##########################
 $bIsEmailSent = FALSE;

 if (!empty($_POST["bSubmit"]))
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         $SupportMemberID = $_SESSION["SupportMemberID"];

         // Connection to the database
         $DbCon = dbConnection();

         // Load all configuration variables from database
         loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                              'CONF_CLASSROOMS',
                                              'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                              'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                              'CONF_CANTEEN_PRICES',
                                              'CONF_NURSERY_PRICES',
                                              'CONF_NURSERY_DELAYS_PRICES'));

         $ContinueProcess = TRUE; // used to check that the parameters are correct

         $sAuthor = trim(strip_tags($_POST["sAuthor"]));
         if (empty($sAuthor))
         {
             // Error : author of the message is missing
             $ContinueProcess = FALSE;
         }

         $sRecipients = trim(strip_tags($_POST["hidMessageRecipients"]));
         if (empty($sRecipients))
         {
             // Error : no recipients for the message
             $ContinueProcess = FALSE;
         }

         $sSubject = trim(strip_tags($_POST["sSubject"]));
         if (empty($sSubject))
         {
             // Error : subject of the message is missing
             $ContinueProcess = FALSE;
         }

         $sMessage = formatText($_POST["sMessage"]);
         if (empty($sMessage))
         {
             // Error : message is missing
             $ContinueProcess = FALSE;
         }

         $AuthorInCopy = FALSE;
         if ((array_key_exists("chkAuthorInCopy", $_POST)) && (!empty($_POST['chkAuthorInCopy'])))
         {
             // The  author of the message must receive a copy of his message
             $AuthorInCopy = TRUE;
         }

         // We upload the file
         $UploadedFilename = "";
         if ($CONF_UPLOAD_MESSAGE_FILES)
         {
             if ($_FILES["fFilename"]["name"] != "")
             {
                 // We give a valide name to the uploaded file
                 $_FILES["fFilename"]["name"] = formatFilename($_FILES["fFilename"]["name"]);

                 // Check if the file owns an allowed extension
                 if (isFileOwnsAllowedExtension($_FILES["fFilename"]["name"], $CONF_UPLOAD_ALLOWED_EXTENSIONS))
                 {
                     if (is_uploaded_file($_FILES["fFilename"]["tmp_name"]))
                     {
                         $UploadedFilename = $_FILES["fFilename"]["name"];

                         if ($_FILES["fFilename"]["size"] > $CONF_UPLOAD_MESSAGE_FILES_MAXSIZE)
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
         }

         // Verification that the parameters are correct
         if ($ContinueProcess)
         {
             if (!empty($UploadedFilename))
             {
                 // We move the uploaded file in the right directory
                 @move_uploaded_file($_FILES["fFilename"]["tmp_name"], $CONF_UPLOAD_MESSAGE_FILES_DIRECTORY_HDD.$UploadedFilename);
                 $UploadedFile = $CONF_UPLOAD_MESSAGE_FILES_DIRECTORY_HDD.$UploadedFilename;
             }

             // Get e-mail addresses and send the e-mail
             $ArrayRecipients = explode(';', $sRecipients);

             $MailingList = array();
             $ArrayRecipientsList = array();

             $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));

             foreach($ArrayRecipients as $r => $rID)
             {
                 // The first letter is the type of recipient
                 $sRecipientType = strToUpper(substr($rID, 0, 1));
                 $sRecipientID = substr($rID, 1);

                 switch($sRecipientType)
                 {
                     case 'A':
                         // The recipient is an alias
                         $Record = getTableRecordInfos($DbCon, 'Alias', $sRecipientID);
                         if (!empty($Record))
                         {
                             $ArrayRecipientsList[] = $Record['AliasName']." (".$GLOBALS['LANG_ALIAS'].")";

                             $ArrayTmpEmails = explode(',', $Record['AliasMailingList']);
                             foreach($ArrayTmpEmails as $e => $CurrentEmail)
                             {
                                 $CurrentEmail = trim($CurrentEmail);
                                 if (substr($CurrentEmail, 0, 2) == '*@')
                                 {
                                     // Internal alias
                                     $DBTable = substr($CurrentEmail, 2);
                                     switch(strToLower($DBTable))
                                     {
                                         case 'families':
                                             // We get e-mail addresses of all activated families for the current school year
                                             $TabParams = array(
                                                                "SchoolYear" => array(getSchoolYear(date('Y-m-d'))),
                                                                "ActivatedChildren" => TRUE
                                                               );

                                             $ArrayFamilies = dbSearchFamily($DbCon, $TabParams, "FamilyLastname", 1, 0);

                                             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                                             {
                                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                                 {
                                                     $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];

                                                     if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                                     {
                                                         $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                                                     }
                                                 }
                                             }

                                             unset($ArrayFamilies);
                                             break;

                                         case 'committee':
                                             // We get e-mail addresses of all activated families for the current school year having
                                             // and InCommittee attribute set
                                             $TabParams = array(
                                                                "SchoolYear" => array(getSchoolYear(date('Y-m-d'))),
                                                                "FamilyMainEmailInCommittee" => array(1),
                                                                "ActivatedChildren" => TRUE
                                                               );

                                             $ArrayFamilies = dbSearchFamily($DbCon, $TabParams, "FamilyLastname", 1, 0);

                                             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                                             {
                                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                                 {
                                                     $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];
                                                 }
                                             }

                                             unset($ArrayFamilies);

                                             $TabParams = array(
                                                                "SchoolYear" => array(getSchoolYear(date('Y-m-d'))),
                                                                "FamilySecondEmailInCommittee" => array(1),
                                                                "ActivatedChildren" => TRUE
                                                               );

                                             $ArrayFamilies = dbSearchFamily($DbCon, $TabParams, "FamilyLastname", 1, 0);

                                             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                                             {
                                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                                 {
                                                     if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                                     {
                                                         $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                                                     }
                                                 }
                                             }

                                             unset($ArrayFamilies);

                                             $MailingList["bcc"] = array_unique($MailingList["bcc"]);
                                             break;

                                         case 'oldfamilies':
                                             // We get e-mail addresses of all old families and children of past scholl years
                                             // First, we get past school years
                                             $ArrayPastSchoolYears = $CONF_SCHOOL_YEAR_START_DATES;
                                             if (isset($ArrayPastSchoolYears[$CurrentSchoolYear]))
                                             {
                                                 // Delete the current school year
                                                 unset($ArrayPastSchoolYears[$CurrentSchoolYear]);
                                             }

                                             $ArrayPastSchoolYears = array_keys($ArrayPastSchoolYears);

                                             // Next, we get children of last grade for each past school year
                                             $iLastGrade = count($CONF_GRADES) - 1;
                                             $ArrayFamiliesID = array();

                                             foreach($ArrayPastSchoolYears as $psy => $CurrentPastSchoolYear)
                                             {
                                                 $ArrayParams = array(
                                                                      'EndSchoolYear' => array($CurrentPastSchoolYear),
                                                                      'ChildGrade' => array($iLastGrade)
                                                                     );

                                                 $ArrayChildren = dbSearchChild($DbCon, $ArrayParams, "FamilyLastname", 1, 0);

                                                 if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
                                                 {
                                                     // We keep Families ID to get their e-mail addresses
                                                     $ArrayFamiliesID = array_unique($ArrayChildren['FamilyID']);

                                                     $sTmpSchoolYear = (((integer)$CurrentPastSchoolYear) - 1).'-'.$CurrentPastSchoolYear;
                                                     $sTmpChildInfos = "(".$CONF_GRADES[$iLastGrade]." $sTmpSchoolYear)";

                                                     foreach($ArrayChildren['ChildID'] as $ac => $CurrentChildID)
                                                     {
                                                         if (!empty($ArrayChildren['ChildEmail'][$ac]))
                                                         {
                                                             // We can send e-mail to this old child
                                                             $ArrayRecipientsList[] = $ArrayChildren['FamilyLastname'][$ac]
                                                                                      ." ".$ArrayChildren['ChildFirstname'][$ac]
                                                                                      ." $sTmpChildInfos";

                                                             $MailingList["bcc"][] = $ArrayChildren['ChildEmail'][$ac];
                                                         }
                                                     }
                                                 }
                                             }

                                             $ArrayFamiliesID = array_unique($ArrayFamiliesID);
                                             if (!empty($ArrayFamiliesID))
                                             {
                                                 // We found families of children for this grade : we get e-mails of these families
                                                 $ArrayParams = array(
                                                                      'FamilyID' => array_unique($ArrayFamiliesID)
                                                                     );

                                                 $ArrayFamilies = dbSearchFamily($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                                                 if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                                                 {
                                                     foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                                     {
                                                         $bFamilyAdded = FALSE;

                                                         if ($ArrayFamilies['FamilyMainEmailContactAllowed'][$f] == 1)
                                                         {
                                                             // The main e-mail can be contacted
                                                             $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];

                                                             $ArrayRecipientsList[] = $ArrayFamilies['FamilyLastname'][$f]
                                                                                      .' ('.$LANG_FAMILY.')';
                                                             $bFamilyAdded = TRUE;
                                                         }

                                                         if ((!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                                             && ($ArrayFamilies['FamilySecondEmailContactAllowed'][$f] == 1))
                                                         {
                                                             // The second e-mail can be contacted and is set
                                                             $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];

                                                             if (!$bFamilyAdded)
                                                             {
                                                                 $ArrayRecipientsList[] = $ArrayFamilies['FamilyLastname'][$f]
                                                                                          .' ('.$LANG_FAMILY.')';
                                                             }
                                                         }
                                                     }
                                                 }
                                             }

                                             unset($ArrayFamiliesID);
                                             break;
                                     }
                                 }
                                 else
                                 {
                                     // True e-mail address
                                     $MailingList["bcc"][] = $CurrentEmail;
                                 }
                             }

                             unset($ArrayTmpEmails);
                         }
                         break;

                     case 'C':
                         // The recipient is a classroom (of children)
                         // We have to get e-mails of families with activated children for this classroom
                         $ArrayParams = array(
                                              'Activated' => TRUE,
                                              'SchoolYear' => array($CurrentSchoolYear),
                                              'ChildClass' => array($sRecipientID)
                                             );

                         $ArrayChildren = dbSearchChild($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                         if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
                         {
                             // We found families of children for this classroom : we get e-mails of these families
                             $ArrayParams = array(
                                                  'SchoolYear' => array($CurrentSchoolYear),
                                                  'FamilyID' => array_unique($ArrayChildren['FamilyID'])
                                                 );

                             $ArrayFamilies = dbSearchFamily($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                             {
                                 // Recipient is the classroom of children
                                 $ArrayRecipientsList[] = $CONF_CLASSROOMS[$CurrentSchoolYear][$sRecipientID]." ($LANG_CHILD_CLASS)";

                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                 {
                                     $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];

                                     if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                     {
                                         // The second e-mail is set
                                         $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                                     }
                                 }
                             }

                             unset($ArrayFamilies);
                         }

                         unset($ArrayChildren);
                         break;

                     case 'F':
                         // The recipient is a family
                         $Record = getTableRecordInfos($DbCon, 'Families', $sRecipientID);
                         if (!empty($Record))
                         {
                             $ArrayRecipientsList[] = $Record['FamilyLastname'];

                             $MailingList["bcc"][] = $Record['FamilyMainEmail'];
                             if (!empty($Record['FamilySecondEmail']))
                             {
                                 $MailingList["bcc"][] = $Record['FamilySecondEmail'];
                             }
                         }
                         break;

                     case 'G':
                         // The recipient is a grade (of children)
                         // We have to get e-mails of families with activated children for this grade
                         $ArrayParams = array(
                                              'Activated' => TRUE,
                                              'SchoolYear' => array($CurrentSchoolYear),
                                              'ChildGrade' => array($sRecipientID)
                                             );

                         $ArrayChildren = dbSearchChild($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                         if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
                         {
                             // We found families of children for this grade : we get e-mails of these families
                             $ArrayParams = array(
                                                  'SchoolYear' => array($CurrentSchoolYear),
                                                  'FamilyID' => array_unique($ArrayChildren['FamilyID'])
                                                 );

                             $ArrayFamilies = dbSearchFamily($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                             {
                                 // Recipient is the grade of children
                                 $ArrayRecipientsList[] = $CONF_GRADES[$sRecipientID]." ($LANG_CHILD_GRADE)";

                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                 {
                                     $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];

                                     if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                     {
                                         $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                                     }
                                 }
                             }

                             unset($ArrayFamilies);
                         }

                         unset($ArrayChildren);
                         break;

                     case 'R':
                         // The recipient is a workgroup registration
                         $Record = getTableRecordInfos($DbCon, 'WorkGroupRegistrations', $sRecipientID);
                         if (!empty($Record))
                         {
                             $ArrayRecipientsList[] = $Record['WorkGroupRegistrationLastname']." "
                                                      .$Record['WorkGroupRegistrationFirstname']." (".$GLOBALS['LANG_WORKGROUP'].")";

                             $MailingList["bcc"][] = $Record['WorkGroupRegistrationEmail'];
                         }
                         break;

                     case 'S':
                         // The recipient is a supporter
                         $Record = getSupportMemberInfos($DbCon, $sRecipientID);
                         if (!empty($Record))
                         {
                             $sSupporter = $Record['SupportMemberLastname'];
                             if (strlen($Record['SupportMemberFirstname']) >= 2)
                             {
                                 $sSupporter .= " ".$Record['SupportMemberFirstname'];
                             }

                             $ArrayRecipientsList[] = "$sSupporter (".getSupportMemberStateName($DbCon, $Record['SupportMemberStateID'])
                                                      .")";

                             $MailingList["bcc"][] = $Record['SupportMemberEmail'];
                         }
                         break;

                     case 'T':
                         // The recipients are families of a given town
                         $ArrayParams = array(
                                              'TownID' => array($sRecipientID),
                                              'SchoolYear' => array($CurrentSchoolYear),
                                              'ActivatedChildren' => TRUE
                                             );

                         $ArrayFamilies = dbSearchFamily($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                         if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                         {
                             // We get the town name
                             $Record = getTableRecordInfos($DbCon, 'Towns', $sRecipientID);
                             if (!empty($Record))
                             {
                                 $ArrayRecipientsList[] = $Record['TownName'].' ('.$LANG_TOWN.')';
                             }

                             unset($Record);

                             foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                             {
                                 // Main e-mail
                                 $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];
                                 $ArrayRecipientsList[] = $ArrayFamilies['FamilyLastname'][$f].' ('.$LANG_FAMILY.')';

                                 if (!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                 {
                                     // The second e-mail is set
                                     $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                                 }
                             }
                         }

                         unset($ArrayFamilies);
                         break;

                     case 'W':
                         // The recipient is a workgroup
                         $Record = getTableRecordInfos($DbCon, 'WorkGroups', $sRecipientID);
                         if (!empty($Record))
                         {
                             $ArrayRecipientsList[] = $Record['WorkGroupName']." (".$GLOBALS['LANG_WORKGROUP'].")";

                             $MailingList["bcc"][] = $Record['WorkGroupEmail'];
                         }
                         break;

                     case 'Y':
                         // The recipients are children of the last grade of a school year
                         // We have to get e-mails of families with activated children for this grade and school year
                         $iLastGrade = count($CONF_GRADES) - 1;
                         $ArrayParams = array(
                                              'EndSchoolYear' => array($sRecipientID),
                                              'ChildGrade' => array($iLastGrade)
                                             );

                         $ArrayChildren = dbSearchChild($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                         if ((isset($ArrayChildren['ChildID'])) && (!empty($ArrayChildren['ChildID'])))
                         {
                             // We found families of children for this grade : we get e-mails of these families
                             $ArrayParams = array(
                                                  'SchoolYear' => array($sRecipientID),
                                                  'FamilyID' => array_unique($ArrayChildren['FamilyID'])
                                                 );

                             $ArrayFamilies = dbSearchFamily($DbCon, $ArrayParams, "FamilyLastname", 1, 0);
                             if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                             {
                                 // Recipient is the grade of children
                                 $sTmpSchoolYear = (((integer)$sRecipientID) - 1).'-'.$sRecipientID;
                                 $ArrayRecipientsList[] = $CONF_GRADES[$iLastGrade]." $sTmpSchoolYear ($LANG_SCHOOL_YEAR)";

                                 foreach($ArrayFamilies['FamilyID'] as $f => $CurrentFamilyID)
                                 {
                                     if ($ArrayFamilies['FamilyMainEmailContactAllowed'][$f] == 1)
                                     {
                                         // The main e-mail can be contacted
                                         $MailingList["bcc"][] = $ArrayFamilies['FamilyMainEmail'][$f];
                                     }

                                     if ((!empty($ArrayFamilies['FamilySecondEmail'][$f]))
                                         && ($ArrayFamilies['FamilySecondEmailContactAllowed'][$f] == 1))
                                     {
                                         // The second e-mail can be contacted and is set
                                         $MailingList["bcc"][] = $ArrayFamilies['FamilySecondEmail'][$f];
                                     }
                                 }
                             }

                             unset($ArrayFamilies);
                         }

                         unset($ArrayChildren);
                         break;
                 }
             }

             if ($AuthorInCopy)
             {
                 // The message must be sent to the author of the message :
                 // we get his e-mail address
                 $MailingList["bcc"][] = $_SESSION['SupportMemberEmail'];
             }

             // Keep unique e-mails and recipients
             $MailingList["bcc"] = array_unique($MailingList["bcc"]);
             $ArrayRecipientsList = array_unique($ArrayRecipientsList);

             // Check if a notification must be sent
             if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail']))
                 && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Template])))
             {
                 $EmailSubject = stripslashes($sSubject);
                 if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MESSAGE]))
                 {
                     $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_MESSAGE].$EmailSubject;
                 }

                 // Formats the list of recipients
                 $sRecipientsList = "<dl style=\"font-style: italic;\">\n";
                 $sRecipientsList .= "\t<dt style=\"text-decoration: underline;\">".$LANG_MESSAGE_RECIPIENTS." : </dt>\n";

                 foreach($ArrayRecipientsList as $r => $CurrentRecipient)
                 {
                     $sRecipientsList .= "\t<dd>$CurrentRecipient</dd>\n";
                 }

                 $sRecipientsList .= "</dl>\n";

                 // We define the content of the mail
                 $TemplateToUse = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Template];
                 $ReplaceInTemplate = array(
                                            array(
                                                  "{MessageAuthor}", "{MessageRecipients}", "{MessageContent}"
                                                 ),
                                            array(
                                                  "$LANG_MESSAGE_AUTHOR : ".stripslashes($sAuthor), $sRecipientsList,
                                                  stripslashes(stripslashes($sMessage))
                                                 )
                                           );

                 $ArrayUploads = array();
                 if (!empty($UploadedFilename))
                 {
                     // Set the uploaded file in attachment
                     $ArrayUploads = array($UploadedFile);
                 }

                 // Get the recipients of the e-mail notification
                 $MailingList["to"] = array();
                 if (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][To]))
                 {
                     $MailingList["to"] = $CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][To];
                 }

                 if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Cc]))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Cc]))
                    )
                 {
                     foreach($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Cc] as $b => $CurrentCc)
                     {
                         if ((!in_array($CurrentCc, array(TO_AUTHOR_MESSAGE))) && (!in_array($CurrentCc, $MailingList["cc"])))
                         {
                             $MailingList["cc"][] = $CurrentCc;
                         }
                     }
                 }

                 if ((isset($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Bcc]))
                     && (!empty($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Bcc]))
                    )
                 {
                     foreach($CONF_EMAIL_SYSTEM_NOTIFICATIONS['UserMessageEmail'][Bcc] as $b => $CurrentBcc)
                     {
                         if ((!in_array($CurrentBcc, array(TO_AUTHOR_MESSAGE))) && (!in_array($CurrentBcc, $MailingList["bcc"])))
                         {
                             $MailingList["bcc"][] = $CurrentBcc;
                         }
                     }
                 }

                 // DEBUG MODE
                 if ($GLOBALS["CONF_MODE_DEBUG"])
                 {
                     if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                     {
                         // Without this test, there is a server mail error...
                         $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                     }
                 }

                 // We send the e-mail : now or after ?
                 if ((isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL])) && (isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_MESSAGE]))
                     && (count($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_MESSAGE]) == 2))
                 {
                     // The message is delayed (job)
                     $ArrayBccRecipients = array_chunk($MailingList["bcc"], $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_MESSAGE][JobSize]);
                     $PlannedDateStamp = strtotime("+1 min", strtotime("now"));
                     $ArrayJobParams = array(
                                             array(
                                                   "JobParameterName" => "subject",
                                                   "JobParameterValue" => $EmailSubject
                                                  ),
                                             array(
                                                   "JobParameterName" => "template-name",
                                                   "JobParameterValue" => $TemplateToUse
                                                  ),
                                             array(
                                                   "JobParameterName" => "replace-in-template",
                                                   "JobParameterValue" => base64_encode(serialize($ReplaceInTemplate))
                                                  ),
                                             array(
                                                   "JobParameterName" => "attachment",
                                                   "JobParameterValue" => base64_encode(serialize($ArrayUploads))
                                                  )
                                            );

                     $iNbJobsCreated = 0;
                     $CurrentMainlingList = array();
                     foreach($ArrayBccRecipients as $r => $CurrentRecipients)
                     {
                         if ($r == 0)
                         {
                             // To and CC only for the first job
                             if (isset($MailingList["to"]))
                             {
                                 $CurrentMainlingList['to'] = $MailingList["to"];
                             }

                             if (isset($MailingList["cc"]))
                             {
                                 $CurrentMainlingList['cc'] = $MailingList["cc"];
                             }
                         }
                         elseif ($r == 1)
                         {
                             // To delete To and CC
                             unset($CurrentMainlingList);
                         }

                         // Define recipients
                         $CurrentMainlingList['bcc'] = $CurrentRecipients;

                         // Create the job to send a delayed e-mail
                         $JobID = dbAddJob($DbCon, $_SESSION['SupportMemberID'], JOB_EMAIL,
                                           date('Y-m-d H:i:s', $PlannedDateStamp), NULL, 0, NULL,
                                           array_merge($ArrayJobParams,
                                                       array(array("JobParameterName" => "mailinglist",
                                                                   "JobParameterValue" => base64_encode(serialize($CurrentMainlingList)))))
                                          );

                         if ($JobID > 0)
                         {
                             $iNbJobsCreated++;

                             // Compute date/time for the next job
                             $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_MESSAGE][DelayBetween2Jobs] * 60;
                         }
                     }

                     unset($ArrayBccRecipients, $ArrayJobParams);

                     if ($iNbJobsCreated > 0)
                     {
                         // Log event
                         logEvent($DbCon, EVT_MESSAGE, EVT_SERV_MESSAGE, EVT_ACT_DIFFUSED, $_SESSION['SupportMemberID'],
                                  $_SESSION['SupportMemberID']);

                         $ConfirmationCaption = $LANG_CONFIRMATION;
                         $ConfirmationSentence = $LANG_CONFIRM_MESSAGE_SENT;
                         $ConfirmationStyle = "ConfirmationMsg";

                         $bIsEmailSent = TRUE;
                     }
                     else
                     {
                         // Wrong parameters
                         $ConfirmationCaption = $LANG_ERROR;
                         $ConfirmationSentence = $LANG_ERROR_MESSAGE_SENT;
                         $ConfirmationStyle = "ErrorMsg";
                     }
                 }
                 else
                 {
                     // We send now the message (e-mail)
                     $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate, $ArrayUploads);

                     if ($bIsEmailSent != 0)
                     {
                         // Log event
                         logEvent($DbCon, EVT_MESSAGE, EVT_SERV_MESSAGE, EVT_ACT_DIFFUSED, $_SESSION['SupportMemberID'],
                                  $_SESSION['SupportMemberID']);

                         $ConfirmationCaption = $LANG_CONFIRMATION;
                         $ConfirmationSentence = $LANG_CONFIRM_MESSAGE_SENT;
                         $ConfirmationStyle = "ConfirmationMsg";
                     }
                     else
                     {
                         // Wrong parameters
                         $ConfirmationCaption = $LANG_ERROR;
                         $ConfirmationSentence = $LANG_ERROR_MESSAGE_SENT;
                         $ConfirmationStyle = "ErrorMsg";
                     }
                 }
             }
             else
             {
                 // Wrong parameters
                 $ConfirmationCaption = $LANG_ERROR;
                 $ConfirmationSentence = $LANG_ERROR_MESSAGE_SENT;
                 $ConfirmationStyle = "ErrorMsg";
             }
         }
         else
         {
             // Errors
             $ConfirmationCaption = $LANG_ERROR;

             if (empty($sAuthor))
             {
                 // The author is empty
                 $ConfirmationSentence = $LANG_ERROR_MESSAGE_AUTHOR;
             }
             elseif (empty($sRecipients))
             {
                 // No recipients
                 $ConfirmationSentence = $LANG_ERROR_MESSAGE_RECIPIENTS;
             }
             elseif (empty($sSubject))
             {
                 // No subject
                 $ConfirmationSentence = $LANG_ERROR_MESSAGE_SUBJECT;
             }
             elseif (empty($sMessage))
             {
                 // No message
                 $ConfirmationSentence = $LANG_ERROR_MESSAGE_CONTENT;
             }
             elseif (!empty($UploadedFilename))
             {
                 // File too big or with a not allowed extension
                 $ConfirmationSentence = $LANG_ERROR_EXTENSION;
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
         // The supporter isn't logged
         $ConfirmationCaption = $LANG_ERROR;
         $ConfirmationSentence = $LANG_ERROR_NOT_LOGGED;
         $ConfirmationStyle = "ErrorMsg";
     }
 }
 else
 {
     // The supporter doesn't come from the SendMessage.php page
     $ConfirmationCaption = $LANG_ERROR;
     $ConfirmationSentence = $LANG_ERROR_COME_FORM_PAGE;
     $ConfirmationStyle = "ErrorMsg";
 }

 if ($bIsEmailSent)
 {
     // A notification is sent
     $ConfirmationSentence .= '&nbsp;'.generateStyledPicture($CONF_NOTIFICATION_SENT_ICON);
 }
 //################################ END FORM PROCESSING ##########################

 initGraphicInterface(
                      $LANG_INTRANET_NAME,
                      array(
                            '../GUI/Styles/styles.css' => 'screen',
                            'Styles_Support.css' => 'screen'
                           ),
                      array('Verifications.js')
                     );
 openWebPage();

 // Display the header of the application
 displayHeader($LANG_INTRANET_HEADER);

 // Display the main menu at the top of the web page
 displaySupportMainMenu();

 // Content of the web page
 openArea('id="content"');

 // Display the "parameters" contextual menu if the supporter is logged, an empty contextual menu otherwise
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Open the contextual menu area
     openArea('id="contextualmenu"');

     displaySupportMemberContextualMenu("parameters", 0, Param_SendMessage);

     // Display information about the logged user
     displayLoggedUser($_SESSION);

     // Close the <div> "contextualmenu"
     closeArea();

     openArea('id="page"');
 }

 // Display the informations, forms, etc. on the right of the web page
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