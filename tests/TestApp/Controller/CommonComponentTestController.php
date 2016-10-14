<?php
namespace TestApp\Controller;

use Tools\Controller\Controller;

/**
 * Use Controller instead of AppController to avoid conflicts
 */
class CommonComponentTestController extends Controller {

    /**
     * @var string
     */
    public $name = 'MyController';

    /**
     * @var array
     */
    public $components = ['Tools.Common'];

    /**
     * @var array
     */
    public $autoRedirectActions = ['allowed'];

}
