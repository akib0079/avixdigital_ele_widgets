<?php
define('WP_ADMIN', true);
// Integration tests use real WordPress + Elementor, with a disposable SQLite DB.
$_SERVER['HTTP_HOST'] = '127.0.0.1:4174';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
require __DIR__.'/wordpress/wp-load.php';
wp_set_current_user(1);
$widget = \Elementor\Plugin::instance()->widgets_manager->get_widget_types('avix-about-hero');
if (!$widget) throw new RuntimeException('About Hero is not registered.');
$controls = $widget->get_controls();
file_put_contents(__DIR__.'/controls.json',json_encode($controls));
$defaults = [];
foreach ($controls as $key=>$control) if (array_key_exists('default',$control)) $defaults[$key]=$control['default'];
foreach ($defaults['platforms'] as $index=>&$item) $item['_id']='platform'.$index;
unset($item);
$checks=[];
function check($condition,$label) { global $checks; $checks[$label]=(bool)$condition; if (!$condition) throw new RuntimeException($label); }
function render_widget($settings, $id='hero-test') {
    $instance = new \AvixWidgets\Widgets\About_Hero(['id'=>$id,'elType'=>'widget','widgetType'=>'avix-about-hero','settings'=>$settings], []);
    ob_start(); $instance->print_element(); return ob_get_clean();
}
$default_html=render_widget($defaults);
$preview='<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>About Avix Digital — Hero preview</title><link rel="stylesheet" href="widget-assets/css/base.css"><link rel="stylesheet" href="widget-assets/css/about-hero.css"><style>html,body{margin:0;padding:0}body{background:white}.elementor-widget{width:100%}</style><script src="widget-assets/js/about-hero.js" defer></script></head><body>'.str_replace(AVIX_EW_URL,'widget-assets/',$default_html).'</body></html>';
file_put_contents(dirname(__DIR__).'/previews/about-hero/index.html',$preview);
check(strpos($default_html,'data-avix-about')!==false,'Real Elementor renders the widget');
check(strpos($default_html,'avix-about__cta')!==false,'Default booking button renders');
check(strpos($default_html,'viewBox="2 2 20 20"')!==false,'Approved badge SVG is rendered');
check(strpos($default_html,'<header')===false,'Hero contains no header');
check(count($defaults['platforms'])===4,'Four editable default platforms');
check(!empty($controls['title']['dynamic']['active']) && !empty($controls['brand_logo']['dynamic']['active']) && !empty($controls['button_link']['dynamic']['active']),'Text, media and URL accept dynamic tags');
check(!empty($controls['tile_width']['is_responsive']) && !empty($controls['title_typography_font_size']['is_responsive']),'Responsive style controls registered');
$unsafe=array_replace($defaults,['title'=>'[<script>alert(1)</script>]','title_tag'=>'script','button_link'=>['url'=>'javascript:alert(1)'],'platforms'=>[['name'=>'<img onerror="alert(1)">','icon'=>'code','_id'=>'bad"class']]]);
$unsafe_html=render_widget($unsafe,'escaped');
check(strpos($unsafe_html,'<script>alert')===false && strpos($unsafe_html,'&lt;script&gt;')!==false,'HTML in dynamic text is escaped');
check(strpos($unsafe_html,'javascript:')===false,'Unsafe booking URL omitted');
check(strpos($unsafe_html,'<h1')!==false,'Headline tag is allowlisted');
$empty=render_widget(array_replace($defaults,['show_expertise'=>'','title'=>'','button_text'=>'']),'empty');
check(strpos($empty,'avix-about__expertise')===false && strpos($empty,'avix-about__cta')===false,'Hidden sections and empty button are omitted');
check(strpos(render_widget($defaults,'other'),'avix-about-title-other')!==false,'Heading identifiers are unique by instance');

function create_page($slug,$settings) {
    $existing=get_page_by_path($slug);
    $id=$existing ? $existing->ID : wp_insert_post(['post_type'=>'page','post_title'=>'About Hero — '. $slug,'post_name'=>$slug,'post_status'=>'publish']);
    update_post_meta($id,'_wp_page_template','elementor_canvas');
    update_post_meta($id,'_elementor_edit_mode','builder');
    update_post_meta($id,'_elementor_version',ELEMENTOR_VERSION);
    update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode([
        ['id'=>'aboutwrap','elType'=>'container','settings'=>['content_width'=>'full','width'=>['unit'=>'%','size'=>100],'padding'=>['unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true],'gap'=>['unit'=>'px','size'=>0]],'elements'=>[
            ['id'=>'abouthero','elType'=>'widget','widgetType'=>'avix-about-hero','settings'=>$settings,'elements'=>[]]
        ]]
    ])));
    return $id;
}
$page_id=create_page('about-widget-test',[]);
$custom=array_replace($defaults,[
    'title'=>'Your [ideas.] Our craft.','button_text'=>'Talk to our team','brand_label'=>'Avix studio',
    'platforms'=>[['name'=>'Custom platform','icon'=>'upload','logo'=>['url'=>AVIX_EW_URL.'assets/images/avix-mark.webp'],'_id'=>'custom']],
    'pause_text'=>'Stop motion','resume_text'=>'Continue motion','pause_hover'=>'yes',
    'title_typography_typography'=>'custom','title_typography_font_size'=>['size'=>48,'unit'=>'px'],
    'title_typography_font_size_mobile'=>['size'=>28,'unit'=>'px'],
    'tile_width'=>['size'=>130,'unit'=>'px'],'tile_background'=>'#ffffff',
    'button_background'=>'#112233','check_color'=>'#198754','duration'=>['size'=>12,'unit'=>'s']
]);
$custom_id=create_page('about-widget-custom',$custom);
$static_id=create_page('about-widget-static',array_replace($defaults,['animate'=>'']));
$report=['wordpress'=>$GLOBALS['wp_version'],'elementor'=>ELEMENTOR_VERSION,'plugin'=>AVIX_EW_VERSION,'control_count'=>count($controls),'checks'=>$checks,'pages'=>['default'=>$page_id,'custom'=>$custom_id,'static'=>$static_id]];
file_put_contents(__DIR__.'/elementor-verification.json',json_encode($report,JSON_PRETTY_PRINT));
echo json_encode($report,JSON_PRETTY_PRINT).PHP_EOL;
