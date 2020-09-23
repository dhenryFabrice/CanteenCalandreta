<?php
/* Copyright (C) 2012 Calandreta Del PaÔs Murethin
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
 * Common module : library of functions
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2012-01-12
 */

 include_once("DbLibrary.php");         // Include the database primitives library
 include_once("DateTimeLibrary.php");   // Include the date/time primitives library
 include_once("LoadDBConfig.php");      // Include DB config library
 include_once("EmailLibrary.php");      // Include the e-mail primitives library


//########################### EXECUTION TIME FUNCTIONS ###########################
/**
 * Initialize the start time value
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-27
 */
 function initStartTime()
 {
     global $START_TIME;

     $timeparts = explode(" ", microtime());
     $START_TIME = $timeparts[1].substr($timeparts[0], 1);
 }


/**
 * Initialize the end time value
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2003-12-27
 */
 function initEndTime()
 {
     global $END_TIME;

     $timeparts = explode(" ", microtime());
     $END_TIME = $timeparts[1].substr($timeparts[0], 1);
 }


/**
 * Check if the logged user is an admin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-10-28
 */
 function isAdmin()
 {
     if ((isset($_SESSION['SupportMemberStateID'])) && ($_SESSION['SupportMemberStateID'] == 1))
     {
         return TRUE;
     }
     else
     {
         return FALSE;
     }
 }


/**
 * Compute the execution time of a function
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-01-04
 *
 * @param $Fct            String         Name of the function to evaluate
 * @param $ArrayParams    Mixed array    Parameters of the function
 * @param $Display        String         Used to display the execution time if
 *                                       not empty string
 * @param $Mask           String         Tag used in $Display to be replaced by the
 *                                       execution time
 *
 * @return Mixed          Value returned by the function
 */
 function profilingCode($Fct, $ArrayParams, $Display = '', $Mask = '__TIME__')
 {
     // Get start time
     $timeparts = explode(" ", microtime());
     $start_time = $timeparts[1].substr($timeparts[0], 1);

     // Eval the function
     $ReturnValue = call_user_func_array($Fct, $ArrayParams);

     // Get the end time
     $timeparts = explode(' ', microtime());
     $end_time = $timeparts[1].substr($timeparts[0], 1);

     // Display the time
     if ($Display != '')
     {
         echo str_replace(array($Mask), array(bcsub($end_time, $start_time, 6)), $Display);
     }

     // Return the value of the evaluated function
     return $ReturnValue;
 }


