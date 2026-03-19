<?php

declare(strict_types=1);

namespace MyAdmin\Licenses\Cpanel\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Static analysis tests for the procedural source files.
 *
 * These files rely heavily on global framework functions (DB, logging, etc.)
 * that are unavailable in a unit test context. We use file_get_contents to
 * parse the source and verify structural expectations: function declarations,
 * required calls, parameter counts, and coding conventions.
 */
class SourceFileAnalysisTest extends TestCase
{
    /**
     * @var string Base path to the src directory.
     */
    private static $srcDir;

    public static function setUpBeforeClass(): void
    {
        self::$srcDir = dirname(__DIR__) . '/src';
    }

    // ---------------------------------------------------------------
    // cpanel.inc.php
    // ---------------------------------------------------------------

    /**
     * Tests that cpanel.inc.php exists and is readable.
     * This is the primary licensing functions file.
     */
    public function testCpanelIncFileExists(): void
    {
        $file = self::$srcDir . '/cpanel.inc.php';
        $this->assertFileExists($file);
        $this->assertFileIsReadable($file);
    }

    /**
     * Tests that cpanel.inc.php declares the activate_cpanel function.
     * This function is called by the Plugin::getActivate event handler.
     */
    public function testCpanelIncDeclaresActivateCpanel(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertMatchesRegularExpression(
            '/function\s+activate_cpanel\s*\(/',
            $content,
            'activate_cpanel function not found in cpanel.inc.php'
        );
    }

    /**
     * Tests that cpanel.inc.php declares the deactivate_cpanel function.
     * This function is called by the Plugin::getDeactivate event handler.
     */
    public function testCpanelIncDeclaresDeactivateCpanel(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertMatchesRegularExpression(
            '/function\s+deactivate_cpanel\s*\(/',
            $content,
            'deactivate_cpanel function not found in cpanel.inc.php'
        );
    }

    /**
     * Tests that cpanel.inc.php declares the verify_cpanel function.
     * This function verifies a license status with the cPanel API.
     */
    public function testCpanelIncDeclaresVerifyCpanel(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertMatchesRegularExpression(
            '/function\s+verify_cpanel\s*\(/',
            $content,
            'verify_cpanel function not found in cpanel.inc.php'
        );
    }

    /**
     * Tests that cpanel.inc.php declares get_cpanel_license_data_by_ip.
     * This function retrieves license details for a given IP.
     */
    public function testCpanelIncDeclaresGetCpanelLicenseDataByIp(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertMatchesRegularExpression(
            '/function\s+get_cpanel_license_data_by_ip\s*\(/',
            $content,
            'get_cpanel_license_data_by_ip function not found in cpanel.inc.php'
        );
    }

    /**
     * Tests that cpanel.inc.php declares get_cpanel_licenses.
     * This function fetches all licenses from the cPanel API.
     */
    public function testCpanelIncDeclaresGetCpanelLicenses(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertMatchesRegularExpression(
            '/function\s+get_cpanel_licenses\s*\(/',
            $content,
            'get_cpanel_licenses function not found in cpanel.inc.php'
        );
    }

    /**
     * Tests that cpanel.inc.php declares get_cpanel_accounts_for_license_ip.
     * This function returns account data for a licensed IP.
     */
    public function testCpanelIncDeclaresGetCpanelAccountsForLicenseIp(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertMatchesRegularExpression(
            '/function\s+get_cpanel_accounts_for_license_ip\s*\(/',
            $content,
            'get_cpanel_accounts_for_license_ip function not found in cpanel.inc.php'
        );
    }

    /**
     * Tests that activate_cpanel accepts exactly 2 parameters ($ipAddress, $package).
     * Ensures the function signature has not changed.
     */
    public function testActivateCpanelParameterCount(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        preg_match('/function\s+activate_cpanel\s*\(([^)]*)\)/', $content, $matches);
        $this->assertNotEmpty($matches, 'Could not parse activate_cpanel signature');
        $params = array_filter(array_map('trim', explode(',', $matches[1])));
        $this->assertCount(2, $params, 'activate_cpanel should accept 2 parameters');
    }

    /**
     * Tests that deactivate_cpanel has a default value for its parameter.
     * The IP parameter defaults to false for admin CLI usage.
     */
    public function testDeactivateCpanelHasDefaultParameter(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        preg_match('/function\s+deactivate_cpanel\s*\(([^)]*)\)/', $content, $matches);
        $this->assertNotEmpty($matches, 'Could not parse deactivate_cpanel signature');
        $this->assertStringContainsString('=', $matches[1], 'deactivate_cpanel parameter should have a default value');
    }

