<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="col-sm-12">
<h1>Pages</h1>

<ul>
<?php
foreach ($pages as $page) {
?><li>
		<?php
		echo $this->Html->linkReset($page['label'], ['controller' => 'Pages', 'action' => 'display', $page['action']]);
		?>
	</li>
<?php } ?>
</ul>

</div>
