<?php
if (!isset($model)) {
	$model = '';
} else {
	$model .= '.';
}
?>
<fieldset>
	<legend><?php echo __d('tools', 'Master Password');?></legend>
	<?php echo $this->Form->input($model.'master_pwd', array('label'=>__d('tools', 'Password'))); ?>
</fieldset>