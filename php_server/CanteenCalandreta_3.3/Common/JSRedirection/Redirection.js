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
 * Common module : function used to do redirections
 *
 * @author STNA/7SQ
 * @version 1.1
 * @since 2004-04-14
 */


/**
 * Function used to do a time-lag redirection to a web page
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2004-04-14
 *
 * @param Url       String       URL of the redirection
 * @param Time      Integer      Time-lag in seconds
 */
 function Redirection(Url, Time)
 {
     if (Url != "")
     {
         setTimeout('location="' + Url + '"', Time * 1000);
     }
 }