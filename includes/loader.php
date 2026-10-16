<?php
/**
 * Avix loader: a short welcome on the first page of a visit.
 *
 * The pixel buddy assembles itself square by square, waves, and leaps up and
 * away while the screen dissolves into brand squares, revealing the page. It
 * waits for nothing: it ends as soon as the page has loaded (never sooner than
 * Avix_Loader::MIN ms, never later than Avix_Loader::MAX ms) and shows once per
 * browser session. Everything is inline in <head> (no extra request); the page
 * renders underneath, so the loader never delays it.
 *
 * Never shown in wp-admin, the Elementor editor or its preview, inside frames,
 * to search engines and audit tools, or to visitors who prefer reduced motion.
 * Without JS nothing is shown at all.
 *
 * Settings > Avix loader. Filter: avix_loader_enabled (bool $enabled, array $options).
 * The Hero Banner waits for the "avix:booted" event before its buddy lands.
 *
 * @package AvixWidgets
 */

namespace AvixWidgets;

defined( 'ABSPATH' ) || exit;

final class Loader {

	const OPTION = 'avix_loader';
	const GROUP  = 'avix_loader';
	const PAGE   = 'avix-loader';

	/** Shortest and longest time on screen, in ms. */
	const MIN = 900;
	const MAX = 2200;

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/* ---------- Options ---------- */

	public static function defaults(): array {
		return array(
			'enabled' => 1,
			'scope'   => 'all',
		);
	}

	public static function options(): array {
		$saved = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * @param mixed $value Raw option.
	 */
	public static function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$d     = self::defaults();
		return array(
			'enabled' => isset( $value['enabled'] ) && is_scalar( $value['enabled'] ) ? ( (int) $value['enabled'] ? 1 : 0 ) : $d['enabled'],
			'scope'   => isset( $value['scope'] ) && in_array( $value['scope'], array( 'all', 'home' ), true ) ? $value['scope'] : $d['scope'],
		);
	}

	/* ---------- Front end ---------- */

	private static function wanted(): bool {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_embed() || is_customize_preview() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context check.
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['preview'] ) ) {
			return false;
		}
		if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && is_object( \Elementor\Plugin::$instance->preview ) && method_exists( \Elementor\Plugin::$instance->preview, 'is_preview_mode' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return false;
		}
		$o  = self::options();
		$on = $o['enabled'] && ( 'all' === $o['scope'] || is_front_page() );
		return (bool) apply_filters( 'avix_loader_enabled', $on, $o );
	}

	public static function head() {
		if ( ! self::wanted() ) {
			return;
		}
		$markup = '<div id="avix-boot" class="avix-boot" aria-hidden="true">'
			. '<div class="avix-boot__tiles"></div>'
			. '<div class="avix-boot__stage">'
			. '<svg class="avix-boot__buddy" viewBox="0 0 10 11" focusable="false">'
			. '<rect style="--i:0" x="2" y="7" width="2" height="4"/>'
			. '<rect style="--i:1" x="6" y="7" width="2" height="4"/>'
			. '<rect style="--i:2" x="2" y="3" width="6" height="4"/>'
			. '<rect style="--i:3" x="0" y="4" width="2" height="3"/>'
			. '<rect class="a" style="--i:4" x="8" y="4" width="2" height="3"/>'
			. '<rect style="--i:5" x="3" y="0" width="4" height="3"/>'
			. '<rect class="e" style="--i:6" x="4" y="1" width="1" height="1"/>'
			. '</svg>'
			. '<span class="avix-boot__bar"><i></i><i></i><i></i><i></i><i></i></span>'
			. '<span class="avix-boot__name">' . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ?: get_bloginfo( 'name' ) ) . '</span>'
			. '</div></div>';
		?>
