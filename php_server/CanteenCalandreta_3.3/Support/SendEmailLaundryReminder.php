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
 * Support module : Send an e-mail to concerned families to remind to wash the laundry
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-10-12 : taken into account Bcc and load some configuration variables from database
 *
 * @since 2015-06-19
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

 $NotificationType = 'RemindLaundry';

 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 // We get next date to wash laundry
 $CurrentDate = date('Y-m-d');
 $NextLaundryDate = date('Y-m-d', strtotime("+".$CONF_LAUNDRY_REMINDER_DELAY.' days', strtotime($CurrentDate)));

 $ArrayLaundryRegistrations = getLaundryRegistrations($DbCon, $NextLaundryDate, $NextLaundryDate,
                                                      'LaundryRegistrationDate, LaundryRegistrationID', NULL, PLANNING_BETWEEN_DATES);

 // We check if we must send a notification
 if ((isset($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Template]))
     && (!empty($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Template]))
     && (isset($ArrayLaundryRegistrations['LaundryRegistrationID'])) && (!empty($ArrayLaundryRegistrations['LaundryRegistrationID']))
    )
 {
     foreach($ArrayLaundryRegistrations['LaundryRegistrationID'] as $sl => $LaundryRegistrationID)
     {
         // We get the school year of the date to get the laundry
         $SchoolYear = getSchoolYear($ArrayLaundryRegistrations['LaundryRegistrationDate'][$sl]);

         echo "Notification de la lessive envoyée à la famille ".$ArrayLaundryRegistrations['FamilyLastname'][$sl]
             ." pour le ".date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($ArrayLaundryRegistrations['LaundryRegistrationDate'][$sl]))
             .".<br />\n";

         $EmailSubject = $LANG_REMIND_LAUNDRY_EMAIL_SUBJECT;

         if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_LAUNDRY_PLANNING]))
         {
             $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_LAUNDRY_PLANNING].$EmailSubject;
         }

         $LaundryUrl = $CONF_URL_SUPPORT."Canteen/LaundryPlanning.php?lYear=$SchoolYear";
         $LaundryLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

         // We define the content of the mail
         $TemplateToUse = $CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Template];
         $ReplaceInTemplate = array(
                                    array(
                                          "{LaundryUrl}", "{LaundryLinkTip}", "{LaundryRegistrationDate}"
                                         ),
                                    array(
                                          $LaundryUrl, $LaundryLinkTip,
                                          date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                               strtotime($ArrayLaundryRegistrations['LaundryRegistrationDate'][$sl]))
                                         )
                                   );

         // Get the recipients of the e-mail notification
         $MailingList = array();

         if (empty($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][To]))
         {
             // We send the notification to concerned family : we get e-mails of family
             $RecordFamily = getTableRecordInfos($DbCon, "Families", $ArrayLaundryRegistrations['FamilyID'][$sl]);
             $MailingList["to"][] = $RecordFamily['FamilyMainEmail'];
             if (!empty($RecordFamily['FamilySecondEmail']))
             {
                 $MailingList["to"][] = $RecordFamily['FamilySecondEmail'];
             }
         }
         else
         {
             // We send the notification to the defined mailing-list
             $MailingList["to"] = $CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][To];
         }

         if ((isset($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Cc])) && (!empty($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Cc])))
         {
             $MailingList["cc"] = $CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Cc];
         }

         if ((isset($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Bcc])) && (!empty($CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Bcc])))
         {
             $MailingList["bcc"] = $CONF_LAUNDRY_NOTIFICATIONS[$NotificationType][Bcc];
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

         // We send the e-mail
         sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
     }
 }

 // We close the database connection
 dbDisconnection($DbCon);
?>