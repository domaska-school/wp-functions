<?php
// Свой размер изображения для галерей
$_wp_additional_image_sizes['domcad-size'] = array(
	'width'  => absint( 730 ),
	'height' => absint( 0 ),
	'crop'   => false,
);

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
		define('DISABLE_WP_CRON', true);
		
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

class Video {

	/** Ссылка на ролик */
	private $link;
	
	/** Видеохостинг */
	private $hosting;

	/** данные видео */
	public $videoInfo = array();

	/** Автоматическое сохранение изображения */
	private $autosave = false;

	/** Ссылка на каталог перевьюшек */
	private $dir_images = "assets/images/video/";

	const YOUTUBE = 'youtube';
	const RUTUBE  = 'rutube';
	const DEF     = 'default';

	/**
	 * @param string|null $link ссылка на видео
	 */
	public function __construct(string $link = null, bool $autosave = false, array &$videoInfo = array())
	{
		//$this->modx = EvolutionCMS();
		$this->autosave = $autosave ? true : false;
		if (!empty($link)) {
			$videoInfo = $this->cleanLink($link)->getVideoInfo();
		}
	}


	/** Проверка и подготовка ссылки и частей */
	private function cleanLink($link)
	{
		if (!preg_match('/^(http|https)\:\/\//i', $link)) {
			$this->link = 'https://' . $link;
		}else{
			$this->link = preg_replace('/^(?:https?):\/\//i', 'https://', $link, 1);
		}
		return $this;
	}

	/** Определяем хостинг и получаем информацию о видео */
	private function getVideoInfo()
	{
		$re_youtube = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(youtu(?:\.be|be\.com))\//i';
		$re_rutube  = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(rutube\.ru)/i';
		if(preg_match($re_youtube, $this->link)){
			$this->hosting = self::YOUTUBE;
			return $this->getYouTubeInfo();
		}elseif(preg_match($re_rutube, $this->link)){
			$this->hosting = self::RUTUBE;
			return $this->getRuTubeInfo();
		}
		$this->hosting = self::DEF;
		return array();
	}

	/** Получение информации с RuTube */
	private function getRuTubeInfo()
	{
		$re = '/\/video\/([\w\-_]+)/i';
		preg_match($re, $this->link, $match);
		if(count($match)){
			$id = $match[1];
			//$link = "https://rutube.ru/api/video/" . $id . "/?format=json";
			//$str = $this->fetchPage($link);
			//$json = json_decode($str, true);
			// <iframe width="720" height="405" src="https://rutube.ru/play/embed/748b8fd0491c7ba687e04d95ab1ea187" frameBorder="0" allow="clipboard-write" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
			if($id){
				$this->videoInfo['id'] = $id;
				$this->videoInfo['link'] = $this->link;
				//$json['track_id']
				$embed = "https://rutube.ru/play/embed/" . $this->videoInfo['id'] . "/";
				$this->videoInfo['embed'] = $embed;
				$this->videoInfo['video'] = '<div class="wp-block-video embed"><div class="embed-responsive embed-responsive-16by9"><iframe src="' . $embed . '" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div></div>';
			}else{
				return array();
			}
		}else{
			return array();
		}
		return $this->videoInfo;
	}

	/** Получение информации с YouTube */
	private function getYouTubeInfo()
	{
		$re = '#(?<=(?:v|i)=)[a-z0-9-_]+(?=&)|(?<=(?:v|i)\/)[^&\n]+|(?<=embed\/)[^"&\n]+|(?<=(?:v|i)=)[^&\n]+|(?<=youtu.be\/)[^&\n]+#i';
		preg_match($re, $this->link, $match);
		if(count($match)){
			if($id){
				$this->videoInfo['id'] = $match[0];
				$this->videoInfo['link'] = $this->link;
				$embed = 'https://www.youtube.com/embed/' . $match[0] . '?';
				parse_str(parse_url($this->link, PHP_URL_QUERY), $params);
				if($params['list']){
					$embed .= 'list=' . $params['list'] . '&';
				}
				$embed .= 'showinfo=0&modestbranding=1&rel=0';
				$this->videoInfo['embed'] = $embed;
				$this->videoInfo['video'] = '<div class="wp-block-video embed"><div class="embed-responsive embed-responsive-16by9"><iframe src="' . $embed . '" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe></div></div>';
			}
		}else{
			return array();
		}
		return $this->videoInfo;
	}

	/** Скачивание с помощью CURL */
	private function fetchPage($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode >= 400){
			return false;
		}
		return $result;
	}
	
	public function setLink(string $link = null)
	{
		$videoInfo = array();
		if (!empty($link)) {
			$videoInfo = $this->cleanLink($link)->getVideoInfo();
		}
		return $videoInfo;
	}
}
// Инициируем
wpRun::init();
registerStyleScript::init();
yandexFormShotCode::init();
galleryShortCode::init();
videoShortCode::init();
