<?php

define('MM_FILES_DIR', DATA_DIR . '/files/mailmagik/files/');
define('MM_TMP_DIR', DATA_DIR . '/files/mailmagik/tmp/');
define('MM_PERM', 0755);

define('CMD_FETCHMAIL', 'mailmagik:fetchmail');

define('KEY_PREFIX', 'metamagikkey_');

define('CMD_TASKMAILNOTIFY', 'mailmagik:notify');
define('EVENT_TASKMAILNOTIFY', 'task.email');

define('DEFAULT_ENCODING', 'UTF-8');
