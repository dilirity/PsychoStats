<?php
/**
 *	This file is part of PsychoStats.
 *
 *	Written by Jason Morriss
 *	Copyright 2008 Jason Morriss
 *
 *	PsychoStats is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	PsychoStats is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with PsychoStats.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	Version: $Id: clans.php 450 2008-05-20 11:34:52Z lifo $
 */

define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('PsychoStats - Clan Stats');

// Check to see if there is any data in the database before we continue.
$cmd = "SELECT * FROM $ps->t_plr_data LIMIT 1";

$results = array();
$results = $ps->db->fetch_rows(1, $cmd);

// if $results is empty then we have no data in the database
if (empty($results)) {
	$cms->full_page_err('awards', array(
		'message_title'	=> $cms->trans("No Stats Found"),
		'message'	=> $cms->trans("You must run stats.pl before you will see any stats."),
	));
	exit();
}
unset ($results);

// change this if you want the default sort of the clan listing to be something else like 'kills'
$DEFAULT_SORT = 'skill';

// collect url parameters ...
$validfields = array('sort','order','start','limit','xml');
$cms->theme->assign_request_vars($validfields, true);

$sort = trim(strtolower($sort));
$order = trim(strtolower($order));
if (!preg_match('/^\w+$/', $sort)) $sort = $DEFAULT_SORT;
if (!in_array($order, array('asc','desc'))) $order = 'desc';
if (!is_numeric($start) || $start < 0) $start = 0;
if (!is_numeric($limit) || $limit < 0 || $limit > 500) $limit = 100;

// fetch stats, etc...
$totalclans  = $ps->get_total_clans(array('allowall' => 1));
$totalranked = $ps->get_total_clans(array('allowall' => 0));

$clans = $ps->get_clan_list(array(
	'sort'		=> $sort,
	'order'		=> $order,
	'start'		=> $start,
	'limit'		=> $limit,
//	'fields'	=> "kills,deaths,killsperdeath",
));

$pager = pagination(array(
	'baseurl'	=> ps_url_wrapper(array('limit' => $limit, 'sort' => $sort, 'order' => $order)),
	'total'		=> $totalranked,
	'start'		=> $start,
	'perpage'	=> $limit,
	'separator'	=> ' ', 
	'force_prev_next' => true,
        'next'          => $cms->trans("Next"),
        'prev'          => $cms->trans("Previous"),
));

// build a dynamic table that plugins can use to add custom columns of data
$table = $cms->new_table($clans);
$table->if_no_data($cms->trans("No Clans Found"));
$table->attr('class', 'ps-table ps-clan-table');
$table->start_and_sort($start, $sort, $order);
$table->columns(array(
	'+'			=> '#',
	'clantag'		=> array( 'label' => $cms->trans("Clan Tag"), 'callback' => 'ps_table_clan_link' ), 
	'name'			=> array( 'label' => $cms->trans("Clan Name"), 'callback' => 'ps_table_clan_link2' ),
	'totalmembers'		=> array( 'label' => $cms->trans("Members"), 'modifier' => 'commify' ),
	'kills'			=> array( 'label' => $cms->trans("Kills"), 'modifier' => 'commify' ),
	'deaths'		=> array( 'label' => $cms->trans("Deaths"), 'modifier' => 'commify' ),
	'killsperdeath' 	=> array( 'label' => $cms->trans("K:D"), 'tooltip' => $cms->trans("Kills Per Death") ),
	'headshotkills'		=> array( 'label' => $cms->trans("HS"), 'modifier' => 'commify', 'tooltip' => $cms->trans("Headshot Kills") ),
	'headshotkillspct'	=> array( 'label' => $cms->trans("HS%"), 'modifier' => '%s%%', 'tooltip' => $cms->trans("Headshot Kills Percentage") ),
	'activity'		=> array( 'label' => $cms->trans("Activity"), 'modifier' => 'activity_bar' ),
	'skill'			=> $cms->trans("Skill")
));
$table->column_attr('clantag', 'class', 'left');
$table->column_attr('name', 'class', 'left');
$ps->clans_table_mod($table);
$cms->filter('clans_table_object', $table);


$cms->theme->assign(array(
	'clans'		=> $clans,
	'clans_table'	=> $table->render(),
	'pager'		=> $pager,
	'totalclans'	=> $totalclans,
	'totalranked' 	=> $totalranked,
));


// display the output
$basename = basename(__FILE__, '.php');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

function activity_bar($pct) {
	$out = pct_bar(array( 'pct' => $pct ));
	return $out;
}

function ps_table_clan_link2($name, $clan) {
	return ps_table_clan_link($name, $clan, false, false);
}


?>
