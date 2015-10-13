<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_Semantic_DescriptionDao extends DataAccessObject {

    public function searchByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM tracker_semantic_description
                WHERE tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function save($tracker_id, $field_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $field_id   = $this->da->escapeInt($field_id);
        $sql = "REPLACE INTO tracker_semantic_description (tracker_id, field_id)
                VALUES ($tracker_id, $field_id)";
        return $this->update($sql);
    }

}