    /**
     * Tests that verify_cpanel accepts exactly 1 parameter ($ipAddress).
     * Ensures the function signature has not changed.
     */
    public function testVerifyCpanelParameterCount(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        preg_match('/function\s+verify_cpanel\s*\(([^)]*)\)/', $content, $matches);
        $this->assertNotEmpty($matches, 'Could not parse verify_cpanel signature');
        $params = array_filter(array_map('trim', explode(',', $matches[1])));
        $this->assertCount(1, $params, 'verify_cpanel should accept 1 parameter');
    }

    /**
     * Tests that cpanel.inc.php uses the Detain\Cpanel\Cpanel class.
     * All API calls go through this vendor dependency.
     */
    public function testCpanelIncUsesCpanelClass(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertStringContainsString(
            '\\Detain\\Cpanel\\Cpanel',
            $content,
            'cpanel.inc.php should reference \\Detain\\Cpanel\\Cpanel'
        );
    }

    /**
     * Tests that cpanel.inc.php calls myadmin_log for auditing.
     * Logging is required for all license operations.
     */
    public function testCpanelIncCallsMyadminLog(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        $this->assertStringContainsString('myadmin_log(', $content);
    }

    /**
     * Tests that the total number of functions in cpanel.inc.php is as expected.
     * Guards against accidentally removing or adding functions.
     */
    public function testCpanelIncFunctionCount(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
        preg_match_all('/^function\s+\w+\s*\(/m', $content, $matches);
        $this->assertCount(6, $matches[0], 'cpanel.inc.php should declare exactly 6 functions');
    }

    // ---------------------------------------------------------------
    // cpanel_kcare_addon.php
    // ---------------------------------------------------------------

    /**
     * Tests that cpanel_kcare_addon.php exists and is readable.
     * This file provides the KernelCare addon activation form and logic.
     */
    public function testCpanelKcareAddonFileExists(): void
    {
        $file = self::$srcDir . '/cpanel_kcare_addon.php';
        $this->assertFileExists($file);
        $this->assertFileIsReadable($file);
    }

    /**
     * Tests that cpanel_kcare_addon.php declares the cpanel_kcare_addon function.
     * This is called via the requirements loader.
     */
    public function testCpanelKcareAddonDeclaresFunction(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_kcare_addon.php');
        $this->assertMatchesRegularExpression(
            '/function\s+cpanel_kcare_addon\s*\(/',
            $content
        );
    }

    /**
     * Tests that cpanel_kcare_addon takes no parameters.
     * It reads input from globals.
     */
    public function testCpanelKcareAddonNoParameters(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_kcare_addon.php');
        preg_match('/function\s+cpanel_kcare_addon\s*\(([^)]*)\)/', $content, $matches);
        $this->assertNotEmpty($matches);
        $this->assertSame('', trim($matches[1]), 'cpanel_kcare_addon should have no parameters');
    }

    /**
     * Tests that cpanel_kcare_addon.php references the Cloudlinux API class.
     * KernelCare licenses are managed through the CloudLinux API.
     */
    public function testCpanelKcareAddonUsesCloudlinux(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_kcare_addon.php');
        $this->assertStringContainsString(
            '\\Detain\\Cloudlinux\\Cloudlinux',
            $content
        );
    }

    /**
     * Tests that cpanel_kcare_addon.php calls get_module_settings.
     * Module settings are needed to query the correct database tables.
     */
    public function testCpanelKcareAddonCallsGetModuleSettings(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_kcare_addon.php');
        $this->assertStringContainsString('get_module_settings(', $content);
    }

    // ---------------------------------------------------------------
    // cpanel_ksplice_addon.php
    // ---------------------------------------------------------------

    /**
     * Tests that cpanel_ksplice_addon.php exists and is readable.
     * This file provides the Ksplice addon activation form and logic.
     */
    public function testCpanelKspliceAddonFileExists(): void
    {
        $file = self::$srcDir . '/cpanel_ksplice_addon.php';
        $this->assertFileExists($file);
        $this->assertFileIsReadable($file);
    }

    /**
     * Tests that cpanel_ksplice_addon.php declares the cpanel_ksplice_addon function.
     * This is called via the requirements loader.
     */
    public function testCpanelKspliceAddonDeclaresFunction(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_ksplice_addon.php');
        $this->assertMatchesRegularExpression(
            '/function\s+cpanel_ksplice_addon\s*\(/',
            $content
        );
    }

