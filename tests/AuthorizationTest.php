<?php

declare(strict_types=1);

namespace MyAdmin\Licenses\Cpanel\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Executable authorization tests for the procedural cPanel license pages.
 *
 * These replace the source-text greps that used to live in SourceFileAnalysisTest
 * ("the file contains ima == 'admin'" / "ima != 'admin'"). Those greps went red the
 * moment the pages migrated from $GLOBALS['tf']->ima to \MyAdmin\App::ima() even
 * though the gates were still fully intact, and they would have stayed GREEN if
 * somebody had left the substring in a comment while deleting the real check.
 *
 * The tests here instead run the pages against framework stubs and assert on what
 * they observably do: whether privileged data is fetched, whether the request is
 * refused, and whether the page ever reaches its database.
 */
class AuthorizationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/Stubs.php';
        require_once dirname(__DIR__).'/src/cpanel_list.php';
        require_once dirname(__DIR__).'/src/unbilled_cpanel.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        FrameworkState::reset();
        // get_module_db() hands back a clone of the module handle; GateProbeDb trips on
        // clone, so acquiring a database is observable.
        foreach (['licenses', 'vps'] as $module) {
            $GLOBALS[$module.'_dbh'] = new GateProbeDb($module);
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['licenses_dbh'], $GLOBALS['vps_dbh']);
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // cpanel_list()
    // ---------------------------------------------------------------

    /**
     * A non-admin must not get the license list: no license data is fetched from
     * the cPanel API and nothing is rendered.
     */
    public function testCpanelListFetchesNothingAndRendersNothingForNonAdmin(): void
    {
        FrameworkState::$ima = 'client';
        FrameworkState::$cpanelLicenses = ['licenses' => [
            ['ip' => '10.0.0.1', 'hostname' => 'client-should-not-see.example.com'],
        ]];

        cpanel_list();

        $this->assertSame(
            0,
            FrameworkState::$getCpanelLicensesCalls,
            'cpanel_list() must not query the cPanel license API for a non-admin'
        );
        $this->assertSame(
            '',
            FrameworkState::outputText(),
            'cpanel_list() must render no license data for a non-admin'
        );
    }

    /**
     * An admin gets the license list: the API is queried and every license row
     * reaches the rendered output.
     */
    public function testCpanelListRendersEveryLicenseForAdmin(): void
    {
        FrameworkState::$ima = 'admin';
        FrameworkState::$cpanelLicenses = ['licenses' => [
            ['ip' => '10.0.0.1', 'hostname' => 'first.example.com', 'license_id' => '111'],
            ['ip' => '10.0.0.2', 'hostname' => 'second.example.com', 'license_id' => '222'],
        ]];

        cpanel_list();

        $this->assertSame(
            1,
            FrameworkState::$getCpanelLicensesCalls,
            'cpanel_list() must query the cPanel license API exactly once for an admin'
        );

        $rendered = FrameworkState::outputText();
        foreach (['10.0.0.1', 'first.example.com', '111', '10.0.0.2', 'second.example.com', '222'] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $rendered,
                "cpanel_list() should render license value '{$expected}'"
            );
        }
        // Column headings are derived from the license keys.
        $this->assertStringContainsString('License Id', $rendered);
    }

    /**
     * The admin path lazy-loads the API helper before calling it, so the page still
     * works when cpanel.inc.php has not already been included.
     */
    public function testCpanelListLazyLoadsLicenseHelperBeforeUse(): void
    {
        FrameworkState::$ima = 'admin';
        FrameworkState::$cpanelLicenses = ['licenses' => []];

        cpanel_list();

        $this->assertContains(
            'get_cpanel_licenses',
            FrameworkState::$requirements,
            'cpanel_list() must lazy-load get_cpanel_licenses before calling it'
        );
    }

    // ---------------------------------------------------------------
    // unbilled_cpanel()
    // ---------------------------------------------------------------

    /**
     * A client session is refused and never reaches the database.
     */
    public function testUnbilledCpanelRefusesNonAdmin(): void
    {
        FrameworkState::$ima = 'client';
        FrameworkState::$acls = ['view_service' => true];

        $result = unbilled_cpanel();

        $this->assertFalse($result, 'unbilled_cpanel() must return false for a non-admin');
        $this->assertSame(
            [],
            FrameworkState::$moduleDbRequests,
            'unbilled_cpanel() must not open a module database for a non-admin'
        );
        $this->assertNotEmpty(
            FrameworkState::$dialogs,
            'unbilled_cpanel() must tell the user it was refused'
        );
        $this->assertSame('Not admin', FrameworkState::$dialogs[0]['title']);
    }

    /**
     * An admin who lacks the view_service ACL is refused too: being admin alone is
     * not sufficient, the ACL is a second independent condition.
     */
    public function testUnbilledCpanelRefusesAdminWithoutViewServiceAcl(): void
    {
        FrameworkState::$ima = 'admin';
        FrameworkState::$acls = ['view_service' => false];

        $result = unbilled_cpanel();

        $this->assertFalse($result, 'unbilled_cpanel() must return false for an admin lacking view_service');
        $this->assertSame(
            [],
            FrameworkState::$moduleDbRequests,
            'unbilled_cpanel() must not open a module database without the view_service ACL'
        );
        $this->assertNotEmpty(FrameworkState::$dialogs);
    }

    /**
     * An admin holding view_service is let through: execution reaches the point
     * where the page opens its module database.
     */
    public function testUnbilledCpanelAllowsAdminWithViewServiceAcl(): void
    {
        FrameworkState::$ima = 'admin';
        FrameworkState::$acls = ['view_service' => true];

        try {
            unbilled_cpanel();
            $this->fail('unbilled_cpanel() should have reached get_module_db() for a permitted admin');
        } catch (GateOpened $e) {
            // Expected: the permission gate let the request through.
        }

        $this->assertSame(
            [],
            FrameworkState::$dialogs,
            'unbilled_cpanel() must not show a refusal dialog to a permitted admin'
        );
        $this->assertContains(
            'licenses',
            FrameworkState::$moduleDbRequests,
            'unbilled_cpanel() should open the licenses module database once permitted'
        );
    }

    /**
     * The ACL helper is lazy-loaded before the gate consults it, otherwise the gate
     * would fatal instead of denying.
     */
    public function testUnbilledCpanelLazyLoadsAclHelperBeforeGate(): void
    {
        FrameworkState::$ima = 'client';

        unbilled_cpanel();

        $this->assertContains(
            'has_acl',
            FrameworkState::$requirements,
            'unbilled_cpanel() must lazy-load has_acl before the permission gate uses it'
        );
    }
}
