<?php
namespace TestApp\Controller;

use Tools\Controller\Controller;

/**
 * Use Controller instead of AppController to avoid conflicts
 */
class FlashComponentTestController extends Controller {

    /**
     * @var array
     */
    public $components = ['Tools.Flash'];

    /**
     * @var bool
     */
    public $failed = false;

    /**
     * @var array
     */
    public $testHeaders = [];

    public function fail() {
        $this->failed = true;
    }

    public function redirect($url, $status = null, $exit = true) {
        return $status;
    }

    public function header($status) {
        $this->testHeaders[] = $status;
    }

}