    /**
     * Tests that cpanel_ksplice_addon takes no parameters.
     * It reads input from globals.
     */
    public function testCpanelKspliceAddonNoParameters(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_ksplice_addon.php');
        preg_match('/function\s+cpanel_ksplice_addon\s*\(([^)]*)\)/', $content, $matches);
        $this->assertNotEmpty($matches);
        $this->assertSame('', trim($matches[1]), 'cpanel_ksplice_addon should have no parameters');
    }

    /**
     * Tests that cpanel_ksplice_addon.php references the Ksplice class.
     * Ksplice licenses are managed through the Ksplice API wrapper.
     */
    public function testCpanelKspliceAddonUsesKsplice(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_ksplice_addon.php');
        $this->assertStringContainsString(
            '\\Detain\\MyAdminKsplice\\Ksplice',
            $content
        );
    }

    /**
     * Tests that cpanel_ksplice_addon.php calls myadmin_log.
     * License operations must be audited.
     */
    public function testCpanelKspliceAddonCallsMyadminLog(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_ksplice_addon.php');
        $this->assertStringContainsString('myadmin_log(', $content);
    }

    // ---------------------------------------------------------------
    // cpanel_list.php
    // ---------------------------------------------------------------

    /**
     * Tests that cpanel_list.php exists and is readable.
     * This file renders the admin list of all cPanel licenses.
     */
    public function testCpanelListFileExists(): void
    {
        $file = self::$srcDir . '/cpanel_list.php';
        $this->assertFileExists($file);
        $this->assertFileIsReadable($file);
    }

    /**
     * Tests that cpanel_list.php declares the cpanel_list function.
     * This is invoked from the admin menu.
     */
    public function testCpanelListDeclaresFunction(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_list.php');
        $this->assertMatchesRegularExpression(
            '/function\s+cpanel_list\s*\(/',
            $content
        );
    }

    /**
     * Tests that cpanel_list takes no parameters.
     * It reads state from globals.
     */
    public function testCpanelListNoParameters(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_list.php');
        preg_match('/function\s+cpanel_list\s*\(([^)]*)\)/', $content, $matches);
        $this->assertNotEmpty($matches);
        $this->assertSame('', trim($matches[1]), 'cpanel_list should have no parameters');
    }

    /**
     * Tests that cpanel_list.php checks for admin access.
     * Only admins should be able to view license lists.
     */
    public function testCpanelListChecksAdmin(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_list.php');
        $this->assertStringContainsString("ima == 'admin'", $content);
    }

    /**
     * Tests that cpanel_list.php calls get_cpanel_licenses.
     * The license data comes from the cPanel API.
     */
    public function testCpanelListCallsGetCpanelLicenses(): void
    {
        $content = file_get_contents(self::$srcDir . '/cpanel_list.php');
        $this->assertStringContainsString('get_cpanel_licenses(', $content);
    }

    // ---------------------------------------------------------------
    // unbilled_cpanel.php
    // ---------------------------------------------------------------

    /**
     * Tests that unbilled_cpanel.php exists and is readable.
     * This file identifies licenses without corresponding billing records.
     */
    public function testUnbilledCpanelFileExists(): void
    {
        $file = self::$srcDir . '/unbilled_cpanel.php';
        $this->assertFileExists($file);
        $this->assertFileIsReadable($file);
    }

    /**
     * Tests that unbilled_cpanel.php declares the unbilled_cpanel function.
     * This is called from the admin menu.
     */
    public function testUnbilledCpanelDeclaresFunction(): void
    {
        $content = file_get_contents(self::$srcDir . '/unbilled_cpanel.php');
        $this->assertMatchesRegularExpression(
            '/function\s+unbilled_cpanel\s*\(/',
            $content
        );
    }

    /**
     * Tests that unbilled_cpanel takes no parameters.
     * It reads state from globals and the framework.
     */
    public function testUnbilledCpanelNoParameters(): void
    {
        $content = file_get_contents(self::$srcDir . '/unbilled_cpanel.php');
        preg_match('/function\s+unbilled_cpanel\s*\(([^)]*)\)/', $content, $matches);
        $this->assertNotEmpty($matches);
        $this->assertSame('', trim($matches[1]), 'unbilled_cpanel should have no parameters');
    }

