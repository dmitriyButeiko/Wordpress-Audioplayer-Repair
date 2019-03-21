<?php
/*
	Displays a nicely formatted snippet with Author Information
*/
class glvModAudior extends glvModuleBase
{ 
	public static  $sInstance;
	public static function IsEnabled() { return self::$sInstance != null ;}

	protected $mSlug = "glv-audior";
	protected $mMenuPageID = 'glv-audior-menupage';		
	public $mSettings;

	protected function LogError( $inMsg )	{	glvLog::Error( $inMsg );	}
	

	    
	//========================================================
	//	Constructor - sets an instancence and registers WP Hookss
	//========================================================	
	function __construct() 
	{
		//set our instance
		if( self::$sInstance != null )
		{
			glvLog::Error("Module 'glvModAudior' is being created twice!");
		}
		self::$sInstance = $this;
		
		$this->mSettings = new glvCompSettings( $this->mSlug );
		
		add_action('admin_init', array( $this, 'wp_admin_init'));
		add_action('admin_menu', array( $this, 'wp_admin_menu'));
		add_action( 'parse_request', array( $this, 'wp_parse_request') );
		
	}
	
	//========================================================
	//	ADmin Init of our plugging. Settings and filters
	//========================================================		
	function wp_admin_init()
	{					
		$this->mSettings->Register( 'audior_license_key' );
		$this->mSettings->Register( 'default_upload_folder' );
		
	}

	//========================================================
	//	Register Admin Menu page
	//========================================================	
	function wp_admin_menu() 
	{
		add_submenu_page( 	'glvnewssuite', 
						'Audior', 
						'Audior', 
						'manage_options', 
						$this->mMenuPageID, 
						array($this, 'wp_submenu_page_callback')   ); 
	}	
	//========================================================
	//	Create our menu page
	//========================================================	
	function wp_submenu_page_callback() 
	{
?>
		<div class="glv_mod_admin <?php echo $this->mSlug . "-admin" ?> jq_tabs_enabled">

			<h2>Audior</h2>
			<p>Audior Support for GLV News Suite. This module does not do anything by itself but may be required by other modules.</p>			

			<div class="tab_content">

<?php		
		
		$this->admin_tab_settings()
?>		</div></div> <?php		
	}
	
	
	//========================================================
	//	ADMIN: Settings
	//========================================================		
	function admin_tab_settings()
	{	
?>
		<form method="post" action="options.php">
			<?php $this->mSettings->PrintAdminFormData() ?>
			
			<div class="general_box jq_section">
			
				<h3>General</h3>
				
					<?php $this->mSettings->PrintTextbox("audior_license_key", "", "Audior License Key"); ?>
					
				<!-- ======================================= -->
				<div class="entry <?php echo $inSetting ?>">
					<span class="name">Default Upload Folder:</span><br /><?php echo home_url()?><input type="text" name="<?php echo $this->mSettings->GetWPName("default_upload_folder")  ?>" value ="<?php echo $this->mSettings->Get("default_upload_folder",  "/wp-content/uploads/audio_uploads")  ?>" /> 
					<span class="description"></span>
				</div>	
				
			</div>

			<div style="clear: both"></div>
			<?php submit_button(); ?>
		</form>
<?php

	}
	
	
	//=========================================================
	//	Registers XML Settings to be used by Audior
	//=========================================================		
	protected $mXmlSettings = array();		
	public function RegisterXMLSettings( $inUniqueID, $inSettings )
	{
		if( array_key_exists( $inUniqueID, $this->mXmlSettings ) )
		{
			$this->LogError("Audior: Trying to register duplicate XML Settings: " . $inUniqueID );
			return false;
		}
		
		if( !is_a($inSettings, 'glvModAudior_Settings' ) )
		{
			$this->LogError("Audior: Trying to register invalid object as XML Settings: " . $inUniqueID );
			return false;			
		}
		
		$inSettings->mID  = $inUniqueID;		
		$this->mXmlSettings[ $inUniqueID ] = $inSettings;
	}	
	
