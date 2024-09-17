<?php
/**
 * Hestia functions and definitions
 *
 * @package Hestia
 * @since   Hestia 1.0
 */

define( 'HESTIA_VERSION', '3.1.10' );
define( 'HESTIA_VENDOR_VERSION', '1.0.2' );
define( 'HESTIA_PHP_INCLUDE', trailingslashit( get_template_directory() ) . 'inc/' );
define( 'HESTIA_CORE_DIR', HESTIA_PHP_INCLUDE . 'core/' );

if ( ! defined( 'HESTIA_DEBUG' ) ) {
	define( 'HESTIA_DEBUG', false );
}

// Load hooks
require_once( HESTIA_PHP_INCLUDE . 'hooks/hooks.php' );

// Load Helper Globally Scoped Functions
require_once( HESTIA_PHP_INCLUDE . 'helpers/sanitize-functions.php' );
require_once( HESTIA_PHP_INCLUDE . 'helpers/layout-functions.php' );

if ( class_exists( 'WooCommerce', false ) ) {
	require_once( HESTIA_PHP_INCLUDE . 'compatibility/woocommerce/functions.php' );
}

if ( function_exists( 'max_mega_menu_is_enabled' ) ) {
	require_once( HESTIA_PHP_INCLUDE . 'compatibility/max-mega-menu/functions.php' );
}

// Load starter content
require_once( HESTIA_PHP_INCLUDE . 'compatibility/class-hestia-starter-content.php' );


/**
 * Adds notice for PHP < 5.3.29 hosts.
 */
function hestia_no_support_5_3() {
	$message = __( 'Hey, we\'ve noticed that you\'re running an outdated version of PHP which is no longer supported. Make sure your site is fast and secure, by upgrading PHP to the latest version.', 'hestia' );

	printf( '<div class="error"><p>%1$s</p></div>', esc_html( $message ) );
}


if ( version_compare( PHP_VERSION, '5.3.29' ) < 0 ) {
	/**
	 * Add notice for PHP upgrade.
	 */
	add_filter( 'template_include', '__return_null', 99 );
	switch_theme( WP_DEFAULT_THEME );
	unset( $_GET['activated'] );
	add_action( 'admin_notices', 'hestia_no_support_5_3' );

	return;
}

/**
 * Begins execution of the theme core.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function hestia_run() {

	require_once HESTIA_CORE_DIR . 'class-hestia-autoloader.php';
	$autoloader = new Hestia_Autoloader();

	spl_autoload_register( array( $autoloader, 'loader' ) );

	new Hestia_Core();

	$vendor_file = trailingslashit( get_template_directory() ) . 'vendor/composer/autoload_files.php';
	if ( is_readable( $vendor_file ) ) {
		$files = require_once $vendor_file;
		foreach ( $files as $file ) {
			if ( is_readable( $file ) ) {
				include_once $file;
			}
		}
	}
	add_filter( 'themeisle_sdk_products', 'hestia_load_sdk' );

	if ( class_exists( 'Ti_White_Label', false ) ) {
		Ti_White_Label::instance( get_template_directory() . '/style.css' );
	}
}

/**
 * Loads products array.
 *
 * @param array $products All products.
 *
 * @return array Products array.
 */
function hestia_load_sdk( $products ) {
	$products[] = get_template_directory() . '/style.css';

	return $products;
}

require_once( HESTIA_CORE_DIR . 'class-hestia-autoloader.php' );

/**
 * The start of the app.
 *
 * @since   1.0.0
 */
hestia_run();

/**
 * Append theme name to the upgrade link
 * If the active theme is child theme of Hestia
 *
 * @param string $link - Current link.
 *
 * @return string $link - New upgrade link.
 * @package hestia
 * @since   1.1.75
 */
function hestia_upgrade_link( $link ) {

	$theme_name = wp_get_theme()->get_stylesheet();

	$hestia_child_themes = array(
		'orfeo',
		'fagri',
		'tiny-hestia',
		'christmas-hestia',
		'jinsy-magazine',
	);

	if ( $theme_name === 'hestia' ) {
		return $link;
	}

	if ( ! in_array( $theme_name, $hestia_child_themes, true ) ) {
		return $link;
	}

	$link = add_query_arg(
		array(
			'theme' => $theme_name,
		),
		$link
	);

	return $link;
}

add_filter( 'hestia_upgrade_link_from_child_theme_filter', 'hestia_upgrade_link' );

/**
 * Check if $no_seconds have passed since theme was activated.
 * Used to perform certain actions, like displaying upsells or add a new recommended action in About Hestia page.
 *
 * @param integer $no_seconds number of seconds.
 *
 * @return bool
 * @since  1.1.45
 * @access public
 */
