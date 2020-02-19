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
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class UpdateOneNewsController implements DispatchableWithRequest
{

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        //db connect
        $List_news_dao = new ListNewsDao();
        $news_id = (int) $variables['news_id'];

        //update datas
        $title = (string) $request->get('title');
        $content = (string) $request->get('content');
        $List_news_dao->updateOneNews($news_id, $title, $content);

        $layout->redirect('/project/102/news');
    }
}
