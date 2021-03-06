<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\ProjectMilestones\Widget;

use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_MilestoneDao;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use HTTPRequest;
use Planning;
use Planning_MilestoneFactory;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker_Artifact_PriorityDao;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneBacklogItemDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneItemsFinder;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

class ProjectMilestonesPresenterBuilder
{
    private const COUNT_ELEMENTS_MODE = "count";
    private const EFFORT_MODE = "effort";

    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $agile_dashboard_milestone_backlog_backlog_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory
     */
    private $agile_dashboard_milestone_backlog_backlog_item_collection_factory;
    /**
     * @var Planning_MilestoneFactory
     */
    private $planning_milestone_factory;
    /**
     * @var \Planning_VirtualTopMilestone
     */
    private $planning_virtual_top_milestone;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var Planning
     */
    private $root_planning;
    /**
     * @var SemanticTimeframe
     */
    private $semantic_timeframe;
    /**
     * @var CountElementsModeChecker
     */
    private $count_elements_mode_checker;

    public function __construct(
        HTTPRequest $request,
        AgileDashboard_Milestone_Backlog_BacklogFactory $agile_dashboard_milestone_backlog_backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $agile_dashboard_milestone_backlog_backlog_item_collection_factory,
        Planning_MilestoneFactory $planning_milestone_factory,
        TrackerFactory $tracker_factory,
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        Planning $root_planning,
        SemanticTimeframe $semantic_timeframe,
        CountElementsModeChecker $count_elements_mode_checker
    ) {
        $this->request                                                           = $request;
        $this->agile_dashboard_milestone_backlog_backlog_factory                 = $agile_dashboard_milestone_backlog_backlog_factory;
        $this->agile_dashboard_milestone_backlog_backlog_item_collection_factory = $agile_dashboard_milestone_backlog_backlog_item_collection_factory;
        $this->planning_milestone_factory                                        = $planning_milestone_factory;
        $this->current_user                                                      = $this->request->getCurrentUser();
        $this->planning_virtual_top_milestone                                    = $this->planning_milestone_factory->getVirtualTopMilestone($this->current_user, $this->request->getProject());
        $this->tracker_factory                                                   = $tracker_factory;
        $this->explicit_backlog_dao                                              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao                                 = $artifacts_in_explicit_backlog_dao;
        $this->root_planning                                                     = $root_planning;
        $this->semantic_timeframe                                                = $semantic_timeframe;
        $this->count_elements_mode_checker                                       = $count_elements_mode_checker;
    }

    public static function build(Planning $root_planning): ProjectMilestonesPresenterBuilder
    {
        $semantic_timeframe_builder = new SemanticTimeframeBuilder(
            new SemanticTimeframeDao(),
            Tracker_FormElementFactory::instance()
        );

        $planning_factory = new PlanningFactory(
            new PlanningDao(),
            TrackerFactory::instance(),
            new PlanningPermissionsManager()
        );

        $scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            new ScrumForMonoMilestoneDao(),
            $planning_factory
        );

        $mono_milestone_items_finder = new MonoMilestoneItemsFinder(
            new MonoMilestoneBacklogItemDao(),
            Tracker_ArtifactFactory::instance()
        );

