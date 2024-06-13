<?php
/**
 * SVG Icon
 */

declare(strict_types=1);

namespace J7\PowerCourse\Templates\Components;

use J7\PowerCourse\Utils\Base;

/**
 * Class FrontEnd
 */
abstract class Icon {


	/**
	 * Fire
	 *
	 * @param array|null $props props.
	 * - type: ''
	 * - class: 'w-6 h-6'
	 * - color: '#FF4D00'
	 * @return string
	 */
	public static function fire( ?array $props = array() ): string {

		$default_props = array(
			'type'  => '',  // '
			'class' => 'w-6 h-6',
			'color' => '#FF4D00',
		);

		$props = array_merge( $default_props, $props );

		$html = sprintf(
			'<svg class="%1$s" fill="%2$s" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M12.8324 21.8013C15.9583 21.1747 20 18.926 20 13.1112C20 7.8196 16.1267 4.29593 13.3415 2.67685C12.7235 2.31757 12 2.79006 12 3.50492V5.3334C12 6.77526 11.3938 9.40711 9.70932 10.5018C8.84932 11.0607 7.92052 10.2242 7.816 9.20388L7.73017 8.36604C7.6304 7.39203 6.63841 6.80075 5.85996 7.3946C4.46147 8.46144 3 10.3296 3 13.1112C3 20.2223 8.28889 22.0001 10.9333 22.0001C11.0871 22.0001 11.2488 21.9955 11.4171 21.9858C10.1113 21.8742 8 21.064 8 18.4442C8 16.3949 9.49507 15.0085 10.631 14.3346C10.9365 14.1533 11.2941 14.3887 11.2941 14.7439V15.3331C11.2941 15.784 11.4685 16.4889 11.8836 16.9714C12.3534 17.5174 13.0429 16.9454 13.0985 16.2273C13.1161 16.0008 13.3439 15.8564 13.5401 15.9711C14.1814 16.3459 15 17.1465 15 18.4442C15 20.4922 13.871 21.4343 12.8324 21.8013Z" />
</svg>',
			$props['class'],
			$props['color']
		);

		return $html;
	}

	/**
	 * Shopping Bag
	 *
	 * @param array|null $props props.
	 * - type: ''
	 * - class: 'w-6 h-6'
	 * - color: '#1677ff'
	 * @return string
	 */
	public static function shopping_bag( ?array $props = array() ): string {
		$default_props = array(
			'type'  => '',  // '
			'class' => 'w-6 h-6',
			'color' => Base::PRIMARY_COLOR,
		);

		$props = array_merge( $default_props, $props );

		$html = sprintf(
			'<svg class="%1$s" fill="%2$s" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve">
<g>
	<g>
		<path d="M447.988,139.696c-0.156-2.084-1.9-3.696-3.988-3.696h-72v-20C372,52.036,319.96,0,256,0S140,52.036,140,116v20H68    c-2.088,0-3.832,1.612-3.988,3.696l-28,368c-0.084,1.108,0.296,2.204,1.056,3.02C37.824,511.536,38.888,512,40,512h432    c1.112,0,2.176-0.464,2.932-1.28c0.756-0.816,1.14-1.912,1.056-3.02L447.988,139.696z M172,116c0-46.316,37.68-84,84-84    s84,37.684,84,84v20H172V116z M156,248c-22.06,0-40-17.944-40-40c0-15.964,8-30.348,24-36.66V208c0,8.824,7.18,16,16,16    s16-7.176,16-16v-36.636c16,6.312,24,20.804,24,36.636C196,230.056,178.06,248,156,248z M356,248c-22.06,0-40-17.944-40-40    c0-15.964,8-30.348,24-36.66V208c0,8.824,7.18,16,16,16s16-7.176,16-16v-36.636c16,6.312,24,20.804,24,36.636    C396,230.056,378.06,248,356,248z"/>
	</g>
</g>
</svg>',
			$props['class'],
			$props['color']
		);

		return $html;
	}

