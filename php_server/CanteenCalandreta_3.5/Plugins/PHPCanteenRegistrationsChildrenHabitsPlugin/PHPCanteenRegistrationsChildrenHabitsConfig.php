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
 * PHP plugin canteen registrations children habits module : defined constants and parameters of this plugin
 *
 * @author Christophe Javouhey
 * @version 1.0
 * @since 2014-06-10
 */


 switch($CONF_LANG)
 {
     case 'fr':
         include_once('./Languages/PHPCanteenRegistrationsChildrenHabitsFrancais.lang.php');
         break;

     case 'oc':
         include_once('./Languages/PHPCanteenRegistrationsChildrenHabitsOccitan.lang.php');
         break;

     default:
         include_once('./Languages/PPHPCanteenRegistrationsChildrenHabitsEnglish.lang.php');
         break;
 }


 //########################### Constants #########################
 define('HABIT_TYPE_1_WEEK', 1);
 define('HABIT_TYPE_4_WEEKS', 4);

 //########################### Parameters ########################
 // Database and tables used to store data of the plugin
 $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_DB    = 'CantineProd';
 $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_TABLE = 'CanteenRegistrationsChildrenHabits';

 // Parameters of the analysis of families' habits
 $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_LIMIT_ANALYSIS_DATE = date('Y-m-01', strtotime('6 Months ago'));
 $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_MIN_RATE_TO_KEEP    = 30;  // In % (ex : 30 = 30%) : min rate to keep a profil

 // Parameters to send e-mails
 $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_EMAIL_TEMPLATES_DIRECTORY_HDD = dirname(__FILE__).'/Templates/';
 $CONF_CANTEEN_REGISTRATIONS_CHILDREN_HABITS_NOTIFICATIONS = array('NoConformity' => 'EmailNoConformity');
?>