/**
 * Check if we can log the given event and log it
 *
 * @author Christophe Javouhey
 * @version 2.7
 *     - 2013-04-05 : taken into account events of the Cooperation module
 *     - 2014-02-03 : taken into account nursery delays
 *     - 2015-07-09 : taken into account snack registrations, laundry registrations and exit permissions
 *     - 2015-10-13 : taken into account workgroups of the Cooperation module
 *     - 2016-03-04 : taken into account towns, alias, messages sent and donations
 *     - 2016-10-28 : taken into account SWAP action for snack and laundry plannings
 *     - 2017-10-06 : taken into account discounts of families
 *     - 2019-05-07 : taken into account documents approvals
 *
 * @since 2012-01-12
 *
 * @param $DbConnection          DB object      Object of the opened database connection
 * @param $ItemType              String         Type of the event to log
 * @param $Service               String         Name of the service of the event to log
 * @param $Action                String         Name of the action of the event to log
 * @param $SupportMemberID       Integer        ID of the supporter who has done the action [0..n]
 * @param $ItemID                Integer        ID of the object concerned by the event (ask of work, document...) [0..n]
 * @param $ArrayInfos            Mixed array    More info about the event or the object concerned by the event
 *                                              (very usefull for the "delete" event)
 *
 * @return Boolean               TRUE if the event has been logged, FALSE otherwise
 */
 function logEvent($DbConnection, $ItemType, $Service, $Action, $SupportMemberID = 0, $ItemID = 0, $ArrayInfos = array())
 {
     if (($ItemID >= 0) && (!empty($ItemType)) && (!empty($Service)) && (!empty($Action)) && ($SupportMemberID >= 0))
     {
         // Check if this event exists and if its level can be logged
         if (
             (isset($GLOBALS['CONF_LOG_EVENTS'][$ItemType][$Service][$Action]))
              && (isset($GLOBALS['CONF_LOG_EVENTS'][$ItemType][$Service][$Action]['level']))
             )
         {
             $Level = $GLOBALS['CONF_LOG_EVENTS'][$ItemType][$Service][$Action]['level'];
             if (in_array($Level, $GLOBALS['CONF_LOG_EVENTS_LEVELS']))
             {
                 // We can log this event
                 $EventDate = date('Y-m-d H:i:s');

                 // ID of the linked object (only for 0-n, 1-n and n-n relations in the database
                 $LinkedObjectID = NULL;

                 // A title for this event?
                 $Title = '';

                 // A description for this event?
                 $Subject = '';
                 $ID = '';
                 $Filename = '';
                 $Access = '';
                 $Reference = '';
                 $Type = '';
                 $Supporter = '';
                 $Name = '';
                 $EmailContent = '';
                 $Date = '';
                 switch($ItemType)
                 {
                     case EVT_SYSTEM:
                         switch($Service)
                         {
                             case EVT_SERV_LOGIN:
                                 switch($Action)
                                 {
                                     case EVT_ACT_LOGIN:
                                     case EVT_ACT_LOGIN_FAILED:
                                         $Title = "Login";
                                         break;

                                     case EVT_ACT_LOGOUT:
                                         $Title = "Logout";
                                         break;
                                 }
                                 break;

                             case EVT_SERV_TOWN:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingTown($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Towns', $ItemID);
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $Subject = $Record['TownName']." (".$Record['TownCode'].")";
                                 $Name = $Record['TownName'];

                                 $Title = $GLOBALS['LANG_TOWN']." n∞$Reference : $Subject";
                                 break;
                         }
                         break;

                     case EVT_PROFIL:
                         switch($Service)
                         {
                             case EVT_SERV_PROFIL:
                                 if (isExistingSupportMember($DbConnection, $ItemID))
                                 {
                                     $ID = $ItemID;
                                     $Reference = $ItemID;
                                     $Subject = $GLOBALS['LANG_PROFIL'];
                                     $RecordSupporter = getSupportMemberInfos($DbConnection, $ItemID);
                                     $Supporter = $RecordSupporter['SupportMemberFirstname'].' '.$RecordSupporter['SupportMemberLastname'];

                                     $Title = $GLOBALS['LANG_PROFIL'];
                                 }
                                 break;

                             case EVT_SERV_LOGIN:
                                 if (isExistingSupportMember($DbConnection, $ItemID))
                                 {
                                     $ID = $ItemID;
                                     $Reference = $ItemID;
                                     $Subject = $GLOBALS['LANG_LOGIN'];
                                     $RecordSupporter = getSupportMemberInfos($DbConnection, $ItemID);
                                     $Supporter = $RecordSupporter['SupportMemberFirstname'].' '.$RecordSupporter['SupportMemberLastname'];

                                     $Title = $GLOBALS['LANG_LOGIN'];
                                 }
                                 break;

                             case EVT_SERV_PREPARED_REQUEST:
                                 if ((isset($ArrayInfos['FuncName'])) && (!empty($ArrayInfos['FuncName'])))
                                 {
                                     $FuncName = $ArrayInfos['FuncName'];
                                     $Title = 'RequÍte prÈparÈe '.$FuncName.'() exÈcutÈe';
                                 }
                                 break;
                         }
                         break;

                     case EVT_FAMILY:
                         switch($Service)
                         {
                             case EVT_SERV_FAMILY:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingFamily($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Families', $ItemID);
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $Subject = $Record['FamilyLastname'];

                                 $Title = $GLOBALS['LANG_FAMILY']." n∞$Reference : $Subject";
                                 break;

                             case EVT_SERV_CHILD:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingChild($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Children', $ItemID);
                                             $LinkedObjectID = $ItemID;
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['ChildDetails'])) && (!empty($ArrayInfos['ChildDetails'])))
                                         {
                                             $Record = $ArrayInfos['ChildDetails'];
                                             $LinkedObjectID = $Record['FamilyID'];
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingFamily($DbConnection, $Record['FamilyID'])))
                                 {
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $Record['FamilyID']);
                                     $Reference = $RecordFamily['FamilyID'];
                                     $Subject = $RecordFamily['FamilyLastname'];
                                     $Name = $Record['ChildFirstname'];

                                     $Title = "$Reference : $Subject";
                                 }
                                 break;

                             case EVT_SERV_SUSPENSION:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingSuspension($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Suspensions', $ItemID);
                                             $LinkedObjectID = $ItemID;
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingChild($DbConnection, $Record['ChildID'])))
                                 {
                                     $RecordChild = getTableRecordInfos($DbConnection, 'Children', $Record['ChildID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordChild['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'].' '.$RecordChild['ChildFirstname'];

                                     $Title = $GLOBALS['LANG_SUSPENSION']." $Reference : $Name";
                                 }
                                 break;
                         }
                         break;

                     case EVT_PAYMENT:
                         switch($Service)
                         {
                             case EVT_SERV_PAYMENT:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingPayment($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Payments', $ItemID);
                                             $LinkedObjectID = $ItemID;
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['PaymentDetails'])) && (!empty($ArrayInfos['PaymentDetails'])))
                                         {
                                             $Record = $ArrayInfos['PaymentDetails'];
                                             $LinkedObjectID = $Record['FamilyID'];
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingFamily($DbConnection, $Record['FamilyID'])))
                                 {
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $Record['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'];
                                     $Type = $GLOBALS['CONF_PAYMENTS_TYPES'][$Record['PaymentType']];

                                     $Title = "$Reference : $Type";
                                 }
                                 break;

                             case EVT_SERV_BANK:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingBank($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Banks', $ItemID);
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $Subject = $Record['BankName'];
                                 $Name = $Record['BankName'];

                                 $Title = $GLOBALS['LANG_BANK']." n∞$Reference : $Subject";
                                 break;

                             case EVT_SERV_DISCOUNT:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingDiscountFamily($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'DiscountsFamilies', $ItemID);
                                             $LinkedObjectID = $ItemID;
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['DiscountDetails'])) && (!empty($ArrayInfos['DiscountDetails'])))
                                         {
                                             $Record = $ArrayInfos['DiscountDetails'];
                                             $LinkedObjectID = $Record['FamilyID'];
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingFamily($DbConnection, $Record['FamilyID'])))
                                 {
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $Record['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'];
                                     $Type = $GLOBALS['CONF_DISCOUNTS_FAMILIES_TYPES'][$Record['DiscountFamilyType']];

                                     $Title = "$Reference : $Type";
                                 }
                                 break;
                         }
                         break;

                     case EVT_CANTEEN:
                         switch($Service)
                         {
                             case EVT_SERV_PLANNING:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingCanteenRegistration($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'CanteenRegistrations', $ItemID);
                                             $LinkedObjectID = $ItemID;
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['CanteenRegistrationDetails'])) && (!empty($ArrayInfos['CanteenRegistrationDetails'])))
                                         {
                                             $Record = $ArrayInfos['CanteenRegistrationDetails'];
                                             $LinkedObjectID = $Record['ChildID'];
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingChild($DbConnection, $Record['ChildID'])))
                                 {
                                     $RecordChild = getTableRecordInfos($DbConnection, 'Children', $Record['ChildID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordChild['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'].' '.$RecordChild['ChildFirstname'];
                                     $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($Record['CanteenRegistrationForDate']));

                                     $Title = $GLOBALS['LANG_CANTEEN']." : $Name ($Date)";
                                 }
                                 break;
                         }
                         break;

                     case EVT_NURSERY:
                         switch($Service)
                         {
                             case EVT_SERV_PLANNING:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingNurseryRegistration($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'NurseryRegistrations', $ItemID);
                                             $LinkedObjectID = $ItemID;
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['NurseryRegistrationDetails'])) && (!empty($ArrayInfos['NurseryRegistrationDetails'])))
                                         {
                                             $Record = $ArrayInfos['NurseryRegistrationDetails'];
                                             $LinkedObjectID = $Record['ChildID'];
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingChild($DbConnection, $Record['ChildID'])))
                                 {
                                     $RecordChild = getTableRecordInfos($DbConnection, 'Children', $Record['ChildID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordChild['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'].' '.$RecordChild['ChildFirstname'];
                                     $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($Record['NurseryRegistrationForDate']));

                                     // AM and/or PM?
                                     $sAmPm = '';
                                     if (!empty($Record['NurseryRegistrationForAM']))
                                     {
                                         $sAmPm = $GLOBALS['LANG_AM'];
                                     }

                                     if (!empty($Record['NurseryRegistrationForPM']))
                                     {
                                         if (!empty($sAmPm))
                                         {
                                             $sAmPm .= " / ";
                                         }

                                         $sAmPm .= $GLOBALS['LANG_PM'];
                                     }

                                     $Date.= ", $sAmPm";
                                     $Title = $GLOBALS['LANG_NURSERY']." : $Name ($Date)";
                                 }
                                 break;

                             case EVT_SERV_DELAY:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                         if (isExistingNurseryRegistration($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'NurseryRegistrations', $ItemID);
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingChild($DbConnection, $Record['ChildID'])))
                                 {
                                     $RecordChild = getTableRecordInfos($DbConnection, 'Children', $Record['ChildID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordChild['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'].' '.$RecordChild['ChildFirstname'];
                                     $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($Record['NurseryRegistrationForDate']));

                                     $Title = $GLOBALS['LANG_NURSERY_DELAY']." : $Name ($Date)";
                                 }
                                 break;
                         }
                         break;

                     case EVT_SNACK:
                         switch($Service)
                         {
                             case EVT_SERV_PLANNING:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                         $LinkedObjectID = NULL;
                                         break;

                                     case EVT_ACT_UPDATE:
                                         if (isExistingSnackRegistration($DbConnection, $ItemID))
                                         {
                                             if ((isset($ArrayInfos['ChildDetails'])) && (!empty($ArrayInfos['ChildDetails'])))
                                             {
                                                 $Record = $ArrayInfos['ChildDetails'];
                                                 $LinkedObjectID = $ArrayInfos['FamilyID'];
                                             }
                                         }
                                         break;

                                     case EVT_ACT_SWAP:
                                         if (isExistingSnackRegistration($DbConnection, $ItemID))
                                         {
                                             $RecordRegistration = getTableRecordInfos($DbConnection, "SnackRegistrations", $ItemID);
                                             $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordRegistration['FamilyID']);

                                             $Reference = $ItemID;
                                             $Name = $RecordFamily['FamilyLastname'];
                                             $Title = "$Reference : $Name";
                                             $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                          strtotime($RecordRegistration['SnackRegistrationDate']));

                                             unset($RecordRegistration, $RecordFamily);
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingChild($DbConnection, $Record['ChildID'])))
                                 {
                                     $RecordChild = getTableRecordInfos($DbConnection, 'Children', $Record['ChildID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordChild['FamilyID']);

                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'].' '.$RecordChild['ChildFirstname'];
                                     $Title = "$Reference : $Name";
                                 }
                                 break;
                         }
                         break;

                     case EVT_LAUNDRY:
                         switch($Service)
                         {
                             case EVT_SERV_PLANNING:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                         $LinkedObjectID = NULL;
                                         break;

                                     case EVT_ACT_UPDATE:
                                         if (isExistingLaundryRegistration($DbConnection, $ItemID))
                                         {
                                             if ((isset($ArrayInfos['ChildDetails'])) && (!empty($ArrayInfos['ChildDetails'])))
                                             {
                                                 $Record = $ArrayInfos['ChildDetails'];
                                                 $LinkedObjectID = $ArrayInfos['FamilyID'];
                                             }
                                         }
                                         break;

                                     case EVT_ACT_SWAP:
                                         if (isExistingLaundryRegistration($DbConnection, $ItemID))
                                         {
                                             $RecordRegistration = getTableRecordInfos($DbConnection, "LaundryRegistrations", $ItemID);
                                             $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordRegistration['FamilyID']);

                                             $Reference = $ItemID;
                                             $Name = $RecordFamily['FamilyLastname'];
                                             $Title = "$Reference : $Name";
                                             $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'],
                                                          strtotime($RecordRegistration['LaundryRegistrationDate']));

                                             unset($RecordRegistration, $RecordFamily);
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingChild($DbConnection, $Record['ChildID'])))
                                 {
                                     $RecordChild = getTableRecordInfos($DbConnection, 'Children', $Record['ChildID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordChild['FamilyID']);

                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'].' '.$RecordChild['ChildFirstname'];
                                     $Title = "$Reference : $Name";
                                 }
                                 break;
                         }
                         break;

                     case EVT_EXIT_PERMISSION:
                         switch($Service)
                         {
                             case EVT_SERV_PLANNING:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingExitPermission($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'ExitPermissions', $ItemID);
                                             $LinkedObjectID = $Record['ChildID'];
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['ExitPermissionDetails'])) && (!empty($ArrayInfos['ExitPermissionDetails'])))
                                         {
                                             $Record = $ArrayInfos['ExitPermissionDetails'];
                                             $LinkedObjectID = $Record['ChildID'];
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingChild($DbConnection, $Record['ChildID'])))
                                 {
                                     $RecordChild = getTableRecordInfos($DbConnection, 'Children', $Record['ChildID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $RecordChild['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'].' '.$RecordChild['ChildFirstname'];
                                     $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($Record['ExitPermissionDate']));

                                     $Title = $GLOBALS['LANG_EXIT_PERMISSION']." : $Name ($Date)";
                                 }
                                 break;
                         }
                         break;

                     case EVT_DOCUMENT_APPROVAL:
                         switch($Service)
                         {
                             case EVT_SERV_DOCUMENT_APPROVAL:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingDocumentApproval($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'DocumentsApprovals', $ItemID);
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['DocumentApprovalDetails'])) && (!empty($ArrayInfos['DocumentApprovalDetails'])))
                                         {
                                             $Record = $ArrayInfos['DocumentApprovalDetails'];
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $Name = $Record['DocumentApprovalName'];

                                 $Title = $GLOBALS['LANG_DOCUMENT_APPROVAL']." n∞$ItemID : $Name";
                                 break;

                             case EVT_SERV_DOCUMENT_FAMILY_APPROVAL:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingDocumentFamilyApproval($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'DocumentsFamiliesApprovals', $ItemID);
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['ExitDocumentFamilyApprovalDetails'])) && (!empty($ArrayInfos['ExitDocumentFamilyApprovalDetails'])))
                                         {
                                             $Record = $ArrayInfos['ExitDocumentFamilyApprovalDetails'];
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $LinkedObjectID = $Record['DocumentApprovalID'];

                                 $RecordSupporter = getSupportMemberInfos($DbConnection, $SupportMemberID);
                                 $Supporter = $RecordSupporter['SupportMemberFirstname'].' '.$RecordSupporter['SupportMemberLastname'];

                                 // Get infos about the document
                                 $RecordDocumentApproval = getTableRecordInfos($DbConnection, 'DocumentsApprovals', $LinkedObjectID);
                                 $Name = $RecordDocumentApproval['DocumentApprovalName'];

                                 $Title = $GLOBALS['LANG_DOCUMENT_FAMILY_APPROVAL']." : $Name";
                                 break;
                         }
                         break;

                     case EVT_EVENT:
                         switch($Service)
                         {
                             case EVT_SERV_EVENT:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingEvent($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Events', $ItemID);
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['EventDetails'])) && (!empty($ArrayInfos['EventDetails'])))
                                         {
                                             $Record = $ArrayInfos['EventDetails'];
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $Subject = $Record['EventTitle'];

                                 $Title = $GLOBALS['LANG_EVENT']." n∞$Reference : $Subject";
                                 break;

                             case EVT_SERV_EVENT_REGISTRATION:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingEventRegistration($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'EventRegistrations', $ItemID);
                                             $LinkedObjectID = $Record['EventID'];
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['EventRegistrationDetails'])) && (!empty($ArrayInfos['EventRegistrationDetails'])))
                                         {
                                             $Record = $ArrayInfos['EventRegistrationDetails'];
                                             $LinkedObjectID = $Record['EventID'];
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isExistingFamily($DbConnection, $Record['FamilyID'])))
                                 {
                                     $RecordEvent = getTableRecordInfos($DbConnection, 'Events', $Record['EventID']);
                                     $RecordFamily = getTableRecordInfos($DbConnection, 'Families', $Record['FamilyID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'];
                                     $Subject = $RecordEvent['EventTitle'];
                                     $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($RecordEvent['EventStartDate']));

                                     $Title = $GLOBALS['LANG_EVENT']." : $Subject ($Name)";
                                 }
                                 break;

                             case EVT_SERV_EVENT_SWAPPED_REGISTRATION:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingEventSwappedRegistration($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'EventSwappedRegistrations', $ItemID);
                                             $LinkedObjectID = $Record['RequestorEventID'];
                                             if (isExistingFamily($DbConnection, $Record['RequestorFamilyID']))
                                             {
                                                 $RecordFamily = getTableRecordInfos($DbConnection, 'Families',
                                                                                     $Record['RequestorFamilyID']);
                                             }
                                         }
                                         break;

                                     case EVT_ACT_DIFFUSED:
                                         if (isExistingEventSwappedRegistration($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'EventSwappedRegistrations', $ItemID);
                                             $LinkedObjectID = $Record['AcceptorEventID'];
                                             if (isExistingFamily($DbConnection, $Record['AcceptorFamilyID']))
                                             {
                                                 $RecordFamily = getTableRecordInfos($DbConnection, 'Families',
                                                                                     $Record['AcceptorFamilyID']);
                                             }
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['EventSwappedRegistrationDetails']))
                                             && (!empty($ArrayInfos['EventSwappedRegistrationDetails'])))
                                         {
                                             $Record = $ArrayInfos['EventSwappedRegistrationDetails'];
                                             $LinkedObjectID = $Record['RequestorEventID'];
                                             if (isExistingFamily($DbConnection, $Record['RequestorFamilyID']))
                                             {
                                                 $RecordFamily = getTableRecordInfos($DbConnection, 'Families',
                                                                                     $Record['RequestorFamilyID']);
                                             }
                                         }
                                         break;
                                 }

                                 if ((isset($Record)) && (isset($RecordFamily)))
                                 {
                                     $RecordEvent = getTableRecordInfos($DbConnection, 'Events', $Record['RequestorEventID']);
                                     $Reference = $ItemID;
                                     $Name = $RecordFamily['FamilyLastname'];
                                     $Subject = $RecordEvent['EventTitle'];
                                     $Date = date($GLOBALS['CONF_DATE_DISPLAY_FORMAT'], strtotime($RecordEvent['EventStartDate']));

                                     $Title = $GLOBALS['LANG_EVENT']." : $Subject ($Name)";
                                 }
                                 break;
                         }
                         break;

                     case EVT_WORKGROUP:
                         switch($Service)
                         {
                             case EVT_SERV_WORKGROUP:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingWorkGroup($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'WorkGroups', $ItemID);
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['WorkGroupDetails'])) && (!empty($ArrayInfos['WorkGroupDetails'])))
                                         {
                                             $Record = $ArrayInfos['WorkGroupDetails'];
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $Subject = $Record['WorkGroupName'];

                                 $Title = $GLOBALS['LANG_WORKGROUP']." n∞$Reference : $Subject";
                                 break;

                             case EVT_SERV_WORKGROUP_REGISTRATION:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_ADD:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingWorkGroupRegistration($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'WorkGroupRegistrations', $ItemID);
                                             $LinkedObjectID = $Record['WorkGroupID'];
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['WorkGroupRegistrationDetails']))
                                             && (!empty($ArrayInfos['WorkGroupRegistrationDetails'])))
                                         {
                                             $Record = $ArrayInfos['WorkGroupRegistrationDetails'];
                                             $LinkedObjectID = $Record['WorkGroupID'];
                                         }
                                         break;
                                 }

                                 if (isset($Record))
                                 {
                                     $RecordWorkGroup = getTableRecordInfos($DbConnection, 'WorkGroups', $Record['WorkGroupID']);
                                     $Reference = $ItemID;
                                     $Name = $Record['WorkGroupRegistrationLastname'].' '.$Record['WorkGroupRegistrationFirstname'];
                                     $Subject = $RecordWorkGroup['WorkGroupName'];

                                     $Title = $GLOBALS['LANG_WORKGROUP']." : $Subject ($Name)";
                                 }
                                 break;
                         }
                         break;

                     case EVT_DONATION:
                         switch($Service)
                         {
                             case EVT_SERV_DONATION:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingDonation($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Donations', $ItemID);
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['DonationDetails'])) && (!empty($ArrayInfos['DonationDetails'])))
                                         {
                                             $Record = $ArrayInfos['DonationDetails'];
                                         }
                                         break;
                                 }

                                 $Reference = $Record['DonationReference'];
                                 $Subject = $Record['DonationLastname'].' '.$Record['DonationFirstname'];

                                 $Title = $GLOBALS['LANG_DONATION']." n∞$Reference : $Subject";
                                 break;
                         }
                         break;

                     case EVT_MESSAGE:
                         switch($Service)
                         {
                             case EVT_SERV_ALIAS:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_CREATE:
                                     case EVT_ACT_UPDATE:
                                         if (isExistingAlias($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Alias', $ItemID);
                                         }
                                         break;

                                     case EVT_ACT_COPY:
                                         if (isExistingAlias($DbConnection, $ItemID))
                                         {
                                             $Record = getTableRecordInfos($DbConnection, 'Alias', $ItemID);
                                         }

                                         if ((isset($ArrayInfos['WorkGroupDetails'])) && (!empty($ArrayInfos['WorkGroupDetails'])))
                                         {
                                             $RecordWorkGroup = $ArrayInfos['WorkGroupDetails'];
                                             $Subject = $RecordWorkGroup['WorkGroupName'];
                                         }
                                         break;

                                     case EVT_ACT_DELETE:
                                         if ((isset($ArrayInfos['AliasDetails'])) && (!empty($ArrayInfos['AliasDetails'])))
                                         {
                                             $Record = $ArrayInfos['AliasDetails'];
                                         }
                                         break;
                                 }

                                 $Reference = $ItemID;
                                 $Name = $Record['AliasName'];

                                 $Title = $GLOBALS['LANG_ALIAS']." n∞$Reference : $Name";
                                 break;

                             case EVT_SERV_MESSAGE:
                                 $ID = $ItemID;
                                 switch($Action)
                                 {
                                     case EVT_ACT_DIFFUSED:
                                         $RecordSupporter = getSupportMemberInfos($DbConnection, $ItemID);

                                         $Supporter = '';
                                         if (strlen($RecordSupporter['SupportMemberFirstname']) >= 2)
                                         {
                                             $Supporter = $RecordSupporter['SupportMemberFirstname'].' ';
                                         }

                                         $Supporter .= $RecordSupporter['SupportMemberLastname'];
                                         $Title = $GLOBALS['LANG_MESSAGE'];
                                         break;
                                 }
                                 break;
                         }
                         break;
                 }

                 // A description for this event?
                 $Description = '';
                 if (isset($GLOBALS['CONF_LOG_EVENTS'][$ItemType][$Service][$Action]['msg']))
                 {
                     $Description = $GLOBALS['CONF_LOG_EVENTS'][$ItemType][$Service][$Action]['msg'];
                 }

                 // Define tags to replace by values
                 $ArrayReplacements = array(
                                            'Replace' => array(),
                                            'Replacement' => array()
                                           );

                 $ArrayReplacements['Replace'][] = "@IP";
                 $ArrayReplacements['Replacement'][] = $_SERVER['REMOTE_ADDR'];

                 $ArrayReplacements['Replace'][] = "@SUBJECT";
                 $ArrayReplacements['Replacement'][] = $Subject;

                 $ArrayReplacements['Replace'][] = "@ID";
                 $ArrayReplacements['Replacement'][] = $ID;

                 $ArrayReplacements['Replace'][] = "@FILENAME";
                 $ArrayReplacements['Replacement'][] = $Filename;

                 $ArrayReplacements['Replace'][] = "@REF";
                 $ArrayReplacements['Replacement'][] = $Reference;

                 $ArrayReplacements['Replace'][] = "@TYPE";
                 $ArrayReplacements['Replacement'][] = $Type;

                 $ArrayReplacements['Replace'][] = "@SUPPORTER";
                 $ArrayReplacements['Replacement'][] = $Supporter;

                 $ArrayReplacements['Replace'][] = "@NAME";
                 $ArrayReplacements['Replacement'][] = $Name;

                 $ArrayReplacements['Replace'][] = "@DATE";
                 $ArrayReplacements['Replacement'][] = $Date;

                 $ArrayReplacements['Replace'][] = "@EMAIL_CONTENT";
                 $ArrayReplacements['Replacement'][] = formatText(str_replace(
                                                                              array('<html>', '</html>', '<body>', '</body>'),
                                                                              array('', '', '', ''),
                                                                              $EmailContent
                                                                              ));

                 // Do replacements
                 $Description = str_replace($ArrayReplacements['Replace'], $ArrayReplacements['Replacement'], $Description);

                 // Use the title prefix of the action
                 if (!empty($GLOBALS['CONF_LOG_EVENTS_TITLE_PREFIX'][$Action]))
                 {
                     $Title = $GLOBALS['CONF_LOG_EVENTS_TITLE_PREFIX'][$Action]." $Title";
                 }

                 // Log the event in the database
                 $LogEventID = dbLogEvent($DbConnection, $EventDate, $ItemID, $ItemType, $Service, $Action, $Level, $SupportMemberID,
                                          $Title, $Description, $LinkedObjectID);

                 if ($LogEventID > 0)
                 {
                     // Event logged
                     return TRUE;
                 }
             }
         }
     }

     // ERROR
     return FALSE;
 }


