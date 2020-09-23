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
 * Support module : access rules of the user groups to the contextual menus of the application.
 *
 * The table contains the ID of each user group. For each user group, we define a table wich contains the
 * web pages that the user group can access.
 * ATTENTION : the order of each contextual menu item in the array is very important and musn't change!
 *
 * @author Christophe Javouhey
 * @version 3.1
 * @since 2012-01-10
 */


 $ACCESS_CONTEXTUALMENUS = array(
                       /* admin */
                       1 => array(
                                  'canteen' => array(),
                                  'cooperation' => array(),
                                  'admin' => array(),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => TRUE,
                                                        Param_FamilyDetails => FALSE,
                                                        Param_CreateAlias => TRUE,
                                                        Param_AliasList => TRUE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => TRUE
                                                       )
                                 ),
                       /* resp fact */
                       2 => array(
                                  'canteen' => array(
                                                     Canteen_CreateFamily => TRUE,
                                                     Canteen_FamiliesList => TRUE,
                                                     Canteen_AddPayment => FALSE,
                                                     Canteen_PaymentsSynthesis => TRUE,
                                                     Canteen_GenerateMonthlyBill => TRUE,
                                                     Canteen_GenerateAnnualBill => TRUE,
                                                     Canteen_PrepareNewYearFamilies => FALSE,
                                                     Canteen_PrepareNewYearChildren => FALSE,
                                                     Canteen_CanteenPlanning => TRUE,
                                                     Canteen_CanteenAnnualRegistrations => TRUE,
                                                     Canteen_WeekSynthesis => TRUE,
                                                     Canteen_DaySynthesis => TRUE,
                                                     Canteen_NurseryPlanning => TRUE,
                                                     Canteen_NurseryDaySynthesis => TRUE,
                                                     Canteen_NurseryDelays => TRUE,
                                                     Canteen_SnackPlanning => FALSE,
                                                     Canteen_LaundryPlanning => FALSE,
                                                     Canteen_ExitPermissions => FALSE,
                                                     Canteen_AddDocumentApproval => FALSE,
                                                     Canteen_DocumentsApprovalsList => TRUE
                                                    ),
                                  'cooperation' => array(
                                                         Coop_CreateEvent => FALSE,
                                                         Coop_FestiveEventsList => FALSE,
                                                         Coop_MaintenanceEventsList => FALSE,
                                                         Coop_ClosedEventsList => FALSE,
                                                         Coop_FamiliesList => FALSE,
                                                         Coop_CreateWorkGroup => FALSE,
                                                         Coop_WorkGroupsList => FALSE,
                                                         Coop_CreateDonation => FALSE,
                                                         Coop_DonationsList => TRUE,
                                                         Coop_GenerateTaxReceipt => FALSE
                                                        ),
                                  'admin' => array(
                                                   Admin_CreateSupportMember => FALSE,
                                                   Admin_SupportMembersList => FALSE,
                                                   Admin_SupportMembersStatesList => FALSE,
                                                   Admin_HolidaysList => FALSE,
                                                   Admin_OpenedSpecialDaysList => FALSE,
                                                   Admin_SwapSnackPlanning => FALSE,
                                                   Admin_LaundryPlanning => FALSE,
                                                   Admin_EventTypesList => FALSE,
                                                   Admin_ConfigParametersList => FALSE
                                                  ),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => TRUE,
                                                        Param_FamilyDetails => FALSE,
                                                        Param_CreateAlias => FALSE,
                                                        Param_AliasList => FALSE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => TRUE
                                                       )
                                 ),
                       /* resp cantine */
                       3 => array(
                                  'canteen' => array(
                                                     Canteen_CreateFamily => FALSE,
                                                     Canteen_FamiliesList => FALSE,
                                                     Canteen_AddPayment => FALSE,
                                                     Canteen_PaymentsSynthesis => FALSE,
                                                     Canteen_GenerateMonthlyBill => FALSE,
                                                     Canteen_GenerateAnnualBill => FALSE,
                                                     Canteen_PrepareNewYearFamilies => FALSE,
                                                     Canteen_PrepareNewYearChildren => FALSE,
                                                     Canteen_CanteenPlanning => TRUE,
                                                     Canteen_CanteenAnnualRegistrations => TRUE,
                                                     Canteen_WeekSynthesis => TRUE,
                                                     Canteen_DaySynthesis => TRUE,
                                                     Canteen_NurseryPlanning => FALSE,
                                                     Canteen_NurseryDaySynthesis => FALSE,
                                                     Canteen_NurseryDelays => FALSE,
                                                     Canteen_SnackPlanning => FALSE,
                                                     Canteen_LaundryPlanning => FALSE,
                                                     Canteen_ExitPermissions => FALSE,
                                                     Canteen_AddDocumentApproval => FALSE,
                                                     Canteen_DocumentsApprovalsList => TRUE
                                                    ),
                                  'cooperation' => array(
                                                         Coop_CreateEvent => FALSE,
                                                         Coop_FestiveEventsList => FALSE,
                                                         Coop_MaintenanceEventsList => FALSE,
                                                         Coop_ClosedEventsList => FALSE,
                                                         Coop_FamiliesList => FALSE,
                                                         Coop_CreateWorkGroup => FALSE,
                                                         Coop_WorkGroupsList => FALSE,
                                                         Coop_CreateDonation => FALSE,
                                                         Coop_DonationsList => FALSE,
                                                         Coop_GenerateTaxReceipt => FALSE
                                                        ),
                                  'admin' => array(
                                                   Admin_CreateSupportMember => FALSE,
                                                   Admin_SupportMembersList => FALSE,
                                                   Admin_SupportMembersStatesList => FALSE,
                                                   Admin_HolidaysList => FALSE,
                                                   Admin_OpenedSpecialDaysList => FALSE,
                                                   Admin_SwapSnackPlanning => FALSE,
                                                   Admin_LaundryPlanning => FALSE,
                                                   Admin_EventTypesList => FALSE,
                                                   Admin_ConfigParametersList => FALSE
                                                  ),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => TRUE,
                                                        Param_FamilyDetails => FALSE,
                                                        Param_CreateAlias => FALSE,
                                                        Param_AliasList => FALSE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => TRUE
                                                       )
                                 ),
                       /* ajude */
                       4 => array(
                                  'canteen' => array(
                                                     Canteen_CreateFamily => FALSE,
                                                     Canteen_FamiliesList => FALSE,
                                                     Canteen_AddPayment => FALSE,
                                                     Canteen_PaymentsSynthesis => FALSE,
                                                     Canteen_GenerateMonthlyBill => FALSE,
                                                     Canteen_GenerateAnnualBill => FALSE,
                                                     Canteen_PrepareNewYearFamilies => FALSE,
                                                     Canteen_PrepareNewYearChildren => FALSE,
                                                     Canteen_CanteenPlanning => FALSE,
                                                     Canteen_CanteenAnnualRegistrations => FALSE,
                                                     Canteen_WeekSynthesis => FALSE,
                                                     Canteen_DaySynthesis => TRUE,
                                                     Canteen_NurseryPlanning => TRUE,
                                                     Canteen_NurseryDaySynthesis => TRUE,
                                                     Canteen_NurseryDelays => TRUE,
                                                     Canteen_SnackPlanning => TRUE,
                                                     Canteen_LaundryPlanning => TRUE,
                                                     Canteen_ExitPermissions => TRUE,
                                                     Canteen_AddDocumentApproval => FALSE,
                                                     Canteen_DocumentsApprovalsList => TRUE
                                                    ),
                                  'cooperation' => array(
                                                         Coop_CreateEvent => FALSE,
                                                         Coop_FestiveEventsList => TRUE,
                                                         Coop_MaintenanceEventsList => TRUE,
                                                         Coop_ClosedEventsList => FALSE,
                                                         Coop_FamiliesList => FALSE,
                                                         Coop_CreateWorkGroup => FALSE,
                                                         Coop_WorkGroupsList => FALSE,
                                                         Coop_CreateDonation => FALSE,
                                                         Coop_DonationsList => FALSE,
                                                         Coop_GenerateTaxReceipt => FALSE
                                                        ),
                                  'admin' => array(
                                                   Admin_CreateSupportMember => FALSE,
                                                   Admin_SupportMembersList => FALSE,
                                                   Admin_SupportMembersStatesList => FALSE,
                                                   Admin_HolidaysList => FALSE,
                                                   Admin_OpenedSpecialDaysList => FALSE,
                                                   Admin_SwapSnackPlanning => FALSE,
                                                   Admin_LaundryPlanning => FALSE,
                                                   Admin_EventTypesList => FALSE,
                                                   Admin_ConfigParametersList => FALSE
                                                  ),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => FALSE,
                                                        Param_FamilyDetails => FALSE,
                                                        Param_CreateAlias => FALSE,
                                                        Param_AliasList => FALSE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => FALSE
                                                       )
                                 ),
                       /* parent */
                       5 => array(
                                  'canteen' => array(
                                                     Canteen_CreateFamily => FALSE,
                                                     Canteen_FamiliesList => FALSE,
                                                     Canteen_AddPayment => FALSE,
                                                     Canteen_PaymentsSynthesis => FALSE,
                                                     Canteen_GenerateMonthlyBill => FALSE,
                                                     Canteen_GenerateAnnualBill => FALSE,
                                                     Canteen_PrepareNewYearFamilies => FALSE,
                                                     Canteen_PrepareNewYearChildren => FALSE,
                                                     Canteen_CanteenPlanning => TRUE,
                                                     Canteen_CanteenAnnualRegistrations => FALSE,
                                                     Canteen_WeekSynthesis => FALSE,
                                                     Canteen_DaySynthesis => FALSE,
                                                     Canteen_NurseryPlanning => TRUE,
                                                     Canteen_NurseryDaySynthesis => FALSE,
                                                     Canteen_NurseryDelays => FALSE,
                                                     Canteen_SnackPlanning => TRUE,
                                                     Canteen_LaundryPlanning => TRUE,
                                                     Canteen_ExitPermissions => TRUE,
                                                     Canteen_AddDocumentApproval => FALSE,
                                                     Canteen_DocumentsApprovalsList => TRUE
                                                    ),
                                  'cooperation' => array(
                                                         Coop_CreateEvent => FALSE,
                                                         Coop_FestiveEventsList => TRUE,
                                                         Coop_MaintenanceEventsList => TRUE,
                                                         Coop_ClosedEventsList => FALSE,
                                                         Coop_FamiliesList => FALSE,
                                                         Coop_CreateWorkGroup => FALSE,
                                                         Coop_WorkGroupsList => TRUE,
                                                         Coop_CreateDonation => FALSE,
                                                         Coop_DonationsList => FALSE,
                                                         Coop_GenerateTaxReceipt => FALSE
                                                        ),
                                  'admin' => array(
                                                   Admin_CreateSupportMember => FALSE,
                                                   Admin_SupportMembersList => FALSE,
                                                   Admin_SupportMembersStatesList => FALSE,
                                                   Admin_HolidaysList => FALSE,
                                                   Admin_OpenedSpecialDaysList => FALSE,
                                                   Admin_SwapSnackPlanning => FALSE,
                                                   Admin_LaundryPlanning => FALSE,
                                                   Admin_EventTypesList => FALSE,
                                                   Admin_ConfigParametersList => FALSE
                                                  ),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => FALSE,
                                                        Param_FamilyDetails => TRUE,
                                                        Param_CreateAlias => FALSE,
                                                        Param_AliasList => FALSE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => TRUE
                                                       )
                                 ),
                       /* resp admin */
                       6 => array(
                                  'canteen' => array(
                                                     Canteen_CreateFamily => TRUE,
                                                     Canteen_FamiliesList => TRUE,
                                                     Canteen_AddPayment => FALSE,
                                                     Canteen_PaymentsSynthesis => FALSE,
                                                     Canteen_GenerateMonthlyBill => FALSE,
                                                     Canteen_GenerateAnnualBill => FALSE,
                                                     Canteen_PrepareNewYearFamilies => FALSE,
                                                     Canteen_PrepareNewYearChildren => FALSE,
                                                     Canteen_CanteenPlanning => TRUE,
                                                     Canteen_CanteenAnnualRegistrations => FALSE,
                                                     Canteen_WeekSynthesis => TRUE,
                                                     Canteen_DaySynthesis => TRUE,
                                                     Canteen_NurseryPlanning => TRUE,
                                                     Canteen_NurseryDaySynthesis => TRUE,
                                                     Canteen_NurseryDelays => TRUE,
                                                     Canteen_SnackPlanning => TRUE,
                                                     Canteen_LaundryPlanning => TRUE,
                                                     Canteen_ExitPermissions => FALSE,
                                                     Canteen_AddDocumentApproval => TRUE,
                                                     Canteen_DocumentsApprovalsList => TRUE
                                                    ),
                                  'cooperation' => array(
                                                         Coop_CreateEvent => FALSE,
                                                         Coop_FestiveEventsList => TRUE,
                                                         Coop_MaintenanceEventsList => TRUE,
                                                         Coop_ClosedEventsList => TRUE,
                                                         Coop_FamiliesList => TRUE,
                                                         Coop_CreateWorkGroup => TRUE,
                                                         Coop_WorkGroupsList => TRUE,
                                                         Coop_CreateDonation => TRUE,
                                                         Coop_DonationsList => TRUE,
                                                         Coop_GenerateTaxReceipt => TRUE
                                                        ),
                                  'admin' => array(
                                                   Admin_CreateSupportMember => FALSE,
                                                   Admin_SupportMembersList => FALSE,
                                                   Admin_SupportMembersStatesList => FALSE,
                                                   Admin_HolidaysList => FALSE,
                                                   Admin_OpenedSpecialDaysList => FALSE,
                                                   Admin_SwapSnackPlanning => FALSE,
                                                   Admin_LaundryPlanning => FALSE,
                                                   Admin_EventTypesList => FALSE,
                                                   Admin_ConfigParametersList => FALSE
                                                  ),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => TRUE,
                                                        Param_FamilyDetails => FALSE,
                                                        Param_CreateAlias => FALSE,
                                                        Param_AliasList => FALSE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => TRUE
                                                       )
                                 ),
                       /* resp ev */
                       7 => array(
                                  'canteen' => array(
                                                     Canteen_CreateFamily => FALSE,
                                                     Canteen_FamiliesList => FALSE,
                                                     Canteen_AddPayment => FALSE,
                                                     Canteen_PaymentsSynthesis => FALSE,
                                                     Canteen_GenerateMonthlyBill => FALSE,
                                                     Canteen_GenerateAnnualBill => FALSE,
                                                     Canteen_PrepareNewYearFamilies => FALSE,
                                                     Canteen_PrepareNewYearChildren => FALSE,
                                                     Canteen_CanteenPlanning => FALSE,
                                                     Canteen_CanteenAnnualRegistrations => FALSE,
                                                     Canteen_WeekSynthesis => FALSE,
                                                     Canteen_DaySynthesis => FALSE,
                                                     Canteen_NurseryPlanning => FALSE,
                                                     Canteen_NurseryDaySynthesis => FALSE,
                                                     Canteen_NurseryDelays => FALSE,
                                                     Canteen_SnackPlanning => FALSE,
                                                     Canteen_LaundryPlanning => FALSE,
                                                     Canteen_ExitPermissions => FALSE,
                                                     Canteen_AddDocumentApproval => FALSE,
                                                     Canteen_DocumentsApprovalsList => TRUE
                                                    ),
                                  'cooperation' => array(
                                                         Coop_CreateEvent => TRUE,
                                                         Coop_FestiveEventsList => TRUE,
                                                         Coop_MaintenanceEventsList => TRUE,
                                                         Coop_ClosedEventsList => TRUE,
                                                         Coop_FamiliesList => TRUE,
                                                         Coop_CreateWorkGroup => FALSE,
                                                         Coop_WorkGroupsList => TRUE,
                                                         Coop_CreateDonation => FALSE,
                                                         Coop_DonationsList => FALSE,
                                                         Coop_GenerateTaxReceipt => FALSE
                                                        ),
                                  'admin' => array(
                                                   Admin_CreateSupportMember => FALSE,
                                                   Admin_SupportMembersList => FALSE,
                                                   Admin_SupportMembersStatesList => FALSE,
                                                   Admin_HolidaysList => FALSE,
                                                   Admin_OpenedSpecialDaysList => FALSE,
                                                   Admin_SwapSnackPlanning => FALSE,
                                                   Admin_LaundryPlanning => FALSE,
                                                   Admin_EventTypesList => FALSE,
                                                   Admin_ConfigParametersList => FALSE
                                                  ),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => TRUE,
                                                        Param_FamilyDetails => FALSE,
                                                        Param_CreateAlias => FALSE,
                                                        Param_AliasList => FALSE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => TRUE
                                                       )
                                 ),
                       /* ancienne famille */
                       8 => array(
                                  'canteen' => array(
                                                     Canteen_CreateFamily => FALSE,
                                                     Canteen_FamiliesList => FALSE,
                                                     Canteen_AddPayment => FALSE,
                                                     Canteen_PaymentsSynthesis => FALSE,
                                                     Canteen_GenerateMonthlyBill => FALSE,
                                                     Canteen_GenerateAnnualBill => FALSE,
                                                     Canteen_PrepareNewYearFamilies => FALSE,
                                                     Canteen_PrepareNewYearChildren => FALSE,
                                                     Canteen_CanteenPlanning => FALSE,
                                                     Canteen_CanteenAnnualRegistrations => FALSE,
                                                     Canteen_WeekSynthesis => FALSE,
                                                     Canteen_DaySynthesis => FALSE,
                                                     Canteen_NurseryPlanning => FALSE,
                                                     Canteen_NurseryDaySynthesis => FALSE,
                                                     Canteen_NurseryDelays => FALSE,
                                                     Canteen_SnackPlanning => FALSE,
                                                     Canteen_LaundryPlanning => FALSE,
                                                     Canteen_ExitPermissions => FALSE,
                                                     Canteen_AddDocumentApproval => FALSE,
                                                     Canteen_DocumentsApprovalsList => TRUE
                                                    ),
                                  'cooperation' => array(
                                                         Coop_CreateEvent => FALSE,
                                                         Coop_FestiveEventsList => FALSE,
                                                         Coop_MaintenanceEventsList => FALSE,
                                                         Coop_ClosedEventsList => FALSE,
                                                         Coop_FamiliesList => FALSE,
                                                         Coop_CreateWorkGroup => FALSE,
                                                         Coop_WorkGroupsList => FALSE,
                                                         Coop_CreateDonation => FALSE,
                                                         Coop_DonationsList => FALSE,
                                                         Coop_GenerateTaxReceipt => FALSE
                                                        ),
                                  'admin' => array(
                                                   Admin_CreateSupportMember => FALSE,
                                                   Admin_SupportMembersList => FALSE,
                                                   Admin_SupportMembersStatesList => FALSE,
                                                   Admin_HolidaysList => FALSE,
                                                   Admin_OpenedSpecialDaysList => FALSE,
                                                   Admin_SwapSnackPlanning => FALSE,
                                                   Admin_LaundryPlanning => FALSE,
                                                   Admin_EventTypesList => FALSE,
                                                   Admin_ConfigParametersList => FALSE
                                                  ),
                                  'parameters' => array(
                                                        Param_Profil => TRUE,
                                                        Param_PreparedRequests => FALSE,
                                                        Param_FamilyDetails => TRUE,
                                                        Param_CreateAlias => FALSE,
                                                        Param_AliasList => FALSE,
                                                        Param_SendMessage => TRUE,
                                                        Param_MessagesJobsList => FALSE
                                                       )
                                 )
                      );
?>