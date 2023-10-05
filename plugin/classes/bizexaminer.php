<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main plugin class
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer;

use coding_exception;
use mod_bizexaminer\api\api_client;
use mod_bizexaminer\api\api_credentials;
use mod_bizexaminer\api\exam_modules;
use mod_bizexaminer\api\exams;
use mod_bizexaminer\api\remote_proctors;
use mod_bizexaminer\callback_api\callback_api;
use mod_bizexaminer\gradebook\grading;

/**
 * The main plugin class which also acts as a DI-container for services.
 *
 * @package mod_bizexaminer
 */
class bizexaminer {

    /**
     * The DI-container for services
     * Holds service definitions, $services holds instances
     * @var array
     */
    private array $container;

    /**
     * Initialized service instances
     * $container holds service definitions, this holds instances
     * @var array
     */
    private $services = [];

    /**
     * The main plugin instance
     * @var bizexaminer
     */
    private static $instance = null;

    /**
     * Get the current main plugin instance
     *
     * @return bizexaminer
     */
    public static function get_instance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create a new main plugin instance
     */
    public function __construct() {
        $this->container = [];

        $this->init_api();
        $this->init_services();
    }

    /**
     * Get a container value.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key) {
        if (array_key_exists($key, $this->container)) {
            return $this->container[$key];
        }
        return null;
    }

    /**
     * Get an initialized service from the container.
     *
     * @param string $key
     * @return mixed
     * @throws coding_exception
     */
    public function get_service(string $key) {
        $servicedefinition = $this->get($key);
        if (!$servicedefinition) {
            throw new coding_exception('service ' . $key . ' is not defined in bizExaminer.');
        }

        // Create instance if not already exists.
        if (!array_key_exists($key, $this->services)) {
            if (is_callable($servicedefinition)) {
                $this->services[$key] = $servicedefinition($this);
            }
        }

        if (!$this->services[$key]) {
            throw new coding_exception('there was an error creating a service instance for ' . $key);
        }
        return $this->services[$key];

    }

    /**
     * Set a container value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value) {
        $this->container[$key] = $value;
    }

    /**
     * Initialize the API.
     */
    protected function init_api() {
        $instance = get_config('mod_bizexaminer', 'apikeyinstance');
        $keyowner = get_config('mod_bizexaminer', 'apikeyowner');
        $keyorganisation = get_config('mod_bizexaminer', 'apikeyorganisation');

        $this->container['api.instance'] = $instance;
        $this->container['api.keyowner'] = $keyowner;
        $this->container['api.keyorganisation'] = $keyorganisation;
    }

    /**
     * Initialize all services.
     */
    protected function init_services() {
        $this->container['api'] = function($plugin) {
            $credentials = new api_credentials(
                $plugin->get('api.instance'), $plugin->get('api.keyowner'), $plugin->get('api.keyorganisation')
            );
            $apiclient = new api_client($credentials);
            return $apiclient;
        };
        $this->container['settings'] = function(bizexaminer $plugin) {
            return new settings($plugin->get_service('api'));
        };
        $this->container['exammodules'] = function(bizexaminer $plugin) {
            return new exam_modules($plugin->get_service('api'));
        };
        $this->container['remoteproctors'] = function(bizexaminer $plugin) {
            return new remote_proctors($plugin->get_service('api'));
        };
        $this->container['exams'] = function(bizexaminer $plugin) {
            return new exams($plugin->get_service('api'));
        };
        $this->container['callbackapi'] = function(bizexaminer $plugin) {
            return new callback_api();
        };
        $this->container['grading'] = function(bizexaminer $plugin) {
            return new grading();
        };
    }
}