function hestia_check_passed_time( $no_seconds ) {
	$activation_time = get_option( 'hestia_time_activated' );
	if ( ! empty( $activation_time ) ) {
		$current_time    = time();
		$time_difference = (int) $no_seconds;
		if ( $current_time >= $activation_time + $time_difference ) {
			return true;
		} else {
			return false;
		}
	}

	return true;
}

/**
 * Legacy code function.
 */
function hestia_setup_theme() {
	return;
}

/**
 * Minimize CSS.
 *
 * @param string $css Inline CSS.
 * @return string
 */
function hestia_minimize_css( $css ) {
	if ( empty( $css ) ) {
		return $css;
	}
	// Normalize whitespace.
	$css = preg_replace( '/\s+/', ' ', $css );
	// Remove spaces before and after comment.
	$css = preg_replace( '/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $css );
	// Remove comment blocks, everything between /* and */, unless.
	// preserved with /*! ... */ or /** ... */.
	$css = preg_replace( '~/\*(?![\!|\*])(.*?)\*/~', '', $css );
	// Remove ; before }.
	$css = preg_replace( '/;(?=\s*})/', '', $css );
	// Remove space after , : ; { } */ >.
	$css = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $css );
	// Remove space before , ; { } ( ) >.
	$css = preg_replace( '/ (,|;|\{|}|\(|\)|>)/', '$1', $css );
	// Strips leading 0 on decimal values (converts 0.5px into .5px).
	$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );
	// Strips units if value is 0 (converts 0px to 0).
	$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );
	// Converts all zeros value into short-hand.
	$css = preg_replace( '/0 0 0 0/', '0', $css );
	// Shortern 6-character hex color codes to 3-character where possible.
	$css = preg_replace( '/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css );
	return trim( $css );
}


function my_setup() {
    wp_enqueue_style('faq_base', get_template_directory_uri() . '/assets/css/base.css');
    wp_enqueue_style('faq_main', get_template_directory_uri() . '/assets/css/main.css');
    wp_enqueue_style('faq_tailwind', get_template_directory_uri() . '/assets/css/tailwind.css', array(), '1.0.0', 'all');
}


function custom_post_list_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'posts_per_page' => 5,
        ),
        $atts,
        'post_list'
    );

    $query = new WP_Query(array(
        'post_type' => 'post',
        'posts_per_page' => $atts['posts_per_page'],
        'category_name' => 'promotions'
    ));

    if ($query->have_posts()) {
        $output = '<div class="discount-content less-content">';
        while ($query->have_posts()) {
            $output .= '<div class="group-wrap">';
            $query->the_post();
            if( has_post_thumbnail() ) {
                $output .= '<img src="' . wp_get_attachment_url( get_post_thumbnail_id(get_the_ID())) . '" alt="' . get_the_title() . '">' ;
            }
            $output .= '<h4 class="title-wrap" >' . get_the_title() . '</h4>';
            $output .= '<p class="time u-green">';
            $output .=  '<span class="start_date">'.get_field('start_date').'</span> ~ ';
            $output .= '<span class="start_date">'.get_field('end_date').'</span>';
            $output .= '</p>';
            $output .= '<p class="overview">' . get_the_excerpt() . '</p>';
            $output .= '<a class="u-btn-1 bg-autumn-maple-100 text-white py-[6px] rounded-lg h-[36px] w-[120px] max-w-[120px] inline-block text-center" href="' . get_the_permalink() . '"> 詳細を確認 </a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<div class="text-center"><a class="u-btn-1 bg-autumn-maple-100 text-white py-[6px] rounded-lg h-[36px] w-[120px] max-w-[120px] inline-block text-center" href="/promotions"> すべて表示 </a></div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'No posts found.';
    }
}
add_shortcode('post_list', 'custom_post_list_shortcode');

add_action('wp_enqueue_scripts', 'my_setup');

