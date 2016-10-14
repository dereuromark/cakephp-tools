<?php
namespace TestApp\Controller;

use Tools\Controller\Controller;

class MobileComponentTestController extends Controller {

    /**
     * Components property
     *
     * @var array
     */
    public $components = ['RequestHandler', 'Tools.Mobile'];

}
