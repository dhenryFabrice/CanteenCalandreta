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
 * Common module : defined constants of CanteenCalandreta
 *
 * @author Christophe Javouhey
 * @version 3.4
 * @since 2012-01-12
 */


 //########################### Config.php constants ###################
 // Used to manage the sessions
 define("SESSION_TYPE_PHP", "php");
 define("SESSION_TYPE_DB", "db");
 define("SESSION_TYPE_FILE", "file");

 // Used to trace the errors
 define("ERROR_NO_MODE", 0);
 define("ERROR_ECHO_MODE", 1);
 define("ERROR_FILE_MODE", 2);

 // Used by jobs
 define("JOB_EMAIL", 1);

 define("JobSize", "size");
 define("DelayBetween2Jobs", "delay");
 define("JobNbFails", "nbfails");
 define("DelayAfterXJobFails", "faildelay");

 // Modes of monthly contributions
 define('MC_DEFAULT_MODE', 0);
 define('MC_BENEFACTOR_MODE', 1);
 define('MC_FAMILY_COEFF_1_MODE', 2);
 define('MC_FAMILY_COEFF_2_MODE', 3);
 define('MC_FAMILY_COEFF_3_MODE', 4);
 define('MC_FAMILY_COEFF_4_MODE', 5);

 // Used to send notifications by e-mail
 define("Template", 0);
 define("Cc", 1);
 define("To", 2);
 define("UsersInCopyTpl", 3);
 define("Bcc", 4);
 define("Inhibition", 5);

 // Used by notifications of the Cooperation module
 define("TO_AUTHOR_EVENT", 1);
 define("TO_FAMILY_EVENT", 2);
 define("TO_ALL_FAMILIES_EVENT", 3);
 define("TO_ALL_REGISTRERED_FAMILIES_EVENT", 4);
 define("TO_ALL_UNREGISTRERED_FAMILIES_EVENT", 5);
 define("TO_NO_INDICOOP_FAMILIES_EVENT", 6);
 define("TO_NO_INDICOOP_FAMILIES_FIRST_TO_ALL_UNREGISTRERED_FAMILIES_AFTER_EVENT", 7);

 // Used by notifications of the Message module
 define("TO_AUTHOR_MESSAGE", 1);

 // Used by donations
 define("Language", 1);
 define("Unit", 2);
 define("Page", 3);
 define("Recipient", 10);
 define("Donator", 20);
 define("Text", 100);
 define("PosX", 101);
 define("PosY", 102);
 define("DimWidth", 103);
 define("Items", 104);

 // OS
 define('APPL_OS_WINDOWS', 1);
 define('APPL_OS_LINUX', 2);

 // PDF Lib
 define('PDF_LIB_WKHTMLTOPDF', 1);
 define('PDF_LIB_DOMPDF', 2);
 define('PDF_LIB_FPDF', 3);

 //########################### Config parameters ######################
 define('CONF_PARAM_TYPE_XML', 'xml');
 define('CONF_PARAM_TYPE_STRING', 'string');

 //########################### Access rights for pages ################
 // Functions
 define('FCT_SYSTEM', 100);
 define('FCT_ADMIN', 99);
 define('FCT_UPLOAD_FILE', 98);
 define('FCT_FAMILY', 1);
 define('FCT_PAYMENT', 2);
 define('FCT_BANK', 3);
 define('FCT_BILL', 4);
 define('FCT_CANTEEN_PLANNING', 5);
 define('FCT_NURSERY_PLANNING', 6);
 define('FCT_SNACK_PLANNING', 7);
 define('FCT_LAUNDRY_PLANNING', 8);
 define('FCT_EXIT_PERMISSION', 9);
 define('FCT_EVENT', 10);
 define('FCT_EVENT_REGISTRATION', 11);
 define('FCT_WORKGROUP', 12);
 define('FCT_WORKGROUP_REGISTRATION', 13);
 define('FCT_ALIAS', 14);
 define('FCT_MESSAGE', 15);
 define('FCT_DONATION', 16);
 define('FCT_TOWN', 17);
 define('FCT_DOCUMENT_APPROVAL', 18);
 define('FCT_MEETING', 19);

 // Actions
 define('FCT_ACT_CREATE', 1);  // = write access
 define('FCT_ACT_UPDATE', 2);  // = write access
 define('FCT_ACT_READ_ONLY', 3);
 define('FCT_ACT_PARTIAL_READ_ONLY', 5);  // Some data can be hidden
 define('FCT_ACT_UPDATE_OLD_USER', 6);
 define('FCT_ACT_DELETE', 4);
 define('FCT_ACT_NO_RIGHTS', 99);

 //########################### Log events constants ###################
 // Types of items
 define("EVT_SYSTEM", "system");
 define("EVT_ADMIN", "admin");
 define("EVT_PROFIL", "profil");
 define("EVT_UPLOADED_FILE", "uploaded_file");
 define("EVT_FAMILY", "family");
 define("EVT_PAYMENT", "payment");
 define("EVT_CANTEEN", "canteen");
 define("EVT_NURSERY", "nursery");
 define("EVT_SNACK", "snack");
 define("EVT_LAUNDRY", "laundry");
 define("EVT_EXIT_PERMISSION", "exit_permission");
 define("EVT_EVENT", "event");
 define("EVT_WORKGROUP", "workgroup");
 define("EVT_MESSAGE", "message");
 define("EVT_DONATION", "donation");
 define("EVT_DOCUMENT_APPROVAL", "document_approval");
 define("EVT_MEETING", "meeting");

 // Types of Services
 define("EVT_SERV_LOGIN", "login");            // System
 define("EVT_SERV_PROFIL", "profil");          // Profil
 define("EVT_SERV_PREPARED_REQUEST", "prepared_request");    // Profil / prepared request
 define("EVT_SERV_UPLOADED_FILE", "uploaded_file");  // Uploaded file
 define("EVT_SERV_FAMILY", "family");          // Family
 define("EVT_SERV_CHILD", "child");            // Child, exit permissions
 define("EVT_SERV_SUSPENSION", "child_suspension");          // Only for family
 define("EVT_SERV_PAYMENT", "payment");        // Payment
 define("EVT_SERV_DISCOUNT", "discount");      // Discount/increase
 define("EVT_SERV_BANK", "bank");              // Bank
 define("EVT_SERV_TOWN", "town");              // Town
 define("EVT_SERV_PLANNING", "planning");      // Canteen, nursery, snack, laundry and exit permissions
 define("EVT_SERV_DELAY", "delay");            // For nursery only
 define("EVT_SERV_EVENT", "event");            // Event
 define("EVT_SERV_EVENT_REGISTRATION", "event_registration");     // Only for event
 define("EVT_SERV_EVENT_SWAPPED_REGISTRATION", "event_swap_reg"); // Only for event
 define("EVT_SERV_WORKGROUP", "workgroup");            // Workgroup
 define("EVT_SERV_WORKGROUP_REGISTRATION", "workgroup_registration");     // Only for workgroup
 define("EVT_SERV_ALIAS", "alias");            // Alias
 define("EVT_SERV_MESSAGE", "message");        // Message
 define("EVT_SERV_DONATION", "donation");      // Donation
 define("EVT_SERV_DOCUMENT_APPROVAL", "document_approval");            // Document approval
 define("EVT_SERV_DOCUMENT_FAMILY_APPROVAL", "family_approval");     // Only for document approval
 define("EVT_SERV_MEETING", "meeting");     // Only for meetings

 // Types of actions
 define("EVT_ACT_LOGIN", "login");                 // System
 define("EVT_ACT_LOGIN_FAILED", "login_failed");   // System
 define("EVT_ACT_LOGOUT", "logout");               // System
 define("EVT_ACT_CREATE", "create");               // All
 define("EVT_ACT_UPDATE", "update");               // All
 define("EVT_ACT_COPY", "copy");                   // All
 define("EVT_ACT_DELETE", "delete");               // All
 define("EVT_ACT_ADD", "add");                     // Children, ...
 define("EVT_ACT_SWAP", "swap");                   // Snack planning, laundry planning
 define("EVT_ACT_LINK", "link");                   // Payments and bills, ...
 define("EVT_ACT_DIFFUSED", "diffused");           // Bills, messages
 define("EVT_ACT_EXECUTE", "execute");             // Prepared request

 // Types of levels of events
 define("EVT_LEVEL_WARNING", 1);
 define("EVT_LEVEL_SYSTEM", 3);
 define("EVT_LEVEL_MAIN_EVT", 4);
 define("EVT_LEVEL_OTHER_EVT", 5);

 //########################### Objects constants ######################
 // Types of objects
 define("OBJ_EVENT", 10);   // Event object

 //########################### Stats constants ########################
 define("STAT_TYPE_NB_EMAILS_SENT", "NB_EMAILS_SENT");       // Stat about the number of e-mails sent during a period
 define("STAT_TYPE_NB_EMAILS_ERRORS", "NB_EMAILS_ERRORS");   // Stat about errors for e-mails during a period

 //########################### Web services constants #################
 // Used to manage web services
 define("WEB_SERVICE_AUTH_TYPE", "Auth");
 define("WEB_SERVICE_PARAMS", "Params");
 define("WEB_SERVICE_CONFIG", "Config");

 // Types of authentification
 define("WS_AUTH_NONE", 0);
 define("WS_AUTH_CUSTOMER", 1);
 define("WS_AUTH_SUPPORT", 2);

 // Specific names for parameters
 define("WS_XMLDOC", "XMLDoc");
 define("WS_MAIL_HEADER", "MailHeader");

 //########################### FctLibrary.php constants ###############
 // Used in the function array_filtered()
 define("BY_KEY_KEEP_VALUE", 0);
 define("BY_KEY_KEEP_KEY", 1);
 define("BY_VALUE_KEEP_VALUE", 2);
 define("BY_VALUE_KEEP_KEY", 3);
 define("BY_VALUE_KEEP_ASSOC_VALUE", 4);
 define("BY_VALUE_KEEP_ASSOC_KEY", 5);

 // Used in the function getInitials()
 define("BOTH_INITIALS", 0);
 define("FIRSTNAME_INITIALS", 1);

 // Used in the function getRelativeUrlDepth()
 define("HTTP", 0);
 define("PATH", 1);

 // Parameters of the views
 define("Fieldnames", 0);
 define("Params", 1);
 define("SearchMode", 2);
 define("OrderBy", 3);

 //###################### ContextualMenusAccess.php constants #########
 // Used in the function displaySupportMemberContextualMenu() to display
 // or not the items of the contextual menus
 // Constants of the CANTEEN main menu
 define('Canteen_CreateFamily', 1);
 define('Canteen_FamiliesList', 2);
 define('Canteen_AddPayment', 3);
 define('Canteen_PaymentsSynthesis', 4);
 define('Canteen_GenerateMonthlyBill', 5);
 define('Canteen_GenerateAnnualBill', 6);
 define('Canteen_PrepareNewYearChildren', 7);
 define('Canteen_PrepareNewYearFamilies', 8);
 define('Canteen_CanteenPlanning', 9);
 define('Canteen_CanteenAnnualRegistrations', 10);
 define('Canteen_WeekSynthesis', 11);
 define('Canteen_DaySynthesis', 12);
 define('Canteen_NurseryPlanning', 13);
 define('Canteen_NurseryDaySynthesis', 14);
 define('Canteen_NurseryDelays', 15);
 define('Canteen_SnackPlanning', 16);
 define('Canteen_LaundryPlanning', 17);
 define('Canteen_ExitPermissions', 18);
 define('Canteen_AddDocumentApproval', 19);
 define('Canteen_DocumentsApprovalsList', 20);

 // Constants of the COOPERATION main menu
 define('Coop_CreateEvent', 1);
 define('Coop_FestiveEventsList', 2);
 define('Coop_MaintenanceEventsList', 3);
 define('Coop_AllEventsList', 4);
 define('Coop_ClosedEventsList', 5);
 define('Coop_FamiliesList', 6);
 define('Coop_CreateWorkGroup', 7);
 define('Coop_WorkGroupsList', 8);
 define('Coop_CreateDonation', 9);
 define('Coop_DonationsList', 10);
 define('Coop_GenerateTaxReceipt', 11);
 define('Coop_CreateMeetingRoomRegistration', 12);
 define('Coop_MeetingRoomsPlanning', 13);

 // Constants of the ADMIN main menu
 define('Admin_CreateSupportMember', 1);
 define('Admin_SupportMembersList', 2);
 define('Admin_SupportMembersStatesList', 3);
 define('Admin_HolidaysList', 4);
 define('Admin_OpenedSpecialDaysList', 5);
 define('Admin_SwapSnackPlanning', 6);
 define('Admin_LaundryPlanning', 7);
 define('Admin_EventTypesList', 8);
 define('Admin_ConfigParametersList', 9);

 // Constants of the PARAMETERS contextual menu
 define('Param_Profil', 1);
 define('Param_PreparedRequests', 2);
 define('Param_FamilyDetails', 3);
 define('Param_CreateAlias', 4);
 define('Param_AliasList', 5);
 define('Param_SendMessage', 6);
 define('Param_MessagesJobsList', 7);

 //##################### DbBillsLibrary.php constants #################
 // Used in the function getBillAmount()
 define("WITHOUT_PREVIOUS_BALANCE", 0);
 define("WITH_PREVIOUS_BALANCE", 1);

 //##################### DbCanteenRegistrations.php constants #########
 // Used in the function getCanteenRegistrations()
 define("DATES_INCLUDED_IN_PLANNING", 0);
 define("PLANNING_INCLUDED_IN_DATES", 1);
 define("DATES_BETWEEN_PLANNING", 2);
 define("PLANNING_BETWEEN_DATES", 3);
 define("NO_DATES", -1);

 // Used in the function getNbCanteenRegistrations()
 define("GROUP_BY_FOR_DATE_BY_DAY", 1);
 define("GROUP_BY_FOR_DATE_BY_YEARMONTH", 2);
 define("GROUP_BY_FOR_DATE_BY_YEAR", 3);
 define("GROUP_BY_FOR_DATE_BY_DAY_AND_MONTHLY_CONTRIBUTION_MODE", 4);
 define("GROUP_BY_CREATION_DATE_BY_DAY", 10);
 define("GROUP_BY_CREATION_DATE_BY_YEARMONTH", 11);
 define("GROUP_BY_CREATION_DATE_BY_YEAR", 12);
 define("GROUP_BY_GRADE", 20);
 define("GROUP_BY_CLASSROOM", 21);
 define("GROUP_BY_CHILD_ID", 22);
 define("GROUP_BY_FAMILY_ID", 23);
 define("GROUP_BY_MONTHLY_CONTRIBUTION_MODE", 24);

 // Mode of repetitions of a planning entry. Used in the function getRepeatedDates()
 define("REPEAT_DAILY", 1);
 define("REPEAT_WEEKLY", 2);
 define("REPEAT_MONTHLY", 3);
 define("REPEAT_EVERY_2_MONTHS", 4);
 define("REPEAT_EVERY_3_MONTHS", 5);
 define("REPEAT_EVERY_6_MONTHS", 6);
 define("REPEAT_EVERY_YEAR", 7);
 define("REPEAT_EVERY_2_YEARS", 8);
 define("REPEAT_EVERY_3_YEARS", 9);
 define("REPEAT_EVERY_4_YEARS", 10);
 define("REPEAT_EVERY_5_YEARS", 11);

 // Mode to display the canteen planning
 define("PLANNING_MONTH_VIEW", 1);
 define("PLANNING_WEEKS_VIEW", 2);
 define("PLANNING_DAYS_VIEW", 3);

 define("PLANNING_VIEWS_RESTRICTION_ALL", 1);
 define("PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN", 2);

 // Types of meals for canteen registrations
 define("CANTEEN_REGISTRATION_DEFAULT_MEAL", 0);
 define("CANTEEN_REGISTRATION_WITHOUT_PORK", 1);
 define("CANTEEN_REGISTRATION_PACKED_LUNCH", 2);

 //################# GiHighLevelsFormsEventsLibrary.php constants #####
 define("EVENT_REGISTRATION_VIEWS_RESTRICTION_ALL", 1);
 define("EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY", 2);
 define("EVENT_HIDDEN_FAMILY_DATA", 'xxxxxxxx');
 define("EVENT_SWAPPED_REGISTRATION_AUTHOR", 1);
 define("EVENT_SWAPPED_REGISTRATION_REQUESTOR", 2);
 define("EVENT_SWAPPED_REGISTRATION_ACCEPTOR", 3);
 define("EVENT_SWAPPED_REGISTRATION_OTHER", 0);

 //############# GiHighLevelsFormsWorkGroupsLibrary.php constants #####
 define("WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL", 1);
 define("WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY", 2);
 define("WORKGROUP_HIDDEN_FAMILY_DATA", 'xxxxxxxx');

 //##################### GiDisplayComponentsLibrary.php constants #####
 // Used in the function generateAowVisualIndicators()
 define("TABLE", 0);
 define("DETAILS", 1);

 // Used in the function generateLegendsOfVisualIndicators()
 define("ICON", 0);
 define("CSS_STYLE", 1);
 define("DYN_CSS_STYLE", 2);

 //########################### GiXMLLibrary.php constants #############
 // Used in the functions xmlXslProcess() and xmlXslGenerate()
 define("XMLFILE_XSLFILE", 0);
 define("XMLFILE_XSLSTREAM", 1);
 define("XMLSTREAM_XSLSTREAM", 2);
 define("XMLSTREAM_XSLFILE", 3);

 //##################### GiPrintComponentsLibrary.php constants #######
 define("MONTHLY_BILL_DOCTYPE", 1);
 define("ALL_MONTHLY_BILLS_DOCTYPE", 2);
 define("ANNUAL_BILL_DOCTYPE", 3);
 define("ALL_ANNUAL_BILLS_DOCTYPE", 4);
 define("DONATION_TAX_RECEIPT_DOCTYPE", 5);
 define("ALL_DONATION_TAX_RECEIPT_DOCTYPE", 6);

 //################### PreparedRequestsParameters.php constants #######
 // Parameters of the views
 define("Fctname", 3);
?>