	/**
	 * Shopping Bag
	 *
	 * @param array|null $props props.
	 * - type: ''
	 * - class: 'w-6 h-6'
	 * - color: '#1677ff'
	 *
	 * @return string
	 */
	public static function calendar( ?array $props = array() ): string {
		$default_props = array(
			'type'  => '',  // '
			'class' => 'w-6 h-6',
			'color' => Base::PRIMARY_COLOR,
		);

		$props = array_merge( $default_props, $props );

		$html = sprintf(
			'<svg class="%s" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<g>
<path d="M4 8H20M4 8V16.8002C4 17.9203 4 18.4801 4.21799 18.9079C4.40973 19.2842 4.71547 19.5905 5.0918 19.7822C5.5192 20 6.07899 20 7.19691 20H16.8031C17.921 20 18.48 20 18.9074 19.7822C19.2837 19.5905 19.5905 19.2842 19.7822 18.9079C20 18.4805 20 17.9215 20 16.8036V8M4 8V7.2002C4 6.08009 4 5.51962 4.21799 5.0918C4.40973 4.71547 4.71547 4.40973 5.0918 4.21799C5.51962 4 6.08009 4 7.2002 4H8M20 8V7.19691C20 6.07899 20 5.5192 19.7822 5.0918C19.5905 4.71547 19.2837 4.40973 18.9074 4.21799C18.4796 4 17.9203 4 16.8002 4H16M8 4H16M8 4V2M16 4V2M11.75 16C11.8881 16 12 15.8881 12 15.75V12.25C12 12.1119 11.8881 12 11.75 12H8.25C8.11193 12 8 12.1119 8 12.25V15.75C8 15.8881 8.11193 16 8.25 16H11.75Z" stroke="%2$s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</g>
</svg>',
			$props['class'],
			$props['color']
		);

		return $html;
	}

	/**
	 * List
	 *
	 * @param array|null $props props.
	 * - type: ''
	 * - class: 'w-6 h-6'
	 * - color: '#1677ff'
	 * @return string
	 */
	public static function list( ?array $props = array() ): string {
		$default_props = array(
			'type'  => '',  // '
			'class' => 'w-6 h-6',
			'color' => Base::PRIMARY_COLOR,
		);

		$props = array_merge( $default_props, $props );

		$html = sprintf(
			'<svg class="%s" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
  <path stroke="%2$s" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8h15M12 16h9M12 24h15"/>
  <path fill="%2$s" d="M6 10a2 2 0 100-4 2 2 0 000 4zM6 18a2 2 0 100-4 2 2 0 000 4zM6 26a2 2 0 100-4 2 2 0 000 4z"/>
</svg>',
			$props['class'],
			$props['color']
		);

		return $html;
	}


	/**
	 * Eye
	 *
	 * @param array|null $props props.
	 * - type: ''
	 * - class: 'w-6 h-6'
	 * - color: '#1677ff'
	 * @return string
	 */
	public static function eye( ?array $props = array() ): string {
		$default_props = array(
			'type'  => '',  // '
			'class' => 'w-6 h-6',
			'color' => Base::PRIMARY_COLOR,
		);

		$props = array_merge( $default_props, $props );

		$html = sprintf(
			'<svg class="%1$s" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path stroke="%2$s" d="M2.42012 12.7132C2.28394 12.4975 2.21584 12.3897 2.17772 12.2234C2.14909 12.0985 2.14909 11.9015 2.17772 11.7766C2.21584 11.6103 2.28394 11.5025 2.42012 11.2868C3.54553 9.50484 6.8954 5 12.0004 5C17.1054 5 20.4553 9.50484 21.5807 11.2868C21.7169 11.5025 21.785 11.6103 21.8231 11.7766C21.8517 11.9015 21.8517 12.0985 21.8231 12.2234C21.785 12.3897 21.7169 12.4975 21.5807 12.7132C20.4553 14.4952 17.1054 19 12.0004 19C6.8954 19 3.54553 14.4952 2.42012 12.7132Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path stroke="%2$s" d="M12.0004 15C13.6573 15 15.0004 13.6569 15.0004 12C15.0004 10.3431 13.6573 9 12.0004 9C10.3435 9 9.0004 10.3431 9.0004 12C9.0004 13.6569 10.3435 15 12.0004 15Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
			$props['class'],
			$props['color']
		);

		return $html;
	}

