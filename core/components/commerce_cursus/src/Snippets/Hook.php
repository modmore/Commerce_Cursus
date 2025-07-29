<?php
/**
 * Abstract Hook
 *
 * @package commerce_cursus
 * @subpackage hook
 */

namespace modmore\Commerce_Cursus\Snippets;

use fiHooks;
use LoginHooks;
use modX;
use siHooks;

/**
 * Class Hook
 */
abstract class Hook extends Snippet
{
    /**
     * A reference to the fiHooks instance
     * @var fiHooks|siHooks|LoginHooks $hook
     */
    protected $hook;

    /**
     * The optional property prefix for snippet properties
     * @var string $propertyPrefix
     */
    protected $propertyPrefix = 'commercecursus';

    /**
     * Creates a new Hook instance.
     *
     * @param modX $modx
     * @param fiHooks|siHooks $hook
     * @param array $properties
     */
    public function __construct($modx, $hook, $properties = [])
    {
        $this->hook = &$hook;

        parent::__construct($modx, $properties);
    }
}
