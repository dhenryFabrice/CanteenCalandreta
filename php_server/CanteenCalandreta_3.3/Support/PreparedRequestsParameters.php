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
 * Support module : define the prepared requests parameters
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-10
 */


 if (!isSet($Page))
 {
     $Page = 1;
 }

 // All fieldnames which can be displayed in th result table
 $PREPARED_REQUESTS_ALL_FIELDNAMES          = array();

 // All order by filednames used to sort the result table
 $PREPARED_REQUESTS_ALL_ORDER_BY_FIELDNAMES = array();

 // All captions which can be displayed in the result table
 $PREPARED_REQUESTS_ALL_CAPTIONS            = array();

 // Prepared requests parameters
 $PREPARED_REQUESTS_PARAMETERS = array(
                                       1 => array(
                                                  "Extraction informations familles actives" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractInfosFamiliesList",
                                                                               Params => array("ResultFilename" => "ListeInfosFamilles.xls")
                                                                              ),
                                                  "Extraction familles à désactiver" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractFamiliesToDesactivate",
                                                                               Params => array("ResultFilename" => "ListeFamillesADesactiver.xls")
                                                                              ),
                                                  "Extraction e-mails familles pour MAJ mailing-list parents" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractMailsFamiliesList",
                                                                               Params => array("ResultFilename" => "MailingListParents.txt")
                                                                              ),
                                                  "Extraction e-mails familles pour MAJ mailing-lists classes" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractMailsClassroomsList",
                                                                               Params => array("ResultFilename" => "MailingListsClasses.txt")
                                                                              ),
                                                  "Extraction montants totaux par mois" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractTotalAmountByMonth",
                                                                               Params => array("ResultFilename" => "MontantsTotauxParMois.xls", "BillForDate" => array(date("01/m/Y", strtotime("13 Months ago")), date("t/m/Y", strtotime("last Month"))))
                                                                              ),
                                                  "Extraction cantines par mois année scolaire 2012-1013" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractSchoolYearCanteensByMonth",
                                                                               Params => array("ResultFilename" => "CantinesParMois.xls", "CanteenRegistrationForDate" => array("01/09/2012", "31/07/2013"))
                                                                              ),
                                                  "Extraction cantines (enfants/ajudes) par mois année scolaire 2012-1013" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractSchoolYearSplittedCanteensByMonth",
                                                                               Params => array("ResultFilename" => "CantinesEnfantsAjudesParMois.xls", "CanteenRegistrationForDate" => array("01/09/2012", "31/07/2013"))
                                                                              ),
                                                  "Extraction paiements mois ".date("m-Y", strtotime("4 Month ago")) => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractPaymentsList",
                                                                               Params => array("ResultFilename" => "Paiements_".date("m-Y", strtotime("4 Month ago")).".xls", "BillForDate" => array(date("01/m/Y", strtotime("4 Month ago")), date("t/m/Y", strtotime("4 Month ago"))))
                                                                              ),
                                                  "Extraction paiements mois ".date("m-Y", strtotime("3 Month ago")) => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractPaymentsList",
                                                                               Params => array("ResultFilename" => "Paiements_".date("m-Y", strtotime("3 Month ago")).".xls", "BillForDate" => array(date("01/m/Y", strtotime("3 Month ago")), date("t/m/Y", strtotime("3 Month ago"))))
                                                                              ),
                                                  "Extraction paiements mois ".date("m-Y", strtotime("2 Month ago")) => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractPaymentsList",
                                                                               Params => array("ResultFilename" => "Paiements_".date("m-Y", strtotime("2 Month ago")).".xls", "BillForDate" => array(date("01/m/Y", strtotime("2 Month ago")), date("t/m/Y", strtotime("2 Month ago"))))
                                                                              ),
                                                  "Extraction paiements mois ".date("m-Y", strtotime("last month")) => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractPaymentsList",
                                                                               Params => array("ResultFilename" => "Paiements_".date("m-Y", strtotime("last month")).".xls", "BillForDate" => array(date("01/m/Y", strtotime("last month")), date("t/m/Y", strtotime("last month"))))
                                                                              ),
                                                  "Extraction familles en difficulté de paiement" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractFamiliesWithPaymentsPbs",
                                                                               Params => array("ResultFilename" => "FamillesAvecPbPaiement.xls", "AlertBalance" => -300.00)
                                                                              ),
                                                  "Extraction enfants ayant leur cotisation mensuelle suspendue" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractSuspensionsChildren",
                                                                               Params => array("ResultFilename" => "EnfantsSuspendus.xls")
                                                                              ),
                                                  "Extraction enfants ayant leur cotisation mensuelle suspendue mais allant à l'école" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractSuspensionsChildrenGoingToSchool",
                                                                               Params => array("ResultFilename" => "EnfantsSuspendusAllantAEcole.xls")
                                                                              ),
                                                  "Extraction enfants actifs de Muret" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractChildrenOfTowns",
                                                                               Params => array("TownID" => array(1), "ResultFilename" => "EnfantsDeMuret.xls")
                                                                              ),
                                                  "Extraction enfants actifs pas de Muret" => array(
                                                                               Fieldnames => array(),
                                                                               Fctname => "ExtractChildrenOfTowns",
                                                                               Params => array("NotTownID" => array(1), "ResultFilename" => "EnfantsPasDeMuret.xls")
                                                                              )
                                                 )
                                      ); // Keys are support members ID ans values, arrays
?>