<?php
/**
 * @var \App\View\AppView $this
 * @var string $string
 * @var array $result
 */
?>
<h1>String analyzer</h1>

<p>Explain any input string</p>

<div class="page form">
<?php echo $this->Form->create();?>
	<fieldset>
		<legend><?php echo __('Enter the string you want to have analyzed'); ?></legend>
	<?php
		echo $this->Form->control('string', ['type' => 'textarea']);
	?>
	</fieldset>
<?php echo $this->Form->submit(__('Submit')); echo $this->Form->end();?>
</div>

<?php if (!empty($result)) { ?>
<h2 style="margin-top:20px">Result</h2>

<table class="table">
	<thead>
	<tr>
		<th>#</th>
		<th>Character</th>
		<th>Unicode</th>
		<th>Name</th>
		<th>Type</th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ($result as $i => $row) {
	?>
		<tr>
			<td><?php echo $i; ?></td>
			<td><?php echo h($row['char']); ?></td>
			<td class='code'><?php echo h($row['code']); ?></td>
			<td><?php echo h($row['name']); ?></td>
			<td><?php echo h($row['type']); ?></td>
		</tr>
	<?php } ?>

<?php } ?>
	</tbody>
</table>
