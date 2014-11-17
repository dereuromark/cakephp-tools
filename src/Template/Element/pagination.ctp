<p class="paging-description"><?php

if (!isset($separator)) {
	if (defined('PAGINATOR_SEPARATOR')) {
		$separator = PAGINATOR_SEPARATOR;
	} else {
		$separator = ' ';
	}
}

if (empty($first)) {
	$first = __d('tools', 'first');
}
if (empty($last)) {
	$last = __d('tools', 'last');
}
if (empty($prev)) {
	$prev = __d('tools', 'previous');
}
if (empty($next)) {
	$next = __d('tools', 'next');
}
if (!empty($reverse)) {
	$tmp = $first;
	$first = $last;
	$last = $tmp;

	$tmp = $prev;
	$prev = $next;
	$next = $tmp;
}
if (!empty($addArrows)) {
	$prev = '« ' . $prev;
	$next .= ' »';
}
$escape = isset($escape) ? $escape : true;

echo $this->Paginator->counter(array(
		'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total'))); ?></p>
<div class="paging">
	<?php echo $this->Paginator->first($first, array('escape' => $escape));?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->prev($prev, array('escape' => $escape), null, array('class' => 'prev disabled'));?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->numbers(array('escape' => $escape, 'separator' => $separator));?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->next($next, array('escape' => $escape), null, array('class' => 'next disabled'));?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->last($last, array('escape' => $escape));?>
</div>
<?php if (!empty($options['ajaxPagination'])) {
	$ajaxContainer = !empty($options['paginationContainer']) ? $options['paginationContainer'] : '.page';

	$script = "$(document).ready(function() {
	$('div.paging a').live('click', function () {
		$('$ajaxContainer').fadeTo(300, 0);

		var thisHref = $(this).attr('href');

		$('$ajaxContainer').load(thisHref, function() {
			$(this).fadeTo(200, 1);
			$('html, body').animate({
				scrollTop: $('$ajaxContainer').offset().top
			}, 200);
		});
		return false;
	});
});";

	$this->Js->buffer($script);
} ?>