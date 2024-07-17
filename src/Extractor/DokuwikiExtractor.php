<?php

namespace HalloWelt\MigrateDokuwiki\Extractor;

use HalloWelt\MediaWiki\Lib\Migration\ExtractorBase;
use SplFileInfo;

class DokuwikiExtractor extends ExtractorBase {

	/**
	 * @var array
	 */
	private $categories = [];

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	protected function doExtract( SplFileInfo $file ): bool {
		if ( isset( $this->config['config']['categories'] ) ) {
			$this->categories = $this->config['config']['categories'];
		}

		return true;
	}
}
