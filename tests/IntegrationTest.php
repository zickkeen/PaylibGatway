<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway\Tests;

use PHPUnit\Framework\TestCase;
use zickkeen\PaylibGateway\PaymentGateway;
use zickkeen\PaylibGateway\Config;
use zickkeen\PaylibGateway\Exceptions\PaymentException;

/**
 * Integration Test untuk Payment Gateway Library
 *
 * Test ini memvalidasi integrasi komponen dan fungsionalitas dasar
 */
class IntegrationTest extends TestCase
{
    private array $testConfig;

    protected function setUp(): void
    {
        $this->testConfig = [
            'debug' => true,
            'providers' => [
                'ovo' => [
                    'enabled' => true,
                    'phone' => '08123456789'
                ],
                'gopay' => [
                    'enabled' => true,
                    'phone' => '08123456789',
                    'password' => 'test_password'
                ]
            ]
        ];
    }

    /**
     * Test inisialisasi PaymentGateway
     */
    public function testPaymentGatewayInitialization(): void
    {
        $gateway = new PaymentGateway($this->testConfig);

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        $this->assertContains('ovo', $gateway->getAvailableProviders());
        $this->assertContains('gopay', $gateway->getAvailableProviders());
    }

    /**
     * Test Config class
     */
    public function testConfigClass(): void
    {
        $config = new Config($this->testConfig);

        $this->assertTrue($config->isProviderEnabled('ovo'));
        $this->assertTrue($config->isProviderEnabled('gopay'));
        $this->assertEquals(['ovo', 'gopay'], $config->getEnabledProviders());
    }

    /**
     * Test error handling untuk provider yang tidak ada
     */
    public function testInvalidProviderError(): void
    {
        $gateway = new PaymentGateway($this->testConfig);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Provider \'invalid_provider\' is not configured');

        $gateway->login('invalid_provider', '08123456789');
    }

    /**
     * Test autentikasi check
     */
    public function testAuthenticationCheck(): void
    {
        $gateway = new PaymentGateway($this->testConfig);

        // Sebelum login harus false
        $this->assertFalse($gateway->isAuthenticated('ovo'));
        $this->assertFalse($gateway->isAuthenticated('gopay'));
    }

    /**
     * Test logout untuk provider yang belum login
     */
    public function testLogoutBeforeLogin(): void
    {
        $gateway = new PaymentGateway($this->testConfig);

        // Logout sebelum login harus return true (no-op)
        $result = $gateway->logout('ovo');
        $this->assertTrue($result);
    }

    /**
     * Test Config validation
     */
    public function testConfigValidation(): void
    {
        // Valid config
        $validConfig = new Config(['timeout' => 30]);
        $this->assertEquals(30, $validConfig->get('timeout'));

        // Invalid timeout
        $this->expectException(PaymentException::class);
        new Config(['timeout' => -1]);
    }

    /**
     * Test nested config access
     */
    public function testNestedConfigAccess(): void
    {
        $config = new Config($this->testConfig);

        $this->assertEquals('08123456789', $config->get('providers.ovo.phone'));
        $this->assertTrue($config->get('providers.gopay.enabled'));
        $this->assertEquals('test_password', $config->get('providers.gopay.password'));
    }

    /**
     * Test config file loading
     */
    public function testConfigFileLoading(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'config_test');

        $configData = [
            'debug' => true,
            'providers' => [
                'ovo' => ['enabled' => true, 'phone' => '08111111111']
            ]
        ];

        file_put_contents($tempFile, "<?php return " . var_export($configData, true) . ";");

        try {
            $config = Config::loadFromFile($tempFile);
            $this->assertTrue($config->get('debug'));
            $this->assertEquals('08111111111', $config->get('providers.ovo.phone'));
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test invalid config file
     */
    public function testInvalidConfigFile(): void
    {
        $this->expectException(PaymentException::class);
        Config::loadFromFile('/nonexistent/file.php');
    }

    /**
     * Test JSON config file
     */
    public function testJsonConfigFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'config_test');
        $configData = ['debug' => true, 'timeout' => 60];

        file_put_contents($tempFile . '.json', json_encode($configData));

        try {
            $config = Config::loadFromFile($tempFile . '.json');
            $this->assertTrue($config->get('debug'));
            $this->assertEquals(60, $config->get('timeout'));
        } finally {
            unlink($tempFile . '.json');
        }
    }

    /**
     * Test provider config retrieval
     */
    public function testProviderConfigRetrieval(): void
    {
        $config = new Config($this->testConfig);

        $ovoConfig = $config->getProviderConfig('ovo');
        $this->assertEquals('08123456789', $ovoConfig['phone']);
        $this->assertTrue($ovoConfig['enabled']);

        $gopayConfig = $config->getProviderConfig('gopay');
        $this->assertEquals('test_password', $gopayConfig['password']);
    }

    /**
     * Test invalid provider config retrieval
     */
    public function testInvalidProviderConfigRetrieval(): void
    {
        $config = new Config($this->testConfig);

        $this->expectException(PaymentException::class);
        $config->getProviderConfig('nonexistent');
    }

    /**
     * Test config modification
     */
    public function testConfigModification(): void
    {
        $config = new Config($this->testConfig);

        $config->set('debug', false);
        $this->assertFalse($config->get('debug'));

        $config->set('providers.ovo.phone', '08199999999');
        $this->assertEquals('08199999999', $config->get('providers.ovo.phone'));
    }

    /**
     * Test default config values
     */
    public function testDefaultConfigValues(): void
    {
        $config = new Config();

        $this->assertEquals(30, $config->get('timeout'));
        $this->assertEquals(3, $config->get('retries'));
        $this->assertFalse($config->get('debug'));
        $this->assertTrue($config->get('providers.ovo.enabled'));
        $this->assertTrue($config->get('providers.gopay.enabled'));
    }
}