//########################### NUMBERS FUNCTIONS ############################
/**
 * Check if a value is an integer
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2005-09-21 : function patched (pb with a value like "2001-001"
 *                    considered as an integer)
 *
 * @since 2004-04-11
 *
 * @param $Value       Mixed      Value to check
 *
 * @return Boolean                TRUE if the value is an integer, FALSE otherwise
 */
 function isInteger($Value)
 {
     if (preg_match("/^[+\-]{0,1}[0-9]{1,}$/", $Value) != 0)
     {
         // The value must not have a '.' or ',' to be an integer
         if ((preg_match("[\.]", $Value) == 0) && (preg_match("[,]", $Value) == 0))
         {
             // The value is an integer
             return TRUE;
         }
     }

     // The values isn't an integer
     return FALSE;
 }


/**
 * Check if a value is a float
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-11
 *
 * @param $Value       Mixed      Value to check
 *
 * @return Boolean                TRUE if the value is a float, FALSE otherwise
 */
 function isFloat($Value)
 {
     // The value must have a '.' or ',' to be a float
     if (preg_match("[\d+[.\,]\d+]", $Value) != 0)
     {
         // The value is a float
         return TRUE;
     }

     // The values isn't a float
     return FALSE;
 }


/**
 * Try to find the right divisor for the Value to get an integer.
 * The divisor is in the range of [MinDiv ; MaxDiv]
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2015-01-16
 *
 * @param $Value       Float      Value for which we want to find the right divisor
 * @param $MinDiv      Float      Min divisor
 * @param $MaxDiv      Float      Max divisor
 *
 * @return Float       The right divisor to get Value / divisor = integer, 0 otherwise
 */
 function findExactDivisor($Value, $MinDiv, $MaxDiv, $Inc = 0.1)
 {
     if ($MinDiv > $MaxDiv)
     {
         // Swap values
         $fTmp = $MinDiv;
         $MinDiv = $MaxDiv;
         $MaxDiv = $fTmp;
     }

     if ($Inc < 0)
     {
         $Inc = 0.1;
     }

     $bFound = FALSE;
     $fCurrentDiv = $MinDiv;

     do
     {
         $fCurrentDiv += $Inc;
         $iNb = $Value / $fCurrentDiv;

         if (round(abs($iNb - round($iNb)), 3) == 0)
         {
             // Divisor found
             $bFound = TRUE;
         }
     } while((!$bFound) && ($fCurrentDiv <= $MaxDiv));

     if ($bFound)
     {
         return $fCurrentDiv;
     }
     else
     {
         // Divisor not found
         return 0;
     }
 }


