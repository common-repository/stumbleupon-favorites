<?php
/*
Plugin Name: StumbleUpon Favorites
Plugin URI: http://www.argee.org/
Description: This plugin creates a widget listing the most recent stumbled upon pages you liked.
Version: 1.0
Author: Rohit Garg
Author URI: http://www.argee.org/
*/

/*  Copyright 2008  Rohit Garg  (email : RG@mail.rit.edu)

    This program is free software; you can redistribute it and/or modify
    it with attribution under the terms of the GNU General Public License as
    published by the Free Software Foundation; either version 2 of the License,
    or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//The plugin begins:
function widgets_stumblefave($args, $widget_args = 1) {
	extract($args, EXTR_SKIP);
	if ( is_numeric($widget_args) )
		$widget_args = array( 'number' => $widegt_args );
	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
	extract($widget_args, EXTR_SKIP);

	$options = get_option('widgets_stumblefave');

	if ( !isset($options[$number]) )
		return;

	if ( isset($options[$number]['error']) && $options[$number]['error'] )
		return;

	$url = $options[$number]['url'];
	while ( strstr($url, 'http') != $url )
		$url = substr($url, 1);
	if ( empty($url) )
		return;

	require_once(ABSPATH . WPINC . '/rss.php');

	$rss = fetch_rss($url);
	$link = clean_url(strip_tags($rss->channel['link']));
	while ( strstr($link, 'http') != $link )
		$link = substr($link, 1);
	$desc = attribute_escape(strip_tags(html_entity_decode($rss->channel['description'], ENT_QUOTES)));
	$title = $options[$number]['title'];
	$logobool = (int) $options[$number]['show_logo'];

	if ( empty($title) )
		$title = htmlentities(strip_tags($rss->channel['title']));
	if ( empty($title) )
		$title = $desc;
	if ( empty($title) )
		$title = __('Unknown Feed');
	$url = clean_url(strip_tags($url));
	if ( file_exists(dirname(__FILE__) . '/stumbleupon.png') )
		$logo = str_replace(ABSPATH, get_option('siteurl').'/', dirname(__FILE__)) . '/stumbleupon.png';
	else
		$logo = get_option('siteurl').'/wp-content/plugins/stumble_faves/stumbleupon.png';
	$title = "<a class='suwidget' href='$link' title='$desc'>$title</a>";
	if($logobool) $title = "<a class='suwidget' href='$url' title='" . attribute_escape(__('Syndicate this content')) ."'><img style='float:left;' src='".$logo."' alt='' height='16' width='16' /></a>&nbsp;&nbsp;".$title;

	echo $before_widget;
	echo $before_title . $title . $after_title;

	widgets_stumblefave_output( $rss, $options[$number] );

	echo $after_widget;
}

function widgets_stumblefave_output( $rss, $args = array() ) {
	if ( is_string( $rss ) ) {
		require_once(ABSPATH . WPINC . '/rss.php');
		if ( !$rss = fetch_rss($rss) )
			return;
	} elseif ( is_array($rss) && isset($rss['url']) ) {
		require_once(ABSPATH . WPINC . '/rss.php');
		$args = $rss;
		$rssurl = 'http://www.stumbleupon.com/syndicate.php?stumbler='.$rss['url'];
		if ( !$rss = fetch_rss($rssurl) )
			return;
	} elseif ( !is_object($rss) ) {
		return;
	}

	extract( $args, EXTR_SKIP );

	$items = (int) $items;
	if ( $items < 1 || 20 < $items )
		$items = 10;
	$show_summary  = (int) $show_summary;
	$show_logo     = (int) $show_logo;
	$show_date     = (int) $show_date;

	if ( is_array( $rss->items ) && !empty( $rss->items ) ) {
		$rss->items = array_slice($rss->items, 0, $items);
		echo '<ul>';
		foreach ($rss->items as $item ) {
			while ( strstr($item['link'], 'http') != $item['link'] )
				$item['link'] = substr($item['link'], 1);
			$link = clean_url(strip_tags($item['link']));
			$title = attribute_escape(strip_tags($item['title']));
			$title = str_replace("&amp;","&",str_replace("amp;821", "#821", $title));
			if(strpos($title, ' ')==false && strlen($title)>50) {
				//Chop-chop time!
				$cutoff = 16;
				$title = substr($title, 0, $cutoff).'...'.substr($title, -$cutoff);
			} 
			if ( empty($title) )
				$title = __('Untitled');
			$desc = '';
				if ( isset( $item['description'] ) && is_string( $item['description'] ) )
					$desc = str_replace(array("\n", "\r"), ' ', attribute_escape(strip_tags(html_entity_decode($item['description'], ENT_QUOTES))));
				elseif ( isset( $item['summary'] ) && is_string( $item['summary'] ) )
					$desc = str_replace(array("\n", "\r"), ' ', attribute_escape(strip_tags(html_entity_decode($item['summary'], ENT_QUOTES))));

			$summary = '';
			if ( isset( $item['description'] ) && is_string( $item['description'] ) )
				$summary = $item['description'];
			elseif ( isset( $item['summary'] ) && is_string( $item['summary'] ) )
				$summary = $item['summary'];

				$desc = str_replace(array("\n", "\r"), ' ', attribute_escape(strip_tags(html_entity_decode($item['summary'], ENT_QUOTES))));

			if ( $show_summary ) {
				$desc = '';
				//$summary = wp_specialchars( $summary );
				$summary = "<div class='widgets_stumblefave-reviews'>$summary</div>";
			} else {
				$summary = '';
			}

			$date = '';
			if ( $show_date ) {
				if ( isset($item['pubdate']) )
					$date = $item['pubdate'];
				elseif ( isset($item['published']) )
					$date = $item['published'];

				if ( $date ) {
					if ( $date_stamp = strtotime( $date ) )
						$date = '<br/><span class="widgets_stumblefave-date">' . date_i18n( get_option( 'date_format' ), $date_stamp ) . '</span>';
					else
						$date = '';
				}
			}

			echo "<li><a class='suwidget' href='$link' title='$desc' rel='nofollow' target='_blank'>$title</a>{$date}{$summary}</li>";
		}
		echo '</ul>';
	} else {
		echo '<ul><li>' . __( 'An error has occurred; the feed is probably down. Try again later.' ) . '</li></ul>';
	}
}

function widgets_stumblefave_control($widget_args) {
	global $wp_registered_widgets;
	static $updated = false;

	if ( is_numeric($widget_args) )
		$widget_args = array( 'number' => $widget_args );
	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
	extract($widget_args, EXTR_SKIP);

	$options = get_option('widgets_stumblefave');
	if ( !is_array($options) )
		$options = array();

	$urls = array();
	foreach ( $options as $option )
		if ( isset($option['url']) )
			$urls[$option['url']] = true;

	if ( !$updated && 'POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['sidebar']) ) {
		$sidebar = (string) $_POST['sidebar'];

		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( isset($sidebars_widgets[$sidebar]) )
			$this_sidebar =& $sidebars_widgets[$sidebar];
		else
			$this_sidebar = array();

		foreach ( $this_sidebar as $_widget_id ) {
			if ( 'widgets_stumblefave' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
				if ( !in_array( "widgets_stumblefave-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
					unset($options[$widget_number]);
			}
		}

		foreach( (array) $_POST['widgets_stumblefave'] as $widget_number => $widgets_stumblefave ) {
			if ( !isset($widgets_stumblefave['url']) && isset($options[$widget_number]) ) // user clicked cancel
				continue;
			$widgets_stumblefave = stripslashes_deep( $widgets_stumblefave );
			$url = sanitize_url(strip_tags($widgets_stumblefave['url']));
			$options[$widget_number] = widgets_stumblefave_process( $widgets_stumblefave, !isset($urls[$url]) );
		}

		update_option('widgets_stumblefave', $options);
		$updated = true;
	}

	if ( -1 == $number ) {
		$title = '';
		$url = '';
		$items = 10;
		$error = false;
		$number = '%i%';
		$show_summary = 0;
		$show_logo = 0;
		$show_date = 0;
	} else {
		extract( (array) $options[$number] );
	}

	widgets_stumblefave_form( compact( 'number', 'title', 'url', 'items', 'error', 'show_summary', 'show_logo', 'show_date' ) );
}

function widgets_stumblefave_form( $args, $inputs = null ) {
	$default_inputs = array( 'url' => true, 'title' => true, 'items' => true, 'show_summary' => true, 'show_logo' => true, 'show_date' => true );
	$inputs = wp_parse_args( $inputs, $default_inputs );
	extract( $args );
	$number = attribute_escape( $number );
	$title  = attribute_escape( $title );
	$url    = attribute_escape( $url );
	$url    = substr($url, -($strlen-50));
	$items  = (int) $items;
	if ( $items < 1 || 20 < $items )
		$items  = 10;
	$show_summary   = (int) $show_summary;
	$show_logo      = (int) $show_logo;
	$show_date      = (int) $show_date;

	if ( $inputs['url'] ) :
?>
	<p>
		<label for="widgets_stumblefave-url-<?php echo $number; ?>"><?php _e('Enter your StumbleUpon username:'); ?>
			<input class="widefat" id="widgets_stumblefave-url-<?php echo $number; ?>" name="widgets_stumblefave[<?php echo $number; ?>][url]" type="text" value="<?php echo $url; ?>" />
		</label>
	</p>
<?php endif; if ( $inputs['title'] ) : ?>
	<p>
		<label for="widgets_stumblefave-title-<?php echo $number; ?>"><?php _e('Give the feed a title (optional):'); ?>
			<input class="widefat" id="widgets_stumblefave-title-<?php echo $number; ?>" name="widgets_stumblefave[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" />
		</label>
	</p>
<?php endif; if ( $inputs['items'] ) : ?>
	<p>
		<label for="widgets_stumblefave-items-<?php echo $number; ?>"><?php _e('How many items would you like to display?'); ?>
			<select id="widgets_stumblefave-items-<?php echo $number; ?>" name="widgets_stumblefave[<?php echo $number; ?>][items]">
				<?php
					for ( $i = 1; $i <= 20; ++$i )
						echo "<option value='$i' " . ( $items == $i ? "selected='selected'" : '' ) . ">$i</option>";
				?>
			</select>
		</label>
	</p>
<?php endif; if ( $inputs['show_logo'] ) : ?>
	<p>
		<label for="widgets_stumblefave-show-logo-<?php echo $number; ?>">
			<input id="widgets_stumblefave-show-logo-<?php echo $number; ?>" name="widgets_stumblefave[<?php echo $number; ?>][show_logo]" type="checkbox" value="1" <?php if ( $show_logo ) echo 'checked="checked"'; ?>/>
			<?php _e('Display StumbleUpon logo?'); ?>
		</label>
	</p>
<?php endif; if ( $inputs['show_summary'] ) : ?>
	<p>
		<label for="widgets_stumblefave-show-summary-<?php echo $number; ?>">
			<input id="widgets_stumblefave-show-summary-<?php echo $number; ?>" name="widgets_stumblefave[<?php echo $number; ?>][show_summary]" type="checkbox" value="1" <?php if ( $show_summary ) echo 'checked="checked"'; ?>/>
			<?php _e('Show no. of votes/reviews for item?'); ?>
		</label>
	</p>
<?php endif; if ( $inputs['show_date'] ) : ?>
	<p>
		<label for="widgets_stumblefave-show-date-<?php echo $number; ?>">
			<input id="widgets_stumblefave-show-date-<?php echo $number; ?>" name="widgets_stumblefave[<?php echo $number; ?>][show_date]" type="checkbox" value="1" <?php if ( $show_date ) echo 'checked="checked"'; ?>/>
			<?php _e('Display item date?'); ?>
		</label>
	</p>
	<input type="hidden" name="widgets_stumblefave[<?php echo $number; ?>][submit]" value="1" />
<?php
	endif;
	foreach ( array_keys($default_inputs) as $input ) :
		if ( 'hidden' === $inputs[$input] ) :
			$id = str_replace( '_', '-', $input );
?>
	<input type="hidden" id="widgets_stumblefave-<?php echo $id; ?>-<?php echo $number; ?>" name="widgets_stumblefave[<?php echo $number; ?>][<?php echo $input; ?>]" value="<?php echo $$input; ?>" />
<?php
		endif;
	endforeach;
}

// Expects unescaped data
function widgets_stumblefave_process( $widgets_stumblefave, $check_feed = true ) {
	$items = (int) $widgets_stumblefave['items'];
	if ( $items < 1 || 20 < $items )
		$items = 10;
	$url           = sanitize_url(strip_tags( "http://www.stumbleupon.com/syndicate.php?stumbler=".$widgets_stumblefave['url'] ));
	$title         = trim(strip_tags( $widgets_stumblefave['title'] ));
	$show_summary  = (int) $widgets_stumblefave['show_summary'];
	$show_logo     = (int) $widgets_stumblefave['show_logo'];
	$show_date     = (int) $widgets_stumblefave['show_date'];

	if ( $check_feed ) {
		require_once(ABSPATH . WPINC . '/rss.php');
		$rss = fetch_rss($url);
		$error = false;
		$link = '';
		if ( !is_object($rss) ) {
			$url = wp_specialchars(__('Error: could not find an RSS or ATOM feed at that URL.'), 1);
			$error = sprintf(__('Error in RSS %1$d'), $widget_number );
		} else {
			$link = clean_url(strip_tags($rss->channel['link']));
			while ( strstr($link, 'http') != $link )
				$link = substr($link, 1);
		}
	}

	return compact( 'title', 'url', 'link', 'items', 'error', 'show_summary', 'show_logo', 'show_date' );
}

function widgets_stumblefave_register() {
	if ( !$options = get_option('widgets_stumblefave') )
		$options = array();
	$widget_ops = array('classname' => 'widgets_stumblefave', 'description' => __( 'Favorite sites from StumbleUpon' ));
	$control_ops = array('width' => 400, 'height' => 200, 'id_base' => 'widgets_stumblefave');
	$name = __('StumbleUpon Favorites');

	$id = false;
	foreach ( array_keys($options) as $o ) {
		// Old widgets can have null values for some reason
		if ( !isset($options[$o]['url']) || !isset($options[$o]['title']) || !isset($options[$o]['items']) )
			continue;
		$id = "widgets_stumblefave-$o"; // Never never never translate an id
		wp_register_sidebar_widget($id, $name, 'widgets_stumblefave', $widget_ops, array( 'number' => $o ));
		wp_register_widget_control($id, $name, 'widgets_stumblefave_control', $control_ops, array( 'number' => $o ));
	}

	// If there are none, we register the widget's existance with a generic template
	if ( !$id ) {
		wp_register_sidebar_widget( 'widgets_stumblefave-1', $name, 'widgets_stumblefave', $widget_ops, array( 'number' => -1 ) );
		wp_register_widget_control( 'widgets_stumblefave-1', $name, 'widgets_stumblefave_control', $control_ops, array( 'number' => -1 ) );
	}
}


add_action( 'widgets_init', 'widgets_stumblefave_register' )

?>