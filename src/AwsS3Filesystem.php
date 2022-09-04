<?php
/**
 * @link https://github.com/creocoder/yii2-flysystem
 * @copyright Copyright (c) 2015 Alexander Kochetov
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace creocoder\flysystem;

use Aws\CacheInterface;
use Aws\Credentials\CredentialsInterface;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use yii\base\InvalidConfigException;

/**
 * AwsS3Filesystem
 *
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
class AwsS3Filesystem extends Filesystem
{
    /**
     * @var string
     */
    public $key;
    /**
     * @var string
     */
    public $secret;
    /**
     * @var string
     */
    public $region;
    /**
     * @var string
     */
    public $baseUrl;
    /**
     * @var string
     */
    public $version;
    /**
     * @var string
     */
    public $bucket;
    /**
     * @var string|null
     */
    public $prefix;
    /**
     * Visibility::PUBLIC OR Visibility::PRIVATE
     * @var string
     */
    public $visibility = Visibility::PUBLIC;
    /**
     * @var bool
     */
    public $pathStyleEndpoint = false;
    /**
     * default FinfoMimeTypeDetector
     * @var MimeTypeDetector|null
     */
    public $mimeTypeDetector;
    /**
     * @var array
     */
    public $options = [];
    /**
     * @var bool
     */
    public $streamReads = false;
    /**
     * @var string
     */
    public $endpoint;
    /**
     * @var array|CacheInterface|CredentialsInterface|bool|callable
     */
    public $credentials;
    /**
     * @var S3Client
     */
    protected $client;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->credentials === null) {
            if ($this->key === null) {
                throw new InvalidConfigException('The "key" property must be set.');
            }

            if ($this->secret === null) {
                throw new InvalidConfigException('The "secret" property must be set.');
            }
        }

        if ($this->bucket === null) {
            throw new InvalidConfigException('The "bucket" property must be set.');
        }

        if (!in_array($this->visibility, [Visibility::PUBLIC, Visibility::PRIVATE])) {
            throw new InvalidConfigException('The "visibility" property must be private or public');
        }

        parent::init();
    }

    /**
     * @return AwsS3V3Adapter
     */
    protected function prepareAdapter(): AwsS3V3Adapter
    {
        $config = [];

        if ($this->credentials === null) {
            $config['credentials'] = ['key' => $this->key, 'secret' => $this->secret];
        } else {
            $config['credentials'] = $this->credentials;
        }


        if ($this->pathStyleEndpoint === true) {
            $config['use_path_style_endpoint'] = true;
        }

        if ($this->region !== null) {
            $config['region'] = $this->region;
        }

        if ($this->baseUrl !== null) {
            $config['base_url'] = $this->baseUrl;
        }

        if ($this->endpoint !== null) {
            $config['endpoint'] = $this->endpoint;
        }

        $config['version'] = (($this->version !== null) ? $this->version : 'latest');

        $this->client = new S3Client($config);

        return new AwsS3V3Adapter(
            $this->client,
            $this->bucket,
            $this->prefix,
            new PortableVisibilityConverter($this->visibility),
            $this->mimeTypeDetector,
            $this->options,
            $this->streamReads
        );
    }

    /**
     * @return S3Client
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }
}