//########################### ARRAY FUNCTIONS ##############################
/**
 * Insert an element or an array in an array at a specified position
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2004-04-30 : able to insert a single value or an array
 *
 * @since 2004-04-05
 *
 * @param $Table           Array             Array in which the value must be inserted
 * @param $Value           mixed or Array    Single value or array of values to insert in the array
 * @param $Position        Integer           After this position, the value must be inserted in the array [0..n]
 *
 * @return array                             The array with the value/array inserted, the init array otherwise
 */
 function array_insertElement($Table, $Value, $Position)
 {
     $TableSize = count($Table);
     if (($Position >= 0) && ($Position < $TableSize))
     {
         if (is_array($Value))
         {
             // We insert an array
             $Table = array_merge(array_slice($Table, 0, $Position + 1), $Value, array_slice($Table, $Position + 1));
         }
         else
         {
             // We insert an element
             $Table = array_merge(array_slice($Table, 0, $Position + 1), array($Value), array_slice($Table, $Position + 1));
         }
     }

     // $Table with the value/array inserted after the position $Position
     return $Table;
 }


/**
 * Extract an element of an array at a specified position
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-05-05
 *
 * @param $Table           Array             Array of which the element must be removed
 * @param $Position        Integer           Position of the element to remove
 *
 * @return array                             The array without the element, the init array otherwise
 */
 function array_extractElement($Table, $Position)
 {
     $TableSize = count($Table);
     if ($TableSize > 0)
     {
         if (is_string($Position))
         {
             // The position is a key
             $ArrayKeys = array_keys($Table);
             $Position = array_search($Position, $ArrayKeys);
             if ($Position !== FALSE)
             {
                 // This key exists
                 $Table = array_merge(array_slice($Table, 0, $Position), array_slice($Table, $Position + 1));
                 return $Table;
             }
         }
         else
         {
             // The position is an index
             if (($Position !== FALSE) && ($Position >= 0) && ($Position < $TableSize) && (!is_Null($Position)))
             {
                 // The position exists
                 $Table = array_merge(array_slice($Table, 0, $Position), array_slice($Table, $Position + 1));
                 return $Table;
             }
         }
     }

     return $Table;
 }


/**
 * Sort an array and keep the relation between the values and the keys
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2007-10-05
 *
 * @param $Table           Mixed array    Array to sort
 *
 * @return Mixed array     The array sorted and the relation between values and keys kept,
 *                         the same array otherwise
 */
 function array_sort_keep_keys($Table)
 {
     if ((is_array($Table)) && (count($Table) > 0))
     {
         $CompareFct = create_function('$a,$b', 'if ($a == $b) return 0; return ($a < $b) ? -1 : 1;');
         uasort($Table, $CompareFct);
     }

     return $Table;
 }


/**
 * Sort an array first by values, next by keys when they have the same value. This function keep
 * the relation between the values and the keys
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2013-11-20
 *
 * @param $Table           Mixed array    Array to sort
 *
 * @return Mixed array     The array sorted and the relation between values and keys kept,
 *                         the same array otherwise
 */
 function array_asort_then_ksort($Table)
 {
     $iSize = count($Table);
     if ((is_array($Table)) && ($iSize > 0))
     {
         $FinalArray = array();

         // First, sort by values
         asort($Table);

         // Next, sort by keys when they have the same value
         $iPos = 1;
         $ArrayKeys = array_keys($Table);
         $PreviousValue = $Table[$ArrayKeys[0]];
         $LocalArray[$ArrayKeys[0]] = $PreviousValue;
         while($iPos < $iSize)
         {
             // Search same values
             if ($PreviousValue === $Table[$ArrayKeys[$iPos]])
             {
                 $LocalArray[$ArrayKeys[$iPos]] = $Table[$ArrayKeys[$iPos]];
             }
             else
             {
                 ksort($LocalArray);
                 foreach($LocalArray as $k => $Value)
                 {
                     $FinalArray[$k] = $Value;
                 }

                 $PreviousValue = $Table[$ArrayKeys[$iPos]];
                 unset($LocalArray);
                 $LocalArray[$ArrayKeys[$iPos]] = $PreviousValue;
             }

             $iPos++;
         }

         // Sort the last values
         ksort($LocalArray);
         foreach($LocalArray as $k => $Value)
         {
             $FinalArray[$k] = $Value;
         }

         $Table = $FinalArray;
         unset($FinalArray, $ArrayKeys);
     }

     return $Table;
 }


