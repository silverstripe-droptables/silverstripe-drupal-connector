<?php

class PostImportStepExtension extends Extension {
	public $importResults = array();

	public function onAfterImport($result) {
		$this->importResults[] = $result;
	}
}