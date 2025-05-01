<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="row">
	<div class="col-12">
		<h1>Tools plugin</h1>

		<h2>Useful quick-tools</h2>
		<ul>
			<li><?php echo $this->Html->link('Available "pages"', ['controller' => 'Pages', 'action' => 'index']); ?></li>
			<li><?php echo $this->Html->link('Bitmasks', ['controller' => 'Helper', 'action' => 'bitmasks']);?></li>
		</ul>
	</div>
</div>
