<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\MediaBundle\Tests\Unit\Doctrine\Phpcr;

use Doctrine\Bundle\PHPCRBundle\ManagerRegistry;
use Doctrine\ODM\PHPCR\DocumentManager;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\Directory;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\Media;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\MediaManager;

class MediaManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $registryMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dmMock;
    /**
     * @var Directory
     */
    private $testRoot;

    public function setUp(): void
    {
        $this->registryMock = $this->createMock(ManagerRegistry::class);
        $this->dmMock = $this->createMock(DocumentManager::class);
        $this->testRoot = new Directory();
        $this->testRoot->setId('/test/media');
    }

    private function getMediaManager(): MediaManager
    {
        return new MediaManager($this->registryMock, 'themanager', '/test/media');
    }

    public function testGetPath(): void
    {
        $media = new Media();
        $media->setId('/test/media/mymedia');
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('/test/media/mymedia', $mediaManager->getPath($media));
    }

    public function testGetUrlSafePath(): void
    {
        $media = new Media();
        $media->setId('/test/media/mymedia');
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('test/media/mymedia', $mediaManager->getUrlSafePath($media));
    }

    public function setDefaultsProvider()
    {
        return array(
            array('mymedia', 'mymedia'),
            array('mymedia', null, false, '/test/media/mymedia'),
            array(null, 'mymedia', true),
        );
    }

    /**
     * @dataProvider setDefaultsProvider
     */
    public function testSetDefaults($expectedName = null, $name = null, $nameExists = false, $id = null): void
    {
        $returnMediaExists = $nameExists ? new Media() : null;
        $rootPath = '/test/media/file';
        $managerName = 'anothermanager';

        $this->registryMock->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo($managerName))
            ->will($this->returnValue($this->dmMock))
        ;
        $this->dmMock->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap(array(
                array('Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\Media', $rootPath.'/'.$name, $returnMediaExists),
                array(null, $rootPath, $this->testRoot),
            )))
        ;

        $media = new Media();
        $media->setId($id);
        $media->setName($name);

        $mediaManager = $this->getMediaManager();
        $mediaManager->setRootPath($rootPath);
        $mediaManager->setManagerName($managerName);
        $mediaManager->setDefaults($media);

        $this->assertEquals($this->testRoot, $media->getParent());
        if ($expectedName) {
            $this->assertEquals($expectedName, $media->getName());
        } else {
            $this->assertNotEquals($name, $media->getName());
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetDefaultsException(): void
    {
        $media = new Media();

        $mediaManager = $this->getMediaManager();
        $mediaManager->setDefaults($media);
    }

    public function mapPathToIdProvider()
    {
        return array(
            array('/test/media/mymedia', null),
            array('/test/media/mymedia', '/test/media'),
        );
    }

    /**
     * @dataProvider mapPathToIdProvider
     */
    public function testMapPathToId($path, $rootPath): void
    {
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('/test/media/mymedia', $mediaManager->mapPathToId($path, $rootPath));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testMapPathToIdException(): void
    {
        $mediaManager = $this->getMediaManager();

        $mediaManager->mapPathToId('/test/media/mymedia', '/out/of/bound');
    }

    public function mapUrlSafePathToIdProvider()
    {
        return array(
            array('test/media/mymedia', null),
            array('test/media/mymedia', '/test/media'),
        );
    }

    /**
     * @dataProvider mapUrlSafePathToIdProvider
     */
    public function testMapUrlSafePathToId($path, $rootPath): void
    {
        $mediaManager = $this->getMediaManager();

        $this->assertEquals('/test/media/mymedia', $mediaManager->mapPathToId($path, $rootPath));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testMapUrlSafePathToIdException(): void
    {
        $mediaManager = $this->getMediaManager();

        $mediaManager->mapUrlSafePathToId('test/media/mymedia', '/out/of/bound');
    }
}
