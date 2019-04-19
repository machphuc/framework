<?php

namespace Themosis\Core\Cache;

use Illuminate\Contracts\Cache\Repository;

class WordPressCacheWrapper
{
    /**
     * Cache store instance.
     *
     * @var Repository
     */
    private $store;

    /**
     * Global cache groups.
     *
     * @var array
     */
    private $globalGroups = [];

    /**
     * Global non persistent groups.
     *
     * @var array
     */
    private $nonPersistentGroups = [];

    /**
     * Blog ID prefix followed by a colon ":"
     *
     * @var string "$id:"
     */
    private $blogPrefix;

    /**
     * Is is a multisite installation.
     *
     * @var bool
     */
    private $multisite;

    public function __construct(Repository $store, bool $multisite = false, string $blogPrefix = '')
    {
        $this->store = $store;
        $this->multisite = $multisite;
        $this->blogPrefix = $blogPrefix;
    }

    /**
     * Sets the list of global cache groups.
     *
     * @param array $groups
     */
    public function addGlobalGroups(array $groups)
    {
        $groups = array_fill_keys($groups, true);

        $this->globalGroups = array_merge(
            $this->globalGroups,
            $groups
        );
    }

    /**
     * Adds a group or set of groups to the list of non-persistent groups.
     *
     * @param array $groups
     */
    public function addNonPersistentGroups(array $groups)
    {
        $this->nonPersistentGroups = array_unique(
            array_merge(
                $this->nonPersistentGroups,
                $groups
            )
        );
    }

    /**
     * Switches the internal blog prefix ID.
     *
     * @param int $blog_id
     */
    public function switchToBlog(int $blog_id)
    {
        $this->blogPrefix = $this->multisite ? $blog_id.':' : '';
    }

    /**
     * Format key name based on a key and a group.
     * WordPress cache keys are stored using a nomenclature
     * in their name: groupname_keyname
     *
     * @param string $key
     * @param string $group
     *
     * @return string
     */
    private function formatKeyName(string $key, string $group)
    {
        return sprintf('%s_%s', $group, $key);
    }

    /**
     * Retrieves the cache contents, it it exists.
     *
     * @param string|int $key
     * @param string     $group
     * @param bool       $force
     * @param null       $found
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return bool|mixed False on failure. Cache value on success.
     */
    public function get($key, $group = 'default', $force = false, &$found = null)
    {
        if (empty($group)) {
            $group = 'default';
        }

        if ($this->multisite && ! isset($this->globalGroups[$group])) {
            $key = $this->blogPrefix.$key;
        }

        $key = $this->formatKeyName($key, $group);

        if ($this->store->has($key)) {
            $found = true;

            return $this->store->get($key);
        }

        return false;
    }

    /**
     * Adds data to the cache if the cache key doesn't already exist.
     *
     * @param string|int $key
     * @param mixed      $data
     * @param string     $group
     * @param int        $expire
     *
     * @return bool
     */
    public function add($key, $data, $group = 'default', $expire = 0): bool
    {
        return false;
    }
}