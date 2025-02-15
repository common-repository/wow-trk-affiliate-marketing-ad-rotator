<?php
/*
Plugin Name: WOW TRK Ads
Description: WOW TRK - Banner Ad Management plugin. Edit options under 'Settings > WOW TRK' also add it as widget under 'Appearance > Widgets'.
Version: 1.3.3
Author: WOW TRK - International Affiliate Network
Author URI: https://www.wowtrk.com/
*/
global $wowtrk_affiliate;

function wowtrk_get_ad_code( $options ){
	global $wowtrk_affiliate;
	$campaign_id = $options['campaign_id'];
	$affiliate_id = $options['affiliate_id'];
	$sub_id = $options['sub_id'];
	$request_type = $options['request_type'];
	$banner = $options['banner'];
	$banner_size = $options['banner_size'];
	$banner_type = $options['banner_type'];
	$banner_alignment = $options['banner_alignment'];
	$https = $options['https'];
	$banner_position = $options['banner_position'];
	$show_referral_link = $options['show_referral_link'];
	
	if(!$affiliate_id) return'';
	
	$ad_url = 'http://t.wowtrk.com/aff_ad?campaign_id='.$campaign_id.'&aff_id='.urlencode($affiliate_id);
	
	if ($https == 'Yes')
		$ad_url = 'https://t.wowtrk.com/aff_ad?campaign_id='.$campaign_id.'&aff_id='.urlencode($affiliate_id);
	
	if($sub_id)
		$ad_url .= '&aff_sub='.urlencode($sub_id);

	$referral_link = '';
	if($show_referral_link){
		$referral_link .= '<div class="ads-by-text" style="clear: both; width: '.$banner['width'].'px;';
		if($banner_alignment == 'left' || $banner_alignment == 'right')
			$referral_link .= 'float: '.$banner_alignment.';';
		else
			$referral_link .= 'margin: 0 auto;';
		$referral_link .= 'text-align: right; font-size: 11px;">Ads by <a href="https://www.wowtrk.com/signup-affiliate/?ref_id='. urlencode($affiliate_id) .'" target="_blank">WOW TRK</a></div>
		<div style="clear: both;"></div>';
	}
		
	if($banner_type == 'JavaScript'){
		$div_id = 'wowtrk_'.mt_rand(100000,999999);
			
		$html .= '<!-- Javascript Ad Tag: '.$campaign_id.' -->
<div class="wowtrk-ad" id="'.$div_id.'" style="line-height: 1em; text-align: '.$banner_alignment.'"><noscript><iframe src="'.$ad_url.'&format=iframe" scrolling="no" frameborder="0" marginheight="0" marginwidth="0" width="468" height="60"></iframe></noscript></div>'. $referral_link .'<!-- // End Ad Tag -->';
		$wowtrk_affiliate->add_script('<script src="'.$ad_url.'&format=js&divid='.$div_id.'" type="text/javascript"></script>');
	}
	else{
		$html .= '<!-- iFrame Ad Tag: '.$campaign_id.' -->
			<div class="wowtrk-ad" style="line-height: 1em; text-align: '.$banner_alignment.'">
			<iframe src="'.$ad_url.'&format=iframe" scrolling="no" frameborder="0" marginheight="0" marginwidth="0" width="'.$banner['width'].'" height="'.$banner['height'].'"></iframe>'. $referral_link .'</div>
		<!-- // End Ad Tag -->';
	}
	return $html;
}

class wowtrk_affiliate{
	private $ad_options = array(
		52 => array('name' => '468x60', 'width' => '468', 'height' => '60'),
		54 => array('name' => '728x90', 'width'=>'728', 'height' => '90'),
		56 => array('name' => '300x250', 'width'=>'300', 'height' => '250'),
		60 => array('name' => '125x125', 'width'=>'125', 'height' => '125')
	);
	private $options = array();
	private $js = '';
	
	function wowtrk_affiliate(){
		$this->options = get_option('wowtrk-affiliate-options',$defaults);
		
		add_action( 'wp_footer', array($this, 'footer_scripts') );
		add_action( 'widgets_init', array($this, 'register_widgets') );
	
		add_filter( 'the_content', array($this,'content_filter') );
		
		if(is_admin())
			$this->admin_features();
	}
	
