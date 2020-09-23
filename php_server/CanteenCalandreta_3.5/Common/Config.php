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
 * Common module : configuration file of CanteenCalandreta v3.5
 *
 * @author Christophe Javouhey
 * @version 3.5
 *     - 2012-07-10 : taken into account the "LostPwd" plugin
 *     - 2013-01-25 : allow several types of views for the plannings (canteen, nursery) and add new variables :
 *                    $CONF_PLANNING_WEEKS_TO_DISPLAY, $CONF_CANTEEN_DEFAULT_VIEW_TYPES and
 *                    $CONF_NURSERY_DEFAULT_VIEW_TYPES, $CONF_BILLS_NOT_PAID_BILLS_LIMIT and
 *                    $CONF_BILLS_FAMILIES_WITH_NOT_PAID_BILLS_NOTIFICATION
 *     - 2013-04-03 : taken into account new variables about the "Cooperation" module, taken into account
 *                    the new structure of the CONF_CLASSROOMS variable (includes school year)
 *     - 2013-09-17 : taken into account several modes of monthly contributions in $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS
 *                    and the new $CONF_MONTHLY_CONTRIBUTION_MODES_ICONS and $CONF_MONTHLY_CONTRIBUTION_MODES variables
 *     - 2013-11-20 : nothing new
 *     - 2014-01-31 : taken into account the management of delays to get children at the nursery, $CONF_PAYMENT_NOT_USED_ICON
 *     - 2014-03-12 : taken into account $CONF_NB_FAMILY_BILLS
 *     - 2014-05-22 : nothing new
 *     - 2015-01-16 : taken into account $CONF_WARNING_ICON, $CONF_PAYMENT_RESET_ICON, $CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS
 *                    and $CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS_DELAY
 *     - 2015-05-13 : taken into account $CONF_CANTEEN_PROVISIONAL_QUANTITIES_TMP_FILE
 *     - 2015-06-15 : taken into account constants about snack registrations, laundry registrations and exit permissions
 *     - 2015-09-21 : taken into account constant about system notifications ($CONF_EMAIL_SYSTEM_NOTIFICATIONS), about
 *                    workgroups and $CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON
 *     - 2016-03-01 : taken into account the management of alias and messages sent by users, new values in
 *                    $CONF_MONTHLY_CONTRIBUTION_MODES and $CONF_MONTHLY_CONTRIBUTION_MODES_ICONS to take into account
 *                    coefficients of families, the management of donations, $CONF_CHARSET for the charset of the application,
 *                    the management of delayed jobs, upload of files
 *     - 2016-09-09 : v3.0. Taken into account $CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT, $CONF_ACTIVATION_ICON, $CONF_JOBS_EXECUTION_DELAY
 *                    and allow to load some variables of configuration from database.
 *     - 2017-09-06 : v3.1. Allow partial read only on FCT_NURSERY_PLANNING and taken into account $CONF_NURSERY_REGISTER_DELAY_PLANNING_REGISTRATION,
 *                    $CONF_COOP_WORKGROUP_ALLOW_REGISTRATIONS_FOR_REFERENTS and $CONF_COOP_WORKGROUP_ALLOW_UNREGISTRATIONS_FOR_USERS,
 *                    taken into account CONF_DISCOUNTS_FAMILIES_TYPES and $CONF_DISCOUNTS_FAMILIES_REASON_TYPES,
 *                    taken into account $CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE, taken into account
 *                    $CONF_MEAL_TYPES and $CONF_CANTEEN_PRICES_CONCERNED_MEAL_TYPES
 *     - 2018-05-16 : v3.2. Taken into account $CONF_DEFAULT_VALUES_SET
 *     - 2019-05-07 : v3.3. Taken into account documents approvals, $CONF_UPLOAD_DOCUMENTS_FILES_MAXSIZE, $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY,
 *                    $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD and $CONF_COOP_EVENT_USE_RANDOM_AUTO_FAMILIES_REGISTRATIONS
 *     - 2019-11-08 : v3.4. Taken into account PEAR:DB library in /Common/, $CONF_EMAIL_SMTP_SERVERS and $CONF_LOG_USE_STATS, $CONF_UPLOAD_EVENTS_FILES_DIRECTORY,
 *                    $CONF_UPLOAD_EVENTS_FILES_DIRECTORY_HDD, $CONF_UPLOAD_UPLOADED_FILES_MAXSIZE, $CONF_CONTRIBUTIONS_RESET_MONTHLY_CONTRIBUTION_MODE
 *                    and meeting rooms registrations
 *     - 2020-01-22 : v3.5. Taken into account $CONF_CHECK_ALL_ICON, $CONF_NURSERY_OTHER_TIMESLOTS and $CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION,
 *                    $CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_USE_CAPACITIES and $CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_CAPACITIES
 *
 * @since 2012-01-10
 */


/**
 * Give the path of the Intranet root directory
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2012-01-10
 *
 * @return String             Intranet root directory
 */
 function getIntranetRootDirectory()
 {
     global $QUERY_STRING;
     $QUERY_STRING = $_SERVER['QUERY_STRING'];

     $ArrayTmp = explode('/', $_SERVER['PHP_SELF']);

     $Protocol = 'http';
     if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS']))
     {
         // HTTPS activated on the server
         $Protocol = 'https';
     }

     if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
         return "$Protocol://localhost/$ArrayTmp[1]/";
     }

     if ((array_key_exists('HTTP_HOST', $_SERVER)) && (!empty($_SERVER['HTTP_HOST'])) && ($_SERVER['HTTP_HOST'] != 'localhost')) {
         $DomainName = $_SERVER['HTTP_HOST'];
     } elseif ((array_key_exists('SERVER_NAME', $_SERVER)) && (!empty($_SERVER['SERVER_NAME'])) && ($_SERVER['SERVER_NAME'] != 'localhost')) {
         $DomainName = $_SERVER['SERVER_NAME'];
     } elseif ((array_key_exists('HOSTNAME', $_ENV)) && (!empty($_ENV['HOSTNAME']))) {
         $DomainName = $_ENV['HOSTNAME'];
     } elseif ((array_key_exists('COMPUTERNAME', $_ENV)) && (!empty($_ENV['COMPUTERNAME']))) {
         $DomainName = $_ENV['COMPUTERNAME'];
     } else {
         $DomainName = 'localhost';
     }

     if (!isset($_SERVER['SCRIPT_URI'])) {
         $_SERVER['SCRIPT_URI'] = "$Protocol://".$DomainName.$_SERVER['REQUEST_URI'];
     }

     return "$Protocol://$DomainName/".$ArrayTmp[1].'/';
 }


 $CONF_ROOT_DIRECTORY = getIntranetRootDirectory();


 //###################### Management of the cache and sessions #############
 $CONF_CHARSET = "ISO-8859-1";  // Charset used by the application

 header('Pragma: no-cache');
 header('Cache-Control: no-cache');
 header("Content-type: text/html; charset=$CONF_CHARSET");

 session_name('CanteenCalandreta');

 set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/php-pear;'.dirname(__FILE__).'/php-pear/DB-1.9.3');  // For Pear::DB

 $CONF_SESSIONS_TYPE      = SESSION_TYPE_PHP;  // Type of session. Use the value SESSION_TYPE_PHP to use the default php session
 $CONF_SESSIONS_LIFETIME  = 10;               // Lifetime, in hours, on a session
 switch($CONF_SESSIONS_TYPE)
 {
     case SESSION_TYPE_DB:
         // Sessions in database
         require 'Sessions.php';
         ini_set('session.save_handler', 'user');
         session_set_save_handler('openDBSession', 'closeDBSession', 'readDBSession', 'writeDBSession', 'destroyDBSession', 'gcDBSession');
         break;

     case SESSION_TYPE_FILE:
         // Sessions in files
         require 'Sessions.php';
         ini_set('session.save_handler', 'user');
         ini_set('session.save_path', '/local/tmp/sessionCanteenCalandreta');
         ini_set('session.cookie_lifetime', 3600 * $CONF_SESSIONS_LIFETIME);
         ini_set('session.gc_maxlifetime', 3600 * $CONF_SESSIONS_LIFETIME);
         ini_set('session.cache_expire', 60 * $CONF_SESSIONS_LIFETIME);
         session_set_save_handler('openFileSession', 'closeFileSession', 'readFileSession', 'writeFileSession', 'destroyFileSession', 'gcFileSession');
         break;

     case SESSION_TYPE_PHP:
     default:
         // Default session php system
         /*ini_set('session.save_path', '/local/tmp/sessionCanteenCalandreta');
         ini_set('session.cookie_lifetime', 3600 * $CONF_SESSIONS_LIFETIME);
         ini_set('session.gc_maxlifetime', 3600 * $CONF_SESSIONS_LIFETIME);
         ini_set('session.cache_expire', 60 * $CONF_SESSIONS_LIFETIME); */
         break;
 }

 //########################### Mode parameters  ############################
 $CONF_MODE_DEBUG = FALSE;     // Active some functions used in the debug mode

 //############################ Management of the errors ###################
 $CONF_ERROR_MODE     = ERROR_NO_MODE;     // Mode to trace the errors (no, echo, file, ...)
 $CONF_ERROR_LOG_FILE = $_SERVER['DOCUMENT_ROOT']."/CanteenCalandreta/errors.log";    // Path and name of the log errors file
 switch($CONF_ERROR_MODE)
 {
     case ERROR_NO_MODE:
         // Nothing to do
         break;

     default:
         // Errors are traced
         set_error_handler('errorManager');
         break;
 }

 //############################ Database access ############################