/**
 * Filter an array thanks to another array which contains the values to keep in the first array
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-27
 *
 * @param $Table           Array          Array to filter
 * @param $Filter          Array          Values to keep in the first array
 * @param $Mode            Const          Mode of filtering
 *
 * @return array                          The array filtered with just the values contained in the second
 *                                        array, the input array no filtered otherwise
 */
 function array_filtered($Table, $Filter, $Mode = BY_VALUE_KEEP_VALUE)
 {
     // There is a filter?
     if ((is_array($Table)) && (count($Filter) > 0))
     {
         // There is a filter : we filter
         $ArrayTmp = array();

         switch($Mode)
         {
             case BY_KEY_KEEP_VALUE:
                  // We filter on the key value, but we keep the value set to the key
                  foreach($Table as $i => $CurrentValue)
                  {
                      if (in_array($i, $Filter))
                      {
                          $ArrayTmp[] = $CurrentValue;
                      }
                  }
                  break;

             case BY_KEY_KEEP_KEY:
                  // We filter on the key value and we keep the key
                  foreach($Table as $i => $CurrentValue)
                  {
                      if (in_array($i, $Filter))
                      {
                          $ArrayTmp[] = $i;
                      }
                  }
                  break;

             case BY_VALUE_KEEP_VALUE:
                  // We filter on the value contained by the key, but we keep the value
                  foreach($Table as $i => $CurrentValue)
                  {
                      if (in_array($CurrentValue, $Filter))
                      {
                          $ArrayTmp[] = $CurrentValue;
                      }
                  }
                  break;

             case BY_VALUE_KEEP_KEY:
                  // We filter on the value contained by the key, but we keep the key
                  foreach($Table as $i => $CurrentValue)
                  {
                      if (in_array($CurrentValue, $Filter))
                      {
                          $ArrayTmp[] = $i;
                      }
                  }
                  break;

              case BY_VALUE_KEEP_ASSOC_VALUE:
                  // We filter on the value contained by the key, but we keep the value in a associative array
                  foreach($Table as $i => $CurrentValue)
                  {
                      if (in_array($CurrentValue, $Filter))
                      {
                          $ArrayTmp[$i] = $CurrentValue;
                      }
                  }
                  break;

              case BY_VALUE_KEEP_ASSOC_KEY:
                  // We filter on the value contained by the key, but we keep the key in a associative array
                  foreach($Table as $i => $CurrentValue)
                  {
                      if (in_array($CurrentValue, $Filter))
                      {
                          $ArrayTmp[$i] = $i;
                      }
                  }
                  break;
         }

         return $ArrayTmp;
     }
     else
     {
         // No filter : we return the input table
         return $Table;
     }
 }


/**
 * Check if the given array contains values >, <, >=, or <= than a given value used to compare
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-29
 *
 * @param $Table             Array          Array to filter
 * @param $FilteringValue    Mixed          Value used to compare with the other values in the gieven array
 * @param $Mode              String         Mode of filtering (<, >, <=, >=)
 *
 * @return Boolean                          The array contains at least one value which recpecte the filtering mode
 */
 function array_ContainsValues($Table, $FilteringValue, $Mode)
 {
     $TableSize = count($Table);
     if ($TableSize > 0)
     {
         $i = 0;
         $ArrayKeys = array_keys($Table);
         switch($Mode)
         {
             case "<":
                 while($i < $TableSize)
                 {
                     if ($Table[$ArrayKeys[$i]] < $FilteringValue)
                     {
                         return TRUE;
                     }
                     $i++;
                 }
                 break;

             case "<=":
                 while($i < $TableSize)
                 {
                     if ($Table[$ArrayKeys[$i]] <= $FilteringValue)
                     {
                         return TRUE;
                     }
                     $i++;
                 }
                 break;

             case ">":
                 while($i < $TableSize)
                 {
                     if ($Table[$ArrayKeys[$i]] > $FilteringValue)
                     {
                         return TRUE;
                     }
                     $i++;
                 }
                 break;

             case ">=":
                 while($i < $TableSize)
                 {
                     if ($Table[$ArrayKeys[$i]] >= $FilteringValue)
                     {
                         return TRUE;
                     }
                     $i++;
                 }
                 break;

             default:
                 // mode "<"
                 while($i < $TableSize)
                 {
                     if ($Table[$ArrayKeys[$i]] < $FilteringValue)
                     {
                         return TRUE;
                     }
                     $i++;
                 }
                 break;
         }
     }

     return FALSE;
 }


/**
 * Give the position of a given value which must be between tow values of an array.
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-25
 *
 * @param Value            Mixed          Value which must be in an interval in the array
 * @param $Table           Array          Array in which we search the position of the value
 *
 * @return Integer                        Position of the interval, in the array, where the value is, -1 otherwise
 */
 function array_getPosInterval($Value, $Table)
 {
     if (count($Table) > 0)
     {
         foreach($Table as $i => $CurrentValue)
         {
             // We get the value of interval left bound
             if ($i == 0)
             {
                 // Because it's the first value of the array
                 $MinInterval = 0;
             }
             else
             {
                 $MinInterval = $Table[$i-1];
             }

             // We get the value of interval right bound
             $MaxInterval = $CurrentValue;

             // The value is in this interval?
             if (($Value >= $MinInterval) && ($Value <= $MaxInterval))
             {
                 // Yes : we return the position
                 return $i;
             }
         }
     }

     // ERROR
     return -1;
 }


//########################### TEXT PROCESSING FUNCTIONS ###########################
/**
 * Check if a value is a MD5 string
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2009-04-07
 *
 * @param $Value        String       String to check
 *
 * @return Boolean      TRUE if the value is a MD5 string, FALSE otherwise
 */
 function isMD5($Value)
 {
     if ((strlen($Value) == 32) && (preg_match("/[a-f0-9]{32,32}/", strToLower($Value)) == 1))
     {
         // MD5 value
         return true;
     }
     else
     {
         // Not a md5 value
         return false;
     }
 }


/**
 * Escape " characters one time in a string value
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-08-30
 *
 * @param $Value         String             String value to escape " characters
 *
 * @return String        Value with " characters escaped
 */
 function escapeSQLString($Value)
 {
     $iSize = strlen($Value);
     $sEscapedValue = '';
     
     for($i = 0; $i < $iSize; $i++)
     {
         if ($Value{$i} == '"')
         {
             // We check if the " character is already escaped
             if (($i > 0) && ($Value{$i - 1} != '\\'))
             {
                 // No
                 $sEscapedValue .= '\\"';
             }
             else
             {
                 // Yes
                 $sEscapedValue .= $Value{$i};
             }
         }
         else
         {
             $sEscapedValue .= $Value{$i};
         }
     }

     return $sEscapedValue;
 }


/**
 * Remove alias and some SQL instructions of an array containing specified fields for the SELECT
 * SQL instruction of a query
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-06-13
 *
 * @param $ArrayValues        Array of values      Array which contains the specified fields for the SELECT
 *                                                 instruction
 *
 * @return Array of Strings        SELECT Specified fields without alias or SQL instructions
 */
 function simplifySQLSELECTSpecifiedFields($ArrayValues)
 {
     if (count($ArrayValues) > 0)
     {
         foreach($ArrayValues as $f => $CurrentField)
         {
             // We remove the alias of the table name
             if (strpos($CurrentField, 'minDate') !== FALSE)
             {
                 // For the min date of an ask of work
                 $ArrayValues[$f] =  'minDate';
             }
             else if (strpos($CurrentField, 'maxDate') !== FALSE)
             {
                 // For the max date of an ask of work
                 $ArrayValues[$f] = 'maxDate';
             }
             else
             {
                 // Simple alias (ex : st.)
                 $ArrayValues[$f] = substr($CurrentField, strpos($CurrentField, '.') + 1);
             }
         }
     }

     return $ArrayValues;
 }


/**
 * Construct a string which contains the values of an array and usable by the SQL IN instruction
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-01-04
 *
 * @param $ArrayValues         Array of values             Array which contains the values uised to construct the final string
 *
 * @return String                                          String usable by the SQL IN instruction
 */
 function constructSQLINString($ArrayValues)
 {
     // According to the type of value, the function included '' or not
     if (count($ArrayValues) > 0)
     {
         switch(getType($ArrayValues[0]))
         {
             case "string":
                   $SQL_IN_String = "('".implode("', '", $ArrayValues)."')";
                   break;

             default:
                   $SQL_IN_String = "(".implode(", ", $ArrayValues).")";
                   break;
         }

         return $SQL_IN_String;
     }

     // No values
     return "";
 }


