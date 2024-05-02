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
 * Base data object for any objects stored in the database.
 *
 * @package     mod_bizexaminer
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer;

use moodle_database;
use stdClass;

/**
 * Base data object for any objects stored in the database.
 *
 * @package mod_bizexaminer
 */
abstract class data_object {
    /**
     * The table name in the database (without moodle prefix).
     * @var string
     */
    public const TABLE = '';

    /**
     * The id of the object in the database.
     * @var null|int
     */
    public ?int $id;

    /**
     * Create a new in-memory instance.
     */
    public function __construct() {
        $this->id = null;
    }

    /**
     * Get a data object from the database
     *
     * @param int $id
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means we will throw an exception if no record or multiple records found.
     * @return null|static
     */
    public static function get(int $id, int $strictness = IGNORE_MISSING): ?self {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            $obj = $DB->get_record(static::TABLE, ['id' => $id], '*', $strictness);
            if (!$obj) {
                return null;
            }
        } catch (\dml_exception $exception) {
            return null;
        }

        return self::create_from_data($obj);
    }

    /**
     * Gets a single data object from the database by a custom sql query
     * Wrapper fro $DB->get_record_sql
     *
     * @see $DB->get_record_sql
     *
     * @param string $sql
     * @param null|array $params
     * @param int $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                        IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                        MUST_EXIST means we will throw an exception if no record or multiple records found.
     * @return null|static
     */
    public static function get_by_sql(string $sql, ?array $params = null, int $strictness = IGNORE_MISSING): ?self {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            $obj = $DB->get_record_sql($sql, $params, $strictness);
            if (!$obj) {
                return null;
            }
        } catch (\dml_exception $exception) {
            return null;
        }

        return self::create_from_data($obj);
    }

    /**
     * Gets a single data object from the database where the given conditions are used in the WHERE clause.
     * Wrapper fro $DB->get_record_select
     *
     * @see $DB->get_record_select
     *
     * @param string $select
     * @param null|array $params
     * @return null|static
     */
    public static function get_by_select(string $select, ?array $params = null): ?self {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            $obj = $DB->get_record_select(static::TABLE, $select, $params);
            if (!$obj) {
                return null;
            }
        } catch (\dml_exception $exception) {
            return null;
        }

        return self::create_from_data($obj);
    }

    /**
     * Get one data object from the database by conditions
     * Similar to $DB->get_records
     *
     * @uses get_all because get_record does not allow sorting
     *
     * @param array $conditions optional array $fieldname=>requestedvalue with AND in between
     * @param string $sort an order to sort the results in (optional, a valid SQL ORDER BY parameter).
     * @param int $limitfrom return a subset of records, starting at this point (optional).
     * @return null|static
     */
    public static function get_by(array $conditions, $sort = '', $limitfrom = 0): ?self {
        $all = static::get_all($conditions, $sort, '*', $limitfrom, 1);
        if (empty($all)) {
            return null;
        }
        $firstkey = array_key_first($all);
        return $all[$firstkey];
    }

    /**
     * Gets multiple data objects from the database
     * Similar to $DB->get_records
     *
     * @see $DB->get_records
     *
     * @param array|null $conditions optional array $fieldname=>requestedvalue with AND in between
     * @param array ...$args Any args passed directly to $DB->get_records
     * string $args['sort'] an order to sort the results in (optional, a valid SQL ORDER BY parameter).
     * string $args['fields'] a comma separated list of fields to return (optional, by default
     *   all fields are returned). The first field will be used as key for the
     *   array so must be a unique field such as 'id'.
     * int $args['limitfrom'] return a subset of records, starting at this point (optional).
     * int $args['limitnum'] return a subset comprising this many records in total (optional, required if $limitfrom is set).
     * @return static[] An array of Objects indexed by id.
     */
    public static function get_all(?array $conditions = [], ...$args): array {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            $objects = $DB->get_records(static::TABLE, $conditions, ...$args);
        } catch (\dml_exception $exception) {
            return [];
        }

        return array_filter(array_map([static::class, 'create_from_data'], $objects));
    }

    /**
     * Counts the records in the database
     * Wrapper for $DB->count_records
     *
     * @see $DB->count_records
     *
     * @param array|null $conditions optional array $fieldname=>requestedvalue with AND in between
     * @return int
     */
    public static function count(?array $conditions = []): int {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            return $DB->count_records(static::TABLE, $conditions);
        } catch (\dml_exception $exception) {
            return 0;
        }
    }

    /**
     * Save a data object to the database
     *
     * @param self $dataobject
     * @return int|false
     */
    public static function save(self $dataobject) {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        $obj = $dataobject->get_data();
        try {
            if ($obj->id) {
                $updated = $DB->update_record(static::TABLE, $obj);
                return $updated ? $obj->id : false;
            } else {
                $id = $DB->insert_record(static::TABLE, $obj, true);
                $dataobject->id = $id;
                return $id;
            }
        } catch (\dml_exception $exception) {
            return false;
        }

    }

    /**
     * Deletes an instance of the data_object from the database
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id) {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            $exists = $DB->get_record(static::TABLE, ['id' => $id]);
            if (!$exists) {
                return false;
            }
        } catch (\dml_exception $exception) {
            return false;
        }

        try {
            return $DB->delete_records(static::TABLE, ['id' => $id]);
        } catch (\dml_exception $exception) {
            return false;
        }
    }

    /**
     * Delete multiple data objects from the database based on conditions
     *
     * @uses $DB->delete_records
     *
     * @param null|array $conditions
     * @return bool
     */
    public static function delete_all(?array $conditions = []): bool {
        /** @var moodle_database $DB */ // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        global $DB;
        try {
            return $DB->delete_records(static::TABLE, $conditions);
        } catch (\dml_exception $exception) {
            return false;
        }
    }

    /**
     * Creates a new instance of the data_object from a moodle data object (eg mod_form, database)
     *
     * @param stdClass $obj
     * @return static
     */
    public static function create_from_data(\stdClass $obj): self {
        $dataobject = new static();
        static::load_data($dataobject, $obj);
        return $dataobject;
    }

    /**
     * Loads data from a moodle data object (eg mod_form, database) into an instance of the data_object
     *
     * @param self $dataobject
     * @param stdClass $data
     */
    public static function load_data(self $dataobject, \stdClass $data) {
        $dataobject->id = $data->id ?? null;
    }

    /**
     * Get the data_objects data as a moodle data object (eg for mod_form, database)
     *
     * @return stdClass
     */
    public function get_data(): \stdClass {
        $data = new \stdClass;
        $data->id = $this->id;

        return $data;
    }
}