    /**
     * Tests that unbilled_cpanel.php checks admin + ACL permissions.
     * This page should only be accessible to admins with the view_service ACL.
     */
    public function testUnbilledCpanelChecksPermissions(): void
    {
        $content = file_get_contents(self::$srcDir . '/unbilled_cpanel.php');
        $this->assertStringContainsString("ima != 'admin'", $content);
        $this->assertStringContainsString("has_acl('view_service')", $content);
    }

    /**
     * Tests that unbilled_cpanel.php references the cPanel API class.
     * License data is fetched from the API for comparison.
     */
    public function testUnbilledCpanelUsesCpanelClass(): void
    {
        $content = file_get_contents(self::$srcDir . '/unbilled_cpanel.php');
        $this->assertStringContainsString('\\Detain\\Cpanel\\Cpanel', $content);
    }

    /**
     * Tests that unbilled_cpanel.php uses fetchLicenses.
     * The full license list is needed to cross-reference billing.
     */
    public function testUnbilledCpanelCallsFetchLicenses(): void
    {
        $content = file_get_contents(self::$srcDir . '/unbilled_cpanel.php');
        $this->assertStringContainsString('fetchLicenses(', $content);
    }

    // ---------------------------------------------------------------
    // Plugin.php
    // ---------------------------------------------------------------

    /**
     * Tests that Plugin.php exists and is readable.
     * This is the main class file for the package.
     */
    public function testPluginFileExists(): void
    {
        $file = self::$srcDir . '/Plugin.php';
        $this->assertFileExists($file);
        $this->assertFileIsReadable($file);
    }

    /**
     * Tests that Plugin.php declares the correct namespace.
     * PSR-4 autoloading depends on the namespace matching the directory structure.
     */
    public function testPluginFileHasCorrectNamespace(): void
    {
        $content = file_get_contents(self::$srcDir . '/Plugin.php');
        $this->assertStringContainsString(
            'namespace MyAdmin\\Licenses\\Cpanel;',
            $content
        );
    }

    /**
     * Tests that Plugin.php uses GenericEvent from Symfony.
     * All event handlers accept GenericEvent as their parameter type.
     */
    public function testPluginFileImportsGenericEvent(): void
    {
        $content = file_get_contents(self::$srcDir . '/Plugin.php');
        $this->assertStringContainsString(
            'use Symfony\\Component\\EventDispatcher\\GenericEvent;',
            $content
        );
    }

    // ---------------------------------------------------------------
    // Cross-file consistency
    // ---------------------------------------------------------------

    /**
     * Tests that all source files start with a PHP open tag.
     * Ensures no BOM or whitespace precedes the tag.
     */
    public function testAllSourceFilesStartWithPhpTag(): void
    {
        $files = glob(self::$srcDir . '/*.php');
        $this->assertNotEmpty($files, 'No PHP files found in src/');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringStartsWith(
                '<?php',
                $content,
                basename($file) . ' should start with <?php'
            );
        }
    }

    /**
     * Tests that no source file contains a closing PHP tag.
     * PSR-12 recommends omitting the closing tag to prevent header issues.
     */
    public function testNoSourceFileHasClosingPhpTag(): void
    {
        $files = glob(self::$srcDir . '/*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            // Remove the opening tag to avoid false match
            $rest = substr($content, 5);
            $this->assertStringNotContainsString(
                '?>',
                $rest,
                basename($file) . ' should not contain a closing ?> tag'
            );
        }
    }

    /**
     * Tests that all source files are valid PHP syntax.
     * Uses php -l (lint) to check each file.
     */
    public function testAllSourceFilesHaveValidSyntax(): void
    {
        $files = glob(self::$srcDir . '/*.php');
        foreach ($files as $file) {
            $output = [];
            $returnCode = 0;
            $escapedFile = escapeshellarg($file);
            // Using proc_open for safer process execution
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $process = proc_open('php -l ' . $escapedFile, $descriptors, $pipes);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $returnCode = proc_close($process);
            }
            $this->assertSame(
                0,
                $returnCode,
                basename($file) . ' has a syntax error: ' . ($stdout ?? '')
            );
        }
    }

    /**
     * Tests the exact list of source files in the src directory.
     * Prevents unexpected file additions or removals.
     */
    public function testSourceFileList(): void
    {
        $files = glob(self::$srcDir . '/*.php');
        $basenames = array_map('basename', $files);
        sort($basenames);

        $expected = [
            'Plugin.php',
            'cpanel.inc.php',
            'cpanel_kcare_addon.php',
            'cpanel_ksplice_addon.php',
            'cpanel_list.php',
            'unbilled_cpanel.php',
        ];
        sort($expected);

        $this->assertSame($expected, $basenames);
    }
}
