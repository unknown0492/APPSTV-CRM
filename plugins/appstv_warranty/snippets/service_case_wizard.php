<?php
/*
 * "Open a service case" wizard (v1.0.21) - markup only; behaviour in js/service_case_wizard.js (SCW).
 * Included by pages/service_cases.php. Steps: 1 Customer and complainant, 2 TVs, 3 Problem, 6 Who and when
 * 5 Photos and videos (1.0.22); 4 Serial numbers follows in a later version.
 */
?>
<div class="modal fade" id="scw_modal" tabindex="-1" data-bs-focus="false" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="scw_title">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
        <div class="modal-content">

            <div class="modal-header py-3 flex-wrap gap-2">
                <div class="d-flex flex-column">
                    <h3 class="modal-title" id="scw_title">Open a service case</h3>
                    <div class="text-muted fs-8" id="scw_saved" aria-live="polite"></div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" id="scw_close" aria-label="Close (the draft is kept)" title="Close (the draft is kept)"><i class="fa-solid fa-xmark fs-2"></i></button>
                <!-- Progress -->
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="scw-steps" id="scw_steps"></div>
                        <div class="text-muted fs-7 text-nowrap ms-3" id="scw_step_text"></div>
                    </div>
                    <div class="progress h-5px"><div class="progress-bar bg-primary" id="scw_progress_bar" role="progressbar" style="width: 0%"></div></div>
                </div>
            </div>

            <div class="modal-body">

                <!-- STEP 1: customer and complainant -->
                <div class="scw-step" data-step="1">
                    <div class="scw-block">
                        <div class="scw-block-title">Customer / company</div>
                        <div id="scw_customer_box" class="position-relative">
                            <input type="search" id="scw_customer_search" class="form-control form-control-lg form-control-solid" placeholder="Type 2-3 letters: company, name, phone or e-mail" autocomplete="off" aria-label="Search the customer" />
                            <div id="scw_customer_results" class="sc-dropdown d-none"></div>
                            <button type="button" class="btn btn-link btn-sm px-0 mt-2" id="scw_add_customer_open"><i class="fa-solid fa-plus me-1"></i>Add a new customer</button>
                        </div>
                        <div id="scw_customer_selected" class="sc-selected d-none">
                            <div><div class="fw-bold fs-5" id="scw_customer_name"></div><div class="text-muted fs-7" id="scw_customer_sub"></div></div>
                            <button type="button" class="btn btn-sm btn-light" id="scw_customer_change">Change</button>
                        </div>
                        <!-- Add a new customer on the spot -->
                        <div id="scw_add_customer" class="scw-subform d-none">
                            <div class="fw-semibold mb-3">New customer</div>
                            <div class="row g-3">
                                <div class="col-12"><label class="form-label" for="scw_nc_company">Company name</label>
                                    <input type="text" id="scw_nc_company" class="form-control form-control-solid" maxlength="200" autocomplete="organization" /></div>
                                <div class="col-6"><label class="form-label" for="scw_nc_first">Contact first name</label>
                                    <input type="text" id="scw_nc_first" class="form-control form-control-solid" maxlength="100" /></div>
                                <div class="col-6"><label class="form-label" for="scw_nc_last">Last name</label>
                                    <input type="text" id="scw_nc_last" class="form-control form-control-solid" maxlength="100" /></div>
                                <div class="col-12 col-md-6"><label class="form-label" for="scw_nc_mobile">Mobile</label>
                                    <input type="tel" id="scw_nc_mobile" class="form-control form-control-solid" maxlength="20" /></div>
                                <div class="col-12 col-md-6"><label class="form-label" for="scw_nc_email">E-mail <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="email" id="scw_nc_email" class="form-control form-control-solid" maxlength="200" /></div>
                            </div>
                            <div id="scw_similar" class="mt-3"></div>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-sm btn-light" id="scw_add_customer_cancel">Cancel</button>
                                <button type="button" class="btn btn-sm btn-primary" id="scw_add_customer_save">
                                    <span class="indicator-label">Save customer</span>
                                    <span class="indicator-progress">Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="scw-block">
                        <div class="scw-block-title">Person who complained</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6 position-relative">
                                <label class="form-label required" for="scw_c_name">Name</label>
                                <input type="text" id="scw_c_name" class="form-control form-control-solid" maxlength="200" autocomplete="off" data-scw-field="complainant.name" />
                                <div id="scw_contact_results" class="sc-dropdown d-none"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="scw_c_phone">Phone</label>
                                <input type="tel" id="scw_c_phone" class="form-control form-control-solid" maxlength="50" data-scw-field="complainant.phone" />
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="scw_c_email">E-mail <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="email" id="scw_c_email" class="form-control form-control-solid" maxlength="200" data-scw-field="complainant.email" />
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="scw_c_position">Position <span class="text-muted fw-normal">(optional, e.g. Front Office)</span></label>
                                <input type="text" id="scw_c_position" class="form-control form-control-solid" maxlength="100" data-scw-field="complainant.position" />
                            </div>
                        </div>
                    </div>

                    <div class="scw-block">
                        <div class="scw-block-title">When and how did they complain?</div>
                        <div class="row g-3 mb-4">
                            <div class="col-7 col-md-4"><label class="form-label required" for="scw_complained_date">Date</label>
                                <input type="date" id="scw_complained_date" class="form-control form-control-solid" data-scw-field="complained_date" /></div>
                            <div class="col-5 col-md-3"><label class="form-label required" for="scw_complained_time">Time</label>
                                <input type="time" id="scw_complained_time" class="form-control form-control-solid" data-scw-field="complained_time" /></div>
                        </div>
                        <div class="form-label required">Tick one or more</div>
                        <div class="scw-chips" id="scw_channels" role="group" aria-label="How they complained"></div>
                    </div>
                </div>

                <!-- STEP 2: which TVs -->
                <div class="scw-step d-none" data-step="2">
                    <div class="scw-block">
                        <div class="scw-block-title">How many TVs have a problem?</div>
                        <div class="scw-choice-row">
                            <button type="button" class="scw-big" data-scw-pick="many" data-value="0"><i class="fa-solid fa-tv"></i><span>One</span></button>
                            <button type="button" class="scw-big" data-scw-pick="many" data-value="1"><i class="fa-solid fa-layer-group"></i><span>More than one</span></button>
                        </div>
                        <div id="scw_tv_count_box" class="mt-4 d-none">
                            <label class="form-label required" for="scw_tv_count">Number of TVs</label>
                            <input type="number" id="scw_tv_count" class="form-control form-control-solid scw-count" min="2" max="500" step="1" inputmode="numeric" data-scw-field="tv_count" />
                        </div>
                        <div id="scw_split_box" class="mt-4 d-none">
                            <div class="scw-options">
                                <button type="button" class="scw-option" data-scw-pick="split" data-value="0"><span class="scw-radio"></span>
                                    <span><span class="fw-semibold d-block">Open ONE case for all the TVs</span><span class="text-muted fs-8">One case number, all TVs on it</span></span></button>
                                <button type="button" class="scw-option" data-scw-pick="split" data-value="1"><span class="scw-radio"></span>
                                    <span><span class="fw-semibold d-block">Open ONE CASE PER TV</span><span class="text-muted fs-8">A main case + one case for each TV</span></span></button>
                            </div>
                            <div class="text-muted fs-7 mt-2" id="scw_split_hint"></div>
                        </div>
                    </div>
                    <div class="scw-block">
                        <div class="scw-block-title">TV models <span class="text-muted fw-normal fs-7">(a rough guess is fine)</span></div>
                        <div id="scw_models"></div>
                    </div>
                    <div class="scw-block">
                        <div class="scw-block-title">Warranty</div>
                        <div class="scw-options" id="scw_warranty"></div>
                    </div>
                </div>

                <!-- STEP 3: the problem -->
                <div class="scw-step d-none" data-step="3">
                    <div class="scw-block">
                        <div class="scw-block-title">What is wrong? <span class="text-muted fw-normal fs-7">(tick one or more)</span></div>
                        <div class="scw-chips" id="scw_problem_types" role="group" aria-label="Problem"></div>
                    </div>
                    <div class="scw-block">
                        <label class="scw-block-title required d-block" for="scw_problem">In their own words</label>
                        <textarea id="scw_problem" class="form-control form-control-solid" rows="5" maxlength="5000" placeholder="What the customer told us" data-scw-field="problem"></textarea>
                    </div>
                </div>

                <!-- STEP 5: photos and videos the customer shared (optional) -->
                <div class="scw-step d-none" data-step="5">
                    <div class="scw-block">
                        <div class="scw-block-title">Photos and videos <span class="text-muted fw-normal fs-7">(optional, e.g. what the customer sent on WhatsApp)</span></div>
                        <div id="scw_media"></div>
                    </div>
                </div>

                <!-- STEP 6: who goes and when -->
                <div class="scw-step d-none" data-step="6">
                    <div class="scw-block">
                        <div class="scw-block-title required">Assign to <span class="text-muted fw-normal fs-7">(one or more)</span></div>
                        <div class="scw-chips" id="scw_staff" role="group" aria-label="Staff"></div>
                    </div>
                    <div class="scw-block">
                        <div class="scw-block-title required">Visit window</div>
                        <div class="row g-3">
                            <div class="col-6 col-md-4"><label class="form-label" for="scw_visit_from">From</label>
                                <input type="date" id="scw_visit_from" class="form-control form-control-solid" data-scw-field="visit_from" /></div>
                            <div class="col-6 col-md-4"><label class="form-label" for="scw_visit_to">To</label>
                                <input type="date" id="scw_visit_to" class="form-control form-control-solid" data-scw-field="visit_to" /></div>
                        </div>
                        <div class="form-text">The staff get a reminder e-mail every morning until the end of this window.</div>
                    </div>
                    <div class="scw-block">
                        <div class="scw-block-title">Summary</div>
                        <div class="scw-summary" id="scw_summary"></div>
                    </div>
                </div>

            </div>

            <div class="modal-footer py-3 scw-footer">
                <button type="button" class="btn btn-light" id="scw_back"><i class="fa-solid fa-arrow-left"></i> Back</button>
                <button type="button" class="btn btn-light-primary" id="scw_save_draft"><i class="fa-regular fa-floppy-disk"></i> Save as draft</button>
                <button type="button" class="btn btn-primary" id="scw_next">Next <i class="fa-solid fa-arrow-right ms-1"></i></button>
                <button type="button" class="btn btn-success d-none" id="scw_open">
                    <span class="indicator-label"><i class="fa-solid fa-folder-plus"></i> OPEN CASE</span>
                    <span class="indicator-progress">Opening… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>
