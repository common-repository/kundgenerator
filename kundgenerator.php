<?php
defined( 'ABSPATH' ) or die( 'This plugin must be run within the scope of WordPress.' );
/*
Plugin Name: Kundgenerator+
Plugin URI: http://www.kundgenerator.se
Description: Adds the Kundgenerator+ tracking code to your website
Version: 1.0.6
Author: Kundgenerator+
Author URI: http://www.multinet.se
Requires at least: 3.3
Tested up to: 4.9
Text Domain: kundgenerator
Domain Path: /languages
*/
	class Kundgenerator
	{
		public static $ScriptKey = 'kundgenerator_script';
		public static $OwnScriptKey = 'kundgenerator_own_script';
		public static $SiteIdKey = 'kundgenerator_site_id';
	
		public function GetHashKey() { return $_REQUEST['hashKey']; }
		public function GetAlreadyConnected() { return !self::IsNullOrEmptyString(get_option(self::$ScriptKey)); }
		public function GetHasHashKey() { return !self::IsNullOrEmptyString($_REQUEST['hashKey']); }
		public function GetWebcode() { return get_option(self::$ScriptKey); }
		public function GetOwnScriptcode() { return get_option(self::$OwnScriptKey); }
		public function GetSiteId() { return get_option(self::$SiteIdKey); }
		public function GetForceUpdate() { return $_REQUEST['forceUpdate'] == "true"; }
		
		public static function Init()
		{
			delete_option(self::$ScriptKey, '');
			delete_option(self::$OwnScriptKey, '');
			delete_option(self::$SiteIdKey, '');
		}
		public static function LoadLanguage()
		{
			load_plugin_textdomain( 'kundgenerator' );
		}
				
		public static function MenuItems()
		{
			add_options_page(__('Kundgenerator+', 'kundgenerator'), __('Kundgenerator+', 'kundgenerator'), 8, basename(__FILE__), 'KGAdmin');
		}
		
		public static function PrintScript()
		{
			$code = get_option(self::$ScriptKey);
			$ownCodeScript = get_option(self::$OwnScriptKey);
			$ownCodeScriptTag = self::IsNullOrEmptyString($ownCodeScript) ? "" : " <script type=\"text/javascript\">".$ownCodeScript."</script>";
			$completeScriptCode = get_option(self::$ScriptKey) . $ownCodeScriptTag;
			echo stripslashes($completeScriptCode); 
		}
				
		public static function SetScript()
		{
			if($_REQUEST['save'] == "true" && $_REQUEST['page'] == "kundgenerator.php")
			{
				$code = $_POST['txtWebcode'];
				delete_option(self::$ScriptKey);
				update_option(self::$ScriptKey, $code);
				
				$ownCodeScript = $_POST['txtOwnScriptCode'];
				delete_option(self::$OwnScriptKey);
				update_option(self::$OwnScriptKey, $ownCodeScript);
				
				$siteId = $_POST['hdnSiteId'];
				delete_option(self::$SiteIdKey);
				update_option(self::$SiteIdKey, $siteId);
				
				$baseClass = new Kundgenerator();
				$forceUpdate = $baseClass->GetForceUpdate();
				
				$redirectUrl = 	($forceUpdate 
								? get_bloginfo('wpurl') . "/wp-admin/options-general.php?page=kundgenerator.php&forceUpdate=true"
								: get_bloginfo('wpurl') . "/wp-admin/options-general.php?page=kundgenerator.php&updated=true");
				
				//Omdirigerar webbläsaren tillbaka till vårt plugin och visar att det är uppdaterat
				header('Location: ' . $redirectUrl);
			}
		}
		
		public static function SettingLink($links)
		{
			$settings_link = '<a href="options-general.php?page=kundgenerator.php">' . __('Settings') . '</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}
		
		public static function SettingsScript($hook)
		{
			if('settings_page_kundgenerator' != $hook)
				return;
					
			wp_enqueue_script('kundgeneratorScript', plugins_url('/kundgenerator.js', __FILE__));
			
			$jsPhrases = array(
				'selectSite' => __( 'Select site', 'kundgenerator' ),
				'enterWebcode' => __( 'Enter web code!', 'kundgenerator' )
			);
			
			wp_localize_script('kundgeneratorScript', 'l10nObj', $jsPhrases);
			wp_enqueue_script('jquery');
		}
		
		public static function IsNullOrEmptyString($question){
			return (!isset($question) || trim($question)==='');
		}
		
		public static function GetFullUrl()
		{
			$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
			$sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
			$protocol = substr($sp, 0, strpos($sp, "/")) . $s;
			$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
			return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
		}
	}
	 
	$plugin = plugin_basename(__FILE__); 
	add_filter("plugin_action_links_$plugin", 'Kundgenerator::SettingLink' );
	add_action('wp_footer', 'Kundgenerator::PrintScript');
	
	function KGAdmin() 
	{
		if (!current_user_can('manage_options')) //Om man inte är admin skall vi inte visa något för säkerhets skull
		{
			wp_die( __('You do not have sufficient permissions to access this page.'));
		}
		
		//Kontrollera om den ska ansluta.
		$baseClass = new Kundgenerator();
				
		$alreadyConnected = $baseClass->GetAlreadyConnected();
		$hasHashKey = $baseClass->GetHasHashKey();
		$webCode = $baseClass->GetWebcode();
		$ownScriptCode = $baseClass->GetOwnScriptcode();
		$siteId = $baseClass->GetSiteId();
		$forceUpdate = $baseClass->GetForceUpdate();
		
		$kgUrl = "https://app1.kundgenerator.se/Pub/ApiWordpressAuthorize";
		$orginalUrl = urlencode(str_replace("&updated=true", "", Kundgenerator::GetFullUrl()));
		$denyUrl = urlencode(get_bloginfo('wpurl')."/wp-admin/plugins.php");
		$kgCompleteUrl = $kgUrl . "?denyUrl=" . $denyUrl . "&orginalUrl=" . $orginalUrl;
				
		if(!$alreadyConnected && !$hasHashKey)
		{			
			?>
				<script type="text/javascript">
					window.location.href = '<?php echo($kgCompleteUrl) ?>';
				</script>
			<?php	
		} else {
			?>
			<script type="text/javascript">
				var kgCompleteUrl = '<?php echo($kgCompleteUrl.urlencode("&forceUpdate=true")) ?>';
				var kgSiteId = '<?php echo($siteId) ?>';
			</script>
			<?php	
		}
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		
		<h2><?php _e("Kundgenerator+ Web code", "kundgenerator"); ?></h2>
		<form action="<?php  echo get_bloginfo('wpurl'); ?>/wp-admin/options-general.php?page=kundgenerator.php&save=true&hashKey=<?php echo($_REQUEST['hashKey']) ?>" method="POST">
			<?php if((!$alreadyConnected && $hasHashKey) || $forceUpdate) { ?>
				<table class="form-table">
					<tbody>
						<tr valign="top" id="trConnectError" style="display:none;">
							<td colspan="2"><span class="spanConnectError errorMessage" style="color:red;"></span></td>
						</tr>
						<tr valign="top" id="trDllSite" style="display:none;">
							<th scope="row"><label for="ddlSite" ><?php _e("Site", "kundgenerator"); ?>:</label></th>
							<td>
								<select id="ddlSite" style="width:460px;"></select>
							</td>
						</tr>
						<tr valign="top" id="trTxtWebcode" style="display:none;">
							<th scope="row"><label for="txtWebcode"><?php _e("Web code", "kundgenerator"); ?>:</label></th>
							<td>
								<textarea id="txtWebcode" name="txtWebcode" type="text" class="regular-text" style="height:220px; width:460px;" readonly="readonly"></textarea>
								<br>
								<em id="txtWebcodeInfo" style="display:none;">
									<?php _e("You can “Save changes” below to add the web code on your site.", "kundgenerator"); ?>
								</em>
								<input type="hidden" name="hdnSiteId" type="hidden" id="hdnSiteId" />
							</td>
						</tr>
						<tr valign="top" id="trTxtOwnScriptCode" style="display:none;">
							<th scope="row"><label for="txtOwnScriptCode"><?php _e("Custom script code (Javascript)", "kundgenerator"); ?>:</label></th>
							<td>
								<code>&lt;script type="text/javascript"&gt;</code><br>
								<textarea id="txtOwnScriptCode" name="txtOwnScriptCode" type="text" class="regular-text" style="height:180px; width:460px;"><?php echo(stripslashes($ownScriptCode)); ?></textarea><br>
								<code>&lt;/script&gt;</code>
								<br>
								<em>
									<?php _e("This field is used if you want to add custom scripts. (Optional)", "kundgenerator"); ?>
								</em>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit"><input type="submit" name="btnSaveWebcode" id="btnSaveWebcode" class="button button-primary" value="<?php esc_attr_e("Save changes", "kundgenerator"); ?>"></p>
			<?php } else { ?>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label><?php _e("Current web code", "kundgenerator"); ?>:</label></th>
							<td>
								<?php echo(htmlentities(stripslashes($webCode))); ?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label><?php _e("Custom script code (Javascript)", "kundgenerator"); ?>:</label></th>
							<td>
								<?php echo(html_entity_decode(stripslashes($ownScriptCode))); ?>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit"><input type="button" name="btnChangeWebcode" id="btnChangeWebcode" class="button button-primary" value="<?php esc_attr_e("Change web code", "kundgenerator");  ?>"></p>
			<?php } ?>
		</form>
   </div>
<?php
	}
	add_action('admin_menu', 'Kundgenerator::MenuItems');
	add_action('admin_enqueue_scripts', 'Kundgenerator::SettingsScript');
	register_activation_hook(__FILE__, array('Kundgenerator', 'Init'));
	add_action('init', 'Kundgenerator::SetScript', 9999);
	add_action('plugins_loaded', 'Kundgenerator::LoadLanguage');
?>