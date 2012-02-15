<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/plugin/Plugin.class.php';

/**
 * AgileDashboardPlugin
 */
class AgileDashboardPlugin extends Plugin {

    /**
     * Plugin constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('cssfile', 'cssfile', false);
    }

    /**
     * @return AgileDashboardPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'AgileDashboardPluginInfo.class.php';
            $this->pluginInfo = new AgileDashboardPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function cssfile($params) {
        // Only show the stylesheet if we're actually in the AgileDashboard pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function process(Codendi_Request $request, ProjectManager $manager, BaseLanguage $language, Layout $layout) {
        $project = $manager->getProject($request->get('group_id'));
        $service = $project->getService('plugin_agiledashboard');
        
        if ($service) {
            $this->displayService($service, $language);
        } else {
            $serviceLabel = $language->getText('plugin_agiledashboard', 'title');
            $errorMessage = $language->getText('project_service', 'service_not_used', array($serviceLabel));
            $layout->addFeedback('error', $errorMessage);
            $layout->redirect('/projects/' . $project->getUnixName() . '/');
        }
    }
    
    function displayService(Service $service, BaseLanguage $language) {
        $title = $language->getText('plugin_agiledashboard', 'title');
        
        $service->displayHeader($title, array(), array()); 
        echo 'Hello from AgileDashboardPlugin';
        $service->displayFooter();
    }
}

?>