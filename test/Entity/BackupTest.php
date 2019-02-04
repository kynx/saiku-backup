<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Backup\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Kynx\Saiku\Backup\Entity\Backup;
use Kynx\Saiku\Backup\Exception\BadBackupException;
use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Entity\Datasource;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Backup\Entity\Backup
 */
class BackupTest extends TestCase
{
    /**
     * @var Backup
     */
    private $backup;

    protected function setUp()
    {
        $this->backup = new Backup();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorPopulatesFromJson()
    {
        $json = file_get_contents(dirname(__DIR__) . '/asset/backup.json');
        $backup = new Backup($json);
        $created = new DateTimeImmutable("2019-02-02T14:35:20+00:00");
        $this->assertEquals($created, $backup->getCreated());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorPopulatesFromArray()
    {
        $json = file_get_contents(dirname(__DIR__) . '/asset/backup.json');
        $array = json_decode($json, true);
        $backup = new Backup($array);
        $created = new DateTimeImmutable("2019-02-02T14:35:20+00:00");
        $this->assertEquals($created, $backup->getCreated());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorSetsCreatedToNow()
    {
        $now = new DateTimeImmutable("now", new DateTimeZone("UTC"));
        $backup = new Backup();
        $created = $backup->getCreated();
        $interval = $now->diff($created, true);
        $this->assertLessThan(1, $interval->s);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorBadCreatedThrowsException()
    {
        $this->expectException(BadBackupException::class);
        new Backup(['created' => 'foo']);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorPopulatesLicense()
    {
        $license = new File(['path' => '/etc/license.lic']);
        $array = [
            'created' => "2019-02-02T14:35:20+00:00",
            'license' => $license->toArray(),
            'homes' => [],
            'acls' => [],
            'users' => [],
            'datasources' => [],
            'schemas' => [],
        ];
        $backup = new Backup($array);
        $this->assertEquals($license, $backup->getLicense());
    }

    /**
     * @covers ::getCreated
     */
    public function testGetCreated()
    {
        $this->assertInstanceOf(DateTimeImmutable::class, $this->backup->getCreated());
    }

    /**
     * @covers ::setLicense
     * @covers ::getLicense
     */
    public function testSetLicense()
    {
        $license = new File(['path' => '/etc/license.lic']);
        $this->backup->setLicense($license);
        $this->assertEquals($license, $this->backup->getLicense());
    }

    /**
     * @covers ::setHomes
     * @covers ::getHomes
     */
    public function testSetHomes()
    {
        $homes = new Folder(['path' => '/homes']);
        $this->backup->setHomes($homes);
        $this->assertEquals($homes, $this->backup->getHomes());
    }

    /**
     * @covers ::addAcl
     * @covers ::getAcl
     */
    public function testAddAcl()
    {
        $acl = new Acl(['type' => [Acl::TYPE_SECURED]]);
        $this->backup->addAcl('/homes/home:admin', $acl);
        $this->assertEquals($acl, $this->backup->getAcl('/homes/home:admin'));
    }

    /**
     * @covers ::addUser
     * @covers ::getUsers
     */
    public function testAddUser()
    {
        $user = new User(['username' => 'slarty']);
        $this->backup->addUser($user);
        $users = $this->backup->getUsers();
        $this->assertCount(1, $users);
        $this->assertEquals($user, $users['slarty']);
    }

    /**
     * @covers ::addDatasource
     * @covers ::getDatasources
     */
    public function testAddDatasource()
    {
        $datasource = new Datasource(['id' => 'idiot']);
        $this->backup->addDatasource($datasource);
        $datasources = $this->backup->getDatasources();
        $this->assertCount(1, $datasources);
        $this->assertEquals($datasource, $datasources['idiot']);
    }

    /**
     * @covers ::addSchema
     * @covers ::getSchemas
     */
    public function testAddSchema()
    {
        $schema = new Schema(['name' => 'conniving']);
        $this->backup->addSchema($schema);
        $schemas = $this->backup->getSchemas();
        $this->assertCount(1, $schemas);
        $this->assertEquals($schema, $schemas['conniving']);
    }
}
