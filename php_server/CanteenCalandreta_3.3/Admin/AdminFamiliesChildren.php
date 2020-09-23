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
 * Admin module : update Families, Children, Towns and HistoLevelsChildren tables.
 * If no entry in SupportMembers table for the family, we create an account.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2013-06-21 : taken into account the new structure of the CONF_CLASSROOMS variable
 *                    (includes school year)
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-04-06
 */


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


 function formatName($Name)
 {
     // Format a lastname or a firstname
     if (!empty($Name))
     {
         $Name = trim($Name);

         // Check the separator
         $ArraySeparators = array("-", " ", "'");
         $Separator = NULL;
         foreach($ArraySeparators as $s => $Sep)
         {
             if (stripos($Name, $Sep))
             {
                 $Separator = $Sep;
                 break;
             }
         }

         if (is_null($Separator))
         {
             $Name = ucfirst(strtolower($Name));
         }
         else
         {
             $ArrayTmp = explode($Separator, $Name);
             foreach($ArrayTmp as $a => $sTmp)
             {
                 $ArraySecSepName = explode("'", $sTmp);
                 foreach($ArraySecSepName as $s => $CurrName)
                 {
                     $ArraySecSepName[$s] = ucfirst(strtolower($CurrName));
                 }

                 $ArrayTmp[$a] = implode("'", $ArraySecSepName);
             }

             $Name = implode($Separator, $ArrayTmp);
         }
     }

     return $Name;
 }


 // Configuration variables
 $DOCUMENT_ROOT = getIntranetRootDirectoryHDD();
 $Filename = basename(str_replace(array("Admin"), array(), __FILE__), ".php");
 $CONF_ADMIN_INPUT_FILE_PATH = dirname(__FILE__)."/Import".$Filename.".csv";

 define('ADMIN_DEFAULT_FAMILY_NB_MEMBERS', 1);
 define('ADMIN_DEFAULT_FAMILY_NB_POWERED_MEMBERS', 0);
 define('ADMIN_DEFAULT_FAMILY_BALANCE', 0.00);
 define('ADMIN_DEFAULT_CHILD_WITHOUT_PORK', 0);
 define('ADMIN_DEFAULT_CHILD_GRADE', 0);
 define('ADMIN_DEFAULT_CHILD_CLASSROOM', 0);
 define('ADMIN_DEFAULT_SUPPORT_MEMBER_STATE_ID', 5);
 define('ADMIN_DEFAULT_SUPPORT_MEMBER_PWD', "calandreta");

 $CONF_ADMIN_REQUIRED_FIELDS = array(
                                     "Towns" => array("TownName", "TownCode"),
                                     "Families" => array("FamilyLastname", "FamilyDate", "FamilyNbMembers", "FamilyMainEmail"),
                                     "Children" => array("ChildFirstname", "ChildSchoolDate", "ChildGrade", "ChildClass",
                                                         "ChildWithoutPork")
                                    );

 include_once($DOCUMENT_ROOT.'GUI/GraphicInterface.php');

 $DbCon = dbConnection();

 // Load all configuration variables from database
 loadDbConfigParameters($DbCon, array('CONF_SCHOOL_YEAR_START_DATES',
                                      'CONF_CLASSROOMS',
                                      'CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS',
                                      'CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS',
                                      'CONF_CANTEEN_PRICES',
                                      'CONF_NURSERY_PRICES',
                                      'CONF_NURSERY_DELAYS_PRICES'));

 // Read the CSV schema file
 $SchemaCSVFile = file(dirname(__FILE__)."/Schema".$Filename.".csv");

 // Read the CSV data file
 $DataCSVFile = getContentCSVFile($CONF_ADMIN_INPUT_FILE_PATH, 200000);

 // Check if the first line of the CSV file is the same as the schema
 $Schema = trim($SchemaCSVFile[0]);

 $ArrayColumns = explode(';', $Schema);
 foreach($ArrayColumns as $c => $ColName)
 {
     $ArrayColumns[$c] = strtolower($ColName);
 }

 if (count($DataCSVFile[0]) == count($ArrayColumns))
 {
     $CurrentSchoolYear = getSchoolYear(date('Y-m-d'));
     foreach($DataCSVFile as $i => $CurrentLine)
     {
         // We don't treat the first line (headers)
         if ($i > 0)
         {
             // Init the structure of data
             $iArrayColumnsSize = count($ArrayColumns);
             $iCurrentLineSize = count($CurrentLine);
             if ($iCurrentLineSize < $iArrayColumnsSize)
             {
                 $CurrentLine = array_pad($CurrentLine, $iArrayColumnsSize, '');
             }

             $RecordToImport = array_combine($ArrayColumns, $CurrentLine);
             foreach($RecordToImport as $Field => $Value)
             {
                 $Value = strip_tags(trim($Value));
                 switch(strtolower($Field))
                 {
                     /**** Data for the Families table ****/
                     case "nom":
                         $RecordToImport['Families']['FamilyLastname'] = formatName($Value);
                         break;

                     case "date première inscription":
                         if (empty($Value))
                         {
                             // No date, by default, the first day of the current school year
                             $Value = $CONF_SCHOOL_YEAR_START_DATES[$CurrentSchoolYear];
                         }
                         elseif (strpos($Value, '/') !== FALSE)
                         {
                             // Frensh format
                             $Value = formatedDate2EngDate($Value);
                         }

                         $RecordToImport['Families']['FamilyDate'] = $Value;
                         $RecordToImport['Children']['ChildSchoolDate'] = $Value;
                         break;

                     case "adresse e-mail principale":
                         if (empty($Value))
                         {
                             $Value = str_replace(array(" ", "'"), array("", "-"), $RecordToImport['Families']['FamilyLastname']);
                             $Value .= "@test-calandreta.fr";
                         }

                         $RecordToImport['Families']['FamilyMainEmail'] = strtolower($Value);
                         break;

                     case "adresse e-mail secondaire":
                         $RecordToImport['Families']['FamilySecondEmail'] = strtolower($Value);
                         break;

                     case "solde":
                         if (empty($Value))
                         {
                             $RecordToImport['Families']['FamilyBalance'] = ADMIN_DEFAULT_FAMILY_BALANCE;
                         }
                         else
                         {
                             $RecordToImport['Families']['FamilyBalance'] = $Value;
                         }
                         break;

                     /**** Data for the Children table ****/
                     case "prénom":
                         $RecordToImport['Children']['ChildFirstname'] = formatName($Value);
                         break;

                     case "niveau":
                         $RecordToImport['Children']['ChildGrade'] = NULL;
                         $iPos = array_search(strtoupper($Value), $CONF_GRADES);
                         if ($iPos !== FALSE)
                         {
                             $RecordToImport['Children']['ChildGrade'] = $iPos;
                         }
                         else
                         {
                             $RecordToImport['Children']['ChildGrade'] = ADMIN_DEFAULT_CHILD_GRADE;
                         }
                         break;

                     case "classe":
                         $RecordToImport['Children']['ChildClass'] = NULL;
                         $iPos = array_search(strtoupper($Value), $CONF_CLASSROOMS[$CurrentSchoolYear]);
                         if ($iPos !== FALSE)
                         {
                             $RecordToImport['Children']['ChildClass'] = $iPos;
                         }
                         else
                         {
                             $RecordToImport['Children']['ChildClass'] = ADMIN_DEFAULT_CHILD_CLASSROOM;
                         }
                         break;

                     case "repas sans porc":
                         $RecordToImport['Children']['ChildWithoutPork'] = ADMIN_DEFAULT_CHILD_WITHOUT_PORK;
                         $Value = strtolower($Value);
                         if ($Value == "oui")
                         {
                             $RecordToImport['Children']['ChildWithoutPork'] = 1;
                         }
                         break;

                     /**** Data for the Towns table ****/
                     case "code postal":
                         $RecordToImport['Towns']['TownCode'] = NULL;
                         if (strlen($Value) == 5)
                         {
                             $RecordToImport['Towns']['TownCode'] = $Value;
                         }
                         break;

                     case "commune":
                         $RecordToImport['Towns']['TownName'] = ucfirst(strtolower($Value));
                         break;
                 }

                 unset($RecordToImport[$Field]);
             }

             $RecordToImport['Families']['FamilyNbMembers'] = ADMIN_DEFAULT_FAMILY_NB_MEMBERS;
             $RecordToImport['Families']['FamilyNbPoweredMembers'] = ADMIN_DEFAULT_FAMILY_NB_POWERED_MEMBERS;

             // We treat the record
             $bContinue = TRUE;
             foreach($RecordToImport as $Table => $aFields)
             {
                 foreach($aFields as $Field => $Value)
                 {
                     // Check if each required value is ok
                     if ((in_array($Field, $CONF_ADMIN_REQUIRED_FIELDS[$Table])) && (($Value === '') || (is_null($Value))))
                     {
                         // Error : required field with an empty value
                         $bContinue = FALSE;
                     }
                 }
             }

             if ($bContinue)
             {
                 // Check if town exists : create or get it's ID
                 $TownID = dbAddTown($DbCon, $RecordToImport['Towns']['TownName'], $RecordToImport['Towns']['TownCode']);
                 if ($TownID > 0)
                 {
                     // Check if the family exists : create or get it's ID
                     $FamilyID = dbAddFamily($DbCon, $RecordToImport['Families']['FamilyDate'],
                                             $RecordToImport['Families']['FamilyLastname'], $TownID,
                                             $RecordToImport['Families']['FamilyMainEmail'],
                                             $RecordToImport['Families']['FamilySecondEmail'],
                                             $RecordToImport['Families']['FamilyNbMembers'],
                                             $RecordToImport['Families']['FamilyNbPoweredMembers'],
                                             $RecordToImport['Families']['FamilyBalance'], '', NULL);

                     if ($FamilyID > 0)
                     {
                          // Family created, we chek if the family has an account to use the software
                          $SupportMemberID = getTableFieldValueByFieldName($DbCon, "SupportMembers", "SupportMemberID",
                                                                           "SupportMemberLastname", "=",
                                                                           $RecordToImport['Families']['FamilyLastname'],
                                                                           "SupportMemberID");
                          if (empty($SupportMemberID))
                          {
                              $SupportMemberFirstname = "-";
                              $SupportMemberPhone = "";

                              $SupportMemberID = dbAddSupportMember($DbCon, $RecordToImport['Families']['FamilyLastname'],
                                                                    $SupportMemberFirstname, $RecordToImport['Families']['FamilyMainEmail'],
                                                                    ADMIN_DEFAULT_SUPPORT_MEMBER_STATE_ID, $SupportMemberPhone, 1);

                              if ($SupportMemberID)
                              {
                                  // Create a login and password
                                  $sLogin = md5(strtolower($RecordToImport['Families']['FamilyLastname']));
                                  $sPassword = md5(strtolower(ADMIN_DEFAULT_SUPPORT_MEMBER_PWD));
                                  dbSetLoginPwdSupportMember($DbCon, $SupportMemberID, $sLogin, $sPassword);

                                  // Create a webservice key
                                  $sWebServiceKey = md5(strtolower($RecordToImport['Families']['FamilyLastname']).$SupportMemberID);
                                  dbSetWebServiceKeySupportMember($DbCon, $SupportMemberID, $sWebServiceKey);
                              }
                          }

                          $ArrayChildren = dbSearchChild($DbCon, array(
                                                                       "FamilyID" => array($FamilyID),
                                                                       "ChildFirstname" => $RecordToImport['Children']['ChildFirstname']
                                                                      ), "ChildFirstname", 1, 0);

                          if (!empty($ArrayChildren))
                          {
                              $ChildID = $ArrayChildren['ChildID'][0];
                          }
                          else
                          {
                              // We must create the child
                              $ChildID = dbAddChild($DbCon, $RecordToImport['Children']['ChildSchoolDate'],
                                                    $RecordToImport['Children']['ChildFirstname'], $FamilyID,
                                                    $RecordToImport['Children']['ChildGrade'], $RecordToImport['Children']['ChildClass'],
                                                    $RecordToImport['Children']['ChildWithoutPork'], NULL);
                          }

                          if ($ChildID > 0)
                          {
                              // Get the current history of the child
                              $ArrayHistoChild = getHistoLevelsChild($DbCon, $ChildID, "HistoLevelChildYear DESC,
                                                                     HistoLevelChildID DESC");

                              if ((isset($ArrayHistoChild['HistoLevelChildID'])) && (!empty($ArrayHistoChild['HistoLevelChildID'])))
                              {
                                  // We check if the first entry (so, the current state of the child) = current data about the child
                                  if ($ArrayHistoChild['HistoLevelChildYear'][0] == $CurrentSchoolYear)
                                  {
                                      $HistoChildID = dbUpdateHistoLevelChild($DbCon, $ArrayHistoChild['HistoLevelChildID'][0], $ChildID,
                                                                              $ArrayHistoChild['HistoLevelChildYear'][0],
                                                                              $RecordToImport['Children']['ChildGrade'],
                                                                              $RecordToImport['Children']['ChildClass'],
                                                                              $RecordToImport['Children']['ChildWithoutPork']);
                                  }
                                  elseif ($CurrentSchoolYear > $ArrayHistoChild['HistoLevelChildYear'][0])
                                  {
                                      $HistoChildID = dbAddHistoLevelChild($DbCon, $ChildID, $CurrentSchoolYear,
                                                                           $RecordToImport['Children']['ChildGrade'],
                                                                           $RecordToImport['Children']['ChildClass'],
                                                                           $RecordToImport['Children']['ChildWithoutPork']);
                                  }
                              }
                              else
                              {
                                  // No history, we create one entry for this school year
                                  $HistoChildID = dbAddHistoLevelChild($DbCon, $ChildID, $CurrentSchoolYear,
                                                                       $RecordToImport['Children']['ChildGrade'],
                                                                       $RecordToImport['Children']['ChildClass'],
                                                                       $RecordToImport['Children']['ChildWithoutPork']);
                              }

                              if ($HistoChildID > 0)
                              {
                                  echo "<p>Famille <b>".$RecordToImport['Families']['FamilyLastname']
                                       ."</b>, Enfant <b>".$RecordToImport['Children']['ChildFirstname']
                                       ."</b>, Commune <b>".$RecordToImport['Towns']['TownName']."</b> traité.</p>\n";
                              }
                          }
                     }
                 }
             }
             else
             {
                 echo "<p style=\"color: #f00;\">ERREUR sur Famille <b>".$RecordToImport['Families']['FamilyLastname']
                      ."</b>, Enfant <b>".$RecordToImport['Children']['ChildFirstname']
                      ."</b>, Commune <b>".$RecordToImport['Towns']['TownName']."</b>.</p>\n";
             }
         }
     }
 }

 dbDisconnection($DbCon);
?>
