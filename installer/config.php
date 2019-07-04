<?php
/*
  Edited: Feb 7, 2019, RM.
*/
/**
 * Path
 */
define("FF_DISK_PATH", '/data/www/hihealth.hcore.app');
define("FF_SITE_PATH", '');
define("SITE_UPDIR", '/uploads');
define("DISK_UPDIR", '/data/www/hihealth.hcore.app/uploads');

/**
 * Session
 */
define("SESSION_SAVE_PATH", '/tmp');
define("SESSION_NAME", 'hihealth');
define("MOD_SECURITY_SESSION_PERMANENT", false);

/**
 * Database
define("DB_CHARACTER_SET"                           , 'utf8');
define("DB_COLLATION"                               , 'utf8_unicode_ci');

/**
 * Database Mysql
 */
define("FF_DATABASE_HOST", 'localhost');
define("FF_DATABASE_NAME", 'hihealth');
define("FF_DATABASE_USER", 'hihealth');
define("FF_DATABASE_PASSWORD", 'ReV3_UV~=_K%W]8!');

/**
 * Database Mongo
 */
define("MONGO_DATABASE_HOST", 'localhost');
define("MONGO_DATABASE_NAME", '');
define("MONGO_DATABASE_USER", '');
define("MONGO_DATABASE_PASSWORD", '');

/**
 * Trace
 */
define("TRACE_TABLE_NAME", '');
define("TRACE_ONESIGNAL_APP_ID", '');
define("TRACE_ONESIGNAL_API_KEY", '');

/**
 * Trace Database Mysql
 */
define("TRACE_DATABASE_HOST", 'localhost');
define("TRACE_DATABASE_NAME", '');
define("TRACE_DATABASE_USER", '');
define("TRACE_DATABASE_PASSWORD", '');

/**
 * Trace Database Mongo
 */
define("TRACE_MONGO_DATABASE_HOST", 'localhost');
define("TRACE_MONGO_DATABASE_NAME", '');
define("TRACE_MONGO_DATABASE_USER", '');
define("TRACE_MONGO_DATABASE_PASSWORD", '');

/**
 * Notifier
 */
define("NOTIFY_TABLE_NAME", '');
define("NOTIFY_TABLE_KEY", '');
define("NOTIFY_ONESIGNAL_APP_ID", '');
define("NOTIFY_ONESIGNAL_API_KEY", '');

/**
 * Notifier Database Mysql
 */
define("NOTIFY_DATABASE_HOST", 'localhost');
define("NOTIFY_DATABASE_NAME", '');
define("NOTIFY_DATABASE_USER", '');
define("NOTIFY_DATABASE_PASSWORD", '');

/**
 * Notifier Database Mongo
 */
define("NOTIFY_MONGO_DATABASE_HOST", 'localhost');
define("NOTIFY_MONGO_DATABASE_NAME", '');
define("NOTIFY_MONGO_DATABASE_USER", '');
define("NOTIFY_MONGO_DATABASE_PASSWORD", '');

/**
 * Database Mysql
 */
define("ANAGRAPH_DATABASE_HOST", 'localhost');
define("ANAGRAPH_DATABASE_NAME", 'hihealth');
define("ANAGRAPH_DATABASE_USER", 'hihealth');
define("ANAGRAPH_DATABASE_PASSWORD", "ReV3_UV~=_K%W]8!");

/**
 * Email SMTP
 */

define("A_SMTP_HOST", 'smtp.eu.sparkpostmail.com');
define("SMTP_AUTH", true);
define("A_SMTP_USER", 'SMTP_Injection');
define("A_SMTP_PASSWORD", 'bc4320da6cda3495497dc08caac4e699791db440');
define("A_SMTP_PORT", '587');
define("A_SMTP_SECURE", 'tls');

/**
 * Email Settings
 */
define("A_FROM_EMAIL", 'hello@hi.health');
define("A_FROM_NAME", 'hi.health');
define("CC_FROM_EMAIL", '');
define("CC_FROM_NAME", '');
define("BCC_FROM_EMAIL", '');
define("BCC_FROM_NAME", '');

/**
 * Superadmin
 */
define("SUPERADMIN_USERNAME", 'admin');
define("SUPERADMIN_PASSWORD", 'hihealth');

/**
 * Auth Apachee
 */
define("AUTH_USERNAME", '');
define("AUTH_PASSWORD", '');

/**
 * FTP
 */
define("FTP_USERNAME", '');
define("FTP_PASSWORD", '');
define("FTP_PATH", '/');

/**
 * Debug
 */
define("DEBUG_MODE", false);
define("DEBUG_PROFILING", true);
define("DEBUG_LOG", true);

/**
 * Site Settings
 */
define("CACHE_LAST_VERSION", '');

define("CM_LOCAL_APP_NAME", 'hi.health');
define("APPID", 'hihealth');

define("TRACE_VISITOR", false);

/**
 * Theme Frontend
 */
define("FRAMEWORK_CSS", 'foundation');
define("FONT_ICON", 'fontawesome');
define("LANGUAGE_DEFAULT", 'ENG');
define("LANGUAGE_DEFAULT_ID", '2');
define("LOGO_FAVICON", '');
define("LOGO_EMAIL", '');


/**
 * Theme Admin
 */
define("ADMIN_THEME", 'admin');
define("FRAMEWORK_CSS_RESTRICTED", 'foundation');
define("FONT_ICON_RESTRICTED", 'fontawesome');
define("LANGUAGE_RESTRICTED_DEFAULT", 'ENG');
define("LANGUAGE_RESTRICTED_DEFAULT_ID", '2');
define("LOGO_BRAND", '');
define("LOGO_DOCS", '');


/**
 * Server Settings
 */
define("MEMORY_LIMIT", '96M');
define("SERVICE_TIME_LIMIT", '');
define("TIMEZONE", 'Europe/Rome');
define("SECURITY_SHIELD", false);

define("PHP_EXT_MEMCACHE", false);
define("PHP_EXT_APC", false);
define("PHP_EXT_JSON", true);
define("PHP_EXT_GD", true);
define("APACHE_MODULE_EXPIRES", false);
define("MYSQLI_EXTENSIONS", true);

define("AUTH_SECURITY_LEVEL", 3);