	function content_filter( $content ){
		if(!is_single() && !is_page()) return $content;
		
		if(!isset($this->ad_options[$this->options['banner_size']])) $this->options['banner_size'] = 52;
		$this->options['campaign_id'] = $this->options['banner_size'];
		$this->options['banner'] = $this->ad_options[$this->options['campaign_id']];
		
		switch( get_post_type() ){
			case'post':
				if(!$this->options['show_on_posts']) return $content;
				break;
			case'page':
				if(!$this->options['show_on_pages']) return $content;
				break;
			default:
				return $content;
				break;
		}

		$html = wowtrk_get_ad_code( $this->options );
		
		if($this->options['banner_position'] == 'above_content')
			$content = $html . $content;
		else
			$content .= $html;
		return $content;
	}
	
	function admin_features(){
		add_action('admin_menu', array($this, 'admin_menu') );
		
		if(!$this->options['affiliate_id'])
			add_action('admin_notices', array($this, 'admin_notice'));
	}
	
	function admin_menu(){
		add_options_page( 'WOW TRK Ads: Settings', 'WOW TRK', 'administrator', 'wowtrk-options', array($this, 'wowtrk_options') );
	}
	
	function admin_notice(){
		if(isset($_GET['page']) && $_GET['page'] == 'wowtrk-options') return;
		?>
		<div class="updated">
			<p><?php _e( 'You need to configure the WOW TRK Ads plugin before you can display ads from WOW TRK on your website. <a href="'.admin_url('options-general.php?page=wowtrk-options').'">Click Here</a> to configure it now.', 'wowtrk' ); ?></p>
		</div>
		<?php
	}
	
	function register_widgets(){
		register_widget( 'WOWTrk_Widget' );
	}
	
	function footer_scripts(){
		echo $this->js;
	}
	
	function add_script( $script ){
		$this->js .= $script;
	}
	