	//=========================================================
	//	Few Helpers
	//=========================================================	
	public function GetAudiorBaseURL()
	{
		return plugin_dir_url( __FILE__ ) . "audior/";
	}
	public function GetUploadFolder($inPath=null)
	{
		if( $inPath == null )
		{
			$inPath = $this->mSettings->Get("default_upload_folder", "wp-content/uploads/audio_uploads/");
		}
		$url = ABSPATH . $inPath;
		if( substr($url, -1) != "/" )		{			$url .= "/"; }
		return $url;
	}
	public function GetUploadFolderURL($inPath=null)
	{
		if( $inPath == null )
		{
			$inPath = $this->mSettings->Get("default_upload_folder", "wp-content/uploads/audio_uploads/");
		}	
		$url = home_url() . $inPath;
		if( substr($url, -1) != "/" )		{			$url .= "/";		}
		return $url;
	}	
	
	//=========================================================
	//	Parse Request for Audio Settings
	//=========================================================
	function wp_parse_request() 
	{
		$qs = $_SERVER["REQUEST_URI"];
		$cmd = "glv_newssuite_audior_settings_";
		$cmdPos = stripos($qs, $cmd );
		
	   if( $cmdPos !== FALSE )
	   {
			$endPos = strpos( $qs, "?" );
			if( $endPos === FALSE ) { $endPos = strpos( $qs, "&" ); }
			$startPos = $cmdPos + strlen($cmd);
			if( $endPos !== FALSE )
			{
				$id = substr( $qs, $startPos, $endPos -  $startPos );
			}
			else
			{
				$id = substr( $qs, $startPos );
			}
			if( !array_key_exists( $id, $this->mXmlSettings ) )
			{
				$this->LogError("Audior: Trying to Parse Settings Request but we got invalid XML Settings ID: " . $id );
				die("Audior: Trying to Parse Settings Request but we got invalid XML Settings ID: " . $id);
				return false;
			}
			
			$settings = $this->mXmlSettings[ $id ];
			$settings->PrintXml();
			die();
	   }
	   
	   $cmd = "glv_newssuite_audior_upload_";
	   $cmdPos = stripos($qs, $cmd );
	   
	   if( $cmdPos !== FALSE )
	   {
			$endPos = strpos( $qs, "?" );
			if( $endPos === FALSE ) { $endPos = strpos( $qs, "&" ); }
			$startPos = $cmdPos + strlen($cmd);
			if( $endPos !== FALSE )
			{
				$id = substr( $qs, $startPos, $endPos -  $startPos );
			}
			else
			{
				$id = substr( $qs, $startPos );
			}
			
			if( !array_key_exists( $id, $this->mXmlSettings ) )
			{
				$this->LogError("Audior: Trying to Parse Upload Request but we got invalid XML Settings ID: " . $id );
				return false;
			}			
			$this->HandleAudiorUpload($this->mXmlSettings[$id] );
			die();
	   }	   
	}
	
