<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008-2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

define('STATUSNET_BASE_VERSION', '1.0.1');
define('STATUSNET_LIFECYCLE', 'rc1'); // 'dev', 'alpha[0-9]+', 'beta[0-9]+', 'rc[0-9]+', 'release'
define('STATUSNET_VERSION', STATUSNET_BASE_VERSION . STATUSNET_LIFECYCLE);

define('LACONICA_VERSION', STATUSNET_VERSION); // compatibility

define('STATUSNET_CODENAME', 'Get It Together');

define('AVATAR_PROFILE_SIZE', 96);
define('AVATAR_STREAM_SIZE', 48);
define('AVATAR_MINI_SIZE', 24);

define('NOTICES_PER_PAGE', 20);
define('PROFILES_PER_PAGE', 20);
define('MESSAGES_PER_PAGE', 20);

define('FOREIGN_NOTICE_SEND', 1);
define('FOREIGN_NOTICE_RECV', 2);
define('FOREIGN_NOTICE_SEND_REPLY', 4);

define('FOREIGN_FRIEND_SEND', 1);
define('FOREIGN_FRIEND_RECV', 2);

define('NOTICE_INBOX_SOURCE_SUB', 1);
define('NOTICE_INBOX_SOURCE_GROUP', 2);
define('NOTICE_INBOX_SOURCE_REPLY', 3);
define('NOTICE_INBOX_SOURCE_FORWARD', 4);
define('NOTICE_INBOX_SOURCE_PROFILE_TAG', 5);
define('NOTICE_INBOX_SOURCE_GATEWAY', -1);

// append our extlib dir as the last-resort place to find libs

set_include_path(get_include_path() . PATH_SEPARATOR . INSTALLDIR . '/extlib/');

// To protect against upstream libraries which haven't updated
// for PHP 5.3 where dl() function may not be present...
if (!function_exists('dl')) {
    // function_exists() returns false for things in disable_functions,
    // but they still exist and we'll die if we try to redefine them.
    //
    // Fortunately trying to call the disabled one will only trigger
    // a warning, not a fatal, so it's safe to leave it for our case.
    // Callers will be suppressing warnings anyway.
    $disabled = array_filter(array_map('trim', explode(',', ini_get('disable_functions'))));
    if (!in_array('dl', $disabled)) {
        function dl($library) {
            return false;
        }
    }
}

// global configuration object

require_once('PEAR.php');
require_once('PEAR/Exception.php');
require_once('DB/DataObject.php');
require_once('DB/DataObject/Cast.php'); # for dates

require_once(INSTALLDIR.'/lib/language.php');

// This gets included before the config file, so that admin code and plugins
// can use it

require_once(INSTALLDIR.'/lib/event.php');
require_once(INSTALLDIR.'/lib/plugin.php');

function addPlugin($name, $attrs = null)
{
    return StatusNet::addPlugin($name, $attrs);
}

function _have_config()
{
    return StatusNet::haveConfig();
}

function __autoload($cls)
{
    if (file_exists(INSTALLDIR.'/classes/' . $cls . '.php')) {
        require_once(INSTALLDIR.'/classes/' . $cls . '.php');
    } else if (file_exists(INSTALLDIR.'/lib/' . strtolower($cls) . '.php')) {
        require_once(INSTALLDIR.'/lib/' . strtolower($cls) . '.php');
    } else if (mb_substr($cls, -6) == 'Action' &&
               file_exists(INSTALLDIR.'/actions/' . strtolower(mb_substr($cls, 0, -6)) . '.php')) {
        require_once(INSTALLDIR.'/actions/' . strtolower(mb_substr($cls, 0, -6)) . '.php');
    } else if ($cls == 'OAuthRequest') {
        require_once('OAuth.php');
    } else {
        Event::handle('Autoload', array(&$cls));
    }
}

// XXX: how many of these could be auto-loaded on use?
// XXX: note that these files should not use config options
// at compile time since DB config options are not yet loaded.

require_once 'Validate.php';
require_once 'markdown.php';

// XXX: other formats here

/**
 * Avoid the NICKNAME_FMT constant; use the Nickname class instead.
 *
 * Nickname::DISPLAY_FMT is more suitable for inserting into regexes;
 * note that it includes the [] and repeating bits, so should be wrapped
 * directly in a capture paren usually.
 *
 * For validation, use Nickname::normalize(), Nickname::isValid() etc.
 *
 * @deprecated
 */
define('NICKNAME_FMT', VALIDATE_NUM.VALIDATE_ALPHA_LOWER);

require_once INSTALLDIR.'/lib/util.php';
require_once INSTALLDIR.'/lib/action.php';
require_once INSTALLDIR.'/lib/mail.php';
require_once INSTALLDIR.'/lib/subs.php';

require_once INSTALLDIR.'/lib/clientexception.php';
require_once INSTALLDIR.'/lib/serverexception.php';


//set PEAR error handling to use regular PHP exceptions
function PEAR_ErrorToPEAR_Exception($err)
{
    //DB_DataObject throws error when an empty set would be returned
    //That behavior is weird, and not how the rest of StatusNet works.
    //So just ignore those errors.
    if ($err->getCode() == DB_DATAOBJECT_ERROR_NODATA) {
        return;
    }

    $msg      = $err->getMessage();
    $userInfo = $err->getUserInfo();

    // Log this; push the message up as an exception

    common_log(LOG_ERR, "PEAR Error: $msg ($userInfo)");

    if ($err->getCode()) {
        throw new PEAR_Exception($msg, $err, $err->getCode());
    } else {
        throw new PEAR_Exception($msg, $err);
    }
}

PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'PEAR_ErrorToPEAR_Exception');
