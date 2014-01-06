<div class="page form">

<h2><?php echo __('Qlogins'); ?></h2>
<?php echo $qlogins;?> <?php echo __('valid ones'); ?>
<br /><br />
<?php if (!empty($url)) { ?>
<h3><?php echo __('Generated Link'); ?></h3>
<code><?php echo h($url);?></code>
<?php } ?>

<h3><?php echo __('Add %s', __('Qlogin')); ?></h3>
<?php echo $this->Form->create('Qlogin');?>
	<fieldset>
		<legend><?php echo __('Add %s', __('Qlogin')); ?></legend>
	<?php
		echo $this->Form->input('url', array('placeholder' => '/controller/action/...'));
		echo $this->Form->input('user_id', array('empty' => '---'));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('Reset %s', __('Qlogins')), array('action' => 'reset'), array(), __('Sure?'));?></li>
	<?php if (false) { ?>
		<li><?php echo $this->Html->link(__('List %s', __('Qlogins')), array('action' => 'listing'));?></li>
	<?php } ?>
	</ul>
</div>