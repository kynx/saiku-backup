<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Backup\Entity;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Kynx\Saiku\Backup\Exception\BadBackupException;
use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Entity\Datasource;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Entity\User;

final class Backup
{
    /**
     * @var DateTimeImmutable
     */
    private $created;
    /**
     * @var File
     */
    private $license;
    /**
     * @var Folder
     */
    private $homes;
    /**
     * @var Acl[]
     */
    private $acls = [];
    /**
     * @var User[]
     */
    private $users = [];
    /**
     * @var Datasource[]
     */
    private $datasources = [];
    /**
     * @var Schema[]
     */
    private $schemas = [];

    public function __construct($backup = null)
    {
        if (is_string($backup)) {
            $backup = json_decode($backup, true);
        }
        if (is_array($backup)) {
            try {
                $this->created = new DateTimeImmutable($backup['created']);
            } catch (\Exception $e) {
                throw new BadBackupException("Cannot parse created date");
            }

            if (isset($backup['license'])) {
                $this->license = new File($backup['license']);
            }

            $this->homes = new Folder($backup['homes']);
            foreach ($backup['acls'] as $path => $acl) {
                $this->addAcl($path, new Acl($acl));
            }
            foreach ($backup['schemas'] as $schema) {
                $this->addSchema(new Schema($schema));
            }
            foreach ($backup['datasources'] as $datasource) {
                $this->addDatasource(new Datasource($datasource));
            }
            foreach ($backup['users'] as $user) {
                $this->addUser(new User($user));
            }
        } else {
            $this->created = new DateTimeImmutable("now", new DateTimeZone("UTC"));
        }
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @return File
     */
    public function getLicense(): ?File
    {
        return $this->license;
    }

    /**
     * @param File $license
     *
     * @return Backup
     */
    public function setLicense(?File $license): Backup
    {
        $this->license = $license;
        return $this;
    }

    /**
     * @return Folder
     */
    public function getHomes(): Folder
    {
        return $this->homes;
    }

    /**
     * @param Folder $homes
     *
     * @return Backup
     */
    public function setHomes(Folder $homes): Backup
    {
        $this->homes = $homes;
        return $this;
    }

    /**
     * @return Acl[]
     */
    public function getAcl(string $path): Acl
    {
        return $this->acls[$path];
    }

    public function addAcl(string $path, Acl $acl)
    {
        $this->acls[$path] = $acl;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @param User $user
     */
    public function addUser(User $user): void
    {
        $this->users[$user->getUsername()] = $user;
    }

    /**
     * @return Datasource[]
     */
    public function getDatasources(): array
    {
        return $this->datasources;
    }

    public function addDatasource(Datasource $datasource): void
    {
        $this->datasources[$datasource->getId()] = $datasource;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function addSchema(Schema $schema): void
    {
        $this->schemas[$schema->getName()] = $schema;
    }

    public function toArray(): array
    {
        $data = [
            'created' => $this->created->format(DateTime::RFC3339),
            'license' => $this->license ? $this->license->toArray() : null,
            'users' => [],
            'schemas' => [],
            'datasources' => [],
            'homes' => $this->homes->toArray(),
            'acls' => []
        ];
        foreach ($this->users as $user) {
            $data['users'][] = $user->toArray();
        }
        foreach ($this->schemas as $schema) {
            $data['schemas'][] = $schema->toArray();
        }
        foreach ($this->datasources as $datasource) {
            $data['datasources'][] = $datasource->toArray();
        }
        foreach ($this->acls as $path => $acl) {
            $data['acls'][$path] = $acl->toArray();
        }

        return $data;
    }

    public function toJson(bool $pretty = false): string
    {
        $options = $pretty ? JSON_PRETTY_PRINT : 0;
        return json_encode($this->toArray(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }
}
