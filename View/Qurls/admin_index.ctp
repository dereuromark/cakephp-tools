<div class="page index">
	<h2><?php echo __('Qurls');?></h2>

	<table class="list">
		<tr>
		<th><?php echo $this->Paginator->sort('url');?></th>
		<th><?php echo $this->Paginator->sort('used');?></th>
		<th><?php echo $this->Paginator->sort('last_used');?></th>
		<th><?php echo $this->Paginator->sort('created', null, ['direction' => 'desc']);?></th>
		<th class="actions"><?php echo __('Actions');?></th>
	</tr>
<?php
$i = 0;
foreach ($qurls as $qurl): ?>
	<tr>
		<td>
			<?php
				$url = $qurl['Qurl']['url'];
				if (strpos($url, $baseUrl = Router::url('/', true)) === 0) {
					$url = substr($url, strlen($baseUrl));
					if (strpos($url, '/') !== 0) {
						$url = '/'.$url;
					}
				}
				echo $url; ?>
			<div><code><?php echo Qurl::urlByKey($qurl['Qurl']['key'], $qurl['Qurl']['title']); ?></code></div>
			<?php if ($qurl['Qurl']['comment']) { ?>
			<small><?php echo h($qurl['Qurl']['comment']); ?></small>
			<?php } ?>
		</td>

		<td>
			<?php echo h($qurl['Qurl']['used']); ?>
			<div><?php echo $this->Format->yesNo($qurl['Qurl']['active'], ['onTitle' => __('Active'), 'offTitle' => __('Inactive')]); ?></div>
		</td>
		<td>
			<?php echo $this->Datetime->niceDate($qurl['Qurl']['last_used']); ?>
		</td>
		<td>
			<?php echo $this->Datetime->niceDate($qurl['Qurl']['created']); ?>
		</td>
		<td class="actions">
			<?php echo $this->Html->link($this->Format->icon('add', 'Als Vorlage verwenden'), ['action'=>'add', $qurl['Qurl']['id']], ['escape'=>false]); ?>
			<?php echo $this->Html->link($this->Format->icon('view'), ['action'=>'view', $qurl['Qurl']['id']], ['escape'=>false]); ?>
			<?php echo $this->Html->link($this->Format->icon('edit'), ['action'=>'edit', $qurl['Qurl']['id']], ['escape'=>false]); ?>
			<?php echo $this->Form->postLink($this->Format->icon('delete'), ['action'=>'delete', $qurl['Qurl']['id']], ['escape' => false, 'confirm' => __('Are you sure you want to delete # %s?', $qurl['Qurl']['id'])]); ?>
		</td>
	</tr>
<?php endforeach; ?>
	</table>

	<div class="pagination-container">
<?php echo $this->element('Tools.pagination'); ?>
	</div>

</div>

<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('New %s', __('Qurl')), ['action' => 'add']); ?></li>
	</ul>
</div>