function tag_link($content){
    $posttags = get_the_tags();
    $match_num_from = 1;  // 一个标签在文章中出现少于多少次不添加链接
    $match_num_to = 1; // 一篇文章中同一个标签添加几次链接

    // 定义 $case 变量，默认值为空字符串
    $case = '';

    if ($posttags) {
        usort($posttags, "tag_sort");
        //var_dump($posttags);
        foreach($posttags as $tag) {
            $link = get_tag_link($tag->term_id);
            $keyword = $tag->name;
            //链接的代码
            $cleankeyword = stripslashes($keyword);
            $url = "<a href=\"$link\" title=\"".str_replace('%s',addcslashes($cleankeyword, '$'),__('【指定のタグ「%s」を含む記事を表示する】'))."\"";
            $url .= ' target="_blank" ';
            $url .= ">".addcslashes($cleankeyword, '$')."</a>";
            $limit = rand($match_num_from,$match_num_to);
            //不链接的代码
            $pattern = "/<code.*?>(.*?)<\/code>/is"; // 匹配 <code> 标签
            $content = preg_replace_callback(
                $pattern,
                static function($matches) use ($cleankeyword) {
                    return str_replace($cleankeyword, '%&&&&&%', $matches[0]);
                },
                $content
            );
            $title_pattern = "/<(h[1-6]).*?>(.*?)<\/\\1>/is";
            $content = preg_replace_callback(
                $title_pattern,
                static function($matches) use ($cleankeyword) {
                    return str_replace($cleankeyword, '%&&&&&%', $matches[0]);
                },
                $content
            );
            //$content = preg_replace( '|(<a[^>]+>)(.*)('.$ex_word.')(.*)(</a[^>]*>)|U'.$case, '$1$2%&&&&&%$4$5', $content);
            //$content = preg_replace( '|(<img)(.*?)('.$ex_word.')(.*?)(>)|U'.$case, '$1$2%&&&&&%$4$5', $content);
            $cleankeyword = preg_quote($cleankeyword,'\'');
            $regEx = '\'(?!((<.*?)|(<a.*?)))('. $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
            $content = preg_replace($regEx,$url,$content,$limit);
            $content = str_replace( '%&&&&&%', stripslashes($cleankeyword), $content);
        }
    }
    return $content;
}

add_filter('the_content','tag_link',1);

add_filter( 'rest_authentication_errors', function( $result ) {
    // If a previous authentication check was applied,
    // pass that result along without modification.
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    // No authentication has been performed yet.
    // Return an error if user is not logged in.
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            __( 'You are not currently logged in.' ),
            array( 'status' => 401 )
        );
    }

    // Our custom authentication check should have no effect
    // on logged-in requests
    return $result;
});
//文章目录
function article_index($content) {
    $matches = array();
    $ul_li = '';
    $r = '/<h([2-6]).*?\>(.*?)<\/h[2-6]>/is';
    if(is_single() && preg_match_all($r, $content, $matches)) {
        foreach($matches[1] as $key => $value) {
            $title = trim(strip_tags($matches[2][$key]));
            $content = str_replace($matches[0][$key], '<h' . $value . ' id="title-' . $key . '">'.$title.'</h2>', $content);
            $ul_li .= '<li><a href="#title-'.$key.'" title="'.$title.'">'.$title."</a></li>\n";
        }
        $content = "\n<div id=\"article-index\">
        <strong>目次</strong>
        <ul id=\"index-ul\">\n" . $ul_li . "</ul>
        </div>\n" . $content;
    }
    return $content;
}
add_filter( 'the_content', 'article_index' );

//404页面 301回首页
function redirect_404_to_home() {
    if (is_404()) {
        wp_redirect(home_url(), 301);
        exit();
    }
}
add_action('template_redirect', 'redirect_404_to_home');

//搜索伪静态
function wp_search_url_rewrite() {
    if ( is_search() && ! empty( $_GET['s'] ) ) {
        wp_redirect( home_url( "/search/" ) . urlencode( get_query_var( 's' ) ) . "/");
        exit();
    }
}
add_action( 'template_redirect', 'wp_search_url_rewrite' );

function tag_sort($a, $b){
    if ( $a->name == $b->name ) return 0;
    return ( strlen($a->name) > strlen($b->name) ) ? -1 : 1;
}

// wordpress自动设置最后一张图为特色图片代码
function wpforce_featured() {
    global $post;
    // 检查 $post 对象是否存在并且不是 null
    if (isset($post) && !is_null($post)) {
        $already_has_thumb = has_post_thumbnail($post->ID);
        if (!$already_has_thumb)  {
            $attached_image = get_children( array(
                'post_parent' => $post->ID,
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'numberposts' => 1
            ));
            if ($attached_image) {
                foreach ($attached_image as $attachment_id => $attachment) {
                    set_post_thumbnail($post->ID, $attachment_id);
                }
            }
        }
    }
}  //end function
add_action('the_post', 'wpforce_featured');
add_action('save_post', 'wpforce_featured');
add_action('draft_to_publish', 'wpforce_featured');
add_action('new_to_publish', 'wpforce_featured');
add_action('pending_to_publish', 'wpforce_featured');
add_action('future_to_publish', 'wpforce_featured');