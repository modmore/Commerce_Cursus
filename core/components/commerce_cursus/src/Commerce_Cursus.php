<?php
/**
 * Commerce_Cursus
 *
 * Copyright 2023-2024 by modmore <support@modmore.com> & Thomas Jakobi <office@treehillstudio.com>
 *
 * @package agenda
 * @subpackage classfile
 */

namespace modmore\Commerce_Cursus;

use Cursus;
use modLexicon;
use modX;

/**
 * Class Commerce_Cursus
 */
class Commerce_Cursus
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * A reference to the Cursus instance
     * @var Cursus $cursus
     */
    public $cursus;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'commerce_cursus';

    /**
     * The package name
     * @var string $packageName
     */
    public $packageName = 'Commerce_Cursus';

    /**
     * The version
     * @var string $version
     */
    public $version = '1.1.0';

    /**
     * The class options
     * @var array $options
     */
    public $options = [];

    /**
     * Commerce_Cursus constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, $options = [])
    {
        $this->modx =& $modx;
        $this->namespace = $this->getOption('namespace', $options, $this->namespace);

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/' . $this->namespace . '/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/' . $this->namespace . '/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/' . $this->namespace . '/');
        $modxversion = $this->modx->getVersionData();

        // Load some default paths for easier management
        $this->options = array_merge([
            'namespace' => $this->namespace,
            'version' => $this->version,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $assetsUrl . 'connector.php'
        ], $options);

        $lexicon = $this->modx->getService('lexicon', modLexicon::class);
        $lexicon->load($this->namespace . ':default');

        $this->packageName = $this->modx->lexicon('commerce_cursus');

        // Add default options
        $this->options = array_merge($this->options, [
            'debug' => $this->getBooleanOption('debug', [], false),
            'modxversion' => $modxversion['version'],
        ]);
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = [], $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("$this->namespace.$key", $this->modx->config)) {
                $option = $this->modx->getOption("$this->namespace.$key");
            }
        }
        return $option;
    }

    /**
     * Get Boolean Option
     *
     * @param string $key
     * @param array $options
     * @param mixed $default
     * @return bool
     */
    public function getBooleanOption($key, $options = [], $default = null)
    {
        $option = $this->getOption($key, $options, $default);
        return ($option === 'true' || $option === true || $option === '1' || $option === 1);
    }

    /**
     * Get JSON Option
     *
     * @param string $key
     * @param array $options
     * @param mixed $default
     * @return array
     */
    public function getJsonOption($key, $options = [], $default = null)
    {
        $value = json_decode($this->modx->getOption($key, $options, $default ?? ''), true);
        return (is_array($value)) ? $value : [];
    }

    /**
     * Get Bound Option
     *
     * @param string $key
     * @param array $options
     * @param mixed $default
     * @return mixed
     */
    public function getBoundOption($key, $options = [], $default = null)
    {
        $value = trim($this->getOption($key, $options, $default));
        if (strpos($value, '@FILE') === 0) {
            $path = trim(substr($value, strlen('@FILE')));
            // Sanitize to avoid ../ style path traversal
            $path = preg_replace(["/\.*[\/|\\\]/i", "/[\/|\\\]+/i"], ['/', '/'], $path);
            // Include only files inside the MODX base path
            if (strpos($path, MODX_BASE_PATH) === 0 && file_exists($path)) {
                $value = file_get_contents($path);
            }
        } elseif (strpos($value, '@CHUNK') === 0) {
            $name = trim(substr($value, strlen('@CHUNK')));
            $chunk = $this->modx->getObject('modChunk', ['name' => $name]);
            $value = ($chunk) ? $chunk->get('snippet') : '';
        }
        return $value;
    }

    /**
     * Set a local configuration option.
     *
     * @param array $options The options to be set.
     * @param bool $merge Merge the new options with the existing options.
     */
    public function setOptions(array $options = [], $merge = true)
    {
        $this->options = ($merge) ? array_merge($this->options, $options) : $options;
    }

    /**
     * Set a local configuration option.
     *
     * @param string $key The option key to be set.
     * @param mixed $value The value.
     */
    public function setOption(string $key, $value)
    {
        $this->options[$key] = $value;
    }
}
