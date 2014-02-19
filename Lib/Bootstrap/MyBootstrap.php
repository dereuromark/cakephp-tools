<?php
/**
 * Basic bootstrap stuff
 *
 * Note: Do not use App::uses() to include this file.
 * Use App::import('Lib', 'Tools.Bootstrap/MyBootstrap'); as noted in the readme.
 *
 * // DEPRECATED - since App::import also seems to be buggy, we better use
 * // CakePlugin::load('Tools', array('bootstrap' => true)); to include the bootstrap file
 * // directly.
 * // This file then can be ignored.
 */
require(CakePlugin::path('Tools') . 'Config' . DS . 'bootstrap.php');
