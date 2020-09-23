<?php
/* Copyright (C) 2007  STNA/7SQ (IVDS)
 *
 * This file is part of ASTRES.
 *
 * ASTRES is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ASTRES is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ASTRES; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Web services module : define the order of arguments in some functions
 *
 * @author STNA/7SQ
 * @version 3.6
 * @since 2011-03-01
 */


 $WS_ARGS_FUNCTIONS = array(
                            'dbAddAow' => array(
                                                'AowRef', 'AowSubject', 'AowDescription', 'AowTypeID', 'ProjectID', 'AowimpactedFieldID',
                                                'AowActivityID', 'AowWishedDeadline', 'AowDeadline', 'AowPlatform', 'AowCriticity',
                                                'AowMain', 'AowAnalysis', 'AowComment', 'AowAnswer', 'AowSysTime', 'AowConfTime',
                                                'AowStudyTime', 'AowDocTime', 'AowModifConf', 'AowTreeLevel', 'CustomerID',
                                                'SupporterMemberID', 'AowPreviousID', 'AowSplittedID', 'HistorySupporterID',
                                                'AowCustomersInCopy'
                                               ),
                            'dbAddConstructorCall' => array(
                                                'Ref', 'ConstructorCallExternalRef', 'ConstructorCallSubject', 'ConstructorCallDate',
                                                'ConstructorCallPlatform', 'ConstructorCallType', 'ConstructorCallCriticity',
                                                'ConstructorCallDescription', 'SupportMemberID', 'ConstructorID', 'ProjectID',
                                                'ConstructorCallComment', 'ConstructorCallAnswer', 'ConstructorCallClosingDate', 'AowID'
                                               )
                           );
?>
