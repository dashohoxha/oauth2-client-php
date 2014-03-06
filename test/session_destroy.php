<?php
// while testing, sometimes it is useful to cleanup the session
session_start();
session_destroy();

highlight_file(__FILE__);