        $milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            Tracker_ArtifactFactory::instance(),
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance(),
            new AgileDashboard_Milestone_MilestoneStatusCounter(new AgileDashboard_BacklogItemDao(), new Tracker_ArtifactDao(), Tracker_ArtifactFactory::instance()),
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            $scrum_mono_milestone_checker,
            new TimeframeBuilder(Tracker_FormElementFactory::instance(), new SemanticTimeframeBuilder(new SemanticTimeframeDao(), Tracker_FormElementFactory::instance()), new \BackendLogger()),
            new MilestoneBurndownFieldChecker(Tracker_FormElementFactory::instance())
        );

        return new self(
            HTTPRequest::instance(),
            new AgileDashboard_Milestone_Backlog_BacklogFactory(
                new AgileDashboard_BacklogItemDao(),
                Tracker_ArtifactFactory::instance(),
                $planning_factory,
                $scrum_mono_milestone_checker,
                $mono_milestone_items_finder
            ),
            new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
                new AgileDashboard_BacklogItemDao(),
                Tracker_ArtifactFactory::instance(),
                $milestone_factory,
                $planning_factory,
                new AgileDashboard_Milestone_Backlog_BacklogItemBuilder,
                new RemainingEffortValueRetriever(Tracker_FormElementFactory::instance()),
                new ArtifactsInExplicitBacklogDao(),
                new Tracker_Artifact_PriorityDao()
            ),
            $milestone_factory,
            TrackerFactory::instance(),
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao(),
            $root_planning,
            $semantic_timeframe_builder->getSemantic($root_planning->getPlanningTracker()),
            new CountElementsModeChecker(new ProjectsCountModeDao())
        );
    }

    public function getProjectMilestonePresenter(): ProjectMilestonesPresenter
    {
        return new ProjectMilestonesPresenter(
            $this->request->getProject(),
            $this->getNumberUpcomingReleases(),
            $this->getNumberBacklogItems(),
            $this->getTrackersIdAgileDashboard(),
            $this->getLabelTrackerPlanning(),
            $this->isTimeframeDurationField(),
            $this->getLabelStartDateField(),
            $this->getLabelTimeframeField(),
            $this->userCanViewSubMilestonesPlanning(),
            $this->activateBurnupChart(),
            $this->getBurnupMode()
        );
    }

    private function getNumberUpcomingReleases(): int
    {
        $futures_milestones = $this->planning_milestone_factory->getAllFutureMilestones($this->current_user, $this->root_planning);

        return count($futures_milestones);
    }

    private function getNumberBacklogItems(): int
    {
        $project_id = (int) $this->request->getProject()->getID();

        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            return $this->artifacts_in_explicit_backlog_dao->getNumberOfItemsInExplicitBacklog($project_id);
        }

        $backlog = $this->agile_dashboard_milestone_backlog_backlog_item_collection_factory
            ->getUnassignedOpenCollection(
                $this->current_user,
                $this->planning_virtual_top_milestone,
                $this->agile_dashboard_milestone_backlog_backlog_factory->getSelfBacklog($this->planning_virtual_top_milestone),
                false
            );

        return $backlog->count();
    }

    private function getTrackersIdAgileDashboard(): array
    {
        $trackers_agile_dashboard    = [];
        $trackers_id_agile_dashboard = $this->planning_virtual_top_milestone->getPlanning()->getBacklogTrackersIds();

        foreach ($trackers_id_agile_dashboard as $tracker_id) {
            $tracker                 = $this->tracker_factory->getTrackerById($tracker_id);
            $tracker_agile_dashboard = [
                'id' => (int)$tracker_id,
                'color_name' => $tracker->getColor()->getName(),
                'label' => $tracker->getName()
            ];

            $trackers_agile_dashboard[] = $tracker_agile_dashboard;
        }

        return $trackers_agile_dashboard;
    }

    private function getLabelTrackerPlanning(): string
    {
        return $this->root_planning->getPlanningTracker()->getName();
    }

    private function userCanViewSubMilestonesPlanning(): bool
    {
        if (count($this->root_planning->getPlanningTracker()->getChildren()) === 0) {
            return false;
        }

        return $this->root_planning->getPlanningTracker()->getChildren()[0]->userCanView();
    }

    private function isTimeframeDurationField(): bool
    {
        return $this->semantic_timeframe->getDurationField() !== null;
    }

    private function getLabelTimeframeField(): string
    {
        $duration_field = $this->semantic_timeframe->getDurationField();
        $end_date_field = $this->semantic_timeframe->getEndDateField();

        if ($duration_field) {
            return $duration_field->getLabel();
        }
        if ($end_date_field) {
            return $end_date_field->getLabel();
        }

        throw new TimeframeBrokenConfigurationException($this->semantic_timeframe->getTracker());
    }

    private function getLabelStartDateField(): string
    {
        $start_date_field = $this->semantic_timeframe->getStartDateField();

        if ($start_date_field) {
            return $start_date_field->getLabel();
        }

        return 'start date';
    }

    private function activateBurnupChart(): bool
    {
        if (! \ForgeConfig::get('project_milestones_activate_burnup')) {
            return false;
        }

        return true;
    }

    private function getBurnupMode(): string
    {
        if ($this->count_elements_mode_checker->burnupMustUseCountElementsMode($this->request->getProject())) {
            return self::COUNT_ELEMENTS_MODE;
        }

        return self::EFFORT_MODE;
    }
}
