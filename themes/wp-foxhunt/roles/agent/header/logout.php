<?php
wp_logout ();
header ('Location:' . FH_Theme::HOME, 303);
exit (1);
?>
