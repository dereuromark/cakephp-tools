<div class="page form">

<h2><?php echo __d('tools', 'Qlogins'); ?></h2>
<?php echo $qlogins;?> <?php echo __d('tools', 'valid ones'); ?>
<br /><br />
<?php if (!empty($url)) { ?>
<h3><?php echo __d('tools', 'Generated Link'); ?></h3>
<code><?php echo h($url);?></code>
<?php } ?>

<h3><?php echo __d('tools', 'Add %s', __d('tools', 'Qlogin')); ?></h3>
<?php echo $this->Form->create('Qlogin');?>
	<fieldset>
		<legend><?php echo __d('tools', 'Add %s', __d('tools', 'Qlogin')); ?></legend>
	<?php
		echo $this->Form->input('url', ['placeholder' => '/controller/action/...']);
		echo $this->Form->input('user_id', ['empty' => '---']);
	?>
	</fieldset>
<?php echo $this->Form->end(__d('tools', 'Submit'));?>
</div>

<br /><br />

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__d('tools', 'Reset %s', __d('tools', 'Qlogins')), ['action' => 'reset'], [], __d('tools', 'Sure?'));?></li>
	<?php if (false) { ?>
		<li><?php echo $this->Html->link(__d('tools', 'List %s', __d('tools', 'Qlogins')), ['action' => 'listing']);?></li>
	<?php } ?>
	</ul>
</div>