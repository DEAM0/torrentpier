<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2017 TorrentPier (https://torrentpier.me)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

if (!defined('BB_ROOT')) {
    die(basename(__FILE__));
}

require_once INC_DIR . '/functions_admin.php';

if ($bb_cfg['prune_enable']) {
    $sql = "SELECT forum_id, prune_days FROM " . BB_FORUMS . " WHERE prune_days != 0";

    foreach (DB()->fetch_rowset($sql) as $row) {
        topic_delete('prune', $row['forum_id'], (TIMENOW - 86400 * $row['prune_days']));
    }
}
