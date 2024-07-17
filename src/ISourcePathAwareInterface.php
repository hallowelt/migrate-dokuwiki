<?php

namespace HalloWelt\MigrateDokuwiki;

interface ISourcePathAwareInterface {

	/**
	 *
	 * @param string $path
	 * @return void
	 */
	public function setSourcePath( $path );
}
