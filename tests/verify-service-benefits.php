<?php
define('WP_ADMIN',true);
$_SERVER['HTTP_HOST']='127.0.0.1:4174';
$_SERVER['SERVER_NAME']='127.0.0.1';
require __DIR__.'/wordpress/wp-load.php';
wp_set_current_user(1);
$widget=\Elementor\Plugin::instance()->widgets_manager->get_widget_types('avix-service-benefits');
if(!$widget)throw new RuntimeException('Service Benefits not registered');
$controls=$widget->get_controls();
$defaults=[];
foreach($controls as $key=>$control)if(array_key_exists('default',$control))$defaults[$key]=$control['default'];
foreach($defaults['cards'] as $index=>&$item)$item['_id']='service'.$index;
unset($item);
$checks=[];
function benefit_check($condition,$label){global $checks;$checks[$label]=(bool)$condition;if(!$condition)throw new RuntimeException($label);}
function benefit_render($settings,$id='benefit-test'){
 $instance=new \AvixWidgets\Widgets\Service_Benefits(['id'=>$id,'elType'=>'widget','widgetType'=>'avix-service-benefits','settings'=>$settings],[]);
 ob_start();$instance->print_element();return ob_get_clean();
}
$html=benefit_render($defaults);
benefit_check(substr_count($html,'<article')===3,'Three approved cards render through real Elementor');
benefit_check(substr_count($html,'srcset=')===3,'Bundled artwork includes responsive derivatives');
benefit_check(strpos($html,'avix-benefits__arm')!==false,'Existing avatar rendered');
foreach(['title','description','help_link','avatar'] as $key)benefit_check(!empty($controls[$key]['dynamic']['active']),'Dynamic '.$key);
foreach($controls['cards']['fields'] as $field)if(in_array($field['name'],['title','description','image','image_alt','link','link_text']))benefit_check(!empty($field['dynamic']['active']),'Dynamic card '.$field['name']);
benefit_check(!empty($controls['title_typography_font_size']['is_responsive'])&&!empty($controls['card_padding']['is_responsive']),'Responsive typography and padding controls');
$unsafe=$defaults;$unsafe['title']='[<script>alert(1)</script>]';$unsafe['title_tag']='script';$unsafe['card_tag']='script';$unsafe['cards'][0]['title']='<img onerror="alert(1)">';$unsafe['cards'][0]['link']['url']='javascript:alert(1)';$unsafe['help_link']['url']='javascript:alert(2)';
$escaped=benefit_render($unsafe);
benefit_check(strpos($escaped,'<script>alert')===false&&strpos($escaped,'&lt;script&gt;')!==false,'Dynamic text escaped');
benefit_check(strpos($escaped,'javascript:')===false,'Unsafe links omitted');
benefit_check(strpos($escaped,'<h2')!==false&&strpos($escaped,'<h3')!==false,'Heading tags allowlisted');
$hidden=benefit_render(array_replace($defaults,['title'=>'','cards'=>[],'show_help'=>'']));
benefit_check(strpos($hidden,'avix-benefits__help')===false&&strpos($hidden,'<article')===false&&strpos($hidden,'aria-label=')!==false,'Empty and hidden sections handled');
benefit_check(strpos(benefit_render($defaults,'second-instance'),'avix-benefits-title-second-instance')!==false,'Identifiers unique by instance');
$external=$defaults;$external['cards'][0]['link']=['url'=>'https://example.com/','is_external'=>true,'nofollow'=>true];
benefit_check(strpos(benefit_render($external),'noopener noreferrer')!==false&&strpos(benefit_render($external),'nofollow')!==false,'External links get appropriate relation attributes');
class Avix_Test_Benefit_Tag extends \Elementor\Core\DynamicTags\Tag {
 public function get_name(){return 'avix-test-benefit-tag';}
 public function get_title(){return 'Integration test';}
 public function get_group(){return 'site';}
 public function get_categories(){return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];}
 public function render(){echo 'Dynamic service benefits';}
}
\Elementor\Plugin::instance()->dynamic_tags->register(new Avix_Test_Benefit_Tag());
$dynamic=$defaults;$dynamic['__dynamic__']['title']='[elementor-tag id="test" name="avix-test-benefit-tag" settings="%7B%7D"]';
benefit_check(strpos(benefit_render($dynamic),'Dynamic service benefits')!==false,'Dynamic tag resolves at render time');

