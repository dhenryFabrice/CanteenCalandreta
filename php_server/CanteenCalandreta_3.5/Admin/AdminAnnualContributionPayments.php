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
 * Admin module : update Payments table (annual contribution).
 *
 * @author Christophe Javouhey
 * @version 3.0
 *     - 2013-02-05 : taken into account the new field "PaymentReceiptDate"
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


 function extractBankAcronymFromCheckNb($CheckNb)
 {
     if (!empty($CheckNb))
     {
         $iSize = strlen($CheckNb);
         $iStopCar = 0;
         for($i = 0; $i < $iSize; $i++)
         {
             switch($CheckNb{$i})
             {
                 case '0':
                 case '1':
                 case '2':
                 case '3':
                 case '4':
                 case '5':
                 case '6':
                 case '7':
                 case '8':
                 case '9':
                     // Stop
                     $iStopCar = $i - 1;
                     break 2;
             }
         }

         if ($iStopCar > 0)
         {
             $CheckNb = substr($CheckNb, 0, $iStopCar + 1);
         }
     }

     return $CheckNb;
 }

 // Configuration variables
 $DOCUMENT_ROOT = getIntranetRootDirectoryHDD();
 $Filename = basename(str_replace(array("Admin"), array(), __FILE__), ".php");
 $CONF_ADMIN_INPUT_FILE_PATH = dirname(__FILE__)."/Import".$Filename.".csv";


 define('ADMIN_DEFAULT_PAYMENT_TYPE', 0);
 define('ADMIN_DEFAULT_PAYMENT_MODE', 1);


 $CONF_ADMIN_REQUIRED_FIELDS = array(
                                     "Families" => array("FamilyLastname", "FamilyNbMembers"),
                                     "Payments" => array("PaymentDate", "PaymentType", "PaymentMode", "PaymentAmount")
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
     $CurrentSchoolYearStartDate = NULL;
     if (isset($CONF_SCHOOL_YEAR_START_DATES[$CurrentSchoolYear]))
     {
         $CurrentSchoolYearStartDate = $CONF_SCHOOL_YEAR_START_DATES[$CurrentSchoolYear];
     }

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

                     case "nb membres":
                         $RecordToImport['Families']['FamilyNbMembers'] = NULL;
                         if ((!empty($Value)) && (isInteger($Value)))
                         {
                             $RecordToImport['Families']['FamilyNbMembers'] = $Value;
                         }
                         break;

                     /**** Data for the Payments table ****/
                     case "montant":
                         $RecordToImport['Payments']['PaymentAmount'] = NULL;
                         if ((!empty($Value)) && ((isInteger($Value)) || (isFloat($Value))))
                         {
                             $RecordToImport['Payments']['PaymentAmount'] = $Value;
                         }
                         break;

                     case "mode paiement";
                         if ((strtolower($Value) == 'esp') || (strtolower($Value) == 'eps')|| (empty($Value)))
                         {
                             $RecordToImport['Payments']['PaymentMode'] = 0;
                             $RecordToImport['Payments']['PaymentCheckNb'] = NULL;
                         }
                         else
                         {
                             $RecordToImport['Payments']['PaymentMode'] = 1;
                             $RecordToImport['Payments']['PaymentCheckNb'] = strtoupper($Value);
                         }
                         break;

                     case "date paiement":
                         if (strpos($Value, '/') !== FALSE)
                         {
                             // Frensh format
                             $Value = formatedDate2EngDate($Value);
                         }

                         if (empty($Value))
                         {
                             $RecordToImport['Payments']['PaymentDate'] = $CurrentSchoolYearStartDate;
                         }
                         else
                         {
                             $RecordToImport['Payments']['PaymentDate'] = $Value;
                         }
                         break;
                 }

                 unset($RecordToImport[$Field]);
             }

             // Payment for annual contribution
             $RecordToImport['Payments']['PaymentType'] = ADMIN_DEFAULT_PAYMENT_TYPE;

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
                 //  Get The ID of the family
                 $ArrayFamilies = dbSearchFamily($DbCon, array(
                                                               "FamilyLastname" => $RecordToImport['Families']['FamilyLastname'],
                                                              ), "FamilyLastname", 1, 0);

                 if ((empty($ArrayFamilies)) && (strpos($RecordToImport['Families']['FamilyLastname'], ' ') !== FALSE))
                 {
                     // Replace whitespaces by '-' and retry the search
                     $sTmpFamilyLastname = str_replace(array(' '), array('-'), $RecordToImport['Families']['FamilyLastname']);

                     $ArrayFamilies = dbSearchFamily($DbCon, array(
                                                                   "FamilyLastname" => $sTmpFamilyLastname,
                                                                  ), "FamilyLastname", 1, 0);
                 }

                 if ((isset($ArrayFamilies['FamilyID'])) && (count($ArrayFamilies['FamilyID']) == 1))
                 {
                     $FamilyID = $ArrayFamilies['FamilyID'][0];

                     // Get the bank ID
                     if ($RecordToImport['Payments']['PaymentMode'] > 0)
                     {
                         $BankAcronym = extractBankAcronymFromCheckNb($RecordToImport['Payments']['PaymentCheckNb']);
                         $BankID = getBankID($DbCon, $BankAcronym);
                         if ($BankID <= 0)
                         {
                             $bContinue = FALSE;
                         }
                     }

                     // We check if the amount is right in relation with the nb of members
                     if (isset($CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$CurrentSchoolYear][$RecordToImport['Families']['FamilyNbMembers']]))
                     {
                         $Price = $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS[$CurrentSchoolYear][$RecordToImport['Families']['FamilyNbMembers']];
                         if ($Price != $RecordToImport['Payments']['PaymentAmount'])
                         {
                             // Wrong amount
                             $bContinue = FALSE;
                         }
                     }

                     if ($bContinue)
                     {
                         $PaymentID = dbAddPayment($DbCon, date('Y-m-d H:i.s', strtotime($RecordToImport['Payments']['PaymentDate'].' 11:29:17')),
                                                   $RecordToImport['Payments']['PaymentDate'], $FamilyID,
                                                   $RecordToImport['Payments']['PaymentAmount'],
                                                   $RecordToImport['Payments']['PaymentType'], $RecordToImport['Payments']['PaymentMode'],
                                                   $RecordToImport['Payments']['PaymentCheckNb'], $BankID, NULL);

                         if ($PaymentID > 0)
                         {
                             // Update the number of members of the family
                             $DbCon->query("UPDATE Families SET FamilyNbMembers = ".$RecordToImport['Families']['FamilyNbMembers']
                                           ." WHERE FamilyID = $FamilyID");

                             echo "<p>Cotisation annuelle <b>".$RecordToImport['Families']['FamilyLastname']."</b> de <b>"
                                  .$RecordToImport['Payments']['PaymentAmount']." ".$CONF_PAYMENTS_UNIT."</b> ("
                                  .$RecordToImport['Payments']['PaymentCheckNb'].").</p>\n";
                         }
                         else
                         {
                             echo "<p style=\"color: #f00;\">ERREUR paiement Cotisation annuelle <b>".$RecordToImport['Families']['FamilyLastname']."</b> de <b>"
                                  .$RecordToImport['Payments']['PaymentAmount']." ".$CONF_PAYMENTS_UNIT."</b> ("
                                  .$RecordToImport['Payments']['PaymentCheckNb'].").</p>\n";
                         }
                     }
                     else
                     {
                         echo "<p style=\"color: #f00;\">Banque pas trouvée ou mauvais montant pour la cotisation annuelle <b>"
                              .$RecordToImport['Families']['FamilyLastname']."</b> de <b>".$RecordToImport['Payments']['PaymentAmount']." ".$CONF_PAYMENTS_UNIT."</b>("
                              .$RecordToImport['Payments']['PaymentCheckNb'].").</p>\n";
                     }
                 }
                 else
                 {
                     echo "<p style=\"color: #f00;\">ERREUR, famille <b>".$RecordToImport['Families']['FamilyLastname']
                      ."</b> pas trouvé!</p>\n";
                 }
             }
             else
             {
                 echo "<p style=\"color: #f00;\">ERREUR sur Famille <b>".$RecordToImport['Families']['FamilyLastname']."</b>.</p>\n";
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
