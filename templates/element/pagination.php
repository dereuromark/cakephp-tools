<?php
/**
 * @var \App\View\AppView $this
 * @var bool $addArrows
 * @var array $options
 * @var bool $reverse
 */
if (!isset($separator)) {
	if (defined('PAGINATOR_SEPARATOR')) {
		$separator = PAGINATOR_SEPARATOR;
	} else {
		$separator = '';
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
if (!isset($format)) {
	$format = __d('tools', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total');
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
$modulus = isset($modulus) ? $modulus : 8;
?>

<div class="paginator paging row">
	<div class="col-lg-6">

	<ul class="pagination">
	<?php echo $this->Paginator->first($first, ['escape' => $escape]);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->prev($prev, ['escape' => $escape, 'disabledTitle' => false]);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->numbers(['escape' => $escape, 'separator' => $separator, 'modulus' => $modulus]);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->next($next, ['escape' => $escape, 'disabledTitle' => false]);?>
 <?php echo $separator; ?>
	<?php echo $this->Paginator->last($last, ['escape' => $escape]);?>
	</ul>

	</div>
	<div class="col-lg-6">
		<?php if (!\Cake\Core\Configure::read('Paginator.limitControl')) { ?>
		<p class="paging-description">
			<?php echo $this->Paginator->counter($format); ?>
		</p>
		<?php } else { ?>
		<div class="d-flex justify-content-between align-items-center">
			<p class="paging-description mb-0">
				<?php echo $this->Paginator->counter($format); ?>
			</p>
			<div class="limit-selector">
				<?php
				$currentLimit = $this->Paginator->param('perPage');
				$maxLimit = $this->Paginator->param('maxLimit') ?: 100;
				$limits = [10, 25, 50, 100];
				// Filter limits to only show those <= maxLimit
				$limits = array_filter($limits, fn($limit) => $limit <= $maxLimit);
				?>
				<?php if (count($limits) > 1) { ?>
				<label class="me-2">Show:</label>
				<select class="form-select form-select-sm d-inline-block w-auto" onchange="window.location.href=this.value">
					<?php foreach ($limits as $limitOption) { ?>
						<option value="<?= $this->Url->build(['?' => ['limit' => $limitOption] + $this->request->getQuery()]) ?>" <?= $currentLimit == $limitOption ? 'selected' : '' ?>>
							<?= $limitOption ?>
						</option>
					<?php } ?>
				</select>
				<?php } ?>
			</div>
		</div>
		<?php } ?>
	</div>
</div>
<?php if (!empty($options['ajaxPagination'])) {
	$ajaxContainer = !empty($options['paginationContainer']) ? $options['paginationContainer'] : '.page';

	$script = "$(document).ready(function() {
	$('div.pagination a').live('click', function () {
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

	if (isset($this->Js)) {
		$this->Js->buffer($script);
	}
} ?>
