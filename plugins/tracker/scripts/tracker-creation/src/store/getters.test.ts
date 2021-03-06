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

import * as getters from "./getters";
import { CreationOptions, State, Tracker } from "./type";

describe("getters", () => {
    describe("is_ready_for_step_2", () => {
        it("Is not ready if no option is selected", () => {
            const state: State = {
                active_option: CreationOptions.NONE_YET,
                selected_tracker_template: null
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(false);
        });

        it("Is not ready if no tracker template is selected", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_TEMPLATE,
                selected_tracker_template: null
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(false);
        });

        it("Is ready otherwise", () => {
            const state: State = {
                active_option: CreationOptions.TRACKER_TEMPLATE,
                selected_tracker_template: { id: "101", name: "Bugs" } as Tracker
            } as State;

            expect(getters.is_ready_for_step_2(state)).toBe(true);
        });
    });

    describe("is_ready_to_submit", () => {
        it("Is not ready if the tracker has no name", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "",
                    shortname: ""
                }
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(false);
        });

        it("Is not ready if the tracker has no shortname", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "Bugz",
                    shortname: ""
                }
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(false);
        });

        it("Is ready otherwise", () => {
            const state: State = {
                tracker_to_be_created: {
                    name: "Bugz",
                    shortname: "bugz"
                }
            } as State;

            expect(getters.is_ready_to_submit(state)).toBe(true);
        });
    });
});
