<?php
/**
 * @copyright (c) 2006-2017 Stickee Technology Limited
 */

namespace Stickee\Cache;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgument;

class InvalidKeyException extends InvalidArgumentException implements SimpleCacheInvalidArgument
{

}
