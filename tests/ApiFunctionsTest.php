<?php

namespace Detain\MyAdminKayako\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * Tests for the procedural API functions in src/api.php.
 *
 * These functions depend heavily on global state (Kayako SDK, database, GLOBALS),
 * so we test input validation paths, function signatures, and structural aspects.
 */
class ApiFunctionsTest extends TestCase
{
    /**
     * Ensures the api.php file is loaded once for all tests.
     */
    public static function setUpBeforeClass(): void
    {
        $apiFile = dirname(__DIR__) . '/src/api.php';
        if (!function_exists('openTicket')) {
            // Define stubs for global functions used by api.php only if they don't exist
            if (!function_exists('function_requirements')) {
                /** @return void */
                function function_requirements(string $name): void
                {
                }
            }
            if (!function_exists('myadmin_log')) {
                /** @return void */
                function myadmin_log(string $module, string $level, string $message, int $line, string $file, string $extra = ''): void
                {
                }
            }
            if (!function_exists('api_register')) {
                /** @return void */
                function api_register(string $name, array $params, array $returns, string $desc): void
                {
                }
            }
            if (!function_exists('api_register_array')) {
                /** @return void */
                function api_register_array(string $name, array $fields): void
                {
                }
            }
            if (!function_exists('api_register_array_array')) {
                /** @return void */
                function api_register_array_array(string $name, string $sub): void
                {
                }
            }
            require_once $apiFile;
        }
    }

    // -----------------------------------------------------------------------
    // openTicket tests
    // -----------------------------------------------------------------------

    /**
     * Tests that the openTicket function exists and is callable.
     */
    public function testOpenTicketFunctionExists(): void
    {
        $this->assertTrue(function_exists('openTicket'));
    }

    /**
     * Tests the openTicket function signature has the expected parameter count.
     */
    public function testOpenTicketSignature(): void
    {
        $ref = new ReflectionFunction('openTicket');
        $this->assertCount(6, $ref->getParameters());
    }

    /**
     * Tests the openTicket parameter names match the expected API contract.
     */
    public function testOpenTicketParameterNames(): void
    {
        $ref = new ReflectionFunction('openTicket');
        $names = array_map(fn($p) => $p->getName(), $ref->getParameters());
        $this->assertSame(
            ['user_email', 'user_ip', 'subject', 'product', 'body', 'box_auth_value'],
            $names
        );
    }

    /**
     * Tests that openTicket returns a failure when user_email is empty.
     */
    public function testOpenTicketFailsWithEmptyEmail(): void
    {
        $result = openTicket('', '127.0.0.1', 'Test', 'VPS', 'Body', '');
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
        $this->assertStringContainsString('email', strtolower($result['status_text']));
    }

    /**
     * Tests that openTicket returns a failure when user_ip is empty.
     */
    public function testOpenTicketFailsWithEmptyIp(): void
    {
        $result = openTicket('test@example.com', '', 'Test', 'VPS', 'Body', '');
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
        $this->assertStringContainsString('ip', strtolower($result['status_text']));
    }

    /**
     * Tests that openTicket returns a failure when user_email is invalid.
     */
    public function testOpenTicketFailsWithInvalidEmail(): void
    {
        $result = openTicket('not-an-email', '127.0.0.1', 'Test', 'VPS', 'Body', '');
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
        $this->assertStringContainsString('not valid', strtolower($result['status_text']));
    }

    /**
     * Tests that openTicket result always contains the expected keys.
     */
    public function testOpenTicketResultStructure(): void
    {
        $result = openTicket('', '', '', '', '', '');
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('status_text', $result);
    }

    /**
     * Tests that all openTicket parameters are required (no defaults).
     */
    public function testOpenTicketAllParametersRequired(): void
    {
        $ref = new ReflectionFunction('openTicket');
        foreach ($ref->getParameters() as $param) {
            $this->assertFalse(
                $param->isOptional(),
                "Parameter \${$param->getName()} should be required"
            );
        }
    }

    // -----------------------------------------------------------------------
    // getTicketList tests
    // -----------------------------------------------------------------------

    /**
     * Tests that the getTicketList function exists.
     */
    public function testGetTicketListFunctionExists(): void
    {
        $this->assertTrue(function_exists('getTicketList'));
    }

    /**
     * Tests the getTicketList function signature.
     */
    public function testGetTicketListSignature(): void
    {
        $ref = new ReflectionFunction('getTicketList');
        $this->assertCount(3, $ref->getParameters());
    }

    /**
     * Tests the getTicketList parameter names.
     */
    public function testGetTicketListParameterNames(): void
    {
        $ref = new ReflectionFunction('getTicketList');
        $names = array_map(fn($p) => $p->getName(), $ref->getParameters());
        $this->assertSame(['page', 'limit', 'status'], $names);
    }

    /**
     * Tests that getTicketList has default values for all parameters.
     */
    public function testGetTicketListDefaultValues(): void
    {
        $ref = new ReflectionFunction('getTicketList');
        $params = $ref->getParameters();

        $this->assertTrue($params[0]->isOptional());
        $this->assertSame(1, $params[0]->getDefaultValue());

        $this->assertTrue($params[1]->isOptional());
        $this->assertSame(10, $params[1]->getDefaultValue());

        $this->assertTrue($params[2]->isOptional());
        $this->assertNull($params[2]->getDefaultValue());
    }

