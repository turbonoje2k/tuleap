<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\News;

class OneNewsPresenter
{
    public $id;
    public $title;
    public $content;
    public $project_id;

    public function __construct(int $id, string $title, string $content, int $project_id)
    {
        $this->id = $id;
        $this->title = $title;
        //limit caracter in view
        //2 steps (1 count letter wth strlen 2 limite the letter wth substr)
        if (strlen($title)>25) {
            $this->title = substr($title, 0, 25);
        }
        $this->content = $content;
        if (strlen($content)>80) {
            $this->content = substr($content, 0, 70);
        }
        $this->project_id = $project_id;
    }
}