	function wowtrk_options(){
		$defaults = array(
			'affiliate_id' => '',
			'sub_id' => '',
			'banner_size' => 52,
			'banner_alignment' => 'center',
			'https' => 'Yes',
			'banner_position' => 'below_content',
			'show_on_posts' => true,
			'show_on_pages' => false,
			'show_referral_link' => true
		);
		$updated = false;
		
		if(isset($_POST['wowtrk_action']) && $_POST['wowtrk_action'] == 'save_options'){
			$banner_size = (isset($_POST['banner_size']) && isset($this->ad_options[$_POST['banner_size']])) ? $_POST['banner_size'] : 52;
			$banner_type = (isset($_POST['banner_type']) && $_POST['banner_type'] == 'JavaScript') ? 'JavaScript' : 'iFrame';
			$banner_alignment = (in_array($_POST['banner_alignment'],array('center','left','right'))) ? $_POST['banner_alignment'] : 'center';
			$https = (in_array($_POST['https'],array('No','Yes'))) ? $_POST['https'] : 'No';
			$banner_position = (in_array($_POST['banner_position'],array('below_content','above_content'))) ? $_POST['banner_position'] : 'below_content';
			$show_on_posts = (isset($_POST['show_on_posts']) && $_POST['show_on_posts'] == 1) ? true : false;
			$show_on_pages = (isset($_POST['show_on_pages']) && $_POST['show_on_pages'] == 1) ? true : false;
			$show_referral_link = (isset($_POST['show_referral_link']) && $_POST['show_referral_link'] == 1) ? true : false;
			$options = array(
				'affiliate_id' => $_POST['affiliate_id'],
				'sub_id' => $_POST['sub_id'],
				'banner_size' => $banner_size,
				'banner_type' => $banner_type,
				'banner_alignment' => $banner_alignment,
				'https' => $https,
				'banner_position' => $banner_position,
				'show_on_posts' => $show_on_posts,
				'show_on_pages' => $show_on_pages,
				'show_referral_link' => $show_referral_link
			);
			update_option('wowtrk-affiliate-options',$options);
			
			$updated = true;
		}
		$options = get_option('wowtrk-affiliate-options',$defaults);
		echo '<h1>WOW TRK Ads - Settings</h1>
Configure the plugin settings below to show WOW TRK Ads on your website. You can choose where to display ads on your website and what size of ads. Additionally you can also configure WOW TRK Widgets to display ads on your website. In order for commission to track to your account you must enter your unique Affiliate ID below.
';
		if($updated)
			echo '<div class="updated">Settings Updated!</div>';
		echo'<form method="post" action="'.admin_url('options-general.php?page=wowtrk-options').'">
		<table class="form-table">
		<tr><th scope="row">Affiliate ID</th><td><input type="text" name="affiliate_id" value="'.$options['affiliate_id'].'"><br>
Unsure what your Affiliate ID is?<BR>
<a href="https://www.wowtrk.com/login" target="_blank">Login</a> to your account and go to My Account -> Account Details, on the account details page under Company Details you will find your ID.
</td>
</tr>
		<tr><th scope="row">Sub ID (optional)</th><td><input type="text" name="sub_id" value="'.$options['sub_id'].'"></td></tr>
		<tr><th scope="row">Banner Size</th>
			<td>
				<select name="banner_size">';
					foreach($this->ad_options as $key => $option)
						echo'<option value="'.$key.'" '.(($key == $options['banner_size']) ? 'selected="selected"' : '').'>'.$option['name'].'</option>';
				echo'</select>
			</td>
		</tr>
		<tr><th scope="row">Banner Type</th>
			<td>
				<select name="banner_type">';
					foreach(array('JavaScript','iFrame') as $option)
						echo '<option value="'.$option.'" '.(($option == $options['banner_type']) ? 'selected="selected"' : '').'>'.$option.'</option>';
				echo'</select>
			</td>
		</tr>
		<tr><th scope="row">Banner Alignment</th>
			<td>
				<select name="banner_alignment">';
					foreach(array('center','left','right') as $option)
						echo '<option value="'.$option.'" '.(($option == $options['banner_alignment']) ? 'selected="selected"' : '').'>'.ucfirst($option).'</option>';
				echo'</select>
			</td>
		</tr>
		
		<tr><th scope="row">HTTPS</th>
			<td>
				<select name="https">';
					foreach(array('No','Yes') as $option)
						echo '<option value="'.$option.'" '.(($option == $options['https']) ? 'selected="selected"' : '').'>'.ucfirst(str_replace('_',' ',$option)).'</option>';
				echo'</select><BR>
        Enable this to serve ads over HTTPS instead of HTTP. It is recommended you serve ads over HTTPS, serving them over HTTP can cause tracking issues. Only disable this if HTTPS ads are causing issues on your website.
			</td>
		</tr>
		
		
		<tr><th scope="row">Display at</th>
			<td>
				<select name="banner_position">';
					foreach(array('below_content','above_content') as $option)
						echo '<option value="'.$option.'" '.(($option == $options['banner_position']) ? 'selected="selected"' : '').'>'.ucfirst(str_replace('_',' ',$option)).'</option>';
				echo'</select>
			</td>
		</tr>
		<tr><th scope="row">Display on</th>
			<td>
				<label><input type="checkbox" name="show_on_posts" value="1" '.(($options['show_on_posts'])?'checked="checked"':'').'> Posts</label><br/>
				<label><input type="checkbox" name="show_on_pages" value="1" '.(($options['show_on_pages'])?'checked="checked"':'').'> Pages</label>
			</td>
		</tr>
		<tr><th scope="row">Show Referral Link</th>
			<td>
				<label><input type="checkbox" name="show_referral_link" value="1" '.(($options['show_referral_link'])?'checked="checked"':'').'> Display \'Ads by WOW TRK\' link and earn 3% for life on any affiliates you refer.</label><br/>
			</td>
		</tr>
		<tr><th scope="row"></th><td><input type="hidden" name="wowtrk_action" value="save_options"><input type="submit" value="Update Settings"></td></tr>
		</table>
		</form>

<BR><BR>
If you need any help setting up the WOW TRK Ads plugin, please visit <a href="https://help.wowtrk.com">help.wowtrk.com</a> or email info@wowtrk.com.
<BR><BR><BR>
<b>Not joined WOW TRK yet?</b> Open an account using the link below and start earning from your website!<br>
<A href="https://www.wowtrk.com/signup-affiliate/?utm_source=wordpress&utm_medium=plugin" target="_blank">https://www.wowtrk.com/signup-affiliate/</a>
		';
	}
}

