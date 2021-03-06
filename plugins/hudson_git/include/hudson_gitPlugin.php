<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../hudson/include/hudsonPlugin.php';
require_once __DIR__ . '/../../git/include/gitPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use FastRoute\RouteCollector;
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Tuleap\Git\CollectGitRoutesEvent;
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\HudsonGit\GitJenkinsAdministrationController;
use Tuleap\HudsonGit\GitJenkinsAdministrationDeleteController;
use Tuleap\HudsonGit\GitJenkinsAdministrationPaneBuilder;
use Tuleap\HudsonGit\GitJenkinsAdministrationPOSTController;
use Tuleap\HudsonGit\GitJenkinsAdministrationServerAdder;
use Tuleap\HudsonGit\GitJenkinsAdministrationServerDao;
use Tuleap\HudsonGit\GitJenkinsAdministrationServerDeleter;
use Tuleap\HudsonGit\GitJenkinsAdministrationServerFactory;
use Tuleap\HudsonGit\GitJenkinsAdministrationURLBuilder;
use Tuleap\HudsonGit\HudsonGitPluginDefaultController;
use Tuleap\HudsonGit\Plugin\PluginInfo;
use Tuleap\HudsonGit\Hook;
use Tuleap\HudsonGit\Logger;
use Tuleap\HudsonGit\Job\JobManager;
use Tuleap\HudsonGit\Job\JobDao;
use Tuleap\HudsonGit\GitWebhooksSettingsEnhancer;
use Tuleap\Git\GitViews\RepoManagement\Pane\Hooks;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class hudson_gitPlugin extends Plugin
{
    public const DISPLAY_HUDSON_ADDITION_INFO = 'display_hudson_addition_info';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-hudson_git', __DIR__ . '/../site-content');

        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook('cssfile', 'cssFile', false);

        if (defined('GIT_BASE_URL')) {
            $this->addHook(Hooks::ADDITIONAL_WEBHOOKS);
            $this->addHook(GIT_HOOK_POSTRECEIVE_REF_UPDATE);
            $this->addHook(self::DISPLAY_HUDSON_ADDITION_INFO);
            $this->addHook(GitAdminGetExternalPanePresenters::NAME);
            $this->addHook(CollectGitRoutesEvent::NAME);
        }
    }

    public function cssFile($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/administration/jenkins') !== false &&
            strpos($_SERVER['REQUEST_URI'], '/plugins/git/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getIncludeAssets()->getFileURL('style.css') .'" />';
        }
    }

    /**
     * @access protected for test purpose
     */
    protected function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/hudson_git',
            "/assets/hudson_git"
        );
    }

    public function display_hudson_addition_info($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['installed'] = defined('GIT_BASE_URL');
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('git', 'hudson');
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /** @see Tuleap\Git\GitViews\RepoManagement\Pane\Hooks::ADDITIONAL_WEBHOOKS */
    public function plugin_git_settings_additional_webhooks(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $xzibit = new GitWebhooksSettingsEnhancer(
                new Hook\HookDao(),
                new JobManager(new JobDao()),
                $this->getCSRF()
            );
            $xzibit->pimp($params);
        }
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeGetPostLegacyController'));

            $r->post('/jenkins_server', $this->getRouteHandler('getPostGitAdministrationJenkinsServer'));
            $r->post('/jenkins_server/delete', $this->getRouteHandler('getDeleteGitAdministrationJenkinsServer'));
        });
    }

    public static function getDeleteGitAdministrationJenkinsServer(): GitJenkinsAdministrationDeleteController
    {
        return new GitJenkinsAdministrationDeleteController(
            ProjectManager::instance(),
            self::getGitPermissionsManager(),
            new GitJenkinsAdministrationServerFactory(
                new GitJenkinsAdministrationServerDao(),
                ProjectManager::instance()
            ),
            new GitJenkinsAdministrationServerDeleter(
                new GitJenkinsAdministrationServerDao()
            ),
            new CSRFSynchronizerToken(GitJenkinsAdministrationURLBuilder::buildDeleteUrl())
        );
    }

    public static function getPostGitAdministrationJenkinsServer(): GitJenkinsAdministrationPOSTController
    {
        return new GitJenkinsAdministrationPOSTController(
            ProjectManager::instance(),
            self::getGitPermissionsManager(),
            new GitJenkinsAdministrationServerAdder(
                new GitJenkinsAdministrationServerDao()
            ),
            new CSRFSynchronizerToken(GitJenkinsAdministrationURLBuilder::buildAddUrl())
        );
    }

    public function collectGitRoutesEvent(CollectGitRoutesEvent $event)
    {
        $event->getRouteCollector()->get(
            '/{project_name}/administration/jenkins',
            $this->getRouteHandler('routeGetGitJenkinsAdministration')
        );
    }

    public function routeGetGitJenkinsAdministration(): GitJenkinsAdministrationController
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        assert($git_plugin instanceof GitPlugin);

        return new GitJenkinsAdministrationController(
            ProjectManager::instance(),
            self::getGitPermissionsManager(),
            $git_plugin->getMirrorDataMapper(),
            new GitJenkinsAdministrationServerFactory(
                new GitJenkinsAdministrationServerDao(),
                ProjectManager::instance()
            ),
            $git_plugin->getHeaderRenderer(),
            TemplateRendererFactory::build()->getRenderer(HUDSON_GIT_BASE_DIR.'/templates/git-administration'),
            $this->getIncludeAssets()
        );
    }

    private static function getGitPermissionsManager(): GitPermissionsManager
    {
        $git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            )
        );

        $fine_grained_dao       = new FineGrainedDao();
        $fine_grained_retriever = new FineGrainedRetriever($fine_grained_dao);

        return new GitPermissionsManager(
            new Git_PermissionsDao(),
            $git_system_event_manager,
            $fine_grained_dao,
            $fine_grained_retriever
        );
    }

    public function routeGetPostLegacyController()
    {
        $request    = HTTPRequest::instance();
        $project_id = (int) $request->getProject()->getID();

        return new HudsonGitPluginDefaultController(
            $this->getHookController($request),
            $this->isAllowed($project_id)
        );
    }

    public function git_hook_post_receive_ref_update($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $http_client     = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
            $request_factory = HTTPFactoryBuilder::requestFactory();
            $controller      = new Hook\HookTriggerController(
                new Hook\HookDao(),
                new Hook\JenkinsClient(
                    $http_client,
                    $request_factory,
                    new JenkinsCSRFCrumbRetriever($http_client, $request_factory)
                ),
                $this->getLogger(),
                new JobManager(new JobDao())
            );
            $controller->trigger($params['repository'], $params['newrev']);
        }
    }

    /**
     * @return Hook\HookController
     */
    private function getHookController(Codendi_Request $request)
    {
        return new Hook\HookController(
            $request,
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            new Hook\HookDao(),
            $this->getCSRF()
        );
    }

    private function getCSRF()
    {
        return new CSRFSynchronizerToken('hudson-git-hook-management');
    }

    private function getLogger()
    {
        return new WrapperLogger(new Logger(), 'hudson_git');
    }

    public function gitAdminGetExternalPanePresenters(GitAdminGetExternalPanePresenters $event): void
    {
        if (! ForgeConfig::get('git_allow_jenkins_plugin_hook_configuration')) {
            return;
        }

        $event->addExternalPanePresenter(GitJenkinsAdministrationPaneBuilder::buildPane($event->getProject()));
    }
}
