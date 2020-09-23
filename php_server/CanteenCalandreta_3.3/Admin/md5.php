<?php
/* This file is part of ASTRES.
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


if (isSet($_POST["pwd"]))
{
    $Value = md5($_POST["pwd"]);
}
else
{
    $Value = "";
}
?>

<html>
<head>
<title>Login / Password</title>
</head>
<body>
<form name="Pwd" action="md5.php" method="post">
Login or Password : <input name="pwd" type="text" size="35" value="<?php echo $Value ?>">
<br /><br />
<input type="submit" name="submit" value="submit">&nbsp;&nbsp;<input type="reset" name="reset" value="reset">
</form>
</body>
</html>