<?php

/**
 * @defgroup plugins_importexport_crossref
 */
 
/**
 * @file plugins/importexport/crossref/index.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_crossref
 * @brief Wrapper for CrossRef export plugin.
 *
 */

require_once('CrossRefExportPlugin.inc.php');

return new CrossRefExportPlugin();

?>
