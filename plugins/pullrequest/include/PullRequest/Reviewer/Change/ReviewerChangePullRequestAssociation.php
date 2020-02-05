<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest\Reviewer\Change;

use Tuleap\PullRequest\PullRequest;

final class ReviewerChangePullRequestAssociation
{
    /**
     * @var ReviewerChange
     */
    private $reviewer_change;
    /**
     * @var PullRequest
     */
    private $pull_request;

    public function __construct(ReviewerChange $reviewer_change, PullRequest $pull_request)
    {
        $this->reviewer_change = $reviewer_change;
        $this->pull_request    = $pull_request;
    }

    /**
     * @return ReviewerChange
     */
    public function getReviewerChange() : ReviewerChange
    {
        return $this->reviewer_change;
    }

    /**
     * @return PullRequest
     */
    public function getPullRequest() : PullRequest
    {
        return $this->pull_request;
    }
}