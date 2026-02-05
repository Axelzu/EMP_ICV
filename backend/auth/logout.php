<?php
session_start();
session_destroy();

header("Location: /ICV/index.php");
exit;
