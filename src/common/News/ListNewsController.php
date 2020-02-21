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
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\GetProjectTrait;

class ListNewsController implements DispatchableWithRequest, DispatchableWithBurningParrot, DispatchableWithProject
{
    use GetProjectTrait;

    /**
     * @var \MustacheRenderer|\TemplateRenderer
     */
    private $renderer;
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(\TemplateRendererFactory $renderer_factory, \ProjectManager $project_manager)
    {
        $this->renderer = $renderer_factory->getRenderer(__DIR__ . '/templates');
        $this->project_manager = $project_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        //new instance db connect
        $list_news_dao = new ListNewsDao();
        $all_news = [];

        //boucle on project_id for needed data of project (id, title, content, group id)
        foreach ($list_news_dao->getProjectNews($project) as $row) {
            //fill array with data in instance of newsView
            $all_news []= new OneNewsPresenter($row['id'], $row['summary'], $row['details'], $row['group_id']);
        }

        //here is create the view
        $layout->header(['title' => 'List of news']);
        $this->renderer->renderToPage('list-news', new ListOfNewsPresenter($all_news, (int) $project->getID()));
        $layout->footer([]);
    }
}
