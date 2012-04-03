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

require_once dirname(__FILE__).'/../../../include/Tracker/TrackerManager.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Tracker.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Report/Tracker_Report.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Report/Tracker_Report_Criteria.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Artifact/Tracker_ArtifactFactory.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/Artifact/Tracker_Artifact.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/FormElement/Tracker_FormElementFactory.class.php';

Mock::generate('Tracker_Report');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Report_Criteria');
Mock::generate('Tracker');
Mock::generate('Tracker_CrossSearch_SemanticStatusReportField');

class Tracker_CrossSearch_SearchContentViewTest extends TuleapTestCase {
    
    private function buildTreeWithArtifact($artifact_id) {
        $artifact_node = new TreeNode();
        $artifact_node->setId(1);
        $artifact_node->setData(array('id' => $artifact_id, 'title' => 'foo', 'last_changeset_id' => '567'));
        
        $root = new TreeNode();
        $root->setId(0);
        $root->addChild($artifact_node);
        
        return $root;
    }
    
    public function itDoesNotTryToRetrieveSharedFieldOriginForSemanticStatus() {
        $status            = '1'; // Open
        $report            = new MockTracker_Report();
        $status_field      = new MockTracker_CrossSearch_SemanticStatusReportField();
        $criterion         = new Tracker_Report_Criteria(null, $report, $status_field, 0, true);
        $criteria          = array($criterion);
        $artifact_id       = 123;
        $artifact          = new MockTracker_Artifact();
        $tree_of_artifacts = $this->buildTreeWithArtifact($artifact_id);
        $artifact_factory  = new MockTracker_ArtifactFactory();
        $factory           = new MockTracker_FormElementFactory();
        $tracker           = new MockTracker();
        
        $artifact_factory->setReturnValue('getArtifactById', $artifact, array($artifact_id));
        $artifact_factory->expectOnce('getArtifactById', array($artifact_id));
        $artifact->setReturnValue('getTracker', $tracker);
        $factory->setReturnValue('getFieldFromTrackerAndSharedField', $status_field, array($tracker, $status_field));
        $factory->expectNever('getFieldFromTrackerAndSharedField');
        
        $view = new Tracker_CrossSearch_SearchContentView($report,
                                                          $criteria,
                                                          $tree_of_artifacts,
                                                          $artifact_factory,
                                                          $factory);
        $html = $view->fetch();
    }
}
?>
