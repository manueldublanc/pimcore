<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Reports;

use Pimcore\Model\Tool\CustomReport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/custom-report")
 */
class CustomReportController extends ReportsControllerBase
{
    /**
     * @Route("/tree", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeAction(Request $request)
    {
        $this->checkPermission('reports_config');
        $reports = CustomReport\Config::getReportsList();

        return $this->adminJson($reports);
    }

    /**
     * @Route("/portlet-report-list", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function portletReportListAction(Request $request)
    {
        $this->checkPermission('reports');
        $reports = CustomReport\Config::getReportsList($this->getAdminUser());

        return $this->adminJson(['data' => $reports]);
    }

    /**
     * @Route("/add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $this->checkPermission('reports_config');

        $success = false;

        $report = CustomReport\Config::getByName($request->get('name'));

        if (!$report) {
            $report = new CustomReport\Config();
            $report->setName($request->get('name'));
            $report->save();

            $success = true;
        }

        return $this->adminJson(['success' => $success, 'id' => $report->getName()]);
    }

    /**
     * @Route("/delete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        $this->checkPermission('reports_config');

        $report = CustomReport\Config::getByName($request->get('name'));
        $report->delete();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/clone", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cloneAction(Request $request)
    {
        $this->checkPermission('reports_config');

        $newName = $request->get('newName');
        $report = CustomReport\Config::getByName($newName);
        if ($report) {
            throw new \Exception('report already exists');
        }

        $report = CustomReport\Config::getByName($request->get('name'));
        $reportData = $this->encodeJson($report);
        $reportData = $this->decodeJson($reportData);

        unset($reportData['name']);
        $reportData['name'] = $newName;

        foreach ($reportData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($report, $setter)) {
                $report->$setter($value);
            }
        }

        $report->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/get", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $this->checkPermissionsHasOneOf(['reports_config', 'reports']);

        $report = CustomReport\Config::getByName($request->get('name'));

        return $this->adminJson($report);
    }

    /**
     * @Route("/update", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        $this->checkPermission('reports_config');

        $report = CustomReport\Config::getByName($request->get('name'));
        $data = $this->decodeJson($request->get('configuration'));

        if (!is_array($data['yAxis'])) {
            $data['yAxis'] = strlen($data['yAxis']) ? [$data['yAxis']] : [];
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($report, $setter)) {
                $report->$setter($value);
            }
        }

        $report->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/column-config", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function columnConfigAction(Request $request)
    {
        $this->checkPermission('reports_config');

        $report = CustomReport\Config::getByName($request->get('name'));
        $columnConfiguration = $report->getColumnConfiguration();
        if (!is_array($columnConfiguration)) {
            $columnConfiguration = [];
        }

        $configuration = json_decode($request->get('configuration'));
        $configuration = $configuration[0];

        $success = false;
        $columns = null;
        $errorMessage = null;

        $result = [];

        try {
            $adapter = CustomReport\Config::getAdapter($configuration);
            $columns = $adapter->getColumns($configuration);
            if (!is_array($columns)) {
                $columns = [];
            }

            foreach ($columnConfiguration as $item) {
                $name = $item['name'];
                if (in_array($name, $columns)) {
                    $result[] = $name;
                    array_splice($columns, array_search($name, $columns), 1);
                }
            }
            foreach ($columns as $remainingColumn) {
                $result[] = $remainingColumn;
            }

            $success = true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->adminJson([
            'success' => $success,
            'columns' => $result,
            'errorMessage' => $errorMessage
        ]);
    }

    /**
     * @Route("/get-report-config", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getReportConfigAction(Request $request)
    {
        $this->checkPermission('reports');

        $reports = [];

        $list = new CustomReport\Config\Listing();
        $items = $list->getDao()->loadForGivenUser($this->getAdminUser());

        /** @var $report CustomReport\Config */
        foreach ($items as $report) {
            $reports[] = [
                'name' => $report->getName(),
                'niceName' => $report->getNiceName(),
                'iconClass' => $report->getIconClass(),
                'group' => $report->getGroup(),
                'groupIconClass' => $report->getGroupIconClass(),
                'menuShortcut' => $report->getMenuShortcut(),
                'reportClass' => $report->getReportClass()
            ];
        }

        return $this->adminJson([
            'success' => true,
            'reports' => $reports
        ]);
    }

    /**
     * @Route("/data", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function dataAction(Request $request)
    {
        $this->checkPermission('reports');

        $offset = $request->get('start', 0);
        $limit = $request->get('limit', 40);
        $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
        if ($sortingSettings['orderKey']) {
            $sort = $sortingSettings['orderKey'];
            $dir = $sortingSettings['order'];
        }

        $filters = ($request->get('filter') ? json_decode($request->get('filter'), true) : null);

        $drillDownFilters = $request->get('drillDownFilters', null);

        $config = CustomReport\Config::getByName($request->get('name'));
        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, $offset, $limit, null, $drillDownFilters, $config);

        return $this->adminJson([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total']
        ]);
    }

    /**
     * @Route("/drill-down-options", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function drillDownOptionsAction(Request $request)
    {
        $this->checkPermission('reports');

        $field = $request->get('field');
        $filters = ($request->get('filter') ? json_decode($request->get('filter'), true) : null);
        $drillDownFilters = $request->get('drillDownFilters', null);

        $config = CustomReport\Config::getByName($request->get('name'));
        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);
        $result = $adapter->getAvailableOptions($filters, $field, $drillDownFilters);

        return $this->adminJson([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * @Route("/chart", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function chartAction(Request $request)
    {
        $this->checkPermission('reports');

        $sort = $request->get('sort');
        $dir = $request->get('dir');
        $filters = ($request->get('filter') ? json_decode($request->get('filter'), true) : null);
        $drillDownFilters = $request->get('drillDownFilters', null);

        $config = CustomReport\Config::getByName($request->get('name'));

        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);
        $result = $adapter->getData($filters, $sort, $dir, null, null, null, $drillDownFilters);

        return $this->adminJson([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total']
        ]);
    }

    /**
     * @Route("/download-csv", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function downloadCsvAction(Request $request)
    {
        $this->checkPermission('reports');

        set_time_limit(300);

        $sort = $request->get('sort');
        $dir = $request->get('dir');
        $filters = ($request->get('filter') ? json_decode($request->get('filter'), true) : null);
        $drillDownFilters = $request->get('drillDownFilters', null);
        $includeHeaders = $request->get('headers', false);

        $config = CustomReport\Config::getByName($request->get('name'));

        $columns = $config->getColumnConfiguration();
        $fields = [];
        $headers = [];
        foreach ($columns as $column) {
            if ($column['export']) {
                $fields[] = $column['name'];
                $headers[] = !empty($column['label']) ? $column['label'] : $column['name'];
            }
        }

        $configuration = $config->getDataSourceConfig();
        //if many rows returned as an array than use the first row. Fixes: #782
        $configuration = is_array($configuration)
            ? $configuration[0]
            : $configuration;

        $adapter = CustomReport\Config::getAdapter($configuration, $config);
        $result = $adapter->getData($filters, $sort, $dir, null, null, $fields, $drillDownFilters);

        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/report-export-' . uniqid() . '.csv';
        @unlink($exportFile);

        $fp = fopen($exportFile, 'w');

        if ($includeHeaders) {
            fputcsv($fp, $headers, ';');
        }

        foreach ($result['data'] as $row) {
            fputcsv($fp, array_values($row), ';');
        }

        fclose($fp);

        $response = new BinaryFileResponse($exportFile);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'export.csv');
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
