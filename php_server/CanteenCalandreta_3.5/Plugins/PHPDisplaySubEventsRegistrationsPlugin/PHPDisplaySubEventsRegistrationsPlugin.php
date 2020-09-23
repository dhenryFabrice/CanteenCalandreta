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
 * JS plugin displaying registrations of sub-events module : for some groups of users, get families registered on
 * sub-events of the parent event
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-06-20 : taken into account $CONF_CHARSET
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2013-10-14
 */


 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 session_start();

 $XmlData = '';

 if (isSet($_SESSION["SupportMemberID"]))
 {
     // Connection to the database
     $DbCon = dbConnection();

     // Load all configuration variables from database
     loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                          'CONF_CLASSROOMS'));

     // Get info about the displayed parent event
     $EventID = trim(strip_tags($_GET['Id']));
     if (isExistingEvent($DbCon, $EventID))
     {
         // We check if the event has sub-events
         $ArrayTreeEvents = getEventsTree($DbCon, $EventID, array(1), 'EventTitle');
         if ((!empty($ArrayTreeEvents)) && (count($ArrayTreeEvents['EventID']) > 1))
         {
             // Get registered families to the event and sub events
             $ArrayConcernedFamilies = array();
             foreach($ArrayTreeEvents['EventID'] as $i => $CurrEventID)
             {
                 $ArrayRegistrations = dbSearchEventRegistration($DbCon, array("EventID" => $CurrEventID), "FamilyLastname", 1, 0);
                 if ((isset($ArrayRegistrations['FamilyID'])) && (!empty($ArrayRegistrations['FamilyID'])))
                 {
                     foreach($ArrayRegistrations['FamilyID'] as $f => $CurrFamilyID)
                     {
                         if (!isset($ArrayConcernedFamilies[$CurrFamilyID]))
                         {
                             $ArrayConcernedFamilies[$CurrFamilyID] = array(
                                                                            'FamilyLastname' => $ArrayRegistrations['FamilyLastname'][$f],
                                                                            'EventID' => $CurrEventID,
                                                                            'EventTitle' => $ArrayTreeEvents['EventTitle'][$i],
                                                                            'EventRegistrationComment' => $ArrayRegistrations['EventRegistrationComment'][$f]
                                                                           );
                         }
                         else
                         {
                             if (!empty($ArrayRegistrations['EventRegistrationComment'][$f]))
                             {
                                 if (empty($ArrayConcernedFamilies[$CurrFamilyID]['EventRegistrationComment']))
                                 {
                                     // We set the comment
                                     $ArrayConcernedFamilies[$CurrFamilyID]['EventRegistrationComment'] = $ArrayRegistrations['EventRegistrationComment'][$f];
                                 }
                                 else
                                 {
                                     // We concat the comment
                                     $ArrayConcernedFamilies[$CurrFamilyID]['EventRegistrationComment'] .= " / ".$ArrayRegistrations['EventRegistrationComment'][$f];
                                 }
                             }
                         }
                     }
                 }
             }

             $XmlData = xmlOpenDocument();
             foreach($ArrayConcernedFamilies as $FamilyID => $ArrayValues)
             {
                 $ArrayParams = array(
                                      'eventid' => $ArrayValues['EventID'],
                                      'eventtitle' => invFormatText($ArrayValues['EventTitle'], "XML"),
                                      'familyid' => $FamilyID,
                                      'familyurl' => "../Canteen/UpdateFamily.php?Cr=".md5($FamilyID)."&amp;Id=$FamilyID",
                                      'familylastname' => invFormatText($ArrayValues['FamilyLastname'], "XML"),
                                      'eventregistrationcomment' => invFormatText($ArrayValues['EventRegistrationComment'], "XML")
                                     );

                 $XmlData .= xmlTag("RegisteredFamily", "", $ArrayParams);
             }

             $XmlData .= xmlCloseDocument();
         }
     }

     // Release the connection to the database
     dbDisconnection($DbCon);
 }

 header("Content-type: application/xml; charset=".strtolower($CONF_CHARSET));
 echo $XmlData;
?>