	function PrintAudior($inXmlSettingsID, $inRecordedID = "recorder1" )
	{
		if( !array_key_exists( $inXmlSettingsID, $this->mXmlSettings ) )
		{
			$this->LogError("Audior: Trying to Print Audior with invalid XML Settings ID: " . $inUniqueID );
			return false;
		}
		
		$settings = $this->mXmlSettings[ $inXmlSettingsID ];
		
		
	
?>
	<!-- AUDIOR -->
		<script type="text/javascript" src="<?php echo $this->GetAudiorBaseURL() ?>swfobject.js"></script>
		<?php/*  <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script> */?>
		<script type="text/javascript" src="<?php echo $this->GetAudiorBaseURL() ?>js/jquery.form.min.js"></script>		
       <script type="text/javascript">
            var swfVersionStr = "11.1.0";
            var xiSwfUrlStr = "";
       
            var flashvars = {};
            flashvars.lstext="Loading...";//you can provide a translation here for the "Laoding..." text taht shows up while this file and the external language file is loaded
            flashvars.recorderId = "<?php echo $inRecordedID ?>";//set this var if you have multiple instances of Audior on a page and you want to identify them
            flashvars.userId ="<?php echo wp_get_current_user()->ID ?>";//this variable is sent back to upload.php when the user presses the [SAVE] button
	     flashvars.licenseKey = "<?php echo $this->mSettings->Get("audior_license_key") ?>"; //licensekey variable, you get it when you purchase the software
	     flashvars.settingsFile = "<?php echo home_url("/glv_newssuite_audior_settings_" . $inXmlSettingsID); ?>"; //this setting instructs Audior what setting file to load. Either the static .XML or the dynamic .PHP that generates a dynamic xml.
			
            var params = {};     
            params.quality = "high";
            params.bgcolor = "#ffffff";
            params.allowscriptaccess = "sameDomain";
            params.allowfullscreen = "true";

            var attributes = {};
            attributes.id = "Audior";
            attributes.name = "Audior";
            attributes.align = "middle";
		
		var mobile = false;
		var ua = navigator.userAgent.toLowerCase();
		if(navigator.appVersion.indexOf("iPad") != -1 || navigator.appVersion.indexOf("iPhone") != -1 || ua.indexOf("android") != -1 || ua.indexOf("ipod") != -1 || ua.indexOf("windows ce") != -1 || ua.indexOf("windows phone") != -1){
			mobile = true;
		}
		
		if(mobile == false){
			 swfobject.embedSWF(
			  "<?php echo $this->GetAudiorBaseURL() ?>Audior.swf", "flashContent",  
			  "600", "140", 
			  swfVersionStr, xiSwfUrlStr, 
			  flashvars, params, attributes);
		     swfobject.createCSS("#flashContent", "display:block;text-align:left;");
		}else{
			 //do nothing
		}
		
           
        </script>		
		<!-- The following script is used for mobile devices ONLY -->
		<script type="text/javascript">
				jQuery(document).ready(function() { 
					var options = { 
							target:   '#output',  
							beforeSubmit:  beforeSubmit,  
							success:       afterSuccess,  
							uploadProgress: OnProgress, 
							resetForm: true        
						}; 
						
					 jQuery('#recordingForm').submit(function(e) { 


					 		e.preventDefault();
							jQuery(this).ajaxSubmit(options);  			
							
							return false; 
						}); 

					jQuery('#recorderAudio').hide();				

					function afterSuccess()
					{
						jQuery('#submit-btn').show();
						jQuery('#recorderAudio').show();
						jQuery('#loading-img').hide(); 
						jQuery('#progressbox').delay( 1000 ).fadeOut();
						fileName = document.getElementById("output").innerHTML;	
						var res = fileName.split(" ");
						var recordingName;
						
						for(i = 0; i < res.length; i++){
							if(res[i].indexOf("mp3") != 0){
								recordingName = res[i];
							}
						}
						

						onUploadDone(true, recordingName, 300, 1);

						var audio = document.getElementById("recorderAudio");
						audio.style.display = "none";
						setTimeout(function(){
							//audio.setAttribute("src", "../audioer/recordings/mobileRecordings/"+recordingName);
						}, 2000);
						
					}


					function beforeSubmit(){
						
					   if (window.File && window.FileReader && window.FileList && window.Blob)
						{
							
							if( !jQuery('#FileInput').val()) 
							{
								jQuery("#output").html("Are you kidding me?");
								return false
							}
							
							var fsize = jQuery('#FileInput')[0].files[0].size; 
							var ftype = jQuery('#FileInput')[0].files[0].type; 
							
							switch(ftype)
							 {
									case 'audio/3ga':
									case 'video/quicktime':
								  break;
								 default:
								 // jQuery("#output").html("<b>"+ftype+"</b> Unsupported file type!");
								//		return false
							 }
							
							/*if(fsize>5242880) 
							{
								jQuery("#output").html("<b>"+bytesToSize(fsize) +"</b> Too big file! <br />File is too big, it should be less than 5 MB.");
								return false
							}*/
									
							jQuery('#submit-btn').hide(); 
							jQuery('#loading-img').show();
							jQuery("#output").html("");  
						}
						else
						{
							jQuery("#output").html("Please upgrade your browser, because your current browser lacks some new features we need!");
							return false;
						}
					}


					function OnProgress(event, position, total, percentComplete)
					{
						jQuery('#progressbox').show();
						jQuery('#progressbar').width(percentComplete + '%') 
						jQuery('#statustxt').html(percentComplete + '%'); 
						if(percentComplete>50)
						 {
							 jQuery('#statustxt').css('color','#000');
						 }
					}

					function bytesToSize(bytes) {
					   var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
					   if (bytes == 0) return '0 Bytes';
					   var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
					   return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
					}

				}); 

		</script>
		
        <div align="center" id="flashContent">
			<form action="/../audioer/uploadFromMobile.php" method="post" enctype="multipart/form-data" id="recordingForm">
				<div style="margin-left:50px;"><input name="FileInput" id="FileInput" type="file" accept="audio/*" capture="microphone" value="Start Recording" class="custom-file-input"/></div>
				<input type="submit"  id="submit-btn" value="2. Upload"  class = "btnUpload"/>
				<img src="<?php echo $this->GetAudiorBaseURL() ?>ajax-loader.gif" id="loading-img" style="display:none;" alt="Please Wait"/>

				<input type="hidden" name="microphone_recorded_audio_data">
				<input type="hidden" name="microphone_recorded_audio_name">
			</form>
			<div id="progressbox" ><div id="progressbar"></div ><div id="statustxt">0%</div></div>
			<div id="output"></div>
			<div>
				<br/>
				<audio  id='recorderAudio' controls>
					<source src="">
				</audio >
			</div>
        </div>
        
        <noscript>
            You need to have JS enabled to record Audio to show.
        </noscript>   		
		
		<!-- AUDIOR END -->
		

<?php	
		return true;
	}
	


