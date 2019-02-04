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
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\SaikuClient;

final class SaikuRestore
{
    use HomesTrait;

    private $client;
    private $includeLicense;

    public function __construct(SaikuClient $client, bool $includeLicense = false)
    {
        $this->client = $client;
        $this->includeLicense = $includeLicense;
    }

    public function restore(Backup $backup): void
    {
        if ($this->includeLicense) {
            $this->restoreLicense($backup);
        }
        $this->restoreUsers($backup);
        $this->restoreSchemas($backup);
        $this->restoreDatasources($backup);
        $this->restoreHomes($backup);
    }

    private function restoreLicense(Backup $backup): void
    {
        $license = $backup->getLicense();
        if ($license !== null) {
            $this->client->storeResource($license);
        }
    }

    private function restoreUsers(Backup $backup): void
    {
        $existing = $restored = [];
        foreach ($this->client->getUsers() as $user) {
            $existing[$user->getUsername()] = $user;
        }

        foreach ($backup->getUsers() as $user) {
            $userName = $user->getUsername();
            if (isset($existing[$userName])) {
                $this->client->updateUserAndPassword($user);
            } else {
                $this->client->createUser($user);
            }
            $restored[$userName] = $user;
        }

        foreach (array_diff_key($existing, $restored) as $user) {
            $this->client->deleteUser($user);
        }
    }

    private function restoreSchemas(Backup $backup): void
    {
        $existing = $restored = [];
        foreach ($this->client->getSchemas() as $schema) {
            $existing[$schema->getName()] = $schema;
        }

        foreach ($backup->getSchemas() as $schema) {
            $schemaName = $schema->getName();
            if (isset($existing[$schemaName])) {
                $this->client->updateSchema($schema);
            } else {
                $this->client->createSchema($schema);
            }
            $restored[$schemaName] = $schema;
        }

        foreach (array_diff_key($existing, $restored) as $schema) {
            $this->client->deleteSchema($schema);
        }
    }

    private function restoreDatasources(Backup $backup): void
    {
        $existing = $restored = [];
        foreach ($this->client->getDatasources() as $datasource) {
            $existing[$datasource->getId()] = $datasource;
        }

        foreach ($backup->getDatasources() as $datasource) {
            $datasourceId = $datasource->getId();
            if (isset($existing[$datasourceId])) {
                $this->client->updateDatasource($datasource);
            } else {
                $this->client->createDatasource($datasource);
            }
            $restored[$datasourceId] = $datasource;
        }

        foreach (array_diff_key($existing, $restored) as $datasource) {
            $this->client->deleteDatasource($datasource);
        }
    }

    private function restoreHomes(Backup $backup): void
    {
        $homes = $this->getHomes($this->client->getRespository());
        $existing = iterator_to_array($this->flattenRepo($homes));
        $restored = [];

        foreach ($this->flattenRepo($backup->getHomes()) as $resource) {
            $path = $resource->getPath();
            // 500 error if you try and POST an existing folder
            if ($resource instanceof File || ! isset($existing[$path])) {
                $this->client->storeResource($resource);
            }

            $acl = $backup->getAcl($path);
            if ($acl !== null) {
                $this->client->setAcl($path, $acl);
            }

            $restored[$path] = $resource;
        }

        foreach (array_diff_key($existing, $restored) as $resource) {
            $this->client->deleteResource($resource);
        }
    }

    /**
     * @param Folder $folder
     *
     * @return \Generator|AbstractNode[]
     */
    private function flattenRepo(Folder $folder)
    {
        foreach ($folder->getRepoObjects() as $object) {
            yield $object->getPath() => $object;

            if ($object instanceof Folder) {
                foreach ($this->flattenRepo($object) as $path => $child) {
                    yield $path => $child;
                }
            }
        }
    }
}
