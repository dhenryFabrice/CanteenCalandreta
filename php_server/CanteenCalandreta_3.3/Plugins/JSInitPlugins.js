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
 * JS plugin module : functions used to manage JavaScript plugins
 *
 * @author STNA/7SQ
 * @version 2.5
 * @since 2008-02-07
 */


/**
 * Function used to init Javascript plugins
 *
 * @author STNA/7SQ
 * @version 1.0
 * @since 2008-02-07
 *
 * @param PluginToInit         String     Name of the init function of the plugin
 *                                        to launch to init the plugin
 */
 function initPlugins(PluginToInit)
 {
     if (arguments.length > 0)
     {
         for(var i = 0, ArrayPluginsToInit = [], length = arguments.length; i < length; i++)
         {
             ArrayPluginsToInit.push(arguments[i]);
             eval(arguments[i]);
         }
     }
 }
