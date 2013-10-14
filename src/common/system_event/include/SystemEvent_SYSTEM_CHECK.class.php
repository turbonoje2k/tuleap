<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


/**
* System Event classes
*
*/
class SystemEvent_SYSTEM_CHECK extends SystemEvent {
    
    /**
     * Verbalize the parameters so they are readable and much user friendly in 
     * notifications
     * 
     * @param bool $with_link true if you want links to entities. The returned 
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link) {
        return '-';
    }
    
    /** 
     * Process stored event
     */
    function process() {
        /* @var $backendSystem BackendSystem */
        $backendSystem      = Backend::instance('System');
        $backendAliases     = Backend::instance('Aliases');
        $backendSVN         = Backend::instance('SVN');
        $backendCVS         = Backend::instance('CVS');
        $backendMailingList = Backend::instance('MailingList');
        
        //TODO: 
        // User: unix_status vs status??
        // Private project: if codeaxadm is not member of the project: check access to SVN (incl. ViewVC), CVS, Web...
        // CVS Watch?
        // TODO: log event in syslog?
        // TODO: check that there is no pending event??? What about lower priority events??
        
        // First, force NSCD refresh to be sure that uid/gid will exist on next
        // actions
        
        $backendSystem->flushNscdAndFsCache();
        
        // remove deleted releases and released files
        if (!$backendSystem->cleanupFRS()) {
            $this->error("An error occured while moving FRS files");
            return false;
        }
        
        // Force global updates: aliases, CVS roots, SVN roots
        $backendAliases->setNeedUpdateMailAliases();

        // Remove temporary files generated by aborted CVS commits
        $backendCVS->cleanup();

        // Check mailing lists
        // (re-)create missing ML
        $mailinglistdao = new MailingListDao();
        $dar = $mailinglistdao->searchAllActiveML();
        foreach($dar as $row) {
            $list = new MailingList($row);
            if (!$backendMailingList->listExists($list)) {
                $backendMailingList->createList($list->getId());
            }
            // TODO what about lists that changed their setting (description, public/private) ?
        }
        
        // Check users
        // (re-)create missing home directories
        $user_manager     = UserManager::instance();
        $userdao          = new UserDao();
        $allowed_statuses = array(PFUser::STATUS_ACTIVE, PFUser::STATUS_RESTRICTED);
        $dar              = $userdao->searchByStatus($allowed_statuses);
        foreach($dar as $row) {
            $user = $user_manager->getUserInstanceFromRow($row);
            if ($user) {
                $backendSystem->userHomeSanityCheck($user);
            }
        }
        
        $project_manager = ProjectManager::instance();
        foreach($project_manager->getProjectsByStatus(Project::STATUS_ACTIVE) as $project) {
            
            // Recreate project directories if they were deleted
            if (!$backendSystem->createProjectHome($project->getId())) {
                $this->error("Could not create project home");
                return false;
            }
            
            if ($project->usesCVS()) {
                $backendCVS->setCVSRootListNeedUpdate();

                if (!$backendCVS->repositoryExists($project)) {
                    if (!$backendCVS->createProjectCVS($project->getId())) {
                        $this->error("Could not create/initialize project CVS repository");
                        return false;
                    }
                    $backendCVS->setCVSPrivacy($project, !$project->isPublic() || $project->isCVSPrivate());
                }
                $backendCVS->createLockDirIfMissing($project);
                // check post-commit hooks
                if (!$backendCVS->updatePostCommit($project)) {
                    return false;
                }
                $backendCVS->updateCVSwriters($project->getID());
                
                $backendCVS->updateCVSWatchMode($project->getID());
                 
                // Check ownership/mode/access rights
                $backendCVS->checkCVSMode($project);
            }
            
            if ($project->usesSVN()) {
                if (!$backendSVN->repositoryExists($project)) {
                    if (!$backendSVN->createProjectSVN($project->getId())) {
                        $this->error("Could not create/initialize project SVN repository");
                        return false;
                    }
                    $backendSVN->updateSVNAccess($project->getId());
                    $backendSVN->setSVNPrivacy($project, !$project->isPublic() || $project->isSVNPrivate());
                    $backendSVN->setSVNApacheConfNeedUpdate();
                } else {
                    $backendSVN->checkSVNAccessPresence($project->getId());
                }
                $backendSVN->updateHooks($project);

                // Check ownership/mode/access rights
                $backendSVN->checkSVNMode($project);
            }
        }

        $logger = new BackendLogger();
        if (is_file($logger->getFilepath())) {
            $backendSystem->changeOwnerGroupMode(
                $logger->getFilepath(),
                Config::get('sys_http_user'),
                Config::get('sys_http_user'),
                0640
            );
        }

        // If no codendi_svnroot.conf file, force recreate.
        if (!is_file($GLOBALS['svn_root_file'])) {
            $backendSVN->setSVNApacheConfNeedUpdate();
        }

        try {
            EventManager::instance()->processEvent(
                Event::PROCCESS_SYSTEM_CHECK,
                array(
                    'logger' => $logger
                )
            );
        } catch(Exception $exception) {
            $this->error($exception->getMessage());
            return false;
        }
        
        $this->done();
        return true;
    }

}

?>