class WOWTrk_Widget extends WP_Widget {
	private $ad_options = array(
		60 => array('name' => '125x125', 'width' => '125', 'height' => '125'),
		56 => array('name' => '300x250', 'width'=>'300', 'height' => '250'),
		62 => array('name' => '120x600', 'width'=>'120', 'height' => '600')
	);
	
	public function __construct() {
		parent::__construct(
	 		'wowtrk_affiliate_widget', // Base ID
			'WOW TRK Ad', // Name
			array( 'description' => __( 'A WOW TRK Affiliate Ad', 'wowtrk' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		extract( $args );

		if(!isset($this->ad_options[$instance['banner_size']])) $instance['banner_size'] = 60;
		$instance['campaign_id'] = $instance['banner_size'];
		$instance['banner'] = $this->ad_options[$instance['campaign_id']];
		
		$defaults = array(
			'show_referral_link' => true
		);
		
		$options = get_option('wowtrk-affiliate-options',$defaults);
		
		$instance['show_referral_link'] = $options['show_referral_link'];
		
		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		
		echo wowtrk_get_ad_code( $instance );
		
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['affiliate_id'] = strip_tags( $new_instance['affiliate_id'] );
		$instance['sub_id'] = strip_tags( $new_instance['sub_id'] );
		if(!isset($this->ad_options[$new_instance['banner_size']]))
			$new_instance['banner_size'] = 60;
		$instance['banner_size'] = $new_instance['banner_size'];
		$instance['banner_type'] = $new_instance['banner_type'];
		$instance['banner_alignment'] = $new_instance['banner_alignment'];

		return $instance;
	}

	public function form( $instance ) {
		$title = (isset($instance[ 'title' ])) ? $instance['title'] : '';
		$affiliate_id = (isset($instance[ 'affiliate_id' ])) ? $instance['affiliate_id'] : '';
		$sub_id = (isset($instance[ 'sub_id' ])) ? $instance['sub_id'] : '';
		$banner_size = $instance['banner_size'];
		$banner_type = $instance['banner_type'];
		$banner_alignment = $instance['banner_alignment'];


		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (optional):' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'affiliate_id' ); ?>"><?php _e( 'Affiliate ID:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'affiliate_id' ); ?>" name="<?php echo $this->get_field_name( 'affiliate_id' ); ?>" type="text" value="<?php echo esc_attr( $affiliate_id ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'sub_id' ); ?>"><?php _e( 'Sub ID (optional):' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'sub_id' ); ?>" name="<?php echo $this->get_field_name( 'sub_id' ); ?>" type="text" value="<?php echo esc_attr( $sub_id ); ?>" />
		</p>
		<p>
			<label><?php _e( 'Banner Size:' ); ?></label> 
			<select class="widefat" name="<?php echo $this->get_field_name( 'banner_size' ); ?>">
				<?php
				foreach($this->ad_options as $key => $option)
					echo'<option value="'.$key.'" '.(($key == $banner_size) ? 'selected="selected"' : '').'>'.$option['name'].'</option>';
				?>
			</select>
		</p>
		<p>
			<label><?php _e( 'Banner Type:' ); ?></label> 
			<select class="widefat" name="<?php echo $this->get_field_name( 'banner_type' ); ?>">
				<?php
				foreach(array('JavaScript','iFrame') as $option)
				echo '<option value="'.$option.'" '.(($option == $banner_type) ? 'selected="selected"' : '').'>'.$option.'</option>';
				?>
			</select>
		</p>
		<p>
			<label><?php _e( 'Banner Alignment:' ); ?></label> 
			<select class="widefat" name="<?php echo $this->get_field_name( 'banner_alignment' ); ?>">
				<?php
				foreach(array('center','left','right') as $option)
				echo '<option value="'.$option.'" '.(($option == $banner_alignment) ? 'selected="selected"' : '').'>'.ucfirst($option).'</option>';
				?>
			</select>
		</p>
		<?php 
	}

}

$wowtrk_affiliate = new wowtrk_affiliate();



?>
