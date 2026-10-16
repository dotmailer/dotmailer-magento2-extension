<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Config\Backend;

use Dotdigitalgroup\Email\Model\Config\Backend\TrackingHostValidate;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrackingHostValidateTest extends TestCase
{
    /**
     * @var TrackingHostValidate
     */
    private TrackingHostValidate $model;

    protected function setUp(): void
    {
        $context = $this->createStub(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createStub(ManagerInterface::class));

        $this->model = new TrackingHostValidate(
            $context,
            $this->createStub(Registry::class),
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(TypeListInterface::class)
        );
    }

    #[DataProvider('validHostProvider')]
    public function testAcceptsPermittedHosts(?string $value, string $expected): void
    {
        $this->model->setValue($value);

        $this->assertSame($this->model, $this->model->beforeSave());
        $this->assertSame($expected, $this->model->getValue());
    }

    #[DataProvider('invalidHostProvider')]
    public function testRejectsInvalidHosts(string $value): void
    {
        $this->model->setValue($value);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'The tracking host must be ddlnk.net, dotdigital.internal, or a subdomain of either domain.'
        );

        $this->model->beforeSave();
    }

    public static function validHostProvider(): array
    {
        return [
            'default domain' => ['ddlnk.net', 'ddlnk.net'],
            'regional host' => ['r2.ddlnk.net', 'r2.ddlnk.net'],
            'custom regional host' => ['r1-gl.ddlnk.net', 'r1-gl.ddlnk.net'],
            'internal domain' => ['dotdigital.internal', 'dotdigital.internal'],
            'internal host' => ['debug-webbehavior.dotdigital.internal', 'debug-webbehavior.dotdigital.internal'],
            'nested subdomain' => ['tracking.debug.dotdigital.internal', 'tracking.debug.dotdigital.internal'],
            'HTTPS URL' => ['https://r1-gl.ddlnk.net', 'https://r1-gl.ddlnk.net'],
            'HTTP URL' => ['http://debug-tracking.dotdigital.internal', 'http://debug-tracking.dotdigital.internal'],
            'uppercase' => ['HTTPS://R1.DDLNK.NET', 'https://r1.ddlnk.net'],
            'whitespace' => [' r1-gl.ddlnk.net ', 'r1-gl.ddlnk.net'],
            'empty default' => ['', ''],
            'unset default' => [null, ''],
            'whitespace default' => ['   ', '']
        ];
    }

    public static function invalidHostProvider(): array
    {
        return [
            'unrelated domain' => ['anything.com'],
            'unrelated URL' => ['https://anything.com'],
            'external suffix' => ['ddlnk.net.anything.com'],
            'internal external suffix' => ['debug.dotdigital.internal.anything.com'],
            'missing domain boundary' => ['evilddlnk.net'],
            'missing internal domain boundary' => ['evildotdigital.internal'],
            'credentials' => ['https://ddlnk.net@anything.com'],
            'credentials on permitted host' => ['https://anything.com@ddlnk.net'],
            'backslash' => ['https://anything.com\\@ddlnk.net'],
            'path' => ['https://ddlnk.net/DM-12345/ddgtag.js'],
            'trailing slash' => ['https://ddlnk.net/'],
            'query' => ['https://ddlnk.net?host=anything.com'],
            'fragment' => ['https://ddlnk.net#anything.com'],
            'port' => ['https://ddlnk.net:8080'],
            'protocol relative URL' => ['//ddlnk.net'],
            'script scheme' => ['javascript:ddlnk.net'],
            'FTP scheme' => ['ftp://ddlnk.net'],
            'IPv4 address' => ['127.0.0.1'],
            'IPv6 address' => ['[::1]'],
            'empty label' => ['tracking..ddlnk.net'],
            'leading hyphen' => ['-tracking.ddlnk.net'],
            'trailing hyphen' => ['tracking-.ddlnk.net'],
            'oversized label' => [str_repeat('a', 64) . '.ddlnk.net'],
            'embedded newline' => ["https://anything.com\n.ddlnk.net"],
            'encoded separator' => ['anything.com%2f.ddlnk.net']
        ];
    }
}
