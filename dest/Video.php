<?php
/**
 * Класс получения информации о видео на Rutube, YouTube
*/

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