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
 * Admin module : update CanteenRegistrations table.
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2016-11-02 : load some configuration variables from database
 *
 * @since 2012-04-13
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

 // Configuration variables
 $DOCUMENT_ROOT = getIntranetRootDirectoryHDD();
 $Filename = basename(str_replace(array("Admin"), array(), __FILE__), ".php");
 $CONF_ADMIN_INPUT_FILE_PATH = dirname(__FILE__)."/Import".$Filename.".csv";


 define('ADMIN_DEFAULT_CANTEEN_REGISTRATION_WITHOUT_PORK', 0);


 $CONF_ADMIN_REQUIRED_FIELDS = array(
                                     "Families" => array("FamilyLastname"),
                                     "Children" => array("ChildFirstname"),
                                     "CanteenRegistrations" => array("CanteenRegistrationDate", "CanteenRegistrationForDate",
                                                                     "CanteenRegistrationWithoutPork")
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
                         $RecordToImport['Families']['FamilyLastname'] = ucfirst(strtolower($Value));
                         break;

                     /**** Data for the Children table ****/
                     case "prénom":
                         $RecordToImport['Children']['ChildFirstname'] = ucfirst(strtolower($Value));
                         break;

                     /**** Data for the CanteenRegistrations table ****/
                     case "date début semaine":
                         if (strpos($Value, '/') !== FALSE)
                         {
                             // Frensh format
                             $Value = formatedDate2EngDate($Value);
                         }

                         if (empty($Value))
                         {
                             $RecordToImport['CanteenRegistrations']['StartDate'] = NULL;
                             $RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'] = NULL;
                         }
                         else
                         {
                             $RecordToImport['CanteenRegistrations']['StartDate'] = $Value;
                             $RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'] = array();
                         }
                         break;

                     case "lundi":
                         if (($Value == 1) && (!empty($RecordToImport['CanteenRegistrations']['StartDate'])))
                         {
                             $RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'][] = $RecordToImport['CanteenRegistrations']['StartDate'];
                         }
                         break;

                     case "mardi":
                         if (($Value == 1) && (!empty($RecordToImport['CanteenRegistrations']['StartDate'])))
                         {
                             $RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'][] = date('Y-m-d',
                                                                                                            strtotime("+1 days",
                                                                                                                      strtotime($RecordToImport['CanteenRegistrations']['StartDate'])));
                         }
                         break;

                     case "merdredi":
                         if (($Value == 1) && (!empty($RecordToImport['CanteenRegistrations']['StartDate'])))
                         {
                             $RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'][] = date('Y-m-d',
                                                                                                            strtotime("+2 days",
                                                                                                                      strtotime($RecordToImport['CanteenRegistrations']['StartDate'])));
                         }
                         break;

                     case "jeudi":
                         if (($Value == 1) && (!empty($RecordToImport['CanteenRegistrations']['StartDate'])))
                         {
                             $RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'][] = date('Y-m-d',
                                                                                                            strtotime("+3 days",
                                                                                                                      strtotime($RecordToImport['CanteenRegistrations']['StartDate'])));
                         }
                         break;

                     case "vendredi":
                         if (($Value == 1) && (!empty($RecordToImport['CanteenRegistrations']['StartDate'])))
                         {
                             $RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'][] = date('Y-m-d',
                                                                                                            strtotime("+4 days",
                                                                                                                      strtotime($RecordToImport['CanteenRegistrations']['StartDate'])));
                         }
                         break;
                 }

                 unset($RecordToImport[$Field]);
             }

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
                 //  Get The ID of the child
                 $ArrayChildren = dbSearchChild($DbCon, array(
                                                              "FamilyLastname" => $RecordToImport['Families']['FamilyLastname'],
                                                              "ChildFirstname" => $RecordToImport['Children']['ChildFirstname']
                                                             ), "ChildFirstname", 1, 0);

                 if ((empty($ArrayChildren['ChildID'])) && (strpos($RecordToImport['Families']['FamilyLastname'], ' ') !== FALSE))
                 {
                     // Replace whitespaces by '-' and retry the search
                     $sTmpFamilyLastname = str_replace(array(' '), array('-'), $RecordToImport['Families']['FamilyLastname']);

                     $ArrayChildren = dbSearchChild($DbCon, array(
                                                                  "FamilyLastname" => $sTmpFamilyLastname,
                                                                  "ChildFirstname" => $RecordToImport['Children']['ChildFirstname']
                                                                 ), "ChildFirstname", 1, 0);
                 }

                 if ((isset($ArrayChildren['ChildID'])) && (count($ArrayChildren['ChildID']) == 1))
                 {
                     // We get if the child is with or withou pork
                     $ChildID = $ArrayChildren['ChildID'][0];
                     $RecordChild = getTableRecordInfos($DbCon, 'Children', $ChildID);
                     if (!empty($RecordChild))
                     {
                         $CurrTime = strtotime(date('Y-m-01'));
                         foreach($RecordToImport['CanteenRegistrations']['CanteenRegistrationForDate'] as $d => $Day)
                         {
                             $Valided = 0;
                             $DateTime = strtotime(date('Y-m-01', strtotime($Day)));
                             if ($DateTime < $CurrTime)
                             {
                                 $Valided = 1;
                             }

                             $CanteenRegistrationID = dbAddCanteenRegistration($DbCon, date('Y-m-d'), $Day, $ChildID,
                                                                               $RecordChild['ChildWithoutPork'], $Valided, NULL);

                             if ($CanteenRegistrationID > 0)
                             {
                                 echo "<p>Enfant <b>".$RecordToImport['Families']['FamilyLastname']
                                      ." ".$RecordToImport['Children']['ChildFirstname']."</b> inscrit à la cantine pour le "
                                      ."<b>".date('d/m/Y', strtotime($Day))."</b>, (repas sans pork = <b>"
                                      .$RecordChild['ChildWithoutPork']."</b>).</p>\n";
                             }
                             else
                             {
                                 echo "<p style=\"color: #f00;\">ERREUR inscription cantine enfant <b>"
                                      .$RecordToImport['Families']['FamilyLastname']." ".$RecordToImport['Children']['ChildFirstname']
                                      ."</b> inscrit à la cantine pour le <b>".date('d/m/Y', strtotime($Day))
                                      ."</b>, (repas sans pork = <b>".$RecordChild['ChildWithoutPork']."</b>).</p>\n";
                             }
                         }
                     }
                 }
                 else
                 {
                     echo "<p style=\"color: #f00;\">ERREUR, enfant <b>".$RecordToImport['Families']['FamilyLastname']
                      ." ".$RecordToImport['Children']['ChildFirstname']."</b> pas trouvé!</p>\n";
                 }
             }
             else
             {
                 echo "<p style=\"color: #f00;\">ERREUR sur Enfant <b>".$RecordToImport['Families']['FamilyLastname']
                      ." ".$RecordToImport['Children']['ChildFirstname']."</b>.</p>\n";
             }
         }
     }
 }
 else
 {
     echo "<p style=\"color: #f00;\">Le schéma et le fichier d'import n'ont pas le même nb de colonnes!</p>\n";
 }

 dbDisconnection($DbCon);
?>
