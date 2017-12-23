<?php
namespace App\View\Helper;

use Tools\View\Helper\TimelineHelper as ToolsTimelineHelper;

class TimelineHelper extends ToolsTimelineHelper {

	/**
	 * @return array
	 */
	public function items() {
		return $this->_items;
	}

}
