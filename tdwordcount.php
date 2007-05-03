<?php
/*
Plugin Name: TD Word Count
Plugin URI: http://www.tdscripts.com/wp/tdwordcount/
Description: Word count stats
Author: TDavid
Version: 0.4.3
Author URI: http://www.tdscripts.com/

Last update: 3/25/07 (2/5/07, 7/24/2006)

Functions:
tdwordcount_calc : recalcs stats on any new post published or edited

Copyright 2006-2007 TDavid @ tdscripts.com  (Plugins/Mods/Themes blog : tdscripts.com/wp/)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
$tdwordcount_version = 'v0.4.3'; // the current version

function tdwordcount_calc(){
global $wpdb, $table_prefix;

	$anames = $wpdb->get_results("SELECT ID, user_nicename FROM $table_prefix" . "users;");
	foreach($anames as $anum => $auser) {
		$author_names["$auser->ID"] = $auser->user_nicename;
	}

	$posts = $wpdb->get_results("SELECT ID, post_content, post_status, post_date, post_title, post_author, post_type FROM $table_prefix" . "posts ORDER BY ID;");
	$numposts_pub = 0;
	$numwords_pub = 0;

	$numposts_other = 0;
	$numwords_other = 0;

	$numposts_all = 0;
	$total_words = 0;

	$more_than_300 = 0;
	$max_words = 0;
	$cache_string_pub = '';
	$cache_string_other = '';

	$cache_string_pauthor = array(); // contains author id to match post
      $cache_string_pauthorp = array(); // contains posts by month [ID][022007]
      $cache_string_pauthorw = array(); // contains words by month [ID][022007]
	$cache_string_pauthorids = array(); // contains published posts IDs/dates by author

	foreach($posts as $num => $entry) {
		$numwords = str_word_count(strip_tags($entry->post_content));
                  
		// AUTHOR -- Multi-Author date breakdown for author
		$yparts = explode(" ",$entry->post_date);
		$yymmparts = explode("-",$yparts[0]);
            $yymm = $yymmparts[0] . $yymmparts[1]; // 012007, 022007, etc

		if($entry->post_status == "publish") {
			$numwords_pub += $numwords;
			$numposts_pub++;
			$cache_string_pub["$entry->ID"] = $numwords;
			$cache_string_ptitle["$entry->ID"] = $entry->post_title;
			$cache_string_pdate["$entry->ID"] = $entry->post_date;

                  // author v0.4
                  $cache_string_pauthor["$entry->ID"] = $entry->post_author;
                  $cache_string_pauthorp["$entry->post_author"]["$yymm"]++; // increase pub mo post++
                  $cache_string_pauthorw["$entry->post_author"]["$yymm"] += $numwords; // word count++
			$cache_string_pauthorids["$entry->post_author"]["$yymm"] .= $entry->ID . ',';

		} elseif ($entry->post_type != "attachment") {
			$numwords_other += $numwords;
			$numposts_other++;
			$cache_string_other["$entry->ID"] = $numwords;
			$cache_string_otitle["$entry->ID"] = $entry->post_title;
			$cache_string_odate["$entry->ID"] = $entry->post_date;
		}
		if($numwords > 299) {
			$more_than_299++;
		}

		if($numwords > $max_words) {
			$max_words = $numwords;
			$max_words_data = "$entry->ID|$numwords|$entry->post_date|$entry->post_title";
		}

	}

	$numwords_all = $numwords_pub + $numwords_other;
	$tdwordcount_arr = array(
		'more_than_299' => $more_than_299,
		'numposts_pub' => $numposts_pub,
		'numwords_pub' => $numwords_pub,
		'numposts_other' => $numposts_other,
		'numwords_other' => $numwords_other,
		'cache_string_pub' => $cache_string_pub,
		'cache_string_other' => $cache_string_other,
		'cache_string_ptitle' => $cache_string_ptitle,
		'cache_string_otitle' => $cache_string_otitle,
		'cache_string_pdate' => $cache_string_pdate,
		'cache_string_odate' => $cache_string_odate,

'author_names' => $author_names,
'cache_string_pauthor' => $cache_string_pauthor,
'cache_string_pauthorp' => $cache_string_pauthorp,
'cache_string_pauthorw' => $cache_string_pauthorw,
'cache_string_pauthorids' => $cache_string_pauthorids,

		'max_words' => $max_words_data,
		'total_words' => $numwords_all,
	);
	update_option('tdwordcount_data', $tdwordcount_arr);
}

function tdwordcount_menuadd() {
	if ( function_exists('add_submenu_page') )  {
		// add_submenu_page(parent, page_title, menu_title, access_level/capability, file, [function]);
		add_submenu_page('index.php', 'TD Word Count', 'Word Count', 1, basename(__FILE__), 'tdwordcount_adminmenu');
	}
}

function tdwordcount_adminmenu() {
global $tdwordcount_version;

$tdwordcount_data = get_option('tdwordcount_data');
@extract($tdwordcount_data);
echo('<div class="wrap">');
	echo '<h2>Word Count Stats ' . $tdwordcount_version . '</h2>';
	echo '<font color="green">+ ' . number_format($numwords_pub) . ' total <b>published</b> words from ' . number_format($numposts_pub) . ' posts</font><br />';
	echo '<font color="purple">+ ' . number_format($numwords_other) . ' total <i>unpublished</i> words from ' . number_format($numposts_other) . ' posts</font><br />';
	echo '--------------------------------------------------------<br />';
	echo '<b>= ' . number_format($total_words) . ' total all words from ' . number_format($numposts_pub+$numposts_other). ' posts</b><br />';
	echo '<b>= ' . floor($total_words / ($numposts_pub + $numposts_other)) . "</b> average words per post<br />";

	echo ' ' . number_format($more_than_299) . ' (' .  (sprintf("%02f.", ($more_than_299 / ($numposts_pub+$numposts_other))) * 100) . '%) posts contain 300+ words<br />';

	$max_parts = explode('|',$max_words);
	echo 'Max words for a single post: <b>' . $max_parts[1] . ' words</b> <a href="' . get_permalink($max_parts[0]) . '" "target=_blank">' .  $max_parts[3] . "</a> on $max_parts[2]<br /><br />";

	$ps = $_GET['pubstat'];
	$tdsortby = $_GET['sortby'];

	$tdauthorkey = $_GET['authid'];
	$tdauthormonth = $_GET['authormonth'];

	if($ps == 1) {
		echo '<b>Unpublished</b> '; 
		echo '<small><small><i><a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=$tdsortby&pubstat=0\">Published</a></i></small></small>"; 
	} else {
		echo '<b>Published</b> ';
		echo '<small><small><i><a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=$tdsortby&pubstat=1\">Unpublished</a></i></small></small>"; 
	}

	echo ' posts sorted by<br /> words: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . '<a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=&pubstat=$ps\">" . 'most</a> | ' . '<a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=1&pubstat=$ps\">"  . 'least</a>' 
		. ' <br /> title: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . '<a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=2&pubstat=$ps\">" . '(a-z)</a> | ' . '<a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=3&pubstat=$ps\">"  . '(z-a)</a>'
		. ' <br /> date: ' . '<a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=4&pubstat=$ps\">" . 'old to new</a> | ' . '<a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=5&pubstat=$ps\">"  . 'new to old</a><br /><br />';

	reset($cache_string_pub);
		if( isset($tdsortby) ){
			switch ($tdsortby){
				case 1:
					if($ps == 1) {
						asort($cache_string_other);
					} else {
						asort($cache_string_pub);
					}
					break;
				case 2:
					if($ps == 1) {
						asort($cache_string_otitle);
					} else {
						asort($cache_string_ptitle);
					}
					break;
				case 3:
					if($ps == 1) {
						arsort($cache_string_otitle);
					} else {
						arsort($cache_string_ptitle);
					}
					break;
				case 4:
					if($ps == 1) {
						ksort($cache_string_odate);
					} else {
						asort($cache_string_pdate);
					}
					break;
				case 5:
					if($ps == 1) {
						krsort($cache_string_odate);
					} else {
						arsort($cache_string_pdate);
					}
					break;
				default:
					if($ps == 1) {
						arsort($cache_string_other);
					} else {
						arsort($cache_string_pub);
					}
			}
		} else {
			// nothing set
			if($ps == 1) {
				arsort($cache_string_other);
			} else {
				arsort($cache_string_pub);
			}

		// end no sorting options
		}
      $tdrank = 1;
	if($tdsortby < 2) {
		if($ps == 1) {
			foreach($cache_string_other as $tdkey => $tdvalue) {
				echo number_format($tdvalue) . ' w <i>' . $cache_string_odate["$tdkey"] . "</i> - $tdrank " . '<a href="' . get_permalink($tdkey) . '" "target=_blank" alt="' . $tdkey . '">' . $cache_string_otitle["$tdkey"] . "</a><br />\n";
			$tdrank++;
			}
		} else {
			foreach($cache_string_pub as $tdkey => $tdvalue) {
				$author_ID = $cache_string_pauthor["$tdkey"];
				echo number_format($tdvalue) . ' w <i>' . $cache_string_pdate["$tdkey"] . "</i> - $tdrank " . '<a href="' . get_permalink($tdkey) . '" "target=_blank" alt="' . $tdkey . '">' . $cache_string_ptitle["$tdkey"] . "</a> <i>" . $author_names["$author_ID"] . "</i> <br />\n";
			$tdrank++;
			}
		}
	} elseif ($tdsortby == 2 or $tdsortby == 3) {
		$max_length = strlen($max_parts[1]);		
		if($ps == 1) {
			foreach($cache_string_otitle as $tdkey => $tdtitle) {
				echo str_replace(' ', '&nbsp;', str_pad(number_format($cache_string_other["$tdkey"]), $max_length, ' ', STR_PAD_LEFT)) . ' w <i>' . $cache_string_odate["$tdkey"] . "</i> - $tdrank " .  '<a href="' . get_permalink($tdkey) . '" "target=_blank" alt="#' . $tdkey . ' on ' . $cache_string_odate["$tdkey"] . '">' . $tdtitle . "</a><br />\n";
			$tdrank++;
			}
		} else {
			foreach($cache_string_ptitle as $tdkey => $tdtitle) {
				echo str_replace(' ', '&nbsp;', str_pad(number_format($cache_string_pub["$tdkey"]), $max_length, ' ', STR_PAD_LEFT)) . ' w <i>' . $cache_string_pdate["$tdkey"] . "</i> - $tdrank " .  '<a href="' . get_permalink($tdkey) . '" "target=_blank" alt="#' . $tdkey . ' on ' . $cache_string_pdate["$tdkey"] . '">' . $tdtitle . "</a><br />\n";
			$tdrank++;
			}
		}
	} else {
		$max_length = strlen($max_parts[1]) + 1;
		if($ps == 1) {
			foreach($cache_string_odate as $tdkey => $tddate) {
				echo str_replace(' ', '&nbsp;', str_pad(number_format($cache_string_other["$tdkey"]), $max_length, ' ', STR_PAD_LEFT)) . " w <i>$tddate</i> - $tdrank " .  '<a href="' . get_permalink($tdkey) . '" "target=_blank" alt="#' . $tdkey . '">' . $cache_string_otitle["$tdkey"] . "</a><br />\n";
			$tdrank++;
			}
		} else {
			foreach($cache_string_pdate as $tdkey => $tddate) {
				echo str_replace(' ', '&nbsp;', str_pad(number_format($cache_string_pub["$tdkey"]), $max_length, ' ', STR_PAD_LEFT)) . " w <i>$tddate</i> - $tdrank " .  '<a href="' . get_permalink($tdkey) . '" "target=_blank" alt="#' . $tdkey . '">' . $cache_string_ptitle["$tdkey"] . "</a><br />\n";
			$tdrank++;
			}
		}
	}

echo '<br /><h2>Author Published Post Stats</h2>';

if($tdauthorkey AND $tdauthormonth != "") {
	// valid month and author?
	if($cache_string_pauthorp["$tdauthorkey"]["$tdauthormonth"] != "") {
		$tdpostids = explode(",", $cache_string_pauthorids["$tdauthorkey"]["$tdauthormonth"]);
		$tdrank = 1;
		echo "<font color='green'><b>" . (count($tdpostids)-1) . '</b></font> published posts by <b>' . $author_names["$tdauthorkey"] . "</b> for month: <b>$tdauthormonth</b><br />";
		foreach($tdpostids as $tdeach) {
			if($tdeach != "") {
				$tdtitle = $cache_string_ptitle["$tdeach"];
				echo "[#$tdrank] " . str_replace(' ', '&nbsp;', str_pad(number_format($cache_string_pub["$tdeach"]), $max_length, ' ', STR_PAD_LEFT)) . ' w <a href="' . get_permalink($tdeach) . '" "target=_blank" alt="#' . $tdeach . '">' . $tdtitle . "</a><br />";
				$tdrank++;
			}
		}
	echo '<br />';
	}
}

reset($cache_string_pauthorp);
foreach($author_names as $authorkey => $authorname) {
	$author_total_posts = 0;
	$author_total_words = 0;
	$toshow = '';
	if(!empty($cache_string_pauthorp["$authorkey"])) {
		foreach($cache_string_pauthorp["$authorkey"] as $authmonth => $authposts) {
			$author_total_posts += $authposts;
			$authorwords = $cache_string_pauthorw["$authorkey"]["$authmonth"];
			$author_total_words += $authorwords;
			$toshow .= "$authmonth - " . '<a href="' . $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__) . "&sortby=$tdsortby&pubstat=&authid=$authorkey&authormonth=$authmonth\">$authposts</a> posts " . number_format($authorwords) . " words<br />";
		}
	}
	if($author_total_words > 0) {
		// only show active authors
		echo "<b>$authorname</b><br />$toshow";			
		echo "<br /><font color='green'><b>" . number_format($author_total_posts) . "</b></font> total published posts<br /><font color='green'><b>" . number_format($author_total_words) . "</b></font> total published words";
		echo '<br /><br />';
	}
}

echo('<div>');
}

add_action('publish_post', 'tdwordcount_calc');
add_action('edit_post', 'tdwordcount_calc');
add_action('admin_menu', 'tdwordcount_menuadd');
?>