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
 * Support module : Send a warning e-mail to families and mailing-list in the case there is too many
 * canteen registrations for a given date
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2015-02-10
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

 $NotificationType = 'WarningTooManyRegistrations';

 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS'));

 $ArrayDatesToTreat = array();  // In this array, we set date for which we must send an e-mail

 // Get the next day
 $CurrentDateStamp = strtotime("now");
 $CurrentDate = date('Y-m-d', $CurrentDateStamp);

 // We anticipate the the date to compute the next date to order meals
 $AnticipatedDateStamp = strtotime('+'.$CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION." hours", $CurrentDateStamp);
 $AnticipatedDate = date('Y-m-d', strtotime('+'.$CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS_DELAY.' days',
                                            strtotime(date('Y-m-d', $AnticipatedDateStamp))));

 $NextDate = getNextWorkingDay($DbCon, $AnticipatedDate);
 $NextDateStamp = strtotime($NextDate);
 $ArrayDatesToTreat[] = $NextDate;

 // Check if there is an opened special day just after this next working day
 // We limit the search of opened special dayes to the current week
 $NextSunday = date('Y-m-d', strtotime("next sunday", strtotime($NextDate)));
 $ArrayOpenedSpecialsDays = getOpenedSpecialDays($DbCon, $NextDate, $NextSunday, 'OpenedSpecialDayDate');
 if (!empty($ArrayOpenedSpecialsDays))
 {
     // We include the found opened special days in the array of dates to treat
     $ArrayDatesToTreat = array_merge($ArrayDatesToTreat, $ArrayOpenedSpecialsDays['OpenedSpecialDayDate']);
 }

 // We compute the date after the mail can be send (mainly in case of vacation, not to send to early and several times
 // the same mail for the same "next day")
 $AllowedDateStamp = strtotime($CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION." hours ago", $NextDateStamp);

 // We add the delay to warn families
 $AllowedDateStamp = strtotime($CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS_DELAY.' days ago',
                               strtotime(date('Y-m-d', $AllowedDateStamp)));

 // We check if we must send a notification
 if ((isset($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Template]))
     && (!empty($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Template]))
     && (!empty($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][To]))
     && ($CurrentDateStamp >= $AllowedDateStamp)
    )
 {
     foreach($ArrayDatesToTreat as $dtt => $DateToTreat)
     {
         // Get nomber of canteen registrations for the next day or another if today is the day before the week-end or vacations
         // Check if there is too many registrations
         $StartDate = $DateToTreat;
         $EndDate = $DateToTreat;

         $ArrayWarningCanteenRegistrations = array();
         if ($CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS > 0)
         {
             $ArrayWarningCanteenRegistrations = getNbCanteenRegistrations($DbCon, $StartDate, $EndDate,
                                                                           array(GROUP_BY_FOR_DATE_BY_DAY), NULL, FALSE,
                                                                           PLANNING_BETWEEN_DATES,
                                                                           array("NbCanteenregistrations" => array(">=", $CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS)));
         }

         if (!empty($ArrayWarningCanteenRegistrations))
         {
             echo "send $DateToTreat";

             // There is too many canteen registrations for the given date : we must send a warning e-mail
             // to families and mailing-list !
             $Year = date('o', strtotime($StartDate));
             $Week = date('W', strtotime($StartDate));
             $View = PLANNING_WEEKS_VIEW;

             // We send an e-mail
             $ForDateToDisplay =  date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"], strtotime($StartDate));
             $EmailSubject = $LANG_WARNING_TOO_MANY_CANTEEN_REGISTRATIONS_EMAIL_SUBJECT." $ForDateToDisplay !";

             if (isset($CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_CANTEEN_PLANNING]))
             {
                 $EmailSubject = $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX[FCT_CANTEEN_PLANNING].$EmailSubject;
             }

             $PlanningUrl = $CONF_URL_SUPPORT."Canteen/CanteenPlanning.php?lView=$View&lWeek=$Week&lYear=$Year";
             $PlanningLinkTip = $LANG_VIEW_DETAILS_INSTRUCTIONS;

             // We define the content of the mail
             $TemplateToUse = $CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Template];
             $ReplaceInTemplate = array(
                                        array(
                                              "{CanteenRegistrationForDate}", "{PlanningUrl}", "{PlanningLinkTip}"
                                             ),
                                        array(
                                              $ForDateToDisplay, $PlanningUrl, $PlanningLinkTip
                                             )
                                       );

             // Get the recipients of the e-mail notification
             $MailingList["to"] = array();

             $MailingList["to"] = $CONF_CANTEEN_NOTIFICATIONS[$NotificationType][To];

             if ($CONF_MODE_DEBUG)
             {
                 $MailingList["to"] = array_merge(array($CONF_EMAIL_INTRANET_EMAIL_ADDRESS), $MailingList["to"]);
             }

             if (isset($CONF_CANTEEN_NOTIFICATIONS[$NotificationType][Bcc]))
             {
                 // Bcc available in the config : send e-amil to families in bcc
                 // We get registered children for this date to get e-mails of their families
                 $MailingList["bcc"] = array();
                 $ArrayCanteenRegistrations = getCanteenRegistrations($DbCon, $StartDate, $EndDate, 'FamilyLastname', NULL,
                                                                      FALSE, PLANNING_BETWEEN_DATES);

                 if (isset($ArrayCanteenRegistrations['ChildID']))
                 {
                     $ArrayFamilyID = array();
                     foreach($ArrayCanteenRegistrations['FamilyID'] as $c => $FamilyID)
                     {
                         if (!in_array($FamilyID, $ArrayFamilyID))
                         {
                             // We get e-mails of the family
                             $RecordFamily = getTableRecordInfos($DbCon, 'Families', $FamilyID);
                             if (!empty($RecordFamily))
                             {
                                 $MailingList["bcc"][] = $RecordFamily['FamilyMainEmail'];
                                 if (!empty($RecordFamily['FamilySecondEmail']))
                                 {
                                     $MailingList["bcc"][] = $RecordFamily['FamilySecondEmail'];
                                 }
                             }

                             $ArrayFamilyID[] = $FamilyID;
                         }
                     }
                 }

             }

             // We send the e-mail
             sendEmail(NULL, $MailingList, $EmailSubject, $TemplateToUse, $ReplaceInTemplate);
         }
     }
 }

 // We close the database connection
 dbDisconnection($DbCon);
?>