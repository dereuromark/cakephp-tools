<?php
/**
 * @var \App\View\AppView $this
 */
?>
<h1>Bitmasks</h1>
Using the BitmaskedBehavior

<h2>Re-configure using SQL-Snippets</h2>
Syntax: <b>OLDID[,...]:NEWID[,...]</b> (allowing multiple ids on each side<br />
e.g.: <i>4:8,16</i>, single statements per line

<div class="page form">
<?php echo $this->Form->create();?>
	<fieldset>
		<legend><?php echo __('Adjustment Matrix'); ?></legend>
	<?php
		echo $this->Form->control('model', ['placeholder' => 'PluginName.ModelName']);
		echo $this->Form->control('field', ['placeholder' => 'field_name']);
		echo $this->Form->control('matrix', ['type' => 'textarea']);
	?>
	</fieldset>
<?php echo $this->Form->submit(__('Submit')); echo $this->Form->end();?>
</div>

<?php if (!empty($result)) { ?>
<h2>Result</h2>
<?php
foreach ($result as $key => $value) {
	echo pre($value['from']);
	echo pre($value['to']);

}
echo '<pre>';
foreach ($result as $key => $value) {
	echo $value['sql'] . PHP_EOL;
}
echo '</pre>';
?>
<?php } ?>
