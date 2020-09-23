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
 * Support module : execute jobs recorded in the Jobs table
 *
 * @author Christophe Javouhey
 * @version 3.1
 *     - 2016-09-09 : taken into account the "reply-to" parameter for JOB_EMAIL, load some
 *                    configuration variables from database and taken into account $CONF_JOBS_EXECUTION_DELAY
 *     - 2017-10-10 : taken into account $CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE
 *
 * @since 2016-05-28
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

 $CONF_URL_SUPPORT = "https://www.calandreta-mureth.dsmynas.org/CanteenCalandreta/Support/";
 $CONF_EMAIL_TEMPLATES_DIRECTORY_HDD = $DOCUMENT_ROOT."Templates/";

 $DbCon = dbConnection();

 // We get jobs to execute for the given period
 $JobPlannedStartDate = date('Y-m-d H:00:00', strtotime("$CONF_JOBS_EXECUTION_DELAY hours ago"));
 $JobPlannedEndDate = date('Y-m-d H:i:00');

 echo "Jobs in [$JobPlannedStartDate ; $JobPlannedEndDate]";

 $ArrayJobs = dbSearchJobs($DbCon, array(
                                         "JobPlannedStartDate" => array($JobPlannedStartDate, ">="),
                                         "JobPlannedEndDate" => array($JobPlannedEndDate, "<="),
                                         "Executed" => FALSE
                                        ), "JobID", 1, 0);

 if ((isset($ArrayJobs['JobID'])) && (!empty($ArrayJobs['JobID'])))
 {
     // We have job to execute
     foreach($ArrayJobs['JobID'] as $j => $JobID)
     {
         // Start execution of the job
         $PlannedDate = NULL;
         $ExecutedDate = date('Y-m-d H:i:s');
         $iNbTries = $ArrayJobs['JobNbTries'][$j] + 1;

         dbUpdateJob($DbCon, $JobID, NULL, NULL, NULL, $ExecutedDate, $iNbTries, NULL);

         switch($ArrayJobs['JobType'][$j])
         {
             case JOB_EMAIL:
                 // Job to send an e-mail : we get parameters of the e-mail to send
                 // First : we find the position of each parameter
                 $ArrayParamsPos = array();
                 foreach($ArrayJobs['JobParameters'][$j] as $p => $CurrentJobParam)
                 {
                     $ArrayParamsPos[strtolower($CurrentJobParam['JobParameterName'])] = $p;
                 }

                 // Next, we define the content of the mail
                 $EmailSubject = $ArrayJobs['JobParameters'][$j][$ArrayParamsPos['subject']]['JobParameterValue'];
                 $TemplateToUse = $ArrayJobs['JobParameters'][$j][$ArrayParamsPos['template-name']]['JobParameterValue'];
                 $ReplaceInTemplate = unserialize(base64_decode($ArrayJobs['JobParameters'][$j][$ArrayParamsPos['replace-in-template']]['JobParameterValue']));

                 // Get the recipients of the e-mail notification
                 $MailingList = unserialize(base64_decode($ArrayJobs['JobParameters'][$j][$ArrayParamsPos['mailinglist']]['JobParameterValue']));

                 // We check if there are some files to send with the e-mail
                 $ArrayFiles = array();
                 if (isset($ArrayParamsPos['attachment']))
                 {
                     $ArrayFiles = unserialize(base64_decode($ArrayJobs['JobParameters'][$j][$ArrayParamsPos['attachment']]['JobParameterValue']));
                 }

                 // We check if there is a specific reply-to
                 $ReplyTo = '';
                 if (isset($ArrayParamsPos['reply-to']))
                 {
                     $ReplyTo = $ArrayJobs['JobParameters'][$j][$ArrayParamsPos['reply-to']]['JobParameterValue'];
                 }

                 // DEBUG MODE
                 if ($GLOBALS["CONF_MODE_DEBUG"])
                 {
                     if (!isset($MailingList["to"]))
                     {
                         $MailingList["to"] = array();
                     }

                     if (!in_array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS, $MailingList["to"]))
                     {
                         // Without this test, there is a server mail error...
                         $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
                     }
                 }

                 // We send the e-mail
                 $JobResult = sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate, $ArrayFiles,
                                        "", $ReplyTo);

                 if ($JobResult === FALSE)
                 {
                     // We reinit the execution date because error
                     $ExecutedDate = '';

                     // If the number of tries is too big, we change the planned date with the DelayAfterXJobFails value
                     if ((isset($CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE[JOB_EMAIL]))
                         && (isset($CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE[JOB_EMAIL][JobNbFails]))
                         && (($iNbTries % $CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE[JOB_EMAIL][JobNbFails]) == 0)
                         && (isset($CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE[JOB_EMAIL][DelayAfterXJobFails]))
                         && ($CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE[JOB_EMAIL][DelayAfterXJobFails] > 0))
                     {
                         $PlannedDate = date('Y-m-d H:i:s',
                                             strtotime('+'.($CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE[JOB_EMAIL][DelayAfterXJobFails]).' minutes',
                                                       strtotime("now")));
                     }
                 }
                 break;
         }

         // Save result of the job
         dbUpdateJob($DbCon, $JobID, NULL, NULL, $PlannedDate, $ExecutedDate, NULL, $JobResult);
     }
 }

 // Delete old executed jobs
 $EndDate = date('Y-m-d H:i:00', strtotime("$CONF_JOBS_DELETE_AFTER_NB_DAYS days ago"));
 $ArrayJobs = dbSearchJobs($DbCon, array(
                                         "JobExecutionEndDate" => array($EndDate, "<="),
                                         "Executed" => TRUE
                                        ), "JobID", 1, 0);

 if ((isset($ArrayJobs['JobID'])) && (!empty($ArrayJobs['JobID'])))
 {
     // Jobs to delete because to old
     foreach($ArrayJobs['JobID'] as $j => $JobID)
     {
         dbDeleteJob($DbCon, $JobID);
     }
 }

 // Delete files sent by e-mail
 $LimitStamp = strtotime("7 days ago");
 $ArrayFiles = glob($CONF_UPLOAD_MESSAGE_FILES_DIRECTORY_HDD."*.*");
 foreach($ArrayFiles as $f => $CurrentFile)
 {
     if (filemtime($CurrentFile) < $LimitStamp)
     {
         // We can delete the file because to old to be used by a job
         unlink($CurrentFile);
         echo "<br />\n$CurrentFile deleted !";
     }
 }

 unset($ArrayFiles);

 // We close the database connection
 dbDisconnection($DbCon);
?>