#$CONF_DB_SERVER                 = $_SERVER['SERVER_NAME'];
 $CONF_DB_SERVER                 = 'db'
 $CONF_DB_USER                   = 'root';
 $CONF_DB_PASSWORD               = 'root';
 $CONF_DB_DATABASE               = 'CantineTest';      // Name of the database used by the application
 $CONF_DB_PORT                   = '';
 $CONF_DB_SGBD_TYPE              = 'mysql';                // Type of SGBD : mysql, pgsql, oci8, MSAccess, IBMDB2,...
 $CONF_DB_SGBD_VERSION           = 5;                      // Version of the sgbd : mysql 3, mysql 5...
 $CONF_DB_PERSISTANCE_CONNECTION = FALSE;

 //########################### Languages parameters ########################
 $CONF_LANG = 'fr';                 // Language of the messages displayed on the intranet : fr, en, oc...

 //########################### Display the execution script time ###########
 $CONF_DISPLAY_EXECUTION_TIME_SCRIPT = FALSE;    // Allow or not to display the execution script time on each web page of the intranet

 //########################### OpenID parameters ###########################
 $CONF_OPENID_USED = FALSE;    // Allow or not to use OpenID to login
 $CONF_OPENID_SERVER = "";    // Url of the server to check the OpenID
 $CONF_OPENID_LIB_DIRECTORY = "";

 //########################### Logged events parameters ####################
 $CONF_LOG_EVENTS = array(
                          EVT_SYSTEM => array(
                                              EVT_SERV_LOGIN => array(
                                                                      EVT_ACT_LOGIN => array(
                                                                                             'level' => EVT_LEVEL_SYSTEM,
                                                                                             'msg' => "Connexion avec IP @IP."
                                                                                            ),
                                                                      EVT_ACT_LOGIN_FAILED => array(
                                                                                                    'level' => EVT_LEVEL_WARNING,
                                                                                                    'msg' => "Erreur de connexion : @IP"
                                                                                                   ),
                                                                      EVT_ACT_LOGOUT => array(
                                                                                              'level' => EVT_LEVEL_SYSTEM,
                                                                                              'msg' => "Déconnexion."
                                                                                             )
                                                                     ),
                                              EVT_SERV_TOWN => array(
                                                                         EVT_ACT_CREATE => array(
                                                                                                 'level' => EVT_LEVEL_OTHER_EVT,
                                                                                                 'msg' => "Ville @NAME créée.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateTown.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_OTHER_EVT,
                                                                                                 'msg' => "Ville @NAME mise à jour.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateTown.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_PROFIL => array(
                                              EVT_SERV_PROFIL => array(
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour @SUPPORTER.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Profil.php'
                                                                                           )
                                                                      ),
                                              EVT_SERV_LOGIN  => array(
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour @SUPPORTER.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Profil.php'
                                                                                           )
                                                                      ),
                                              EVT_SERV_PREPARED_REQUEST => array(
                                                                                 EVT_ACT_EXECUTE => array(
                                                                                            'level' => EVT_LEVEL_OTHER_EVT,
                                                                                            'msg' => "Requête préparée exécutée."
                                                                                           )
                                                                      )
                                             ),
                          EVT_UPLOADED_FILE => array(
                                              EVT_SERV_UPLOADED_FILE => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Fichier @FILENAME ajouté pour @NAME (@TYPE).",
                                                                                                 'url' => ''
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Fichier @FILENAME mis à jour pour @NAME (@TYPE).",
                                                                                                 'url' => ''
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Fichier @FILENAME supprimé.",
                                                                                                 'url' => ''
                                                                                                )
                                                                        )
                                             ),
                          EVT_FAMILY => array(
                                              EVT_SERV_FAMILY => array(
                                                                       EVT_ACT_CREATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Création.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateFamily.php'
                                                                                           ),
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateFamily.php'
                                                                                           )
                                                                      ),
                                              EVT_SERV_CHILD => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME ajouté.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateChild.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME mis à jour.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateChild.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME supprimé.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateFamily.php'
                                                                                                )
                                                                        ),
                                              EVT_SERV_SUSPENSION => array(
                                                                       EVT_ACT_ADD => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Suspension de la cotisation mensuelle pour l'enfant @NAME.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateChildSuspension.php'
                                                                                           ),
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour de la suspension de cotisation mensuelle pour l'enfant @NAME.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateChildSuspension.php'
                                                                                           )
                                                                      )
                                             ),
                          EVT_PAYMENT => array(
                                              EVT_SERV_PAYMENT => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Paiement ajouté pour la famille @NAME pour payer @TYPE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdatePayment.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Paiement mis à jour pour la famille @NAME pour payer @TYPE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdatePayment.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Paiement supprimé pour la famille @NAME pour payer @TYPE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdatePayment.php'
                                                                                                )
                                                                        ),
                                              EVT_SERV_BANK => array(
                                                                         EVT_ACT_CREATE => array(
                                                                                                 'level' => EVT_LEVEL_OTHER_EVT,
                                                                                                 'msg' => "Banque @NAME créée.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateBank.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_OTHER_EVT,
                                                                                                 'msg' => "Banque @NAME mise à jour.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateBank.php'
                                                                                                )
                                                                        ),
                                              EVT_SERV_DISCOUNT => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Réduction/majoration ajoutée pour la famille @NAME de type @TYPE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateDiscountFamily.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Réduction/majoration mise à jour pour la famille @NAME de type @TYPE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateDiscountFamily.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Réduction/majoration supprimée pour la famille @NAME de type @TYPE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateDiscountFamily.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_CANTEEN => array(
                                              EVT_SERV_PLANNING => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME inscrit à la cantine pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/CanteenPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME inscrit à la cantine pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/CanteenPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Cantine supprimée pour l'enfant @NAME pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/CanteenPlanning.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_NURSERY => array(
                                              EVT_SERV_PLANNING => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME inscrit à la garderie pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/NurseryPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME inscrit à la garerie pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/NurseryPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Garderie supprimée pour l'enfant @NAME pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/NurseryPlanning.php'
                                                                                                )
                                                                        ),
                                              EVT_SERV_DELAY => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Enfant @NAME récupéré en retard à la garderie le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/NurseryPlanning.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_SNACK => array(
                                              EVT_SERV_PLANNING => array(
                                                                         EVT_ACT_CREATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Planning des goûters généré.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/SnackPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Planning des goûters modifié suite à la désactivation de l'enfant @NAME.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/SnackPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_SWAP => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Planning des goûters modifié suite à un échange de tour de goûter de la famille @NAME pour la date du @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/SnackPlanning.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_LAUNDRY => array(
                                              EVT_SERV_PLANNING => array(
                                                                         EVT_ACT_CREATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Planning des lessives généré.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/LaundryPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Planning des lessives modifié suite à la désactivation de l'enfant @NAME.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/LaundryPlanning.php'
                                                                                                ),
                                                                         EVT_ACT_SWAP => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Planning des lessives modifié suite à un échange de tour de lessive de la famille @NAME pour la date du @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/LaundryPlanning.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_EXIT_PERMISSION => array(
                                              EVT_SERV_PLANNING => array(
                                                                         EVT_ACT_CREATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Autorisation de sortie pour l'enfant @NAME enregistrée pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/ExitPermissions.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Autorisation de sortie supprimée pour l'enfant @NAME pour le @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/ExitPermissions.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_DOCUMENT_APPROVAL => array(
                                              EVT_SERV_DOCUMENT_APPROVAL => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Nouveau document à approuver ajouté : @NAME.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateDocumentApproval.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Document à approuver mis à jour : @NAME.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateDocumentApproval.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Document à approuver @NAME supprimé.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/DocumentsApprovalsList.php'
                                                                                                )
                                                                        ),
                                              EVT_SERV_DOCUMENT_FAMILY_APPROVAL => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Document @NAME approuvé par @SUPPORTER.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateDocumentApproval.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Approbation du document @NAME supprimée par @SUPPORTER.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Canteen/UpdateDocumentApproval.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_EVENT => array(
                                              EVT_SERV_EVENT => array(
                                                                       EVT_ACT_CREATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Création.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateEvent.php'
                                                                                           ),
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateEvent.php'
                                                                                           ),
                                                                       EVT_ACT_DELETE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Evénement @SUBJECT supprimé.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateEvent.php'
                                                                                           )
                                                                      ),
                                              EVT_SERV_EVENT_REGISTRATION => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Famille @NAME inscrite à l'événement @SUBJECT du @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateEventRegistration.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Famille @NAME inscrite à l'événement @SUBJECT du @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateEventRegistration.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Suppression de l'inscription de la Famille @NAME à l'événement @SUBJECT.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateEventRegistration.php'
                                                                                                )
                                                                        ),
                                              EVT_SERV_EVENT_SWAPPED_REGISTRATION => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Famille @NAME demande un échange pour l'événement @SUBJECT du @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateSwapEventRegistration.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Famille @NAME met à jour sa demande d'échange pour l'événement @SUBJECT du @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateSwapEventRegistration.php'
                                                                                                ),
                                                                         EVT_ACT_DIFFUSED => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Famille @NAME accepte la demande d'échange avec l'événement @SUBJECT du @DATE.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateSwapEventRegistration.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Suppression de l'échange demandé par la famille @NAME pour l'événement @SUBJECT.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateSwapEventRegistration.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_WORKGROUP => array(
                                              EVT_SERV_WORKGROUP => array(
                                                                       EVT_ACT_CREATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Création.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateWorkGroup.php'
                                                                                           ),
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateWorkGroup.php'
                                                                                           ),
                                                                       EVT_ACT_DELETE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Commission @SUBJECT supprimée.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateWorkGroup.php'
                                                                                           )
                                                                        ),
                                              EVT_SERV_WORKGROUP_REGISTRATION => array(
                                                                         EVT_ACT_ADD => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "@NAME dans la commission @SUBJECT.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateWorkGroupRegistration.php'
                                                                                                ),
                                                                         EVT_ACT_UPDATE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "@NAME dans la commission @SUBJECT.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateWorkGroupRegistration.php'
                                                                                                ),
                                                                         EVT_ACT_DELETE => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "Suppression de @NAME de la commission @SUBJECT.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateWorkGroupRegistration.php'
                                                                                                )
                                                                        )
                                             ),
                          EVT_DONATION => array(
                                              EVT_SERV_DONATION => array(
                                                                       EVT_ACT_CREATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Création.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateDonation.php'
                                                                                           ),
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateDonation.php'
                                                                                           ),
                                                                       EVT_ACT_DELETE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Don de @SUBJECT supprimé.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateDonation.php'
                                                                                           )
                                                                        )
                                             ),
                          EVT_MEETING => array(
                                              EVT_SERV_MEETING => array(
                                                                       EVT_ACT_CREATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Réservation de la salle @NAME.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateMeetingRoomRegistration.php'
                                                                                           ),
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour de la réservation de la salle @NAME.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateMeetingRoomRegistration.php'
                                                                                           ),
                                                                       EVT_ACT_DELETE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Réservation de salle de réunion @NAME du @DATE supprimée.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/Cooperation/UpdateMeetingRoomRegistration.php'
                                                                                           )
                                                                      )
                                             ),
                          EVT_MESSAGE => array(
                                              EVT_SERV_ALIAS => array(
                                                                       EVT_ACT_CREATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Création.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/UpdateAlias.php'
                                                                                           ),
                                                                       EVT_ACT_COPY => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Copie de l'alias de la commission @SUBJECT.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/UpdateAlias.php'
                                                                                           ),
                                                                       EVT_ACT_UPDATE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Mise à jour.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/UpdateAlias.php'
                                                                                           ),
                                                                       EVT_ACT_DELETE => array(
                                                                                            'level' => EVT_LEVEL_MAIN_EVT,
                                                                                            'msg' => "Alias @NAME supprimé.",
                                                                                            'url' => $CONF_ROOT_DIRECTORY.'Support/UpdateAlias.php'
                                                                                           )
                                                                        ),
                                              EVT_SERV_MESSAGE => array(
                                                                         EVT_ACT_DIFFUSED => array(
                                                                                                 'level' => EVT_LEVEL_MAIN_EVT,
                                                                                                 'msg' => "@SUPPORTER a envoyé un message.",
                                                                                                 'url' => $CONF_ROOT_DIRECTORY.'Support/SendMessage.php'
                                                                                                )
                                                                        )
                                             )
                         );  // Keys are types of items to log, services and actions

 $CONF_LOG_EVENTS_LEVELS = array(EVT_LEVEL_WARNING, EVT_LEVEL_SYSTEM, EVT_LEVEL_MAIN_EVT, EVT_LEVEL_OTHER_EVT);  // Levels of events to log
 $CONF_LOG_EVENTS_LIMIT  = 1000000;   // The max number of events to log
 $CONF_LOG_EVENTS_TITLE_PREFIX = array(
                                       EVT_ACT_LOGIN => "",
                                       EVT_ACT_LOGIN_FAILED => "",
                                       EVT_ACT_LOGOUT => "",
                                       EVT_ACT_CREATE => "[+]",
                                       EVT_ACT_UPDATE => "[MAJ]",
                                       EVT_ACT_COPY   => "[//]",
                                       EVT_ACT_DELETE => "[-]",
                                       EVT_ACT_ADD => "[++]",
                                       EVT_ACT_LINK => "[§]",
                                       EVT_ACT_DIFFUSED => "[->]",
                                       EVT_ACT_EXECUTE => ""
                                      );  // Prefix to display in the title of the logged events, in relation with the actions done

 $CONF_LOG_USE_STATS = TRUE;  // To collect somes stats about intranet's functions (number of sent e-mails...)

 //########################### Graphic interface parameters ################
 $CONF_DISCONNECTION                  = $CONF_ROOT_DIRECTORY."GUI/Styles/Disconnection.gif"; // Picture used when the user want to close his session
 $CONF_PRINT_BULLET                   = $CONF_ROOT_DIRECTORY."GUI/Styles/Print.gif";        // Picture used to print a web page
 $CONF_EXPORT_TO_XML_BULLET           = $CONF_ROOT_DIRECTORY."GUI/Styles/ExportToXml.gif";  // Picture used to export data to XML format
 $CONF_EXPORT_TO_CSV_BULLET           = $CONF_ROOT_DIRECTORY."GUI/Styles/ExportToCsv.gif";  // Picture used to export data to CSV format
 $CONF_EXPORT_TO_HTML_BULLET          = $CONF_ROOT_DIRECTORY."GUI/Styles/ExportToHtml.gif";  // Picture used to export data to HTML format
 $CONF_PLANNING_LOCKED_ICON           = $CONF_ROOT_DIRECTORY."GUI/Styles/PlanningLocked.gif";  // Picture used when a registration in a planning is locked
 $CONF_HELP_ICON                      = $CONF_ROOT_DIRECTORY."GUI/Styles/Help.gif";  // Picture used to help the user
 $CONF_ADD_ICON                       = $CONF_ROOT_DIRECTORY."GUI/Styles/AddItem.png";  // Picture used to display a button to add an item
 $CONF_WARNING_ICON                   = $CONF_ROOT_DIRECTORY."GUI/Styles/Warning.png";  // Picture used to warn the user about a problem
 $CONF_NOTIFICATION_SENT_ICON         = $CONF_ROOT_DIRECTORY."GUI/Styles/email.gif";  // Picture used to show that a notification is sent
 $CONF_DELETE_ICON                    = $CONF_ROOT_DIRECTORY."GUI/Styles/Delete.gif";  // Picture used to delete something
 $CONF_ACTIVATION_ICON                = $CONF_ROOT_DIRECTORY."GUI/Styles/Reactivation.gif";  // Picture used to activate/reactivate something
 $CONF_SORT_TABLE_ASC                 = $CONF_ROOT_DIRECTORY."GUI/Styles/SortASC.gif";  // Picture used to show an ASC sort in a table
 $CONF_SORT_TABLE_DESC                = $CONF_ROOT_DIRECTORY."GUI/Styles/SortDESC.gif"; // Picture used to show an DESC sort in a table
 $CONF_CHECK_ALL_ICON                 = $CONF_ROOT_DIRECTORY."GUI/Styles/Check-all.png";  // Picture used to display a button to check all checkboxes
 $CONF_BILL_PAID_ICON                 = $CONF_ROOT_DIRECTORY."GUI/Styles/BillPaid.gif"; // Picture used when a bill is paid
 $CONF_PAYMENT_NOT_USED_ICON          = $CONF_ROOT_DIRECTORY."GUI/Styles/PaymentNotUsed.png"; // Picture used when a payment isn't totally used
 $CONF_PAYMENT_RESET_ICON             = $CONF_ROOT_DIRECTORY."GUI/Styles/DeleteLink.gif";     // Picture used to reset affectation of a payment to bills
 $CONF_ANNUAL_CONTRIBUTION_NOT_PAID_ICON = $CONF_ROOT_DIRECTORY."GUI/Styles/AnnualContributionNotPaid.gif";  // Picture used when an annual contribution isn't paid by a family
 $CONF_MONTHLY_CONTRIBUTION_MODES_ICONS  = array(
                                                 MC_BENEFACTOR_MODE => $CONF_ROOT_DIRECTORY."GUI/Styles/Benefactor.png",
                                                 MC_FAMILY_COEFF_1_MODE => $CONF_ROOT_DIRECTORY."GUI/Styles/Family_Coeff_T1.png",
                                                 MC_FAMILY_COEFF_2_MODE => $CONF_ROOT_DIRECTORY."GUI/Styles/Family_Coeff_T2.png",
                                                 MC_FAMILY_COEFF_3_MODE => $CONF_ROOT_DIRECTORY."GUI/Styles/Family_Coeff_T3.png",
                                                 MC_FAMILY_COEFF_4_MODE => $CONF_ROOT_DIRECTORY."GUI/Styles/Family_Coeff_T4.png"
                                                );  // Picture used when a family not use the default monthly contribution mode
 $CONF_DOCUMENT_APPROVED_ICON            = $CONF_ROOT_DIRECTORY."GUI/Styles/DocumentApproved.gif"; // Picture used when a document is approved by a family
 $CONF_REGISTERED_ON_EVENT_ICON          = $CONF_ROOT_DIRECTORY."GUI/Styles/RegisteredOnThisEvent.gif"; // Picture used when a family is registered on a displayed event
 $CONF_EVENT_COOPERATION_OK_ICON         = $CONF_ROOT_DIRECTORY."GUI/Styles/CooperationOK.gif"; // Picture used when a family has a good cooperation for events
 $CONF_EVENT_COOPERATION_NOK_ICON        = $CONF_ROOT_DIRECTORY."GUI/Styles/CooperationNOK.gif"; // Picture used when a family hasn't a good cooperation for events
 $CONF_EVENT_SWAP_IN_PROGRESS_ICON       = $CONF_ROOT_DIRECTORY."GUI/Styles/SwapInProgress.gif"; // Picture used when a family has a swap of registration to an event in progress
 $CONF_EVENT_NOT_TAKEN_INTO_ACCOUNT_ICON = $CONF_ROOT_DIRECTORY."GUI/Styles/NotTakenIntoAccount.gif"; // Picture used when a family is registered on an event but registration not valided
 $CONF_REGISTERED_ON_WORKGROUP_ICON      = $CONF_ROOT_DIRECTORY."GUI/Styles/RegisteredOnThisEvent.gif"; // Picture used when a person is registered on a displayed workgroup
 $CONF_WORKGROUP_REFERENT_ICON           = $CONF_ROOT_DIRECTORY."GUI/Styles/Referent.gif"; // Picture used when a support member is a referent of a workgroup

 //########################### URL of modules ##############################
 $CONF_URL_SUPPORT           = $CONF_ROOT_DIRECTORY."Support/";   // URL of the support molule
 $CONF_REWRITING_URL_SUPPORT = array();                           // Other Urls pointing on the support module

 //########################### Upload files parameters #####################
 $CONF_UPLOAD_UPLOADED_FILES_MAXSIZE        = 5242880;
 $CONF_UPLOAD_ALLOWED_EXTENSIONS            = array("txt", "pdf", "rtf", "odt", "doc", "docx", "xls", "xlsx", "csv", "ppt", "pptx", "pps", "vsd", "zip", "rar", "bmp", "jpg", "jpeg", "png", "gif", "mp3", "wav"); // Extensions allowed to upload a file

 $CONF_UPLOAD_DOCUMENTS_FILES_MAXSIZE       = 5242880;                         // Max size of a file for documents approvals, in bytes
 $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY     = $CONF_ROOT_DIRECTORY."Upload/Documents/";  // Path where the files uploaded for documents approvals are stored
 $CONF_UPLOAD_DOCUMENTS_FILES_DIRECTORY_HDD = dirname(__FILE__)."/../Upload/Documents/"; // The same path but used by the 'file_exists' and 'move_uploaded_file' functions
 $CONF_UPLOAD_EVENTS_FILES_DIRECTORY        = $CONF_ROOT_DIRECTORY."Upload/Events/";  // Path where the files uploaded for events are stored
 $CONF_UPLOAD_EVENTS_FILES_DIRECTORY_HDD    = dirname(__FILE__)."/../Upload/Events/"; // The same path but used by the 'file_exists' and 'move_uploaded_file' functions
 $CONF_UPLOAD_MESSAGE_FILES_DIRECTORY       = $CONF_ROOT_DIRECTORY."Upload/";  // Path where the files uploaded for messages are stored
 $CONF_UPLOAD_MESSAGE_FILES_DIRECTORY_HDD   = dirname(__FILE__)."/../Upload/"; // The same path but used by the 'file_exists' and 'move_uploaded_file' functions
 $CONF_UPLOAD_MESSAGE_FILES_MAXSIZE         = 5242880;                         // Max size of a file for a message, in bytes
 $CONF_UPLOAD_MESSAGE_FILES                 = TRUE;                            // The upload file function is enabled or not for the users

 //############################ E-mail parameters ##########################
 $CONF_EMAIL_SMTP_SERVERS = array(
                                  'Free' => array(
                                                  'Host' => 'smtp.free.fr',
                                                  'Port' => 465,
                                                  'Auth' => TRUE,
                                                  'Secure' => 'ssl',
                                                  'User' => 'root',
                                                  'Pwd' => 'root',
                                                  'FromName' => "Planeta Calandreta",
                                                  'From' => 'test@free.fr'
                                                 ),
                                  'Test' => array(
                                                  'Host' => 'smtp.test.com',
                                                  'Port' => 587,
                                                  'Auth' => TRUE,
                                                  'Secure' => 'tls',
                                                  'User' => 'root',
                                                  'Pwd' => 'root',
                                                  'FromName' => "Planeta Calandreta",
                                                  'From' => 'test@test.com'
                                                 )
                                 );  // List of SMTP servers to use to send notifications of the intranet
 $CONF_EMAIL_INTRANET_EMAIL_ADDRESS  = "xmailuser@xmailserver.test";      // E-mail address of the intranet used to send notifications
 $CONF_EMAIL_REPLY_EMAIL_ADDRESS     = "xmailuser@xmailserver.test";   // E-mail address to reply to a message sent by the intranet
 $CONF_EMAIL_TEMPLATES_DIRECTORY_HDD = dirname(__FILE__)."/../Templates/"; // HDD path of the e-mail templates
 $CONF_EMAIL_ANONYMOUS_SENDER        = TRUE;     // Display or not the e-mail address of the sender
 $CONF_EMAIL_OBJECTS_SUBJECT_PREFIX  = array(
                                             FCT_BILL => "[FACTURE Calandreta Muret] ",
                                             FCT_CANTEEN_PLANNING => "[CANTINE Calandreta Muret] ",
                                             FCT_SNACK_PLANNING => "[GOUTER Calandreta Muret] ",
                                             FCT_LAUNDRY_PLANNING => "[LESSIVE Calandreta Muret] ",
                                             FCT_EVENT => "[COOPERATION Calandreta Muret] ",
                                             FCT_WORKGROUP => "[COMMISSIONS Calandreta Muret] ",
                                             FCT_WORKGROUP_REGISTRATION => "[COMMISSIONS Calandreta Muret] ",
                                             FCT_MESSAGE => "[MESSAGE Calandreta Muret] ",
                                             FCT_DONATION => "[DON Calandreta Muret] ",
                                             FCT_DOCUMENT_APPROVAL => "[DOCUMENT Calandreta Muret] ",
                                             FCT_MEETING => "[REUNION Calandreta Muret] "
                                            );   // For each type of object, a prefix added to the subject of the notification must be defined
 $CONF_EMAIL_SYSTEM_NOTIFICATIONS    = array(
                                             "FamilyEmailUpdated" => array(
                                                                       To => array("calandreta@test.fr"),
                                                                       Cc => array(),
                                                                       Bcc => array(),
                                                                       Template => "EmailSystemFamilyEmailUpdated"
                                                                     ),
                                             "FamilyEmailContactAllowedUpdated" => array(
                                                                       To => array("chris.jav@libertysurf.fr"),
                                                                       Cc => array(),
                                                                       Bcc => array(),
                                                                       Template => "EmailSystemFamilyEmailContactAllowedUpdated"
                                                                     ),
                                             "ChildEmailUpdated" => array(
                                                                       To => array("chris.jav@libertysurf.fr"),
                                                                       Cc => array(),
                                                                       Bcc => array(),
                                                                       Template => "EmailSystemChildEmailUpdated"
                                                                     ),
                                             "WorkGroupDeleted" => array(
                                                                       To => array("calandreta@test.fr"),
                                                                       Cc => array(),
                                                                       Bcc => array(),
                                                                       Template => "EmailSystemWorkGroupDeleted"
                                                                     ),
                                             "WorkGroupRegistrationEmailUpdated" => array(
                                                                       To => array("calandreta@test.fr"),
                                                                       Cc => array(),
                                                                       Bcc => array(),
                                                                       Template => "EmailSystemWorkGroupRegistrationEmailUpdated"
                                                                     ),
                                             "UserMessageEmail" => array(
                                                                       To => array(),
                                                                       Cc => array(),
                                                                       Bcc => array(TO_AUTHOR_MESSAGE),
                                                                       Template => "EmailSystemUserMessageEmail"
                                                                     )
                                            );  // Parameters of system notifications

 //############################ LDAP parameters ############################
 $CONF_LDAP_HOSTNAME        = ""; // Name of the LDAP server
 $CONF_LDAP_PORT            = 389;        // Port of the LDAP server
 $CONF_LDAP_BASE_DN         = array("");  // List of the bases DN to contact
 $CONF_LDAP_FIELDS_RETURNED = array("cn", "mail");       // List of the fields to returned in the result

 //############################ Export parameters ##########################
 $CONF_EXPORT_DIRECTORY              = $CONF_ROOT_DIRECTORY."Exports/";                     // Path used to store export results
 $CONF_EXPORT_DIRECTORY_HDD          = dirname(__FILE__)."/../Exports/";      // HDD path used to store export results
 $CONF_EXPORT_XML_RESULT_FILENAME    = "XML_ExportResult";                                  // Filename used to store a XML export result
 $CONF_EXPORT_CSV_RESULT_FILENAME    = "CSV_ExportResult";                                  // Filename used to store a XML export result
 $CONF_PRINT_TEMPLATES_DIRECTORY     = $CONF_ROOT_DIRECTORY."Templates/";                   // Path used to store print templates (xsl and css files)
 $CONF_PRINT_TEMPLATES_DIRECTORY_HDD = dirname(__FILE__)."/../Templates/";    // HDD path used to store print templates (xsl and css files)

 //########################### Date/time format parameters #################
 if (ini_get('date.timezone') == '')
 {
     date_default_timezone_set('Europe/Paris');
 }
 else
 {
     date_default_timezone_set(ini_get('date.timezone'));
 }

 $CONF_DATE_SEPARATOR      = "/";
 $CONF_DATE_DISPLAY_FORMAT = "d".$CONF_DATE_SEPARATOR."m".$CONF_DATE_SEPARATOR."Y";     // This format is used to display a date with the date() PHP function : JJ/MM/AAAA
 $CONF_TIME_SEPARATOR      = ":";
 $CONF_TIME_DISPLAY_FORMAT = "H".$CONF_TIME_SEPARATOR."i".$CONF_TIME_SEPARATOR."s";     // This format is used to display a time with the date() PHP function : HH:MM:SS

 //########################### Redirections parameters #####################
 $CONF_TIME_LAG = 2;   // Time-lag of the redirection (between the display of the source page and the destination page)

 //############################ Support module parameters ##################
 $CONF_SUPPORT_URL_TO_DISPLAY_AFTER_LOGIN = array(
                                                  1 => 'index.php',
                                                  2 => 'Canteen/FamiliesList.php',
                                                  3 => 'Canteen/CanteenPlanning.php',
                                                  4 => 'Canteen/DaySynthesis.php',
                                                  5 => 'Canteen/CanteenPlanning.php',
                                                  7 => 'Cooperation/Cooperation.php',
                                                  'default' => 'index.php'
                                                 );   // Url to use as redirection after the user of a support member state is logged

 //############################ Access rights parameters ###################
 $CONF_ACCESS_APPL_PAGES = array(
                                 FCT_SYSTEM => array(
                                                     FCT_ACT_CREATE => array(1),
                                                     FCT_ACT_UPDATE => array(1),
                                                     FCT_ACT_READ_ONLY => array()
                                                    ),
                                 FCT_ADMIN => array(
                                                     FCT_ACT_CREATE => array(1),
                                                     FCT_ACT_UPDATE => array(1),
                                                     FCT_ACT_READ_ONLY => array()
                                                    ),
                                 FCT_FAMILY => array(
                                                     FCT_ACT_CREATE => array(1, 2, 6),
                                                     FCT_ACT_UPDATE => array(1, 2, 6),
                                                     FCT_ACT_READ_ONLY => array(),
                                                     FCT_ACT_PARTIAL_READ_ONLY => array(5),
                                                     FCT_ACT_UPDATE_OLD_USER => array(8)
                                                    ),
                                 FCT_PAYMENT => array(
                                                     FCT_ACT_CREATE => array(1, 2),
                                                     FCT_ACT_UPDATE => array(1, 2),
                                                     FCT_ACT_READ_ONLY => array(5, 6)
                                                    ),
                                 FCT_BANK => array(
                                                     FCT_ACT_CREATE => array(1, 2, 6),
                                                     FCT_ACT_UPDATE => array(1, 2, 6),
                                                     FCT_ACT_READ_ONLY => array(5)
                                                    ),
                                 FCT_BILL => array(
                                                     FCT_ACT_CREATE => array(1, 2),
                                                     FCT_ACT_UPDATE => array(1, 2),
                                                     FCT_ACT_READ_ONLY => array(5, 6)
                                                    ),
                                 FCT_CANTEEN_PLANNING => array(
                                                     FCT_ACT_CREATE => array(1, 2, 3, 5),
                                                     FCT_ACT_UPDATE => array(1, 2, 3, 5),
                                                     FCT_ACT_READ_ONLY => array(4, 6)
                                                    ),
                                 FCT_NURSERY_PLANNING => array(
                                                     FCT_ACT_CREATE => array(1, 2, 4),
                                                     FCT_ACT_UPDATE => array(1, 2, 4),
                                                     FCT_ACT_READ_ONLY => array(6),
                                                     FCT_ACT_PARTIAL_READ_ONLY => array(5)
                                                    ),
                                 FCT_SNACK_PLANNING => array(
                                                     FCT_ACT_CREATE => array(1),
                                                     FCT_ACT_UPDATE => array(1),
                                                     FCT_ACT_READ_ONLY => array(4, 5, 6)
                                                    ),
                                 FCT_LAUNDRY_PLANNING => array(
                                                     FCT_ACT_CREATE => array(1),
                                                     FCT_ACT_UPDATE => array(1),
                                                     FCT_ACT_READ_ONLY => array(4, 5, 6)
                                                    ),
                                 FCT_EXIT_PERMISSION => array(
                                                     FCT_ACT_CREATE => array(1, 5, 6),
                                                     FCT_ACT_UPDATE => array(1, 5, 6),
                                                     FCT_ACT_READ_ONLY => array(4)
                                                    ),
                                 FCT_DOCUMENT_APPROVAL => array(
                                                     FCT_ACT_CREATE => array(1, 6),
                                                     FCT_ACT_UPDATE => array(1, 6),
                                                     FCT_ACT_READ_ONLY => array(2, 3, 4, 7, 8),
                                                     FCT_ACT_PARTIAL_READ_ONLY => array(5)
                                                    ),
                                 FCT_EVENT => array(
                                                     FCT_ACT_CREATE => array(1, 7),
                                                     FCT_ACT_UPDATE => array(1, 7),
                                                     FCT_ACT_READ_ONLY => array(4, 6),
                                                     FCT_ACT_PARTIAL_READ_ONLY => array(5)
                                                    ),
                                 FCT_EVENT_REGISTRATION => array(
                                                     FCT_ACT_CREATE => array(1, 5, 7),
                                                     FCT_ACT_UPDATE => array(1, 5, 7),
                                                     FCT_ACT_READ_ONLY => array(6)
                                                    ),
                                 FCT_WORKGROUP => array(
                                                     FCT_ACT_CREATE => array(1, 6),
                                                     FCT_ACT_UPDATE => array(1, 6),
                                                     FCT_ACT_READ_ONLY => array(7),
                                                     FCT_ACT_PARTIAL_READ_ONLY => array(5)
                                                    ),
                                 FCT_WORKGROUP_REGISTRATION => array(
                                                     FCT_ACT_CREATE => array(1, 5, 6),
                                                     FCT_ACT_UPDATE => array(1, 5, 6),
                                                     FCT_ACT_READ_ONLY => array()
                                                    ),
                                 FCT_ALIAS => array(
                                                     FCT_ACT_CREATE => array(1),
                                                     FCT_ACT_UPDATE => array(1),
                                                     FCT_ACT_READ_ONLY => array(2, 3, 4, 5, 6, 7, 8)
                                                    ),
                                 FCT_MESSAGE => array(
                                                     FCT_ACT_CREATE => array(1, 2, 3, 4, 5, 6, 7, 8),
                                                     FCT_ACT_UPDATE => array(1, 6),
                                                     FCT_ACT_READ_ONLY => array(2, 3, 4, 5, 7, 8)
                                                    ),
                                 FCT_DONATION => array(
                                                     FCT_ACT_CREATE => array(1, 6),
                                                     FCT_ACT_UPDATE => array(1, 6),
                                                     FCT_ACT_READ_ONLY => array(2)
                                                    ),
                                 FCT_MEETING => array(
                                                     FCT_ACT_CREATE => array(1, 5, 6),
                                                     FCT_ACT_UPDATE => array(1, 5, 6),
                                                     FCT_ACT_READ_ONLY => array(2, 3, 4, 5, 7)
                                                    ),
                                 FCT_TOWN => array(
                                                     FCT_ACT_CREATE => array(1, 2, 6),
                                                     FCT_ACT_UPDATE => array(1, 2, 6),
                                                     FCT_ACT_READ_ONLY => array(5)
                                                    )
                                );  // Access rights for functions of the application. Values are support members states ID

 //##################### School, grades and classrooms parameters ##########
 $CONF_SCHOOL_YEAR_START_DATES = array();  // Loaded from DB ! The start date of each school year
 $CONF_SCHOOL_YEAR_LAST_MONTH  = "07";
 $CONF_GRADES          = array("-", "TPS", "PS", "MS", "GS", "CP", "CE1", "CE2", "CM1", "CM2");  // Grades of children
 $CONF_CLASSROOMS      = array();  // Loaded from DB ! Names of the classrooms, for each school year
 $CONF_GRADES_GROUPS   = array(
                               "Maternelles" => array(0, 1, 2, 3, 4),
                               "Primaires" => array(5, 6, 7, 8, 9)
                              );  // To group grades in some categories

 //########################### Payments parameters #########################
 $CONF_PAYMENTS_UNIT  = "E";  // Unit of the money used to pay
 $CONF_PAYMENTS_TYPES = array("Cotisation annuelle", "Facture mensuelle");  // Types of payments (what to pay)
 $CONF_PAYMENTS_MODES = array("Espèces", "Chèque", "Virement", "Carte banquaire");  // How to pay
 $CONF_PAYMENTS_MODES_BANK_REQUIRED = array(1);  // Indexes of payment modes for which the bank and check number are required
 $CONF_PAYMENTS_MANUAL_BILL_PART_AMOUNT = FALSE;    // Allow to enter manualy the part amount of a payment linked to a bill (FALSE = auto)

 //########################### Contributions parameters ####################
 $CONF_CONTRIBUTIONS_ANNUAL_AMOUNTS  = array();      // Loaded from DB ! Amounts of the annual contributions for years and for a number of powers
 $CONF_CONTRIBUTIONS_MONTHLY_AMOUNTS = array();      // Loaded from DB ! Amounts of the monthly contributions for years and for a number of children
 $CONF_CONTRIBUTIONS_RESET_MONTHLY_CONTRIBUTION_MODE = TRUE;  // When a new school year starts, the FamilyMonthlyContributionMode must be resetting to the default mode
 $CONF_NO_CONTRIBUTION_FOR_MONTHS    = array(7, 8);  // No monthly contribution to pay for these months

 //########################### Discounts/increases parameters ##############
 $CONF_DISCOUNTS_FAMILIES_TYPES        = array("Réduction", "Majoration");  // Types of discounts or increases
 $CONF_DISCOUNTS_FAMILIES_REASON_TYPES = array("Pénalité de retard de paiement", "Réduction de la dette", "Trop perçu");  // Types of reasons of discounts or increases

 //########################### Canteen parameters ##########################
 $CONF_PLANNING_VIEWS_TYPES = array(
                                    PLANNING_MONTH_VIEW => "Mois",
                                    PLANNING_WEEKS_VIEW => "Semaine"/*,
                                    PLANNING_DAYS_VIEW => "Jour"*/
                                   );  // Types of views to display the planning of the canteen

 $CONF_PLANNING_WEEKS_TO_DISPLAY  = 1;  // Number of weeks to display in the planning

 $CONF_CANTEEN_DEFAULT_VIEW_TYPES = array(
                                          1 => PLANNING_WEEKS_VIEW,
                                          2 => PLANNING_WEEKS_VIEW,
                                          3 => PLANNING_WEEKS_VIEW,
                                          4 => PLANNING_WEEKS_VIEW,
                                          5 => PLANNING_MONTH_VIEW,
                                          6 => PLANNING_WEEKS_VIEW,
                                          7 => PLANNING_WEEKS_VIEW
                                         );  // Default type of view of the canteen planning for support member states ID

 $CONF_CANTEEN_VIEWS_RESTRICTIONS = array(
                                          1 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          2 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          3 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          4 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          5 => PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN,
                                          6 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          7 => PLANNING_VIEWS_RESTRICTION_ALL
                                   );  // Restrictions on the view of the canteen planning for support member states ID

 $CONF_CANTEEN_OPENED_WEEK_DAYS                   = array(TRUE, TRUE, FALSE, TRUE, TRUE, FALSE, FALSE);  // The days in the week for which the canteen is opened. The first value is for monday
 $CONF_CANTEEN_NB_MONTHS_PLANNING_REGISTRATION    = 9;  // Nb of months for which the registration in the planning is allowed for families
 $CONF_CANTEEN_UPDATE_DELAY_PLANNING_REGISTRATION = 76;  // Minimum of hours between the modification of the registration in the planning by a family and the lunch
 $CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS       = 73;  // Display a warning on planning when number of canteen registrations for a day is > to this quantity. 0 to desactive
 $CONF_CANTEEN_WARNING_MAX_NB_REGISTRATIONS_DELAY = 1;   // Nb of days between the sent of an order of meals and to send a warning e-mail because there is too many canteen registrations for a date
 $CONF_CANTEEN_DELAYS_RESTRICTIONS = array(4, 5, 6);    // Support member states ID concerned by the delays restrictions
 $CONF_CANTEEN_QUANTITY_CORRECTIONS_FOR_GRADES_GROUPS = array(
                                                              "Maternelles" => array(-15, 0),
                                                              "Primaires" => array(15, 0)
                                                             );  // Meals to add or remove to the registraded quantities, for each grade group and for meals with pork and meals without pork
 $CONF_CANTEEN_MORE_MEALS_DISPATCHED_ON_GROUP = "Primaires";  // To add quantities of "more meals" on a given group. Leave empty if quantities mustn't dispatched on a group
 $CONF_CANTEEN_PROVISIONAL_QUANTITIES_TMP_FILE    = dirname(__FILE__)."/../Exports/CanteenProvisionalQuantities.txt";  // Path of temporary file containing canteen provisional quantities
 $CONF_CANTEEN_NOTIFICATIONS                      = array(
                                                          "ProvisionalPlanning" => array(
                                                                                         To => array("cam@test.fr"),
                                                                                         Cc => array("calandreta@test.fr"),
                                                                                         Bcc => array("calandreta.muret@test.fr"),
                                                                                         Template => "EmailProvisionalPlanning"
                                                                                        ),
                                                          "TodayPlanning" => array(
                                                                                         To => array("cam@test.fr"),
                                                                                         Cc => array("calandreta@test.fr"),
                                                                                         Bcc => array("calandreta.muret@test.fr"),
                                                                                         Template => "EmailTodayPlanning"
                                                                                        ),
                                                          "WarningTooManyRegistrations" => array(
                                                                                         To => array("calandreta@test.fr"),
                                                                                         Cc => array(),
                                                                                         Bcc => array(),
                                                                                         Template => "EmailWarningTooManyRegistrations"
                                                                                        )
                                                         );  // Notifications for some "functions"
 $CONF_CANTEEN_PRICES                             = array(); // Loaded from DB ! Price of the meal and the nursery after lunch
 $CONF_CANTEEN_PRICES_CONCERNED_MEAL_TYPES        = array(CANTEEN_REGISTRATION_DEFAULT_MEAL, CANTEEN_REGISTRATION_WITHOUT_PORK);  // Meal types concerned by the price of the lunch

 //########################### Nursery parameters ##########################
 $CONF_NURSERY_OPENED_WEEK_DAYS                   = array(
                                                          array(TRUE, TRUE),
                                                          array(TRUE, TRUE),
                                                          array(FALSE, FALSE),
                                                          array(TRUE, TRUE),
                                                          array(TRUE, TRUE),
                                                          array(FALSE, FALSE),
                                                          array(FALSE, FALSE)
                                                         );  // The days (AM and PM) in the week for which the canteen is opened. The first array is for monday (AM and PM)

 $CONF_NURSERY_DEFAULT_VIEW_TYPES = array(
                                          1 => PLANNING_WEEKS_VIEW,
                                          2 => PLANNING_WEEKS_VIEW,
                                          3 => PLANNING_WEEKS_VIEW,
                                          4 => PLANNING_WEEKS_VIEW,
                                          5 => PLANNING_MONTH_VIEW,
                                          6 => PLANNING_WEEKS_VIEW
                                         );  // Default type of view of the nursery planning for support member states ID

 $CONF_NURSERY_VIEWS_RESTRICTIONS = array(
                                          1 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          2 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          3 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          4 => PLANNING_VIEWS_RESTRICTION_ALL,
                                          5 => PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN,
                                          6 => PLANNING_VIEWS_RESTRICTION_ALL
                                   );  // Restrictions on the view of the nursery planning for support member states ID

 $CONF_NURSERY_UPDATE_DELAY_PLANNING_REGISTRATION   = 14;      // Maximum of days between the use of the nursery by a child and his registration in the planning of the nursery by a supporter
 $CONF_NURSERY_REGISTER_DELAY_PLANNING_REGISTRATION = 7;      // Nb of days for which the registration in the nurseray planning is allowed for users with FCT_ACT_PARTIAL_READ_ONLY access (ex : families)
 $CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_USE_CAPACITIES = FALSE;    // Allow or not registrations in the planning if "delay before planning registration" is passed but only is capacities allow new registrations in relation with grades of children
 $CONF_NURSERY_REGISTER_DELAY_BEFORE_PLANNING_REGISTRATION_CAPACITIES = array(
                                                                              array("Grade" => array(0, 1, 2, 3, 4), "Nb" => 14),
                                                                              array("Grade" => array(5, 6, 7, 8, 9), "Nb" => 18)
                                                                             );  // Capacities to supervise children in relation with their grade
 $CONF_NURSERY_REGISTER_DELAY_PLANNING_REGISTRATION = 49;      // Nb of days for which the registration in the nurseray planning is allowed for users with FCT_ACT_PARTIAL_READ_ONLY access (ex : families)
 $CONF_NURSERY_DELAYS_RESTRICTIONS                = array(3, 4, 5, 6);    // Support member states ID concerned by the delays restrictions
 $CONF_NURSERY_OTHER_TIMESLOTS                    = array(); // Loaded from DB ! Infos about other timeslots (ID, labels and opened days for the week)
 $CONF_NURSERY_PRICES                             = array(); // Loaded from DB ! Prices of nursery for AM and PM
 $CONF_NURSERY_DELAYS_PRICES                      = array(); // Loaded from DB ! Prices in relation with the number of nursery delays in the month

 //########################### Snack parameters ############################
 $CONF_SNACK_REMINDER_DELAY             = 4;  // The number of days between the date to bring the snack by families and the date to send a notification to the concerned families
 $CONF_SNACK_NOTIFICATIONS              = array(
                                                "RemindSnack" => array(
                                                                       To => array(),
                                                                       Cc => array("calandreta@test.fr"),
                                                                       Bcc => array(),
                                                                       Template => "EmailRemindSnack"
                                                                      ),
                                                "RemindSnackDuringWeek" => array(
                                                                       To => array(),
                                                                       Cc => array("calandreta@test.fr"),
                                                                       Bcc => array(),
                                                                       Template => "EmailRemindSnackDuringWeek"
                                                                      ),
                                                "UpdatedSnackPlanning" => array(
                                                                       To => array(),
                                                                       Cc => array("calandreta@test.fr"),
                                                                       Bcc => array(),
                                                                       Template => "EmailSnackPlanningUpdated"
                                                                      )
                                               );  // Parameters of notifications of snack registrations

 //########################### Laundry parameters ##########################
 $CONF_LAUNDRY_FOR_DAYS                 = array(5);  // The days in the week for which laundry must be done by families
 $CONF_LAUNDRY_NB_FAMILIES_FOR_A_DATE   = 2;  // The number of families to get laundry for a given date
 $CONF_LAUNDRY_REMINDER_DELAY           = 1;  // The number of days between the date to get laundry by families and the date to send a notification to the concerned families
 $CONF_LAUNDRY_NOTIFICATIONS            = array(
                                                "RemindLaundry" => array(
                                                                       To => array(),
                                                                       Cc => array("calandreta@test.fr"),
                                                                       Bcc => array(),
                                                                       Template => "EmailRemindLaundry"
                                                                      ),
                                                "UpdatedLaundryPlanning" => array(
                                                                       To => array(),
                                                                       Cc => array("calandreta@test.fr"),
                                                                       Bcc => array(),
                                                                       Template => "EmailLaundryPlanningUpdated"
                                                                      )
                                               );  // Parameters of notifications of laundry registrations

 //########################### Exit permissions parameters #################
 $CONF_EXIT_PERMISSIONS_VIEWS_RESTRICTIONS = array(
                                                   1 => PLANNING_VIEWS_RESTRICTION_ALL,
                                                   2 => PLANNING_VIEWS_RESTRICTION_ALL,
                                                   3 => PLANNING_VIEWS_RESTRICTION_ALL,
                                                   4 => PLANNING_VIEWS_RESTRICTION_ALL,
                                                   5 => PLANNING_VIEWS_RESTRICTION_ONLY_OWN_CHILDREN,
                                                   6 => PLANNING_VIEWS_RESTRICTION_ALL
                                                  );  // Restrictions on the view of the exit permissions list for support member states ID

 $CONF_EXIT_PERMISSIONS_NB_DAYS_REGISTRATION    = 14;  // Nb of day for which the registration in the exit persmissions list is allowed for families

 //########################### Documents approvals parameters ##############
 $CONF_DOCUMENTS_APPROVALS_TYPES         = array("Autorisation droit à l'image", "Autorisation de sortie", "Règlement intérieur");  // Types of documents to approve
 $CONF_DOCUMENTS_APPROVALS_NOTIFICATIONS = array(
                                                 "NewDocument" => array(
                                                                        Cc => array(),
                                                                        Template => "EmailDocumentToApproveAdded"
                                                                       )
                                                );  // Parameters of notifications of documents to approve

 //########################### Bills parameters ############################
 $CONF_BILLS_ALLOW_BILL_FOR_CURRENT_MONTHS = array(7);  // Months for which the bill can be generated even the month isn't past
 $CONF_BILLS_PRINT_CSS_PATH        = $CONF_PRINT_TEMPLATES_DIRECTORY_HDD."PrintBillsStyles.css";  // Path of the CSS to use for the generated bills in HTML/CSS format then PDF
 $CONF_BILLS_PRINT_XSL_PATH        = $CONF_PRINT_TEMPLATES_DIRECTORY_HDD."PrintDetailsBill.xsl";  // Path of the XSL file to use for the generated bills in HTML/CSS format then PDF
 $CONF_BILLS_PRINT_GLOBAL_XSL_PATH = $CONF_PRINT_TEMPLATES_DIRECTORY_HDD."PrintDetailsAllBills.xsl";  // Path of the XSL file to use for the generated the HTML/CSS file (then PDF) containing all bills of the month
 $CONF_BILLS_PRINT_FILENAME        = "Facture"; // Name of the file for HTML and PDF files, without extension
 $CONF_BILLS_PRINT_GLOBAL_FILENAME = "FacturesMensuelles"; // Name of the file for HTML and PDF files for the file containing all bills of the month, without extension
 $CONF_BILLS_FAMILIES_SEND_NOTIFICATION_BY_DEFAULT = FALSE;  // By default, check the box to send or not a notification with the bill of the month to each family
 $CONF_BILLS_FAMILIES_NOTIFICATION = "EmailFamilyBill";  // Template to use to send monthly bills to families. If empty, no notification sent
 $CONF_BILLS_NOT_PAID_BILLS_LIMIT  = 2;  // The number of not paid bills of a family from which the "not paid bills" template is used to send the monthly bill to the family
 $CONF_BILLS_FAMILIES_WITH_NOT_PAID_BILLS_NOTIFICATION = "EmailFamilyBillWithNotPaidBills";  // Template to use to send monthly bills to families having several not paid bills. If empty, no notification sent

 $CONF_BILLS_PRINT_ANNUAL_XSL_PATH        = $CONF_PRINT_TEMPLATES_DIRECTORY_HDD."PrintDetailsAnnualBill.xsl";  // Path of the XSL file to use for the generated annual bills in HTML/CSS format then PDF
 $CONF_BILLS_PRINT_GLOBAL_ANNUAL_XSL_PATH = $CONF_PRINT_TEMPLATES_DIRECTORY_HDD."PrintDetailsAllAnnualBills.xsl";  // Path of the XSL file to use for the generated the HTML/CSS file (then PDF) containing all bills of the year
 $CONF_BILLS_PRINT_GLOBAL_ANNUAL_FILENAME = "FacturesAnnuelles"; // Name of the file for HTML and PDF files for the file containing all bills of the year, without extension
 $CONF_BILLS_FAMILIES_SEND_ANNUAL_NOTIFICATION_BY_DEFAULT = FALSE;  // By default, check the box to send or not a notification with the bill of the year to each family
 $CONF_BILLS_FAMILIES_ANNUAL_NOTIFICATION = "EmailFamilyAnnualBill";  // Template to use to send annual bills to families. If empty, no notification sent

 //########################### Cooperation parameters ######################
 $CONF_COOP_EVENT_TYPE_CATEGORIES                   = array("Festif", "Aide/Travaux", "Bureaux/Commissions");  // Categories to group event types
 $CONF_COOP_EVENT_TYPE_CATEGORIES_MIN_REGISTRATIONS = array(
                                                            0 => 1,
                                                            1 => 1
                                                           );  // For each category of even type, the minimum registrations for each family for a school year
 $CONF_COOP_EVENT_REGISTRATION_VIEWS_RESTRICTIONS = array(
                                                          1 => EVENT_REGISTRATION_VIEWS_RESTRICTION_ALL,
                                                          5 => EVENT_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY,
                                                          6 => EVENT_REGISTRATION_VIEWS_RESTRICTION_ALL,
                                                          7 => EVENT_REGISTRATION_VIEWS_RESTRICTION_ALL
                                                         );  // Restrictions on the view of the event registration for support member states ID
 $CONF_COOP_EVENT_DEFAULT_MAX_FAMILIES       = 15;   // By default, the max number of families which can register to an event
 $CONF_COOP_EVENT_DEFAULT_REGISTRATION_DELAY = 7;    // By default, the number of days between the start date of the event and the closing date of registrations to this event (for the families)
 $CONF_COOP_EVENT_USE_REGISTRATION_CLOSING_DATE = TRUE;  // True -> display a date field to close registrations / False -> display a texet field with a number of days for the delay
 $CONF_COOP_EVENT_USE_RANDOM_AUTO_FAMILIES_REGISTRATIONS = array(8);  // Event types ID for which we can use an automatic registration of families thanks to a random selection
 $CONF_COOP_EVENT_DELAYS_RESTRICTIONS        = array(5);    // Support member states ID concerned by the delays restrictions
 $CONF_COOP_EVENT_MIN_REGISTRATION_RATE      = 0.5;  // In case of unregistration of a family to an event, define the minimum rate (nb registered families / nb max allowed registrered families) above a notification must be sent
 $CONF_COOP_EVENT_REMINDER_DELAY             = 3;    // The number of days between the start date of the event and the date to send a notification to registrered families to the event (to remind the event)
 $CONF_COOP_EVENT_NOTIFICATIONS = array(
                                        "NewEvent" => array(
                                                            To => array(TO_ALL_FAMILIES_EVENT),
                                                            Cc => array(),
                                                            Template => "EmailEventCreated",
                                                            Inhibition => array(6, 7, 8, 9, 10)
                                                           ),
                                        "UpdatedEvent" => array(
                                                               To => array(TO_ALL_REGISTRERED_FAMILIES_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventUpdated",
                                                               Inhibition => array(6, 7, 8, 9, 10)
                                                              ),
                                        "CommunicationEvent" => array(
                                                               To => array(TO_ALL_REGISTRERED_FAMILIES_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventCommunication",
                                                               Inhibition => array(6, 7)
                                                              ),
                                        "DeletedEvent" => array(
                                                               To => array(TO_ALL_REGISTRERED_FAMILIES_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventDeleted"
                                                              ),
                                        "RemindEvent" => array(
                                                               To => array(TO_ALL_REGISTRERED_FAMILIES_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventReminder"
                                                              ),
                                        "MinRegistrationRatioEvent" => array(
                                                               To => array(TO_NO_INDICOOP_FAMILIES_FIRST_TO_ALL_UNREGISTRERED_FAMILIES_AFTER_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventTooLowRegistrations"
                                                              ),
                                        "MinRegistrationRatioEventAuthor" => array(
                                                               To => array(TO_AUTHOR_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventTooLowRegistrationsAuthor"
                                                              ),
                                        "FamilyRegisteredEvent" => array(
                                                               To => array(TO_FAMILY_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventFamilyRegisteredByResp",
                                                               Inhibition => array(6, 7)
                                                              ),
                                        "FamilyUnregisreredEvent" => array(
                                                               To => array(TO_FAMILY_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventFamilyUnregisteredByResp",
                                                               Inhibition => array(6, 7)
                                                              ),
                                        "FamilySwapRequestRegisteredEvent" => array(
                                                               To => array(TO_FAMILY_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventFamilySwapRequest"
                                                              ),
                                        "FamilySwapAcceptRegisteredEvent" => array(
                                                               To => array(TO_FAMILY_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventFamilySwapAccept"
                                                              ),
                                        "NoIndicoopFamiliesReminder" => array(
                                                               To => array(TO_NO_INDICOOP_FAMILIES_EVENT),
                                                               Cc => array(),
                                                               Template => "EmailEventNoIndicoopFamiliesReminder"
                                                              )
                                       );  // Parameters of notifications of events

 //########################### Workgroups parameters #######################
 $CONF_COOP_WORKGROUP_REGISTRATION_VIEWS_RESTRICTIONS = array(
                                                              1 => WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL,
                                                              5 => WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ONLY_FAMILY,
                                                              6 => WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL,
                                                              7 => WORKGROUP_REGISTRATION_VIEWS_RESTRICTION_ALL
                                                             );  // Restrictions on the view of the workgroup registration for support member states ID
 $CONF_COOP_WORKGROUP_ALLOW_REGISTRATIONS_FOR_REFERENTS = TRUE;  // True to allow referents to register families on a workgroup
 $CONF_COOP_WORKGROUP_ALLOW_UNREGISTRATIONS_FOR_USERS   = FALSE; // True to allow users to delete a registration in a workgroup. False : only referents can delete registrations

 //########################### Donation parameters #########################
 $CONF_DONATION_ENTITIES               = array("Particulier", "Professionnel");  // Entities who can make a donation
 $CONF_DONATION_TYPES                  = array("Carnet", "Formulaire papier", "Plate-forme web");  // Types of donations
 $CONF_DONATION_NATURES                = array("Numéraire", "En nature", "Autre");  // Natures of the donation
 $CONF_DONATION_NATURES_TOTAL          = array(0);  // ID of the natures for which we must do the total in the list of donations page
 $CONF_DONATION_FAMILY_RELATIONSHIP    = array("-", "Parent direct", "Frère/soeur", "Grand-parent", "Voisin/Amis", "Autre");
 $CONF_DONATION_CLOSED_AFTER_NB_YEARS  = 1;  // Nb of years after a donation is closed (in relation with the reception date of the donation)
 $CONF_DONATION_TAX_RECEIPT_FOR_TYPES  = array(0, 1);  // The donation's types for which a tax receipt must be generated
 $CONF_DONATION_TAX_RECEIPTS_PRINT_FILENAME = "RecuFiscal"; // Name of the file for HTML and PDF files, without extension, for each tax receipt sent by e-mail
 $CONF_DONATION_TAX_RECEIPTS_WITHOUT_EMAIL_PRINT_FILENAME = "RecusFiscauxParCourrier"; // Name of the file for HTML and PDF files, without extension, for a global tax receipts document send by mail
 $CONF_DONATION_NOTIFICATIONS            = array(
                                                "TaxReceipt" => array(
                                                                       To => array(),
                                                                       Cc => array(),
                                                                       Bcc => array(),
                                                                       Template => "EmailDonatorTaxReceipt"
                                                                      ),
                                                "ReviveDonators" => array(
                                                                       To => array(),
                                                                       Cc => array(),
                                                                       Template => "EmailDonatorRevive"
                                                                      )
                                               );  // Parameters of notifications of donations and tax receipts
 $CONF_DONATION_TAX_RECEIPT_PARAMETERS = array();  // Loaded from DB ! Define parameters to generate content of tax receipts for a year

 //######################## Meeting registrations parameters ###############
 $CONF_MEETING_REGISTRATIONS_VIEWS_TYPES = array(
                                                 PLANNING_MONTH_VIEW => "Mois",
                                                 PLANNING_WEEKS_VIEW => "Semaine"
                                                );  // Types of views to display the planning of the meeting rooms registrations
 $CONF_MEETING_REGISTRATIONS_WEEKS_TO_DISPLAY   = 1;  // Number of weeks to display in the planning of meeting rooms registrations
 $CONF_MEETING_REGISTRATIONS_DEFAULT_VIEW_TYPES = array(
                                                        1 => PLANNING_MONTH_VIEW,
                                                        2 => PLANNING_MONTH_VIEW,
                                                        3 => PLANNING_MONTH_VIEW,
                                                        4 => PLANNING_MONTH_VIEW,
                                                        5 => PLANNING_MONTH_VIEW,
                                                        6 => PLANNING_MONTH_VIEW,
                                                        7 => PLANNING_MONTH_VIEW
                                                       );  // Default type of view of the meeting rooms registrations planning for support member states ID
 $CONF_MEETING_REGISTRATIONS_PLANNING_COLORS    = array(
                                                        1 => array("background-color" => "#b5e61d", "text-color" => "#000"),
                                                        2 => array("background-color" => "#99d9ea", "text-color" => "#000"),
                                                        3 => array("background-color" => "#ff7f27", "text-color" => "#fff"),
                                                        4 => array("background-color" => "#fff227", "text-color" => "#000"),
                                                        5 => array("background-color" => "#ff80c0", "text-color" => "#000"),
                                                        6 => array("background-color" => "#c8bfe7", "text-color" => "#000")
                                                       );  // Background color and text color to display registrations of each meeting room ID
 $CONF_MEETING_REGISTRATIONS_OPENED_HOURS_FOR_WEEK_DAYS = array(
                                                                array("17:00-23:59"),
                                                                array("17:00-23:59"),
                                                                array("18:30-23:59"),
                                                                array("17:00-23:59"),
                                                                array("17:00-23:59"),
                                                                array("08:00-23:59"),
                                                                array("08:00-23:59")
                                                               );  // The hours for each day of the week for which the meeting rooms can be registered. The first value is for monday
 $CONF_MEETING_REGISTRATIONS_TIME_SLOT_SIZE = 15;  // Size of each time slot, in minutes
 $CONF_MEETING_REGISTRATIONS_ALLOW_REGISTRATIONS_FOR_REFERENTS_ONLY = TRUE;  // True to allow only referents to book some meeting rooms. False to allow all families to book some meeting rooms
 $CONF_MEETING_REGISTRATIONS_ALLOWED_EVENT_CATEGORIES = array(2);  // Event categories ID of events allowed to be linked to meeting rooms registrations
 $CONF_MEETING_REGISTRATIONS_NOTIFICATIONS            = array(
                                                              "NewRegistrationNotifyMailingList" => array(
                                                                       Cc => array(),
                                                                       Template => "EmailMeetingNewRegistrationNotifyMailingList"
                                                                      ),
                                                              "NewRegistrationNotifyRoomEmail" => array(
                                                                       Cc => array(),
                                                                       Template => "EmailMeetingNewRegistrationNotifyRoomEmail"
                                                                      ),
                                                              "DeleteRegistrationNotifyMailingList" => array(
                                                                       Cc => array(),
                                                                       Template => "EmailMeetingDeleteRegistrationNotifyMailingList"
                                                                      ),
                                                              "DeleteRegistrationNotifyRoomEmail" => array(
                                                                       Cc => array(),
                                                                       Template => "EmailMeetingDeleteRegistrationNotifyRoomEmail"
                                                                      )
                                                             );  // Parameters of notifications of meeting rooms registrations

 //################################## Jobs parameters ######################
 $CONF_JOBS_EXECUTION_DELAY      = 6;  // Nb hours from now to take into account jobs to execute or to try to re-execute
 $CONF_JOBS_DELETE_AFTER_NB_DAYS = 2;  // Delete executed jobs after x days
 $CONF_JOBS_TO_EXECUTE           = array(
                                         JOB_EMAIL => array(
                                                            FCT_BILL => array(JobSize => 15, DelayBetween2Jobs => 10),
                                                            FCT_DOCUMENT_APPROVAL => array(JobSize => 15, DelayBetween2Jobs => 10),
                                                            FCT_EVENT => array(JobSize => 15, DelayBetween2Jobs => 10),
                                                            FCT_DONATION => array(JobSize => 15, DelayBetween2Jobs => 10),
                                                            FCT_MESSAGE => array(JobSize => 15, DelayBetween2Jobs => 10)
                                                           )
                                        );  // Parameters of the delayed jobs to execute (ex : send a lot of notifications by e-mail). Delay in minutes
 $CONF_JOBS_BEFORE_CHANGE_PLANNED_DATE = array(
                                               JOB_EMAIL => array(JobNbFails => 10, DelayAfterXJobFails => 90)
                                              ); // Nb of tries before we decide to change the planned date with DelayAfterXJobFails value if set

 //########################### HTML to PDF convertion parameters ###########
 $CONF_OS = APPL_OS_WINDOWS;  // OS used for the application : APPL_OS_WINDOWS or APPL_OS_LINUX
 $CONF_PDF_LIB = PDF_LIB_FPDF;  // PDF libradry used to convert HTML to PDF : PDF_LIB_WKHTMLTOPDF or PDF_LIB_DOMPDF
 $CONF_PDF_BIN_PATH_FOR_OS = array(
                                   PDF_LIB_WKHTMLTOPDF => array(
                                                                APPL_OS_WINDOWS => "C:/Program Files/wkhtmltopdf/wkhtmltopdf.exe",
                                                                APPL_OS_LINUX => "/local/opt/wkhtmltopdf/wkhtmltopdf-i386"
                                                               ),
                                   PDF_LIB_DOMPDF => array(
                                                           APPL_OS_WINDOWS => dirname(__FILE__)."/Dompdf/dompdf_config.inc.php",
                                                           APPL_OS_LINUX => dirname(__FILE__)."/Dompdf/dompdf_config.inc.php"
                                                          ),
                                   PDF_LIB_FPDF => array(
                                                           APPL_OS_WINDOWS => dirname(__FILE__)."/FPDF/fpdf.php",
                                                           APPL_OS_LINUX => dirname(__FILE__)."/FPDF/fpdf.php"
                                                          )
                                  );  // Path of the binary of each PDF tool, for each OS


 //########################### Content of dropdown lists ###################
 $CONF_DAYS_OF_WEEK                  = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi");  // 5 days of the week to display in the planning
 $CONF_LOGICAL_OPERATORS             = array("<", "<=", "=", "<>", ">", ">=");                // Logical operators used by the search engines
 $CONF_PLANNING_MONTHS               = array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");  // Names of the months
 $CONF_MONTHLY_CONTRIBUTION_MODES    = array(MC_DEFAULT_MODE => "Membre actif", MC_BENEFACTOR_MODE => "Membre bienfaiteur", MC_FAMILY_COEFF_1_MODE => "Coeff familial T1", MC_FAMILY_COEFF_2_MODE => "Coeff familial T2", MC_FAMILY_COEFF_3_MODE => "Coeff familial T3", MC_FAMILY_COEFF_4_MODE => "Coeff familial T4");  // Labels of modes of monthly contributions
 $CONF_MEAL_TYPES                    = array(CANTEEN_REGISTRATION_DEFAULT_MEAL => "Repas par défaut", CANTEEN_REGISTRATION_WITHOUT_PORK => "Repas sans viande", CANTEEN_REGISTRATION_PACKED_LUNCH => "Panier repas"); // Labels of types of meals for canteen registrations

 //########################### Default values of fields ####################
 $CONF_DEFAULT_VALUES_SET = array(
                                  'Fields' => array(
                                                    'PaymentMode' => 2,
                                                    'DocumentApprovalType' => 2,
                                                    'DonationEntity' => 0,
                                                    'DonationType' => 1,
                                                    'DonationNature' => 0,
                                                    'DonationPaymentMode' => 1
                                                   ),
                                  'OrderBy' => array(
                                                     'DocumentsApprovals' => -3,
                                                     'DonationsList' => -3
                                                    )
                                 );  // Define default values for some fields and default "order by" for tables of some web pages

 //###################### Number of records, in a table, per page ##########
 $CONF_TABLE_LINKS_PAGES                = 20;
 $CONF_RECORDS_PER_PAGE                 = 25;
 $CONF_NB_FAMILY_BILLS                  = 12;  // Nb of max bills displayed on a family details (0 = no limit)

 //############################ Web services parameters ###################
 $CONF_WEB_SERVICES = array(

                           );  // Activated web services (REST)

 $CONF_MAIL_SERVICES = array(

                            );

 //############################ JavaScript Plugins parameters #############
 $CONF_JS_PLUGINS_TO_USE = array(
                                 '/Support' => array(
                                              'initCalandretaGUIPlugin' => array(
                                                                                'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/JSCalandretaGUIPlugin.js"),
                                                                                'Params' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/"),
                                                                                'Css' => array(
                                                                                                'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/JSCalandretaGUIPluginStyles.css")
                                                                                              )
                                                                            ),
                                              'initCalandretaMainMenuPlugin' => array(
                                                                               'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaMainMenuPlugin/JSCalandretaMainMenuPlugin.js"),
                                                                               'Params' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaMainMenuPlugin/"),
                                                                               'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaMainMenuPlugin/JSCalandretaMainMenuPluginStyles.css")
                                                                                             )
                                                                            ),
                                              'initStillActivePlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/JSStillActivePlugin/JSStillActivePlugin.js"
                                                                                                  ),
                                                                                'Params' => array(180),     // in seconds
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSStillActivePlugin/JSStillActivePluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initTranslatePlugin' => array(
                                                                               'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/PHPTranslatePlugin/JSTranslatePlugin.js"),
                                                                               'Params' => array($CONF_ROOT_DIRECTORY."Plugins/PHPTranslatePlugin/", 35, 5),
                                                                               'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPTranslatePlugin/PHPTranslatePluginStyles.css")
                                                                                             )
                                                                            ),
                                              'initCheckApprovalDocumentsPlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPCheckApprovalDocumentsPlugin/JSCheckApprovalDocumentsPlugin.js"
                                                                                                  ),
                                                                                'Params' => array($CONF_ROOT_DIRECTORY."Plugins/PHPCheckApprovalDocumentsPlugin/", $CONF_LANG),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPCheckApprovalDocumentsPlugin/PHPCheckApprovalDocumentsStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/index.php' => array(
                                              'initCalandretaLostPwdPlugin' => array(
                                                                               'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/PHPCalandretaLostPwdPlugin/JSCalandretaLostPwdPlugin.js"),
                                                                               'Params' => array($CONF_ROOT_DIRECTORY."Plugins/PHPCalandretaLostPwdPlugin/", $CONF_LANG),
                                                                               'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPCalandretaLostPwdPlugin/PHPCalandretaLostPwdPluginStyles.css")
                                                                                             )
                                                                            )
                                              ),
                                 '/Support/SendMessage.php' => array(
                                              'initAutoCompletionSendMessagePlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionSendMessagePlugin/JSAutoCompletionSendMessagePlugin.js"
                                                                                                  ),
                                                                                'Params' => array('sRecipients', $CONF_LANG),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionSendMessagePlugin/PHPAutoCompletionSendMessagePluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Canteen/AddTown.php' => array(
                                              'initAutoCompletion' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sName',
                                                                                                  'Towns',
                                                                                                  'TownName'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Canteen/AddBank.php' => array(
                                              'initAutoCompletion' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sName',
                                                                                                  'Banks',
                                                                                                  'BankName'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Canteen/CanteenPlanning.php' => array(
                                              'initCanteenPlanningAutoSavePlugin' => array(
                                                                               'Scripts' => array(
                                                                                                  $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                  $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                  $CONF_ROOT_DIRECTORY."Plugins/PHPCanteenPlanningAutoSavePlugin/JSCanteenPlanningAutoSavePlugin.js"
                                                                                                 ),
                                                                               'Params' => array($CONF_LANG),
                                                                               'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPCanteenPlanningAutoSavePlugin/PHPCanteenPlanningAutoSavePluginStyles.css")
                                                                                             )
                                                                            )
                                              ),
                                 '/Support/Canteen/NurseryPlanning.php' => array(
                                              'initNurseryPlanningAutoSavePlugin' => array(
                                                                               'Scripts' => array(
                                                                                                  $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                  $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                  $CONF_ROOT_DIRECTORY."Plugins/PHPNurseryPlanningAutoSavePlugin/JSNurseryPlanningAutoSavePlugin.js"
                                                                                                 ),
                                                                               'Params' => array($CONF_LANG),
                                                                               'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPNurseryPlanningAutoSavePlugin/PHPNurseryPlanningAutoSavePluginStyles.css")
                                                                                             )
                                                                            )
                                              ),
                                 '/Support/Canteen/ExitPermissions.php' => array(
                                              'initAutoCompletion' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sLastname',
                                                                                                  'Families',
                                                                                                  'FamilyLastname'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__1' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sLastname',
                                                                                                  'ExitPermissions',
                                                                                                  'ExitPermissionName'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Cooperation/UpdateEvent.php' => array(
                                              'initDisplaySubEventsRegistrationsPlugin' => array(
                                                                               'Scripts' => array(
                                                                                                  $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                  $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                  $CONF_ROOT_DIRECTORY."Plugins/PHPDisplaySubEventsRegistrationsPlugin/PHPDisplaySubEventsRegistrationsPlugin.js"
                                                                                                 ),
                                                                               'Params' => array($CONF_LANG),
                                                                               'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPDisplaySubEventsRegistrationsPlugin/PHPDisplaySubEventsRegistrationsPluginStyles.css")
                                                                                             )
                                                                            )
                                              ),
                                 '/Support/Cooperation/AddWorkGroupRegistration.php' => array(
                                              'initAutoCompletionWorkGroupRegistrationLastnamePlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationLastnamePlugin/JSAutoCompletionWorkGroupRegistrationLastnamePlugin.js"
                                                                                                  ),
                                                                                'Params' => array('sLastname'),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationLastnamePlugin/PHPAutoCompletionWorkGroupRegistrationLastnamePluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletionWorkGroupRegistrationEmailPlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationEmailPlugin/JSAutoCompletionWorkGroupRegistrationEmailPlugin.js"
                                                                                                  ),
                                                                                'Params' => array('sEmail'),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationEmailPlugin/PHPAutoCompletionWorkGroupRegistrationEmailPluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Cooperation/UpdateWorkGroupRegistration.php' => array(
                                              'initAutoCompletionWorkGroupRegistrationLastnamePlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationLastnamePlugin/JSAutoCompletionWorkGroupRegistrationLastnamePlugin.js"
                                                                                                  ),
                                                                                'Params' => array('sLastname'),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationLastnamePlugin/PHPAutoCompletionWorkGroupRegistrationLastnamePluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletionWorkGroupRegistrationEmailPlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationEmailPlugin/JSAutoCompletionWorkGroupRegistrationEmailPlugin.js"
                                                                                                  ),
                                                                                'Params' => array('sEmail'),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionWorkGroupRegistrationEmailPlugin/PHPAutoCompletionWorkGroupRegistrationEmailPluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Cooperation/CreateDonation.php' => array(
                                              'initAutoCompletion' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sLastname',
                                                                                                  'Families',
                                                                                                  'FamilyLastname'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__1' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sLastname',
                                                                                                  'Donations',
                                                                                                  'DonationLastname'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__2' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sFirstname',
                                                                                                  'Donations',
                                                                                                  'DonationFirstname'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__3' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Families',
                                                                                                  'FamilyMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__4' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Families',
                                                                                                  'FamilySecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__5' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Donations',
                                                                                                  'DonationMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__6' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Donations',
                                                                                                  'DonationSecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__7' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Families',
                                                                                                  'FamilyMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__8' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Families',
                                                                                                  'FamilySecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__9' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Donations',
                                                                                                  'DonationMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__10' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Donations',
                                                                                                  'DonationSecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__11' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sAddress',
                                                                                                  'Donations',
                                                                                                  'DonationAddress'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Cooperation/UpdateDonation.php' => array(
                                              'initAutoCompletion' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sLastname',
                                                                                                  'Families',
                                                                                                  'FamilyLastname'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__1' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sLastname',
                                                                                                  'Donations',
                                                                                                  'DonationLastname'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__2' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sFirstname',
                                                                                                  'Donations',
                                                                                                  'DonationFirstname'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__3' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Families',
                                                                                                  'FamilyMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__4' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Families',
                                                                                                  'FamilySecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__5' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Donations',
                                                                                                  'DonationMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__6' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sMainEmail',
                                                                                                  'Donations',
                                                                                                  'DonationSecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__7' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Families',
                                                                                                  'FamilyMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__8' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Families',
                                                                                                  'FamilySecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__9' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Donations',
                                                                                                  'DonationMainEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__10' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sSecondEmail',
                                                                                                  'Donations',
                                                                                                  'DonationSecondEmail'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               ),
                                              'initAutoCompletion__11' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Common/JSScriptaculous/src/scriptaculous.js",
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/JSAutoCompletionPlugin.js"
                                                                                                  ),
                                                                                'Params' => array(
                                                                                                  'sAddress',
                                                                                                  'Donations',
                                                                                                  'DonationAddress'
                                                                                                 ),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPAutoCompletionPlugin/PHPAutoCompletionPluginStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Cooperation/CreateMeetingRoomRegistration.php' => array(
                                              'initLoadMeetingRoomRestrictionsPlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPLoadMeetingRoomRestrictions/JSLoadMeetingRoomRestrictionsPlugin.js"
                                                                                                  ),
                                                                                'Params' => array($CONF_ROOT_DIRECTORY."Plugins/PHPLoadMeetingRoomRestrictions/"),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPLoadMeetingRoomRestrictions/PHPLoadMeetingRoomRestrictionsStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Support/Cooperation/UpdateMeetingRoomRegistration.php' => array(
                                              'initLoadMeetingRoomRestrictionsPlugin' => array(
                                                                                'Scripts' => array(
                                                                                                   $CONF_ROOT_DIRECTORY."Plugins/PHPLoadMeetingRoomRestrictions/JSLoadMeetingRoomRestrictionsPlugin.js"
                                                                                                  ),
                                                                                'Params' => array($CONF_ROOT_DIRECTORY."Plugins/PHPLoadMeetingRoomRestrictions/"),
                                                                                'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/PHPLoadMeetingRoomRestrictions/PHPLoadMeetingRoomRestrictionsStyles.css")
                                                                                              )
                                                                               )
                                              ),
                                 '/Common' => array(
                                                 'initCalandretaGUIPlugin' => array(
                                                                                'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/JSCalandretaGUIPlugin.js"),
                                                                                'Params' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/"),
                                                                                'Css' => array(
                                                                                                'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/JSCalandretaGUIPluginStyles.css")
                                                                                              )
                                                                            ),
                                                 'initCalandretaLogoutPlugin' => array(
                                                                                'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaLogoutPlugin/JSCalandretaLogoutPlugin.js"),
                                                                                'Params' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaLogoutPlugin/"),
                                                                                'Css' => array(
                                                                                                'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaLogoutPlugin/JSCalandretaLogoutPluginStyles.css")
                                                                                              )
                                                                            )
                                                 ),
                                 '/Plugins' => array(
                                                 'initCalandretaGUIPlugin' => array(
                                                                                'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/JSCalandretaGUIPlugin.js"),
                                                                                'Params' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/"),
                                                                                'Css' => array(
                                                                                                'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaGUI/JSCalandretaGUIPluginStyles.css")
                                                                                              )
                                                                            ),
                                                 'initCalandretaMainMenuPlugin' => array(
                                                                               'Scripts' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaMainMenuPlugin/JSCalandretaMainMenuPlugin.js"),
                                                                               'Params' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaMainMenuPlugin/"),
                                                                               'Css' => array(
                                                                                               'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSCalandretaMainMenuPlugin/JSCalandretaMainMenuPluginStyles.css")
                                                                                             )
                                                                            ),
                                                 'initStillActivePlugin' => array(
                                                                               'Scripts' => array(
                                                                                                  $CONF_ROOT_DIRECTORY."Common/JSPrototype/prototype.js",
                                                                                                  $CONF_ROOT_DIRECTORY."Plugins/JSStillActivePlugin/JSStillActivePlugin.js"
                                                                                                 ),
                                                                               'Params' => array(180),     // in seconds
                                                                               'Css' => array(
                                                                                              'screen' => array($CONF_ROOT_DIRECTORY."Plugins/JSStillActivePlugin/JSStillActivePluginStyles.css")
                                                                                             )
                                                                            )
                                                )
                                );
?>
