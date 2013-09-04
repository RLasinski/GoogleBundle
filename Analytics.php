<?php
namespace AntiMattr\GoogleBundle;

use AntiMattr\GoogleBundle\Analytics\CustomVariable;
use AntiMattr\GoogleBundle\Analytics\Event;
use AntiMattr\GoogleBundle\Analytics\Item;
use AntiMattr\GoogleBundle\Analytics\Option;
use AntiMattr\GoogleBundle\Analytics\Transaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Analytics
{
    const EVENT_QUEUE_KEY      = 'google_analytics/event/queue';
    const CUSTOM_PAGE_VIEW_KEY = 'google_analytics/page_view';
    const PAGE_VIEW_QUEUE_KEY  = 'google_analytics/page_view/queue';
    const TRANSACTION_KEY      = 'google_analytics/transaction';
    const ITEMS_KEY            = 'google_analytics/items';

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $customVariables = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var bool
     */
    protected $pageViewsWithBaseUrl = true;

    /**
     * @var array
     */
    protected $trackers;

    /**
     * @var array
     */
    protected $whitelist;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $tableId;

    /**
     * @var array
     */
    protected $pageRules = array();

    /**
     * @var string
     */
    protected $forcedPageName = null;

    /**
     * @var array
     */
    protected $forcedPageParams = array();

    /**
     * @param ContainerInterface $container
     * @param array              $trackers
     * @param array              $whitelist
     * @param array              $dashboard
     * @param array              $pageRules
     */
    public function __construct(
        ContainerInterface $container,
        array $trackers = array(),
        array $whitelist = array(),
        array $dashboard = array(),
        array $pageRules = array()
    ) {
        $this->container = $container;
        $this->trackers = $trackers;
        $this->whitelist = $whitelist;
        $this->pageRules = $pageRules;

        $this->apiKey = isset($dashboard['api_key']) ? $dashboard['api_key'] : '';
        $this->clientId = isset($dashboard['client_id']) ? $dashboard['client_id'] : '';
        $this->tableId = isset($dashboard['table_id']) ? $dashboard['table_id'] : '';
    }

    /**
     * @return $this
     */
    public function excludeBaseUrl()
    {
        $this->pageViewsWithBaseUrl = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function includeBaseUrl()
    {
        $this->pageViewsWithBaseUrl = true;
        return $this;
    }

    /**
     * @param $trackerKey
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function isValidConfigKey($trackerKey)
    {
        if (!array_key_exists($trackerKey, $this->trackers)) {
            throw new \InvalidArgumentException(sprintf('There is no tracker configuration assigned with the key "%s".', $trackerKey));
        }
        return true;
    }

    /**
     * @param $tracker
     * @param $property
     * @param $value
     *
     * @return $this
     */
    private function setTrackerProperty($tracker, $property, $value)
    {
        if ($this->isValidConfigKey($tracker)) {
            $this->trackers[$tracker][$property] = $value;
        }
        return $this;
    }

    /**
     * @param $tracker
     * @param $property
     *
     * @return mixed
     */
    private function getTrackerProperty($tracker, $property)
    {
        if (!$this->isValidConfigKey($tracker)) {
            return null;
        }

        if (array_key_exists($property, $this->trackers[$tracker])) {
            return $this->trackers[$tracker][$property];
        }

        return null;
    }

    /**
     * @param string $trackerKey
     * @param boolean $allowAnchor
     */
    public function setAllowAnchor($trackerKey, $allowAnchor)
    {
        $this->setTrackerProperty($trackerKey, 'allowAnchor', $allowAnchor);
    }

    /**
     * @param string $trackerKey
     * @return boolean $allowAnchor (default:false)
     */
    public function getAllowAnchor($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'allowAnchor'))) {
            return false;
        }
        return $property;
    }

    /**
     * @param string $trackerKey
     * @param boolean $allowHash
     */
    public function setAllowHash($trackerKey, $allowHash)
    {
        $this->setTrackerProperty($trackerKey, 'allowHash', $allowHash);
    }

    /**
     * @param string $trackerKey
     * @return boolean $allowHash (default:false)
     */
    public function getAllowHash($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'allowHash'))) {
            return false;
        }
        return $property;
    }

    /**
     * @param string $trackerKey
     * @param boolean $allowLinker
     */
    public function setAllowLinker($trackerKey, $allowLinker)
    {
        $this->setTrackerProperty($trackerKey, 'allowLinker', $allowLinker);
    }

    /**
     * @param string $trackerKey
     * @return boolean $allowLinker (default:true)
     */
    public function getAllowLinker($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'allowLinker'))) {
            return true;
        }
        return $property;
    }

    /**
     * @param string $trackerKey
     * @param boolean $includeNamePrefix
     */
    public function setIncludeNamePrefix($trackerKey, $includeNamePrefix)
    {
        $this->setTrackerProperty($trackerKey, 'includeNamePrefix', $includeNamePrefix);
    }

    /**
     * @param string $trackerKey
     * @return boolean $includeNamePrefix (default:true)
     */
    public function getIncludeNamePrefix($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'includeNamePrefix'))) {
            return true;
        }
        return $property;
    }

    /**
     * @param string $trackerKey
     * @param boolean $name
     */
    public function setTrackerName($trackerKey, $name)
    {
        $this->setTrackerProperty($trackerKey, 'name', $name);
    }

    /**
     * @param string $trackerKey
     * @return string $name
     */
    public function getTrackerName($trackerKey)
    {
        return $this->getTrackerProperty($trackerKey, 'name');
    }

    /**
     * @param string $trackerKey
     * @param int $siteSpeedSampleRate
     */
    public function setSiteSpeedSampleRate($trackerKey, $siteSpeedSampleRate)
    {
        $this->setTrackerProperty($trackerKey, 'setSiteSpeedSampleRate', $siteSpeedSampleRate);
    }

    /**
     * @param string $trackerKey
     * @return int $siteSpeedSampleRate (default:null)
     */
    public function getSiteSpeedSampleRate($trackerKey)
    {
        if (null != ($property = $this->getTrackerProperty($trackerKey, 'setSiteSpeedSampleRate'))) {
            return (int) $property;
        }
        return null;
    }

    /**
     * @return string $customPageView
     */
    public function getCustomPageView()
    {
        $customPageView = $this->container->get('session')->get(self::CUSTOM_PAGE_VIEW_KEY);
        $this->container->get('session')->remove(self::CUSTOM_PAGE_VIEW_KEY);
        return $customPageView;
    }

    /**
     * @return boolean $hasCustomPageView
     */
    public function hasCustomPageView()
    {
        return $this->has(self::CUSTOM_PAGE_VIEW_KEY);
    }

    /**
     * @param string $customPageView
     */
    public function setCustomPageView($customPageView)
    {
        $this->container->get('session')->set(self::CUSTOM_PAGE_VIEW_KEY, $customPageView);
    }

    /**
     * @param CustomVariable $customVariable
     */
    public function addCustomVariable(CustomVariable $customVariable)
    {
        $this->customVariables[] = $customVariable;
    }

    /**
     * @return array $customVariables
     */
    public function getCustomVariables()
    {
        return $this->customVariables;
    }

    /**
     * @return boolean $hasCustomVariables
     */
    public function hasCustomVariables()
    {
        if (!empty($this->customVariables)) {
            return true;
        }
        return false;
    }

    /**
     * @param Event $event
     */
    public function enqueueEvent(Event $event)
    {
        $this->add(self::EVENT_QUEUE_KEY, $event);
    }

    /**
     * @return array
     */
    public function getEventQueue()
    {
        return $this->getOnce(self::EVENT_QUEUE_KEY);
    }

    /**
     * @return boolean $hasEventQueue
     */
    public function hasEventQueue()
    {
        return $this->has(self::EVENT_QUEUE_KEY);
    }

    /**
     * @param Item $item
     */
    public function addItem(Item $item)
    {
        $this->add(self::ITEMS_KEY, $item);
    }

    /**
     * @return boolean $hasItems
     */
    public function hasItems()
    {
        return $this->has(self::ITEMS_KEY);
    }

    /**
     * @param Item $item
     * @return boolean $hasItem
     */
    public function hasItem(Item $item)
    {
        if (!$this->hasItems()) {
            return false;
        }
        $items = $this->getItemsFromSession();
        return in_array($item, $items, true);
    }

    /**
     * @param array $items
     */
    public function setItems($items)
    {
        $this->container->get('session')->set(self::ITEMS_KEY, $items);
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->getOnce(self::ITEMS_KEY);
    }

    /**
     * @param string $pageView
     */
    public function enqueuePageView($pageView)
    {
        $this->add(self::PAGE_VIEW_QUEUE_KEY, $pageView);
    }

    /**
     * @return array
     */
    public function getPageViewQueue()
    {
        return $this->getOnce(self::PAGE_VIEW_QUEUE_KEY);
    }

    /**
     * @return boolean $hasPageViewQueue
     */
    public function hasPageViewQueue()
    {
        return $this->has(self::PAGE_VIEW_QUEUE_KEY);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request $request
     */
    public function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * Check and apply base url configuration
     * If a GET param whitelist is declared,
     * Then only allow the whitelist
     *
     * @return string $requestUri
     */
    public function getRequestUri()
    {
        $prefix = '';
        if (isset($this->pageRules['prefix'])) {
            $prefix = $this->pageRules['prefix'];
        }

        $request = $this->getRequest();
        $requestUri = $request->getPathInfo();

        if ($this->forcedPageName !== null) {
            $requestUri = $this->forcedPageName;

        } elseif (isset($this->pageRules['rules']) && count($this->pageRules['rules']) > 0) {
            foreach ($this->pageRules['rules'] as $rule) {
                if (preg_match('~' . $rule['path'] . '~', $requestUri)) {
                    $requestUri = $rule['name'];
                    break;
                }
            }
        }

        $params = array();
        if (isset($this->pageRules['add_params']) && $this->pageRules['add_params'] === true) {
            $params = $request->query->all();
            if (!empty($this->whitelist) && !empty($params)) {
                $whitelist = array_flip($this->whitelist);
                $params = array_intersect_key($params, $whitelist);
            }
        }
        if (count($this->forcedPageParams) > 0) {
            $params = array_merge($params, $this->forcedPageParams);
        }
        if (count($params) > 0) {
            $query = http_build_query($params);

            if (isset($query) && '' != trim($query)) {
                $requestUri .= '?'. $query;
            }
        }

        return $prefix . $requestUri;
    }

    /**
     * @param array $trackers
     * @return array $trackers
     */
    public function getTrackers(array $trackers = array())
    {
        if (!empty($trackers)) {
            $trackers = array();
            foreach ($trackers as $key) {
                if (isset($this->trackers[$key])) {
                    $trackers[$key] = $this->trackers[$key];
                }
            }
            return $trackers;
        } else {
            return $this->trackers;
        }
    }

    /**
     * @return boolean $isTransactionValid
     */
    public function isTransactionValid()
    {
        if (!$this->hasTransaction() || (null === $this->getTransactionFromSession()->getOrderNumber())) {
            return false;
        }
        if ($this->hasItems()) {
            $items = $this->getItemsFromSession();
            foreach ($items as $item) {
                if (!$item->getOrderNumber() || !$item->getSku() || !$item->getPrice() || !$item->getQuantity()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return Transaction $transaction
     */
    public function getTransaction()
    {
        $transaction = $this->getTransactionFromSession();
        $this->container->get('session')->remove(self::TRANSACTION_KEY);
        return $transaction;
    }

    /**
     * @return boolean $hasTransaction
     */
    public function hasTransaction()
    {
        return $this->has(self::TRANSACTION_KEY);
    }

    /**
     * @param Transaction $transaction
     */
    public function setTransaction(Transaction $transaction)
    {
        $this->container->get('session')->set(self::TRANSACTION_KEY, $transaction);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    private function add($key, $value)
    {
        $bucket = $this->container->get('session')->get($key, array());
        $bucket[] = $value;
        $this->container->get('session')->set($key, $bucket);
    }

    /**
     * @param string $key
     * @return boolean $hasKey
     */
    private function has($key)
    {
        $bucket = $this->container->get('session')->get($key, array());
        return !empty($bucket);
    }

    /**
     * @param string $key
     * @return array $value
     */
    private function get($key)
    {
        return $this->container->get('session')->get($key, array());
    }

    /**
     * @param string $key
     * @return array $value
     */
    private function getOnce($key)
    {
        $value = $this->container->get('session')->get($key, array());
        $this->container->get('session')->remove($key);
        return $value;
    }

    /**
     * @return Item[]
     */
    private function getItemsFromSession()
    {
        return $this->get(self::ITEMS_KEY);
    }

    /**
     * @return Transaction $transaction
     */
    private function getTransactionFromSession()
    {
        return $this->container->get('session')->get(self::TRANSACTION_KEY);
    }

    /**
     * Set the {@see $apiKey} property.
     *
     * @param string $apiKey
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Get the {@see $apiKey} property.
     *
     * @return string Returns the <em>$apiKey</em> property.
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set the {@see $clientId} property.
     *
     * @param string $clientId
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * Get the {@see $clientId} property.
     *
     * @return string Returns the <em>$clientId</em> property.
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set the {@see $tableId} property.
     *
     * @param string $tableId
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setTableId($tableId)
    {
        $this->tableId = $tableId;
        return $this;
    }

    /**
     * Get the {@see $tableId} property.
     *
     * @return string Returns the <em>$tableId</em> property.
     */
    public function getTableId()
    {
        return $this->tableId;
    }

    /**
     * Set the {@see $forcedPageName} property.
     *
     * @param string $forcedPageName
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setForcedPageName($forcedPageName)
    {
        $this->forcedPageName = (string) $forcedPageName;
        return $this;
    }

    /**
     * Get the {@see $forcedPageName} property.
     *
     * @return string Returns the <em>$forcedPageName</em> property.
     */
    public function getForcedPageName()
    {
        return $this->forcedPageName;
    }

    /**
     * Set the {@see $options} property.
     *
     * @param array $options
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get the {@see $options} property.
     *
     * @return array Returns the <em>$options</em> property.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Option $option
     * @return $this
     */
    public function addOption(Option $option)
    {
        $this->options[] = $option;
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasOptions()
    {
        if (!empty($this->options)) {
            return true;
        }
        return false;
    }

    /**
     * Set the {@see $forcedPageParams} property.
     *
     * @param array $forcedPageParams
     *
     * @return $this Returns the instance of this or a derived class.
     */
    public function setForcedPageParams($forcedPageParams)
    {
        $this->forcedPageParams = $forcedPageParams;
        return $this;
    }

    /**
     * Get the {@see $forcedPageParams} property.
     *
     * @return array Returns the <em>$forcedPageParams</em> property.
     */
    public function getForcedPageParams()
    {
        return $this->forcedPageParams;
    }
}
