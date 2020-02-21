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

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\GetProjectTrait;

class DeleteOneNewsController implements DispatchableWithRequest
{
    use GetProjectTrait;

    private $project_manager;
    //manque le routeur????
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        //db connect
        $List_news_dao = new ListNewsDao();

        //need news_id
        //$news_id = (int) $variables ['news_id'];

        //ou
        $news_id = (int) $request->get('id');

        //delete data on db
        $List_news_dao->deleteOneNews($news_id);

        //reload
        $layout->redirect('/project/102/news');
    }
}