    // -----------------------------------------------------------------------
    // viewTicket tests
    // -----------------------------------------------------------------------

    /**
     * Tests that the viewTicket function exists.
     */
    public function testViewTicketFunctionExists(): void
    {
        $this->assertTrue(function_exists('viewTicket'));
    }

    /**
     * Tests the viewTicket function signature.
     */
    public function testViewTicketSignature(): void
    {
        $ref = new ReflectionFunction('viewTicket');
        $this->assertCount(1, $ref->getParameters());
        $this->assertSame('ticketID', $ref->getParameters()[0]->getName());
    }

    /**
     * Tests that viewTicket fails when ticketID is falsy.
     */
    public function testViewTicketFailsWithEmptyId(): void
    {
        $result = viewTicket('');
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
        $this->assertStringContainsString('required', strtolower($result['status_text']));
    }

    /**
     * Tests that viewTicket fails when ticketID is null.
     */
    public function testViewTicketFailsWithNull(): void
    {
        $result = viewTicket(null);
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
    }

    /**
     * Tests that viewTicket fails when ticketID is zero.
     */
    public function testViewTicketFailsWithZero(): void
    {
        $result = viewTicket(0);
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
    }

    /**
     * Tests that viewTicket result structure includes expected keys.
     */
    public function testViewTicketResultStructure(): void
    {
        $result = viewTicket('');
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('status_text', $result);
    }

    // -----------------------------------------------------------------------
    // ticketPost tests
    // -----------------------------------------------------------------------

    /**
     * Tests that the ticketPost function exists.
     */
    public function testTicketPostFunctionExists(): void
    {
        $this->assertTrue(function_exists('ticketPost'));
    }

    /**
     * Tests the ticketPost function signature.
     */
    public function testTicketPostSignature(): void
    {
        $ref = new ReflectionFunction('ticketPost');
        $this->assertCount(2, $ref->getParameters());
    }

    /**
     * Tests the ticketPost parameter names.
     */
    public function testTicketPostParameterNames(): void
    {
        $ref = new ReflectionFunction('ticketPost');
        $names = array_map(fn($p) => $p->getName(), $ref->getParameters());
        $this->assertSame(['ticketID', 'content'], $names);
    }

    /**
     * Tests that ticketPost fails when ticketID is empty.
     */
    public function testTicketPostFailsWithEmptyTicketId(): void
    {
        $result = ticketPost('', 'some content');
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
        $this->assertStringContainsString('ticket', strtolower($result['status_text']));
    }

    /**
     * Tests that ticketPost fails when content is empty.
     */
    public function testTicketPostFailsWithEmptyContent(): void
    {
        $result = ticketPost('TICKET-123', '');
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
        $this->assertStringContainsString('content', strtolower($result['status_text']));
    }

    /**
     * Tests that ticketPost fails when both params are empty (ticketID checked first).
     */
    public function testTicketPostFailsTicketIdFirst(): void
    {
        $result = ticketPost('', '');
        $this->assertIsArray($result);
        $this->assertSame('Failed', $result['status']);
        $this->assertStringContainsString('ticket', strtolower($result['status_text']));
    }

    /**
     * Tests that all ticketPost parameters are required (no defaults).
     */
    public function testTicketPostAllParametersRequired(): void
    {
        $ref = new ReflectionFunction('ticketPost');
        foreach ($ref->getParameters() as $param) {
            $this->assertFalse(
                $param->isOptional(),
                "Parameter \${$param->getName()} should be required"
            );
        }
    }

    // -----------------------------------------------------------------------
    // Cross-function structural tests
    // -----------------------------------------------------------------------

    /**
     * Tests that all four expected API functions are defined.
     */
    public function testAllExpectedFunctionsExist(): void
    {
        $expectedFunctions = ['openTicket', 'getTicketList', 'viewTicket', 'ticketPost'];
        foreach ($expectedFunctions as $fn) {
            $this->assertTrue(function_exists($fn), "Expected function '{$fn}' to exist");
        }
    }

    /**
     * Tests that all API functions return arrays on validation failure.
     */
    public function testAllFunctionsReturnArrays(): void
    {
        $this->assertIsArray(openTicket('', '', '', '', '', ''));
        $this->assertIsArray(viewTicket(''));
        $this->assertIsArray(ticketPost('', ''));
    }

    /**
     * Tests that validation failure results always contain a status key.
     */
    public function testValidationFailureResultsHaveStatusKey(): void
    {
        $results = [
            openTicket('', '', '', '', '', ''),
            viewTicket(''),
            ticketPost('', ''),
        ];
        foreach ($results as $i => $result) {
            $this->assertArrayHasKey('status', $result, "Result {$i} missing 'status' key");
        }
    }

    /**
     * Tests that validation failure results always contain a status_text key.
     */
    public function testValidationFailureResultsHaveStatusTextKey(): void
    {
        $results = [
            openTicket('', '', '', '', '', ''),
            viewTicket(''),
            ticketPost('', ''),
        ];
        foreach ($results as $i => $result) {
            $this->assertArrayHasKey('status_text', $result, "Result {$i} missing 'status_text' key");
        }
    }
}
