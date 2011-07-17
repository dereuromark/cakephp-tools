<?php

/**
 * used by captcha helper and behavior
 */
class CaptchaLib {

	public static $defaults = array (
			'dummyField' => 'homepage',
			'method' => 'hash',
			'type' => 'both',			
			'checkSession' => false,
			'checkIp' => false,
			'salt' => '',
	);

	# what type of captcha
	public static $types = array('passive', 'active', 'both');
	
	# what method to use
	public static $methods = array('hash', 'db', 'session');


	function __construct() {
		
	}
	
	
	/**
	 * @param array $data
	 * @param array $options
	 * 2011-06-11 ms 
	 */
	public static function buildHash($data, $options, $init = false) {
		if ($init) {
			$data['captcha_time'] = time();
			$data['captcha'] = $data['result'];
		}
		
		$hashValue = date(FORMAT_DB_DATE, (int)$data['captcha_time']).'_';
		$hashValue .= ($options['checkSession'])?session_id().'_' : '';
		$hashValue .= ($options['checkIp'])?env('REMOTE_ADDR').'_' : '';
		$hashValue .= $data['captcha'];
		if (!class_exists('Security')) {
			App::import('Core', 'Security');
		}
		return Security::hash($hashValue, isset($options['hashType']) ? $options['hashType'] : null, $options['salt']);
	}

}


	/*
	function captcha_generate() {
		// First of all we are going to set up an array with the text equivalents of all the numbers we will be using.
		$captcha_number_convert = array(0=>'zero', 1=>'one', 2=>'two', 3=>'three', 4=>'four', 5=>'five', 6=>'six', 7=>'seven', 8=>'eight', 9=>'nine', 10=>'ten');
		// Choose the first number randomly between 6 and 10. This is to stop the answer being negative.
		$captcha_number_first = mt_rand(6, 10);
		// Choose the second number randomly between 0 and 5.
		$captcha_number_second = mt_rand(0, 5);
		// Set up an array with the operators that we want to use. At this stage it is only subtraction and addition.
		$captcha_operator_convert = array(0=>'+', 1=>'-');
		// Choose the operator randomly from the array.
		$captcha_operator = $captcha_operator_convert[mt_rand(0, 1)];
		// Get the equation in textual form to show to the user.
		$captcha_return = (mt_rand(0, 1) == 1 ? __($captcha_number_convert[$captcha_number_first],true) : $captcha_number_first) . ' ' . $captcha_operator . ' ' . (mt_rand(0, 1) == 1 ? __($captcha_number_convert[$captcha_number_second],true) : $captcha_number_second);
		// Evaluate the equation and get the result.
		eval('$captcha_result = ' . $captcha_number_first . ' ' . $captcha_operator . ' ' . $captcha_number_second . ';');
		// Store the result in a session key.
		$_SESSION['main_captcha'] = $captcha_result;
		// Return the question so we can use it in our image.
		return $captcha_return;
	}


	// DEPRECATED
	function captchas() {
		// First off, we start by generating our math problem using the code we created in part one. This also does all the work of storing it in the session.
		$captcha_code = $this->captcha_generate();

		// Here are some user defined variables that you can set.
		// This is the size of the font in points.
		$captcha_font_size = 32;
		// The dimensions of the image. Pretty simple, really! =]
		$captcha_width = 480;
		$captcha_height = 40;
		// The possible angle of the characters in the word. If you do not want any rotation on the letters change it to 0, however 20 looks best in my opinion.
		$captcha_angle = 20;

		// Create an image object using the dimensions you entered above.
		$captcha_image = imagecreate(480, 40);

		// This is where we set the colours for each part of the captcha. The values are random so that bots cannot look at the same colour and develop a reading technique.
		// This is the background colour, which always white.
		$captcha_background_color = imagecolorallocate($captcha_image, 255, 255, 255);
		// This is the text colour, which has random R, G and B values between 50 and 150.
		$captcha_text_color = imagecolorallocate($captcha_image, mt_rand(50, 150), mt_rand(50, 150), mt_rand(50, 150));
		// And finally, this is the colour of the background noise, which is once again random between 150 and 200 for R, G and B values.
		$captcha_noise_color = imagecolorallocate($captcha_image, mt_rand(150, 200), mt_rand(150, 200), mt_rand(150, 200));

		// This is a loop in which we add a whole bunch of random dots into the background. This is to generate the noise which makes it hard for bots to read.
		for($i = 0; $i < 1600; $i++)
		{
			imagefilledellipse($captcha_image, mt_rand(0, $captcha_width), mt_rand(0, $captcha_height), 1, 1, $captcha_noise_color);
		}

		// And this is a look which adds a bunch of random lines in the background, which once again makes it hard for bots to try and read the captcha.
		for($i = 0; $i < 32; $i++)
		{
			imageline($captcha_image, mt_rand(0, $captcha_width), mt_rand(0, $captcha_height), mt_rand(0, $captcha_width), mt_rand(0, $captcha_height), $captcha_noise_color);
		}

		// Okay, now for the part where we print the text onto the image.
		// First, we make an array which contains each seperate chracter of the math problem.
		$captcha_code_array = str_split($captcha_code);
		// Now we calculate the dimensions of the captcha code in the right font size so we know where to position it on the canvas.
		$captcha_textbox = imagettfbbox($captcha_font_size, 0, 'main_captcha_font.ttf', $captcha_code);
		// Using those dimensions, we calculate the co-ordinates of the top left point of where we want to place our text, and we work from there.
		// This is the x co-ordinate...
		$captcha_x = ($captcha_width - $captcha_textbox[4]) / 2;
		// ...And this is the y co-ordinate.
		$captcha_y = ($captcha_height - $captcha_textbox[5]) / 2;

		// Now for another loop. This time, this look prints each character one at a time onto the canvas.
		for($i = 0; $i < count($captcha_code_array); $i++)
		{
			// We calculate the position of the character on the canvas, relative to the top left co-ordinate of the text box that we calculated before.
			$captcha_item = (($captcha_x / strlen($captcha_code)) * $i) + ($captcha_width / 3);
			// And now we draw it onto the canvas, with a random rotation between that which is specified at the top of this class.
			imagettftext($captcha_image, $captcha_font_size, mt_rand(-$captcha_angle, $captcha_angle), $captcha_item, $captcha_y, $captcha_text_color, 'main_captcha_font.ttf', $captcha_code_array[$i]);
		}

		// Alright now that is all done, this is where we decide what type of image we want to output. The default in this script is PNG, but just swap the word png with gif or jpeg wherever it occurs in the next two lines of code.
		// This is the header with the content type.
		header('Content-Type: image/png');
		// And this is the function that tells PHP to output out created image.
		imagepng($captcha_image);

		// Whatever the case, we want to destroy this image from the memory, or else PHP will hang onto it and start to eat up RAM.
		imagedestroy($captcha_image);
	}
	*/
