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
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\GetProjectTrait;
use Tuleap\Request\NotFoundException;

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
        $list_news_dao = new ListNewsDao();
        $all_news = [];
        foreach ($list_news_dao->getProjectNews($project) as $row) {
            $all_news []= new OneNewsPresenter($row['id'], $row['summary'], $row['details']);
        }

        $layout->header(['title' => 'List of news']);
        $this->renderer->renderToPage('list-news', new ListOfNewsPresenter($all_news));
        $layout->footer([]);
    }
}
