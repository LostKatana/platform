<?php declare(strict_types=1);

namespace Shopware\Core\Content\Test\Media\Api;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Test\Media\MediaFixtures;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Shopware\Core\PlatformRequest;

class MediaFolderControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour, MediaFixtures;

    /**
     * @var RepositoryInterface
     */
    private $mediaFolderRepo;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var RepositoryInterface
     */
    private $mediaFolderConfigRepo;

    protected function setUp()
    {
        $this->mediaFolderRepo = $this->getContainer()->get('media_folder.repository');
        $this->mediaFolderConfigRepo = $this->getContainer()->get('media_folder_configuration.repository');

        $this->context = Context::createDefaultContext();
    }

    public function testDissolveWithNonExistingFolder(): void
    {
        $url = sprintf(
            '/api/v%s/_action/media-folder/%s/dissolve',
            PlatformRequest::API_VERSION,
            Uuid::uuid4()->getHex()
        );

        $this->getClient()->request(
            'POST',
            $url
        );
        $response = $this->getClient()->getResponse();
        $responseData = json_decode($response->getContent(), true);

        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('MEDIA_FOLDER_NOT_FOUND_EXCEPTION', $responseData['errors'][0]['code']);
    }

    public function testDissolve(): void
    {
        $folderId = Uuid::uuid4()->getHex();
        $configId = Uuid::uuid4()->getHex();
        $this->mediaFolderRepo->create([
            [
                'id' => $folderId,
                'name' => 'test',
                'useParentConfiguration' => false,
                'configuration' => [
                    'id' => $configId,
                    'createThumbnails' => true,
                    'keepAspectRatio' => true,
                    'thumbnailQuality' => 80,
                ],
            ],
        ], $this->context);

        $url = sprintf(
            '/api/v%s/_action/media-folder/%s/dissolve',
            PlatformRequest::API_VERSION,
            $folderId
        );

        $this->getClient()->request(
            'POST',
            $url
        );
        $response = $this->getClient()->getResponse();

        static::assertEquals(200, $response->getStatusCode(), $response->getContent());
        static::assertEmpty($response->getContent());

        $folder = $this->mediaFolderRepo->read(new ReadCriteria([$folderId]), $this->context)->get($folderId);
        static::assertNull($folder);

        $config = $this->mediaFolderConfigRepo->read(new ReadCriteria([$configId]), $this->context)->get($configId);
        static::assertNull($config);
    }

    public function testMoveWithNonExistingTargetFolder(): void
    {
        $folderId = Uuid::uuid4()->getHex();
        $this->mediaFolderRepo->create([
            [
                'id' => $folderId,
                'name' => 'test',
                'useParentConfiguration' => false,
                'configuration' => [
                    'createThumbnails' => true,
                    'keepAspectRatio' => true,
                    'thumbnailQuality' => 80,
                ],
            ],
        ], $this->context);

        $url = sprintf(
            '/api/v%s/_action/media-folder/%s/move/%s',
            PlatformRequest::API_VERSION,
            $folderId,
            Uuid::uuid4()->getHex()
        );

        $this->getClient()->request(
            'POST',
            $url
        );
        $response = $this->getClient()->getResponse();
        $responseData = json_decode($response->getContent(), true);

        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('MEDIA_FOLDER_NOT_FOUND_EXCEPTION', $responseData['errors'][0]['code']);
    }

    public function testMoveWithNonExistingFolderToMove(): void
    {
        $folderId = Uuid::uuid4()->getHex();
        $this->mediaFolderRepo->create([
            [
                'id' => $folderId,
                'name' => 'test',
                'useParentConfiguration' => false,
                'configuration' => [
                    'createThumbnails' => true,
                    'keepAspectRatio' => true,
                    'thumbnailQuality' => 80,
                ],
            ],
        ], $this->context);

        $url = sprintf(
            '/api/v%s/_action/media-folder/%s/move/%s',
            PlatformRequest::API_VERSION,
            Uuid::uuid4()->getHex(),
            $folderId
        );

        $this->getClient()->request(
            'POST',
            $url
        );
        $response = $this->getClient()->getResponse();
        $responseData = json_decode($response->getContent(), true);

        static::assertEquals(404, $response->getStatusCode());
        static::assertEquals('MEDIA_FOLDER_NOT_FOUND_EXCEPTION', $responseData['errors'][0]['code']);
    }

    public function testMove(): void
    {
        $folderId = Uuid::uuid4()->getHex();
        $targetId = Uuid::uuid4()->getHex();
        $this->mediaFolderRepo->create([
            [
                'id' => $folderId,
                'name' => 'test',
                'useParentConfiguration' => false,
                'configuration' => [
                    'createThumbnails' => true,
                    'keepAspectRatio' => true,
                    'thumbnailQuality' => 80,
                ],
            ],
            [
                'id' => $targetId,
                'name' => 'target',
                'useParentConfiguration' => false,
                'configuration' => [
                    'createThumbnails' => true,
                    'keepAspectRatio' => true,
                    'thumbnailQuality' => 80,
                ],
            ],
        ], $this->context);

        $url = sprintf(
            '/api/v%s/_action/media-folder/%s/move/%s',
            PlatformRequest::API_VERSION,
            $folderId,
            $targetId
        );

        $this->getClient()->request(
            'POST',
            $url
        );
        $response = $this->getClient()->getResponse();

        static::assertEquals(200, $response->getStatusCode(), $response->getContent());
        static::assertEmpty($response->getContent());

        $folder = $this->mediaFolderRepo->read(new ReadCriteria([$folderId]), $this->context)->get($folderId);
        static::assertEquals($targetId, $folder->getParentId());
    }

    public function testMoveToRoot(): void
    {
        $folderId = Uuid::uuid4()->getHex();
        $parentId = Uuid::uuid4()->getHex();
        $this->mediaFolderRepo->create([
            [
                'id' => $parentId,
                'name' => 'test',
                'useParentConfiguration' => false,
                'configuration' => [
                    'createThumbnails' => true,
                    'keepAspectRatio' => true,
                    'thumbnailQuality' => 80,
                ],
            ],
            [
                'id' => $folderId,
                'parentId' => $parentId,
                'name' => 'target',
                'useParentConfiguration' => false,
                'configuration' => [
                    'createThumbnails' => true,
                    'keepAspectRatio' => true,
                    'thumbnailQuality' => 80,
                ],
            ],
        ], $this->context);

        $url = sprintf(
            '/api/v%s/_action/media-folder/%s/move',
            PlatformRequest::API_VERSION,
            $folderId
        );

        $this->getClient()->request(
            'POST',
            $url
        );
        $response = $this->getClient()->getResponse();

        static::assertEquals(200, $response->getStatusCode(), $response->getContent());
        static::assertEmpty($response->getContent());

        $folder = $this->mediaFolderRepo->read(new ReadCriteria([$folderId]), $this->context)->get($folderId);
        static::assertNull($folder->getParentId());
    }
}
