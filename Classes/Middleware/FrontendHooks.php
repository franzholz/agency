<?php

namespace JambageCom\Agency\Middleware;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JambageCom\Div2007\Captcha\Captcha;
use JambageCom\Div2007\Captcha\Freecap;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

use JambageCom\Agency\Constants\Extension;


 
/**
 * Stores the original request for an Ajax call before processing a request for the TYPO3 Frontend.
 *
 */
class FrontendHooks implements MiddlewareInterface
{
    /**
     * Hook to initialize the current captcha
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extensionKey = Extension::KEY;

        // Configure captcha hooks
        if (
            !isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha']) ||
            !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'])
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'] = [];
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'][] = Captcha::class;
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['captcha'][] = Freecap::class;
        }
        return $handler->handle($request);
    }
}

