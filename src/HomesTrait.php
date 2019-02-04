<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Backup;

use Kynx\Saiku\Client\Entity\Folder;

trait HomesTrait
{
    protected function getHomes(Folder $repository): ?Folder
    {
        foreach ($repository->getRepoObjects() as $object) {
            if ($object instanceof Folder and $object->getPath() == '/homes') {
                return $object;
            }
        }
        return null;
    }
}
