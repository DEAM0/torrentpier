<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2017 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

define('BB_SCRIPT', 'feed');
define('BB_ROOT', './');
require __DIR__ . '/common.php';

$user->session_start(array('req_login' => true));

$mode = $_REQUEST['mode'] ?? '';
$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? 0;
$timecheck = TIMENOW - 600;

if (!$mode) {
    bb_simple_die(trans('messages.ATOM_NO_MODE'));
}

if ($mode == 'get_feed_url' && ($type == 'f' || $type == 'u') && $id >= 0) {
    if ($type == 'f') {
        // Check if the user has actually sent a forum ID
        $sql = "SELECT allow_reg_tracker, forum_name FROM " . BB_FORUMS . " WHERE forum_id = $id LIMIT 1";
        if (!$forum_data = OLD_DB()->fetch_row($sql)) {
            if ($id == 0) {
                $forum_data = array();
            } else {
                bb_simple_die(trans('messages.ATOM_ERROR') . ' #1');
            }
        }
        if (file_exists(config('tp.atom.path') . '/f/' . $id . '.atom') && filemtime(config('tp.atom.path') . '/f/' . $id . '.atom') > $timecheck) {
            redirectToUrl(config('tp.atom.url') . '/f/' . $id . '.atom');
        } else {
            require_once INC_DIR . '/functions_atom.php';
            if (update_forum_feed($id, $forum_data)) {
                redirectToUrl(config('tp.atom.url') . '/f/' . $id . '.atom');
            } else {
                bb_simple_die(trans('messages.ATOM_NO_FORUM'));
            }
        }
    }
    if ($type == 'u') {
        // Check if the user has actually sent a user ID
        if ($id < 1) {
            bb_simple_die(trans('messages.ATOM_ERROR') . ' #2');
        }
        if (!$username = get_username($id)) {
            bb_simple_die(trans('messages.ATOM_ERROR') . ' #3');
        }
        if (file_exists(config('tp.atom.path') . '/u/' . floor($id / 5000) . '/' . ($id % 100) . '/' . $id . '.atom') && filemtime(config('tp.atom.path') . '/u/' . floor($id / 5000) . '/' . ($id % 100) . '/' . $id . '.atom') > $timecheck) {
            redirectToUrl(config('tp.atom.url') . '/u/' . floor($id / 5000) . '/' . ($id % 100) . '/' . $id . '.atom');
        } else {
            require_once INC_DIR . '/functions_atom.php';
            if (update_user_feed($id, $username)) {
                redirectToUrl(config('tp.atom.url') . '/u/' . floor($id / 5000) . '/' . ($id % 100) . '/' . $id . '.atom');
            } else {
                bb_simple_die(trans('messages.ATOM_NO_USER'));
            }
        }
    }
} else {
    bb_simple_die(trans('messages.ATOM_ERROR') . ' #4');
}
