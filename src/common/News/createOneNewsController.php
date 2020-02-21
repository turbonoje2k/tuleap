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


use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class createOneNewsController implements DispatchableWithRequest
{
    /**
     * @var \MustacheRenderer|\TemplateRenderer
     */
    private $renderer;

    public function __construct(\TemplateRendererFactory $renderer_factory)
    {
        $this->renderer = $renderer_factory->getRenderer(__DIR__ . '/templates');
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        //db connect
        $List_news_dao = new ListNewsDao();

        //appel de la fonction
        $List_news_dao->createOneNews();

        $new_news = new createOneNewsPresenter();

        $layout->header(['new_news']);
        $this->renderer->renderToPage('new_news', $new_news);
        $layout->footer([]);
    }
}
