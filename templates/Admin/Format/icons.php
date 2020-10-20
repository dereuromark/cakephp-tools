<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="row">
	<div class="col-lg-6">
		<h1>Font Icons</h1>
		<p>As configured in app.php (through `Format.fontIcons`)</p>

		<?php
		$icons = $this->Format->getConfig('fontIcons');
		?>
		<ul>
		<?php foreach ($icons as $icon => $class) { ?>
			<li><?php echo $this->Format->icon($icon); ?> - <?php echo h($icon)?> (<?php echo h($class)?>)</li>
		<?php } ?>
		</ul>

	</div>
	<div class="col-lg-6">
	</div>
</div>
