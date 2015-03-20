<div class="page view">
<h2><?php echo __('Qurl');?></h2>
	<dl>
		<dt><?php echo __('Url'); ?></dt>
		<dd>
			<?php echo h($qurl['Qurl']['url']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Comment'); ?></dt>
		<dd>
			<?php echo h($qurl['Qurl']['comment']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Used'); ?></dt>
		<dd>
			<?php echo h($qurl['Qurl']['used']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Last Used'); ?></dt>
		<dd>
			<?php echo $this->Datetime->niceDate($qurl['Qurl']['last_used']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Created'); ?></dt>
		<dd>
			<?php echo $this->Datetime->niceDate($qurl['Qurl']['created']); ?>
			&nbsp;
		</dd>
	</dl>
</div>

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('Edit %s', __('Qurl')), ['action' => 'edit', $qurl['Qurl']['id']]); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete %s', __('Qurl')), ['action' => 'delete', $qurl['Qurl']['id']], ['confirm' => __('Are you sure you want to delete # %s?', $qurl['Qurl']['id'])]); ?> </li>
		<li><?php echo $this->Html->link(__('List %s', __('Qurls')), ['action' => 'index']); ?> </li>
	</ul>
</div>
