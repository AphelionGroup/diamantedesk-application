<?php
/*
 * Copyright (c) 2015 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */

namespace Diamante\DeskBundle\Controller\Shared;


use Diamante\DeskBundle\Api\Dto\AttachmentDto;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

trait ResponseHandlerTrait
{
    protected $massActionResultFormat = "diamante.%s.actions.mass.%s.messages.%s";
    /**
     * @param string $saveAndStay
     * @param string $saveAndClose
     * @param array $params
     * @return RedirectResponse
     */
    protected function getSuccessSaveResponse($saveAndStay, $saveAndClose, $params = array())
    {
        return $this->get('oro_ui.router')->redirectAfterSave(
            ['route' => $saveAndStay, 'parameters' => $params],
            ['route' => $saveAndClose, 'parameters' => $params]
        );
    }

    /**
     * @param AttachmentDto $attachmentDto
     * @return BinaryFileResponse
     */
    protected function getFileDownloadResponse(AttachmentDto $attachmentDto)
    {
        $response = new BinaryFileResponse($attachmentDto->getFilePath());
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $attachmentDto->getFileName(),
            iconv('UTF-8', 'ASCII//TRANSLIT', $attachmentDto->getFileName())
        );

        return $response;
    }

    /**
     * @param null $redirectUrl
     * @param array $redirectParams
     * @param bool|true $reload
     * @return array
     */
    protected function getWidgetResponse($redirectUrl = null, $redirectParams = [], $reload = true)
    {
        $response = ['reload_page' => $reload];

        if (!is_null($redirectUrl) && !empty($redirectParams)) {
            $response['redirect'] = $this->generateUrl($redirectUrl, $redirectParams);
        }

        return $response;
    }

    /**
     * @param string $action
     * @param string $section
     * @param boolean $result
     * @return JsonResponse
     */
    protected function getMassActionResponse($action, $section, $result = true)
    {
        $data = [
            'successful' => $result ? 1 : 0,
            'message'    => $this->get('translator')
                ->trans(sprintf($this->massActionResultFormat, $section, $action, $result ? 'success' : 'fail')),
        ];

        return new JsonResponse($data, $result ? 200 : 500);
    }
}