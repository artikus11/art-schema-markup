<?php
/*
Plugin Name: Art Schema Markup
Plugin URI: http://wpruse.ru/?p=804
Description: Плагин быстрого внедрения микроразметки по schema.org через json-ld для блогов и инфосайтов. Автоматически размечаются посты и страницы
Author: Артем Абрамович
Version: 1.33
Author URI: http://abrfolio.ru
*/
/* ------------------------------------------------------------------------- *
 *  Выводим разметку через json
/* ------------------------------------------------------------------------- */
	add_action( 'wp_head', 'asm_markup_schemaorg' );
	function asm_markup_schemaorg() {
		global $post; // получаем данные о статьях
		/*--- Задаем переменные ---  */
		$image_url_default = '#'; // картинка по умолчанию, размер изображения должен быть не меньше 696px шириной
		$logo_img          = ''; // логотип, размер логотипа должен быть не больше 600px шириной и не больше 60px высотой
		//$adress_schema = 'Россия'; // страна
		//$phone_schema  = 'Стучатся в почту'; // номер телефона
		$date_published  = $post->post_date; // дата публикации
		$date_modified   = $post->post_modified; // дата обновления
		$author_post     = $post->post_author; // автор статьи
		$author_url      = get_author_posts_url( $post->ID ); // ссылка на автора
		$author_img      = get_avatar_url( $post, "size=24&default=wavatar" ); // аватар автора
		$site_name       = get_bloginfo( 'name' ); // название сайта
		$site_url        = get_home_url(); // ссылка на сайт
		$canonnical_link = get_the_permalink( $post->ID ); // ссылка на статью (работает за каноническую ссылку)
		$post_title      = $post->post_title; // заголовок статьи или страницы
		$comment_count   = $post->comment_count; // количество комментариев
		$post_content    = $post->post_content; // текст статьи или страницы
		$post_excerpt    = $post->post_excerpt; // текст статьи или страницы
		/*--- Ищем изображения в статье ---  */
		$first_img = ''; // обнуляем переменную
		$output    = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches ); // фильтруем контент, находим первую картинку
		$first_img = ( $output ) ? $matches[1][0] : $first_img; // если картинка есть, запсываем ее в переменную, иначе берем пустую переменную
		$image_url = empty( $first_img ) ? $image_url_default : $first_img; // если первой картинки нет, тогда берем картинку по умолчанию, иначе первую картинку записи
		$img_post  = ( has_post_thumbnail( $post->ID ) ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' ) : array(
			$image_url,
			700,
			700,
		);
		/*--- Выводим разметку для постов ---*/
		if ( is_single() ) {
			$posts_schema = '<!-- This markup start  -->';
			$posts_schema .= '<script type="application/ld+json">';
			$posts_schema .= '{
                "@context":"http://schema.org",
                "@type":"BlogPosting",
                "mainEntityOfPage": {
                "@type": "WebPage", 
                "@id": "' . $canonnical_link . '"
                },
                "url":"' . $canonnical_link . '",
                "headline":"' . $post_title . '",
                "image": {
                "@type": "ImageObject",
                "url": "' . $img_post[0] . '",
                    "width": ' . $img_post[2] . ',
                "height": ' . $img_post[1] . '
                
                },
                "datePublished":"' . $date_published . '",
                "dateModified":"' . $date_modified . '",
                "articleSection":"' . asm_markup_category() . '",
                "publisher": {
                "@type":"Organization",
                "name":"' . $site_name . '",
                "logo": {
                    "@type": "ImageObject", 
                    "url": "' . $logo_img . '", 
                    "width": 600, 
                    "height": 60
                }
                },
                "author": {
                "@type":"Person",
                "name":"' . $author_post . '",
                "url":"' . $author_url . '",
                "image": {
                    "@type": "ImageObject", 
                    "url": "' . $author_img . '", 
                            "width": 24,
                    "height": 24
                
                }
                },';
			if ( $comment_count != 0 ) {
				$posts_schema .= '"description":"' . strip_tags( $post_excerpt ) . '",';
				$posts_schema .= '"commentCount":"' . $comment_count . '",';
				$posts_schema .= asm_markup_comments();
			} else {
				$posts_schema .= '"description":"' . strip_tags( $post_excerpt ) . '"';
			}
			$posts_schema .= '}';
			$posts_schema .= '</script><!-- This markup end  --> ';
		}
		/*--- Выводим разметку для страниц ---*/
		if ( is_page() ) {
			$posts_schema = '<!-- This markup start  -->';
			$posts_schema .= '<script type="application/ld+json">';
			$posts_schema .= '{
            "@context":"http://schema.org",
            "@type":"Article",
            "mainEntityOfPage": {
            "@type": "WebPage", 
            "@id": "' . $canonnical_link . '"
            },
            "url":"' . $canonnical_link . '",
            "headline":"' . $post_title . '",
            "image": {
            "@type": "ImageObject",
            "url": "' . $img_post[0] . '",
            "height": ' . $img_post[1] . ',
            "width": ' . $img_post[2] . '
            },
            "datePublished":"' . $date_published . '",
            "dateModified":"' . $date_modified . '",
            "publisher": {
            "@type":"Organization",
            "name":"' . $site_name . '",
            "logo": {
                "@type": "ImageObject", 
                "url": "' . $logo_img . '", 
                "width": 600, 
                "height": 60
            }
            },
            "author": {
            "@type":"Person",
            "name":"' . $author_post . '",
            "url":"' . $author_url . '",
            "image": {
                "@type": "ImageObject", 
                "url": "' . $author_img . '", 
                "height": 24, 
                "width": 24
            }
            },
            "description":"' . strip_tags( $post_content ) . '"';
			$posts_schema .= ' } </script><!-- This markup end  --> ';
		}
		$posts_schema = ! empty( $posts_schema ) ? $posts_schema : '';
		echo $posts_schema;
	}
	
	/* ------------------------------------------------------------------------- *
	 *  Определяем рубрики
	/* ------------------------------------------------------------------------- */
	function asm_markup_category() {
		$categories       = get_the_category();
		$categories_sep   = ', ';
		$categories_count = count( $categories );
		$categories_list  = '';
		if ( $categories_count > 1 ) {
			foreach ( $categories as $category ) {
				$categories_list .= $categories_sep;
				$categories_list .= $category->name;
			}
		} else {
			foreach ( $categories as $category ) {
				$categories_list .= $category->name;
			}
		}
		
		return $categories_list;
	}
	
	/* ------------------------------------------------------------------------- *
	 *  Определяем комментарии
	/* ------------------------------------------------------------------------- */
	function asm_markup_comments() {
		global $post;
		//$comments = array();
		$post_comments = get_comments( array( 'post_id' => $post->ID, 'status' => 'approve', 'type' => 'comment' ) );
		if ( count( $post_comments ) ) {
			$сomments       = '"comment": [ ';
			$counter        = 0;
			$сomments_total = count( $post_comments );
			foreach ( $post_comments as $comment ) {
				$description = json_encode( ( strip_tags( $comment->comment_content ) ) );
				$counter ++;
				if ( $counter == $сomments_total ) {
					$сomments .= '
			{
			"@type":"Comment",
			"dateCreated":"' . $comment->comment_date . '",
			"description":' . $description . ',
			"author": {
				"@type": "Person", 
				"name": "' . $comment->comment_author . '", 
				"url": "' . $comment->comment_author_url . '"
				}
			}';
				} else {
					$сomments .= '
			{
			"@type":"Comment",
			"dateCreated":"' . $comment->comment_date . '",
			"description":' . $description . ',
			"author": {
				"@type": "Person", 
				"name": "' . $comment->comment_author . '", 
				"url": "' . $comment->comment_author_url . '"
				}
			},';
				}
			}
			$сomments .= ' ] ';
		}
		
		return $сomments;
	}