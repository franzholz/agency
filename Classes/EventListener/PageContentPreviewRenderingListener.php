<?php

namespace JambageCom\Agency\EventListener;


use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

use JambageCom\Div2007\Base\PageContentPreviewRenderingListenerBase;
use JambageCom\Div2007\Utility\HtmlUtility;

use JambageCom\Agency\Constants\Extension;


class PageContentPreviewRenderingListener extends PageContentPreviewRenderingListenerBase {
    public $extensionKey = Extension::KEY;

}
