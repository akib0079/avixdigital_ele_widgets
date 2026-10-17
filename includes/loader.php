<?php
/**
 * Avix loader: a short pixel welcome while the page loads. By default it plays
 * the full Avix-mark intro on every load of the home page (and its Dutch
 * translation) and nowhere else, so other pages open straight away.
 *
 * The pixel buddy assembles itself square by square, waves, and leaps up and
 * away while the screen dissolves into brand squares, revealing the page. It
 * waits for nothing: it ends as soon as the page has loaded (never sooner than
 * Loader::MIN ms, never later than Loader::MAX ms) on every page load, or once
 * per browser session (setting). Everything is inline in <head> (no extra
 * request); the page renders underneath, so the loader never delays it, and the
 * page's CSS animations are held until it has gone so they are seen.
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
	const MIN = 700;
	const MAX = 1600;

	/**
	 * The Avix mark on a 1000x1000 box (traced from the logo; the two ink shapes
	 * overshoot the circle, so it is clipped to a circle of radius 480).
	 */
	const MARK_PATH = 'M480.9 14.7C473.6 15.2, 468.9 15.8, 456.7 18.0C443.5 20.3, 441.9 20.6, 430.4 21.8C418.2 23.0, 414.3 23.7, 403.5 26.6C398.9 27.9, 391.8 29.5, 387.7 30.2C375.5 32.5, 367.2 34.7, 354.1 39.4C347.3 41.8, 335.4 45.9, 327.7 48.4C298.0 58.1, 264.8 75.1, 230.7 98.0C206.1 114.6, 201.9 117.8, 188.5 129.6C184.6 133.2, 178.8 138.2, 175.7 140.9C165.1 150.0, 162.1 152.9, 147.7 167.7C121.3 195.0, 114.4 203.5, 91.7 237.3C73.1 265.1, 65.8 278.1, 54.9 302.9C53.4 306.2, 50.5 312.5, 48.4 316.9C41.5 331.4, 39.1 337.8, 30.2 366.1C21.9 392.5, 21.4 394.6, 20.0 407.8C18.9 418.0, 18.6 419.8, 15.7 432.0C11.2 450.7, 11.4 447.8, 11.4 497.7C11.4 548.2, 11.5 551.2, 15.9 574.0C16.9 578.9, 18.1 585.5, 18.7 588.8C21.4 603.9, 26.0 610.2, 34.5 610.3C43.1 610.3, 49.7 604.6, 77.3 572.7C81.7 567.7, 88.3 560.4, 92.0 556.4C101.8 546.1, 107.4 539.7, 118.8 525.6C124.3 518.8, 132.6 508.8, 137.1 503.5C141.6 498.2, 148.4 490.1, 152.2 485.4C156.0 480.8, 162.5 473.4, 166.6 468.9C170.7 464.4, 176.5 457.7, 179.5 453.9C191.1 439.3, 194.9 434.6, 200.4 428.2C203.6 424.5, 210.5 416.4, 215.9 410.2C221.2 403.9, 230.9 393.2, 237.3 386.5C252.5 370.6, 256.4 366.3, 267.1 353.4C277.8 340.4, 286.6 331.5, 297.6 322.6C302.2 318.9, 309.9 312.1, 314.8 307.5C330.8 292.5, 332.3 291.6, 360.5 280.6C382.4 272.1, 386.1 271.0, 406.4 267.6C431.0 263.5, 444.4 262.8, 461.3 264.7C465.2 265.2, 473.9 266.1, 480.6 266.8C501.8 269.1, 506.4 270.4, 528.0 280.1C558.4 293.7, 576.9 306.6, 598.9 329.5C612.0 343.1, 633.2 375.1, 635.0 384.1C638.5 400.6, 623.5 418.9, 610.2 414.5C604.9 412.7, 601.5 408.6, 589.3 389.1C574.8 365.7, 555.8 345.6, 539.0 335.4C473.4 296.1, 408.5 296.8, 347.5 337.4C332.2 347.7, 294.0 385.8, 270.4 414.4C252.1 436.6, 234.8 457.1, 229.9 462.5C216.7 476.9, 212.8 481.4, 204.1 492.2C191.1 508.2, 184.1 516.3, 168.9 532.5C144.5 558.4, 141.3 562.1, 131.3 575.4C123.4 585.9, 121.4 588.2, 111.8 598.5C101.8 609.2, 100.2 611.0, 88.6 625.4C83.6 631.7, 75.4 641.5, 70.4 647.3C45.7 676.0, 44.7 688.2, 64.4 722.9C86.4 761.5, 97.8 759.4, 137.1 709.3C140.5 705.0, 148.4 695.6, 154.7 688.5C169.8 671.2, 177.6 661.9, 188.2 648.8C198.1 636.5, 202.0 632.0, 214.5 617.9C219.4 612.5, 228.1 602.4, 233.9 595.5C239.7 588.6, 247.3 579.9, 250.7 576.3C254.1 572.7, 259.7 566.7, 263.2 563.0C266.6 559.4, 273.5 551.4, 278.6 545.2C283.7 539.1, 291.7 529.9, 296.4 524.9C301.1 519.8, 308.2 511.7, 312.3 506.8C316.4 501.9, 323.2 494.0, 327.4 489.3C331.5 484.5, 338.4 476.4, 342.6 471.2C361.4 448.2, 382.9 424.5, 391.9 417.0C423.0 390.8, 454.9 389.6, 490.2 413.2C513.2 428.5, 527.5 470.1, 522.2 506.0C517.6 537.0, 522.1 550.8, 539.7 559.6C559.2 569.5, 576.6 561.5, 603.6 530.2C608.9 524.2, 617.4 514.8, 622.7 509.3C633.3 498.3, 635.5 495.8, 644.7 483.5C651.7 474.1, 655.4 469.8, 664.5 460.2C668.0 456.6, 675.4 448.2, 680.9 441.7C691.5 429.0, 699.0 420.6, 711.9 407.0C723.9 394.4, 729.8 387.4, 750.8 360.8C758.1 351.7, 762.4 346.7, 771.4 337.1C777.2 330.9, 782.0 325.5, 791.2 314.5C794.6 310.5, 800.7 303.6, 804.7 299.3C813.9 289.3, 816.0 286.8, 824.1 276.0C833.8 263.2, 841.3 254.4, 853.3 241.9C887.8 205.8, 886.8 199.2, 839.9 152.3C806.1 118.5, 754.7 83.4, 710.7 64.3C705.4 62.0, 697.7 58.6, 693.6 56.8C689.5 54.9, 681.3 51.6, 675.3 49.4C669.3 47.1, 658.2 43.0, 650.6 40.2C634.8 34.3, 628.7 32.6, 616.2 30.5C611.2 29.6, 603.9 28.1, 599.9 27.1C588.1 24.1, 583.5 23.3, 572.2 22.3C560.6 21.2, 556.4 20.6, 544.5 18.3C524.5 14.5, 503.0 13.2, 480.9 14.7M904.3 256.2C897.4 258.1, 889.8 265.0, 875.0 282.6C869.6 289.1, 862.4 297.4, 859.0 301.2C855.6 305.0, 847.8 314.0, 841.7 321.3C835.6 328.6, 826.4 339.2, 821.3 345.0C812.6 354.8, 807.5 360.9, 795.1 376.8C790.1 383.2, 785.5 388.1, 768.2 406.4C757.5 417.7, 752.4 423.6, 743.2 435.3C731.3 450.6, 727.6 454.8, 712.2 470.7C704.2 478.9, 700.8 483.0, 694.8 491.8C687.2 503.1, 684.6 506.2, 672.2 518.8C661.1 530.2, 656.0 535.8, 634.1 561.0C588.3 613.7, 550.8 624.4, 508.4 596.9C479.2 578.0, 471.3 556.7, 475.9 509.4C479.6 472.4, 474.7 456.6, 457.0 447.3C439.5 438.2, 424.1 444.9, 399.6 472.1C394.6 477.6, 384.8 488.1, 377.7 495.3C362.1 511.2, 358.0 515.9, 349.1 528.2C338.4 543.0, 331.5 551.1, 314.5 569.4C304.1 580.6, 300.6 584.6, 291.5 596.1C283.0 606.9, 281.2 609.0, 265.5 625.7C244.9 647.8, 241.9 651.2, 225.5 672.0C222.7 675.6, 218.6 680.6, 216.4 683.0C214.2 685.4, 205.3 695.1, 196.6 704.6C175.7 727.5, 173.6 729.9, 164.6 741.4C156.3 752.1, 152.3 756.7, 144.4 765.2C118.3 792.9, 117.0 804.3, 137.5 826.4C166.9 858.3, 191.3 880.2, 222.5 902.4C235.1 911.4, 267.3 929.6, 293.5 942.6C321.4 956.3, 320.7 956.0, 335.2 959.5C345.6 962.0, 348.1 962.8, 362.0 967.9C381.1 974.9, 386.6 976.3, 401.5 978.1C412.6 979.4, 414.3 979.7, 425.9 982.4C446.2 987.2, 484.1 988.9, 532.5 987.3C560.4 986.4, 563.1 986.1, 582.4 981.4C586.6 980.4, 593.6 979.2, 597.9 978.6C615.1 976.4, 618.3 975.6, 637.4 969.0C644.7 966.5, 656.7 962.5, 664.1 960.1C679.9 955.0, 682.1 954.2, 699.2 946.3C714.1 939.5, 720.8 936.0, 731.3 929.7C735.3 927.3, 744.5 921.9, 751.6 917.8C772.8 905.5, 783.2 898.2, 816.5 872.3C836.8 856.5, 840.7 852.9, 856.2 835.3C861.0 830.0, 868.3 821.9, 872.6 817.3C891.7 796.4, 898.6 787.3, 911.5 765.6C915.3 759.2, 921.0 749.7, 924.1 744.5C930.0 734.8, 944.7 706.1, 953.3 687.5C963.8 665.0, 966.6 657.0, 975.8 624.9C984.1 596.0, 984.3 594.8, 985.8 567.1C986.1 561.5, 986.7 551.2, 987.3 544.2C987.8 537.2, 988.5 525.4, 988.8 518.0C989.1 510.6, 989.6 500.5, 989.9 495.6C990.6 484.8, 990.2 478.1, 988.3 462.9C987.5 456.5, 986.4 446.6, 986.0 441.0C985.6 435.3, 985.0 427.7, 984.7 424.1C982.2 392.1, 964.2 386.8, 942.4 411.8C938.5 416.3, 930.9 424.7, 925.7 430.5C913.3 444.0, 907.6 450.7, 894.2 467.4C885.1 478.7, 880.9 483.5, 869.5 495.1C854.9 509.9, 852.6 512.5, 844.3 523.9C841.4 527.8, 838.0 532.4, 836.7 534.0C832.6 539.4, 821.6 551.7, 804.4 570.2C797.2 577.9, 790.8 585.6, 780.3 599.2C772.6 609.2, 761.2 621.8, 738.0 645.9C727.9 656.4, 721.7 663.2, 713.1 673.0C690.3 699.3, 669.5 714.3, 636.5 728.4C613.9 738.0, 609.8 739.2, 592.6 741.2C586.7 741.9, 577.2 743.2, 571.5 744.1C555.7 746.5, 544.8 746.5, 530.0 743.9C524.1 742.8, 515.3 741.5, 510.5 741.0C493.0 738.9, 478.9 734.2, 455.2 722.6C440.3 715.4, 411.2 695.1, 400.7 684.7C383.5 667.5, 364.1 641.5, 360.2 630.4C355.0 615.3, 364.7 597.2, 378.4 596.5C386.3 596.1, 389.9 599.3, 400.7 617.0C408.1 629.1, 428.7 652.9, 438.4 660.6C475.8 690.4, 530.0 705.3, 576.8 698.5C607.3 694.1, 610.8 692.9, 638.9 676.9C660.2 664.7, 665.4 660.8, 679.2 646.7C713.8 611.3, 712.5 612.7, 727.9 593.3C738.4 580.1, 744.5 573.1, 754.2 563.0C766.4 550.3, 775.2 540.2, 788.3 523.9C796.1 514.1, 800.4 509.0, 808.6 499.9C820.3 487.0, 822.7 484.3, 831.8 473.2C851.6 449.1, 855.8 444.4, 867.4 432.5C874.9 424.8, 882.7 416.3, 887.5 410.4C894.2 402.1, 905.3 389.0, 915.9 376.7C917.4 374.9, 921.9 369.9, 926.0 365.5C953.6 335.6, 956.4 321.8, 941.0 290.1C927.1 261.6, 917.3 252.6, 904.3 256.2';

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/* ---------- Options ---------- */

	public static function defaults(): array {
		return array(
			'enabled'   => 1,
			'style'     => 'mark',
			'scope'     => 'home',
			'frequency' => 'always',
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
			'enabled'   => isset( $value['enabled'] ) && is_scalar( $value['enabled'] ) ? ( (int) $value['enabled'] ? 1 : 0 ) : $d['enabled'],
			'style'     => isset( $value['style'] ) && in_array( $value['style'], array( 'mark', 'buddy' ), true ) ? $value['style'] : $d['style'],
			'scope'     => isset( $value['scope'] ) && in_array( $value['scope'], array( 'all', 'home' ), true ) ? $value['scope'] : $d['scope'],
			'frequency' => isset( $value['frequency'] ) && in_array( $value['frequency'], array( 'always', 'every', 'session' ), true ) ? $value['frequency'] : $d['frequency'],
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
		$on = $o['enabled'] && ( 'all' === $o['scope'] || self::is_home() );
		return (bool) apply_filters( 'avix_loader_enabled', $on, $o );
	}

	/**
	 * The front page, or a translation of it (the Dutch home links to the front
	 * page through _avix_translation_of).
	 */
	private static function is_home(): bool {
		if ( is_front_page() ) {
			return true;
		}
		$front = (int) get_option( 'page_on_front' );
		return $front > 0 && is_page() && (int) get_post_meta( (int) get_queried_object_id(), '_avix_translation_of', true ) === $front;
	}

	public static function head() {
		if ( ! self::wanted() ) {
			return;
		}
		if ( 'buddy' !== self::options()['style'] ) {
			self::head_mark();
			return;
		}
		$once = 'session' === self::options()['frequency'];
		// "always" shows this loader on every load, like "every".
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
html.avix-booting{overflow:hidden!important}
html.avix-booting::before{content:"";position:fixed;inset:0;z-index:2147482990;background:#050404;animation:avix-boot-safe 0s 5s forwards}
html.avix-booting-out::before{opacity:0;transition:opacity .2s}
html.avix-booting body *:not(.avix-boot):not(.avix-boot *){animation-play-state:paused!important}
div.avix-boot{position:fixed;inset:0;z-index:2147483000;display:grid;place-items:center;pointer-events:none;animation:avix-boot-safe 0s 5s forwards}
.avix-boot__tiles{position:absolute;inset:0;display:grid;grid-template-columns:repeat(12,1fr);grid-auto-rows:1fr}
.avix-boot__tiles i{background:#050404;transition:opacity .2s linear;transition-delay:var(--d,0ms)}
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
.avix-boot.is-out .avix-boot__buddy{opacity:0;transform:translateY(-38vh) scale(.8);transition:transform .45s cubic-bezier(.4,-.35,.7,.1),opacity .2s .25s}
@keyframes avix-boot-drop{to{opacity:1;transform:none}}
@keyframes avix-boot-wave{50%{transform:translateY(-3px)}}
@keyframes avix-boot-safe{to{visibility:hidden}}
</style>
<script id="avix-boot-js">
(function(d,w){var h=d.documentElement,ss=null;try{if(w.top!==w.self||(w.matchMedia&&w.matchMedia('(prefers-reduced-motion: reduce)').matches)||/bot|crawl|spider|slurp|lighthouse|pagespeed|headless|preview/i.test(navigator.userAgent))return;if(<?php echo $once ? 'true' : 'false'; ?>){try{ss=w.sessionStorage;}catch(e){}if(ss&&ss.getItem('avix-boot'))return;if(ss)ss.setItem('avix-boot','1');}}catch(e){return;}
h.classList.add('avix-booting');var t0=Date.now(),MIN=<?php echo (int) self::MIN; ?>,MAX=<?php echo (int) self::MAX; ?>,el=null,lit=1,done=false,markup=<?php echo wp_json_encode( $markup ); ?>;
function light(n){lit=Math.max(lit,n);if(!el)return;var b=el.querySelectorAll('.avix-boot__bar i');for(var i=0;i<b.length;i++)if(i<lit)b[i].className='is-on';}
function mount(){if(el)return true;if(!d.body)return false;d.body.insertAdjacentHTML('afterbegin',markup);el=d.getElementById('avix-boot');var s='';for(var i=0;i<96;i++)s+='<i style="--d:'+Math.round(Math.random()*240)+'ms"></i>';el.firstChild.innerHTML=s;light(lit);setTimeout(function(){if(el)el.classList.add('is-ready');},640);return true;}
(function wait(){if(!mount())w.requestAnimationFrame(wait);})();
function end(){if(done)return;done=true;light(5);setTimeout(function(){mount();h.classList.add('avix-booting-out');if(el)el.classList.add('is-out');setTimeout(function(){h.classList.remove('avix-booting','avix-booting-out');if(el&&el.parentNode)el.parentNode.removeChild(el);w.__avixBooted=true;try{w.dispatchEvent(new CustomEvent('avix:booted'));}catch(e){}},480);},Math.max(0,MIN-(Date.now()-t0)));}
setTimeout(function(){light(2);},300);d.addEventListener('DOMContentLoaded',function(){light(3);});w.addEventListener('load',function(){light(4);end();});setTimeout(end,MAX);
})(document,window);
</script>
		<?php
	}

	/**
	 * The "mark" loader, as on akib.avixdigital.com.
	 *
	 * First page of a visit: the Avix mark gathers from a grid of pixels while a
	 * counter runs to 100, sharpens to the vector logo, then becomes a window: the
	 * page is revealed through the logo as it zooms past the camera.
	 *
	 * Frequency "always" (the default, on the home page only): the full intro on
	 * every load, and nothing else anywhere.
	 *
	 * Frequency "every": the full intro on the first page of a visit; on every
	 * later page load a dark curtain with the orange mark covers the page and
	 * lifts away with a curved edge once it is ready, and a click on an internal
	 * link first draws the curtain up from below, then opens the next page. With
	 * frequency "session", later pages show nothing. Inline, no library.
	 */
	private static function head_mark() {
		$freq  = self::options()['frequency'];
		$every = 'every' === $freq;
		$fresh = 'always' === $freq;
		$mark  = '<path fill="currentColor" clip-path="url(#avix-ct-clip)" d="' . self::MARK_PATH . '"/>';
		$boot  = '<div id="avix-boot" class="avix-mboot" aria-hidden="true">'
			. '<svg class="avix-mboot__veil" width="100%" height="100%"><defs>'
			. '<clipPath id="avix-mboot-clip"><circle cx="500" cy="500" r="480"/></clipPath>'
			. '<mask id="avix-mboot-mask" maskUnits="userSpaceOnUse" x="0" y="0" width="100%" height="100%">'
			. '<rect width="100%" height="100%" fill="#fff"/>'
			. '<g class="avix-mboot__hole" opacity="0"><path fill="#000" clip-path="url(#avix-mboot-clip)" d="' . self::MARK_PATH . '"/></g>'
			. '</mask></defs>'
			. '<rect width="100%" height="100%" fill="#070605" mask="url(#avix-mboot-mask)"/></svg>'
			. '<canvas class="avix-mboot__px"></canvas>'
			. '<div class="avix-mboot__hud">'
			. '<p class="avix-mboot__log"><span class="avix-mboot__brand">avixdigital</span><span class="avix-mboot__status">Booting interface</span></p>'
			. '<p class="avix-mboot__count">000</p>'
			. '</div></div>';
		$curtain = '<div id="avix-curtain" class="avix-ct" aria-hidden="true"><div class="avix-ct__panel">'
			. '<svg class="avix-ct__mark" viewBox="0 0 1000 1000" focusable="false"><defs><clipPath id="avix-ct-clip"><circle cx="500" cy="500" r="480"/></clipPath></defs>' . $mark . '</svg>'
			. '</div></div>';
		?>
<style id="avix-boot-css">
html.avix-booting{overflow:hidden!important}
html.avix-booting::before{content:"";position:fixed;inset:0;z-index:2147482990;background:#070605;animation:avix-boot-safe 0s 6s forwards}
html.avix-boot-on::before{display:none}
html.avix-booting body *:not(.avix-mboot):not(.avix-mboot *):not(.avix-ct):not(.avix-ct *){animation-play-state:paused!important}
div.avix-mboot{position:fixed;inset:0;z-index:2147483000;pointer-events:none;color:#f1ede8;animation:avix-boot-safe 0s 7s forwards}
.avix-mboot__veil{position:absolute;inset:0;display:block;width:100%;height:100%}
.avix-mboot__px{position:absolute;left:50%;top:50%;width:var(--boot-size,160px);height:var(--boot-size,160px);transform:translate(-50%,-50%)}
.avix-mboot__hud{position:absolute;left:clamp(20px,4vw,65px);right:clamp(20px,4vw,65px);bottom:clamp(20px,4vw,48px);display:flex;align-items:flex-end;justify-content:space-between;gap:24px;transition:opacity .45s ease,transform .5s cubic-bezier(.5,0,.75,0)}
.avix-mboot.is-out .avix-mboot__hud{opacity:0;transform:translateY(24px)}
.avix-mboot__log{display:grid;gap:8px;margin:0;font:500 11px/1.2 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.12em;text-transform:uppercase;color:#8d8580}
.avix-mboot__brand{color:#fb6007}
.avix-mboot__count{margin:0;font:400 clamp(64px,9vw,150px)/.8 "Space Grotesk","Inter",Arial,sans-serif;letter-spacing:-.06em;font-variant-numeric:tabular-nums;color:#f1ede8}
div.avix-ct{position:fixed;inset:0;z-index:2147483000;overflow:hidden;pointer-events:none}
.avix-ct.is-leave{pointer-events:all}
.avix-ct__panel{position:absolute;inset:-12vh 0;display:grid;place-items:center;background:#070605;will-change:transform}
.avix-ct__mark{display:block;width:clamp(48px,5vw,72px);height:clamp(48px,5vw,72px);color:#fb6007}
.avix-ct.is-lift .avix-ct__panel{transform:translateY(-110%);border-radius:0 0 50% 50%/0 0 12vh 12vh;transition:transform 1s cubic-bezier(.76,0,.24,1) .1s,border-radius 1s cubic-bezier(.76,0,.24,1) .1s}
.avix-ct.is-lift .avix-ct__mark{opacity:0;transform:scale(.8);transition:opacity .35s cubic-bezier(.5,0,.75,0),transform .35s cubic-bezier(.5,0,.75,0)}
.avix-ct.is-leave .avix-ct__panel{transform:translateY(110%);border-radius:50% 50% 0 0/12vh 12vh 0 0}
.avix-ct.is-leave .avix-ct__mark{opacity:0;transform:scale(.8)}
.avix-ct.is-leave.is-in .avix-ct__panel{transform:none;border-radius:0;transition:transform .8s cubic-bezier(.76,0,.24,1),border-radius .8s cubic-bezier(.76,0,.24,1)}
.avix-ct.is-leave.is-in .avix-ct__mark{opacity:1;transform:none;transition:opacity .5s cubic-bezier(.16,1,.3,1) .4s,transform .5s cubic-bezier(.16,1,.3,1) .4s}
@keyframes avix-boot-safe{to{visibility:hidden}}
</style>
<script id="avix-boot-js">
(function(d,w){var h=d.documentElement,ss=null,EVERY=<?php echo $every ? 'true' : 'false'; ?>,FRESH=<?php echo $fresh ? 'true' : 'false'; ?>,mode='full';
try{if(w.top!==w.self||(w.matchMedia&&w.matchMedia('(prefers-reduced-motion: reduce)').matches)||/bot|crawl|spider|slurp|lighthouse|pagespeed|headless|preview/i.test(navigator.userAgent))return;if(!FRESH){try{ss=w.sessionStorage;}catch(e){}if(ss&&ss.getItem('avix-boot')){if(!EVERY)return;mode='curtain';}if(ss)ss.setItem('avix-boot','1');}}catch(e){return;}
h.classList.add('avix-booting');
var BOOT=<?php echo wp_json_encode( $boot ); ?>,CT=<?php echo wp_json_encode( $curtain ); ?>,PATH=<?php echo wp_json_encode( self::MARK_PATH ); ?>;
var raf=w.requestAnimationFrame||function(f){return setTimeout(function(){f(Date.now());},16);},loaded=false,ready=false,revealed=false;
w.addEventListener('load',function(){loaded=true;});d.addEventListener('DOMContentLoaded',function(){ready=true;});
function reveal(){if(revealed)return;revealed=true;h.classList.remove('avix-booting');w.__avixBooted=true;try{w.dispatchEvent(new CustomEvent('avix:booted'));}catch(e){}}
function drop(el){h.classList.remove('avix-boot-on');if(el&&el.parentNode)el.parentNode.removeChild(el);}
function until(test,max,cb){var s=Date.now();(function c(){if(test()||Date.now()-s>=max)cb();else setTimeout(c,50);})();}
function mountWith(html,id,cb){(function m(){if(!d.body){raf(m);return;}d.body.insertAdjacentHTML('afterbegin',html);var el=d.getElementById(id);h.classList.add('avix-boot-on');cb(el);})();}

/* Later pages: the curtain lifts away. */
function curtain(){mountWith(CT,'avix-curtain',function(el){var t0=Date.now();until(function(){return ready&&Date.now()-t0>380;},1000,function(){el.classList.add('is-lift');reveal();setTimeout(function(){drop(el);},1200);});});setTimeout(function(){reveal();drop(d.getElementById('avix-curtain'));},4500);}

/* First page of a visit: pixels, the logo, then the page through the logo. */
function full(){var TAU=Math.PI*2,AX=300,AY=205,GRIDS=[14,30],STATUS=['Loading','Almost there'],PAPER='#f1ede8',el,cv,ctx,cnt,st,hole,path,grids=[],last=-1,done=false;
function io2(t){return t<.5?2*t*t:1-Math.pow(-2*t+2,2)/2;}function io4(t){return t<.5?8*t*t*t*t:1-Math.pow(-2*t+2,4)/2;}
function tween(ms,ease,fn,cb){var s=null;function f(ts){if(s===null)s=ts;var k=Math.min(1,(ts-s)/ms);fn(ease(k));if(k<1)raf(f);else if(cb)cb();}raf(f);}
function pad(n){n=String(n);while(n.length<3)n='0'+n;return n;}function rng(s){return function(){s^=s<<13;s^=s>>>17;s^=s<<5;return(s>>>0)/4294967296;};}
function sample(n){var c=d.createElement('canvas');c.width=c.height=n;var x=c.getContext('2d');x.scale(n/1000,n/1000);x.beginPath();x.arc(500,500,480,0,TAU);x.clip();x.fill(path);var a=x.getImageData(0,0,n,n).data,out=[];for(var j=0;j<n;j++)for(var i=0;i<n;i++)if(a[(j*n+i)*4+3]>110)out.push([i,j]);return out;}
function render(p){cnt.textContent=pad(Math.round(p*100));if(p>=1){ctx.clearRect(0,0,cv.width,cv.height);ctx.save();ctx.scale(cv.width/1000,cv.height/1000);ctx.beginPath();ctx.arc(500,500,480,0,TAU);ctx.clip();ctx.fillStyle=PAPER;ctx.fill(path);ctx.restore();return;}var stage=Math.min(GRIDS.length-1,Math.floor(p*GRIDS.length)),local=p*GRIDS.length-stage;if(stage!==last){last=stage;st.textContent=STATUS[stage];}var g=grids[stage],cell=cv.width/g.n,sz=cell*.74,front=stage===0?g.cells.length*Math.min(1,local*1.2):g.cells.length;ctx.clearRect(0,0,cv.width,cv.height);ctx.fillStyle=PAPER;for(var i=0;i<g.cells.length;i++){var a=Math.max(0,Math.min(1,(front-i)/(g.cells.length*.18)));if(a<=0)break;ctx.globalAlpha=a;ctx.fillRect((g.cells[i][0]+.5)*cell-sz/2,(g.cells[i][1]+.5)*cell-sz/2,sz,sz);}ctx.globalAlpha=1;}
function zoom(){if(done)return;var r=cv.getBoundingClientRect(),s0=r.width/1000,vw=w.innerWidth,vh=w.innerHeight,z={s:s0,x:r.left+AX*s0,y:r.top+AY*s0},from={s:z.s,x:z.x,y:z.y},to={s:Math.max(vw,vh)/48,x:vw/2,y:vh/2};function apply(){hole.setAttribute('transform','translate('+z.x.toFixed(2)+' '+z.y.toFixed(2)+') scale('+z.s.toFixed(5)+') translate('+(-AX)+' '+(-AY)+')');}apply();hole.setAttribute('opacity','1');cv.style.visibility='hidden';el.classList.add('is-out');reveal();tween(1150,io4,function(k){z.s=from.s+(to.s-from.s)*k;z.x=from.x+(to.x-from.x)*k;z.y=from.y+(to.y-from.y)*k;apply();},function(){done=true;drop(el);});}
mountWith(BOOT,'avix-boot',function(node){el=node;cv=el.querySelector('canvas');cnt=el.querySelector('.avix-mboot__count');st=el.querySelector('.avix-mboot__status');hole=el.querySelector('.avix-mboot__hole');try{ctx=cv.getContext('2d');path=new Path2D(PATH);}catch(e){ctx=null;}if(!ctx){reveal();drop(el);return;}var size=Math.round(Math.min(Math.max(w.innerWidth*.14,110),190)),dpr=Math.min(w.devicePixelRatio||1,2);el.style.setProperty('--boot-size',size+'px');cv.width=cv.height=Math.round(size*dpr);var r=rng(2019);GRIDS.forEach(function(n){var c=sample(n);c.sort(function(a,b){return Math.hypot(a[0]-n/2,a[1]-n/2)+r()*n*.5-(Math.hypot(b[0]-n/2,b[1]-n/2)+r()*n*.5);});grids.push({n:n,cells:c});});render(0);tween(1100,io2,function(k){render(.86*k);},function(){until(function(){return loaded;},2200,function(){tween(320,io2,function(k){render(.86+.14*k);},function(){st.textContent='Welcome';setTimeout(zoom,160);});});});});
setTimeout(function(){if(!done){done=true;reveal();drop(d.getElementById('avix-boot'));}},6500);}

if(mode==='curtain')curtain();else full();

/* Leaving: an internal link draws the curtain up, then opens the next page. */
if(!EVERY)return;var leaving=false;
function leave(href){if(leaving)return;leaving=true;var el=d.getElementById('avix-curtain');if(el)el.parentNode.removeChild(el);d.body.insertAdjacentHTML('beforeend',CT);el=d.getElementById('avix-curtain');el.className='avix-ct is-leave';void el.offsetWidth;el.classList.add('is-in');setTimeout(function(){w.location.href=href;},760);setTimeout(function(){leaving=false;},4000);}
w.addEventListener('click',function(e){if(e.defaultPrevented||e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey)return;var a=e.target&&e.target.closest?e.target.closest('a[href]'):null;if(!a)return;if((a.target&&a.target!=='_self')||a.hasAttribute('download')||a.hasAttribute('data-no-transition')||a.closest('#wpadminbar'))return;var raw=a.getAttribute('href')||'';if(!raw||raw.charAt(0)==='#'||/^(mailto|tel|sms|javascript|whatsapp):/i.test(raw))return;var u;try{u=new URL(a.href,location.href);}catch(x){return;}if(u.origin!==location.origin||!/^https?:$/.test(u.protocol))return;if(u.pathname===location.pathname&&u.search===location.search)return;if(/^\/(wp-admin|wp-login|wp-json|feed)/.test(u.pathname)||/\.(pdf|zip|jpe?g|png|webp|gif|svg|mp4|docx?|xlsx?)$/i.test(u.pathname))return;e.preventDefault();leave(u.href);},false);
w.addEventListener('pageshow',function(e){if(e.persisted){var el=d.getElementById('avix-curtain');if(el&&el.parentNode)el.parentNode.removeChild(el);leaving=false;}});
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
			<p><?php esc_html_e( 'A pixel welcome while the page loads; the page’s own animations start right after. It never shows to search engines, in the Elementor editor or to visitors who prefer reduced motion. Recommended: the Avix mark, on every load, on the home page only, so every other page opens straight away.', 'avix-widgets' ); ?></p>
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
						<th scope="row"><label for="avix-loader-style"><?php esc_html_e( 'Style', 'avix-widgets' ); ?></label></th>
						<td>
							<select id="avix-loader-style" name="<?php echo esc_attr( $name ); ?>[style]">
								<option value="mark" <?php selected( 'mark', $o['style'] ); ?>><?php esc_html_e( 'Avix mark: builds from pixels, then the page appears through the logo', 'avix-widgets' ); ?></option>
								<option value="buddy" <?php selected( 'buddy', $o['style'] ); ?>><?php esc_html_e( 'Pixel buddy: assembles, waves and leaps away', 'avix-widgets' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-loader-frequency"><?php esc_html_e( 'How often', 'avix-widgets' ); ?></label></th>
						<td>
							<select id="avix-loader-frequency" name="<?php echo esc_attr( $name ); ?>[frequency]">
								<option value="always" <?php selected( 'always', $o['frequency'] ); ?>><?php esc_html_e( 'The full intro on every load, no curtain between pages', 'avix-widgets' ); ?></option>
								<option value="every" <?php selected( 'every', $o['frequency'] ); ?>><?php esc_html_e( 'Avix mark: the full intro on the first page of a visit, then a logo curtain between pages', 'avix-widgets' ); ?></option>
								<option value="session" <?php selected( 'session', $o['frequency'] ); ?>><?php esc_html_e( 'Only on the first page of a visit', 'avix-widgets' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="avix-loader-scope"><?php esc_html_e( 'Where', 'avix-widgets' ); ?></label></th>
						<td>
							<select id="avix-loader-scope" name="<?php echo esc_attr( $name ); ?>[scope]">
								<option value="all" <?php selected( 'all', $o['scope'] ); ?>><?php esc_html_e( 'Every page', 'avix-widgets' ); ?></option>
								<option value="home" <?php selected( 'home', $o['scope'] ); ?>><?php esc_html_e( 'The home page only (and its translations)', 'avix-widgets' ); ?></option>
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
