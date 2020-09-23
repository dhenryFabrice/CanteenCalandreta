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
 * Interface module : XHTML Graphic high level forms library used to manage the donations.
 *
 * @author Christophe Javouhey
 * @version 3.3
 * @since 2016-05-31
 */


/**
 * Display the form to submit a new donation or update a donation, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2018-05-16 : taken into account $CONF_DEFAULT_VALUES_SET to set default values
 *
 * @since 2016-05-31
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $DonationID               String                ID of the donation to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view donations
 */
 function displayDetailsDonationForm($DbConnection, $DonationID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a donation
         $cUserAccess = FCT_ACT_NO_RIGHTS;
         if (empty($DonationID))
         {
             // Creation mode
             if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             if ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
             elseif ((isset($AccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
             {
                 // Partial read mode
                 $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsDonation", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationDonation('".$GLOBALS["LANG_ERROR_JS_DONATION_REFERENCE"]."', '".$GLOBALS['LANG_ERROR_JS_DONATION_RELATIONSHIP']
                                            ."', '".$GLOBALS["LANG_ERROR_JS_DONATION_LASTNAME"]."', '".$GLOBALS["LANG_ERROR_JS_DONATION_FIRSTNAME"]
                                            ."', '".$GLOBALS["LANG_ERROR_JS_DONATION_ADDRESS"]."', '".$GLOBALS["LANG_ERROR_JS_TOWN"]
                                            ."', '".$GLOBALS["LANG_ERROR_JS_DONATION_MAIN_EMAIL"]."', '".$GLOBALS["LANG_ERROR_JS_DONATION_SECOND_EMAIL"]
                                            ."', '".$GLOBALS["LANG_ERROR_JS_PAYMENT_AMOUNT"]."', '".$GLOBALS["LANG_ERROR_JS_BANK_NAME"]
                                            ."', '".$GLOBALS["LANG_ERROR_JS_PAYMENT_CHECK_NB"]."')");

             // Display the table (frame) where the form will take place
             $DonationTitle = $GLOBALS["LANG_DONATION"];
             if ($DonationID > 0)
             {
                 // Display the ID of the donation
                 $DonationTitle .= " ($DonationID)";
             }

             openStyledFrame($DonationTitle, "Frame", "Frame", "DetailsNews");

             // <<< Donation ID >>>
             if ($DonationID == 0)
             {
                 // Define default values to create the new donation
                 $Reference = "&nbsp;";

                 // Set default values if defined
                 $DonationEntityDefaultValue = 0;
                 if (isset($GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationEntity']))
                 {
                     $DonationEntityDefaultValue = $GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationEntity'];
                 }

                 $DonationTypeDefaultValue = 0;
                 if (isset($GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationType']))
                 {
                     $DonationTypeDefaultValue = $GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationType'];
                 }

                 $DonationNatureDefaultValue = 0;
                 if (isset($GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationNature']))
                 {
                     $DonationNatureDefaultValue = $GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationNature'];
                 }

                 $DonationPaymentModeDefaultValue = 0;
                 if (isset($GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationPaymentMode']))
                 {
                     $DonationPaymentModeDefaultValue = $GLOBALS['CONF_DEFAULT_VALUES_SET']['Fields']['DonationPaymentMode'];
                 }

                 $DonationRecord = array(
                                         "DonationReference" => generateDonationRef($DbConnection),
                                         "DonationEntity" => $DonationEntityDefaultValue,
                                         "DonationLastname" => '',
                                         "DonationFirstname" => '',
                                         "DonationAddress" => '',
                                         "DonationPhone" => '',
                                         "DonationMainEmail" => '',
                                         "DonationSecondEmail" => '',
                                         "DonationFamilyRelationship" => 0,
                                         "DonationReceptionDate" => date('Y-m-d'),
                                         "DonationType" => $DonationTypeDefaultValue,
                                         "DonationNature" => $DonationNatureDefaultValue,
                                         "DonationValue" => '',
                                         "DonationReason" => '',
                                         "DonationPaymentMode" => $DonationPaymentModeDefaultValue,
                                         "DonationPaymentCheckNb" => '',
                                         "BankID" => 0,
                                         "TownID" => 0,
                                         "FamilyID" => 0
                                        );

                 $SchoolYear = getSchoolYear(date('Y-m-d'));
                 $bClosed = FALSE;
             }
             else
             {
                 if (isExistingDonation($DbConnection, $DonationID))
                 {
                     // We get the details of the donation
                     $DonationRecord = getTableRecordInfos($DbConnection, "Donations", $DonationID);
                     $SchoolYear = getSchoolYear($DonationRecord['DonationReceptionDate']);

                     // We check if the donation is closed
                     $bClosed = FALSE;
                     if ($GLOBALS['CONF_DONATION_CLOSED_AFTER_NB_YEARS'] > 0)
                     {
                         // Compute the closing date of the donation
                         $ClosingDate = date('Y-01-01', strtotime("+".($GLOBALS['CONF_DONATION_CLOSED_AFTER_NB_YEARS'] + 1)." years",
                                                                  strtotime(date('Y-01-01',
                                                                            strtotime($DonationRecord['DonationReceptionDate'])))));

                         if (strtotime($ClosingDate) < strtotime(date('Y-m-d')))
                         {
                             // The donation is closed
                             $bClosed = TRUE;
                         }
                     }
                 }
                 else
                 {
                     // Error, the donation doesn't exist
                     $DonationID = 0;
                     $SchoolYear = getSchoolYear(date('Y-m-d'));
                     $bClosed = TRUE;
                 }
             }

             // <<< DonationReference INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Reference = stripslashes($DonationRecord["DonationReference"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($DonationID == 0)
                     {
                         $Reference = generateInputField("sReference", "text", "20", "20", $GLOBALS["LANG_DONATION_REFERENCE_TIP"],
                                                         $DonationRecord["DonationReference"]);
                     }
                     else
                     {
                         // The reference isn't updatable
                         $Reference = stripslashes($DonationRecord["DonationReference"]);
                     }
                     break;
             }

             // <<< DonationEntity SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Entity = $GLOBALS['CONF_DONATION_ENTITIES'][$DonationRecord['DonationEntity']];
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $Entity = $GLOBALS['CONF_DONATION_ENTITIES'][$DonationRecord['DonationEntity']];
                     }
                     else
                     {
                         $Entity = generateSelectField("lEntity", array_keys($GLOBALS['CONF_DONATION_ENTITIES']),
                                                       array_values($GLOBALS['CONF_DONATION_ENTITIES']),
                                                       $DonationRecord['DonationEntity']);
                     }
                     break;
             }

             // <<< FamilyID SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     // We get infos about the selected town
                     if (empty($DonationRecord['FamilyID']))
                     {
                         $Family = "-";
                     }
                     else
                     {
                         $ArrayInfosFamily = getTableRecordInfos($DbConnection, 'Families', $DonationRecord['FamilyID']);
                         $Family = $ArrayInfosFamily['FamilyLastname'];
                         unset($ArrayInfosFamily);
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     // Generate the list of activated families
                     if ($bClosed)
                     {
                         if (empty($DonationRecord['FamilyID']))
                         {
                             $Family = "-";
                         }
                         else
                         {
                             $ArrayInfosFamily = getTableRecordInfos($DbConnection, 'Families', $DonationRecord['FamilyID']);
                             $Family = $ArrayInfosFamily['FamilyLastname'];
                             unset($ArrayInfosFamily);
                         }
                     }
                     else
                     {
                         $ArrayFamilyID = array(0);
                         $ArrayFamilyLastname = array('-');

                         $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($SchoolYear)),
                                                         "FamilyLastname", 1, 0);

                         if ((isset($ArrayFamilies['FamilyID'])) && (!empty($ArrayFamilies['FamilyID'])))
                         {
                             $ArrayFamilyID = array_merge($ArrayFamilyID, $ArrayFamilies['FamilyID']);
                             $ArrayFamilyLastname = array_merge($ArrayFamilyLastname, $ArrayFamilies['FamilyLastname']);
                         }

                         $Family = generateSelectField("lFamilyID", $ArrayFamilyID, $ArrayFamilyLastname, $DonationRecord['FamilyID'], "");
                     }
                     break;
             }

             // <<< DonationRelationship SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Relationship = $GLOBALS['CONF_DONATION_FAMILY_RELATIONSHIP'][$DonationRecord['DonationFamilyRelationship']];
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $Relationship = $GLOBALS['CONF_DONATION_FAMILY_RELATIONSHIP'][$DonationRecord['DonationFamilyRelationship']];
                     }
                     else
                     {
                         $Relationship = generateSelectField("lRelationship", array_keys($GLOBALS['CONF_DONATION_FAMILY_RELATIONSHIP']),
                                                             array_values($GLOBALS['CONF_DONATION_FAMILY_RELATIONSHIP']),
                                                             $DonationRecord['DonationFamilyRelationship']);
                     }
                     break;
             }

             // <<< DonationLastname INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Lastname = stripslashes($DonationRecord["DonationLastname"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $Lastname = stripslashes($DonationRecord["DonationLastname"]);
                     }
                     else
                     {
                         $Lastname = generateInputField("sLastname", "text", "100", "30", $GLOBALS["LANG_DONATION_LASTNAME_TIP"],
                                                        $DonationRecord["DonationLastname"]);
                     }
                     break;
             }

             // <<< DonationFirstname INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Firstname = stripslashes($DonationRecord["DonationFirstname"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $Firstname = stripslashes($DonationRecord["DonationFirstname"]);
                     }
                     else
                     {
                         $Firstname = generateInputField("sFirstname", "text", "25", "20", $GLOBALS["LANG_DONATION_FIRSTNAME_TIP"],
                                                         $DonationRecord["DonationFirstname"]);
                     }
                     break;
             }

             // <<< DonationAddress INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $Address = stripslashes($DonationRecord["DonationAddress"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if ($bClosed)
                     {
                         $Address = stripslashes($DonationRecord["DonationAddress"]);
                     }
                     else
                     {
                         $Address = generateInputField("sAddress", "text", "255", "80", $GLOBALS["LANG_DONATION_ADDRESS_TIP"],
                                                       $DonationRecord["DonationAddress"]);
                     }
                     break;
             }

             // <<< DonationPhone INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Phone = stripslashes($DonationRecord["DonationPhone"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $Phone = stripslashes($DonationRecord["DonationPhone"]);
                     }
                     else
                     {
                         $Phone = generateInputField("sPhoneNumber", "text", "30", "20", $GLOBALS["LANG_PHONE_NUMBER_TIP"],
                                                     $DonationRecord["DonationPhone"]);
                     }
                     break;
             }

             // <<< TownID SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     // We get infos about the selected town
                     $ArrayInfosTown = getTableRecordInfos($DbConnection, 'Towns', $DonationRecord['TownID']);
                     $Town = $ArrayInfosTown['TownName'].' ('.$ArrayInfosTown['TownCode'].')';
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if ($bClosed)
                     {
                         // We get infos about the selected town
                         $ArrayInfosTown = getTableRecordInfos($DbConnection, 'Towns', $DonationRecord['TownID']);
                         $Town = $ArrayInfosTown['TownName'].' ('.$ArrayInfosTown['TownCode'].')';
                     }
                     else
                     {
                         $DbResultList = $DbConnection->query("SELECT TownID, TownName, TownCode FROM Towns ORDER BY TownName");
                         $Town = '&nbsp;';
                         if (!DB::isError($DbResultList))
                         {
                             if (empty($DonationID))
                             {
                                 $ArrayTownID = array(0);
                                 $ArrayTownInfos = array('');
                             }
                             else
                             {
                                 $ArrayTownID = array();
                                 $ArrayTownInfos = array();
                             }

                             while($RecordList = $DbResultList->fetchRow(DB_FETCHMODE_ASSOC))
                             {
                                 $ArrayTownID[] = $RecordList["TownID"];
                                 $ArrayTownInfos[] = $RecordList["TownName"].' ('.$RecordList["TownCode"].')';
                             }

                             $Town = generateSelectField("lTownID", $ArrayTownID, $ArrayTownInfos, $DonationRecord['TownID']);

                             // Display a button to add a new town
                             $Town .= generateStyledPictureHyperlink($GLOBALS["CONF_ADD_ICON"], "../Canteen/AddTown.php?Cr=".md5('')."&amp;Id=",
                                                                     $GLOBALS["LANG_ADD_TOWN_TIP"], 'Affectation', '_blank');
                         }
                     }
                     break;
             }

             // <<< DonationMainEmail INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $MainEmail = nullFormatText(stripslashes($DonationRecord["DonationMainEmail"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if ($bClosed)
                     {
                         $MainEmail = nullFormatText(stripslashes($DonationRecord["DonationMainEmail"]));
                     }
                     else
                     {
                         $MainEmail = generateInputField("sMainEmail", "text", "100", "80", $GLOBALS["LANG_DONATION_MAIN_EMAIL_TIP"],
                                                         $DonationRecord["DonationMainEmail"]);
                     }
                     break;
             }

             // <<< DonationSecondEmail INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $SecondEmail = nullFormatText(stripslashes($DonationRecord["DonationSecondEmail"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if ($bClosed)
                     {
                         $SecondEmail = stripslashes(nullFormatText($DonationRecord["DonationSecondEmail"]));
                     }
                     else
                     {
                         $SecondEmail = generateInputField("sSecondEmail", "text", "100", "80", $GLOBALS["LANG_DONATION_SECOND_EMAIL_TIP"],
                                                           $DonationRecord["DonationSecondEmail"]);
                     }
                     break;
             }

             // <<< Payment receipt date INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (!empty($DonationRecord["DonationReceptionDate"]))
                     {
                         $DonationReceptionDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                       strtotime($DonationRecord["DonationReceptionDate"]));
                     }
                     else
                     {
                         $DonationReceptionDate = '-';
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         if (!empty($DonationRecord["DonationReceptionDate"]))
                         {
                             $DonationReceptionDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                           strtotime($DonationRecord["DonationReceptionDate"]));
                         }
                         else
                         {
                             $DonationReceptionDate = '-';
                         }
                     }
                     else
                     {
                         if (empty($DonationRecord["DonationReceptionDate"]))
                         {
                             $DonationReceptionDate = '';
                         }
                         else
                         {
                             $DonationReceptionDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                           strtotime($DonationRecord["DonationReceptionDate"]));
                         }

                         $DonationReceptionDate = generateInputField("donationDate", "text", "10", "10",
                                                                     $GLOBALS["LANG_DONATION_DATE_TIP"], $DonationReceptionDate, TRUE);

                         // Insert the javascript to use the calendar component
                         $DonationReceptionDate .= "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n\t DonationDateCalendar = new dynCalendar('DonationDateCalendar', 'calendarCallbackDonationDate', '".$GLOBALS['CONF_ROOT_DIRECTORY']."Common/JSCalendar/images/'); \n\t//-->\n</script>\n";
                     }
                     break;
             }

             // <<< DonationType SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $DonationType = $GLOBALS['CONF_DONATION_TYPES'][$DonationRecord['DonationType']];
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $DonationType = $GLOBALS['CONF_DONATION_TYPES'][$DonationRecord['DonationType']];
                     }
                     else
                     {
                         $DonationType = generateSelectField("lDonationType", array_keys($GLOBALS['CONF_DONATION_TYPES']),
                                                             array_values($GLOBALS['CONF_DONATION_TYPES']),
                                                             $DonationRecord['DonationType']);
                     }
                     break;
             }

             // <<< DonationNature SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $DonationNature = $GLOBALS['CONF_DONATION_NATURES'][$DonationRecord['DonationNature']];
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $DonationNature = $GLOBALS['CONF_DONATION_NATURES'][$DonationRecord['DonationNature']];
                     }
                     else
                     {
                         $DonationNature = generateSelectField("lDonationNature", array_keys($GLOBALS['CONF_DONATION_NATURES']),
                                                               array_values($GLOBALS['CONF_DONATION_NATURES']),
                                                               $DonationRecord['DonationNature']);
                     }
                     break;
             }

             // <<< DonationReason INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Reason = nullFormatText(stripslashes($DonationRecord["DonationReason"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $Reason = nullFormatText(stripslashes($DonationRecord["DonationReason"]));
                     }
                     else
                     {
                         $Reason = generateInputField("sReason", "text", "255", "20", $GLOBALS["LANG_DONATION_REASON_TIP"],
                                                      $DonationRecord["DonationReason"]);
                     }
                     break;
             }

             // <<< Amount INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $DonationValue = stripslashes($DonationRecord["DonationValue"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $DonationValue = stripslashes($DonationRecord["DonationValue"]);
                     }
                     else
                     {
                         $DonationValue = generateInputField("fAmount", "text", "10", "10", $GLOBALS["LANG_DONATION_AMOUNT_TIP"],
                                                             $DonationRecord["DonationValue"]);
                     }
                     break;
             }

             // <<< Banks SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (!empty($DonationRecord["BankID"]))
                     {
                         $RecordBank = getTableRecordInfos($DbConnection, "Banks", $DonationRecord["BankID"]);
                         $Bank = $RecordBank['BankName'];
                         unset($RecordBank);
                     }
                     else
                     {
                         $Bank = '-';
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         if (!empty($DonationRecord["BankID"]))
                         {
                             $RecordBank = getTableRecordInfos($DbConnection, "Banks", $DonationRecord["BankID"]);
                             $Bank = $RecordBank['BankName'];
                             unset($RecordBank);
                         }
                         else
                         {
                             $Bank = '-';
                         }
                     }
                     else
                     {
                         $ArrayBanks = getTableContent($DbConnection, 'Banks', 'BankName');
                         $ArrayBankID = array(0);
                         $ArrayBankNames = array('');
                         if ((isset($ArrayBanks['BankID'])) && (!empty($ArrayBanks['BankID'])))
                         {
                             $ArrayBankID = array_merge($ArrayBankID, $ArrayBanks['BankID']);
                             $ArrayBankNames = array_merge($ArrayBankNames, $ArrayBanks['BankName']);
                         }

                         $Bank = generateSelectField("lBankID", $ArrayBankID, $ArrayBankNames, $DonationRecord["BankID"], "");

                         // Display a button to add a new bank
                         $Bank .= generateStyledPictureHyperlink($GLOBALS["CONF_ADD_ICON"], "../Canteen/AddBank.php?Cr=".md5('')."&Id=",
                                                                 $GLOBALS["LANG_SUPPORT_UPDATE_BANK_PAGE_CREATE_BANK_TIP"],
                                                                 'Affectation', '_blank');
                     }
                     break;
             }

             // <<< Payment mode SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $PaymentMode = "-";
                     if (isset($GLOBALS["CONF_PAYMENTS_MODES"][$DonationRecord["DonationPaymentMode"]]))
                     {
                         $PaymentMode = $GLOBALS["CONF_PAYMENTS_MODES"][$DonationRecord["DonationPaymentMode"]];
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $PaymentMode = "-";
                         if (isset($GLOBALS["CONF_PAYMENTS_MODES"][$DonationRecord["DonationPaymentMode"]]))
                         {
                             $PaymentMode = $GLOBALS["CONF_PAYMENTS_MODES"][$DonationRecord["DonationPaymentMode"]];
                         }
                     }
                     else
                     {
                         $ArrayPaymentModes = array_merge($GLOBALS["CONF_PAYMENTS_MODES"], array("-"));
                         $PaymentMode = generateSelectField("lPaymentMode", array_keys($ArrayPaymentModes), $ArrayPaymentModes,
                                                            $DonationRecord["DonationPaymentMode"], "");
                     }
                     break;
             }

             // <<< Check number INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $CheckNb = nullFormatText(stripslashes($DonationRecord["DonationPaymentCheckNb"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     if ($bClosed)
                     {
                         $CheckNb = nullFormatText(stripslashes($DonationRecord["DonationPaymentCheckNb"]));
                     }
                     else
                     {
                         $CheckNb = generateInputField("sCheckNb", "text", "30", "20", $GLOBALS["LANG_PAYMENT_CHECK_NB_TIP"],
                                                       $DonationRecord["DonationPaymentCheckNb"]);
                     }
                     break;
             }

             // Display the form
             echo "<table id=\"DonationDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."*</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_DONATION_ENTITY"]."</td><td class=\"Value\">$Entity</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY"]."</td><td class=\"Value\">$Family</td><td class=\"Label\">".$GLOBALS["LANG_DONATION_FAMILY_RELATIONSHIP"]."</td><td class=\"Value\">$Relationship</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DONATION_LASTNAME"]."*</td><td class=\"Value\">$Lastname</td><td class=\"Label\">".$GLOBALS["LANG_DONATION_FIRSTNAME"]."*</td><td class=\"Value\">$Firstname</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_ADDRESS"]."*</td><td class=\"Value\" colspan=\"3\">$Address</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_TOWN"]."*</td><td class=\"Value\">$Town</td><td class=\"Label\">".$GLOBALS["LANG_PHONE_NUMBER"]."</td><td class=\"Value\">$Phone</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DONATION_MAIN_EMAIL"]."</td><td class=\"Value\" colspan=\"3\">$MainEmail</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DONATION_SECOND_EMAIL"]."</td><td class=\"Value\" colspan=\"3\">$SecondEmail</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DONATION_DATE"]."</td><td class=\"Value\">$DonationReceptionDate</td><td class=\"Label\">".$GLOBALS["LANG_DONATION_TYPE"]."</td><td class=\"Value\">$DonationType</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DONATION_NATURE"]."</td><td class=\"Value\">$DonationNature</td><td class=\"Label\">".$GLOBALS["LANG_DONATION_REASON"]."</td><td class=\"Value\">$Reason</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_PAYMENT_MODE"]."</td><td class=\"Value\">$PaymentMode</td><td class=\"Label\">".$GLOBALS["LANG_DONATION_AMOUNT"]."*</td><td class=\"Value\">$DonationValue</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_BANK"]."</td><td class=\"Value\">$Bank</td><td class=\"Label\">".$GLOBALS["LANG_PAYMENT_CHECK_NB"]."</td><td class=\"Value\">$CheckNb</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidDonationID", "hidden", "", "", "", $DonationID);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if (!$bClosed)
                     {
                         // We display the buttons
                         echo "<table class=\"validation\">\n<tr>\n\t<td>";
                         insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"],
                                          $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                         echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                         insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"],
                                          $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);

                         if ($DonationID > 0)
                         {
                             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                             insertInputField("bGenerate", "submit", "", "", $GLOBALS["LANG_DONATION_TAX_RECEIPT_REGENERATE_BUTTON_TIP"],
                                              $GLOBALS["LANG_DONATION_TAX_RECEIPT_REGENERATE_BUTTON_CAPTION"]);
                         }
                         echo "</td>\n</tr>\n</table>\n";
                     }
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a donation
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to search a donation in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.2
 *     - 2016-12-27 : patch a bug with previous/next page and wrong total of donations
 *     - 2019-05-09 : ucfirst() for the year field
 *
 * @since 2016-05-31
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some donations
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the donations found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the donations. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about a donation. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update donations
 * @param $$ArrayDiplayedFormFields    Array of Strings     Contains fieldnames of the form to display or not
 */
 function displaySearchDonationForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to donations list
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Write mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }
         elseif ((isset($AccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
         {
             // Partial read mode
             $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormSearchDonations", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             if (empty($ArrayDiplayedFormFields))
             {
                 // Display all available fields of the form
                 $ArrayDiplayedFormFields = array(
                                                  "lYear" => TRUE,
                                                  "sLastname" => TRUE,
                                                  "lDonationType" => TRUE,
                                                  "lDonationNature" => TRUE
                                                 );
             }

             $SchoolYear = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lYear'])) && ($ArrayDiplayedFormFields['lYear']))
             {
                 // <<< Year SELECTFIELD >>>
                 // Create the years list
                 $ArrayYears = array(0 => '');

                 $MinDate = getDonationMinDate($DbConnection);
                 if (empty($MinDate))
                 {
                     $MinDate = date('Y-m-d');
                 }

                 $MinYear = date('Y', strtotime($MinDate));
                 $MaxYear = date('Y');

                 $GeneratedYears = range($MinYear, $MaxYear);
                 foreach($GeneratedYears as $y => $CurrentYear)
                 {
                     $ArrayYears[$CurrentYear] = $CurrentYear;
                 }

                 if ((isset($TabParams['Year'])) && (count($TabParams['Year']) > 0))
                 {
                     $SelectedItem = $TabParams['Year'][0];
                 }
                 else
                 {
                     // Default value : no item selected
                     $SelectedItem = 0;
                 }

                 $YearsList = generateSelectField("lYear", array_keys($ArrayYears), array_values($ArrayYears),
                                                   zeroFormatValue(existedPOSTFieldValue("lYear",
                                                                                         existedGETFieldValue("lYear",
                                                                                                              $SelectedItem))));
             }

             $sLastname = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sLastname'])) && ($ArrayDiplayedFormFields['sLastname']))
             {
                 // Donator lastname input text
                 $sLastname = generateInputField("sLastname", "text", "100", "13", $GLOBALS["LANG_DONATION_LASTNAME_TIP"],
                                                 stripslashes(strip_tags(existedPOSTFieldValue("sLastname",
                                                                                               stripslashes(existedGETFieldValue("sLastname", ""))))));
             }

             $DonationType = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lDonationType'])) && ($ArrayDiplayedFormFields['lDonationType']))
             {
                 $ArrayDonationTypesID = array_merge(array(-1), array_keys($GLOBALS["CONF_DONATION_TYPES"]));
                 $ArrayDonationTypes = array_merge(array(""), array_values($GLOBALS["CONF_DONATION_TYPES"]));

                 if ((isset($TabParams['DonationType'])) && (count($TabParams['DonationType']) > 0))
                 {
                     $SelectedItem = $TabParams['DonationType'][0];
                 }
                 else
                 {
                     // Default value : no item selected
                     $SelectedItem = -1;
                 }

                 $DonationType = generateSelectField("lDonationType", $ArrayDonationTypesID, $ArrayDonationTypes,
                                                     zeroFormatValue(existedPOSTFieldValue("lDonationType",
                                                                                           existedGETFieldValue("lDonationType",
                                                                                                                $SelectedItem))));
             }

             $DonationNature = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['lDonationNature'])) && ($ArrayDiplayedFormFields['lDonationNature']))
             {
                 $ArrayDonationNaturesID = array_merge(array(-1), array_keys($GLOBALS["CONF_DONATION_NATURES"]));
                 $ArrayDonationNatures = array_merge(array(""), array_values($GLOBALS["CONF_DONATION_NATURES"]));

                 if ((isset($TabParams['DonationNature'])) && (count($TabParams['DonationNature']) > 0))
                 {
                     $SelectedItem = $TabParams['DonationNature'][0];
                 }
                 else
                 {
                     // Default value : no item selected
                     $SelectedItem = -1;
                 }

                 $DonationNature = generateSelectField("lDonationNature", $ArrayDonationNaturesID, $ArrayDonationNatures,
                                                       zeroFormatValue(existedPOSTFieldValue("lDonationNature",
                                                                                             existedGETFieldValue("lDonationNature",
                                                                                                                  $SelectedItem))));
             }

             // Display the form
             echo "<table id=\"DonationsList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".ucfirst($GLOBALS["LANG_YEAR"])."</td><td class=\"Value\">$YearsList</td><td class=\"Label\">".$GLOBALS['LANG_DONATION_LASTNAME']."</td><td class=\"Value\">$sLastname</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_DONATION_TYPE"]."</td><td class=\"Value\">$DonationType</td><td class=\"Label\">".$GLOBALS['LANG_DONATION_NATURE']."</td><td class=\"Value\">$DonationNature</td>\n</tr>\n";
             echo "</table>\n";

             // Display the hidden fields
             insertInputField("hidOrderByField", "hidden", "", "", "", $OrderBy);
             insertInputField("hidOnPrint", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnPrint", existedGETFieldValue("hidOnPrint", ""))));
             insertInputField("hidOnExport", "hidden", "", "", "", zeroFormatValue(existedPOSTFieldValue("hidOnExport", existedGETFieldValue("hidOnExport", ""))));
             insertInputField("hidExportFilename", "hidden", "", "", "", existedPOSTFieldValue("hidExportFilename", existedGETFieldValue("hidExportFilename", "")));
             closeStyledFrame();

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td>\n</tr>\n</table>\n";

             closeForm();

             // The supporter has executed a search
             $NbTabParams = count($TabParams);
             if ($NbTabParams > 0)
             {
                 displayBR(2);

                 $ArrayCaptions = array($GLOBALS["LANG_REFERENCE"], $GLOBALS["LANG_DONATION_LASTNAME"],$GLOBALS["LANG_DONATION_DATE"],
                                        $GLOBALS["LANG_DONATION_AMOUNT"]);
                 $ArraySorts = array("DonationReference", "DonationLastname", "DonationReceptionDate", "DonationValue");

                 // Order by instruction
                 if ((abs($OrderBy) <= count($ArraySorts)) && ($OrderBy != 0))
                 {
                     $StrOrderBy = $ArraySorts[abs($OrderBy) - 1];
                     if ($OrderBy < 0)
                     {
                         $StrOrderBy .= " DESC";
                     }
                 }
                 else
                 {
                     $StrOrderBy = "DonationLastname ASC, DonationFirstname ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchDonations($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only donations of the page
                     $ArrayRecords = dbSearchDonations($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // There are some donations found
                     foreach($ArrayRecords["DonationID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the donation reference
                             $ArrayData[0][] = $ArrayRecords["DonationReference"][$i];
                         }
                         else
                         {
                             // We display the donation reference with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["DonationReference"][$i], $ArrayRecords["DonationID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $DonationLastname = $ArrayRecords["DonationLastname"][$i].' '.$ArrayRecords["DonationFirstname"][$i];

                         if (!empty($ArrayRecords["FamilyID"][$i]))
                         {
                             // Set a hyperlink to the linked family
                             $DonationLastname = generateAowIDHyperlink($DonationLastname, $ArrayRecords["FamilyID"][$i],
                                                                        "../Canteen/UpdateFamily.php",
                                                                        $ArrayRecords["FamilyLastname"][$i]." : "
                                                                        .$GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], "", "_blank");
                         }

                         if ($ArrayRecords["DonationEntity"][$i] > 0)
                         {
                             $DonationLastname .= ' ('.substr($GLOBALS['CONF_DONATION_ENTITIES'][$ArrayRecords["DonationEntity"][$i]], 0, 3).')';
                         }

                         $ArrayData[1][] = $DonationLastname;

                         $ArrayData[2][] = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"],
                                                strtotime($ArrayRecords["DonationReceptionDate"][$i]));

                         $ArrayData[3][] = $ArrayRecords["DonationValue"][$i].' '.$GLOBALS['CONF_PAYMENTS_UNIT'];
                     }

                     // Totals
                     $ArrayTotals = getdbSearchDonationsTotalsByNature($DbConnection, $TabParams);
                     $fTotalDonations = array_sum($ArrayTotals);
                     $fTotalSpecificDonations = 0;
                     foreach($GLOBALS['CONF_DONATION_NATURES_TOTAL'] as $td => $iNature)
                     {
                         if (isset($ArrayTotals[$iNature]))
                         {
                             $fTotalSpecificDonations += $ArrayTotals[$iNature];
                         }
                     }

                     // Display the table which contains the donations found
                     $ArraySortedFields = array("1", "2", "3", "4");

                     displayStyledTable($ArrayCaptions, $ArraySortedFields, $SortFct, $ArrayData, '', '', '', '',
                                        array(), $OrderBy, array());

                     // Display the previous and next links
                     $NoPage = 0;
                     if ($Page <= 1)
                     {
                         $PreviousLink = '';
                     }
                     else
                     {
                         $NoPage = $Page - 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             // GET form
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['Year']))
                                 {
                                     $CurrentValue = $TabParams['Year'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $PreviousLink .= "&amp;lYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $PreviousLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $PreviousLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $PreviousLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }

                     if ($Page < ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]))
                     {
                         $NoPage = $Page + 1;

                         // We get the parameters of the GET form or the POST form
                         if (count($_POST) == 0)
                         {
                             if (count($_GET) == 0)
                             {
                                 // No form submitted
                                 $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                                 if (isset($TabParams['Year']))
                                 {
                                     $CurrentValue = $TabParams['Year'];
                                     if (is_array($CurrentValue))
                                     {
                                         // The value is an array
                                         $CurrentValue = implode("_", $CurrentValue);
                                     }
                                     $NextLink .= "&amp;lYear=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                             else
                             {
                                 // GET form
                                 $NextLink = "$ProcessFormPage?";
                                 foreach($_GET as $i => $CurrentValue)
                                 {
                                     if ($i == "Pg")
                                     {
                                         $CurrentValue = $NoPage;
                                     }
                                     $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                                 }
                             }
                         }
                         else
                         {
                             // POST form
                             $NextLink = "$ProcessFormPage?Pg=$NoPage&amp;Ob=$OrderBy";
                             foreach($_POST as $i => $CurrentValue)
                             {
                                 if (is_array($CurrentValue))
                                 {
                                     // The value is an array
                                     $CurrentValue = implode("_", $CurrentValue);
                                 }

                                 $NextLink .= "&amp;$i=".urlencode(str_replace(array("&", "+"), array("&amp;", "@@@"), $CurrentValue));
                             }
                         }
                     }
                     else
                     {
                         $NextLink = '';
                     }

                     displayPreviousNext("&nbsp;".$GLOBALS["LANG_PREVIOUS"], $PreviousLink, $GLOBALS["LANG_NEXT"]."&nbsp;", $NextLink,
                                         '', $Page, ceil($NbRecords / $GLOBALS["CONF_RECORDS_PER_PAGE"]));

                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NB_RECORDS_FOUND'].$NbRecords.generateBR(2);
                     echo $GLOBALS['LANG_SUPPORT_DONATIONS_LIST_PAGE_SPECIFIC_TOTAL']." : <em>$fTotalSpecificDonations ".$GLOBALS['CONF_PAYMENTS_UNIT']."</em> ".generateBR(1);
                     echo "<strong>".$GLOBALS['LANG_TOTAL']." : $fTotalDonations ".$GLOBALS['CONF_PAYMENTS_UNIT']."</strong>";
                     closeParagraph();
                 }
                 else
                 {
                     // No alias found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of donations
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The user isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }


/**
 * Display the form to generate tax receipts of donations of a year, in the current web page, in the
 * graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2016-06-08
 *
 * @param $DbConnection         DB object             Object of the opened database connection
 * @param $ProcessFormPage      String                URL of the page which will process the form
 * @param $Year                 Integer               Year used to generate tax receipts of donations
 * @param $AccessRules          Array of Integers     List used to select only some support members
 *                                                    allowed to create or update donations and tax receipts
 */
 function displayGenerateDonationTaxReceiptsForm($DbConnection, $ProcessFormPage, $Year, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to generate tax receipts of donations
         $cUserAccess = FCT_ACT_NO_RIGHTS;

         // Creation mode
         if ((isset($AccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_CREATE])))
         {
             $cUserAccess = FCT_ACT_CREATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_UPDATE])))
         {
             // Update mode
             $cUserAccess = FCT_ACT_UPDATE;
         }
         elseif ((isset($AccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $AccessRules[FCT_ACT_READ_ONLY])))
         {
             // Read mode
             $cUserAccess = FCT_ACT_READ_ONLY;
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
         {
             // Open a form
             openForm("FormGenerateTaxReceipts", "post", "$ProcessFormPage", "", "");

             // Display the years list to generate tax receipts
             openParagraph('toolbar');

             // Get the min date of the donations
             $MinDate = getDonationMinDate($DbConnection);
             if (empty($MinDate))
             {
                 // Last year if no donation in the database
                 $MinDate = date('Y-m-01', strtotime("last year"));
             }

             $MaxDate = date('Y-m-t', strtotime("last year"));
             $ArrayYears = array_keys(getPeriodIntervalsStats($MinDate, $MaxDate, "y"));

             echo generateSelectField("lYear", $ArrayYears, $ArrayYears, $Year, "onChangeSelectedYearMonth(this.value)");

             closeParagraph();

             displayBR(2);

             echo "<table class=\"validation\">\n<tr>\n\t<td>";
             insertInputField("bGenerate", "submit", "", "", $GLOBALS["LANG_DONATION_TAX_RECEIPTS_GENERATE_BUTTON_TIP"],
                              $GLOBALS["LANG_DONATION_TAX_RECEIPTS_GENERATE_BUTTON_CAPTION"]);
             echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
             insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
             echo "</td></tr>\n</table>\n";

             closeForm();
         }
         else
         {
             // No access right
             openParagraph('ErrorMsg');
             echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
             closeParagraph();
         }
     }
     else
     {
         // The supporter isn't logged
         openParagraph('ErrorMsg');
         echo $GLOBALS["LANG_ERROR_NOT_LOGGED"];
         closeParagraph();
     }
 }
?>