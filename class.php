<?php

namespace Svarok\Component;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\ArrayResult;
use Bitrix\Main\Diag\SqlTrackerQuery;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Monolog\Logger;
use Monolog\Registry;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var \CMain $APPLICATION */
/** @var \CUser $USER */

class BannersList extends \CBitrixComponent
{
    /** @var bool */
    protected $debugMode = false;

    /** @var array */
    private $debugData = [];

    /** @var int */
    private $startMicroTime;

    /** @var Logger */
    protected $logger;

    /** @var array */
    private $loggerErrorPool = [];

    /** @var array */
    private $loggerContext;

    public function __construct($component = null)
    {
        parent::__construct($component);

        $this->logger = Registry::getInstance('app');

        /** @var \CUser $USER */
        global $USER;

        $this->debugMode = ($this->request->getQuery('debug_mode') === 'Y' && $USER->IsAdmin());

        $this->startMicroTime = microtime(true);

        $this->addDebugData(0, 'Start component');

        $this->loggerConfigure();
    }

    protected function loggerConfigure()
    {
        /** @global \CUser $USER */
        global $USER;

        if (!(is_object($USER) && $USER instanceof \CUser)) {
            $USER = new \CUser();
        }

        $prepareScriptName = ltrim(
            str_replace(
                dirname($_SERVER['DOCUMENT_ROOT']),
                '',
                __FILE__
            ),
            DIRECTORY_SEPARATOR
        );

        $this->loggerContext = [
            'type' => 'component',
            'bitrix_user_id' => $USER->GetID(),
            'script_name' => $prepareScriptName,
            'component_name' => $this->getName()
        ];
    }

    /**
     * Check Required Modules
     *
     * @throws SystemException
     * @throws \Bitrix\Main\LoaderException
     */
    protected function checkModules()
    {
        foreach (['highloadblock'] as $moduleId) {
            if (!Loader::includeModule($moduleId)) {
                $errorMessage = Loc::getMessage('ERROR_INCLUDE_MODULE', ['#MODULE#' => $moduleId]);

                $this->loggerErrorPool[] = $errorMessage;

                throw new SystemException(
                    $errorMessage
                );
            }
        }
    }

    /**
     * Load language file.
     */
    public function onIncludeComponentLang()
    {
        $this->includeComponentLang(basename(__FILE__));

        Loc::loadMessages(__FILE__);
    }

    /**
     * Prepare Component Params
     *
     * @param array $params
     *
     * @return array
     * @throws ArgumentException
     */
    public function onPrepareComponentParams($params)
    {
        $params['BLOCK_ID'] = (int) $params['BLOCK_ID'];

        if (!$params['BLOCK_ID']) {

            $errorMessage = Loc::getMessage('ERROR_PARAM_BLOCK_ID');

            $this->loggerErrorPool[] = $errorMessage;

            throw new ArgumentException($errorMessage);
        }

        $params['LIMIT'] = isset($params['LIMIT']) && (int) $params['LIMIT']
            ? (int) $params['LIMIT']
            : 10;

        $params['SORT_FIELD'] = isset($params['SORT_FIELD']) ? $params['SORT_FIELD'] : 'UF_SORT';

        $params['SORT_ORDER'] = isset($params['SORT_ORDER']) ? $params['SORT_ORDER'] : 'ASC';

        return $params;
    }

