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
use Kynx\Saiku\Client\Saiku;

final class SaikuRestore
{
    use HomesTrait;

    private $client;
    private $includeLicense;

    public function __construct(Saiku $client, bool $includeLicense = false)
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
            $this->client->license()->set($license);
        }
    }

    private function restoreUsers(Backup $backup): void
    {
        $existing = $restored = [];
        foreach ($this->client->user()->getAll() as $user) {
            $existing[$user->getUsername()] = $user;
        }

        foreach ($backup->getUsers() as $user) {
            $userName = $user->getUsername();
            if (isset($existing[$userName])) {
                $this->client->user()->updatePassword($user);
            } else {
                $this->client->user()->create($user);
            }
            $restored[$userName] = $user;
        }

        foreach (array_diff_key($existing, $restored) as $user) {
            $this->client->user()->delete($user);
        }
    }

    private function restoreSchemas(Backup $backup): void
    {
        $existing = $restored = [];
        foreach ($this->client->schema()->getAll() as $schema) {
            $existing[$schema->getName()] = $schema;
        }

        foreach ($backup->getSchemas() as $schema) {
            $schemaName = $schema->getName();
            if (isset($existing[$schemaName])) {
                $this->client->schema()->update($schema);
            } else {
                $this->client->schema()->create($schema);
            }
            $restored[$schemaName] = $schema;
        }

        foreach (array_diff_key($existing, $restored) as $schema) {
            $this->client->schema()->delete($schema);
        }
    }

    private function restoreDatasources(Backup $backup): void
    {
        $existing = $restored = [];
        foreach ($this->client->datasource()->getAll() as $datasource) {
            $existing[$datasource->getId()] = $datasource;
        }

        foreach ($backup->getDatasources() as $datasource) {
            $datasourceId = $datasource->getId();
            if (isset($existing[$datasourceId])) {
                $this->client->datasource()->update($datasource);
            } else {
                $this->client->datasource()->create($datasource);
            }
            $restored[$datasourceId] = $datasource;
        }

        foreach (array_diff_key($existing, $restored) as $datasource) {
            $this->client->datasource()->delete($datasource);
        }
    }

    private function restoreHomes(Backup $backup): void
    {
        $homes = $this->getHomes($this->client->repository()->get());
        $existing = iterator_to_array($this->flattenRepo($homes));
        $restored = [];

        foreach ($this->flattenRepo($backup->getHomes()) as $resource) {
            $path = $resource->getPath();
            // 500 error if you try and POST an existing folder
            if ($resource instanceof File || ! isset($existing[$path])) {
                $this->client->repository()->storeResource($resource);
            }

            $acl = $backup->getAcl($path);
            if ($acl !== null) {
                $this->client->repository()->setAcl($path, $acl);
            }

            $restored[$path] = $resource;
        }

        foreach (array_diff_key($existing, $restored) as $resource) {
            $this->client->repository()->deleteResource($resource);
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
