<?php
/*
Plugin name: CallFire_C2C
 */
use CallFire\Api\Rest\Request;
use CallFire\Api\Rest\Response;
require 'vendor/autoload.php';
add_action('init', array('callfire_c2c', 'init'));
class callfire_c2c 
{
	//Type of call (ivr, voice)
	public static $type;
	//Sound id to call with
	public static $soundid;
	//User supplied name for the shortcode instance
	public static $name;
	//Automatically generated id for the shortcode instance
	private static $id;
	//Number to call from
	private static $fromnum;
	//API pass and key
	private static $api_key;
	private static $api_pass;
	//Array containing call info to be called at the end of page loading
	private static $calls = array();

	public static function init()
	{
		define('WP_DEBUG', true);
		add_action('wp_loaded', array('callfire_c2c', 'call'));
		add_action('admin_init', array('callfire_c2c','settings_init'));
		add_shortcode('callfire-c2c', array('callfire_c2c', 'shortcode'));
	}
	public static function shortcode($atts)
	{
		self::setOptions($atts);
		$callid = self::$id;
		if(isset($_POST['callid']))
		{
			if($_POST['callid'] == self::$id)
			{
				$callinfo = array("type" => self::$type,	"number" => $_POST['number'], "soundid" => self::$soundid);
				self::queueCall($callinfo);
			}
		}
		$return = "<form action='' method='post' name='c2c'><input type='text' name='number'/><input type='hidden' name='callid' value='{$callid}'/><button type='submit'>Submit</button></form>";
		return $return;
	}
	public static function settings_init()
	{
		add_settings_section('callfire_settings', 'CallFire Settings', array('callfire_c2c', 'settings_section'), 'general');	
		register_setting('general', 'callfire_apikey');
		register_setting('general', 'callfire_apipass');
		register_setting('general', 'callfire_fromnum');
		add_settings_field('callfire_apikey', 'CallFire API Key',array('callfire_c2c','settings_key'), 'general', 'callfire_settings');
		add_settings_field('callfire_apipass', 'CallFire API Pass', array('callfire_c2c','settings_pass'), 'general', 'callfire_settings');
		add_settings_field('callfire_fromnum', 'CallFire From Number', array('callfire_c2c', 'settings_fromNum'), 'general', 'callfire_settings');
	}
	public static function settings_key()
	{
		echo "<input type='text' name='callfire_apikey' value='".get_option('callfire_apikey')."'/>";
	}
	public static function settings_pass()
	{
		echo "<input type='text' name='callfire_apipass' value='".get_option('callfire_apipass')."'/>";
	}
	public static function settings_fromNum()
	{
		echo "<input type='text' name='callfire_fromnum' value='".get_option('callfire_fromnum')."'/>";
	}
	public static function settings_section()
	{
		echo "<p>Hi</p>";
	}
	public static function setOptions($options)
	{
		if(array_key_exists("type", $options) && array_key_exists("soundid", $options))
		{
			self::$type = $options["type"];
			self::$soundid = $options['soundid'];
			self::$id = md5(self::$type+self::$soundid);
			self::$fromnum = get_option('callfire_fromnum');
			if(array_key_exists("name", $options))
			{
				self::$name = $options['name'];
			}
			self::$api_key = get_option('callfire_apikey');
			self::$api_pass = get_option('callfire_apipass');
		}
		else
		{
			return "Error";
		}
	}
	public static function queueCall($call)
	{
		self::$calls[] = $call;
	}
	public static function call()
	{
		if(count(self::$calls) > 0)
		{
			$apikey = self::$api_key;
			$apipass = self::$api_pass;
			$fromnum = self::$fromnum;
			foreach(self::$calls as $call)
			{
				$type = $call['type'];
				$soundid = $call['soundid'];
				$number = $call['number'];
				$client = CallFire\Api\Client::Rest($apikey, $apipass, "Call");

				if($type == "voice")
				{
					$request = new Request\SendCall;
					$request->setType($client::BROADCAST_VOICE);
					$request->setFrom($fromnum);
					$request->setTo($number);
					$request->setAnsweringMachineConfig($client::AMCONFIG_LIVE_IMMEDIATE);
					$request->setLiveSoundId($soundid);
					$response = $client->SendCall($request);
					$result = $client::response($response);
					if($result instanceof Response\ResourceReference)
					{
						return true;
					}
					else
					{
						return "Error";
					}

				}
			}

		}
	}
}

