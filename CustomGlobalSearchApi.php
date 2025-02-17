<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit(0);
}

use Sugarcrm\Sugarcrm\SearchEngine\SearchEngine;
use Sugarcrm\Sugarcrm\SearchEngine\Capability\GlobalSearch\ResultSetInterface;
use Sugarcrm\Sugarcrm\Elasticsearch\Adapter\Result;

class CustomGlobalSearchApi extends SugarApi
{
    public function registerApiRest()
    {
        return [
            'customGlobalSearch' => [
                'reqType' => ['GET', 'POST', 'OPTIONS'],
                'path' => ['customGlobalSearch'],
                'pathVars' => [''],
                'method' => 'customGlobalSearchMethod',
                'shortHelp' => 'Custom Global Search',
                'noLoginRequired' => true,
                'exceptions' => [
                    'SugarApiExceptionSearchUnavailable',
                    'SugarApiExceptionSearchRuntime',
                ],
            ],
        ];
    }

    public function customGlobalSearchMethod(ServiceBase $api, array $args)
    {
        global $current_user;

        // Validate Bearer Token
        $headers = getallheaders();

        if (!isset($headers['Authorization']) || strpos($headers['Authorization'], 'Bearer ') !== 0) {
            throw new SugarApiException('Unauthorized');
        }

        $bearerToken = substr($headers['Authorization'], 7);

        // Validate Bearer Token (Replace this with a secure token validation mechanism)
        if (!password_verify($bearerToken, getenv('SUGARCRM_BEARER_HASH'))) {
            throw new SugarApiException('Unauthorized');
        }

        $adminUser = BeanFactory::retrieveBean('Users');
        $adminUser->retrieve_by_string_fields(['user_name' => getenv('SUGARCRM_ADMIN_USER')]);

        if (empty($adminUser->id)) {
            throw new SugarApiException('Admin user not found');
        }

        $current_user = $adminUser;
        $api->user = $current_user;

        if (!isset($args['module_list']) || empty(trim($args['module_list']))) {
            throw new SugarApiException('Missing required parameter: module_list');
        }

        $term = $args['q'] ?? '';
        $limit = isset($args['max_num']) ? (int) $args['max_num'] : 20;
        $offset = isset($args['offset']) ? (int) $args['offset'] : 0;
        $highlights = isset($args['highlights']) ? (bool) $args['highlights'] : true;
        $sort = isset($args['sort']) && is_array($args['sort']) ? $args['sort'] : [];

        $modules = is_array($args['module_list']) ? $args['module_list'] : explode(',', $args['module_list']);

        try {
            $globalSearch = $this->getSearchEngine()->getEngine();
            $globalSearch->from($modules);

            if ($term !== '') {
                $globalSearch->term($term);
            }

            $globalSearch
                ->limit($limit)
                ->offset($offset)
                ->highlighter($highlights)
                ->sort($sort);

            $resultSet = $globalSearch->search();

            return [
                'next_offset' => $this->getNextOffset($resultSet->getTotalHits(), $limit, $offset),
                'total' => $resultSet->getTotalHits(),
                'query_time' => $resultSet->getQueryTime(),
                'records' => $this->formatResults($api, $args, $resultSet),
            ];
        } catch (\Exception $e) {
            throw new SugarApiExceptionSearchRuntime();
        }
    }

    protected function getSearchEngine()
    {
        try {
            $engine = SearchEngine::getInstance('GlobalSearch');
        } catch (\Exception $e) {
            throw new SugarApiExceptionSearchRuntime();
        }

        if (!$engine->isAvailable()) {
            throw new SugarApiExceptionSearchUnavailable();
        }

        return $engine;
    }

    protected function getNextOffset($total, $limit, $offset)
    {
        return ($total > ($limit + $offset)) ? ($limit + $offset) : -1;
    }

    protected function formatResults(ServiceBase $api, array $args, ResultSetInterface $results)
    {
        $formatted = [];

        foreach ($results as $result) {
            $data = $this->formatBeanFromResult($api, $args, $result);

            if ($score = $result->getScore()) {
                $data['_score'] = $score;
            }

            if ($highlights = $result->getHighlights()) {
                foreach ($highlights as $field => $highlight) {
                    if (!isset($data[$field])) {
                        unset($highlights[$field]);
                    }
                }
                $data['_highlights'] = $highlights;
            }

            $formatted[] = $data;
        }

        return $formatted;
    }

    protected function formatBeanFromResult(ServiceBase $api, array $args, Result $result)
    {
        $args = ['fields' => $result->getDataFields()];
        $bean = $result->getBean();

        if (!empty($bean->emailAddress) && isset($bean->email)) {
            $bean->emailAddress->addresses = $bean->email;
            $bean->emailAddress->hasFetched = true;
        }

        return $this->formatBean($api, $args, $bean);
    }
}
