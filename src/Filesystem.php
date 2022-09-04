<?php
/**
 * @link https://github.com/creocoder/yii2-flysystem
 * @copyright Copyright (c) 2015 Alexander Kochetov
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace creocoder\flysystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem as NativeFilesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Replicate\ReplicateAdapter;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\Cache;

/**
 * Filesystem
 *
 * @method \League\Flysystem\FilesystemInterface addPlugin(\League\Flysystem\PluginInterface $plugin)
 * @method void assertAbsent(string $path)
 * @method void assertPresent(string $path)
 * @method boolean copy(string $path, string $newpath)
 * @method boolean createDirectory(string $dirname, array $config = null)
 * @method boolean delete(string $path)
 * @method boolean deleteDirectory(string $dirname)
 * @method \League\Flysystem\Handler get(string $path, \League\Flysystem\Handler $handler = null)
 * @method Config getConfig()
 * @method string|false mimeType(string $path)
 * @method integer|false fileSize(string $path)
 * @method integer|false lastModified(string $path)
 * @method string|false visibility(string $path)
 * @method boolean fileExists(string $path)
 * @method array listContents(string $directory = '', boolean $recursive = false)
 * @method array listWith(array $keys = [], $directory = '', $recursive = false)
 * @method string|false read(string $path)
 * @method string|false readAndDelete(string $path)
 * @method resource|false readStream(string $path)
 * @method boolean move(string $path, string $newpath)
 * @method boolean setVisibility(string $path, string $visibility)
 * @method boolean writeStream(string $path, resource $resource, array $config = [])
 * @method boolean write(string $path, string $contents, array $config = [])
 *
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
abstract class Filesystem extends Component
{
    /**
     * @var array
     */
    public $config = [];
    /**
     * @var string|null
     */
    public $cache;
    /**
     * @var string
     */
    public $cacheKey = 'flysystem';
    /**
     * @var integer
     */
    public $cacheDuration = 3600;
    /**
     * @var string|null
     */
    public $replica;
    /**
     * Note: filesystem do not have getAdapter() getter anymore.
     * @var FilesystemAdapter
     */
    protected $adapter;
    /**
     * @var NativeFilesystem
     */
    protected $filesystem;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->adapter = $this->prepareAdapter();

        if ($this->cache !== null) {
            /* @var Cache $cache */
            $cache = Yii::$app->get($this->cache);

            if (!$cache instanceof Cache) {
                throw new InvalidConfigException('The "cache" property must be an instance of \yii\caching\Cache subclasses.');
            }

            $this->adapter = new CachedAdapter($this->adapter, new YiiCache($cache, $this->cacheKey, $this->cacheDuration));
        }

        if ($this->replica !== null) {
            /* @var Filesystem $filesystem */
            $filesystem = Yii::$app->get($this->replica);

            if (!$filesystem instanceof Filesystem) {
                throw new InvalidConfigException('The "replica" property must be an instance of \creocoder\flysystem\Filesystem subclasses.');
            }

            $this->adapter = new ReplicateAdapter($this->adapter, $filesystem->getAdapter());
        }

        $this->filesystem = new NativeFilesystem($this->adapter, $this->config);
    }

    /**
     * @return AdapterInterface
     */
    abstract protected function prepareAdapter();

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->filesystem, $method], $parameters);
    }

    /**
     * @return FilesystemAdapter
     */
    public function getAdapter(): FilesystemAdapter
    {
        return $this->adapter;
    }

    /**
     * @return NativeFilesystem
     */
    public function getFilesystem(): NativeFilesystem
    {
        return $this->filesystem;
    }
}
