<?php
/**
 * @package silverstripe-drupal-connector
 */

// Add the Zend XML-RPC library to the include path.
set_include_path(dirname(__FILE__) . '/thirdparty' . PATH_SEPARATOR . get_include_path());

DrupalImporter::add_extension('PostImportStepExtension');