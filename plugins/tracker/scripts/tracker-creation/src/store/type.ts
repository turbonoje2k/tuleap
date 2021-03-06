/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export interface State {
    csrf_token: CSRFToken;
    project_templates: ProjectTemplate[];
    active_option: CreationOptions;
    selected_tracker_template: Tracker | null;
    tracker_to_be_created: TrackerToBeCreatedMandatoryData;
    has_form_been_submitted: boolean;
}

export interface CSRFToken {
    name: string;
    value: string;
}

export interface ProjectTemplate {
    readonly project_name: string;
    readonly tracker_list: Tracker[];
}

export interface Tracker {
    readonly id: string;
    readonly name: string;
}

export interface TrackerToBeCreatedMandatoryData {
    name: string;
    shortname: string;
}

export enum CreationOptions {
    NONE_YET = "none_yet",
    TRACKER_TEMPLATE = "tracker_template"
}
