<?php

/**
 * "Side-by-Side" diff renderer.
 *
 * This class renders the diff in "side-by-side" format, like Wikipedia.
 *
 * @package Text_Diff
 */
class Horde_Text_Diff_Renderer_Sidebyside extends Horde_Text_Diff_Renderer {

	protected $_character_diff = true;

	protected $_leading_context_lines = 4;

	protected $_trailing_context_lines = 4;

	protected function _startDiff() {
		ob_start();
		echo '<table class="normal diff">';
	}

	protected function _endDiff() {
		echo '</table>';
		$val = ob_get_contents();
		ob_end_clean();
		return $val;
	}

	protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen) {
		return "$xbeg,$xlen,$ybeg,$ylen";
	}

	protected function _startBlock($header) {
		$h = split(",", $header);
		echo '<tr class="diffheader"><td colspan="2">';
		if ($h[1] == 1)
			echo "Line:&nbsp;" . $h[0];
		else {
			$h[1] = $h[0] + $h[1] - 1;
			echo "Lines:&nbsp;" . $h[0] . '-' . $h[1];
		}
		echo '</td><td colspan="2">';
		if ($h[3] == 1)
			echo "Line:&nbsp;" . $h[2];
		else {
			$h[3] = $h[2] + $h[3] - 1;
			echo "Lines:&nbsp;" . $h[2] . '-' . $h[3];
		}

		echo '</td></tr>';
	}

	protected function _endBlock() {
	}

	protected function _lines($type, $lines, $prefix = '') {
		if ($type == 'context') {
			foreach ($lines as $line) {
				if (!empty($line))
					echo "<tr class='diffbody'><td>&nbsp;</td><td>$line</td><td>&nbsp;</td><td>$line</td></tr>\n";
			}
		} elseif ($type == 'added') {
			foreach ($lines as $line) {
				if (!empty($line))
					echo "<tr><td colspan='2'>&nbsp;</td><td class='diffadded'>$prefix</td><td class='diffadded'>$line</td></tr>\n";
			}
		} elseif ($type == 'deleted') {
			foreach ($lines as $line) {
				if (!empty($line))
					echo "<tr><td class='diffdeleted'>$prefix</td><td class='diffdeleted'>$line</td><td colspan='2'>&nbsp;</td></tr>\n";
			}
		} elseif ($type == 'change-deleted') {
			echo '<tr><td class="diffdeleted" valign="top">' . $prefix . '</td><td class="diffdeleted" valign="top">' . implode("<br />", $lines) .
				"</td>\n";
		} elseif ($type == 'change-added') {
			echo '<td class="diffadded" valign="top">' . $prefix . '</td><td class="diffadded" valign="top">' . implode("<br />", $lines) . "</td></tr>\n";
		}
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
		if ($this->_character_diff) {
			$lines = $this->diffChar($orig, $final);
			$this->_deleted(array($lines[0]), true);
			$this->_added(array($lines[1]), true);
		} else {

			$this->_deleted($orig, true);
			$this->_added($final, true);
		}
	}

	//refactor!!!
	public function diffChar($orig, $final) {
		$line1 = preg_split('//', implode("<br />", $orig), -1, PREG_SPLIT_NO_EMPTY);
		$line2 = preg_split('//', implode("<br />", $final), -1, PREG_SPLIT_NO_EMPTY);
		$z = new Horde_Text_Diff($line1, $line2);
		if ($z->isEmpty()) {
			return array($orig[0], $final[0]);
		}
		// echo "<pre>";print_r($z);echo "</pre>";
		$renderer = new Horde_Text_Diff_Renderer_Character(10000);
		return $renderer->render($z);
	}

}
