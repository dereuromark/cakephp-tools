<?php

/**
 * "Character" diff renderer.
 *
 * This class renders the diff in "Character" format
 *
 * @package Text_Diff
 */
class Horde_Text_Diff_Renderer_Character extends Horde_Text_Diff_Renderer {

	public $orig;

	public $final;

	public function __construct($context_lines = 0) {
		parent::__construct();

		$this->_leading_context_lines = $context_lines;
		$this->_trailing_context_lines = $context_lines;
		$this->orig = "";
		$this->final = "";
	}

	/**
	 * Renders a diff.
	 *
	 * @param Text_Diff $diff  A Text_Diff object.
	 *
	 * @return string  The formatted output.
	 */
	public function render($diff) {
		$xi = $yi = 1;
		$block = false;
		$context = array();

		$nlead = $this->_leading_context_lines;
		$ntrail = $this->_trailing_context_lines;


		$diffs = $diff->getDiff();
		foreach ($diffs as $i => $edit) {
			/* If these are unchanged (copied) lines, and we want to keep
			* leading or trailing context lines, extract them from the copy
			* block. */
			if (is_a($edit, 'Text_Diff_Op_copy')) {
				/* Do we have any diff blocks yet? */
				if (is_array($block)) {
					/* How many lines to keep as context from the copy
					* block. */
					$keep = $i == count($diffs) - 1 ? $ntrail : $nlead + $ntrail;
					if (count($edit->orig) <= $keep) {
						/* We have less lines in the block than we want for
						* context => keep the whole block. */
						$block[] = $edit;
					} else {
						if ($ntrail) {
							/* Create a new block with as many lines as we need
							* for the trailing context. */
							$context = array_slice($edit->orig, 0, $ntrail);
							$block[] = &new Text_Diff_Op_copy($context);
						}
						/* @todo */
						$output = $this->_block($x0, $ntrail + $xi - $x0, $y0, $ntrail + $yi - $y0, $block);
						$block = false;
					}
				}
				/* Keep the copy block as the context for the next block. */
				$context = $edit->orig;
			} else {
				/* Don't we have any diff blocks yet? */
				if (!is_array($block)) {
					/* Extract context lines from the preceding copy block. */
					$context = array_slice($context, count($context) - (int)$nlead);
					$x0 = $xi - count($context);
					$y0 = $yi - count($context);
					$block = array();
					if ($context) {
						$block[] = &new Text_Diff_Op_copy($context);
					}
				}
				$block[] = $edit;
			}

			if ($edit->orig) {
				$xi += count($edit->orig);
			}
			if ($edit->final) {
				$yi += count($edit->final);
			}
		}

		if (is_array($block)) {
			$output = $this->_block($x0, $xi - $x0, $y0, $yi - $y0, $block);
		}

		return $output;
	}

	protected function _startDiff() {
	}

	protected function _endDiff() {
		return array($this->orig, $this->final);
	}

	protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen) {
	}

	protected function _startBlock($header) {
		echo $header;
	}

	protected function _endBlock() {
	}

	protected function _lines($type, $lines, $prefix = '') {
		if ($type == 'context') {
			foreach ($lines as $line) {
				$this->orig .= $line;
				$this->final .= $line;
			}
		} elseif ($type == 'added' || $type == 'change-added') {
			$l = "";
			foreach ($lines as $line) {
				$l .= $line;
			}
			if (!empty($l))
				$this->final .= '<span class="diffchar">' . $l . "</span>";
		} elseif ($type == 'deleted' || $type == 'change-deleted') {
			$l = "";
			foreach ($lines as $line)
				$l .= $line;
			if (!empty($l))
				$this->orig .= '<span class="diffchar">' . $l . "</span>";
		}
	}
	protected function _block($xbeg, $xlen, $ybeg, $ylen, &$edits) {

		foreach ($edits as $edit) {
			switch (strtolower(get_class($edit))) {
				case 'text_diff_op_copy':
					$this->_context($edit->orig);
					break;

				case 'text_diff_op_add':
					$this->_added($edit->final);
					break;

				case 'text_diff_op_delete':
					$this->_deleted($edit->orig);
					break;

				case 'text_diff_op_change':
					$this->_changed($edit->orig, $edit->final);
					break;
			}
		}

		return array($this->orig, $this->final);
	}

	protected function _context($lines) {
		$this->_lines('context', $lines);
	}

	protected function _added($lines, $changemode = false) {
		if ($changemode) {
			$this->_lines('change-added', $lines, '+');
		} else {
			$this->_lines('added', $lines, '+');
		}
	}

	protected function _deleted($lines, $changemode = false) {
		if ($changemode) {
			$this->_lines('change-deleted', $lines, '-');
		} else {
			$this->_lines('deleted', $lines, '-');
		}
	}

	protected function _changed($orig, $final) {
		$this->_deleted($orig, true);
		$this->_added($final, true);
	}

}
