<?php
/*
Plugin Name: VDZ Yandex Share Plugin
Plugin URI:  http://online-services.org.ua
Description: Простое добавление шеринга Яндекс Share на свой сайт
Version:     1.2.7
Author:      VadimZ
Author URI:  http://online-services.org.ua#vdz-yandex-share
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VDZ_YS_API', 'vdz_info_yandex_share' );

require_once 'api.php';
require_once 'updated_plugin_admin_notices.php';

// Код активации плагина
register_activation_hook( __FILE__, 'vdz_ys_activate_plugin' );
function vdz_ys_activate_plugin() {
	global $wp_version;
	if ( version_compare( $wp_version, '3.8', '<' ) ) {
		// Деактивируем плагин
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'This plugin required WordPress version 3.8 or higher' );
	}
	add_option( 'vdz_yandex_share_front_show', 1 );

	do_action( VDZ_YS_API, 'on', plugin_basename( __FILE__ ) );
}

// Код деактивации плагина
register_deactivation_hook( __FILE__, function () {
	$plugin_name = preg_replace( '|\/(.*)|', '', plugin_basename( __FILE__ ));
	$response = wp_remote_get( "http://api.online-services.org.ua/off/{$plugin_name}" );
	if ( ! is_wp_error( $response ) && isset( $response['body'] ) && ( json_decode( $response['body'] ) !== null ) ) {
		//TODO Вывод сообщения для пользователя
	}
} );
//Сообщение при отключении плагина
add_action( 'admin_init', function (){
	if(is_admin()){
		$plugin_data = get_plugin_data(__FILE__);
		$plugin_slug    = isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : sanitize_title( $plugin_data['Name'] );
		$plugin_id_attr = $plugin_slug;
		$plugin_name = isset($plugin_data['Name']) ? $plugin_data['Name'] : ' us';
		$plugin_dir_name = preg_replace( '|\/(.*)|', '', plugin_basename( __FILE__ ));
		$handle = 'admin_'.$plugin_dir_name;
		wp_register_script( $handle, '', null, false, true );
		wp_enqueue_script( $handle );
		$msg = '';
		if ( function_exists( 'get_locale' ) && in_array( get_locale(), array( 'uk', 'ru_RU' ), true ) ) {
			$msg .= "Спасибо, что были с нами! ({$plugin_name}) Хорошего дня!";
		}else{
			$msg .= "Thanks for your time with us! ({$plugin_name}) Have a nice day!";
		}
		if(substr_count( $_SERVER['REQUEST_URI'], 'plugins.php')){
			wp_add_inline_script( $handle, "if(document.getElementById('deactivate-".esc_attr($plugin_id_attr)."')){document.getElementById('deactivate-".esc_attr($plugin_id_attr)."').onclick=function (e){alert('".esc_attr( $msg )."');}}" );
		}
	}
} );



/*Добавляем новые поля для в настройках шаблона шаблона для верификации сайта*/
function vdz_ys_theme_customizer( $wp_customize ) {

	if ( ! class_exists( 'WP_Customize_Control' ) ) {
		exit;
	}

	// Добавляем секцию для идетнтификатора YS
	$wp_customize->add_section(
		'vdz_yandex_share_section',
		array(
			'title'    => __( 'VDZ Yandex Share' ),
			'priority' => 10,
		// 'description' => __( 'Yandex Share code on your site' ),
		)
	);
	// Добавляем настройки
	$wp_customize->add_setting(
		'vdz_yandex_share_front_show',
		array(
			'type'              => 'option',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// Footer OR HEAD
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'vdz_yandex_share_front_show',
			array(
				'label'       => __( 'VDZ Yandex Share' ),
				'section'     => 'vdz_yandex_share_section',
				'settings'    => 'vdz_yandex_share_front_show',
				'type'        => 'select',
				'description' => __( 'ON/OFF' ),
				'choices'     => array(
					1 => __( 'Show' ),
					0 => __( 'Hide' ),
				),
			)
		)
	);

	// Добавляем ссылку на сайт
	$wp_customize->add_setting(
		'vdz_yandex_share_link',
		array(
			'type' => 'option',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'vdz_yandex_share_link',
			array(
				// 'label'    => __( 'Link' ),
									'section' => 'vdz_yandex_share_section',
				'settings'                    => 'vdz_yandex_share_link',
				'type'                        => 'hidden',
				'description'                 => '<br/><a href="//online-services.org.ua#vdz-yandex-share" target="_blank">VadimZ</a>',
			)
		)
	);
}
add_action( 'customize_register', 'vdz_ys_theme_customizer', 1 );


add_filter(
	'the_content',
	function ( $content ) {
		if ( ! (int) get_option( 'vdz_yandex_share_front_show' ) ) {
			return $content;
		}
		$share = '<div class="ya-share2" data-curtain data-shape="round" data-services="facebook,linkedin,vkontakte,twitter,telegram,viber,whatsapp,skype" data-description="' . get_the_title() . '"></div>';
		return $content . PHP_EOL . $share;
	},
	1000,
	1
);


// Добавляем допалнительную ссылку настроек на страницу всех плагинов
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=vdz_yandex_share_section' ) ) . '">' . esc_html__( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		array_walk( $links, 'wp_kses_post' );
		return $links;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'vdz_ys', 'https://yastatic.net/share2/share.js', null, false, true );
	}
);

