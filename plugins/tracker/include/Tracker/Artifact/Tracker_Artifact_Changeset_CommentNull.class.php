<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_Artifact_Changeset_CommentNull extends Tracker_Artifact_Changeset_Comment {

    public function __construct(Tracker_Artifact_Changeset $changeset) {
        parent::__construct(
            0,
            $changeset,
            0,
            0,
            $changeset->getSubmittedBy(),
            $changeset->getSubmittedOn(),
            '',
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            -1
        );
    }
}
