<?php

/*
 *  Redirects anyone viewing this directory to the proper directory.
 *
 */

define(DIR_MAIN, "https://safe.nrao.edu/php/ntc/orr/Rids.php");

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \r\t\t\t\"http://www.w3.org/TR/html4/loose.dtd\">\r";
echo "<HTML>\r";
echo "<HEAD>\r";
echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=" . DIR_MAIN . "\">\r";
echo "</HEAD>\r";
echo "</HTML>\r";

?>