	//=========================================================
	//	Handles uploading of a file
	//=========================================================	
	function HandleAudiorUpload($inXmlSettings) 
	{	 		
		//glvLog::Msg("AUDIOR Got Upload Request for with settings ID: " . ($inXmlSettings ? $inXmlSettings->mID : 'NULL') );
		
		if( $inXmlSettings == null || $inXmlSettings->mUploadDir == null )
		{
			$folderName = $this->GetUploadFolder();
		}
		else
		{
			$folderName =  $inXmlSettings->GetUploadFolder();
		}
		
		if( $folderName == "" )
		{
			$this->LogError( "Audior Upload Folder [" . $folderName . "] not set!");
			die("save=failed");
		}
		
		if( substr($folderName, -1) != "/" )
		{
			$folderName .= "/";
		}
	
		$recorderId = $_GET["recorderId"];		//the recorderId value sent via flash vars from index.html
		$userId= $_GET["userId"];				//the userId sent via flash vars from index.html
		$recordName = basename($_GET["recordName"]);	//the swf sends the name of the recording via the GET variable named "recordName", basename() is a security measure
		$duration= $_GET["duration"];	//the duration of the recorded audio file in seconds but accurate to the millisecond (like this: 4.322)
		
		//$recordName = "audio_recording_u" . $userId . "_" . time() . ".mp3";
		
		//if recordName does not end in .mp3 stop execution
		if (!preg_match('/\.mp3$/i', $recordName))	
		{
			$this->LogError( "Audior Trying to upload Non-MP3 file!");
			die("save=failed");
		}

		//we make the recordings folder if it does not exists
		if(!is_dir($folderName))
		{
			$res = mkdir($folderName,0777); 
			if( $res == false )
			{
				$this->LogError( "Audior failed to create upload folder: " . $folderName);
				die("save=failed");
			}
		}

		//The MP3 data is sent via POST
		if(isset($GLOBALS["HTTP_RAW_POST_DATA"]))
		{
			$recording = fopen($folderName.$recordName,"wb");
			fwrite($recording , $GLOBALS["HTTP_RAW_POST_DATA"] );
			fclose($recording);
		}
		echo "save=ok&fileurl=".$folderName.$recordName;
		die();
		//echo "save=failed" to tell Audior the MP3 saving process has failed
		//The fileurl returned by upload.php is used internally by Audior when someone presses the [Download] button. It is not sent through to the JS API (onUploadDone sends the actual filename not the value of fileurl).	
	}