function benefit_page($slug,$settings,$multiple=false){
 $existing=get_page_by_path($slug);$id=$existing?$existing->ID:wp_insert_post(['post_type'=>'page','post_title'=>'Service Benefits — '.$slug,'post_name'=>$slug,'post_status'=>'publish']);
 update_post_meta($id,'_wp_page_template','elementor_canvas');update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_elementor_version',ELEMENTOR_VERSION);
 $elements=[['id'=>'benefitsone','elType'=>'widget','widgetType'=>'avix-service-benefits','settings'=>$settings,'elements'=>[]]];
 if($multiple)$elements[]=['id'=>'benefitstwo','elType'=>'widget','widgetType'=>'avix-service-benefits','settings'=>$settings,'elements'=>[]];
 update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode([['id'=>'benefitwrap','elType'=>'container','settings'=>['content_width'=>'full','width'=>['unit'=>'%','size'=>100],'padding'=>['unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true],'gap'=>['unit'=>'px','size'=>0]],'elements'=>$elements]])));
 \Elementor\Plugin::instance()->files_manager->clear_cache();return $id;
}
$upload=wp_upload_dir();$source=AVIX_EW_PATH.'assets/images/service-benefits/service-2-1152.webp';$destination=$upload['path'].'/avix-benefit-test.webp';copy($source,$destination);
$attachment=attachment_url_to_postid($upload['url'].'/avix-benefit-test.webp');
if(!$attachment)$attachment=wp_insert_attachment(['post_mime_type'=>'image/webp','post_title'=>'Test artwork','post_status'=>'inherit'],$destination);
require_once ABSPATH.'wp-admin/includes/image.php';
wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$destination));
update_post_meta($attachment,'_wp_attachment_image_alt','Media library artwork alternative text');
$custom=$defaults;
$custom['title']='Your [next chapter.] Built together.';
$custom['title_typography_typography']='custom';$custom['title_typography_font_size']=['size'=>46,'unit'=>'px'];$custom['title_typography_font_size_mobile']=['size'=>31,'unit'=>'px'];
$custom['card_radius']=['size'=>25,'unit'=>'px'];$custom['gap']=['size'=>30,'unit'=>'px'];$custom['gap_mobile']=['size'=>18,'unit'=>'px'];
$custom['link_hover']='#166534';$custom['card_background']='#fafbfc';
$custom['cards'][0]['title']='Custom service offering';$custom['cards'][0]['image']=['id'=>$attachment,'url'=>wp_get_attachment_url($attachment)];$custom['cards'][0]['image_alt']='';$custom['cards'][0]['link']=['url'=>'https://example.com/services/','is_external'=>true,'nofollow'=>true];
$custom['cards'][0]['accent']='#9a3412';
$custom['cards'][]=array_replace($custom['cards'][1],['_id'=>'fourth','title'=>'Another service','description'=>'A fourth editable offering demonstrates flexible card counts and full text reflow.']);
$custom['avatar']=['url'=>AVIX_EW_URL.'assets/images/avix-mark.webp'];
$custom['image_radius']=['size'=>19,'unit'=>'px'];
$library_html=benefit_render($custom);
benefit_check(strpos($library_html,'Media library artwork alternative text')!==false&&strpos($library_html,'srcset=')!==false,'Media library artwork uses responsive sources and metadata alt');
$pages=['default'=>benefit_page('benefits-widget-test',[]),'custom'=>benefit_page('benefits-widget-custom',$custom),'static'=>benefit_page('benefits-widget-static',array_replace($defaults,['hover_motion'=>'','avatar_motion'=>''])),'multiple'=>benefit_page('benefits-widget-multiple',[],true),'no_avatar'=>benefit_page('benefits-widget-no-avatar',array_replace($defaults,['show_avatar'=>'']))];
$report=['wordpress'=>$GLOBALS['wp_version'],'elementor'=>ELEMENTOR_VERSION,'plugin'=>AVIX_EW_VERSION,'control_count'=>count($controls),'checks'=>$checks,'pages'=>$pages];
file_put_contents(__DIR__.'/service-benefits-elementor-verification.json',json_encode($report,JSON_PRETTY_PRINT));
file_put_contents(__DIR__.'/service-benefits-controls.json',json_encode($controls));
echo json_encode($report,JSON_PRETTY_PRINT).PHP_EOL;
