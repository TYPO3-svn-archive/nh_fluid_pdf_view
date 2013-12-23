<?php
namespace Nh\NhFluidPdfView\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 * @package rev_schoolcompare
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class PdfView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Fluid\View\StandaloneView
	 * @inject
	 */
	protected $standaloneView;

	/**
	 * @var array
	 */
	protected $frameworkConfiguration;

	/**
	 * Initalizes the standalone view and the framework configuration
	 */
	public function initializeView() {
		$this->frameworkConfiguration = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		$packageResourcesPath = ExtensionManagementUtility::extPath(
			$this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/';

		$layoutRootPath = !empty($this->frameworkConfiguration['view']['layoutRootPath']) ?
			GeneralUtility::getFileAbsFileName($this->frameworkConfiguration['view']['layoutRootPath']) :
			$packageResourcesPath . 'Private/Layouts';

		$templateRootPath = !empty($this->frameworkConfiguration['view']['templateRootPath']) ?
			GeneralUtility::getFileAbsFileName($this->frameworkConfiguration['view']['templateRootPath']) :
			$packageResourcesPath . 'Private/Templates';

		$partialRootPath = !empty($this->frameworkConfiguration['view']['partialRootPath']) ?
			GeneralUtility::getFileAbsFileName($this->frameworkConfiguration['view']['partialRootPath']) :
			$packageResourcesPath . 'Private/Partials';

		$this->standaloneView->getRequest()->setControllerExtensionName(
			$this->controllerContext->getRequest()->getControllerExtensionName());

		$this->standaloneView->setLayoutRootPath($layoutRootPath);

		$this->standaloneView->setPartialRootPath($partialRootPath);

		$this->standaloneView->setTemplatePathAndFilename(
			$templateRootPath . '/' .
			$this->controllerContext->getRequest()->getControllerName() . '/' .
			ucfirst($this->controllerContext->getRequest()->getControllerActionName()) . '.html'
		);
	}

	public function render() {
		$this->standaloneView->assignMultiple($this->variables);
		$pdfContent = $this->renderPdf();

		header('Content-type: application/pdf');
		header('Content-Disposition: inline; filename="' . $GLOBALS['TSFE']->page['title'] . '"');
		echo $pdfContent; exit;
	}

	/**
	 * Renders the pdf by passing the rendered standalone content to wkhtmltopdf
	 *
	 * @return string
	 */
	public function renderPdf() {
		$cmd = 'echo \'' . $this->standaloneView->render() . '\' | ' .
			ExtensionManagementUtility::extPath('webkitpdf') . 'res/wkhtmltopdf'  .
			$this->createCommandLineOptions() .
			' - -';
		ob_start();
		passthru($cmd, $return);
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Creates the wkhtmltopdf commandline options from settings
	 *
	 * @return string
	 */
	public function createCommandLineOptions() {
		$optionsString = '';
		foreach ($this->frameworkConfiguration['settings']['wkhtmltopdf'] as $key => $value) {
			$optionsString .= ' --' . $key . ' '. $value;
		}
		return $optionsString;
	}
}