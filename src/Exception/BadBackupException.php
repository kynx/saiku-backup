<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Backup\Exception;

use Kynx\Saiku\Client\Exception\SaikuExceptionInterface;
use RuntimeException;

final class BadBackupException extends RuntimeException implements SaikuExceptionInterface
{
}
