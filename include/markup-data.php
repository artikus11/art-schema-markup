<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'asm_markup_post' ) ) {
	add_filter( 'the_content', 'asm_markup_post', 10 );
	/**
	 * Вывод разметки внутри статьи
	 *
	 * @param $content
	 *
	 * @return string
	 */
	function asm_markup_post( $content ) {
		$markup = new Art_Markup_Post();
		
		return $markup->markup_schemaorg() . $content;
	}
}

if ( ! class_exists( 'Art_Markup_Post' ) ) {
	class Art_Markup_Post {
		
		private $categories;
		private $categories_count;
		private $categories_list;
		private $post_obj;
		private $author_data;
		public $default = [
			'image_default' => '',
			'logo'          => '',// Обязательно установить ссылку на логотип
		];
		
		/**
		 * Получение полной разметки
		 *
		 * @return string
		 */
		public function markup_schemaorg() {
			$posts_schema = '<!-- This markup start  --><script type="application/ld+json">';
			$posts_schema .= $this->get_markup_data();
			$posts_schema .= '</script><!-- This markup end  --> ';
			
			return $posts_schema;
		}
		
		/**
		 * Сбор данных и получение строки json
		 *
		 * @return mixed|string
		 */
		public function get_markup_data() {
			$canonnical_link  = get_the_permalink( $this->get_post_obj()->ID );
			$post_type_markup = is_single() ? 'BlogPosting' : 'Article';
			$post_markup      = [
				'@context'         => 'http://schema.org',
				'@type'            => $post_type_markup,
				'mainEntityOfPage' => [
					'@type' => 'WebPage',
					'@id'   => $canonnical_link,
				],
				'url'              => $canonnical_link,
				'headline'         => $this->get_post_obj()->post_title,
				'image'            => [
					'@type'  => 'ImageObject',
					'url'    => $this->get_images()[0],
					'width'  => $this->get_images()[1],
					'height' => $this->get_images()[2],
				],
				'datePublished'    => $this->get_post_obj()->post_date,
				'dateModified'     => $this->get_post_obj()->post_modified,
				'articleSection'   => $this->get_category(),
				'publisher'        => [
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'logo'  => [
						'@type'  => 'ImageObject',
						'url'    => $this->get_default()['logo'],
						'width'  => 600,
						'height' => 60,
					],
				],
				'author'           => [
					'@type' => 'Person',
					'name'  => $this->get_author_data(),
					'url'   => get_author_posts_url( $this->get_post_obj()->post_author ),
					'image' => [
						'@type'  => 'ImageObject',
						'url'    => get_avatar_url( $this->get_post_obj(), "size=24&default=wavatar" ),
						'width'  => 24,
						'height' => 24,
					],
				],
				'comment'          => $this->get_comments(),
			];
			$markup_json      = json_encode( $post_markup, JSON_UNESCAPED_UNICODE );
			
			return $markup_json;
		}
		
		/**
		 * Получение картинок
		 * В первую очередь получаем миниатюру
		 * если ее нет, то первую прикрепленную картинку
		 * иначе занчение по умолчанию
		 *
		 * @param string $size
		 *
		 * @return array|false
		 */
		public function get_images( $size = 'full' ) {
			$images_first = get_children( [
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_parent'    => $this->get_post_obj()->ID,
			] );
			if ( has_post_thumbnail( $this->get_post_obj()->ID ) ) {
				$image_arr = wp_get_attachment_image_src( get_post_thumbnail_id( $this->get_post_obj()->ID ), $size );
			} elseif ( isset( $images_first ) && ! empty( $images_first ) ) {
				$image     = reset( $images_first );
				$image_arr = wp_get_attachment_image_src( $image->ID, $size );
			} elseif ( ! isset( $images_first ) && empty( $images_first ) ) {
				ob_start();
				ob_end_clean();
				$output_image = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $this->get_post_obj()->post_content, $matches );
				$image_arr    = [ $matches [1][0], '1280', '720' ];
			} elseif ( isset( $this->get_default()['image_default']  ) && ! empty( $this->get_default()['image_default']  )) {
				
				$image_arr = [ $this->get_default()['image_default'] , '1280', '720' ];
			} else {
				$image_arr = ['', '1280', '720' ];
			}
			
			return $image_arr;
		}
		
		/**
		 * Получение всех рубрик статьи
		 *
		 * @return string
		 */
		public function get_category() {
			$this->categories       = get_the_category();
			$this->categories_count = count( $this->categories );
			$this->categories_list  = '';
			if ( $this->categories_count > 1 ) {
				foreach ( $this->categories as $category ) {
					$this->categories_list .= $category->name . ', ';
				}
			} else {
				foreach ( $this->categories as $category ) {
					$this->categories_list .= $category->name;
				}
			}
			
			return $this->categories_list;
		}
		
		/**
		 * Получение комментариев статьи
		 *
		 * @return array
		 */
		public function get_comments() {
			$comment_arr   = array();
			$post_comments = get_comments( array(
				'post_id' => $this->get_post_obj()->ID,
				'status'  => 'approve',
				'type'    => 'comment',
			) );
			if ( $post_comments ) {
				foreach ( $post_comments as $comment ) {
					$comment_arr[] = [
						'@type'       => 'Comment',
						'dateCreated' => $comment->comment_date,
						'author'      => [
							'@type' => 'Person',
							'name'  => $comment->comment_author,
							'url'   => $comment->comment_author_url ? $comment->comment_author_url : '',
						],
						'description' => strip_tags( $comment->comment_content ),
					];
				}
			}
			
			return $comment_arr;
		}
		
		/**
		 * @return mixed
		 */
		public function get_categories() {
			return $this->categories;
		}
		
		/**
		 * @param mixed $categories
		 */
		public function set_categories( $categories ) {
			$this->categories = $categories;
		}
		
		/**
		 * @return mixed
		 */
		public function get_categories_count() {
			return $this->categories_count;
		}
		
		/**
		 * @param mixed $categories_count
		 */
		public function set_categories_count( $categories_count ) {
			$this->categories_count = $categories_count;
		}
		
		/**
		 * @return mixed
		 */
		public function get_categories_list() {
			return $this->categories_list;
		}
		
		/**
		 * @param mixed $categories_list
		 */
		public function set_categories_list( $categories_list ) {
			$this->categories_list = $categories_list;
		}
		
		/**
		 * @return mixed
		 */
		public function get_post_obj() {
			global $post;
			$this->post_obj = $post;
			
			return $this->post_obj;
		}
		
		/**
		 * @return mixed
		 */
		public function get_default() {
			$this->default = [
				'image_default' => get_option('asm_option_name')['image_default'],
				'logo'          => get_option('asm_option_name')['image_logo'],
			];
			return $this->default;
		}
		
		/**
		 * @param array $default
		 */
		public function set_default( array $default ): void {
			$this->default = $default;
		}
		
		/**
		 * @return mixed
		 */
		public function get_author_data() {
			$this->author_data = get_userdata( $this->get_post_obj()->post_author );
			
			return $this->author_data->data->display_name;
		}
		
		/**
		 * @param mixed $authordata
		 */
		public function set_author_data( $author_data ): void {
			$this->author_data = $author_data;
		}
		
	}
}
