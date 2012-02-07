<?php
if (!isset($model)) {
	$model = '';
} else {
	$model .= '.';
}
?>
<fieldset>
	<legend><?php echo __('Master Password');?></legend>
	<?php echo $this->Form->input($model.'master_pwd', array('label'=>__('Password'))); ?>
</fieldset>