	//=========================================================
	//	Prints Audior Settings
	//=========================================================	
	function PrintAudiorSettings($inMaxRecordingLength=120) 
	{	   
		// maxTimeLimit controls the maximum length of the recording.The values accepted are in seconds.
		$maxTimeLimit = $inMaxRecordingLength;

		//recordName controls the name of the recording.
		$recordName = 'audio_recording_';

		//addRandomNumberToName helps you generate different names for the recordings. If set to 1 all the mp3 names will start with the recordName above and will end with a 6 digit random number. Values: 0 for disabled, 1 for enabled .
		$addRandomNumberToName = 1;

		//uploadURL is the path to the script that handles the upload of the mp3 file.
		//$uploadURL = $this->GetAudiorBaseURL() . 'upload.php';
		$uploadURL = home_url("/glv_newssuite_audior_upload");

		//canDownload  controls weather or not the user can download the MP3. Values: 0 for disabled, 1 for enabled.
		$canDownload = 0;

		//weather or not the sound wave is shown, 1 for show, 0 for hidden.
		$showSoundWave = 1;

		//Audior will place a marker at every markerDistance seconds. Set to 0 to disable.
		$markerDistance = 5;

		//Path to the used language file.
		$languageFile = $this->GetAudiorBaseURL() . 'translations/en.xml';

		//This setting controls whether or not all of the flash buttons are shown/hidden. Set to 0 to hide the buttons
		$showButtons = 1;

		//This setting controls whether or not the Save button is enabled/disabled. Set to 1 to disable it
		$disableSaveButton = 1;

		//This setting controls whether or not the Record again button is enabled/disabled. Set to 1 to disable it
		$disableRecordAgainButton = 0;

		//This setting controls the radius of the corners of the Audior background. Set this to 0 for square corners.
		$bgCornerRadius = 15;

		//This setting controls the background color for Audior.
		$bgColor = '0xefefef';

		//This setting controls the border color of the Audior background.
		$borderColor = '0x999999';

		//This setting controls the border width of the Audior background.
		$borderWidth = 1;

		//This setting controls the color of the soundwave
		$soundWaveColor = '0x333333';

		//This setting controls the color with which the generated soundwave is filled with upon playback to indicate the position within the recording
		$playBackFillColor = '0xFA5223';

		//if (file_exists(dirname(dirname(__FILE__)) . "/integration.php")){ include(dirname(dirname(__FILE__)) . "/integration.php"); }

		header("Content-Type:text/xml");
		 echo '<AudiorSettings>
				<maxTimeLimit>'.$maxTimeLimit.'</maxTimeLimit>
				<recordName>'.$recordName.'</recordName>
				<addRandomNumberToName>'.$addRandomNumberToName.'</addRandomNumberToName>
				<uploadURL>'.$uploadURL.'</uploadURL>
				<canDownload>'.$canDownload.'</canDownload>
				<showSoundWave>'.$showSoundWave.'</showSoundWave>
				<markerDistance>'.$markerDistance.'</markerDistance>
				<languageFile>'.$languageFile.'</languageFile>
				<showButtons>'.$showButtons.'</showButtons>
				<disableSaveButton>'.$disableSaveButton.'</disableSaveButton>
				<disableRecordAgainButton>'.$disableRecordAgainButton.'</disableRecordAgainButton>
				<bgCornerRadius>'.$bgCornerRadius.'</bgCornerRadius>
				<bgColor>'.$bgColor.'</bgColor>
				<borderColor>'.$borderColor.'</borderColor>
				<borderWidth>'.$borderWidth.'</borderWidth>
				<soundWaveColor>'.$soundWaveColor.'</soundWaveColor>
				<playBackFillColor>'.$playBackFillColor .'</playBackFillColor>
			  </AudiorSettings>';
	}	

}
new glvModAudior();