/**
 * Parse a string and replace spaces by the AND word and "+" by the OR word and return an array of values
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-05-09
 *
 * @param $Text                 String       String to parse
 *
 * @return Array of Strings                  Array of strings with AND and OR instructions, usable by
 *                                           the SQL LIKE instruction
 */
 function parseSQLLIKEString($Text)
 {
     $TextSize = strlen($Text);
     if ($TextSize > 0)
     {
         // Array result
         $ArrayResult = array(
                              array(),
                              array()
                             );

         $TextTmp = str_replace(array(" ", "+"), array("[#@#]", "[#@#]"), $Text);
         $ArrayTextTmp = explode("[#@#]", $TextTmp);

         // We store the first not empty value
         if ($ArrayTextTmp[0] != "")
         {
             $ArrayResult[0][] = "AND";
             $ArrayResult[1][] = $ArrayTextTmp[0];
         }

         $ArrayTextTmpSize = count($ArrayTextTmp);
         if ($ArrayTextTmpSize > 1)
         {
             // There are several values
             $NbChars = strlen($ArrayTextTmp[0]);
             for($i = 1 ; $i < $ArrayTextTmpSize ; $i++)
             {
                 // for each not empty value, we get the AND or OR
                 if ($ArrayTextTmp[$i] != "")
                 {
                     switch($Text[$NbChars])
                     {
                         case " ";
                             $ArrayResult[0][] = "AND";
                             $ArrayResult[1][] = $ArrayTextTmp[$i];
                             break;

                         case "+";
                             $ArrayResult[0][] = "OR";
                             $ArrayResult[1][] = $ArrayTextTmp[$i];
                             break;
                     }
                 }

                 $NbChars += strlen($ArrayTextTmp[$i]) + 1;
             }
         }

         // Return the result
         return $ArrayResult;
     }

     // ERROR : empty string to parse
     return array();
 }


