<div class="page form">
<h2><?php echo __('Edit %s', __('Qurl')); ?></h2>

<?php echo $this->Form->create('Qurl');?>
	<fieldset>
		<legend><?php echo __('Edit %s', __('Qurl')); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('url');
		echo $this->Form->input('title');
		echo $this->Form->input('note', ['type' => 'textarea']);
		echo $this->Form->input('active');
	?>
	</fieldset>
	<fieldset>
		<legend><?php echo __('Internal details')?></legend>
	<?php
		echo $this->Form->input('comment', ['type' => 'textarea']);
		echo $this->Form->input('used');
		echo $this->Form->input('last_used', ['empty' => '- -', 'dateFormat' => 'DMY', 'timeFormat' => 24]);
	 ?>
	</fieldset>
<?php echo $this->Form->submit(__('Submit')); echo $this->Form->end();?>
</div>

<div class="actions">
	<ul>

		<li><?php echo $this->Form->postLink(__('Delete'), ['action' => 'delete', $this->Form->value('Qurl.id')], ['confirm' => __('Are you sure you want to delete # %s?', $this->Form->value('Qurl.id'))]); ?></li>
		<li><?php echo $this->Html->link(__('List %s', __('Qurls')), ['action' => 'index']);?></li>
	</ul>
</div>