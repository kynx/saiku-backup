<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Backup;

use Kynx\Saiku\Backup\Entity\Backup;
use Kynx\Saiku\Client\Entity\AbstractNode;
use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Saiku;

final class SaikuBackup
{
    use HomesTrait;

    private $client;
    private $includeLicense;

    public function __construct(Saiku $client, bool $includeLicense = false)
    {
        $this->client = $client;
        $this->includeLicense = $includeLicense;
    }

    public function backup(): Backup
    {
        $backup = new Backup();

        $repository = $this->client->repository()->get(null, true);

        if ($this->includeLicense) {
            $backup->setLicense($this->getLicense($repository));
        }

        $homes = $this->getHomes($repository);
        if ($homes instanceof Folder) {
            $backup->setHomes($homes);
            foreach ($this->getAcls($homes) as $path => $acl) {
                $backup->addAcl($path, $acl);
            }
        }

        foreach ($this->client->user()->getAll() as $user) {
            $backup->addUser($user);
        }

        foreach ($this->client->schema()->getAll() as $schema) {
            $schema->setXml($this->client->repository()->getResource($schema->getPath()));
            $backup->addSchema($schema);
        }

        foreach ($this->client->datasource()->getAll() as $datasource) {
            $backup->addDatasource($datasource);
        }

        return $backup;
    }

    /**
     * @param AbstractNode $node
     *
     * @return \Generator|Acl[]
     */
    private function getAcls(AbstractNode $node)
    {
        $path = $node->getPath();
        $acl = $this->client->repository()->getAcl($node->getPath());
        if ($acl !== null) {
            yield $path => $acl;
        }

        if ($node instanceof Folder) {
            foreach ($node->getRepoObjects() as $child) {
                foreach ($this->getAcls($child) as $path => $acl) {
                    if ($acl !== null) {
                        yield $path => $acl;
                    }
                }
            }
        }
    }

    private function getLicense(Folder $repository): ?File
    {
        foreach ($repository->getRepoObjects() as $object) {
            if ($object instanceof File && $object->getFileType() == File::FILETYPE_LICENSE) {
                return $object;
            }
            if ($object instanceof Folder) {
                $license = $this->getLicense($object);
                if ($license !== null) {
                    return $license;
                }
            }
        }
        return null;
    }
}
