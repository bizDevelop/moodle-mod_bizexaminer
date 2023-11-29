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
 * Exam modules service.
 *
 * @package     mod_bizexaminer
 * @category    api
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bizexaminer\api;

use cache;
use mod_bizexaminer\task\clear_api_exam_modules_cache;

/**
 * Service for getting exam modules and content revisions
 *
 * Provides methods to get exam modules from the API, explode them to exam modules / content revision
 * and handles caching
 */
class exam_modules extends abstract_api_service {

    /**
     * Get exam modules and content revisions from the Api, uses a cache via transients
     *
     * @return array $examModules (array):
     *               'id' => (string) productPartsId
     *               'name' => (string) productPartName
     *               'contentRevisions' => (array):
     *                  'id' => (string) content revision id
     *                  'fullid' => (string) {$productId}_{$productPartsId}_{$contentRevisionId} (seperated by a _)
     *                  'name' => (string) Name of Product Part Name and content revision name/id
     */
    public function get_exam_modules(): array {
        $cache = cache::make('mod_bizexaminer', 'exam_modules');
        $cachekey = "exam_modules_{$this->api->get_credentials()->get_id()}";

        $returnexams = $cache->get($cachekey);

        if (!$returnexams) {
            $returnexams = [];
            $apiclient = $this->get_api();
            $exammodules = $apiclient->get_exam_modules();

            if (!$exammodules) {
                return $returnexams;
            }

            // See #8 for explanation of structure
            // Product = exam
            // ProductPart = exam Module
            // ContentRevision/examRevision = version of exam module (only 1 - there for backwards compatibility).
            foreach ($exammodules as $exammodule) {
                $productid = $exammodule->productId;
                // Multiple examModules with the same productId (=exam) will be returned by API.
                if (!isset($returnexams[$productid])) {
                    $returnexams[$productid] = [
                        'id' => $productid,
                        'name' => $exammodule->productName,
                        'modules' => [],
                    ];
                }
                // Always use first item - should be the only one.
                // Cecause API returns an examModule entry for each productPart.
                if (!empty($exammodule->examRevisions)) {
                    $revision = $exammodule->examRevisions[0];
                    $id = $exammodule->productPartsId;
                    // The productId is used for grouping; productPartsId and contentRevisionsId for booking.
                    $fullid = self::build_exam_module_id(
                        $productid,
                        $exammodule->productPartsId,
                        $revision->crtContentsRevisionsId
                    );
                    if (empty($exammodule->productPartName)) {
                        $name = sprintf(
                            '%1$s Revision #%2$s',
                            $exammodule->productName,
                            $exammodule->productPartsId
                        );
                    } else {
                        $name = "{$exammodule->productPartName} (#{$id})";
                    }
                    $returnexams[$productid]['modules'][$id] = [
                        'id' => $id,
                        'revisionid' => $revision->crtContentsRevisionsId,
                        'fullid' => $fullid,
                        'name' => $name,
                    ];
                }
            }

            // Save with a relative short amount of expiration
            // this is mostly cached so when viewing settings page, saving, validating (mulitple times within minutes)
            // it gets the same values from local
            // but it needs to be short, so new exam modules created in bizExaminer show here soon.
            $cache->set($cachekey, $returnexams);

            // Trigger adhoc task to clear cache in near future
            // because TTl of cache shouldnt be used according to docs.
            $task = new clear_api_exam_modules_cache();
            $task->set_next_run_time(time() + MINSECS * 5);
            \core\task\manager::reschedule_or_queue_adhoc_task($task);
        }

        return $returnexams;
    }

    /**
     * Extracts the exam module ID and content revision ID from a combined id
     *
     * @param string $fullid ID of exam module & contentRevision ({$productPartsId}_{$contentRevisionId})
     * @return array|false
     *              'product' => (string) product ID
     *              'productpart' => (string) product part ID
     *              'contentrevision' => (string) content revision id
     */
    public function explode_exam_module_ids(string $fullid) {
        if (!str_contains($fullid, '_') || substr_count($fullid, '_') !== 2) {
            return false;
        }
        $idparts = explode('_', $fullid);
        return [
            'product' => $idparts[0],
            'productpart' => $idparts[1],
            'contentrevision' => $idparts[2],
        ];
    }

    /**
     * Build a single unique exam module id usable as reference
     * @param int $productid product ID
     * @param int $productpartsid prorduct part ID
     * @param int $contentrevision content revision ID
     * @return string
     */
    public static function build_exam_module_id(int $productid, int $productpartsid, int $contentrevision): string {
        return "{$productid}_{$productpartsid}_{$contentrevision}";
    }

    /**
     * Checks if an exammodule and content revision exist for a set of api credentials
     *
     * @param string $fullid ID of exam module & contentRevision ({$productId}_{$productPartsId}_{$contentRevisionId})
     * @return bool
     */
    public function has_exam_module_content_revision(string $fullid): bool {
        $ids = $this->explode_exam_module_ids($fullid);
        if (!$ids) {
            return false;
        }
        $productid = $ids['product'];
        $productpartid = $ids['productpart'];
        $contentrevisionid = $ids['contentrevision'];

        $allexammodules = $this->get_exam_modules();
        if (!isset($allexammodules[$productid])) {
            return false;
        }

        if (!isset($allexammodules[$productid]['modules'][$productpartid])) {
            return false;
        }

        if ($allexammodules[$productid]['modules'][$productpartid]['revisionid'] != $contentrevisionid) {
            return false;
        }
        return true;
    }
}
