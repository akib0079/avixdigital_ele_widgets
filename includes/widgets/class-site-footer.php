<?php
/**
 * Site Footer: CTA, brand, link columns, contact, wordmark and legal bar.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

class Site_Footer extends Widget_Base {

	const LOGO_LIGHT = 'https://avixdigital.com/wp-content/uploads/2026/05/Untitled-design244.png';
	const LOGO_DARK  = 'https://avixdigital.com/wp-content/uploads/2024/09/Untitled-design198.png';

	/**
	 * Social marks: Simple Icons (CC0); LinkedIn from Bootstrap Icons (MIT).
	 * Fiverr is drawn as its "fi" roundel, its wordmark is unreadable this small.
	 */
	const SOCIALS = array(
		'fiverr' => array(
			'label' => 'Fiverr',
			'box'   => '0 0 24 24',
			'path'  => '',
		),
		'upwork' => array(
			'label' => 'Upwork',
			'box'   => '0 0 24 24',
			'path'  => 'M18.561 13.158c-1.102 0-2.135-.467-3.074-1.227l.228-1.076.008-.042c.207-1.143.849-3.06 2.839-3.06 1.492 0 2.703 1.212 2.703 2.703-.001 1.489-1.212 2.702-2.704 2.702zm0-8.14c-2.539 0-4.51 1.649-5.31 4.366-1.22-1.834-2.148-4.036-2.687-5.892H7.828v7.112c-.002 1.406-1.141 2.546-2.547 2.548-1.405-.002-2.543-1.143-2.545-2.548V3.492H0v7.112c0 2.914 2.37 5.303 5.281 5.303 2.913 0 5.283-2.389 5.283-5.303v-1.19c.529 1.107 1.182 2.229 1.974 3.221l-1.673 7.873h2.797l1.213-5.71c1.063.679 2.285 1.109 3.686 1.109 3 0 5.439-2.452 5.439-5.45 0-3-2.439-5.439-5.439-5.439z',
		),
		'facebook' => array(
			'label' => 'Facebook',
			'box'   => '0 0 24 24',
			'path'  => 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z',
		),
		'instagram' => array(
			'label' => 'Instagram',
			'box'   => '0 0 24 24',
			'path'  => 'M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077',
		),
		'linkedin' => array(
			'label' => 'LinkedIn',
			'box'   => '0 0 16 16',
			'path'  => 'M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z',
		),
		'x' => array(
			'label' => 'X (Twitter)',
			'box'   => '0 0 24 24',
			'path'  => 'M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z',
		),
		'dribbble' => array(
			'label' => 'Dribbble',
			'box'   => '0 0 24 24',
			'path'  => 'M12 24C5.385 24 0 18.615 0 12S5.385 0 12 0s12 5.385 12 12-5.385 12-12 12zm10.12-10.358c-.35-.11-3.17-.953-6.384-.438 1.34 3.684 1.887 6.684 1.992 7.308 2.3-1.555 3.936-4.02 4.395-6.87zm-6.115 7.808c-.153-.9-.75-4.032-2.19-7.77l-.066.02c-5.79 2.015-7.86 6.025-8.04 6.4 1.73 1.358 3.92 2.166 6.29 2.166 1.42 0 2.77-.29 4-.814zm-11.62-2.58c.232-.4 3.045-5.055 8.332-6.765.135-.045.27-.084.405-.12-.26-.585-.54-1.167-.832-1.74C7.17 11.775 2.206 11.71 1.756 11.7l-.004.312c0 2.633.998 5.037 2.634 6.855zm-2.42-8.955c.46.008 4.683.026 9.477-1.248-1.698-3.018-3.53-5.558-3.8-5.928-2.868 1.35-5.01 3.99-5.676 7.17zM9.6 2.052c.282.38 2.145 2.914 3.822 6 3.645-1.365 5.19-3.44 5.373-3.702-1.81-1.61-4.19-2.586-6.795-2.586-.825 0-1.63.1-2.4.285zm10.335 3.483c-.218.29-1.935 2.493-5.724 4.04.24.49.47.985.68 1.486.08.18.15.36.22.53 3.41-.43 6.8.26 7.14.33-.02-2.42-.88-4.64-2.31-6.38z',
		),
		'behance' => array(
			'label' => 'Behance',
			'box'   => '0 0 24 24',
			'path'  => 'M16.969 16.927a2.561 2.561 0 0 0 1.901.677 2.501 2.501 0 0 0 1.531-.475c.362-.235.636-.584.779-.99h2.585a5.091 5.091 0 0 1-1.9 2.896 5.292 5.292 0 0 1-3.091.88 5.839 5.839 0 0 1-2.284-.433 4.871 4.871 0 0 1-1.723-1.211 5.657 5.657 0 0 1-1.08-1.874 7.057 7.057 0 0 1-.383-2.393c-.005-.8.129-1.595.396-2.349a5.313 5.313 0 0 1 5.088-3.604 4.87 4.87 0 0 1 2.376.563c.661.362 1.231.87 1.668 1.485a6.2 6.2 0 0 1 .943 2.133c.194.821.263 1.666.205 2.508h-7.699c-.063.79.184 1.574.688 2.187ZM6.947 4.084a8.065 8.065 0 0 1 1.928.198 4.29 4.29 0 0 1 1.49.638c.418.303.748.711.958 1.182.241.579.357 1.203.341 1.83a3.506 3.506 0 0 1-.506 1.961 3.726 3.726 0 0 1-1.503 1.287 3.588 3.588 0 0 1 2.027 1.437c.464.747.697 1.615.67 2.494a4.593 4.593 0 0 1-.423 2.032 3.945 3.945 0 0 1-1.163 1.413 5.114 5.114 0 0 1-1.683.807 7.135 7.135 0 0 1-1.928.259H0V4.084h6.947Zm-.235 12.9c.308.004.616-.029.916-.099a2.18 2.18 0 0 0 .766-.332c.228-.158.411-.371.534-.619.142-.317.208-.663.191-1.009a2.08 2.08 0 0 0-.642-1.715 2.618 2.618 0 0 0-1.696-.505h-3.54v4.279h3.471Zm13.635-5.967a2.13 2.13 0 0 0-1.654-.619 2.336 2.336 0 0 0-1.163.259 2.474 2.474 0 0 0-.738.62 2.359 2.359 0 0 0-.396.792c-.074.239-.12.485-.137.734h4.769a3.239 3.239 0 0 0-.679-1.785l-.002-.001Zm-13.813-.648a2.254 2.254 0 0 0 1.423-.433c.399-.355.607-.88.56-1.413a1.916 1.916 0 0 0-.178-.891 1.298 1.298 0 0 0-.495-.533 1.851 1.851 0 0 0-.711-.274 3.966 3.966 0 0 0-.835-.073H3.241v3.631h3.293v-.014ZM21.62 5.122h-5.976v1.527h5.976V5.122Z',
		),
		'github' => array(
			'label' => 'GitHub',
			'box'   => '0 0 24 24',
			'path'  => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
		),
		'youtube' => array(
			'label' => 'YouTube',
			'box'   => '0 0 24 24',
			'path'  => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
		),
		'threads' => array(
			'label' => 'Threads',
			'box'   => '0 0 24 24',
			'path'  => 'M18.263 11.097c-.03-3.486-1.92-5.586-5.111-5.586-2.13 0-3.922.963-4.863 2.499l2.062 1.438c.535-.843 1.272-1.543 2.628-1.543 1.528 0 2.318.85 2.544 2.431a15 15 0 0 0-2.236-.173c-4.125 0-6.068 1.867-6.068 4.336s1.943 3.99 4.804 3.99c3.139 0 5.013-2.115 5.781-4.735.798.361 1.348 1.204 1.348 2.47 0 3.387-3.907 5.232-7.22 5.232-4.885 0-8.077-3.207-8.077-8.424 0-6.392 4.223-10.487 9.9-10.487 3.808 0 5.69 1.671 6.97 3.914l2.108-1.475C21.44 2.078 18.331 0 13.663 0 6.227 0 1.168 5.277 1.168 12.934c0 7 4.953 11.066 10.856 11.066 4.878 0 9.809-2.846 9.809-7.716 0-2.545-1.46-4.231-3.569-5.187m-6.33 4.855c-1.077 0-2.026-.512-2.026-1.453 0-1.483 1.822-1.934 3.606-1.934.678 0 1.34.045 1.927.173-.422 1.927-1.671 3.215-3.508 3.214Z',
		),
		'whatsapp' => array(
			'label' => 'WhatsApp',
			'box'   => '0 0 24 24',
			'path'  => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z',
		),
	);

	public function get_name(): string {
		return 'avix-site-footer';
	}

	public function get_title(): string {
		return esc_html__( 'Site Footer', 'avix-widgets' );
	}

	public function get_icon(): string {
		return 'eicon-footer';
	}

	public function get_categories(): array {
		return array( 'avix-digital' );
	}

	public function get_keywords(): array {
		return array( 'footer', 'cta', 'contact', 'links', 'social', 'avix' );
	}

	public function get_style_depends(): array {
		return array( 'avix-site-footer' );
	}

	public function get_script_depends(): array {
		return array( 'avix-site-footer' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Controls                                                            */
	/* ------------------------------------------------------------------ */

	protected function register_controls(): void {
		$this->controls_cta();
		$this->controls_brand();
		$this->controls_link_column( 1, esc_html__( 'Services', 'avix-widgets' ), $this->default_services() );
		$this->controls_link_column( 2, esc_html__( 'Navigation', 'avix-widgets' ), $this->default_navigation() );
		$this->controls_contact();
		$this->controls_bottom();
		$this->controls_style();
	}

	private function controls_cta() {
		$this->start_controls_section( 'section_cta', array( 'label' => esc_html__( 'Call to Action', 'avix-widgets' ) ) );

		$this->add_control(
			'show_cta',
			array(
				'label'   => esc_html__( 'Show', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'cta_title',
			array(
				'label'       => esc_html__( 'Headline', 'avix-widgets' ),
				'description' => esc_html__( 'Press Enter for a new line.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => "Got a project in mind?\nLet’s build it together.",
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_title_tag',
			array(
				'label'     => esc_html__( 'Headline tag', 'avix-widgets' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'div' => 'div',
					'p'   => 'p',
				),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'show_buddy',
			array(
				'label'       => esc_html__( 'Pixel buddy on the headline', 'avix-widgets' ),
				'description' => esc_html__( 'The Avix pixel character sits on the headline, swings its legs and waves when the button is hovered.', 'avix-widgets' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'buddy_word',
			array(
				'label'       => esc_html__( 'Sits on the word', 'avix-widgets' ),
				'description' => esc_html__( 'A word from the first line keeps it on top at every screen size.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Got',
				'condition'   => array(
					'show_cta'   => 'yes',
					'show_buddy' => 'yes',
				),
			)
		);

		$this->add_control(
			'buddy_x',
			array(
				'label'      => esc_html__( 'Position on the word', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .avix-ft' => '--ft-buddy-x: {{SIZE}}%;' ),
				'condition'  => array(
					'show_cta'   => 'yes',
					'show_buddy' => 'yes',
				),
			)
		);

		$this->add_control(
			'buddy_nudge',
			array(
				'label'       => esc_html__( 'Height adjustment', 'avix-widgets' ),
				'description' => esc_html__( 'Fine-tune if it floats or sinks with another font.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'em' ),
				'range'       => array( 'em' => array( 'min' => -0.3, 'max' => 0.3, 'step' => 0.01 ) ),
				'default'     => array( 'unit' => 'em', 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .avix-ft' => '--ft-buddy-nudge: {{SIZE}}em;' ),
				'condition'   => array(
					'show_cta'   => 'yes',
					'show_buddy' => 'yes',
				),
			)
		);

		$this->add_control(
			'cta_button_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Book a Free Audit', 'avix-widgets' ),
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_button_link',
			array(
				'label'     => esc_html__( 'Button link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'default'   => array( 'url' => 'https://avixdigital.com/contact/' ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_lead',
			array(
				'label'     => esc_html__( 'Supporting text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => 'Transforming premium brands with cutting-edge UI/UX and advanced coding. Partner with Avix Digital for your next custom Shopify, Webflow, or WordPress build.',
				'dynamic'   => array( 'active' => true ),
				'separator' => 'before',
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_alt_prefix',
			array(
				'label'     => esc_html__( 'Secondary line text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Already working with us?', 'avix-widgets' ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_alt_text',
			array(
				'label'     => esc_html__( 'Secondary link text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Client Portal', 'avix-widgets' ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_alt_link',
			array(
				'label'     => esc_html__( 'Secondary link', 'avix-widgets' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'default'   => array( 'url' => 'https://admin.avixdigital.com/' ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_brand() {
		$this->start_controls_section( 'section_brand', array( 'label' => esc_html__( 'Brand & Social', 'avix-widgets' ) ) );

		$this->add_control(
			'logo',
			array(
				'label'       => esc_html__( 'Logo · dark style', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => self::LOGO_LIGHT ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'logo_on_light',
			array(
				'label'   => esc_html__( 'Logo · light style', 'avix-widgets' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => self::LOGO_DARK ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'logo_link',
			array(
				'label'   => esc_html__( 'Logo link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '/' ),
			)
		);

		$this->add_control(
			'about',
			array(
				'label'   => esc_html__( 'Short description', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => 'Avix Digital is a premium development agency focused on high-converting storefronts, bespoke UI/UX design, and scalable custom web architectures.',
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'social_label',
			array(
				'label'     => esc_html__( 'Social label', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Follow us on:', 'avix-widgets' ),
				'separator' => 'before',
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'network',
			array(
				'label'   => esc_html__( 'Network', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'instagram',
				'options' => wp_list_pluck( self::SOCIALS, 'label' ),
			)
		);
		$repeater->add_control(
			'url',
			array(
				'label'   => esc_html__( 'Profile URL', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'default' => array(
					'url'         => '',
					'is_external' => 'on',
				),
			)
		);

		$this->add_control(
			'socials',
			array(
				'label'       => esc_html__( 'Profiles', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ network }}}',
				'default'     => array(
					array(
						'network' => 'fiverr',
						'url'     => array( 'url' => 'https://www.fiverr.com/s/o85mG6x', 'is_external' => 'on' ),
					),
					array(
						'network' => 'upwork',
						'url'     => array( 'url' => 'https://www.upwork.com/freelancers/akibzawayed', 'is_external' => 'on' ),
					),
					array(
						'network' => 'facebook',
						'url'     => array( 'url' => 'https://www.facebook.com/share/17oaLGqFHH/?mibextid=wwXIfr', 'is_external' => 'on' ),
					),
					array(
						'network' => 'instagram',
						'url'     => array( 'url' => 'https://www.instagram.com/avixdigital_agency/', 'is_external' => 'on' ),
					),
					array(
						'network' => 'threads',
						'url'     => array( 'url' => 'https://www.threads.com/@avixdigital_agency', 'is_external' => 'on' ),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Two link columns share one control set: custom links or a WordPress menu.
	 */
	private function controls_link_column( $n, $title, array $defaults ) {
		$p = 'col' . $n . '_';
		/* translators: %d: column number. */
		$this->start_controls_section( 'section_' . $p, array( 'label' => sprintf( esc_html__( 'Link Column %d', 'avix-widgets' ), $n ) ) );

		$this->add_control(
			$p . 'title',
			array(
				'label'   => esc_html__( 'Heading', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => $title,
			)
		);

		$menus = array( '' => esc_html__( '— Select a menu —', 'avix-widgets' ) );
		foreach ( wp_get_nav_menus() as $menu ) {
			$menus[ $menu->term_id ] = $menu->name;
		}

		$this->add_control(
			$p . 'source',
			array(
				'label'   => esc_html__( 'Links from', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => array(
					'custom' => esc_html__( 'Custom links', 'avix-widgets' ),
					'menu'   => esc_html__( 'WordPress menu', 'avix-widgets' ),
				),
			)
		);

		$this->add_control(
			$p . 'menu',
			array(
				'label'       => esc_html__( 'Menu', 'avix-widgets' ),
				'description' => esc_html__( 'Top-level items are shown. Edit them in Appearance → Menus.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $menus,
				'condition'   => array( $p . 'source' => 'menu' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Link', 'avix-widgets' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'avix-widgets' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			$p . 'links',
			array(
				'label'       => esc_html__( 'Links', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => $defaults,
				'condition'   => array( $p . 'source' => 'custom' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_contact() {
		$this->start_controls_section( 'section_contact', array( 'label' => esc_html__( 'Contact Column', 'avix-widgets' ) ) );

		$this->add_control(
			'contact_title',
			array(
				'label'   => esc_html__( 'Heading', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Direct contact', 'avix-widgets' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'type',
			array(
				'label'   => esc_html__( 'Type', 'avix-widgets' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'email',
				'options' => array(
					'email'    => esc_html__( 'Email (with copy button)', 'avix-widgets' ),
					'phone'    => esc_html__( 'Phone', 'avix-widgets' ),
					'whatsapp' => esc_html__( 'WhatsApp', 'avix-widgets' ),
					'link'     => esc_html__( 'Other link', 'avix-widgets' ),
					'text'     => esc_html__( 'Plain text (e.g. address)', 'avix-widgets' ),
				),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label' => esc_html__( 'Small label', 'avix-widgets' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
		$repeater->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Shown text', 'avix-widgets' ),
				'description' => esc_html__( 'Email address, phone number, or text.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Email and phone links are created automatically.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'contacts',
			array(
				'label'       => esc_html__( 'Items', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}} {{{ value }}}',
				'default'     => array(
					array(
						'type'  => 'whatsapp',
						'label' => 'WhatsApp',
						'value' => '(+880) 1904 187508',
						'link'  => array( 'url' => 'https://wa.link/moetob', 'is_external' => 'on' ),
					),
					array(
						'type'  => 'phone',
						'label' => 'Call',
						'value' => '(+880) 1904 187508',
					),
					array(
						'type'  => 'email',
						'label' => 'Email',
						'value' => 'avixdigitalagency@gmail.com',
					),
					array(
						'type'  => 'email',
						'label' => 'Email',
						'value' => 'info@avixdigital.com',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_bottom() {
		$this->start_controls_section( 'section_bottom', array( 'label' => esc_html__( 'Wordmark, Badges & Bottom Bar', 'avix-widgets' ) ) );

		$this->add_control(
			'show_mark',
			array(
				'label'   => esc_html__( 'Big wordmark', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'mark',
			array(
				'label'       => esc_html__( 'Wordmark image', 'avix-widgets' ),
				'description' => esc_html__( 'Light logo on transparent background. An SVG stays sharpest at this size.', 'avix-widgets' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => self::LOGO_LIGHT ),
				'condition'   => array( 'show_mark' => 'yes' ),
			)
		);

		$badges = new Repeater();
		$badges->add_control(
			'image',
			array(
				'label' => esc_html__( 'Badge image', 'avix-widgets' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$badges->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Name (for screen readers)', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'Shopify Partner',
			)
		);
		$badges->add_control(
			'zoom',
			array(
				'label'       => esc_html__( 'Zoom', 'avix-widgets' ),
				'description' => esc_html__( 'For images with lots of empty space around the badge.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 60, 'max' => 250 ) ),
				'default'     => array( 'unit' => '%', 'size' => 100 ),
			)
		);
		$badges->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Proof link (optional)', 'avix-widgets' ),
				'description' => esc_html__( 'Your public partner directory profile, so visitors can verify it.', 'avix-widgets' ),
				'type'        => Controls_Manager::URL,
			)
		);

		$this->add_control(
			'badges',
			array(
				'label'       => esc_html__( 'Partner badges', 'avix-widgets' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $badges->get_controls(),
				'title_field' => '{{{ name }}}',
				'separator'   => 'before',
				'default'     => array(
					array(
						'image' => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/09/jZfxvFmbpRDqIBZd8yCfHG8A.avif' ),
						'name'  => 'Meta Business Partner',
					),
					array(
						'image' => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/09/AOV2DTNHK6JgQPDMFMSruGw.avif' ),
						'name'  => 'Google Partner',
					),
					array(
						'image' => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/09/images-3.jpeg' ),
						'name'  => 'Shopify Partner',
					),
					array(
						'image' => array( 'url' => 'https://avixdigital.com/wp-content/uploads/2026/09/images-5.png' ),
						'name'  => 'WordPress Partner',
						'zoom'  => array( 'unit' => '%', 'size' => 165 ),
					),
				),
			)
		);

		$this->add_control(
			'small_print',
			array(
				'label'   => esc_html__( 'Small print', 'avix-widgets' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => 'Partner badges, platform names and logos are trademarks of their respective owners and are shown to identify the platforms Avix Digital works with.',
			)
		);

		$this->add_control(
			'copyright',
			array(
				'label'       => esc_html__( 'Copyright', 'avix-widgets' ),
				'description' => esc_html__( '{year} becomes the current year.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '© {year} Avix Digital. All Rights Reserved.',
				'label_block' => true,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'show_top',
			array(
				'label'   => esc_html__( 'Back to top button', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'top_text',
			array(
				'label'     => esc_html__( 'Button text', 'avix-widgets' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Back to top', 'avix-widgets' ),
				'condition' => array( 'show_top' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style() {
		$this->start_controls_section(
			'style_card',
			array(
				'label' => esc_html__( 'Card', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'theme',
			array(
				'label'   => esc_html__( 'Style', 'avix-widgets' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'dark',
				'toggle'  => false,
				'options' => array(
					'dark'  => array(
						'title' => esc_html__( 'Dark', 'avix-widgets' ),
						'icon'  => 'eicon-circle',
					),
					'light' => array(
						'title' => esc_html__( 'Light', 'avix-widgets' ),
						'icon'  => 'eicon-circle-o',
					),
				),
			)
		);

		$this->add_control(
			'theme_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Colours below override the chosen style. Leave them empty to use the Avix defaults for dark or light.', 'avix-widgets' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$colors = array(
			'ft_bg'     => array( esc_html__( 'Background', 'avix-widgets' ), '--ft-bg' ),
			'ft_accent' => array( esc_html__( 'Accent', 'avix-widgets' ), '--ft-accent' ),
			'ft_ink'    => array( esc_html__( 'Headings', 'avix-widgets' ), '--ft-ink' ),
			'ft_soft'   => array( esc_html__( 'Links', 'avix-widgets' ), '--ft-soft' ),
			'ft_muted'  => array( esc_html__( 'Secondary text', 'avix-widgets' ), '--ft-muted' ),
			'ft_line'   => array( esc_html__( 'Lines', 'avix-widgets' ), '--ft-line' ),
		);
		foreach ( $colors as $key => $color ) {
			$this->add_control(
				$key,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .avix-ft' => $color[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'show_lines',
			array(
				'label'     => esc_html__( 'Column lines', 'avix-widgets' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_glow',
			array(
				'label'   => esc_html__( 'Orange glow', 'avix-widgets' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_responsive_control(
			'outer_space',
			array(
				'label'      => esc_html__( 'Space around card', 'avix-widgets' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( '{{WRAPPER}} .avix-ft' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'avix-widgets' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 64 ) ),
				'selectors'  => array( '{{WRAPPER}} .avix-ft' => '--ft-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'width_mode',
			array(
				'label'       => esc_html__( 'Content width', 'avix-widgets' ),
				'description' => esc_html__( 'Match the header: the footer lines its content up with the header’s logo and last button, on every screen size.', 'avix-widgets' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'header',
				'options'     => array(
					'header' => esc_html__( 'Match the site header', 'avix-widgets' ),
					'boxed'  => esc_html__( 'Fixed max width', 'avix-widgets' ),
				),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'header_selector',
			array(
				'label'       => esc_html__( 'Header selector', 'avix-widgets' ),
				'description' => esc_html__( 'CSS selector of your header. Common theme headers are found automatically if this one is missing.', 'avix-widgets' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '#avix-smart-header',
				'condition'   => array( 'width_mode' => 'header' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'       => esc_html__( 'Max width', 'avix-widgets' ),
				'description' => esc_html__( 'Also used if the header can’t be found.', 'avix-widgets' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 800, 'max' => 1600 ) ),
				'selectors'   => array( '{{WRAPPER}} .avix-ft' => '--ft-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_type',
			array(
				'label' => esc_html__( 'Typography', 'avix-widgets' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Headline', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-ft .avix-ft__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'link_typography',
				'label'    => esc_html__( 'Links', 'avix-widgets' ),
				'selector' => '{{WRAPPER}} .avix-ft .avix-ft__link, {{WRAPPER}} .avix-ft .avix-ft__text',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	protected function render(): void {
		$s = $this->get_settings_for_display();

		$classes = array( 'avix-ft', 'light' === $s['theme'] ? 'avix-ft--light' : 'avix-ft--dark' );
		if ( 'yes' !== $s['show_lines'] ) {
			$classes[] = 'avix-ft--no-lines';
		}
		if ( 'yes' !== $s['show_glow'] ) {
			$classes[] = 'avix-ft--no-glow';
		}
		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-avix-ft'  => '',
			)
		);
		if ( 'boxed' !== ( $s['width_mode'] ?? 'header' ) ) {
			$this->add_render_attribute( 'root', 'data-ft-match', trim( (string) ( $s['header_selector'] ?? '' ) ) );
		}
		?>
		<footer <?php $this->print_render_attribute_string( 'root' ); ?>>
			<div class="avix-ft__card">
				<div class="avix-ft__lines" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
				<div class="avix-ft__glow" aria-hidden="true"></div>

				<div class="avix-ft__frame">
					<?php
					if ( 'yes' === $s['show_cta'] ) {
						$this->render_cta( $s );
					}
					?>

					<div class="avix-ft__grid">
						<?php $this->render_brand( $s ); ?>
						<?php $this->render_link_column( $s, 1 ); ?>
						<?php $this->render_link_column( $s, 2 ); ?>
						<?php $this->render_contact( $s ); ?>
					</div>

					<?php if ( 'yes' === $s['show_mark'] && ! empty( $s['mark']['url'] ) ) : ?>
						<div class="avix-ft__mark" aria-hidden="true">
							<img src="<?php echo esc_url( $s['mark']['url'] ); ?>" alt="" loading="lazy" decoding="async">
						</div>
					<?php endif; ?>

					<?php $this->render_bottom( $s ); ?>
				</div>
				<p class="avix-ft__sr" data-ft-status aria-live="polite" data-copied="<?php esc_attr_e( 'Copied', 'avix-widgets' ); ?>"></p>
			</div>
		</footer>
		<?php
	}

	private function render_cta( array $s ) {
		$tag   = Utils::validate_html_tag( $s['cta_title_tag'] );
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $s['cta_title'] ) ), 'strlen' ) );
		?>
		<section class="avix-ft__cta">
			<?php if ( $lines ) : ?>
				<<?php echo esc_attr( $tag ); ?> class="avix-ft__title">
					<?php echo $this->title_html( $lines, 'yes' === $s['show_buddy'] ? (string) $s['buddy_word'] : null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in title_html(). ?>
				</<?php echo esc_attr( $tag ); ?>>
			<?php endif; ?>

			<?php
			if ( '' !== trim( (string) $s['cta_button_text'] ) && ! empty( $s['cta_button_link']['url'] ) ) :
				$this->add_link_attributes( 'cta-button', $s['cta_button_link'] );
				$this->add_render_attribute( 'cta-button', 'class', 'avix-ft__pill' );
				?>
				<a <?php $this->print_render_attribute_string( 'cta-button' ); ?>>
					<?php echo esc_html( $s['cta_button_text'] ); ?>
					<span class="avix-ft__pill-icon" aria-hidden="true"><svg class="avix-ft__icon" viewBox="0 0 24 24" focusable="false"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg></span>
				</a>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) $s['cta_lead'] ) ) : ?>
				<p class="avix-ft__lead"><?php echo esc_html( $s['cta_lead'] ); ?></p>
			<?php endif; ?>

			<?php
			if ( '' !== trim( (string) $s['cta_alt_text'] ) && ! empty( $s['cta_alt_link']['url'] ) ) :
				$this->add_link_attributes( 'cta-alt', $s['cta_alt_link'] );
				?>
				<p class="avix-ft__alt">
					<?php echo esc_html( $s['cta_alt_prefix'] ); ?>
					<a <?php $this->print_render_attribute_string( 'cta-alt' ); ?>><?php echo esc_html( $s['cta_alt_text'] ); ?><svg class="avix-ft__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg></a>
				</p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Escaped headline lines joined with <br>. When $perch is given, the first
	 * matching word (or the first word) gets the pixel buddy sitting on it.
	 */
	private function title_html( array $lines, $perch ) {
		$out  = array_map( 'esc_html', $lines );
		if ( null === $perch ) {
			return implode( '<br>', $out );
		}

		$perch = trim( $perch );
		$done  = false;
		foreach ( $out as $i => $line ) {
			$word = '' !== $perch ? esc_html( $perch ) : '';
			if ( '' === $word || false === strpos( $line, $word ) ) {
				continue;
			}
			$out[ $i ] = preg_replace( '/' . preg_quote( $word, '/' ) . '/', '<span class="avix-ft__perch">' . $word . $this->buddy_svg() . '</span>', $line, 1 );
			$done      = true;
			break;
		}
		if ( ! $done ) {
			// Fall back to the first word of the first line.
			$out[0] = preg_replace( '/^(\S+)/u', '<span class="avix-ft__perch">$1' . $this->buddy_svg() . '</span>', $out[0], 1 );
		}
		return implode( '<br>', $out );
	}

	/**
	 * The Avix pixel character (same 10-unit grid as the site), in a sitting pose.
	 */
	private function buddy_svg() {
		return '<span class="avix-ft__buddy" aria-hidden="true"><svg viewBox="0 0 10 11" focusable="false">'
			. '<rect class="avix-ft__b-leg avix-ft__b-leg--l" x="2" y="7" width="2" height="4"/>'
			. '<rect class="avix-ft__b-leg avix-ft__b-leg--r" x="6" y="7" width="2" height="4"/>'
			. '<rect class="avix-ft__b-body" x="2" y="3" width="6" height="4"/>'
			. '<rect class="avix-ft__b-head" x="3" y="0" width="4" height="3"/>'
			. '<rect class="avix-ft__b-arm avix-ft__b-arm--l" x="0" y="4" width="2" height="3"/>'
			. '<rect class="avix-ft__b-arm avix-ft__b-arm--r" x="8" y="4" width="2" height="3"/>'
			. '</svg></span>';
	}

	private function render_brand( array $s ) {
		$logo = $s['logo']['url'] ?? '';
		if ( 'light' === $s['theme'] && ! empty( $s['logo_on_light']['url'] ) ) {
			$logo = $s['logo_on_light']['url'];
		}
		?>
		<div class="avix-ft__col avix-ft__col--brand">
			<?php
			if ( $logo ) :
				$tag = empty( $s['logo_link']['url'] ) ? 'span' : 'a';
				if ( 'a' === $tag ) {
					$this->add_link_attributes( 'logo-link', $s['logo_link'] );
				}
				$this->add_render_attribute( 'logo-link', 'class', 'avix-ft__logo' );
				?>
				<<?php echo esc_attr( $tag ); ?> <?php $this->print_render_attribute_string( 'logo-link' ); ?>>
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Avix Digital' ); ?>" loading="lazy" decoding="async">
				</<?php echo esc_attr( $tag ); ?>>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) $s['about'] ) ) : ?>
				<p class="avix-ft__about"><?php echo esc_html( $s['about'] ); ?></p>
			<?php endif; ?>

			<?php
			$socials = array_filter(
				(array) $s['socials'],
				static function ( $row ) {
					return ! empty( $row['url']['url'] ) && isset( self::SOCIALS[ $row['network'] ?? '' ] );
				}
			);
			if ( $socials ) :
				?>
				<div class="avix-ft__social">
					<?php if ( '' !== trim( (string) $s['social_label'] ) ) : ?>
						<p class="avix-ft__social-label"><?php echo esc_html( $s['social_label'] ); ?></p>
					<?php endif; ?>
					<ul>
						<?php
						foreach ( array_values( $socials ) as $i => $row ) :
							$net = self::SOCIALS[ $row['network'] ];
							$key = 'social-' . $i;
							$this->add_link_attributes( $key, $row['url'] );
							$this->add_render_attribute( $key, array( 'aria-label' => $net['label'], 'title' => $net['label'] ) );
							?>
							<li>
								<a <?php $this->print_render_attribute_string( $key ); ?>>
									<?php if ( 'fiverr' === $row['network'] ) : ?>
										<span class="avix-ft__fi" aria-hidden="true">fi</span>
									<?php else : ?>
										<svg viewBox="<?php echo esc_attr( $net['box'] ); ?>" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $net['path'] ); ?>"/></svg>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_link_column( array $s, $n ) {
		$p     = 'col' . $n . '_';
		$links = $this->column_links( $s, $p );
		$title = trim( (string) $s[ $p . 'title' ] );
		if ( ! $links && '' === $title ) {
			return;
		}
		?>
		<nav class="avix-ft__col" aria-label="<?php echo esc_attr( $title ? $title : __( 'Footer links', 'avix-widgets' ) ); ?>">
			<?php if ( '' !== $title ) : ?>
				<p class="avix-ft__label"><?php echo esc_html( $title ); ?></p>
			<?php endif; ?>
			<ul class="avix-ft__list">
				<?php foreach ( $links as $i => $link ) : ?>
					<?php
					$key = $p . 'link-' . $i;
					$this->add_link_attributes( $key, $link['link'] );
					$this->add_render_attribute( $key, 'class', 'avix-ft__link' );
					?>
					<li><a <?php $this->print_render_attribute_string( $key ); ?>><?php echo esc_html( $link['text'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * @return array<int, array{text:string, link:array}>
	 */
	private function column_links( array $s, $p ) {
		$links = array();

		if ( 'menu' === $s[ $p . 'source' ] ) {
			$items = $s[ $p . 'menu' ] ? wp_get_nav_menu_items( (int) $s[ $p . 'menu' ] ) : array();
			foreach ( (array) $items as $item ) {
				if ( (int) $item->menu_item_parent !== 0 ) {
					continue;
				}
				$links[] = array(
					'text' => wp_strip_all_tags( $item->title ),
					'link' => array(
						'url'         => $item->url,
						'is_external' => '_blank' === $item->target ? 'on' : '',
						'nofollow'    => false !== strpos( (string) $item->xfn, 'nofollow' ) ? 'on' : '',
					),
				);
			}
			return $links;
		}

		foreach ( (array) $s[ $p . 'links' ] as $row ) {
			if ( '' === trim( (string) ( $row['text'] ?? '' ) ) || empty( $row['link']['url'] ) ) {
				continue;
			}
			$links[] = array(
				'text' => $row['text'],
				'link' => $row['link'],
			);
		}
		return $links;
	}

	private function render_contact( array $s ) {
		$items = array_filter(
			(array) $s['contacts'],
			static function ( $row ) {
				return '' !== trim( (string) ( $row['value'] ?? '' ) );
			}
		);
		$title = trim( (string) $s['contact_title'] );
		if ( ! $items && '' === $title ) {
			return;
		}
		?>
		<div class="avix-ft__col avix-ft__col--contact">
			<?php if ( '' !== $title ) : ?>
				<p class="avix-ft__label"><?php echo esc_html( $title ); ?></p>
			<?php endif; ?>
			<ul class="avix-ft__list avix-ft__contact">
				<?php foreach ( array_values( $items ) as $i => $row ) : ?>
					<li>
						<?php if ( '' !== trim( (string) ( $row['label'] ?? '' ) ) ) : ?>
							<span class="avix-ft__key"><?php echo esc_html( $row['label'] ); ?></span>
						<?php endif; ?>
						<span class="avix-ft__value"><?php $this->render_contact_value( $row, $i ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	private function render_contact_value( array $row, $i ) {
		$value = trim( (string) $row['value'] );
		$type  = $row['type'] ?? 'text';
		$key   = 'contact-' . $i;
		$link  = ! empty( $row['link']['url'] ) ? $row['link'] : null;

		if ( ! $link && 'email' === $type && is_email( $value ) ) {
			$link = array( 'url' => 'mailto:' . $value );
		} elseif ( ! $link && in_array( $type, array( 'phone', 'whatsapp' ), true ) ) {
			$digits = preg_replace( '/[^\d+]/', '', $value );
			if ( '' !== $digits ) {
				$link = 'whatsapp' === $type
					? array( 'url' => 'https://wa.me/' . ltrim( $digits, '+' ), 'is_external' => 'on' )
					: array( 'url' => 'tel:' . ( 0 === strpos( $digits, '+' ) ? $digits : '+' . $digits ) );
			}
		}

		if ( 'text' === $type || ! $link ) {
			echo '<span class="avix-ft__text">' . esc_html( $value ) . '</span>';
			return;
		}

		$this->add_link_attributes( $key, $link );
		$this->add_render_attribute( $key, 'class', 'avix-ft__link' );
		echo '<a ' . $this->get_render_attribute_string( $key ) . '>' . esc_html( $value ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes escaped by Elementor.

		if ( 'email' === $type && is_email( $value ) ) {
			/* translators: %s: email address. */
			printf(
				'<button class="avix-ft__copy" type="button" data-ft-copy="%1$s" aria-label="%2$s" title="%3$s"><svg class="avix-ft__copy-idle" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><svg class="avix-ft__copy-done" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m5 12.5 4.5 4.5L19 7"/></svg></button>',
				esc_attr( $value ),
				esc_attr( sprintf( __( 'Copy %s', 'avix-widgets' ), $value ) ),
				esc_attr__( 'Copy email', 'avix-widgets' )
			);
		}
	}

	private function render_bottom( array $s ) {
		$badges = array_values(
			array_filter(
				(array) $s['badges'],
				static function ( $row ) {
					return ! empty( $row['image']['url'] );
				}
			)
		);
		$legal = trim( str_replace( '{year}', wp_date( 'Y' ), (string) $s['copyright'] ) );
		$print = trim( (string) $s['small_print'] );
		$top   = 'yes' === $s['show_top'];
		?>
		<div class="avix-ft__bottom">
			<div class="avix-ft__bottom-row">
				<?php
				if ( $badges ) {
					$this->render_badge_list( $badges );
				} elseif ( '' !== $legal ) {
					echo '<p class="avix-ft__legal">' . esc_html( $legal ) . '</p>';
				}
				?>
				<?php if ( $top ) : ?>
					<button class="avix-ft__top" type="button" data-ft-top>
						<?php echo esc_html( $s['top_text'] ); ?>
						<span aria-hidden="true"><svg class="avix-ft__icon" viewBox="0 0 24 24" focusable="false"><path d="M12 19V5"/><path d="m6 11 6-6 6 6"/></svg></span>
					</button>
				<?php endif; ?>
			</div>
			<?php if ( ( $badges && '' !== $legal ) || '' !== $print ) : ?>
				<div class="avix-ft__bottom-row avix-ft__bottom-row--legal">
					<?php if ( $badges && '' !== $legal ) : ?>
						<p class="avix-ft__legal"><?php echo esc_html( $legal ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $print ) : ?>
						<p class="avix-ft__print"><?php echo esc_html( $print ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_badge_list( array $badges ) {
		echo '<ul class="avix-ft__badges">';
		foreach ( $badges as $i => $row ) {
			$name = trim( (string) ( $row['name'] ?? '' ) );
			$zoom = isset( $row['zoom']['size'] ) && '' !== $row['zoom']['size'] ? max( 0.6, min( 2.5, $row['zoom']['size'] / 100 ) ) : 1;
			$img  = sprintf( '<img src="%s" alt="%s" loading="lazy" decoding="async" style="--ft-zoom:%s">', esc_url( $row['image']['url'] ), esc_attr( $name ), esc_attr( $zoom ) );
			echo '<li class="avix-ft__badge">';
			if ( ! empty( $row['link']['url'] ) ) {
				$key = 'badge-' . $i;
				$this->add_link_attributes( $key, $row['link'] );
				$this->add_render_attribute( $key, 'class', 'avix-ft__badge-tile' );
				echo '<a ' . $this->get_render_attribute_string( $key ) . '>' . $img . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			} else {
				echo '<span class="avix-ft__badge-tile">' . $img . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	/* ------------------------------------------------------------------ */
	/* Defaults                                                            */
	/* ------------------------------------------------------------------ */

	private function default_services() {
		return array(
			array( 'text' => 'Web Development', 'link' => array( 'url' => 'https://avixdigital.com/service/web-development/' ) ),
			array( 'text' => 'Shopify Plus', 'link' => array( 'url' => 'https://avixdigital.com/service/shopify-plus/' ) ),
			array( 'text' => 'UI/UX & Branding', 'link' => array( 'url' => 'https://avixdigital.com/service/uiux-and-brand-design/' ) ),
			array( 'text' => 'WordPress Development', 'link' => array( 'url' => 'https://avixdigital.com/service/wordpress-development/' ) ),
		);
	}

	private function default_navigation() {
		return array(
			array( 'text' => 'Home', 'link' => array( 'url' => '/' ) ),
			array( 'text' => 'About Us', 'link' => array( 'url' => 'https://avixdigital.com/about-avixdigital/' ) ),
			array( 'text' => 'Pricing', 'link' => array( 'url' => 'https://avixdigital.com/pricing/' ) ),
			array( 'text' => 'Contact Us', 'link' => array( 'url' => 'https://avixdigital.com/contact/' ) ),
		);
	}
}
