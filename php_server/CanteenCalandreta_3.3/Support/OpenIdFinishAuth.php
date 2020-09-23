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
 * Support module : end OpenId auth for the support module
 *
 * @author STNA/7SQ
 * @version 2.5
 * @since 2008-02-04
 */

require_once "../Common/Config.php";
require_once "OpenIdCommon.php";
session_start();

function run() {
    $consumer = getConsumer();

    // Complete the authentication process using the server's
    // response.
    $return_to = getReturnTo();
    $response = $consumer->complete($return_to);

    // Check the response status.
    if ($response->status == Auth_OpenID_CANCEL) {
        // This means the authentication was cancelled.

    } else if ($response->status == Auth_OpenID_FAILURE) {
        // Authentication failed; display the error message.

    } else if ($response->status == Auth_OpenID_SUCCESS) {
        // This means the authentication succeeded; extract the
        // identity URL and Simple Registration data (if it was
        // returned).
        $openid = $response->getDisplayIdentifier();
        $esc_identity = htmlspecialchars($openid, ENT_QUOTES);

        $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
        $sreg = $sreg_resp->contents();

        $pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);
    }

    $_SESSION["OpenIdResponse"] = serialize($response);

    header("location: index.php");
}

run();

?>
