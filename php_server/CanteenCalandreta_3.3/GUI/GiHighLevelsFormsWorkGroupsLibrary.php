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
 * Interface module : XHTML Graphic high level forms library used to manage the workgroups and registrations to workgroups.
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2015-10-12
 */


/**
 * Display the form to submit a new workgroup or update a workgroup, in the current row of the table of the web
 * page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-09-13 : allow referent to register some families
 *                    (taken into account $CONF_COOP_WORKGROUP_ALLOW_REGISTRATIONS_FOR_REFERENTS and
 *                    $CONF_COOP_WORKGROUP_ALLOW_UNREGISTRATIONS_FOR_USERS) and display the number
 *                    of registrations
 *
 * @since 2015-10-12
 *
 * @param $DbConnection             DB object             Object of the opened database connection
 * @param $WorkGroupID              String                ID of the workgroup to display
 * @param $ProcessFormPage          String                URL of the page which will process the form
 * @param $AccessRules              Array of Integers     List used to select only some support members
 *                                                        allowed to create, update or view workgroups
 */
 function displayDetailsWorkGroupForm($DbConnection, $WorkGroupID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to create or update a workgroup
         if (is_array($AccessRules))
         {
             $WorkGroupAccessRules = $AccessRules[0];
             $RegistrationAccessRules = $AccessRules[1];
         }
         else
         {
             $WorkGroupAccessRules = $AccessRules;
             $RegistrationAccessRules = NULL;
         }

         $cUserAccess = FCT_ACT_NO_RIGHTS;
         $cUserOtherAccess = FCT_ACT_NO_RIGHTS;
         if (empty($WorkGroupID))
         {
             // Creation mode
             if ((isset($WorkGroupAccessRules[FCT_ACT_CREATE])) && (in_array($_SESSION["SupportMemberStateID"], $WorkGroupAccessRules[FCT_ACT_CREATE])))
             {
                 $cUserAccess = FCT_ACT_CREATE;
             }
         }
         else
         {
             // Access to the workgroup
             if ((isset($WorkGroupAccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $WorkGroupAccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($WorkGroupAccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $WorkGroupAccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserAccess = FCT_ACT_READ_ONLY;
             }
             elseif ((isset($WorkGroupAccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $WorkGroupAccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
             {
                 // Partial read mode
                 $cUserAccess = FCT_ACT_PARTIAL_READ_ONLY;
             }

             // Access to the registrations
             if ((isset($RegistrationAccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserOtherAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($RegistrationAccessRules[FCT_ACT_UPDATE])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_UPDATE])))
             {
                 // Update mode
                 $cUserOtherAccess = FCT_ACT_UPDATE;
             }
             elseif ((isset($RegistrationAccessRules[FCT_ACT_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_READ_ONLY])))
             {
                 // Read mode
                 $cUserOtherAccess = FCT_ACT_READ_ONLY;
             }
             elseif ((isset($RegistrationAccessRules[FCT_ACT_PARTIAL_READ_ONLY])) && (in_array($_SESSION["SupportMemberStateID"], $RegistrationAccessRules[FCT_ACT_PARTIAL_READ_ONLY])))
             {
                 // Partial read mode
                 $cUserOtherAccess = FCT_ACT_PARTIAL_READ_ONLY;
             }
         }

         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE, FCT_ACT_READ_ONLY, FCT_ACT_PARTIAL_READ_ONLY)))
         {
             // Open a form
             openForm("FormDetailsWorkGroup", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationWorkGroup('".$GLOBALS["LANG_ERROR_JS_WORKGROUP_NAME"]."', '"
                                             .$GLOBALS["LANG_ERROR_JS_WORKGROUP_EMAIL"]."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_WORKGROUP"], "Frame", "Frame", "DetailsNews");

             // Get the FamilyID of the logged user
             $LoggedFamilyID = $_SESSION['FamilyID'];
             $bCanDeleteRegistration = FALSE;
             $bCanAddRegistrationForOther = FALSE;
             $bIsReferent = FALSE;

             // <<< WorkGroup ID >>>
             if ($WorkGroupID == 0)
             {
                 // Define default values to create the new workgroup
                 $Reference = "&nbsp;";
                 $WorkGroupRecord = array(
                                          "WorkGroupName" => '',
                                          "WorkGroupDescription" => '',
                                          "WorkGroupEmail" => ''
                                         );
             }
             else
             {
                 if (isExistingWorkGroup($DbConnection, $WorkGroupID))
                 {
                     // We get the details of the workgroup
                     $WorkGroupRecord = getTableRecordInfos($DbConnection, "WorkGroups", $WorkGroupID);
                     $Reference = $WorkGroupID;

                     // We get the registered families to the workgroup
                     $ArrayWorkGroupRegistrations = dbSearchWorkGroupRegistration($DbConnection, array("WorkGroupID" => $WorkGroupID),
                                                                                  "WorkGroupRegistrationLastname", 1, 0);

                     // We check if the user is allowed to delete a workgroup registration
                     switch($cUserOtherAccess)
                     {
                         case FCT_ACT_UPDATE:
                             switch($GLOBALS['CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                             {
                                 case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL:
                                     $bCanDeleteRegistration = TRUE;
                                     $bCanAddRegistrationForOther = TRUE;
                                     break;

                                 case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                                     // We get referents of the workgroup
                                     $ArrayParams = array(
                                                          'WorkGroupID' => $WorkGroupID,
                                                          'WorkGroupRegistrationReferent' => array(1)
                                                         );

                                     $ArrayReferents = dbSearchWorkGroupRegistration($DbConnection, $ArrayParams,
                                                                                     "WorkGroupRegistrationLastname", 1, 0);

                                     if ((isset($ArrayReferents['WorkGroupRegistrationID']))
                                         && (in_array($_SESSION['SupportMemberEmail'], $ArrayReferents['WorkGroupRegistrationEmail'])))
                                     {
                                         // The user is a referent thanks to his e-mail address
                                         $bIsReferent = TRUE;
                                     }

                                     if (((isset($ArrayWorkGroupRegistrations['FamilyID']))
                                         && (in_array($LoggedFamilyID, $ArrayWorkGroupRegistrations['FamilyID'])))
                                         || ($bIsReferent))
                                     {
                                         // The supporter is allowed to delete his registration to the workgroup
                                         $bCanDeleteRegistration = TRUE;

                                         // The user isn't a referent and delete his registration isn't allowed
                                         // (only referent can delete his registration)
                                         if ((!$GLOBALS['CONF_COOP_WORKGROUP_ALLOW_UNREGISTRATIONS_FOR_USERS'])
                                             && (!$bIsReferent))
                                         {
                                             $bCanDeleteRegistration = FALSE;
                                         }

                                         // The supporter is allowed to add registrations for other families if he's referent
                                         if (($GLOBALS['CONF_COOP_WORKGROUP_ALLOW_REGISTRATIONS_FOR_REFERENTS']) && ($bIsReferent))
                                         {
                                             $bCanAddRegistrationForOther = TRUE;
                                         }
                                     }
                                     break;
                             }
                             break;
                     }
                 }
                 else
                 {
                     // Error, the workgroup doesn't exist
                     $WorkGroupID = 0;
                     $Reference = "&nbsp;";
                 }
             }

             // We define the captions of the workgroup registrations table (registrations get at the begining of this function)
             $WorkGroupRegistrations = '&nbsp;';
             $TabRegistrationsCaptions = array($GLOBALS["LANG_FAMILY_LASTNAME"], $GLOBALS["LANG_E_MAIL"]);

             switch($cUserAccess)
             {
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     // We transform the result to be displayed : we hide some data
                     $bLoggedUserRegistered = FALSE;
                     if ((isset($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]))
                         && (count($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]) > 0))
                     {
                         if ($bCanDeleteRegistration)
                         {
                             $TabRegistrationsCaptions[] = '&nbsp;';
                         }

                         foreach($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"] as $i => $CurrentID)
                         {
                             if ($ArrayWorkGroupRegistrations["FamilyID"][$i] == $LoggedFamilyID)
                             {
                                 if (strToLower($_SESSION['SupportMemberEmail']) == strToLower($ArrayWorkGroupRegistrations["WorkGroupRegistrationEmail"][$i]))
                                 {
                                     // Logged user = registered person if same e-mail address
                                     $bLoggedUserRegistered = TRUE;
                                 }

                                 $sTmpLastname = $ArrayWorkGroupRegistrations["WorkGroupRegistrationLastname"][$i]
                                                 .' '.$ArrayWorkGroupRegistrations["WorkGroupRegistrationFirstname"][$i];

                                 // We check if a family is associated to this person
                                 if ($ArrayWorkGroupRegistrations["FamilyID"][$i] > 0)
                                 {
                                     $sTmpLastname .= ' ('.generateCryptedHyperlink($ArrayWorkGroupRegistrations["FamilyLastname"][$i],
                                                                                   $ArrayWorkGroupRegistrations["FamilyID"][$i],
                                                                                   '../Canteen/UpdateFamily.php',
                                                                                   $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank')
                                                      .')';
                                 }

                                 // We display an icon if the pseron is a referent of this workgroup
                                 if ($ArrayWorkGroupRegistrations["WorkGroupRegistrationReferent"][$i] == 1)
                                 {
                                     $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_WORKGROUP_REFERENT_ICON"],
                                                                                $GLOBALS['LANG_WORKGROUP_IS_REFERENT_TIP'], "");
                                 }

                                 $TabRegistrationsData[0][] = $sTmpLastname;
                                 $TabRegistrationsData[1][] = generateCryptedHyperlink($ArrayWorkGroupRegistrations["WorkGroupRegistrationEmail"][$i],
                                                                                       $CurrentID,
                                                                                       '../Cooperation/UpdateWorkGroupRegistration.php',
                                                                                       $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '',
                                                                                       '_blank');

                                 if ($bCanDeleteRegistration)
                                 {
                                     // We can delete him
                                     $TabRegistrationsData[2][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                 "DeleteWorkGroupRegistration.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                                 $GLOBALS["LANG_DELETE"], 'Affectation');
                                 }
                             }
                             else
                             {
                                 $sTmpLastname = $ArrayWorkGroupRegistrations["WorkGroupRegistrationLastname"][$i]
                                                 .' '.$ArrayWorkGroupRegistrations["WorkGroupRegistrationFirstname"][$i];

                                 // We check if a family is associated to this person
                                 if ($ArrayWorkGroupRegistrations["FamilyID"][$i] > 0)
                                 {
                                     $sTmpLastname .= ' ('.$ArrayWorkGroupRegistrations["FamilyLastname"][$i].')';
                                 }

                                 // We display an icon if the pseron is a referent of this workgroup
                                 if ($ArrayWorkGroupRegistrations["WorkGroupRegistrationReferent"][$i] == 1)
                                 {
                                     $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_WORKGROUP_REFERENT_ICON"],
                                                                                $GLOBALS['LANG_WORKGROUP_IS_REFERENT_TIP'], "");
                                 }

                                 $TabRegistrationsData[0][] = $sTmpLastname;

                                 // We hide the e-mail
                                 $TabRegistrationsData[1][] = WORKGROUP_HIDDEN_FAMILY_DATA;

                                 if ($bCanDeleteRegistration)
                                 {
                                     if ($bIsReferent)
                                     {
                                         // The suer is a referent : he can delete registration
                                         $TabRegistrationsData[2][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                                     "DeleteWorkGroupRegistration.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                                     $GLOBALS["LANG_DELETE"], 'Affectation');
                                     }
                                     else
                                     {
                                         $TabRegistrationsData[2][] = "&nbsp;";
                                     }
                                 }
                             }
                         }
                     }

                     // Add or not the button to add a registration
                     switch($cUserOtherAccess)
                     {
                         case FCT_ACT_CREATE:
                         case FCT_ACT_UPDATE:
                             if (!$bLoggedUserRegistered)
                             {
                                 /* The user has rights to add a registration :
                                  * - user not already registered for this workgroup
                                  */
                                 $WorkGroupRegistrations = "<table><tr><td class=\"Action\">";
                                 $WorkGroupRegistrations .= generateStyledLinkText($GLOBALS["LANG_WORKGROUP_ADD_REGISTERED_MEMBER"],
                                                                                   "AddWorkGroupRegistration.php?Cr=".md5($WorkGroupID)."&amp;Id=$WorkGroupID&amp;FCr=".md5($LoggedFamilyID)."&amp;FId=$LoggedFamilyID",
                                                                                   '', $GLOBALS["LANG_WORKGROUP_ADD_REGISTERED_MEMBER_TIP"],
                                                                                   '_blank');
                                 $WorkGroupRegistrations .= "</td></tr></table>";
                             }
                             elseif ($bCanAddRegistrationForOther)
                             {
                                 // The user is a referent, so he can register some other families on the workgroup
                                 $WorkGroupRegistrations = "<table><tr><td class=\"Action\">";
                                 $WorkGroupRegistrations .= generateCryptedHyperlink($GLOBALS["LANG_WORKGROUP_ADD_REGISTERED_MEMBER"], $WorkGroupID,
                                                                                     'AddWorkGroupRegistration.php',
                                                                                     $GLOBALS["LANG_WORKGROUP_ADD_REGISTERED_MEMBER_TIP"], '',
                                                                                     '_blank');
                                 $WorkGroupRegistrations .= "</td></tr></table>";
                             }
                             else
                             {
                                 $WorkGroupRegistrations = '&nbsp;';
                             }
                             break;

                         default:
                             $WorkGroupRegistrations = '&nbsp;';
                             break;
                     }
                     break;

                 case FCT_ACT_READ_ONLY:
                     // We transform the result to be displayed
                     if ((isset($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]))
                         && (count($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]) > 0))
                     {
                         foreach($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"] as $i => $CurrentID)
                         {
                             $sTmpLastname = $ArrayWorkGroupRegistrations["WorkGroupRegistrationLastname"][$i]
                                             .' '.$ArrayWorkGroupRegistrations["WorkGroupRegistrationFirstname"][$i];

                             // We check if a family is associated to this person
                             if ($ArrayWorkGroupRegistrations["FamilyID"][$i] > 0)
                             {
                                 $sTmpLastname .= ' ('.generateCryptedHyperlink($ArrayWorkGroupRegistrations["FamilyLastname"][$i],
                                                                               $ArrayWorkGroupRegistrations["FamilyID"][$i],
                                                                               '../Canteen/UpdateFamily.php',
                                                                               $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank')
                                                  .')';
                             }

                             // We display an icon if the pseron is a referent of this workgroup
                             if ($ArrayWorkGroupRegistrations["WorkGroupRegistrationReferent"][$i] == 1)
                             {
                                 $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_WORKGROUP_REFERENT_ICON"],
                                                                            $GLOBALS['LANG_WORKGROUP_IS_REFERENT_TIP'], "");
                             }

                             $TabRegistrationsData[0][] = $sTmpLastname;
                             $TabRegistrationsData[1][] = generateCryptedHyperlink($ArrayWorkGroupRegistrations["WorkGroupRegistrationEmail"][$i],
                                                                                   $CurrentID,
                                                                                   '../Cooperation/UpdateWorkGroupRegistration.php',
                                                                                   $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');
                         }
                     }

                     $WorkGroupRegistrations = '&nbsp;';
                     break;

                 case FCT_ACT_UPDATE:
                     // We transform the result to be displayed
                     $TabRegistrationsCaptions[] = '&nbsp;';

                     if ((isset($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]))
                         && (count($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]) > 0))
                     {
                         foreach($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"] as $i => $CurrentID)
                         {
                             $sTmpLastname = $ArrayWorkGroupRegistrations["WorkGroupRegistrationLastname"][$i]
                                             .' '.$ArrayWorkGroupRegistrations["WorkGroupRegistrationFirstname"][$i];

                             // We check if a family is associated to this person
                             if ($ArrayWorkGroupRegistrations["FamilyID"][$i] > 0)
                             {
                                 $sTmpLastname .= ' ('.generateCryptedHyperlink($ArrayWorkGroupRegistrations["FamilyLastname"][$i],
                                                                               $ArrayWorkGroupRegistrations["FamilyID"][$i],
                                                                               '../Canteen/UpdateFamily.php',
                                                                               $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank')
                                                  .')';
                             }

                             // We display an icon if the pseron is a referent of this workgroup
                             if ($ArrayWorkGroupRegistrations["WorkGroupRegistrationReferent"][$i] == 1)
                             {
                                 $sTmpLastname .= ' '.generateStyledPicture($GLOBALS["CONF_WORKGROUP_REFERENT_ICON"],
                                                                            $GLOBALS['LANG_WORKGROUP_IS_REFERENT_TIP'], "");
                             }

                             $TabRegistrationsData[0][] = $sTmpLastname;
                             $TabRegistrationsData[1][] = generateCryptedHyperlink($ArrayWorkGroupRegistrations["WorkGroupRegistrationEmail"][$i],
                                                                                   $CurrentID,
                                                                                   '../Cooperation/UpdateWorkGroupRegistration.php',
                                                                                   $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"], '', '_blank');

                             // We can delete him
                             $TabRegistrationsData[2][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                                         "DeleteWorkGroupRegistration.php?Cr=".md5($CurrentID)."&amp;Id=$CurrentID",
                                                                                         $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     $WorkGroupRegistrations = "<table><tr><td class=\"Action\">";
                     $WorkGroupRegistrations .= generateCryptedHyperlink($GLOBALS["LANG_WORKGROUP_ADD_REGISTERED_MEMBER"], $WorkGroupID,
                                                                         'AddWorkGroupRegistration.php',
                                                                         $GLOBALS["LANG_WORKGROUP_ADD_REGISTERED_MEMBER_TIP"], '',
                                                                         '_blank');
                     $WorkGroupRegistrations .= "</td></tr></table>";
                     break;
             }

             // <<< WorkGroupName INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Name = stripslashes($WorkGroupRecord["WorkGroupName"]);
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Name = generateInputField("sWorkGroupName", "text", "50", "30", $GLOBALS["LANG_WORKGROUP_NAME_TIP"],
                                                $WorkGroupRecord["WorkGroupName"]);
                     break;
             }

             // <<< WorkGroupDescription INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Description = stripslashes(nullFormatText($WorkGroupRecord["WorkGroupDescription"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Description = generateInputField("sWorkGroupDescription", "text", "255", "80", $GLOBALS["LANG_WORKGROUP_DESCRIPTION_TIP"],
                                                       $WorkGroupRecord["WorkGroupDescription"]);
                     break;
             }

             // <<< WorkGroupEmail INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     $Email = stripslashes(nullFormatText($WorkGroupRecord["WorkGroupEmail"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Email = generateInputField("sWorkGroupEmail", "text", "100", "80", $GLOBALS["LANG_WORKGROUP_E_MAIL_TIP"],
                                                 $WorkGroupRecord["WorkGroupEmail"]);
                     break;
             }

             // Display the form
             echo "<table id=\"WorkGroupDetails\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_WORKGROUP_NAME"]."*</td><td class=\"Value\">$Name</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_WORKGROUP_DESCRIPTION"]."</td><td class=\"Value\" colspan=\"3\">$Description</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_WORKGROUP_E_MAIL"]."</td><td class=\"Value\" colspan=\"3\">$Email</td>\n</tr>\n";

             if ($WorkGroupID > 0)
             {
                 // Display registered families
                 echo "<tr>\n\t<td class=\"Label\" colspan=\"4\">&nbsp;</td>\n</tr>\n";
                 echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_WORKGROUP_REGISTERED_MEMBERS"]."</td><td class=\"Value\" colspan=\"3\">";
                 echo "<table>\n<tr>\n\t<td>";
                 if ((isset($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]))
                     && (count($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]) > 0))
                 {
                     displayStyledTable($TabRegistrationsCaptions, array_fill(0, count($TabRegistrationsCaptions), ''), '',
                                        $TabRegistrationsData, 'PurposeParticipantsTable', '', '', '', array(), 0, array(0 => 'textLeft'));
                     echo "</td>\n<td class=\"AowFilesSpace\"></td><td>";
                 }
                 echo $WorkGroupRegistrations;

                 echo "</td></tr>\n</table>";

                 if ((isset($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]))
                     && (count($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]) > 0))
                 {
                     // Display the number of registrations
                     echo $GLOBALS['LANG_NB_WORKGROUP_REGISTRATIONS'].' : '
                          .count($ArrayWorkGroupRegistrations["WorkGroupRegistrationID"]);
                 }

                 echo "</td>\n</tr>\n";
             }

             echo "</table>\n";

             insertInputField("hidWorkGroupID", "hidden", "", "", "", $WorkGroupID);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     // We display the buttons
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a workgroup
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
 * Display the form to search a workgroup in the current web page, in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2016-03-07 : taken into account HTML "WorkGroupsList" id
 *
 * @since 2015-10-13
 *
 * @param $DbConnection                DB object            Object of the opened database connection
 * @param $TabParams                   Array of Strings     search criterion used to find some workgroups
 * @param $ProcessFormPage             String               URL of the page which will process the form allowing to find and to sort
 *                                                          the table of the workgroups found
 * @param $Page                        Integer              Number of the Page to display [1..n]
 * @param $SortFct                     String               Javascript function used to sort the table
 * @param $OrderBy                     Integer              n° Criteria used to sort the workgroups. If < 0, DESC is used, otherwise ASC
 *                                                          is used
 * @param $DetailsPage                 String               URL of the page to display details about a workgroup. This string can be empty
 * @param $AccessRules                 Array of Integers    List used to select only some support members
 *                                                          allowed to create or update workgroups
 * @param $$ArrayDiplayedFormFields    Array of Strings     Contains fieldnames of the form to display or not
 */
 function displaySearchWorkGroupForm($DbConnection, $TabParams, $ProcessFormPage, $Page = 1, $SortFct = '', $OrderBy = 0, $DetailsPage = '', $AccessRules = array(), $ArrayDiplayedFormFields = array())
 {
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to workgroups list
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
             $bCanDelete = FALSE;          // Check if the supporter can delete a workgroup
             $bCheckRegistration = FALSE;  // Check if a flag must be displayed on workgroups for which the supporter is registered
             if (isset($GLOBALS['CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION["SupportMemberStateID"]]))
             {
                 switch($GLOBALS['CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION["SupportMemberStateID"]])
                 {
                     case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL:
                         // To delete a workgroup, the supporter must have write access
                         if (in_array($cUserAccess, array(FCT_ACT_CREATE, FCT_ACT_UPDATE)))
                         {
                             $bCanDelete = TRUE;
                         }
                         break;

                     case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                         $bCheckRegistration = TRUE;
                         break;
                 }
             }

             // Open a form
             openForm("FormSearchWorkGroup", "post", "$ProcessFormPage", "", "");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_SEARCH"], "Frame", "Frame", "SearchFrame");

             if (empty($ArrayDiplayedFormFields))
             {
                 // Display all available fields of the form
                 $ArrayDiplayedFormFields = array(
                                                  "sWorkGroupName" => true,
                                                  "sWorkGroupEmail" => true,
                                                  "sLastname" => true,
                                                  "sFamilyEmail" => true
                                                 );
             }

             $sWorkGroupName = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sWorkGroupName'])) && ($ArrayDiplayedFormFields['sWorkGroupName']))
             {
                 // Workgroup name input text
                 $sWorkGroupName = generateInputField("sWorkGroupName", "text", "50", "13", $GLOBALS["LANG_WORKGROUP_NAME_TIP"],
                                                      stripslashes(strip_tags(existedPOSTFieldValue("sWorkGroupName", stripslashes(existedGETFieldValue("sWorkGroupName", ""))))));
             }

             $sWorkGroupEmail = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sWorkGroupEmail'])) && ($ArrayDiplayedFormFields['sWorkGroupEmail']))
             {
                 // Workgroup e-mail input text
                 $sWorkGroupEmail = generateInputField("sWorkGroupEmail", "text", "100", "25", $GLOBALS["LANG_WORKGROUP_E_MAIL_TIP"],
                                                       stripslashes(strip_tags(existedPOSTFieldValue("sWorkGroupEmail", stripslashes(existedGETFieldValue("sWorkGroupEmail", ""))))));
             }

             $sLastname = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sLastname'])) && ($ArrayDiplayedFormFields['sLastname']))
             {
                 // Family lastname input text
                 $sLastname = generateInputField("sLastname", "text", "50", "13", $GLOBALS["LANG_FAMILY_LASTNAME_TIP"],
                                                 stripslashes(strip_tags(existedPOSTFieldValue("sLastname", stripslashes(existedGETFieldValue("sLastname", ""))))));
             }

             $sFamilyEmail = '&nbsp;';
             if ((isset($ArrayDiplayedFormFields['sFamilyEmail'])) && ($ArrayDiplayedFormFields['sFamilyEmail']))
             {
                 // Family e-mail input text
                 $sFamilyEmail = generateInputField("sFamilyEmail", "text", "100", "25", $GLOBALS["LANG_FAMILY_MAIN_EMAIL_TIP"]." / "
                                                    .$GLOBALS['LANG_FAMILY_SECOND_EMAIL_TIP'],
                                                    stripslashes(strip_tags(existedPOSTFieldValue("sFamilyEmail", stripslashes(existedGETFieldValue("sFamilyEmail", ""))))));
             }

             // Display the form
             echo "<table id=\"WorkGroupsList\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_WORKGROUP"]."</td><td class=\"Value\">$sWorkGroupName</td><td class=\"Label\">".$GLOBALS["LANG_WORKGROUP_E_MAIL"]."</td><td class=\"Value\">$sWorkGroupEmail</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS['LANG_LASTNAME']."</td><td class=\"Value\">$sLastname</td><td class=\"Label\">".$GLOBALS["LANG_E_MAIL"]."</td><td class=\"Value\">$sFamilyEmail</td>\n</tr>\n";
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

                 $ArrayCaptions = array($GLOBALS["LANG_WORKGROUP_NAME"], $GLOBALS["LANG_WORKGROUP_DESCRIPTION"],
                                        $GLOBALS["LANG_WORKGROUP_E_MAIL"],
                                        $GLOBALS["LANG_NB_WORKGROUP_REGISTRATIONS"]);
                 $ArraySorts = array("WorkGroupName", "", "WorkGroupEmail", "NbRegistrations");

                 if ($bCanDelete)
                 {
                     // The supporter can delete workgroups : we add a column for this action
                     $ArrayCaptions[] = '&nbsp;';
                     $ArraySorts[] = "";
                 }

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
                     $StrOrderBy = "WorkGroupName ASC";
                 }

                 // We launch the search
                 $NbRecords = getNbdbSearchWorkGroup($DbConnection, $TabParams);
                 if ($NbRecords > 0)
                 {
                     // To get only workgroups of the page
                     $ArrayRecords = dbSearchWorkGroup($DbConnection, $TabParams, $StrOrderBy, $Page, $GLOBALS["CONF_RECORDS_PER_PAGE"]);

                     /*openParagraph('toolbar');
                     displayStyledLinkText($GLOBALS["LANG_PRINT"], "javascript:PrintWebPage()", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_XML_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_XML_RESULT_FILENAME"].time().".xml')", "", "", "");
                     echo "&nbsp;&nbsp;";
                     displayStyledLinkText($GLOBALS["LANG_EXPORT_TO_CSV_FILE"], "javascript:ExportWebPage('".$GLOBALS["CONF_EXPORT_CSV_RESULT_FILENAME"].time().".csv')", "", "", "");
                     closeParagraph(); */

                     // Get FamilyID of the logged supporter if we must check registrations on workgroups
                     if ($bCheckRegistration)
                     {
                         $FamilyID = $_SESSION['FamilyID'];
                         $ArrayTmpParams = array(
                                                 "FamilyID" => array($FamilyID)
                                                );

                         $ArrayWorkGroupRegistrations = dbSearchWorkGroupRegistration($DbConnection, $ArrayTmpParams, "WorkGroupID", 1, 0);
                         unset($ArrayTmpParams);
                     }

                     // There are some workgroups found
                     foreach($ArrayRecords["WorkGroupID"] as $i => $CurrentValue)
                     {
                         if (empty($DetailsPage))
                         {
                             // We display the workgroup name
                             $ArrayData[0][] = $ArrayRecords["WorkGroupName"][$i];
                         }
                         else
                         {
                             // We display the workgroup name with a hyperlink
                             $ArrayData[0][] = generateAowIDHyperlink($ArrayRecords["WorkGroupName"][$i], $ArrayRecords["WorkGroupID"][$i],
                                                                      $DetailsPage, $GLOBALS["LANG_VIEW_DETAILS_INSTRUCTIONS"],
                                                                      "", "_blank");
                         }

                         $ArrayData[1][] = $ArrayRecords["WorkGroupDescription"][$i];
                         $ArrayData[2][] = $ArrayRecords["WorkGroupEmail"][$i];

                         $sNbRegistrations = $ArrayRecords["NbRegistrations"][$i];

                         if (($bCheckRegistration) && (isset($ArrayWorkGroupRegistrations['WorkGroupID'])))
                         {
                             if (in_array($CurrentValue, $ArrayWorkGroupRegistrations['WorkGroupID']))
                             {
                                 // The logged user is registered to this workgroup
                                 $sNbRegistrations .= "&nbsp;".generateStyledPicture($GLOBALS['CONF_REGISTERED_ON_WORKGROUP_ICON'], '', '');
                             }
                         }

                         $ArrayData[3][] = $sNbRegistrations;

                         // Hyperlink to delete the workgroup if allowed
                         if ($bCanDelete)
                         {
                             $ArrayData[4][] = generateStyledPictureHyperlink($GLOBALS["CONF_DELETE_ICON"],
                                                                              "DeleteWorkGroup.php?Cr=".md5($CurrentValue)."&amp;Id=$CurrentValue&amp;Return=$ProcessFormPage&amp;RCr=".md5($CurrentValue)."&amp;RId=$CurrentValue",
                                                                              $GLOBALS["LANG_DELETE"], 'Affectation');
                         }
                     }

                     // Display the table which contains the workgroups found
                     $ArraySortedFields = array("1", "", "3", "4");
                     if ($bCanDelete)
                     {
                         $ArraySortedFields[] = "";
                     }

                     displayStyledTable($ArrayCaptions, $ArraySortedFields, $SortFct, $ArrayData, '', '', '', '',
                                        array(), $OrderBy, array('', '', '', '', ''), 'WorkGroupsList');

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
                     echo $GLOBALS['LANG_NB_RECORDS_FOUND'].$NbRecords;
                     closeParagraph();

                     // Display the legends of the icons
                     if ($bCheckRegistration)
                     {
                         displayBR(1);
                         echo generateLegendsOfVisualIndicators(
                                                                array(
                                                                      array($GLOBALS['CONF_REGISTERED_ON_WORKGROUP_ICON'], $GLOBALS["LANG_WORKGROUP_REGISTERED_ON_WORKGROUP"])
                                                                     ),
                                                                ICON
                                                               );
                     }
                 }
                 else
                 {
                     // No workgroup found
                     openParagraph('nbentriesfound');
                     echo $GLOBALS['LANG_NO_RECORD_FOUND'];
                     closeParagraph();
                 }
             }
         }
         else
         {
             // The supporter isn't allowed to view the list of workgroups
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
 * Display the form to submit a new workgroup registration or update a workgroup registration, in the current web page,
 * in the graphic interface in XHTML
 *
 * @author Christophe Javouhey
 * @version 1.1
 *     - 2017-09-13 : allow referent to register some families
 *                    (taken into account $CONF_COOP_WORKGROUP_ALLOW_REGISTRATIONS_FOR_REFERENTS)
 *
 * @since 2015-10-14
 *
 * @param $DbConnection                 DB object             Object of the opened database connection
 * @param $WorkGroupRegistrationID      String                ID of the workgroup registration to display [0..n]
 * @param $WorkGroupID                  String                ID of the workgroup concerned by the registration [1..n]
 * @param $ProcessFormPage              String                URL of the page which will process the form
 * @param $AccessRules                  Array of Integers     List used to select only some support members
 *                                                            allowed to create or update workgroup registrations
 */
 function displayDetailsWorkGroupRegistrationForm($DbConnection, $WorkGroupRegistrationID, $WorkGroupID, $ProcessFormPage, $AccessRules = array())
 {
     // The supporter must be logged,
     if (isSet($_SESSION["SupportMemberID"]))
     {
         // The supporter must be allowed to access to workgroup registration
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
             // Current school year
             $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));

             // Get the FamilyID of the logged user
             $LoggedFamilyID = $_SESSION['FamilyID'];

             // <<< WorkGroup registration ID >>>
             if ($WorkGroupRegistrationID == 0)
             {
                 switch($GLOBALS['CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                 {
                     case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                         // Registration created by a family
                         $FamilyID = $LoggedFamilyID;
                         break;

                     default:
                         $FamilyID = 0;  // No selected family
                         break;
                 }

                 $Reference = "&nbsp;";
                 $WorkGroupRegistrationRecord = array(
                                                      "WorkGroupRegistrationDate" => date('Y-m-d H:i:s'),
                                                      "WorkGroupRegistrationLastname" => $_SESSION['SupportMemberLastname'],
                                                      "WorkGroupRegistrationFirstname" => '',
                                                      "WorkGroupRegistrationEmail" => '',
                                                      "WorkGroupRegistrationReferent" => 0,
                                                      "WorkGroupID" => $WorkGroupID,
                                                      "SupportMemberID" => $_SESSION['SupportMemberID'],
                                                      "FamilyID" => $FamilyID
                                                     );
             }
             else
             {
                 if (isExistingWorkGroupRegistration($DbConnection, $WorkGroupRegistrationID))
                 {
                     // We get the details of the workgroup registration
                     $WorkGroupRegistrationRecord = getTableRecordInfos($DbConnection, "WorkGroupRegistrations",
                                                                        $WorkGroupRegistrationID);
                     $Reference = $WorkGroupRegistrationID;
                     $WorkGroupID = $WorkGroupRegistrationRecord['WorkGroupID'];
                 }
                 else
                 {
                     // Error, the workgroup registration doesn't exist
                     openParagraph('ErrorMsg');
                     echo $GLOBALS["LANG_ERROR_NOT_ALLOWED_TO_CREATE_OR_UPDATE"];
                     closeParagraph();
                     exit(0);
                 }
             }

             // We get infos about the concerned workgroup
             $WorkGroupRecord = getTableRecordInfos($DbConnection, "WorkGroups", $WorkGroupID);

             // We get referents of the workgroup
             $bIsReferent = FALSE;
             $ArrayParams = array(
                                  'WorkGroupID' => $WorkGroupID,
                                  'WorkGroupRegistrationReferent' => array(1)
                                 );

             $ArrayReferents = dbSearchWorkGroupRegistration($DbConnection, $ArrayParams, "WorkGroupRegistrationLastname", 1, 0);

             if ((isset($ArrayReferents['WorkGroupRegistrationID']))
                 && (in_array($_SESSION['SupportMemberEmail'], $ArrayReferents['WorkGroupRegistrationEmail'])))
             {
                 // The user is a referent thanks to his e-mail address
                 $bIsReferent = TRUE;
             }

             // Open a form
             openForm("FormDetailsWorkGroupRegistration", "post", "$ProcessFormPage?".$GLOBALS["QUERY_STRING"], "",
                      "VerificationWorkGroupRegistration('".$GLOBALS["LANG_ERROR_JS_LASTNAME"]."', '"
                                                           .$GLOBALS["LANG_ERROR_JS_FIRSTNAME"]."', '"
                                                           .$GLOBALS['LANG_ERROR_JS_EMAIL']."')");

             // Display the table (frame) where the form will take place
             openStyledFrame($GLOBALS["LANG_WORKGROUP_REGISTRATION"].' : '.$WorkGroupRecord['WorkGroupName'], "Frame", "Frame",
                             "DetailsNews");

             // Creation date and time of the registration for the workgroup
             $CreationDate = date($GLOBALS["CONF_DATE_DISPLAY_FORMAT"].' '.$GLOBALS["CONF_TIME_DISPLAY_FORMAT"],
                                  strtotime($WorkGroupRegistrationRecord["WorkGroupRegistrationDate"]));

             // We get infos about the author of the workgroup registration
             $ArrayInfosLoggedSupporter = getSupportMemberInfos($DbConnection, $WorkGroupRegistrationRecord["SupportMemberID"]);
             $Author = $ArrayInfosLoggedSupporter["SupportMemberLastname"].' '.$ArrayInfosLoggedSupporter["SupportMemberFirstname"]
                       .' ('.getSupportMemberStateName($DbConnection, $ArrayInfosLoggedSupporter["SupportMemberStateID"]).')';
             $Author .= generateInputField("hidSupportMemberID", "hidden", "", "", "", $WorkGroupRegistrationRecord["SupportMemberID"]);

             // <<< WorkGroupRegistrationLastname INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $sLastname = stripslashes(nullFormatText($WorkGroupRegistrationRecord["WorkGroupRegistrationLastname"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                      $sLastname = generateInputField("sLastname", "text", "50", "25", $GLOBALS["LANG_LASTNAME_TIP"],
                                                      $WorkGroupRegistrationRecord["WorkGroupRegistrationLastname"]);
                      break;
             }

             // <<< WorkGroupRegistrationFirstname INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $sFirstname = stripslashes(nullFormatText($WorkGroupRegistrationRecord["WorkGroupRegistrationLastname"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                      $sFirstname = generateInputField("sFirstname", "text", "25", "25", $GLOBALS["LANG_FIRSTNAME_TIP"],
                                                       $WorkGroupRegistrationRecord["WorkGroupRegistrationFirstname"]);
                      break;
             }

             // <<< WorkGroupRegistrationEmail INPUTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $sEmail = stripslashes(nullFormatText($WorkGroupRegistrationRecord["WorkGroupRegistrationEmail"]));
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                      $sEmail = generateInputField("sEmail", "text", "100", "85", $GLOBALS["LANG_E_MAIL_TIP"],
                                                   $WorkGroupRegistrationRecord["WorkGroupRegistrationEmail"]);
                      break;
             }

             // <<< Family ID SELECTFIELD >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_PARTIAL_READ_ONLY:
                     if ($LoggedFamilyID == $WorkGroupRegistrationRecord["FamilyID"])
                     {
                         // We display the lastname
                         $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $WorkGroupRegistrationRecord["FamilyID"]);
                         $Family = $FamilyRecord['FamilyLastname'];
                     }
                     else
                     {
                         // We hide the lastname
                         $Family = WORKGROUP_HIDDEN_FAMILY_DATA;
                     }
                     break;

                 case FCT_ACT_READ_ONLY:
                     // Get the lastname of the family
                     $Family = '-';
                     if ($WorkGroupRegistrationRecord["FamilyID"] > 0)
                     {
                         $FamilyRecord = getTableRecordInfos($DbConnection, "Families", $WorkGroupRegistrationRecord["FamilyID"]);
                         $Family = $FamilyRecord['FamilyLastname'];
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     switch($GLOBALS['CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS'][$_SESSION['SupportMemberStateID']])
                     {
                         case WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY:
                             $ArrayFamilyID = array();
                             $ArrayFamilyLastname = array();

                             // Only the logged family except the user is the referent of the workgroup
                             // and referent is allowed to add registrations for others
                             if (($GLOBALS['CONF_COOP_WORKGROUP_ALLOW_REGISTRATIONS_FOR_REFERENTS']) && ($bIsReferent))
                             {
                                 // Generate the list of activated families
                                 $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear)),
                                                                 "FamilyLastname", 1, 0);

                                 $ArrayFamilyID = array_merge(array(0), $ArrayFamilies['FamilyID']);
                                 $ArrayFamilyLastname = array_merge(array(''), $ArrayFamilies['FamilyLastname']);

                                 if ($WorkGroupRegistrationID > 0)
                                 {
                                     $SelectedFamilyID = $WorkGroupRegistrationRecord["FamilyID"];
                                 }
                                 else
                                 {
                                     // We try to found the family lastname of the logged supporter in the list
                                     $SelectedFamilyID = $LoggedFamilyID;
                                     $iPos = array_search($_SESSION['SupportMemberLastname'], $ArrayFamilies['FamilyLastname']);
                                     if ($iPos !== FALSE)
                                     {
                                         // Lastname found
                                         $SelectedFamilyID = $ArrayFamilies['FamilyID'][$iPos];
                                     }
                                 }
                             }
                             else
                             {
                                 $ArrayFamilies = dbSearchFamily($DbConnection, array("FamilyID" => $LoggedFamilyID,
                                                                                      "SchoolYear" => array($CurrentSchoolYear)),
                                                                 "FamilyLastname", 1, 0);


                                 $ArrayFamilyID = $ArrayFamilies['FamilyID'];
                                 $ArrayFamilyLastname = $ArrayFamilies['FamilyLastname'];
                                 $SelectedFamilyID = $WorkGroupRegistrationRecord["FamilyID"];
                             }
                             break;

                         default:
                             // Generate the list of activated families
                             $ArrayFamilies = dbSearchFamily($DbConnection, array("SchoolYear" => array($CurrentSchoolYear)),
                                                             "FamilyLastname", 1, 0);

                             $ArrayFamilyID = array_merge(array(0), $ArrayFamilies['FamilyID']);
                             $ArrayFamilyLastname = array_merge(array(''), $ArrayFamilies['FamilyLastname']);

                             if ($WorkGroupRegistrationID > 0)
                             {
                                 $SelectedFamilyID = $WorkGroupRegistrationRecord["FamilyID"];
                             }
                             else
                             {
                                 // We try to found the family lastname of the logged supporter in the list
                                 $SelectedFamilyID = $LoggedFamilyID;
                                 $iPos = array_search($_SESSION['SupportMemberLastname'], $ArrayFamilies['FamilyLastname']);
                                 if ($iPos !== FALSE)
                                 {
                                     // Lastname found
                                     $SelectedFamilyID = $ArrayFamilies['FamilyID'][$iPos];
                                 }
                             }
                             break;
                     }

                     $Family = generateSelectField("lFamilyID", $ArrayFamilyID, $ArrayFamilyLastname, $SelectedFamilyID, "");
                     break;
             }

             // <<< Referent CHECKBOX >>>
             switch($cUserAccess)
             {
                 case FCT_ACT_READ_ONLY:
                     $WorkGroupRegistrationReferent = $GLOBALS["LANG_NO"];
                     if ($WorkGroupRegistrationRecord["WorkGroupRegistrationReferent"] == 1)
                     {
                         $WorkGroupRegistrationReferent = $GLOBALS["LANG_YES"];
                     }
                     break;

                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     $Checked = FALSE;
                     if ($WorkGroupRegistrationRecord["WorkGroupRegistrationReferent"] == 1)
                     {
                         $Checked = TRUE;
                     }

                     $WorkGroupRegistrationReferent = generateInputField("chkWorkGroupRegistrationReferent", "checkbox", "", "",
                                                                         $GLOBALS["LANG_WORKGROUP_REGISTRATION_REFERENT_TIP"],
                                                                         "valided", FALSE, $Checked)." ".$GLOBALS["LANG_YES"];
                     break;
             }

             // Display the form
             echo "<table cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_REFERENCE"]."</td><td class=\"Value\">$Reference</td><td class=\"Label\">".$GLOBALS["LANG_CREATION_DATE"]."</td><td class=\"Value\">$CreationDate, $Author</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_LASTNAME"]."*</td><td class=\"Value\">$sLastname</td><td class=\"Label\">".$GLOBALS["LANG_FIRSTNAME"]."*</td><td class=\"Value\">$sFirstname</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_E_MAIL"]."*</td><td class=\"Value\" colspan=\"3\">$sEmail</td>\n</tr>\n";
             echo "<tr>\n\t<td class=\"Label\">".$GLOBALS["LANG_FAMILY"]."</td><td class=\"Value\">$Family</td><td class=\"Label\">".$GLOBALS["LANG_WORKGROUP_REGISTRATION_REFERENT"]."</td><td class=\"Value\">$WorkGroupRegistrationReferent</td>\n</tr>\n";
             echo "</table>\n";

             insertInputField("hidWorkGroupID", "hidden", "", "", "", $WorkGroupRegistrationRecord["WorkGroupID"]);
             closeStyledFrame();

             switch($cUserAccess)
             {
                 case FCT_ACT_CREATE:
                 case FCT_ACT_UPDATE:
                     // We display the buttons
                     echo "<table class=\"validation\">\n<tr>\n\t<td>";
                     insertInputField("bSubmit", "submit", "", "", $GLOBALS["LANG_SUBMIT_BUTTON_TIP"], $GLOBALS["LANG_SUBMIT_BUTTON_CAPTION"]);
                     echo "</td><td class=\"FormSpaceBetweenButtons\"></td><td>";
                     insertInputField("bReset", "reset", "", "", $GLOBALS["LANG_RESET_BUTTON_TIP"], $GLOBALS["LANG_RESET_BUTTON_CAPTION"]);
                     echo "</td>\n</tr>\n</table>\n";
                     break;
             }

             closeForm();
         }
         else
         {
             // The supporter isn't allowed to create or update a workgroup registration
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