<?php

namespace Directus\Api\Routes;

use Directus\Application\Application;
use Directus\Application\Http\Request;
use Directus\Application\Http\Response;
use Directus\Application\Route;
use Directus\Services\PermissionsService;
use Directus\Util\ArrayUtils;

class Permissions extends Route
{
    public function __invoke(Application $app)
    {
        $app->post('', [$this, 'create']);
        $app->get('/{id}', [$this, 'read']);
        $app->patch('/{id}', [$this, 'update']);
        $app->patch('', [$this, 'update']);
        $app->delete('/{id}', [$this, 'delete']);
        $app->get('', [$this, 'all']);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function create(Request $request, Response $response)
    {
        $this->validateRequestPayload($request);
        $payload = $request->getParsedBody();
        if (isset($payload[0]) && is_array($payload[0])) {
            return $this->batch($request, $response);
        }

        $service = new PermissionsService($this->container);
        $responseData = $service->create(
            $payload,
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function read(Request $request, Response $response)
    {
        $service = new PermissionsService($this->container);
        $responseData = $service->findByIds(
            $request->getAttribute('id'),
            ArrayUtils::pick($request->getQueryParams(), ['fields', 'meta'])
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function update(Request $request, Response $response)
    {
        $this->validateRequestPayload($request);
        $payload = $request->getParsedBody();
        if (isset($payload[0]) && is_array($payload[0])) {
            return $this->batch($request, $response);
        }

        $id = $request->getAttribute('id');
        if (strpos($id, ',') !== false) {
            return $this->batch($request, $response);
        }

        $service = new PermissionsService($this->container);
        $responseData = $service->update(
            $request->getAttribute('id'),
            $payload,
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function delete(Request $request, Response $response)
    {
        $service = new PermissionsService($this->container);
        $service->delete(
            $request->getAttribute('id'),
            $request->getQueryParams()
        );

        $response = $response->withStatus(204);

        return $this->responseWithData($request, $response, []);
    }
    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function all(Request $request, Response $response)
    {
        $service = new PermissionsService($this->container);
        $responseData = $service->findAll(
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     *
     * @throws \Exception
     */
    protected function batch(Request $request, Response $response)
    {
        $permissionService = new PermissionsService($this->container);

        $collection = $request->getAttribute('collection');
        $permissionService->throwErrorIfSystemTable($collection);

        $payload = $request->getParsedBody();
        $params = $request->getQueryParams();

        $responseData = null;
        if ($request->isPost()) {
            $responseData = $permissionService->batchCreate($payload, $params);
        } else if ($request->isPatch()) {
            if ($request->getAttribute('id')) {
                $ids = explode(',', $request->getAttribute('id'));
                $responseData = $permissionService->batchUpdateWithIds($ids, $payload, $params);
            } else {
                $responseData = $permissionService->batchUpdate($payload, $params);
            }
        } else if ($request->isDelete()) {
            $ids = explode(',', $request->getAttribute('id'));
            $permissionService->batchDeleteWithIds($ids, $params);
        }

        if (empty($responseData)) {
            $response = $response->withStatus(204);
            $responseData = [];
        }

        return $this->responseWithData($request, $response, $responseData);
    }
}
