<?php

/**
 * captcha helper
 * works togehter with captcha behaviour/component
 * 2009-12-22 ms
 */
class CaptchaHelper extends AppHelper {
	var $helpers = array('Form');

	private $options = array(
		'dummyField' => 'homepage',
		'method' => 'hash',
		'checkSession' => false,
		'checkIp' => false,
		'salt' => '',
		'type' => 'active',
	);

  private $methods = array('hash', 'db', 'session');


	private $captcha_number_convert = null;
	private $captcha_operator_convert = null;

	private $difficulty = 1;	# initial diff. level (@see operator: + = 0, +- = 1, +-* = 2)
	private $raiseDifficulty = 2;	# number of failed trails, after the x. one the following one it will be more difficult

	function __construct() {
		# First of all we are going to set up an array with the text equivalents of all the numbers we will be using.
		$this->captcha_number_convert = array(0=>'zero', 1=>'one', 2=>'two', 3=>'three', 4=>'four', 5=>'five', 6=>'six', 7=>'seven', 8=>'eight', 9=>'nine', 10=>'ten');

		# Set up an array with the operators that we want to use. With difficulty=1 it is only subtraction and addition.
		$this->captcha_operator_convert = array(0=>array('+',__('calcPlus', true)), 1=>array('-',__('calcMinus', true)), 2=>'*',__('calcTimes', true));

		$configs = (array)Configure::read('Captcha');
		if (!empty($configs)) {
			$this->options = array_merge($this->options, $configs);
		}

		parent::__construct();
	}


  /**
   * shows the statusText of Relations
   * @param int $difficulty: not build in yet
   * 2008-12-12 ms
   */
  private function generate($difficulty = null) {
  	# Choose the first number randomly between 6 and 10. This is to stop the answer being negative.
  	$numberOne = mt_rand(6, 10);
  	# Choose the second number randomly between 0 and 5.
	$numberTwo = mt_rand(0, 5);
	# Choose the operator randomly from the array.
  	$captchaOperatorSelection = $this->captcha_operator_convert[mt_rand(0, 1)];
    $captchaOperator = $captchaOperatorSelection[mt_rand(0, 1)];

  	# Get the equation in textual form to show to the user.
  	$code = (mt_rand(0, 1) == 1 ? __($this->captcha_number_convert[$numberOne],true) : $numberOne) . ' ' . $captchaOperator . ' ' . (mt_rand(0, 1) == 1 ? __($this->captcha_number_convert[$numberTwo],true) : $numberTwo);

  	# Evaluate the equation and get the result.
  	eval('$result = ' . $numberOne . ' ' . $captchaOperatorSelection[0] . ' ' . $numberTwo . ';');

  	return array('code'=>$code, 'result'=>$result);
  }

    /**
     * main captcha output (usually called from $this->input() automatically)
     * - hash-based
     * - session-based (not impl.)
     * - db-based (not impl.)
     * 2009-12-18 ms
     */
	public function captcha($model = null) {
		$captchaCode = $this->generate();

		# Session-Way (only one form at a time) - must be a component then
    //$this->Session->write('Captcha.result', $result);

    # DB-Way (several forms possible, high security via IP-Based max limits)
    // the following should be done in a component and passed to the view/helper
    // $Captcha = ClassRegistry::init('Captcha');
    // $Captcha->new(); $Captcha->update(); etc

  	# Timestamp-SessionID-Hash-Way (several forms possible, not as secure)
  	$hash = $this->buildHash($captchaCode);

		$return = '';

		if ($this->options['type'] == 'active') {
			# //todo obscure html here?
    	$fill = ''; //'<span></span>';
			$return .= '<span id="captchaCode">'.$fill.''.$captchaCode['code'].'</span>';
		}

		$field = 'captcha';
		if (!empty($model)) {
			//$model = $this->Form->model();

			$field = $model.'.'.$field;
		}

		# add passive part on active forms as well
		$return .= '<div style="display:none">'.
            $this->Form->input($field.'_hash', array('value'=>$hash)).
            $this->Form->input($field.'_time', array('value'=>time())).
            $this->Form->input((!empty($model)?$model.'.':'').$this->options['dummyField'], array('value'=>'')).
        '</div>';

		return $return;
	}


	/**
	 * active math captcha
	 * either combined with between=true (all in this one funtion)
	 * or seperated by =false (needs input(false) and captcha() calls then)
	 * @param bool between: [default: true]
	 * 2010-01-08 ms
	 */
	public function input($model = null, $options = array()) {
		$defaultOptions = array('type'=>'text','class'=>'captcha','value'=>'','maxlength'=>3,'label'=>__('Captcha',true).BR.__('captchaExplained',true),'combined'=>true, 'autocomplete'=>'off');
        $options = array_merge($defaultOptions, $options);

		if ($options['combined'] === true) {
            $options['between'] = $this->captcha($model).' = ';
        }
        unset($options['combined']);

		$field = 'captcha';
		if (!empty($model)) {
			$field = $model.'.'.$field;
		}
		return $this->Form->input($field.'', $options); // TODO: rename: _code
	}

	/**
	 * passive captcha
	 * 2010-01-08 ms
	 */
	public function passive($model = null, $options = array()) {
		return $this->captcha($model);
	}

	/**
	 * active captcha
	 * (+ passive captcha right now)
	 * 2010-01-08 ms
	 */
	public function active($model = null, $options = array()) {
		return $this->input($model, $options);
	}


	private function buildHash($captchaCode) {
		$hashValue = date(FORMAT_DB_DATE).'_';
		$hashValue .= ($this->options['checkSession']) ? session_id().'_' : '';
		$hashValue .= ($this->options['checkIp']) ? env('REMOTE_ADDR').'_' : '';
		$hashValue .= $captchaCode['result'].'_'.$this->options['salt'];
		return Security::hash($hashValue);
	}


/** following not needed */



	/*
	public function validateCaptcha($model, $data = null) {
		if (!empty($data[$model]['homepage'])) {
			// trigger error - SPAM!!!
		} elseif (empty($data[$model]['captcha_hash']) || empty($this->data[$model]['captcha_time']) || $this->data[$model]['captcha_time'] > time()-CAPTCHA_MIN_TIME) {
			// trigger error - SPAM!!!
		} elseif (isset($this->data[$model]['captcha'])) {
			$timestamp = date(FORMAT_DB_DATE, $this->data[$model]['captcha_time']);
	    	$hash = Security::hash($timestamp.'_'.$captchaCode['result'].'_');

			if ($this->data[$model]['captcha_hash'] == $hash) {
				return true;
			}
		}
		return false;
	}
	*/




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

}
?>