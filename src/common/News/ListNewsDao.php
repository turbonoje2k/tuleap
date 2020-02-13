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

namespace Tuleap\News;

use Tuleap\DB\DataAccessObject;
use Tuleap\DB\DBConnection;

class ListNewsDao extends DataAccessObject
{
    public function __construct(?DBConnection $db_connection = null)
    {
        parent::__construct($db_connection);
    }

    public function getProjectNews(\Project $project): array
    {
        $sql = 'SELECT * FROM news_bytes WHERE group_id = ?';
        return $this->getDB()->run($sql, $project->getID());
    }

    public function getOneNews(int $news_id): ?array
    {
        $sql = 'SELECT * FROM news_bytes WHERE id = ?';
        return $this->getDB()->row($sql, $news_id);
    }

    /*public function postOneNews(int $update_news): ?array
    {
        $sql = 'UPDATE news_bytes SET summary, details,  WHERE id = ?';
        return $this->getDB()->row($sql, $update_news);
    }
     /*
    public function deleteOneNews(int $id_target): array
    {
        $sql = 'DELETE FROM news_bytes WHERE id = ?';
        return  $this->getDB()->run($sql, $id_target);

       /*
       $sql->delete('id', 'summary', 'details', [
            'id' => 3
        ]);

    }
*/
}
