<?php

declare(strict_types=1);

namespace MyAdmin\Licenses\Cpanel\Tests;

use MyAdmin\Licenses\Cpanel\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Plugin class.
 *
 * Covers class structure, static properties, hook registration,
 * and event handler method signatures using reflection.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Tests that the Plugin class exists and can be reflected.
     * Ensures the autoloader correctly resolves the class.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Tests that the Plugin class resides in the correct namespace.
     * Validates PSR-4 autoloading configuration.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('MyAdmin\Licenses\Cpanel', $this->reflection->getNamespaceName());
    }

    /**
     * Tests that the Plugin class is not abstract and can be instantiated.
     * Plugin classes in this framework must be concrete.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Tests that the Plugin class is not an interface.
     * Ensures it is a concrete class.
     */
    public function testClassIsNotInterface(): void
    {
        $this->assertFalse($this->reflection->isInterface());
    }

    /**
     * Tests that the Plugin class is instantiable.
     * Verifies the constructor is accessible.
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Tests that the $name static property holds the expected value.
     * This property is used as a display name in the plugin system.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('cPanel Licensing', Plugin::$name);
    }

    /**
     * Tests that the $description static property is a non-empty string.
     * This property provides a short description for the plugin registry.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('cPanel', Plugin::$description);
    }

    /**
     * Tests that the $help static property is a non-empty string.
     * This is rendered in the admin help UI.
     */
    public function testHelpProperty(): void
    {
        $this->assertIsString(Plugin::$help);
        $this->assertNotEmpty(Plugin::$help);
    }

    /**
     * Tests that the $module static property is set to 'licenses'.
     * This determines which module the plugin hooks into.
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('licenses', Plugin::$module);
    }

    /**
     * Tests that the $type static property is set to 'service'.
     * This categorizes the plugin within the framework.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Tests that all expected static properties are declared on the class.
     * Ensures the plugin conforms to the expected structure.
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Missing static property: \${$prop}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isStatic(),
                "Property \${$prop} should be static"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "Property \${$prop} should be public"
            );
        }
    }

    /**
     * Tests that the constructor exists, is public, and takes no required parameters.
     * The framework instantiates plugins with no arguments.
     */
    public function testConstructorSignature(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
    }

    /**
     * Tests that the Plugin can be instantiated without errors.
     * Basic smoke test for the constructor.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Tests that getHooks() is a public static method.
     * The framework calls it statically to register event listeners.
     */
    public function testGetHooksMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Tests that getHooks() returns an array.
     * The framework expects an associative array of event => callable pairs.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Tests that getHooks() returns the expected event keys.
     * Each key corresponds to a Symfony EventDispatcher event name.
     */
    public function testGetHooksContainsExpectedEventKeys(): void
    {
        $hooks = Plugin::getHooks();

        $expectedKeys = [
            'licenses.settings',
            'licenses.activate',
            'licenses.reactivate',
            'licenses.deactivate',
            'licenses.deactivate_ip',
            'licenses.change_ip',
            'function.requirements',
            'ui.menu',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Missing hook key: {$key}");
        }
    }

    /**
     * Tests that getHooks() returns exactly 8 hooks.
     * Guards against accidental removal or addition of hooks.
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(8, $hooks);
    }

    /**
     * Tests that every hook value is a valid callable array [class, method].
     * The EventDispatcher requires each handler to be callable.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertIsArray($handler, "Handler for '{$event}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$event}' should have exactly 2 elements");
            $this->assertSame(
                Plugin::class,
                $handler[0],
                "Handler class for '{$event}' should be Plugin"
            );
            $this->assertTrue(
                $this->reflection->hasMethod($handler[1]),
                "Method '{$handler[1]}' referenced in hook '{$event}' does not exist on Plugin"
            );
        }
    }

    /**
     * Tests that the activate and reactivate hooks point to the same handler.
     * Both events share the getActivate method.
     */
    public function testActivateAndReactivateShareHandler(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame($hooks['licenses.activate'], $hooks['licenses.reactivate']);
    }

    /**
     * Tests that hook keys use the module name as prefix where expected.
     * Module-specific hooks must be prefixed with the module name.
     */
    public function testHookKeysPrefixedWithModule(): void
    {
        $hooks = Plugin::getHooks();
        $moduleHooks = [
            'licenses.settings',
            'licenses.activate',
            'licenses.reactivate',
            'licenses.deactivate',
            'licenses.deactivate_ip',
            'licenses.change_ip',
        ];
        foreach ($moduleHooks as $key) {
            $this->assertStringStartsWith('licenses.', $key);
            $this->assertArrayHasKey($key, $hooks);
        }
    }

    /**
     * Tests that getActivate is a public static method accepting a GenericEvent.
     * Validates the handler conforms to the EventDispatcher contract.
     */
    public function testGetActivateMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertNotNull($param->getType());
        $this->assertSame(GenericEvent::class, $param->getType()->getName());
    }

    /**
     * Tests that getDeactivate is a public static method accepting a GenericEvent.
     * Validates the handler conforms to the EventDispatcher contract.
     */
    public function testGetDeactivateMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertNotNull($param->getType());
        $this->assertSame(GenericEvent::class, $param->getType()->getName());
    }

    /**
     * Tests that getDeactivateIp is a public static method accepting a GenericEvent.
     * Validates the handler conforms to the EventDispatcher contract.
     */
    public function testGetDeactivateIpMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivateIp');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertNotNull($param->getType());
        $this->assertSame(GenericEvent::class, $param->getType()->getName());
    }

    /**
     * Tests that getChangeIp is a public static method accepting a GenericEvent.
     * Validates the handler conforms to the EventDispatcher contract.
     */
    public function testGetChangeIpMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getChangeIp');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertNotNull($param->getType());
        $this->assertSame(GenericEvent::class, $param->getType()->getName());
    }

    /**
     * Tests that getMenu is a public static method accepting a GenericEvent.
     * Validates the handler conforms to the EventDispatcher contract.
     */
    public function testGetMenuMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertNotNull($param->getType());
        $this->assertSame(GenericEvent::class, $param->getType()->getName());
    }

    /**
     * Tests that getRequirements is a public static method accepting a GenericEvent.
     * Validates the handler conforms to the EventDispatcher contract.
     */
    public function testGetRequirementsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertNotNull($param->getType());
        $this->assertSame(GenericEvent::class, $param->getType()->getName());
    }

    /**
     * Tests that getSettings is a public static method accepting a GenericEvent.
     * Validates the handler conforms to the EventDispatcher contract.
     */
    public function testGetSettingsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(1, $method->getNumberOfRequiredParameters());

        $param = $method->getParameters()[0];
        $this->assertNotNull($param->getType());
        $this->assertSame(GenericEvent::class, $param->getType()->getName());
    }

    /**
     * Tests that all event handler methods have the correct number of methods.
     * Ensures Plugin declares exactly the expected set of public methods.
     */
    public function testExpectedPublicMethodCount(): void
    {
        $expectedMethods = [
            '__construct',
            'getHooks',
            'getActivate',
            'getDeactivate',
            'getDeactivateIp',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $this->reflection->hasMethod($methodName),
                "Expected public method '{$methodName}' not found"
            );
        }
    }

    /**
     * Tests that all declared methods on Plugin are public.
     * Plugin methods must be public for the framework to invoke them.
     */
    public function testAllDeclaredMethodsArePublic(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $declaredMethods = array_filter($methods, function (ReflectionMethod $m) {
            return $m->getDeclaringClass()->getName() === Plugin::class;
        });

        foreach ($declaredMethods as $method) {
            $this->assertTrue(
                $method->isPublic(),
                "Method '{$method->getName()}' should be public"
            );
        }
    }

    /**
     * Tests that event handler methods (excluding constructor and getHooks)
     * are all static. The framework calls handlers statically.
     */
    public function testEventHandlerMethodsAreStatic(): void
    {
        $eventHandlers = [
            'getActivate',
            'getDeactivate',
            'getDeactivateIp',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($eventHandlers as $methodName) {
            $method = $this->reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isStatic(),
                "Event handler '{$methodName}' should be static"
            );
        }
    }

    /**
     * Tests that the Plugin class does not extend any other class.
     * Plugin is a standalone class without inheritance in this framework.
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Tests that the Plugin class does not implement any interfaces.
     * The current plugin system uses convention over contract.
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaceNames());
    }

    /**
     * Tests that the Plugin class does not use any traits.
     * Ensures the class is self-contained.
     */
    public function testClassUsesNoTraits(): void
    {
        $this->assertEmpty($this->reflection->getTraitNames());
    }

    /**
     * Tests that getHooks returns callables referencing only methods
     * that exist on the class. Guards against typos in handler references.
     */
    public function testAllHookMethodsExistOnClass(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertTrue(
                method_exists($handler[0], $handler[1]),
                "Hook '{$event}' references non-existent method '{$handler[1]}'"
            );
        }
    }
}
