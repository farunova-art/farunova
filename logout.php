<?php

include("connection.php");

session_destroy();

header("location: login.php");