//##############################################################
//	Defines our Audior Settings
//##############################################################
class glvModAudior_Settings
{
	public $mID = "";
	//the directory to upload our files, relative to root. If null, then Audior will use the default path
	public $mUploadDir = null;
	//Max length of recording, in seconds
	public $mMaxRecordingLength; 
	//recordName controls the name of the recording.
	public $mRecordName = 'audio_recording_';	
	//addRandomNumberToName helps you generate different names for the recordings. If set to 1 all the mp3 names will start with the recordName above and will end with a 6 digit random number. Values: 0 for disabled, 1 for enabled .	
	public $mAddRandomNumberToName = 1;	
	//canDownload  controls weather or not the user can download the MP3. Values: 0 for disabled, 1 for enabled.
	public $mCanDownload = 0;

	//weather or not the sound wave is shown, 1 for show, 0 for hidden.
	public $mShowSoundWave = 1;

	//Audior will place a marker at every markerDistance seconds. Set to 0 to disable.
	public $mMarkerDistance = 5;

	//Path to the used language file.
	//public $mLanguageFile = $this->GetAudiorBaseURL() . 'translations/en.xml';

	//This setting controls whether or not all of the flash buttons are shown/hidden. Set to 0 to hide the buttons
	public $mShowButtons = 1;

	//This setting controls whether or not the Save button is enabled/disabled. Set to 1 to disable it
	public $mDisableSaveButton = 1;

	//This setting controls whether or not the Record again button is enabled/disabled. Set to 1 to disable it
	public $mDisableRecordAgainButton = 0;

	//This setting controls the radius of the corners of the Audior background. Set this to 0 for square corners.
	public $mBgCornerRadius = 15;

	//This setting controls the background color for Audior.
	public $mBgColor = '0xefefef';

	//This setting controls the border color of the Audior background.
	public $mBorderColor = '0x999999';

	//This setting controls the border width of the Audior background.
	public $mBorderWidth = 1;

	//This setting controls the color of the soundwave
	public $mSoundWaveColor = '0x333333';

	//This setting controls the color with which the generated soundwave is filled with upon playback to indicate the position within the recording
	public $mPlayBackFillColor = '0xFA5223';
	
