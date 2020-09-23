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
 * Support module : Send an e-mail to registered families to remind the event start date is near
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-09-09 : allow to send notification to registered families with a delay (jobs) and
 *                    load some configuration variables from database
 *
 * @since 2013-05-02
 */

 if (!function_exists('getIntranetRootDirectoryHDD'))
 {
    /**
     * Give the path of the Intranet root directory on the HDD
     *
     * @author Christophe Javouhey
     * @version 1.0
     * @since 2012-03-20
     *
     * @return String             Intranet root directory on the HDD
     */
     function getIntranetRootDirectoryHDD()
     {
         $sLocalDir = str_replace(array("\\"), array("/"), dirname(__FILE__)).'/';
         $bUnixOS = FALSE;
         if ($sLocalDir{0} == '/')
         {
             $bUnixOS = TRUE;
         }

         $ArrayTmp = explode('/', $sLocalDir);

         $iPos = array_search("CanteenCalandreta", $ArrayTmp);
         if ($iPos !== FALSE)
         {
             $sLocalDir = '';
             if ($bUnixOS)
             {
                 $sLocalDir = '/';
             }

             for($i = 0; $i <= $iPos; $i++)
             {
                 $sLocalDir .= $ArrayTmp[$i].'/';
             }
         }

         return $sLocalDir;
     }
 }

 $DOCUMENT_ROOT = getIntranetRootDirectoryHDD();

 include_once($DOCUMENT_ROOT.'GUI/GraphicInterface.php');

 $CONF_URL_SUPPORT = "http://localhost/CanteenCalandreta/Support/";
 $CONF_EMAIL_TEMPLATES_DIRECTORY_HDD = $DOCUMENT_ROOT."Templates/";

 $NotificationType = 'RemindEvent';

 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 // We get next events
 $CurrentDate = date('Y-m-d');
 $NextEventDate = date('Y-m-d', strtotime("+".$CONF_COOP_EVENT_REMINDER_DELAY.' days', strtotime($CurrentDate)));

 $ArrayEvents = dbSearchEvent($DbCon, array("EventStartDate" => array($NextEventDate, "=")), "EventStartDate", 1, 0);

 // We check if we must send a notification
 $iNbJobsCreated = 0;

 if ((isset($CONF_COOP_EVENT_NOTIFICATIONS[$NotificationType][Template]))
     && (!empty($CONF_COOP_EVENT_NOTIFICATIONS[$NotificationType][Template]))
     && (!empty($CONF_COOP_EVENT_NOTIFICATIONS[$NotificationType][To]))
     && (isset($ArrayEvents['EventID'])) && (!empty($ArrayEvents['EventID']))
    )
 {
     foreach($ArrayEvents['EventID'] as $e => $EventID)
     {
         $EmailSubject = $LANG_REMIND_EVENT_EMAIL_SUBJECT;

         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT]))
         {
             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_EVENT].$EmailSubject;
         }

         $EventUrl = $CONF_URL_SUPPORT."Cooperation/UpdateEvent.php?Cr=".md5($EventID)."&amp;Id=$EventID";
         $EventLink = stripslashes($ArrayEvents['EventTitle'][$e]);
         $EventLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

         $RecordTown = getTableRecordInfos($DbCon, 'Towns', $ArrayEvents['TownID'][$e]);
         $TownName = $RecordTown['TownName'];
         $TownCode = $RecordTown['TownCode'];
         unset($RecordTown);

         // We define the content of the mail
         $TemplateToUse = $CONF_COOP_EVENT_NOTIFICATIONS[$NotificationType][Template];
         $ReplaceInTemplate = array(
                                    array(
                                          "{LANG_EVENT}", "{EventUrl}", "{EventLink}", "{EventLinkTip}", "{LANG_TOWN}",
                                          "{TownName}", "{TownCode}", "{LANG_EVENT_START_DATE}", "{EventStartDate}",
                                          "{LANG_EVENT_START_TIME}", "{EventStartTime}"
                                         ),
                                    array(
                                          $LANG_EVENT, $EventUrl, $EventLink, $EventLinkTip, $LANG_TOWN, $TownName, $TownCode,
                                          $LANG_EVENT_START_DATE,
                                          date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ArrayEvents['EventStartDate'][$e])),
                                          $LANG_EVENT_START_TIME, $ArrayEvents['EventStartTime'][$e]
                                         )
                                   );

         // Get the recipients of the e-mail notification
         $MailingList["to"] = array();
         $MailingList["bcc"] = array();
         foreach($CONF_COOP_EVENT_NOTIFICATIONS[$NotificationType][To] as $rt => $RecipientType)
         {
             $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType);
             if (!empty($ArrayRecipients))
             {
                 $MailingList["bcc"] = array_merge($MailingList["bcc"], $ArrayRecipients);
             }
         }

         if (!empty($CONF_COOP_EVENT_NOTIFICATIONS[$NotificationType][Cc]))
         {
             $MailingList["cc"] = array();
             foreach($CONF_COOP_EVENT_NOTIFICATIONS[$NotificationType][Cc] as $rt => $RecipientType)
             {
                 $ArrayRecipients = getEmailRecipients($DbCon, $EventID, $RecipientType);
                 if (!empty($ArrayRecipients))
                 {
                     $MailingList["cc"] = array_merge($MailingList["cc"], $ArrayRecipients);
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
         if ((isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL])) && (isset($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT]))
             && (count($CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT]) == 2))
         {
             // The message is delayed (job)
             $bIsEmailSent = FALSE;

             $ArrayBccRecipients = array_chunk($MailingList["bcc"], $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT][JobSize]);
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
                 $JobID = dbAddJob($DbCon, 1, JOB_EMAIL,
                                   date('Y-m-d H:i:s', $PlannedDateStamp), NULL, 0, NULL,
                                   array_merge($ArrayJobParams,
                                               array(array("JobParameterName" => "mailinglist",
                                                           "JobParameterValue" => base64_encode(serialize($CurrentMainlingList)))))
                                  );

                 if ($JobID > 0)
                 {
                     $iNbJobsCreated++;

                     // Compute date/time for the next job
                     $PlannedDateStamp += $CONF_JOBS_TO_EXECUTE[JOB_EMAIL][FCT_EVENT][DelayBetween2Jobs] * 60;

                     $bIsEmailSent = TRUE;
                 }
             }

             unset($ArrayBccRecipients, $ArrayJobParams);
         }
         else
         {
             // We send the e-mail
             $bIsEmailSent = sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
         }
     }
 }

 // We close the database connection
 dbDisconnection($DbCon);
?>