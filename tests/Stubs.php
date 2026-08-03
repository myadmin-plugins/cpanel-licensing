<?php

/**
 * Test doubles for the MyAdmin framework globals that the procedural cPanel
 * license pages depend on.
 *
 * These exist so the pages' authorization behaviour can actually be EXECUTED in
 * a test instead of being grepped for in the source text. Source-text greps such
 * as "the file contains ima != 'admin'" break on any refactor of how the current
 * role is read (they did: the pages moved from $GLOBALS['tf']->ima to
 * \MyAdmin\App::ima()) while proving nothing about whether the gate is enforced.
 *
 * detain/myadmin-plugin-installer already supplies the real global
 * function_requirements(), get_service_define() and get_module_db() via composer's
 * "files" autoloader, and those delegate to \MyAdmin\App. So rather than shadowing
 * them, the App stub below is what they resolve against — the pages run through the
 * genuine framework shims. Every definition here is guarded so this file is safe to
 * include from more than one test class.
 */

namespace MyAdmin\Licenses\Cpanel\Tests {
    /**
     * Mutable state shared with the framework stubs defined below.
     */
    final class FrameworkState
    {
        /** Value returned by \MyAdmin\App::ima(). */
        public static string $ima = 'client';

        /** @var array<string, bool> ACL name => granted */
        public static array $acls = [];

        /** @var list<string> everything handed to add_output() */
        public static array $output = [];

        /** @var list<array{title: string, body: string}> everything handed to dialog() */
        public static array $dialogs = [];

        /** @var list<string> everything handed to page_title() */
        public static array $titles = [];

        /** @var list<string> every name passed through function_requirements() */
        public static array $requirements = [];

        /** @var list<string> every module whose database handle was opened */
        public static array $moduleDbRequests = [];

        /** Number of times the get_cpanel_licenses() stub was invoked. */
        public static int $getCpanelLicensesCalls = 0;

        /** @var array<string, mixed> payload the get_cpanel_licenses() stub returns */
        public static array $cpanelLicenses = [];

        public static function reset(): void
        {
            self::$ima = 'client';
            self::$acls = [];
            self::$output = [];
            self::$dialogs = [];
            self::$titles = [];
            self::$requirements = [];
            self::$moduleDbRequests = [];
            self::$getCpanelLicensesCalls = 0;
            self::$cpanelLicenses = [];
        }

        /** All captured page output as one string. */
        public static function outputText(): string
        {
            return implode("\n", self::$output);
        }
    }

    /**
     * Raised when a page reaches its module database.
     *
     * Opening a module handle is the first thing each of these pages does once it is
     * past its permission gate, so this exception being raised proves the gate let the
     * request through, and its absence proves the gate blocked it.
     */
    final class GateOpened extends \RuntimeException
    {
    }

    /**
     * Stands in for a module database handle.
     *
     * get_module_db() returns `clone $GLOBALS[$module.'_dbh']`, so cloning is exactly
     * the moment a page acquires its database — and that is where this trips.
     */
    final class GateProbeDb
    {
        /** @var string */
        public $module;

        public function __construct(string $module)
        {
            $this->module = $module;
        }

        public function __clone()
        {
            FrameworkState::$moduleDbRequests[] = $this->module;
            throw new GateOpened('get_module_db('.$this->module.') reached');
        }
    }
}

namespace MyAdmin {
    if (!\class_exists(App::class, false)) {
        /**
         * Minimal stand-in for \MyAdmin\App exposing only the statics the license
         * pages and the plugin-installer global shims reach for.
         */
        class App
        {
            /** @return string */
            public static function ima()
            {
                return \MyAdmin\Licenses\Cpanel\Tests\FrameworkState::$ima;
            }

            /**
             * Backs the real global function_requirements().
             *
             * @param string|array $function
             * @return bool
             */
            public static function functionRequirements($function)
            {
                if (!\is_array($function)) {
                    \MyAdmin\Licenses\Cpanel\Tests\FrameworkState::$requirements[] = (string) $function;
                }
                return true;
            }

            /**
             * Backs the real global get_service_define().
             *
             * @param string $service
             * @return string
             */
            public static function getServiceDefine($service)
            {
                return 'define.'.$service;
            }

            /**
             * @param string $class
             * @return bool
             */
            public static function has($class)
            {
                return false;
            }
        }
    }
}

namespace {
    use MyAdmin\Licenses\Cpanel\Tests\FrameworkState;

    if (!function_exists('page_title')) {
        function page_title($title)
        {
            FrameworkState::$titles[] = (string) $title;
        }
    }

    if (!function_exists('add_output')) {
        function add_output($output)
        {
            FrameworkState::$output[] = (string) $output;
        }
    }

    if (!function_exists('dialog')) {
        function dialog($title, $body = '')
        {
            FrameworkState::$dialogs[] = ['title' => (string) $title, 'body' => (string) $body];
        }
    }

    if (!function_exists('has_acl')) {
        function has_acl($acl)
        {
            return FrameworkState::$acls[$acl] ?? false;
        }
    }

    if (!function_exists('myadmin_log')) {
        function myadmin_log($module, $level, $message, $line = 0, $file = '', ...$rest)
        {
        }
    }

    if (!function_exists('get_cpanel_licenses')) {
        function get_cpanel_licenses()
        {
            FrameworkState::$getCpanelLicensesCalls++;
            return FrameworkState::$cpanelLicenses;
        }
    }

    if (!class_exists('TFTable')) {
        /**
         * Text-rendering stand-in for the framework's TFTable builder. get_table()
         * returns every field that was added, so tests can assert on what a page
         * actually rendered.
         */
        class TFTable
        {
            /** @var string */
            private $title = '';

            /** @var list<string> */
            private $row = [];

            /** @var list<list<string>> */
            private $rows = [];

            public function set_title($title)
            {
                $this->title = (string) $title;
            }

            public function add_field($field = '', $align = '')
            {
                $this->row[] = (string) $field;
            }

            public function add_row()
            {
                $this->rows[] = $this->row;
                $this->row = [];
            }

            public function get_table()
            {
                $out = $this->title;
                foreach ($this->rows as $row) {
                    $out .= "\n".implode(' | ', $row);
                }
                return $out;
            }

            public function alternate_rows()
            {
            }

            public function set_col_options($options = '')
            {
            }

            public function set_colspan($colspan = 1)
            {
            }

            public function add_hidden($name, $value = '')
            {
            }

            public function csrf($name)
            {
            }

            public function make_input($name, $value = '', $size = 20)
            {
                return '<input name="'.$name.'" value="'.$value.'">';
            }

            public function make_submit($label = 'Submit')
            {
                return '<input type="submit" value="'.$label.'">';
            }

            public function make_link($target, $label)
            {
                return '<a href="'.$target.'">'.$label.'</a>';
            }
        }
    }
}