	/**
	 * Team
	 *
	 * @param array|null $props props.
	 * - type: ''
	 * - class: 'w-6 h-6'
	 * - color: '#1677ff'
	 * @return string
	 */
	public static function team( ?array $props = array() ): string {
		$default_props = array(
			'type'  => '',  // '
			'class' => 'w-6 h-6',
			'color' => Base::PRIMARY_COLOR,
		);

		$props = array_merge( $default_props, $props );

		$html = sprintf(
			'<svg class="%1$s" fill="%2$s" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <g>
        <path fill="none" d="M0 0h24v24H0z"/>
        <path fill-rule="nonzero" d="M12 11a5 5 0 0 1 5 5v6h-2v-6a3 3 0 0 0-2.824-2.995L12 13a3 3 0 0 0-2.995 2.824L9 16v6H7v-6a5 5 0 0 1 5-5zm-6.5 3c.279 0 .55.033.81.094a5.947 5.947 0 0 0-.301 1.575L6 16v.086a1.492 1.492 0 0 0-.356-.08L5.5 16a1.5 1.5 0 0 0-1.493 1.356L4 17.5V22H2v-4.5A3.5 3.5 0 0 1 5.5 14zm13 0a3.5 3.5 0 0 1 3.5 3.5V22h-2v-4.5a1.5 1.5 0 0 0-1.356-1.493L18.5 16c-.175 0-.343.03-.5.085V16c0-.666-.108-1.306-.309-1.904.259-.063.53-.096.809-.096zm-13-6a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zm13 0a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zm-13 2a.5.5 0 1 0 0 1 .5.5 0 0 0 0-1zm13 0a.5.5 0 1 0 0 1 .5.5 0 0 0 0-1zM12 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
    </g>
</svg>',
			$props['class'],
			$props['color']
		);

		return $html;
	}


	/**
	 * Clock
	 *
	 * @param array|null $props props.
	 * - type: ''
	 * - class: 'w-6 h-6'
	 * - color: '#1677ff'
	 * @return string
	 */
	public static function clock( ?array $props = array() ): string {
		$default_props = array(
			'type'  => '',  // '
			'class' => 'w-6 h-6',
			'color' => Base::PRIMARY_COLOR,
		);

		$props = array_merge( $default_props, $props );

		$html = sprintf(
			'<svg class="%1$s" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path stroke="%2$s" d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke-width="2"/>
<path stroke="%2$s" d="M12 7L12 11.5L12 11.5196C12 11.8197 12.15 12.1 12.3998 12.2665V12.2665L15 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
			$props['class'],
			$props['color']
		);

		return $html;
	}