	public function GetUploadFolder()
	{		
		$url = ABSPATH . $this->mUploadDir;
		if( substr($url, -1) != "/" )		{			$url .= "/"; }
		return $url;
	}
	public function GetUploadFolderURL()
	{
		$url = home_url() . $this->mUploadDir;
		if( substr($url, -1) != "/" )		{			$url .= "/";		}
		return $url;
	}	
	
	
	public function PrintXML()
	{
	/*
		// maxTimeLimit controls the maximum length of the recording.The values accepted are in seconds.
		$maxTimeLimit = 125;

		//recordName controls the name of the recording.
		$recordName = 'audio_recording_';

		//addRandomNumberToName helps you generate different names for the recordings. If set to 1 all the mp3 names will start with the recordName above and will end with a 6 digit random number. Values: 0 for disabled, 1 for enabled .
		$addRandomNumberToName = 1;

		//uploadURL is the path to the script that handles the upload of the mp3 file.
		//$uploadURL = $this->GetAudiorBaseURL() . 'upload.php';
		$uploadURL = home_url("/glv_newssuite_audior_upload_" . $this->mID);

		//canDownload  controls weather or not the user can download the MP3. Values: 0 for disabled, 1 for enabled.
		$canDownload = 0;

		//weather or not the sound wave is shown, 1 for show, 0 for hidden.
		$showSoundWave = 1;

		//Audior will place a marker at every markerDistance seconds. Set to 0 to disable.
		$markerDistance = 5;

		//Path to the used language file.
		$languageFile = glvModAudior::$sInstance->GetAudiorBaseURL() . 'translations/en.xml';

		//This setting controls whether or not all of the flash buttons are shown/hidden. Set to 0 to hide the buttons
		$showButtons = 1;

		//This setting controls whether or not the Save button is enabled/disabled. Set to 1 to disable it
		$disableSaveButton = 1;

		//This setting controls whether or not the Record again button is enabled/disabled. Set to 1 to disable it
		$disableRecordAgainButton = 0;

		//This setting controls the radius of the corners of the Audior background. Set this to 0 for square corners.
		$bgCornerRadius = 15;

		//This setting controls the background color for Audior.
		$bgColor = '0xefefef';

		//This setting controls the border color of the Audior background.
		$borderColor = '0x999999';

		//This setting controls the border width of the Audior background.
		$borderWidth = 1;

		//This setting controls the color of the soundwave
		$soundWaveColor = '0x333333';

		//This setting controls the color with which the generated soundwave is filled with upon playback to indicate the position within the recording
		$playBackFillColor = '0xFA5223';

		//if (file_exists(dirname(dirname(__FILE__)) . "/integration.php")){ include(dirname(dirname(__FILE__)) . "/integration.php"); }

		
		header("Content-Type:text/xml");
		 echo '<AudiorSettings>
				<maxTimeLimit>'.$maxTimeLimit.'</maxTimeLimit>
				<recordName>'.$recordName.'</recordName>
				<addRandomNumberToName>'.$addRandomNumberToName.'</addRandomNumberToName>
				<uploadURL>'.$uploadURL.'</uploadURL>
				<canDownload>'.$canDownload.'</canDownload>
				<showSoundWave>'.$showSoundWave.'</showSoundWave>
				<markerDistance>'.$markerDistance.'</markerDistance>
				<languageFile>'.$languageFile.'</languageFile>
				<showButtons>'.$showButtons.'</showButtons>
				<disableSaveButton>'.$disableSaveButton.'</disableSaveButton>
				<disableRecordAgainButton>'.$disableRecordAgainButton.'</disableRecordAgainButton>
				<bgCornerRadius>'.$bgCornerRadius.'</bgCornerRadius>
				<bgColor>'.$bgColor.'</bgColor>
				<borderColor>'.$borderColor.'</borderColor>
				<borderWidth>'.$borderWidth.'</borderWidth>
				<soundWaveColor>'.$soundWaveColor.'</soundWaveColor>
				<playBackFillColor>'.$playBackFillColor .'</playBackFillColor>
			  </AudiorSettings>';
		return;
			  */
		//$uploadURL = home_url("/glv_newssuite_audior_upload_" . $this->mID );
		$uploadURL = home_url("/glv_newssuite_audior_upload_" . $this->mID);
		$languageFile = glvModAudior::$sInstance->GetAudiorBaseURL() . 'translations/en.xml';		
				
		header("Content-Type:text/xml");
		echo '<AudiorSettings>
				<maxTimeLimit>'.$this->mMaxRecordingLength.'</maxTimeLimit>
				<recordName>'.$this->mRecordName.'</recordName>
				<addRandomNumberToName>'.$this->mAddRandomNumberToName.'</addRandomNumberToName>
				<uploadURL>'.$uploadURL.'</uploadURL>
				<canDownload>'.$this->mCanDownload.'</canDownload>
				<showSoundWave>'.$this->mShowSoundWave.'</showSoundWave>
				<markerDistance>'.$this->mMarkerDistance.'</markerDistance>
				<languageFile>'.$languageFile.'</languageFile>
				<showButtons>'.$this->mShowButtons.'</showButtons>
				<disableSaveButton>'.$this->mDisableSaveButton.'</disableSaveButton>
				<disableRecordAgainButton>'.$this->mDisableRecordAgainButton.'</disableRecordAgainButton>
				<bgCornerRadius>'.$this->mBgCornerRadius.'</bgCornerRadius>
				<bgColor>'.$this->mBgColor.'</bgColor>
				<borderColor>'.$this->mBorderColor.'</borderColor>
				<borderWidth>'.$this->mBorderWidth.'</borderWidth>
				<soundWaveColor>'.$this->mSoundWaveColor.'</soundWaveColor>
				<playBackFillColor>'.$this->mPlayBackFillColor .'</playBackFillColor>
			  </AudiorSettings>';	
	}
}