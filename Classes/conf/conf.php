<?php
/*
 * Development / Production environment
 */
define ('POSTBOX_ENV', 'development');

/*
 * URL used to retrieve successfully parsed DwC-A file
 */
define('BASE_URL', 'http://postbox.local');

/*
 * If this will be a remote worker, you will need to mount these remote directories
 */
define('UPLOAD_PATH', '/Users/dshorthouse/Sites/BSCPostBox/upload_queue');
define('DOWNLOAD_PATH', '/Users/dshorthouse/Sites/BSCPostBox/download');
define('FAILED_PATH', '/Users/dshorthouse/Sites/BSCPostBox/failed');

/*
 * Worker log path
 */
define('LOG_PATH', '/Users/dshorthouse/Sites/BSCPostBox/log');

/*
 * Location of worker bash
 */
define('POSTBOX_BASH', '/Users/dshorthouse/Sites/BSCPostBox/run.sh');

/*
 * Memory limit for worker process in MB. Above the threshold, jobs get kicked out
 */
define('POSTBOX_MEMORY', '100');

/*
 * Row limit in classification sheet above which the job will get kicked out
 */
define('POSTBOX_CLASSIFICATION_ROWLIMIT', '10000');

/*
 * Set sys. admin email address as well as success or fail email bodies
 */
define('ADMIN_EMAIL', "davidpshorthouse@gmail.com");
define('SYSTEM_EMAIL', "postbox@biologicalsurvey.ca");
define('SYSTEM_NAME', "BSC PostBox");
define('SYSTEM_SUBJECT', "Biological Survey of Canada PostBox submission");
define('QUEUE_MESSAGE', "Dear Biological Survey of Canada PostBox user,\n\nYour submission has been added to the queue for processing. You may check its status at the web address below this message. Otherwise, you will soon receive a second email message containing your Darwin Core archive file as an attachment if processing was successful or a list of reasons why processing may not have been successful.\n\nThanks,\n\nThe Biological Survey of Canada\n\n");
define('SUCCESS_MESSAGE', "Dear Biological Survey of Canada PostBox user,\n\nAttached is a Darwin Core Archive file containing the result of your submission.\n\nIf you did not include a UUID in your Excel file template, one was created for you and included in a new Excel file within your archive. If you intend to re-upload your checklist thus creating a new version of what was originally submitted, please use the new Excel file in your archive.\n\nThanks,\n\nThe Biological Survey of Canada");
define('FAILED_MESSAGE', "Dear Biological Survey of Canada PostBox user,\n\nUnfortunately, your Excel file was not successfully parsed and we attached it to this message for your reference. The parsing errors we found are outlined at the bottom of this note.\n\nThe Biological Survey of Canada\n\n");

/*
 * Resque config
 * If this will be a remote worker, adjust the IP address to point to single beanstalkd instance
 */
define('RESQUE_ADDRESS', '127.0.0.1');
define('RESQUE_PORT', '6379');
define('RESQUE_TUBE', 'postbox');
define('RESQUE_TUBE_FAILED', 'postbox_failed');

define('SMTP_HOST', '10.19.19.213'); //University of Alberta SMTP server is smtp.srv.ualberta.ca
define('SMTP_HOST_ALT', '10.19.19.213');

define('GNACLR_URL', 'http://gnaclr.globalnames.org/classifications');

//set the locale
setlocale(LC_ALL, 'en_US.utf8');

//set the default timezone
date_default_timezone_set('America/New_York');

//alter php.ini settings for PHPExcel memory footprint, filesizes and Mac/Windows line endings
ini_set('memory_limit','1000M');
ini_set('post_max_size', '30M');
ini_set('upload_max_filesize', '30M');
ini_set("auto_detect_line_endings", 1);

set_time_limit(0);

?>