    /**
     * Prepare component data
     *
     * @throws ArgumentException
     * @throws SystemException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    protected function prepareData()
    {
        $hlblock = HL\HighloadBlockTable::getById($this->arParams['BLOCK_ID'])->fetch();

        if (empty($hlblock)) {
            $errorMessage = Loc::getMessage('HLBLOCK_LIST_404');

            $this->loggerErrorPool[] = $errorMessage;

            throw new ArgumentException($errorMessage);
        }

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $sort = [$this->arParams['SORT_FIELD'] => $this->arParams['SORT_ORDER']];

        $mainQuery = (new Query($entity))
            ->setSelect(['*'])
            ->setOrder($sort)
            ->setLimit($this->arParams['LIMIT']);

        $this->addDebugData(microtime(true) - $this->startMicroTime, 'Before query');

        try {

            $iterator = $mainQuery->exec();

            $this->addDebugData(microtime(true) - $this->startMicroTime, 'After query');

        } catch (\Exception $e) {

            $iterator = new ArrayResult([]);

            $iterator->setCount(0);

            $this->loggerErrorPool[] = sprintf(
                "First query error. \n--SQL--:\n%s\n--Error--:\n%s",
                $mainQuery->getQuery(),
                $e->getMessage()
            );

        }

        $this->addDebugData($mainQuery->getQuery(), 'Query');

        while ($row = $iterator->fetch()) {
            $this->arResult['ELEMENTS'][] = $row;
        }
    }

    /**
     * Extract data from cache. No action by default.
     *
     * @return bool
     */
    protected function extractDataFromCache()
    {
        /** @var \CMain $APPLICATION */
        global $APPLICATION;

        if ($this->arParams['CACHE_TYPE'] === 'N') {
            return false;
        }

        $cacheKeys =
            $this->arParams + [
                __FILE__,
                $APPLICATION->GetCurPage(),
                [
                    $this->arParams['SORT_FIELD'],
                    $this->arParams['SORT_ORDER'],
                    $this->arParams['LIMIT'],
                ]
            ];

        /** @var \CUser $USER */
        global $USER;

        return !($this->StartResultCache(3600, [$USER->GetGroups(), $cacheKeys]));
    }

    /**
     * Is AJAX Request?
     * @return bool
     */
    protected function isAjax()
    {
        return (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) || $this->request->isAjaxRequest();
    }

    /**
     * Start Component
     */
    public function executeComponent()
    {
        global $APPLICATION;

        try {

            $this->checkModules();

            if (!$this->extractDataFromCache()) {

                $this->prepareData();

                $this->setResultCacheKeys([]);

                $this->includeComponentTemplate();

                $this->endResultCache();

            }

        } catch (\Exception $e) {

            $this->AbortResultCache();

            if ($this->isAjax()) {
                $APPLICATION->restartBuffer();

                echo Json::encode([
                    'STATUS' => 'ERROR',
                    'MESSAGE' => $e->getMessage(),
                ]);

                die();
            }

            if ($this->debugMode) {
                ShowError(sprintf(
                    '%s (%s:%s)%s',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    PHP_EOL . $e->getTraceAsString()
                ));
            } else {
                ShowError(sprintf('%s', $e->getMessage()));
            }

            $this->loggerErrorPool[] = sprintf(
                "Catch error of component executeComponent method.\n--Error--:\n%s\n--Error trace--:\n%s",
                $e->getMessage(),
                $e->getTraceAsString()
            );

        }

        $this->addDebugData(microtime(true) - $this->startMicroTime, 'End component');

        $this->showDebugData();

        if (sizeof($this->loggerErrorPool) > 0) {
            $this->logger->warn(
                sprintf('Error of component execute. Error pool: "%s"', Json::encode($this->loggerErrorPool)),
                $this->loggerContext
            );
        }
    }

    /**
     * @param $data
     * @param null $key
     */
    private function addDebugData($data, $key = null)
    {
        if ($this->debugMode) {
            if ($key === null) {
                $key = microtime(true) . mt_rand();
            }

            $this->debugData[$key] = $data;
        }
    }

    /**
     * show debug data
     *
     * @return void
     */
    private function showDebugData()
    {
        if ($this->debugMode) {
            $debugData = $this->debugData;

            array_walk_recursive($debugData, function (&$item, $key) {
                if (
                    is_object($item)
                    && ($item instanceof SqlTrackerQuery)
                ) {
                    $item = $item->getSql();
                }

                $item = htmlspecialcharsbx($item);
            });

            echo '<pre>';
            var_dump($debugData);
            echo '</pre>';
        }
    }
}
