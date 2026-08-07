<?php

namespace Detain\MyAdminKayako\Tests;

use Detain\MyAdminKayako\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Detain\MyAdminKayako\Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Tests that the Plugin class can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Tests that the Plugin class exists in the expected namespace.
     */
    public function testClassExistsInCorrectNamespace(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
        $this->assertSame('Detain\\MyAdminKayako\\Plugin', $this->reflection->getName());
    }

    /**
     * Tests that the $name static property is set correctly.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('Kayako Plugin', Plugin::$name);
    }

    /**
     * Tests that the $description static property is set correctly.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertSame(
            'Allows handling of Kayako Ticket Support/Helpdesk System',
            Plugin::$description
        );
    }

    /**
     * Tests that the $help static property is an empty string.
     */
    public function testHelpPropertyIsEmpty(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Tests that the $type static property is 'plugin'.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('plugin', Plugin::$type);
    }

    /**
     * Tests that all expected static properties exist.
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expected = ['name', 'description', 'help', 'type'];
        foreach ($expected as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Missing static property: \${$prop}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isStatic(),
                "\${$prop} should be static"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "\${$prop} should be public"
            );
        }
    }

    /**
     * Tests that all static properties are strings.
     */
    public function testStaticPropertiesAreStrings(): void
    {
        $this->assertIsString(Plugin::$name);
        $this->assertIsString(Plugin::$description);
        $this->assertIsString(Plugin::$help);
        $this->assertIsString(Plugin::$type);
    }

    /**
     * Tests that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Tests that getHooks contains the expected hook keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('api.register', $hooks);
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertArrayHasKey('system.settings', $hooks);
    }

    /**
     * Tests that getHooks returns exactly three hooks.
     */
    public function testGetHooksReturnsExactlyThreeHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(3, $hooks);
    }

    /**
     * Tests that each hook value is a callable-style array referencing the Plugin class.
     */
    public function testHookValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertIsArray($handler, "Handler for '{$event}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$event}' should have two elements");
            $this->assertSame(
                Plugin::class,
                $handler[0],
                "Handler for '{$event}' should reference Plugin class"
            );
            $this->assertIsString(
                $handler[1],
                "Handler method name for '{$event}' should be a string"
            );
        }
    }

    /**
     * Tests that hook handler methods exist on the Plugin class.
     */
    public function testHookHandlerMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertTrue(
                $this->reflection->hasMethod($handler[1]),
                "Method {$handler[1]} referenced by hook '{$event}' does not exist"
            );
        }
    }

    /**
     * Tests that hook handler methods are public and static.
     */
    public function testHookHandlerMethodsArePublicStatic(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $method = $this->reflection->getMethod($handler[1]);
            $this->assertTrue(
                $method->isPublic(),
                "{$handler[1]} should be public"
            );
            $this->assertTrue(
                $method->isStatic(),
                "{$handler[1]} should be static"
            );
        }
    }

    /**
     * Tests that hook handler methods accept a GenericEvent parameter.
     */
    public function testHookHandlerMethodsAcceptGenericEvent(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $method = $this->reflection->getMethod($handler[1]);
            $params = $method->getParameters();
            $this->assertGreaterThanOrEqual(
                1,
                count($params),
                "{$handler[1]} should accept at least one parameter"
            );
            $type = $params[0]->getType();
            $this->assertNotNull($type, "{$handler[1]} first parameter should be type-hinted");
            $this->assertSame(
                GenericEvent::class,
                $type->getName(),
                "{$handler[1]} first parameter should be GenericEvent"
            );
        }
    }

    /**
     * Tests that the getMenu method exists even though it is not in the hooks array.
     */
    public function testGetMenuMethodExists(): void
    {
        $this->assertTrue($this->reflection->hasMethod('getMenu'));
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Tests that the getMenu method accepts a GenericEvent parameter.
     */
    public function testGetMenuAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that the constructor has no required parameters.
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $required = array_filter($params, fn($p) => !$p->isOptional());
        $this->assertCount(0, $required);
    }

    /**
     * Tests that the apiRegister method hook maps to the correct event.
     */
    public function testApiRegisterHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame(['Detain\\MyAdminKayako\\Plugin', 'apiRegister'], $hooks['api.register']);
    }

    /**
     * Tests that the getRequirements method hook maps to the correct event.
     */
    public function testGetRequirementsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame(
            ['Detain\\MyAdminKayako\\Plugin', 'getRequirements'],
            $hooks['function.requirements']
        );
    }

    /**
     * Tests that every source path registered by getRequirements() resolves to a file
     * that actually exists inside this package.
     *
     * Nothing in this suite ever exercised getRequirements() before: the existing tests
     * only inspect the hook table and the method signatures, so they never invoked the
     * method and never touched the filesystem. That is why they stayed green for years
     * while this package registered class.Kayako against src/Kayako.php and
     * deactivate_kcare, deactivate_abuse and get_abuse_licenses against src/abuse.inc.php
     * -- neither file has ever existed here. deactivate_kcare in particular was live and
     * harmful: Loader::add_requirement() is last-write-wins and this package is registered
     * after myadmin-cloudlinux-licensing on core's function.requirements hook, so the
     * dangling path overwrote a working registration and core require_once'd a missing
     * file. Asserting the registration table alone cannot catch that; asserting the
     * filesystem can.
     */
    public function testEveryRegisteredRequirementSourceResolvesToAnExistingFile(): void
    {
        $loader = new class () {
            /**
             * @var array<string, string>
             */
            public array $requirements = [];

            /**
             * Mirrors \MyAdmin\Plugins\Loader::add_requirement().
             *
             * @param string $function
             * @param string $source
             * @param bool   $methods
             */
            public function add_requirement($function, $source, $methods = false): void
            {
                $this->requirements[$function] = $source;
            }
        };

        Plugin::getRequirements(new GenericEvent($loader));

        $this->assertNotEmpty(
            $loader->requirements,
            'getRequirements() registered nothing, so this test would be vacuous'
        );

        // Core resolves a requirement source as INCLUDE_ROOT . '/' . $source, and every
        // source this package registers is of the form '/../vendor/<package>/<path>',
        // which lands back inside this package directory.
        $packagePrefix = '/../vendor/detain/myadmin-kayako-support/';
        $packageRoot = dirname(__DIR__);

        foreach ($loader->requirements as $function => $source) {
            $this->assertIsString($source, "Source for '{$function}' should be a string path");
            $this->assertStringStartsWith(
                $packagePrefix,
                $source,
                "Requirement '{$function}' points outside this package: {$source}"
            );
            $this->assertFileExists(
                $packageRoot . '/' . substr($source, strlen($packagePrefix)),
                "Requirement '{$function}' registers '{$source}', which does not exist on disk"
            );
        }
    }

    /**
     * Tests that the getSettings method hook maps to the correct event.
     */
    public function testGetSettingsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame(
            ['Detain\\MyAdminKayako\\Plugin', 'getSettings'],
            $hooks['system.settings']
        );
    }

    /**
     * Tests that the class is not abstract.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Tests that the class is not final.
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Tests that the class does not implement any interfaces.
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaceNames());
    }

    /**
     * Tests that the class has no parent class.
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Tests the expected set of public methods on the Plugin class.
     */
    public function testExpectedPublicMethods(): void
    {
        $expectedMethods = [
            'getHooks',
            'apiRegister',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];
        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Expected public method '{$method}' not found"
            );
        }
    }
}
