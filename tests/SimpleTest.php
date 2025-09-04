<?php

declare(strict_types=1);

/**
 * Simple Test untuk Payment Gateway Library
 *
 * Test sederhana tanpa dependency framework testing
 */

require_once __DIR__ . '/autoload.php';

use zickkeen\PaylibGateway\PaymentGateway;
use zickkeen\PaylibGateway\Config;
use zickkeen\PaylibGateway\Exceptions\PaymentException;

class SimpleTest
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "🧪 Running Payment Gateway Library Tests\n";
        echo "=======================================\n\n";

        $this->testPaymentGatewayInitialization();
        $this->testConfigClass();
        $this->testInvalidProviderError();
        $this->testAuthenticationCheck();
        $this->testConfigValidation();
        $this->testNestedConfigAccess();
        $this->testConfigFileLoading();
        $this->testProviderConfigRetrieval();

        echo "\n📊 Test Results:\n";
        echo "==============\n";
        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        echo "📈 Total: " . ($this->passed + $this->failed) . "\n";

        if ($this->failed > 0) {
            echo "\n❌ Failed Tests:\n";
            foreach ($this->results as $test => $result) {
                if (!$result['passed']) {
                    echo "  - {$test}: {$result['message']}\n";
                }
            }
        }

        echo "\n" . ($this->failed === 0 ? "🎉 All tests passed!" : "⚠️  Some tests failed.") . "\n";
    }

    private function assert($condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "✅ PASS\n";
        } else {
            $this->failed++;
            echo "❌ FAIL: {$message}\n";
        }
    }

    private function testPaymentGatewayInitialization(): void
    {
        echo "Testing PaymentGateway initialization... ";

        try {
            $config = [
                'ovo' => ['enabled' => true, 'phone' => '08123456789'],
                'gopay' => ['enabled' => true, 'phone' => '08123456789', 'password' => 'test']
            ];

            $gateway = new PaymentGateway($config);
            $providers = $gateway->getAvailableProviders();

            $this->assert(
                $gateway instanceof PaymentGateway,
                "PaymentGateway should be instance of PaymentGateway class"
            );

            // Debug: print available providers
            echo "\n    Available providers: " . implode(', ', $providers) . "\n    ";

            $this->assert(
                in_array('ovo', $providers),
                "Should have ovo provider"
            );

            $this->assert(
                in_array('gopay', $providers),
                "Should have gopay provider"
            );

        } catch (Exception $e) {
            $this->assert(false, "Exception thrown: " . $e->getMessage());
        }
    }

    private function testConfigClass(): void
    {
        echo "Testing Config class... ";

        try {
            $config = new Config([
                'providers' => [
                    'ovo' => ['enabled' => true],
                    'gopay' => ['enabled' => true]
                ]
            ]);

            $this->assert(
                $config->isProviderEnabled('ovo'),
                "OVO should be enabled"
            );

            $this->assert(
                $config->isProviderEnabled('gopay'),
                "GoPay should be enabled"
            );

            $enabled = $config->getEnabledProviders();
            $this->assert(
                count($enabled) === 2 && in_array('ovo', $enabled) && in_array('gopay', $enabled),
                "Should return both providers as enabled"
            );

        } catch (Exception $e) {
            $this->assert(false, "Exception thrown: " . $e->getMessage());
        }
    }

    private function testInvalidProviderError(): void
    {
        echo "Testing invalid provider error... ";

        try {
            $gateway = new PaymentGateway([]);
            $gateway->login('invalid_provider', '08123456789');
            $this->assert(false, "Should throw exception for invalid provider");

        } catch (PaymentException $e) {
            $this->assert(
                str_contains($e->getMessage(), 'invalid_provider'),
                "Should mention invalid provider in error message"
            );

        } catch (Exception $e) {
            $this->assert(false, "Wrong exception type: " . $e->getMessage());
        }
    }

    private function testAuthenticationCheck(): void
    {
        echo "Testing authentication check... ";

        try {
            $gateway = new PaymentGateway([
                'ovo' => ['enabled' => true, 'phone' => '08123456789'],
                'gopay' => ['enabled' => true, 'phone' => '08123456789', 'password' => 'test']
            ]);

            $this->assert(
                !$gateway->isAuthenticated('ovo'),
                "Should not be authenticated before login"
            );

        } catch (Exception $e) {
            $this->assert(false, "Exception thrown: " . $e->getMessage());
        }
    }

    private function testConfigValidation(): void
    {
        echo "Testing config validation... ";

        try {
            // Valid config
            $config = new Config(['timeout' => 30]);
            $this->assert(
                $config->get('timeout') === 30,
                "Should accept valid timeout"
            );

            // Invalid timeout should throw exception
            try {
                new Config(['timeout' => -1]);
                $this->assert(false, "Should reject negative timeout");
            } catch (PaymentException $e) {
                $this->assert(true, "Correctly rejected invalid timeout");
            }

        } catch (Exception $e) {
            $this->assert(false, "Exception thrown: " . $e->getMessage());
        }
    }

    private function testNestedConfigAccess(): void
    {
        echo "Testing nested config access... ";

        try {
            $config = new Config([
                'providers' => [
                    'ovo' => ['phone' => '08123456789', 'enabled' => true],
                    'gopay' => ['password' => 'test123', 'enabled' => true]
                ]
            ]);

            $this->assert(
                $config->get('providers.ovo.phone') === '08123456789',
                "Should access nested OVO phone config"
            );

            $this->assert(
                $config->get('providers.gopay.password') === 'test123',
                "Should access nested GoPay password config"
            );

            $this->assert(
                $config->get('providers.ovo.enabled') === true,
                "Should access nested OVO enabled config"
            );

        } catch (Exception $e) {
            $this->assert(false, "Exception thrown: " . $e->getMessage());
        }
    }

    private function testConfigFileLoading(): void
    {
        echo "Testing config file loading... ";

        $tempFile = tempnam(sys_get_temp_dir(), 'config_test') . '.php';

        try {
            $configData = [
                'debug' => true,
                'providers' => [
                    'ovo' => ['enabled' => true, 'phone' => '08111111111']
                ]
            ];

            file_put_contents($tempFile, "<?php return " . var_export($configData, true) . ";");

            $config = Config::loadFromFile($tempFile);

            $this->assert(
                $config->get('debug') === true,
                "Should load debug setting from file"
            );

            $this->assert(
                $config->get('providers.ovo.phone') === '08111111111',
                "Should load OVO phone from file"
            );

        } catch (Exception $e) {
            $this->assert(false, "Exception thrown: " . $e->getMessage());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function testProviderConfigRetrieval(): void
    {
        echo "Testing provider config retrieval... ";

        try {
            $config = new Config([
                'providers' => [
                    'ovo' => ['phone' => '08123456789', 'enabled' => true],
                    'gopay' => ['password' => 'test123', 'enabled' => true]
                ]
            ]);

            $ovoConfig = $config->getProviderConfig('ovo');
            $this->assert(
                $ovoConfig['phone'] === '08123456789' && $ovoConfig['enabled'] === true,
                "Should retrieve complete OVO config"
            );

            $gopayConfig = $config->getProviderConfig('gopay');
            $this->assert(
                $gopayConfig['password'] === 'test123' && $gopayConfig['enabled'] === true,
                "Should retrieve complete GoPay config"
            );

        } catch (Exception $e) {
            $this->assert(false, "Exception thrown: " . $e->getMessage());
        }
    }
}

// Jalankan test
$test = new SimpleTest();
$test->run();