<style id="avix-boot-css">
html.avix-boot,html.avix-boot body{overflow:hidden!important}
html.avix-boot::before{content:"";position:fixed;inset:0;z-index:2147482990;background:#050404;animation:avix-boot-safe 0s 5s forwards}
html.avix-boot-out::before{opacity:0;transition:opacity .2s}
html.avix-boot .avix-hero,html.avix-boot .avix-hero *{animation-play-state:paused!important}
.avix-boot{position:fixed;inset:0;z-index:2147483000;display:grid;place-items:center;pointer-events:none;animation:avix-boot-safe 0s 5s forwards}
.avix-boot__tiles{position:absolute;inset:0;display:grid;grid-template-columns:repeat(12,1fr);grid-auto-rows:1fr}
.avix-boot__tiles i{background:#050404;transition:opacity .24s linear;transition-delay:var(--d,0ms)}
.avix-boot.is-out .avix-boot__tiles i{opacity:0}
.avix-boot__stage{position:relative;display:flex;flex-direction:column;align-items:center;gap:22px}
.avix-boot__buddy{display:block;width:84px;height:auto;overflow:visible;fill:#fb6007;shape-rendering:crispEdges}
.avix-boot__buddy rect{opacity:0;transform:translateY(-4px);transform-box:view-box;animation:avix-boot-drop .36s cubic-bezier(.3,1.6,.5,1) forwards;animation-delay:calc(var(--i)*70ms + 60ms)}
.avix-boot__buddy .e{fill:#050404}
.avix-boot.is-ready .avix-boot__buddy .a{animation:avix-boot-drop .36s cubic-bezier(.3,1.6,.5,1) forwards,avix-boot-wave .5s steps(1) 3}
.avix-boot__bar{display:flex;gap:6px}
.avix-boot__bar i{width:8px;height:8px;background:rgba(255,255,255,.12);transition:background-color .2s}
.avix-boot__bar i.is-on{background:#fb6007}
.avix-boot__name{color:#8a8380;font:500 12px/1 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.3em}
.avix-boot.is-out .avix-boot__bar,.avix-boot.is-out .avix-boot__name{opacity:0;transition:opacity .18s}
.avix-boot.is-out .avix-boot__buddy{opacity:0;transform:translateY(-38vh) scale(.8);transition:transform .55s cubic-bezier(.4,-.35,.7,.1),opacity .25s .3s}
@keyframes avix-boot-drop{to{opacity:1;transform:none}}
@keyframes avix-boot-wave{50%{transform:translateY(-3px)}}
@keyframes avix-boot-safe{to{visibility:hidden}}
</style>
<script id="avix-boot-js">
(function(d,w){var h=d.documentElement,ss=null;try{if(w.top!==w.self||(w.matchMedia&&w.matchMedia('(prefers-reduced-motion: reduce)').matches)||/bot|crawl|spider|slurp|lighthouse|pagespeed|headless|preview/i.test(navigator.userAgent))return;try{ss=w.sessionStorage;}catch(e){}if(ss&&ss.getItem('avix-boot'))return;if(ss)ss.setItem('avix-boot','1');}catch(e){return;}
h.classList.add('avix-boot');var t0=Date.now(),MIN=<?php echo (int) self::MIN; ?>,MAX=<?php echo (int) self::MAX; ?>,el=null,lit=1,done=false,markup=<?php echo wp_json_encode( $markup ); ?>;
function light(n){lit=Math.max(lit,n);if(!el)return;var b=el.querySelectorAll('.avix-boot__bar i');for(var i=0;i<b.length;i++)if(i<lit)b[i].className='is-on';}
function mount(){if(el)return true;if(!d.body)return false;d.body.insertAdjacentHTML('afterbegin',markup);el=d.getElementById('avix-boot');var s='';for(var i=0;i<96;i++)s+='<i style="--d:'+Math.round(Math.random()*320)+'ms"></i>';el.firstChild.innerHTML=s;light(lit);setTimeout(function(){if(el)el.classList.add('is-ready');},640);return true;}
(function wait(){if(!mount())w.requestAnimationFrame(wait);})();
function end(){if(done)return;done=true;light(5);setTimeout(function(){mount();h.classList.add('avix-boot-out');if(el)el.classList.add('is-out');setTimeout(function(){h.classList.remove('avix-boot','avix-boot-out');if(el&&el.parentNode)el.parentNode.removeChild(el);w.__avixBooted=true;try{w.dispatchEvent(new CustomEvent('avix:booted'));}catch(e){}},700);},Math.max(0,MIN-(Date.now()-t0)));}
setTimeout(function(){light(2);},300);d.addEventListener('DOMContentLoaded',function(){light(3);});w.addEventListener('load',function(){light(4);end();});setTimeout(end,MAX);
})(document,window);
</script>
		<?php
	}

	/* ---------- Settings ---------- */

	public static function menu() {
		add_options_page(
			__( 'Avix loader', 'avix-widgets' ),
			__( 'Avix loader', 'avix-widgets' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'default'           => self::defaults(),
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'avix-widgets' ) );
		}
		$o    = self::options();
		$name = self::OPTION;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Avix loader', 'avix-widgets' ); ?></h1>
			<p><?php esc_html_e( 'On the first page of a visit, the pixel buddy assembles itself, waves and leaps away while the screen dissolves into squares. It ends as soon as the page has loaded (at least 0.9 s, at most 2.2 s) and never shows again in the same browser session, to search engines, in the Elementor editor or to visitors who prefer reduced motion.', 'avix-widgets' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Loader', 'avix-widgets' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( 1, (int) $o['enabled'] ); ?>>
								<?php esc_html_e( 'Show the welcome loader', 'avix-widgets' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-loader-scope"><?php esc_html_e( 'Where', 'avix-widgets' ); ?></label></th>
						<td>
							<select id="avix-loader-scope" name="<?php echo esc_attr( $name ); ?>[scope]">
								<option value="all" <?php selected( 'all', $o['scope'] ); ?>><?php esc_html_e( 'Whichever page a visit starts on', 'avix-widgets' ); ?></option>
								<option value="home" <?php selected( 'home', $o['scope'] ); ?>><?php esc_html_e( 'Only when a visit starts on the home page', 'avix-widgets' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