	/**
	 * Star
	 *
	 * @param array|null $props props.
	 * - type: 'fill', 'half', 'outline'
	 * - class: 'w-6 h-6'
	 * - color: '#FFD700'
	 * @return string
	 */
	public static function star( ?array $props = array() ): string {

		$default_props = array(
			'type'  => 'fill',  // 'fill', 'half', 'outline'
			'class' => 'w-6 h-6',
			'color' => '#FFD700',
		);

		$props = array_merge( $default_props, $props );

		switch ( $props['type'] ) {
			case 'fill':
				return sprintf(
					'<svg class="%1$s" fill="%2$s" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
<title>star-filled</title>
<path d="M30.859 12.545c-0.168-0.506-0.637-0.864-1.189-0.864h-9.535l-2.946-9.067c-0.208-0.459-0.662-0.772-1.188-0.772s-0.981 0.313-1.185 0.764l-0.003 0.008-2.946 9.067h-9.534c-0.69 0-1.25 0.56-1.25 1.25 0 0.414 0.202 0.782 0.512 1.009l0.004 0.002 7.713 5.603-2.946 9.068c-0.039 0.116-0.061 0.249-0.061 0.387 0 0.69 0.56 1.25 1.25 1.25 0.276 0 0.531-0.089 0.738-0.241l-0.004 0.002 7.714-5.605 7.713 5.605c0.203 0.149 0.458 0.238 0.734 0.238 0.691 0 1.251-0.56 1.251-1.251 0-0.138-0.022-0.271-0.064-0.395l0.003 0.009-2.947-9.066 7.715-5.604c0.314-0.231 0.515-0.598 0.515-1.013 0-0.137-0.022-0.27-0.063-0.393l0.003 0.009z"/>
</svg>',
					$props['class'],
					$props['color']
				);
			break;

			case 'half':
				return sprintf(
					'<svg class="%1$s" fill="%2$s" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
<title>star-half-stroke-filled</title>
<path d="M30.859 12.545c-0.168-0.506-0.637-0.864-1.189-0.864h-9.535l-2.946-9.067c-0.168-0.505-0.636-0.863-1.188-0.863-0.138 0-0.272 0.023-0.396 0.064l0.009-0.003c-0.376 0.13-0.664 0.427-0.779 0.8l-0.002 0.009-0.021-0.007-2.946 9.067h-9.534c-0.69 0-1.25 0.56-1.25 1.25 0 0.414 0.202 0.782 0.512 1.009l0.004 0.002 7.713 5.603-2.946 9.068c-0.039 0.116-0.061 0.249-0.061 0.387 0 0.69 0.56 1.25 1.25 1.25 0.276 0 0.531-0.089 0.738-0.241l-0.004 0.002 7.714-5.605 7.713 5.605c0.203 0.149 0.458 0.238 0.734 0.238 0.691 0 1.251-0.56 1.251-1.251 0-0.138-0.022-0.271-0.064-0.395l0.003 0.009-2.947-9.066 7.715-5.604c0.314-0.231 0.515-0.598 0.515-1.013 0-0.137-0.022-0.27-0.063-0.393l0.003 0.009zM20.486 18.057c-0.314 0.231-0.515 0.599-0.515 1.014 0 0.137 0.022 0.27 0.063 0.394l-0.003-0.009 2.039 6.271-5.336-3.877c-0.194-0.135-0.435-0.215-0.694-0.215-0.014 0-0.028 0-0.042 0.001l0.002-0v-14.589l2.037 6.272c0.169 0.505 0.637 0.863 1.189 0.863h6.596z"/>
</svg>',
					$props['class'],
					$props['color']
				);
			break;

			case 'outline':
				return sprintf(
					'<svg class="%1$s" fill="%2$s" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
<title>star</title>
<path d="M30.859 12.545c-0.168-0.506-0.637-0.864-1.189-0.864h-9.535l-2.946-9.067c-0.208-0.459-0.662-0.772-1.188-0.772s-0.981 0.313-1.185 0.764l-0.003 0.008-2.946 9.067h-9.534c-0.69 0-1.25 0.56-1.25 1.25 0 0.414 0.202 0.782 0.512 1.009l0.004 0.002 7.713 5.603-2.946 9.068c-0.039 0.116-0.061 0.249-0.061 0.387 0 0.69 0.56 1.25 1.25 1.25 0.276 0 0.531-0.089 0.738-0.241l-0.004 0.002 7.714-5.605 7.713 5.605c0.203 0.149 0.458 0.238 0.734 0.238 0.691 0 1.251-0.56 1.251-1.251 0-0.138-0.022-0.271-0.064-0.395l0.003 0.009-2.947-9.066 7.715-5.604c0.314-0.231 0.515-0.598 0.515-1.013 0-0.137-0.022-0.27-0.063-0.393l0.003 0.009zM20.486 18.057c-0.314 0.231-0.515 0.599-0.515 1.014 0 0.137 0.022 0.27 0.063 0.394l-0.003-0.009 2.039 6.271-5.336-3.877c-0.203-0.149-0.458-0.238-0.734-0.238s-0.531 0.089-0.738 0.241l0.004-0.002-5.336 3.877 2.038-6.271c0.039-0.116 0.062-0.249 0.062-0.387 0-0.414-0.202-0.781-0.512-1.009l-0.004-0.002-5.335-3.876h6.595c0 0 0 0 0.001 0 0.552 0 1.020-0.358 1.185-0.854l0.003-0.009 2.038-6.272 2.037 6.272c0.169 0.505 0.637 0.863 1.189 0.863h6.596z"/>
</svg>',
					$props['class'],
					$props['color']
				);
			break;

			default:
				return sprintf(
					'<svg class="%1$s" fill="%2$s" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
<title>star-filled</title>
<path d="M30.859 12.545c-0.168-0.506-0.637-0.864-1.189-0.864h-9.535l-2.946-9.067c-0.208-0.459-0.662-0.772-1.188-0.772s-0.981 0.313-1.185 0.764l-0.003 0.008-2.946 9.067h-9.534c-0.69 0-1.25 0.56-1.25 1.25 0 0.414 0.202 0.782 0.512 1.009l0.004 0.002 7.713 5.603-2.946 9.068c-0.039 0.116-0.061 0.249-0.061 0.387 0 0.69 0.56 1.25 1.25 1.25 0.276 0 0.531-0.089 0.738-0.241l-0.004 0.002 7.714-5.605 7.713 5.605c0.203 0.149 0.458 0.238 0.734 0.238 0.691 0 1.251-0.56 1.251-1.251 0-0.138-0.022-0.271-0.064-0.395l0.003 0.009-2.947-9.066 7.715-5.604c0.314-0.231 0.515-0.598 0.515-1.013 0-0.137-0.022-0.27-0.063-0.393l0.003 0.009z"/>
</svg>',
					$props['class'],
					$props['color']
				);
			break;
		}

		return $html;
	}
}
