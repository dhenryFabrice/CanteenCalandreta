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
 * PHP not approval==ed documents plugin module : display not approved document for the logged user
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2019-06-19
 */


 // Include the graphic primitives library
 require '../../GUI/GraphicInterface.php';

 require_once("DB.php");

 session_start();

 $XmlData = '';
 if (isSet($_SESSION["SupportMemberID"]))
 {
     // We analyse the parameters of the request
     if ((array_key_exists('getDocuments', $_GET)) && (strip_tags(trim($_GET['getDocuments'])) == 1))
     {
         // Connection to the database
         $DbCon = dbConnection();

         // Condition
         $sCondition = '';
         if ((isset($_SESSION['FamilyID'])) && (!empty($_SESSION['FamilyID'])))
         {
             // Get SupportMemberID linked to the family of the logged supporter
             $ArraySupportMembers = dbSearchSupportMember($DbCon, array('FamilyID' => array($_SESSION['FamilyID'])), "SupportMemberLastname", 1, 0);
             if ((isset($ArraySupportMembers['SupportMemberID'])) && (!empty($ArraySupportMembers['SupportMemberID'])))
             {
                 $sCondition = " AND dfa.SupportMemberID IN ".constructSQLINString($ArraySupportMembers['SupportMemberID']);
             }
         }
         else
         {
             $sCondition = " AND dfa.SupportMemberID = ".$_SESSION["SupportMemberID"];
         }

         if (!empty($sCondition))
         {
             $DbResult = $DbCon->query("SELECT da.DocumentApprovalID, da.DocumentApprovalName, da.DocumentApprovalType, da.DocumentApprovalDate, dfa.DocumentFamilyApprovalID
                                        FROM DocumentsApprovals da LEFT JOIN DocumentsFamiliesApprovals dfa ON (da.DocumentApprovalID = dfa.DocumentApprovalID $sCondition)
                                        HAVING dfa.DocumentFamilyApprovalID IS NULL
                                        ORDER BY da.DocumentApprovalDate, da.DocumentApprovalName");

             if (!DB::isError($DbResult))
             {
                 if ($DbResult->numRows() > 0)
                 {
                     $ArrayDocuments = array(
                                             'DocumentApprovalID' => array(),
                                             'DocumentApprovalName' => array(),
                                             'DocumentApprovalType' => array(),
                                             'DocumentApprovalDate' => array(),
                                            );

                     while($Record = $DbResult->fetchRow(DB_FETCHMODE_ASSOC))
                     {
                         $ArrayDocuments['DocumentApprovalID'][] = $Record['DocumentApprovalID'];
                         $ArrayDocuments['DocumentApprovalName'][] = $Record['DocumentApprovalName'];
                         $ArrayDocuments['DocumentApprovalType'][] = $CONF_DOCUMENTS_APPROVALS_TYPES[$Record['DocumentApprovalType']];
                         $ArrayDocuments['DocumentApprovalDate'][] = date($CONF_DATE_DISPLAY_FORMAT, strtotime($Record['DocumentApprovalDate']));
                     }

                     $XmlData = xmlOpenDocument();

                     foreach($ArrayDocuments['DocumentApprovalID'] as $d => $DocumentApprovalID)
                     {
                         $url = $CONF_URL_SUPPORT."Canteen/UpdateDocumentApproval.php?Cr=".md5($DocumentApprovalID)."&amp;Id=$DocumentApprovalID";

                         $ArrayParams = array(
                                              'name' => invFormatText($ArrayDocuments['DocumentApprovalName'][$d], "XML"),
                                              'type' => invFormatText($ArrayDocuments['DocumentApprovalType'][$d], "XML"),
                                              'date' => $ArrayDocuments['DocumentApprovalDate'][$d],
                                              'url' => $url
                                             );

                         $XmlData .= xmlTag("approval-document", "", $ArrayParams);
                     }

                     $XmlData .= xmlCloseDocument();
                 }
             }
         }

         // Release the connection to the database
         dbDisconnection($DbCon);
     }
 }
 else
 {
     $XmlData = xmlOpenDocument();
     $XmlData .= xmlCloseDocument();
 }

 header("Content-type: application/xml; charset=iso-8859-1");
 echo $XmlData;
?>
