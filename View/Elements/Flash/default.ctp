<?php
$class = 'message';
if (!empty($params['class'])) {
    $class .= ' ' . $params['class'];
}
if (!empty($type)) {
    $class .= ' ' . $type;
}

if (!isset($escape) || $escape) {
	$message = h($message);
}

?>
<div class="<?php echo h($class) ?>"><?php echo $message ?></div>
