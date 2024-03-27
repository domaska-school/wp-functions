<?php
// Свой размер изображения для галерей
$_wp_additional_image_sizes['domcad-size'] = array(
	'width'  => absint( 730 ),
	'height' => absint( 0 ),
	'crop'   => false,
);

// Подключаем класс получения информации о Video
require_once(dirname(__FILE__) . '/Video.php');


// Можно засунуть всё в один класс, но мы раскидаем по классам.

// Класс отключения/включения чего либо )))
class wpRun {
	static function init() {
		// Увеличение массы загружаемого файла
		@ini_set( 'upload_max_size' , '20MB' );
		@ini_set( 'post_max_size'   , '25MB' );
		@ini_set( 'memory_limit'    , '30MB' );
		
		// Отключение обновления тем
		remove_action('load-update-core.php', 'wp_update_themes');
		add_filter('pre_site_transient_update_themes', create_function('$a', "return null;"));
		wp_clear_scheduled_hook('wp_update_themes');
		
		// Отключаем сообщение о выводе объектного кеша
		add_filter( 'site_status_should_suggest_persistent_object_cache', '__return_false' );
		add_filter( 'site_status_page_cache_supported_cache_headers', '__return_false' );
		
		// Отключаем сообщение о пропущенном задании
		//define('DISABLE_WP_CRON', true);
		//remove_action('do_pings', 'do_all_pings');
		//wp_clear_scheduled_hook('do_pings');
		
		// Отключаем RSS комментариев
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_action( 'template_redirect', array(__CLASS__, 'redirect_attachment_page') );
	}
	
	static function redirect_attachment_page(){
		if ( is_attachment() ):
			global $post;
			if ( $post && $post->post_parent ):
				wp_redirect( esc_url( get_permalink( $post->post_parent ) ), 301 );
				exit;
			else:
				wp_redirect( esc_url( home_url( '/' ) ), 301 );
				exit;
			endif;
		endif;
	}
}

// Класс вывода Яндекс форм
class yandexFormShotCode {

	static $add_script;

	static function init() {
		add_shortcode('yandex_form', array(__CLASS__, 'yandex_form_func'));
		add_action('init', array(__CLASS__, 'register_script'));
		add_action('wp_head', array(__CLASS__, 'print_script'));
	}

	static function yandex_form_func( $atts ) {
		self::$add_script = true;
		extract( shortcode_atts( array(
			'id' => false,
		), $atts ));
		if($id) {
			$frame = '<script src="https://yastatic.net/s3/frontend/forms/_/embed.js"></script><iframe src="https://forms.yandex.ru/u/' . $id . '/?iframe=1" frameborder="0" name="ya-form-' . $id . '" style="display: block; width: 100% !important;"></iframe>';
			return $frame;
		}
		return "";
	}

	static function register_script() {
		//wp_register_script( 'yandex-form', 'https://yastatic.net/s3/frontend/forms/_/embed.js');
	}

	static function print_script () {
		if ( !self::$add_script ) return;
		wp_print_scripts('yandex-form');
	}
}

// Класс вывода галерей
class galleryShortCode {
	static function init() {
		/**
		 ** Отключаем srcset
		**/
		add_filter( 'wp_calculate_image_srcset', array(__CLASS__, 'disable_srcset'     ) );
		/**
		 ** Переопределяем стандартную галерею
		**/
		add_filter( 'post_gallery',              array(__CLASS__, 'gallery_function'   ), 10, 2 );
		/**
		 ** Добавляем свои шоткоды галерей
		 ** Данный функционал только для примера или на всякий случай
		**/
		add_shortcode( 'domashka_gallery',       array(__CLASS__, 'ps_gallery_function') );
		add_shortcode( 'domcad_gallery',         array(__CLASS__, 'ps_gallery_function') );
	}
	
	static function disable_srcset( $sources ) {
		return false;
	}
	
	static function gallery_function( $output, $atts ) {
		global $post; 
		$out = '';
		if($atts["ids"]):
			$arrs = explode(',', $atts["ids"]);
			if(count($arrs)):
				$out = '<figure class="wp-block-gallery has-nested-images columns-default is-cropped wp-block-gallery-1 is-layout-flex wp-block-gallery-is-layout-flex">';
				foreach($arrs as $idstr):
					$id = (int)$idstr;
					if($src = wp_get_attachment_url($id)):
						$medium = wp_get_attachment_image_src($id, "domcad-size");
						$out .= '
			<figure class="wp-block-image size-large">
				<a href="' . $src . '" target="_blank" data-fancybox="gallery-' . $post->ID . '">
					<img decoding="async" width="' . $medium[1] . '" height="' . $medium[2] . '" data-id="' . $id . '" class="wp-image-' . $id . '" src="' . $medium[0] . '" alt="">
				</a>
			</figure>';
					endif;
				endforeach;
				$out .= '
	</figure>';
			endif;
		endif;
		return $out;
	}
	
	static function ps_gallery_function($atts){
		return self::gallery_function(false, $atts);
	}
}

// Класс вывода галлерей video
class videoShortCode {
	static function init(){
		add_shortcode( 'video', array(__CLASS__, 'ps_video_function') );
	}
	static function ps_video_function($atts){
		global $post; 
		$out = '';
		if($atts["links"]):
			$arrs = explode(';', $atts["links"]);
			if(count($arrs)):
				foreach($arrs as $link):
					$video = new Video($link);
					$videoInfo = $video->videoInfo;
					if($videoInfo['video']):
						$out .= $videoInfo['video'];
					endif;
				endforeach;
			endif;
		endif;
		return $out;
	}
}

// Подключение скриптов и стилей
class registerStyleScript {
	static function init(){
		add_action ( 'wp_enqueue_scripts', array(__CLASS__, 'add_theme_scripts') );
	}
	
	static function add_theme_scripts() {
		$url = site_url('/wp-includes/shortcodes', '');
		wp_enqueue_style(  'fancybox', $url . '/jquery.fancybox.min.css', false, '3.5.7', 'all');
		wp_enqueue_script( 'fancybox', $url . '/jquery.fancybox.min.js', array( 'jquery' ), '3.5.7', true);
		wp_enqueue_style(  'fancybox-main', $url . '/main.min.css', false, filemtime(dirname(__FILE__) . '/main.min.css'), 'all');
		wp_enqueue_script( 'fancybox-main', $url . '/main.min.js',  array( 'jquery' ), filemtime(dirname(__FILE__) . '/main.min.js'), true);
	}
}

// Инициируем
wpRun::init();
registerStyleScript::init();
yandexFormShotCode::init();
galleryShortCode::init();
videoShortCode::init();
