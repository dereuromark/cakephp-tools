<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="row">
	<div class="col-lg-6">
		<h1>Font Icons</h1>
		<p>As configured in app.php (through `Icon.map`)</p>

		<?php
		$icons = $this->Icon->getConfig('map');
		?>
		<ul>
		<?php foreach ($icons as $name => $icon) { ?>
			<li><?php echo $this->Icon->render($icon); ?> - <?php echo h($name)?> (<?php echo h($icon)?>)</li>
		<?php } ?>
		</ul>

	</div>
	<div class="col-lg-6">
	</div>
</div>
