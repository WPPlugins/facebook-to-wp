<?php

class f2w_feed_post {

	// Feed id
	private $feed_id = null;
	
	public $options = null;

	public $fb = null;
	
	
	function __construct() {
		
		$this->options = get_option('f2w_options');
		
		$appId = $this->options['app_id'];
		$secret = $this->options['secret'];

		$this->feed_id = $this->options['feed_id'];

		if ( !$appId || !$secret || !$this->feed_id)
			return null;
		
		$this->fb = $this->auth($appId, $secret);
		
		if ( !$this->fb )
			return null;
		else
			return $this;
	}
	
	function auth($appId, $secret) {
		
		include_once 'f2w-facebook.php';
		return new f2w_fb( $appId, $secret );
	}

	function post($limit) {

		$feed_id = $this->feed_id;
		
		$count = 0;
		if( !$limit ) {
			$limit = 10;
		}elseif ( $limit > 30 ) {
			$limit = 30;
		}
		$cat_name = 'facebook';
		$cat_id = get_cat_ID( $cat_name );
		if( !$cat_id ){
			$cat_id = wp_create_category( $cat_name );
		}

		$post_ids = $this->options['post_ids'];
		sort($post_ids, SORT_STRING);
		$latest_post_id = end($post_ids);
		$latest_post_exist = false;

		$api = "/$feed_id/feed?date_format=U&locale=".get_locale();
		$_a = 0;
		$_c = 0;
		while ( $_c < 30 ) {
			$_c++;
			$content = $this->fb->api($api);
			
			if ( $content && isset($content['data']) && count($content['data']) > 0 ) {

				$items = $content['data'];

				foreach( $items as $item ) {
					if ( empty($item) ) {
						continue;	
					} else if ( isset($item['to']) || !isset($item['from']) ) {
						continue;
					} else if ( $item['from']['id'] != $feed_id ) {
						continue;
					}

					$item_id = $item['id'];

					// POST済みかどうか確認
					if ( array_search($item_id, $post_ids) !== false ) {
						if ( $item_id == $latest_post_id ){
							$latest_post_exist = true;
						}
						continue;
					}

					$_a++;
					if($_a > 40) {
						break 2;
					}

					$item_link = preg_split('/_/', $item_id );
					$item_link = 'http://www.facebook.com/'. $item_link[0] .'/posts/'. $item_link[1];

					$created_time = $item['created_time'];

					$type = isset($item['type']) ? trim($item['type']) : null; //status, photo, link, video

					$link = isset($item['link']) ? trim($item['link']) : null;

					$text = isset($item['message']) ? trim($item['message']) : null;
					$description = isset($item['description']) ? trim($item['description']) : null; //og:description
					$thumbnail = isset($item['picture']) ? trim($item['picture']) : null;
					$name = isset($item['name']) ? trim($item['name']) : null; //og:title
					$caption = isset($item['caption']) ? trim($item['caption']) : null;	//domain of link

					//タイトル作成
					$title = $text ? $text : ( $name  ?  $name : ( $description ? $description : null ) );
					if( !$title ) {
						continue;
					} elseif ( mb_strwidth($title, 'UTF-8') > 43 ) {
						$words = split(' ', $title);
						$title = '';
						$_title = '';
						for ( $i = 0; $i < count($words); $i++ ) {
							$_title .= ' '.trim($words[$i]);
							if ( mb_strwidth($_title, 'UTF-8') > 43 ) {
								if ( mb_strwidth($title, 'UTF-8') < 37 ) {
									$title = mb_strimwidth($_title, 0, 40, '...');
								}elseif ( $i != count($words)-1 ){
									$title .= '...';
								}
								break;
							} else {
								$title = $_title;
							}
						}
					}
					$title = trim($title);

					//コンテンツ作成
					$text = $this->link_text($text);
					$post_content = "<div class='f2w-text'>$text</div>";
					$picture = null;
					$ylink = null;

					if ( $type=='photo' ) {
						$patterns = array('/^(http:\/\/photos-a\.)/', '/\_s\.(jpg|png|gif|jpeg)/i');
						$replaces = array('http://sphotos-a.', '_n.$1');
						$picture = preg_replace($patterns, $replaces, $thumbnail);
						if ( $picture !== $thumbnail ) {
							$post_content .= "<div class='f2w-image-box'><a href='$link' target='_blank'><div class='f2w-image'><img src='$picture'/></div></a></div>";
						} else {
							$picture = null;
						}
					} elseif ( $type=='video' ) {
						$source = isset($item['source']) ? trim($item['source']) : null;
						if( $source && preg_match('/(https?:\/\/(?:www\.)?youtube\.com)\/(?:v|embed)\/([-\w]+)/', $source, $matches)) {
							$ylink = $matches[1].'/embed/'.$matches[2];
							$post_content .= "<div class='f2w-video'><iframe width='560' height='315' src='$ylink' frameborder='0' allowfullscreen=''></iframe></div>";
						}
					}

					if ( $link && !$picture && !$ylink && !$get_album) {
						$post_content .= "<div class='f2w-footer'>";
						if ( $thumbnail ) {
							$post_content .= "<div class='f2w-thumb-box'><a href='$link' target='_blank'>";
							$post_content .= "<div class='f2w-thumb'><img src='$thumbnail'/></div>";
							if ( $type=='video' ) {
								$post_content .= '<div class="f2w-thumb-video"><img src="'.$this->plugin_url().'/images/video.png"/></div>';
							}
							$post_content .= "</a></div>";
						}
						if ( !$caption ) {
							$parse_url = parse_url($link);
							$caption = $parse_url['host'];
						}
						$post_content .= "<div class='f2w-link-box'><a href='$link' target='_blank'>";
						$post_content .= $name ? "<div class='f2w-link-name'>$name</div>" : '';
						$post_content .= $caption ? "<div class='f2w-link-caption'>$caption</div>" : '';
						$post_content .= $description ? "<div class='f2w-link-description'>$description</div>" : '';
						$post_content .= "</a></div></div>";
					}
					$post_content .= $item_link ? "<div class='f2w-itemlink'><a href='$item_link' target='_blank'>facebook.com</a></div>" : '';
					
					//投稿
					$post = array(
						'post_status' => 'publish',
						'post_content' => $post_content,
						'post_title' => $title,
						'post_date' => date('Y-m-d H:i:s', $created_time + date('Z')),
						'post_date_gmt' => date('Y-m-d H:i:s', $created_time),
						'post_category' => array($cat_id),
						'post_author' => 1,
					);
					wp_insert_post( $post );

					$post_ids[] = $item_id;
					
					$count++;
					//limit
					if( $count >= $limit) {
						break 2;
					}
				}//foreach

				if( !$latest_post_exist ) {
					//次のページ
					$paging = isset($content['paging']) ? $content['paging'] : null;
					$next = $paging && isset($paging['next']) ? $paging['next'] : null;
					if ( $next ) {
						$url = parse_url($next);
						$api = "/$feed_id/feed?".$url['query'];
						$content = null;
						continue;
					}
				}
			}//if
			break;
		}//while

		if ( $count > 0) {
			$this->options['post_ids'] = $post_ids;
			update_option('f2w_options', $this->options);
			return true;
		} else {
			return false;
		}
	}

	function link_text($text) {
		$pattern = '/http(?:s?):\/\/(?:[0-9a-z\-\.])+(?:(?:\?|\/|\#)[!#-%*-;=?-[\]-~]*[#-%*+\-\/-9;=@-[\]-~])?/i';
		return preg_replace($pattern, '<a href="$0">$0</a>', $text);
	}

	function plugin_url() {
		return WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) );
	}
}
