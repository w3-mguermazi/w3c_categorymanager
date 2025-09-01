<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_categorymanager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use W3code\W3cCategoryManager\Domain\Model\Category;
use W3code\W3cCategoryManager\Domain\Repository\CategoryRepository;
use W3code\W3cCategoryManager\Utility\BackendUserUtility;
use W3code\W3cCategoryManager\Utility\LocalizationUtility;

/**
 * Class AjaxModuleController
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 * @author Haythem Daoud <haythem.daoud@w3code.tn>
 */
#[AsController]
class AjaxModuleController extends ActionController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param PersistenceManager $persistenceManager
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected PersistenceManager $persistenceManager
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \JsonException
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function toggleHideAction(ServerRequestInterface $request): ResponseInterface
    {

        $uid = (int)($request->getParsedBody()['uid'] ?? $request->getQueryParams()['uid'] ?? null);
        $hidden = (int)($request->getParsedBody()['hidden'] ?? $request->getQueryParams()['hidden'] ?? null);

        if ($uid > 0) {
            /** @var Category $category */
            $category = $this->categoryRepository->findBy([
                'uid' => $uid,
                'hidden' => !$hidden,
            ])->getFirst();

            $category->setHidden((bool)$hidden);

            $this->categoryRepository->update($category);
            $this->persistenceManager->persistAll();

            $result = [
                'success' => true,
                'message' => LocalizationUtility::translate('category.success'),
                'uid' => $uid,
                'hidden' => $hidden,
            ];
        } else {
            $result = [
                'success' => false,
            ];
        }

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));

        return $response;

    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function toggleExpandAction(ServerRequestInterface $request): ResponseInterface
    {
        $uid = (int)($request->getParsedBody()['uid'] ?? $request->getQueryParams()['uid'] ?? null);
        $expanded = (int)($request->getParsedBody()['state'] ?? $request->getQueryParams()['state'] ?? null);

        if ($uid > 0) {
            $backendUser = BackendUserUtility::getBackendUser();
            $expandedNodes = $backendUser->uc['w3c_categorymanager']['expandedNodes'] ?? [];

            if ($expanded) {
                if (!$expandedNodes || !in_array($uid, $expandedNodes)) {
                    $expandedNodes[] = $uid;
                }
            } else {
                if ($expandedNodes && in_array($uid, $expandedNodes)) {
                    $key = array_search($uid, $expandedNodes);
                    if ($key !== false) {
                        unset($expandedNodes[$key]);
                    }
                }
            }

            $backendUser->uc['w3c_categorymanager']['expandedNodes'] = $expandedNodes;
            $backendUser->writeUC();

            $result = [
                'success' => true,
                'uid' => $uid,
                'expanded' => $expanded,
            ];
        } else {
            $result = [
                'success' => false,
            ];
        }

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));

        return $response;
    }
}