/**
 * Format a text : replace the characters '<', '>', '\n' and '  ' or '   ' by '&lt', '&gt', '<br />' and
 * '&nbsp;&nbsp;' or '&nbsp;&nbsp;&nbsp;'. Remove too HTML and PHP tags.
 *
 * @author STNA/7SQ
 * @version 2.2
 *     - 2005-04-08 : take into account the JavaScript mode
 *     - 2006-01-27 : allow <a>, <i> and <b> html tags in text in JavaScript mode
 *     - 2010-03-03 : taken into account the RSS mode
 *
 * @since 2004-01-10
 *
 * @param $Text    String     Text to format
 * @param $Mode    String     Define the value to return
 *
 * @return String             Formated text
 */
 function formatText($Text, $Mode = "XHTML")
 {
     $ConvertedText = '';
     switch(strtoupper($Mode))
     {
         default:
         case "XHTML":
             // The text will be displayed by XHTML
             $Text = str_replace(array("<", ">"), array("&lt;", "&gt;"), $Text);

             $Replaced_Chars = array("\n", "  ", "   ");
             $Replacement_Chars = array("<br />", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;");
             $Text = str_replace($Replaced_Chars, $Replacement_Chars, $Text);

             $ConvertedText = addslashes(strip_tags($Text, "<br>"));
             break;

         case "JS":
             // The text will be displayed by Javascript
             $Replaced_Chars = array("\\\"", ";");
             $Replacement_Chars = array("", " ");
             $Text = str_replace($Replaced_Chars, $Replacement_Chars, $Text);

             $ConvertedText = strip_tags($Text, "<a><b><i>");
             break;

         case "RSS":
             // The text will be displayed by Javascript
             $Text = str_replace(array("&"), array("&amp;"), $Text);

             $CharsToReplace = array(
                                     "<" => "&lt;", ">" => "&gt;",
                                     "‡" => "&#224;", "‚" => "&#226;", "‰" => "&#228;",
                                     "Á" => "&#231;",
                                     "Ë" => "&#232;", "È" => "&#233;", "Í" => "&#234;", "Î" => "&#235;",
                                     "Ó" => "&#238;", "Ô" => "&#239;",
                                     "Ù" => "&#244;", "ˆ" => "&#246;",
                                     "˘" => "&#249;", "˚" => "&#251;"
                                    );
             $Replaced_Chars = array_keys($CharsToReplace);
             $Replacement_Chars = array_values($CharsToReplace);
             $ConvertedText = str_replace($Replaced_Chars, $Replacement_Chars, $Text);
             break;
     }

     return $ConvertedText;
 }


/**
 * Reverse function of the 'formatText' function
 *
 * @author STNA/7SQ
 * @version 2.4
 *     - 2004-07-24 : take into account the XML mode
 *     - 2004-08-11 : take into account the CSV mode
 *     - 2005-06-13 : take into account the ; for the CSV mode
 *     - 2008-02-04 : take into account the \ in the xml and replace it by \\
 *     - 2009-03-13 : patch a bug due to strip_tags which remove \n and patch
 *                    a bug in XML with \ and \0
 *
 * @since 2004-01-10
 *
 * @param $Text    String     Text to format
 * @param $Mode    String     Define the value to return
 *
 * @return String             Formated text
 */
 function invFormatText($Text, $Mode = "XHTML")
 {
     switch(strtoupper($Mode))
     {
         default:
         case "XHTML":
         case "TXT":
                 $Replaced_Chars = array("<br />", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;", "&lt;", "&gt;");
                 $Replacement_Chars = array("\n", "  ", "   ", "<",  ">");

                 return str_replace($Replaced_Chars, $Replacement_Chars, stripslashes($Text));
                 break;

         case "CSV":
                 // \0 -> \O
                 $Replaced_Chars = array("\\0", chr(9), chr(10), chr(13), "<br />", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;", "&lt;", "&gt;", ";");
                 $Replacement_Chars = array("\\O", " ", "", " ", "", "  ", "   ", "<",  ">", " ");

                 return str_replace($Replaced_Chars, $Replacement_Chars, stripslashes($Text));
                 break;

         case "XML":
                 $Replaced_Chars = array("\\", "<br />", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;", "&", "\\'", "\\\"");
                 $Replacement_Chars = array("\\\\", "<![CDATA[<br />]]>", "&#160;&#160;", "&#160;&#160;&#160;", "&amp;", "'", "\"");
                 $Result = str_replace($Replaced_Chars, $Replacement_Chars, $Text);
                 return stripslashes($Result);
                 break;


     }
 }


/**
 * Return the XHTML tag "&nbsp;" if the text is NULL or ""
 *
 * @author STNA/7SQ
 * @version 2.2
 *     - 2004-05-11 : take into account the mode
 *     - 2004-07-08 : take into account the XML mode
 *     - 2004-07-15 : take into account the TXT mode
 *
 * @since 2004-03-26
 *
 * @param $Text    String     Text to format
 * @param $Mode    String     Define the value to return
 *
 * @return String             "&nbsp;" or NULL if $Text is NULL or "", the $Text value otherwise
 */
 function nullFormatText($Text, $Mode = "XHTML")
 {
     if ((is_Null($Text)) || ($Text == ""))
     {
         switch(strtoupper($Mode))
         {
             default:
             case "XHTML":
                     return "&nbsp;";
                     break;

             case "XML":
                     return "&#160;";
                     break;

             case "TXT":
                     return "";
                     break;

             case "NULL":
                     return NULL;
                     break;
         }
     }
     else
     {
         return $Text;
     }
 }


/**
 * Return 0 if the text is NULL or ""
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-03-26
 *
 * @param $Text    String     Text to format
 *
 * @return Mixed              0 if $Text is NULL or "", the $Text value otherwise
 */
 function zeroFormatText($Text)
 {
     if ((is_Null($Text)) || ($Text == ""))
     {
         return "0";
     }
     else
     {
         return $Text;
     }
 }


/**
 * Return 0 if the text is NULL, "", 0 or FALSE
 *
 * @author STNA/7SQ
 * @version 1.1
 *    - 2010-03-11 : use empty() in the test
 *
 * @since 2004-05-19
 *
 * @param $Value    Mixed     Value which can be NULL, a boolean, an integer or a String
 *
 * @return Mixed              0 if $Value is NULL, "", FALSE or 0, the $Value value otherwise
 */
 function zeroFormatValue($Value)
 {
     if (empty($Value))
     {
         return "0";
     }
     else
     {
         return $Value;
     }
 }


/**
 * Return the field value if the field exists, a default value otherwise
 *
 * @author STNA/7SQ
 * @version 2.0
 *     - 2004-05-03 : with a 0 value in the $_POST[field], empty() return false. We check if the fieldname is a key in the $_POST array
 *
 * @since 2004-04-11
 *
 * @param $Field        Field of the $_POST array     Field name of the $POST array
 * @param $Default      Mixed                         Value returned if the field doesn't exist
 *
 * @return Mixed                                      The field value if the field exists, a default value otherwise
 */
 function existedPOSTFieldValue($Field, $Default)
 {
     if (array_key_exists($Field, $_POST))
     {
         // The field exists
         return $_POST[$Field];
     }
     else
     {
         // The field doesn't exist
         return $Default;
     }
 }


/**
 * Return the field value if the field exists, a default value otherwise
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-09
 *
 * @param $Field        Field of the $_GET array      Field name of the $GET array
 * @param $Default      Mixed                         Value returned if the field doesn't exist
 *
 * @return Mixed                                      The field value if the field exists, a default value otherwise
 */
 function existedGETFieldValue($Field, $Default)
 {
     if (array_key_exists($Field, $_GET))
     {
         // The field exists
         return $_GET[$Field];
     }
     else
     {
         // The field doesn't exist
         return $Default;
     }
 }


/**
 * Return the initials of a person.
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2007-10-03 : taken into account the mode of computation
 *     - 2010-02-03 : taken into account the "-" in the lastname to
 *                    compute the initials
 *
 * @since 2004-07-06
 *
 * @param $Firstname     String     The firstname of the person
 * @param $Lastname      String     The lastname of the person
 * @param $Mode          Const      The mode of computation of the initials
 *
 * @return String        The initials of the person, an empty string otherwise
 */
 function getInitials($Firstname, $Lastname, $Mode = BOTH_INITIALS)
 {
     if ((strlen($Firstname) > 1) && (strlen($Lastname) > 1))
     {
         // We get the first letter of the firstname (or the 2 first if it's a composed firstname) of the person
         $Initials = "";

         if (($Mode == BOTH_INITIALS) || ($Mode == FIRSTNAME_INITIALS))
         {
             $ArrayFirstnames = explode("-", $Firstname);
             foreach($ArrayFirstnames as $CurrentValue)
             {
                 // We get the first letter
                 $Initials .= strtoupper($CurrentValue[0]);
             }
         }

         // We get the 2 first letters of the lastname
         if ($Mode == BOTH_INITIALS)
         {
             $ArrayLastnames = explode(" ", $Lastname);
             if (count($ArrayLastnames) == 1)
             {
                 // Check if there is a "-" in the lastname
                 $ArrayLastnames = explode("-", $Lastname);
                 if (count($ArrayLastnames) == 1)
                 {
                     $Initials .= strtoupper(substr($ArrayLastnames[0], 0, 2));
                 }
                 else
                 {
                     foreach($ArrayLastnames as $CurrentValue)
                     {
                         // We get the first letter
                         $Initials .= strtoupper($CurrentValue[0]);
                     }
                 }
             }
             else
             {
                 foreach($ArrayLastnames as $CurrentValue)
                 {
                     // We get the first letter
                     $Initials .= strtoupper($CurrentValue[0]);
                 }
             }
         }

         return $Initials;
     }

     // ERROR
     return "";
 }


/**
 * Return the "if value" if the condition is true, the "else value" otherwise
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-16
 *
 * @param $Condition     String     The condition to evaluate
 * @param $IfValue       mixed      The value to return if the condition is true
 * @param $IfValue       mixed      The value to return if the condition is false
 *
 * @return mixed                    The value in relation with the result of the condition
 */
 function getConditionnalValue($Condition, $IfValue, $ElseValue)
 {
     if (eval("return ($Condition);"))
     {
         return $IfValue;
     }
     else
     {
         return $ElseValue;
     }
 }


//########################### FILES FUNCTIONS ##########################################
/**
 * Check if the extension of a file is allowed
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-09
 *
 * @param $Filename             String                  Filename which has to own an allowed extension
 * @param $AllowedExtensions    Array of Strings        List of the allowed extensions
 *
 * @return Boolean                                      TRUE if the filename owns an allowed extension, FALSE otherwise
 */
 function isFileOwnsAllowedExtension($Filename, $AllowedExtensions)
 {
     $InfosFile = pathInfo($Filename);

     // Result
     return  in_array(strToLower($InfosFile["extension"]), $AllowedExtensions);
 }


/**
 * Give a valide name to a filename : no È‡Ô..., no /\,&!@#...
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-05-19
 *
 * @param $Filename             String       Filename to modify
 *
 * @return String                            A valide filename without È‡ËÓ/\&,#@...
 */
 function formatFilename($Filename)
 {
     // We replace some characters
     $TmpFilename = strtr($Filename,
                          "¿¡¬√ƒ≈‡·‚„‰Â“”‘’÷ÿÚÛÙıˆ¯»… ÀËÈÍÎ«ÁÃÕŒœÏÌÓÔŸ⁄€‹˘˙˚¸ˇ—Ò",
                          "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn"
                         );

     $TmpFilename = str_replace(
                                array(" ", "'"),
                                array("_", "_"),
                                $TmpFilename
                               );

     // We check if all characters are valide characters
     $NbChars = strlen($TmpFilename);
     $NewFilename = "";
     for($i = 0 ; $i < $NbChars ; $i++)
     {
         // Get the current character
         $Char = ord($TmpFilename[$i]);

         // >= 0 and <= 9, >= A and <= Z, >= a and <= z, = "-", = "_", = "."
         if ((($Char >= 48) && ($Char <= 57)) || (($Char >= 65) && ($Char <= 90)) || (($Char >= 97) && ($Char <= 122)) || ($Char == 61) || ($Char == 95) || ($Char == 46))
         {
             // We keep the character
             $NewFilename .= $TmpFilename[$i];
         }
     }

     return $NewFilename;
 }


/**
 * Save strings in a file
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-09
 *
 * @param $Filename           String                  Path Filename used to store the data
 * @param $Mode               String                  Mode used to write in the file
 * @param $TabData            Array of Strings        Data to save in the file
 *
 * @return Boolean                                    TRUE if the data are saved in the file, FALSE otherwise
 */
 function saveToFile($Filename, $Mode, $TabData)
 {
     if (($Filename != "") && ($Mode != "") && (count($TabData) > 0))
     {
         // Initialize the file
         if ($fp = fopen($Filename, $Mode))
         {
             foreach($TabData as $i => $CurrentValue)
             {
                 fwrite($fp, $CurrentValue."\n");
             }

             // Close the file
             fclose($fp);

             return TRUE;
         }
     }

     // ERROR
     return FALSE;
 }


/**
 * Put in a string the content of a file
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-07-26
 *
 * @param $Filename           String                  Path Filename used to store the data
 * @param $Mode               String                  Mode used to write in the file
 *
 * @return String                                     The content of the file, an empty string otherwise
 */
 function getContentFile($Filename, $Mode = "rt")
 {
     if ($Filename != "")
     {
         // Initialize the file
         if ($fp = fopen($Filename, $Mode))
         {
             // Read the file
             $ContentFile = "";
             while(!feof($fp))
             {
                 $ContentFile .= fread($fp, 1024);
             }

             // Close the file
             fclose($fp);

             return $ContentFile;
         }
     }

     // ERROR
     return "";
 }


/**
 * Get in an array the content of a CSV file
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2011-07-26
 *
 * @param $Filename           String                  Path Filename used to store the data
 * @param $Length             Integer                 Size of the buffer to get line
 * @param $Delimiter          String                  Character between 2 fields
 * @param $Enclosure          String                  Character used to enclose a field
 * @param $Escape             String                  Character to escape the enclosure character
 * @param $Mode               String                  Mode used to write in the file
 *
 * @return String                                     The content of the file, an empty string otherwise
 */
 function getContentCSVFile($Filename, $Length = 100000, $Delimiter = ';', $Enclosure = '"', $Escape = '\\', $Mode = 'r')
 {
     if (!empty($Filename))
     {
         $ArrayContentFile = array();
         $fp = fopen($Filename, $Mode);
         if ($fp !== FALSE)
         {
             // Read each line, even there is a \n in the line
             while (($Data = fgetcsv($fp, $Length, $Delimiter, $Enclosure)) !== FALSE)
             {
                 $ArrayContentFile[] = $Data;
             }
         }

         fclose($fp);

         return $ArrayContentFile;
     }

     // ERROR
     return array();
 }


/**
 * Check if a remote file exists
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2010-01-22 : add @ for fsockopen() to hide warning in local execution
 *
 * @since 2009-01-05
 *
 * @param $url           String        Url of the remote file to check if it exists
 *
 * @return Boolean or Integer          True if the remote file exists, 1 if invalid url host
 *                                     2 if unable to connect to remote host, false otherwise
 */
 function remote_file_exists($url)
 {
     $head = "";
     $url_p = parse_url ($url);

     if (isset ($url_p["host"]))
     {
         $host = $url_p["host"];
     }
     else
     {
         return 1;
     }

     if (isset ($url_p["path"]))
     {
         $path = $url_p["path"];
     }
     else
     {
         $path = "";
     }

     $fp = @fsockopen ($host, 80, $errno, $errstr, 20);
     if (!$fp)
     {
         return 2;
     }
     else
     {
         $parse = parse_url($url);
         $host = $parse['host'];

         fputs($fp, "HEAD ".$url." HTTP/1.1\r\n" );
         fputs($fp, "HOST: ".$host."\r\n" );
         fputs($fp, "Connection: close\r\n\r\n" );
         $headers = "";
         while (!feof ($fp))
         {
             $headers .= fgets ($fp, 128);
         }
     }

     fclose ($fp);
     $arr_headers = explode("\n", $headers);
     $return = false;
     if (isset ($arr_headers[0]))
     {
         $return = strpos ($arr_headers[0], "404" ) === false;
     }

     return $return;
 }


//########################### SESSIONS FUNCTIONS ##########################################
/**
 * Create a variable with a value in the user session
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-10-13
 *
 * @param $Fieldname          String      Name of the variable to create
 * @param $Value              Mixed       Variable value
 *
 * @return Boolean                        TRUE if the variable is set, FALSE otherwise
 */
 function saveSessionValue($Fieldname, $Value)
 {
     $_SESSION["$Fieldname"] = $Value;

     // check if the variable is set
     return isSet($_SESSION["$Fieldname"]);
 }


/**
 * Create several variables with a value in the user session
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-10-13
 *
 * @param $ArrayFieldsValues        Mixed array      Associated array. In key, the name of the variable,
 *                                                   in value, the variable value
 *
 * @return Boolean                                   TRUE if all variables are set, FALSE otherwise
 */
 function saveSeveralSessionValues($ArrayFieldsValues)
 {
     foreach($ArrayFieldsValues as $Fieldname => $Value)
     {
         $_SESSION["$Fieldname"] = $Value;

         // check if the variable is set
         if (!isSet($_SESSION["$Fieldname"]))
         {
             return FALSE;
         }
     }

     // All variables are set
     return TRUE;
 }


/**
 * Destroy a variable in the user session
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-10-13
 *
 * @param $Fieldname          String      Name of the variable to destroy
 *
 * @return Boolean                        TRUE if the variable is destroyed, FALSE otherwise
 */
 function deleteSessionValue($Fieldname)
 {
     session_unregister("$Fieldname");

     // check if the variable is deleted
     return !isSet($_SESSION["$Fieldname"]);
 }


/**
 * Destroy several variables in the user session
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2006-02-20 : patch the way to delete values stored in the session
 *
 * @since 2004-10-13
 *
 * @param $ArrayFieldnames        Array of strings        Names of variables to destroy
 *
 * @return Boolean                                        TRUE if all variables are destroyed, FALSE otherwise
 */
 function deleteSeveralSessionValues($ArrayFieldnames)
 {
     $bDestroyed = TRUE;
     foreach($ArrayFieldnames as $Fieldname)
     {
         session_unregister("$Fieldname");

         // check if the variable is destroyed
         if (isSet($_SESSION["$Fieldname"]))
         {
             // No
             $bDestroyed =  FALSE;
         }
     }

     // All variables are deleted
     return $bDestroyed;
 }


/**
 * Get the value of a variable in the user session. If the variable doesn't exist, a default value
 * is returned
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-10-13
 *
 * @param $Fieldname          String      Name of the variable to to get the value
 * @param $DefaultValue       Mixed       Default value to return if the varible doesn't exist
 *
 * @return Mixed                          The value of the variable if it exists, the default value otherwise
 */
 function getSessionValue($Fieldname, $DefaultValue = "")
 {
     // check if the variable is set
     if (isSet($_SESSION["$Fieldname"]))
     {
         return $_SESSION["$Fieldname"];
     }
     else
     {
         return $DefaultValue;
     }
 }


//########################### URL FUNCTIONS ###############################################
/**
 * Get the relative depth of an url in relation with the root directory of the application
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2005-01-27
 *
 * @param $Url                String      URL for which we want to know the relative depth
 * @param $UrlType            Const       Type of the URL (http or hdd path)
 *
 * @return String                         Several times the "../" string in relation with the depth
 */
 function getRelativeUrlDepth($Url, $UrlType = HTTP)
 {
     switch($UrlType)
     {
         case PATH:
             // The url is a hdd path c:\ or /var/
             $ArrayUrl = explode("/", str_replace(array("\\"), array("/"), $Url));

             // Get the position of the www directory
             $Pos = array_search("www", $ArrayUrl);
             if ($Pos !== FALSE)
             {
                 // Directory found
                 return str_repeat("../", max(0, count($ArrayUrl) - ($Pos + 3)));
             }
             else
             {
                 // Directory not found
                 return "";
             }
             break;

         case HTTP:
         default:
             // The url is a http:// url
             $ArrayUrl = explode("/", $Url);
             return str_repeat("../", max(0, count($ArrayUrl) - 3));
         break;
     }
 }


/**
 * Get the name of the browser used
 *
 * @author STNA/7SQ
 * @version 1.1
 *     - 2007-07-18 : get the OS name too
 *
 * @since 2005-01-27
 *
 *
 * @return String                         The name of the browser, an empty string otherwise
 */
 function getBrowserName()
 {
     // We get the OS name
     // ex of User-Agent : Mozilla/5.0 (Windows; U; Windows NT 5.0; fr-FR; rv:1.7.5) Gecko/20041108 Firefox/1.0
     $iPosStart = strpos($_SERVER["HTTP_USER_AGENT"], '(');
     $iPosEnd = strpos($_SERVER["HTTP_USER_AGENT"], ';');
     $sOSname = '';
     if (($iPosStart !== FALSE) && ($iPosEnd !== FALSE))
     {
         // We extract the OS name
         $sOSname = strtoupper(substr($_SERVER["HTTP_USER_AGENT"], $iPosStart + 1, $iPosEnd - $iPosStart - 1));
     }

     if (eregi('msie', $_SERVER["HTTP_USER_AGENT"]) && !eregi('opera', $_SERVER["HTTP_USER_AGENT"]))
     {
         // Internet Explorer
         return "IE";
     }
     elseif (eregi('opera', $_SERVER["HTTP_USER_AGENT"]))
     {
         // Opera
         return "OPERA";
     }
     elseif (eregi('Mozilla/4.', $_SERVER["HTTP_USER_AGENT"]))
     {
         // Netscape 4.x
         return "NS4-$sOSname";
     }
     elseif (eregi('Mozilla/5.0', $_SERVER["HTTP_USER_AGENT"]) && !eregi('Konqueror', $_SERVER["HTTP_USER_AGENT"]))
     {
         // Netscape 6
         return "NS6-$sOSname";
     }
     else
     {
         // Autres navigateurs
         return "-$sOSname";
     }
 }


/**
 * Get the version of the browser used
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-10-02
 *
 *
 * @return String        The version of the browser, an empty string otherwise
 */
 function getBrowserVersion()
 {
     $sBrowserVersion = '';
     if (eregi('Mozilla/5.0', $_SERVER["HTTP_USER_AGENT"]) && !eregi('Konqueror', $_SERVER["HTTP_USER_AGENT"]))
     {
         // We search the version of Firefox
         $iPos = strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), 'firefox');
         if ($iPos !== FALSE)
         {
             $sFirefoxVersion = substr($_SERVER["HTTP_USER_AGENT"], $iPos);
             $ArrayVersion = explode('/', $sFirefoxVersion);
             $sBrowserVersion = $ArrayVersion[1];
         }
     }

     return $sBrowserVersion;
 }


/**
 * Redirect the user to the login index.php page if he isn't loggued
 *
 * @author STNA/7SQ
 * @version 1.2
 *     - 2006-07-12 : manage the requested url when the user isn't logged
 *     - 2009-04-14 : taken into account session in database
 *
 * @since 2005-02-16
 *
 * @param $Url                String      URL of the page that the user whant to display
 *
 */
 function setRedirectionToLoginPage($Url = "")
 {
     // We check if the user is logged
     if (isSet($_SESSION["SupportMemberID"]))
     {
         $UserID = $_SESSION["SupportMemberID"];
     }
     elseif (isSet($_SESSION["CustomerID"]))
     {
         $UserID = $_SESSION["CustomerID"];
     }
     else
     {
         // No logged user
         $UserID = NULL;
     }

     if (is_null($UserID))
     {
         // Define the Url used for the redirection
         if ($Url == "")
         {
             $Url = $_SERVER["PHP_SELF"];
         }

         // We save the requested url
         $_SESSION["PreviousUrl"] = $Url;
         if ($GLOBALS["QUERY_STRING"] != "")
         {
             // We take into account the parameters
             $_SESSION["PreviousUrl"] .= "?".$GLOBALS["QUERY_STRING"];
         }

         $ArrayCurrentURL = explode("/", $Url);

         // Redirection if the user isn't loggued, except he already is in the index.php page
         if (is_null($UserID))
         {
             // We remove the first "../"
             $RedirectUrl = substr(getRelativeUrlDepth($Url), 3)."index.php";

             // Redirection
             header("location: $RedirectUrl");
         }
     }
 }


//########################### ERROR FUNCTIONS #############################################
/**
 * Manage errors that occured
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2006-02-28
 *
 * @param $ErrLevel         Enum             Type of error
 * @param $ErrMsg           String           Explain the error
 * @param $ErrFile          String           PHP filename where the error occured
 * @param $ErrLine          Integer          Line number in the php script where the error occured
 * @param $ErrContext       Mixed array      All variables used when the error occured
 */
 function errorManager($ErrLevel, $ErrMsg, $ErrFile, $ErrLine, $ErrContext = array())
 {
     $ErrorType = "";
     switch($ErrLevel)
     {
         case E_WARNING:
             $ErrorType = "E_WARNING";
             break;

         case E_NOTICE:
             $ErrorType = "E_NOTICE";
             break;

         case E_USER_ERROR:
             $ErrorType = "E_USER_ERROR";
             break;

         case E_USER_WARNING:
             $ErrorType = "E_USER_WARNING";
             break;
     }

     if ($ErrorType != "")
     {
         switch($GLOBALS["CONF_ERROR_MODE"])
         {
             case ERROR_ECHO_MODE:
                 // Errors are displayed
                 echo "\n$ErrorType : $ErrMsg, $ErrFile, L$ErrLine\n";
                 break;

             case ERROR_FILE_MODE:
                 // Errors are stored in a log file
                 if ($GLOBALS["CONF_ERROR_LOG_FILE"] != "")
                 {
                     $fp = fopen($GLOBALS["CONF_ERROR_LOG_FILE"], "a+");
                     if ($fp != FALSE)
                     {
                         $sLog = "[".date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"]." ".$GLOBALS["CONF_TIME_DISPLAY_FORMAT"])."] $ErrorType : $ErrMsg, $ErrFile, L$ErrLine\n";
                         fwrite($fp, $sLog) ;
                         fclose($fp) ;
                     }
                 }
                 break;
         }
     }
 }
?>