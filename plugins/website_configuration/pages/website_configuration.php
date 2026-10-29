<?php
checkAuthorizationForPage( 'website_configuration' );

// Website Configuration (Scodezy base feature, gen3 1.0.56; tabs 1.0.58) - the content is drawn by website_configuration.js
$wc_js  = dirname( __DIR__ ) . '/js/' . basename( __FILE__, ".php" ) . '.js';
$wc_css = dirname( __DIR__ ) . '/css/' . basename( __FILE__, ".php" ) . '.css';

/**
 * @description Prints a Save button of a tab
 * @param {string} $section - Section sent to scodezy_save_website_configuration
 * @param {string} $label - Button text
 * @returns {void}
 */
function wcSaveButton( $section, $label ){ ?>
    <div class="d-flex justify-content-end mt-6">
        <button type="button" class="btn btn-primary" data-wc-save="<?= $section ?>">
            <span class="indicator-label"><?= $label ?></span>
            <span class="indicator-progress">Please wait… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
        </button>
    </div>
<?php }
?>
<link href="plugins/website_configuration/css/website_configuration.css?v=<?= @filemtime( $wc_css ) ?>" rel="stylesheet" type="text/css"/>

<!--begin::Website Configuration-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-fluid"><!-- Full width of the content area (1.0.60) -->

        <div class="mb-6">
            <h2 class="fw-bold mb-1">Website Configuration</h2>
            <div class="text-muted fs-6">Name, branding, sign-in pages, admin panel and maintenance settings of this website. Each tab saves on its own.</div>
        </div>

        <div class="text-center text-muted py-15" id="wc_loading">
            <span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading…
        </div>

        <div class="card d-none" id="wc_sections">
            <!-- Folder-style tabs: the selected tab is highlighted (1.0.60) -->
            <div class="px-9 pt-7">
                <ul class="nav nav-tabs wc-tabs fs-6" id="wc_tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link text-nowrap active" data-bs-toggle="tab" href="#wc_tab_general"><i class="fa-solid fa-id-card me-2"></i>General</a></li>
                    <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#wc_tab_branding"><i class="fa-solid fa-image me-2"></i>Branding</a></li>
                    <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#wc_tab_signin_pages"><i class="fa-solid fa-window-maximize me-2"></i>Sign-in pages</a></li>
                    <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#wc_tab_admin"><i class="fa-solid fa-table-columns me-2"></i>Admin panel</a></li>
                    <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#wc_tab_profiles"><i class="fa-solid fa-user-gear me-2"></i>Profiles</a></li>
                    <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#wc_tab_signin"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign-in options</a></li>
                    <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#wc_tab_maintenance"><i class="fa-solid fa-screwdriver-wrench me-2"></i>Maintenance</a></li>
                    <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#wc_tab_social"><i class="fa-solid fa-share-nodes me-2"></i>Social sign-in</a></li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">

                    <!-- General -->
                    <div class="tab-pane fade show active" id="wc_tab_general" role="tabpanel">
                        <div class="row g-8">
                            <div class="col-12 col-xl-7">
                                <h4 class="fw-bold mb-1">Identity</h4>
                                <div class="text-muted fs-7 mb-5">Shown in browser tabs, emails, the sign-in pages and every footer.</div>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label required" for="wc_site_name">Site name</label>
                                        <input type="text" class="form-control form-control-solid" id="wc_site_name" maxlength="100" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="wc_tagline">Tagline</label>
                                        <input type="text" class="form-control form-control-solid" id="wc_tagline" maxlength="200" />
                                        <div class="form-text">The line under the logo on the sign-in pages.</div>
                                    </div>
                                    <div class="col-4 col-md-3">
                                        <label class="form-label required" for="wc_copyright_year">Year</label>
                                        <input type="text" class="form-control form-control-solid" id="wc_copyright_year" maxlength="4" inputmode="numeric" />
                                    </div>
                                    <div class="col-8 col-md-9">
                                        <label class="form-label required" for="wc_copyright_owner">Copyright owner</label>
                                        <input type="text" class="form-control form-control-solid" id="wc_copyright_owner" maxlength="100" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="wc_copyright_url">Copyright link</label>
                                        <input type="url" class="form-control form-control-solid" id="wc_copyright_url" maxlength="300" placeholder="https://" />
                                        <div class="form-text">Footer of every page: <span class="fw-semibold" id="wc_copyright_preview"></span></div>
                                    </div>
                                    <div class="col-4 col-md-3">
                                        <label class="form-label" for="wc_protocol">Protocol</label>
                                        <select class="form-select form-select-solid" id="wc_protocol">
                                            <option value="https">https</option>
                                            <option value="http">http</option>
                                        </select>
                                    </div>
                                    <div class="col-8 col-md-9">
                                        <label class="form-label required" for="wc_domain_name">Domain</label>
                                        <input type="text" class="form-control form-control-solid" id="wc_domain_name" maxlength="200" />
                                    </div>
                                    <div class="col-12">
                                        <div class="notice bg-light-warning rounded border-warning border border-dashed p-3 fs-7 text-gray-700">
                                            <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                                            Domain and protocol build the links in emails (password links, activation). Change them only when the website address changes.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-5">
                                <h4 class="fw-bold mb-1">Support contact</h4>
                                <div class="text-muted fs-7 mb-5">Shown as "Need help?" on the sign-in pages. Leave both empty to hide it.</div>
                                <div class="mb-4">
                                    <label class="form-label" for="wc_support_email">Support email</label>
                                    <input type="email" class="form-control form-control-solid" id="wc_support_email" maxlength="100" placeholder="support@example.com" />
                                </div>
                                <div>
                                    <label class="form-label" for="wc_support_phone">Support phone</label>
                                    <input type="text" class="form-control form-control-solid" id="wc_support_phone" maxlength="30" placeholder="+65 6000 0000" />
                                </div>
                            </div>
                        </div>
                        <?php wcSaveButton( 'identity', 'Save general' ); ?>
                    </div>

                    <!-- Branding -->
                    <div class="tab-pane fade" id="wc_tab_branding" role="tabpanel">
                        <h4 class="fw-bold mb-1">Logos &amp; favicon</h4>
                        <div class="text-muted fs-7 mb-3">PNG, JPG, WEBP, GIF or ICO, up to 2 MB. Each one is saved as soon as it is uploaded. Sign-in pages can also have their own logo (Sign-in pages tab).</div>
                        <div id="wc_images"></div>
                    </div>

                    <!-- Sign-in pages -->
                    <div class="tab-pane fade" id="wc_tab_signin_pages" role="tabpanel">
                        <h4 class="fw-bold mb-1">Background</h4>
                        <div class="text-muted fs-7 mb-4">One background for the Login, Sign Up, Forgot Password and Password Reset pages.</div>
                        <div class="row g-4 align-items-center mb-8">
                            <div class="col-12 col-md-6">
                                <div class="btn-group w-100" role="group" id="wc_bg_type">
                                    <input type="radio" class="btn-check" name="wc_bg_type" id="wc_bg_none" value="none" />
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="wc_bg_none" title="The page's own background (1.0.62)">Built-in</label>
                                    <input type="radio" class="btn-check" name="wc_bg_type" id="wc_bg_gradient" value="gradient" />
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="wc_bg_gradient" id="wc_bg_gradient_label">Animated gradient</label>
                                    <input type="radio" class="btn-check" name="wc_bg_type" id="wc_bg_color" value="color" />
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="wc_bg_color">Colour</label>
                                    <input type="radio" class="btn-check" name="wc_bg_type" id="wc_bg_image" value="image" />
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="wc_bg_image">Image</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color form-control-solid" id="wc_bg_color_value" title="Background colour" />
                                    <span class="text-muted fs-7">Colour (also behind the image)</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3" id="wc_bg_image_box"></div>
                        </div>

                        <h4 class="fw-bold mb-1">Pages</h4>
                        <div class="text-muted fs-7 mb-4">Browser tab title (shown as "title | site name"), heading and sub-heading of each page, and an optional own logo (empty = the common sign-in logo from Branding).</div>
                        <div class="row g-5" id="wc_signin_pages"></div>
                        <?php wcSaveButton( 'signin_pages', 'Save sign-in pages' ); ?>
                    </div>

                    <!-- Admin panel -->
                    <div class="tab-pane fade" id="wc_tab_admin" role="tabpanel">
                        <div class="row g-8">
                            <div class="col-12 col-xl-6">
                                <h4 class="fw-bold mb-1">Titles</h4>
                                <div class="text-muted fs-7 mb-5">How browser tabs and the dashboard are named.</div>
                                <div class="mb-4">
                                    <label class="form-label required" for="wc_tab_title_format">Browser tab title</label>
                                    <input type="text" class="form-control form-control-solid" id="wc_tab_title_format" maxlength="100" placeholder="{page} | {site}" />
                                    <div class="form-text">{page} = the open page, {site} = site name. Preview: <span class="fw-semibold" id="wc_tab_title_preview"></span></div>
                                </div>
                                <div>
                                    <label class="form-label required" for="wc_dashboard_heading">Dashboard heading</label>
                                    <input type="text" class="form-control form-control-solid" id="wc_dashboard_heading" maxlength="80" />
                                    <div class="form-text">Title at the top of the dashboard (also its {page} in the tab title).</div>
                                </div>

                                <h4 class="fw-bold mb-1 mt-10">Footer links</h4>
                                <div class="text-muted fs-7 mb-4">Links on the right of the admin panel footer (up to 4). Remove all to hide them. The copyright line comes from General.</div>
                                <div id="wc_footer_links"></div>
                                <button type="button" class="btn btn-sm btn-light-primary mt-2" id="wc_footer_link_add"><i class="fa-solid fa-plus me-1"></i>Add link</button>
                            </div>
                            <div class="col-12 col-xl-6">
                                <h4 class="fw-bold mb-1">Footer CSS</h4>
                                <div class="text-muted fs-7 mb-4">
                                    Styles for the copyright line and footer links, on the admin panel and the sign-in pages.
                                    Targets: <code>.site-copyright</code>, <code>.site-copyright a</code>, <code>.site-footer-links a</code>, <code>.site-support</code>
                                </div>
                                <textarea class="form-control form-control-solid font-monospace fs-7" id="wc_footer_css" rows="12" maxlength="5000" spellcheck="false"
                                          placeholder=".site-copyright { color: #7e8299; }&#10;.site-copyright a { color: #1b84ff; font-weight: 600; }&#10;.site-footer-links a { color: #1b84ff; }"></textarea>
                            </div>
                        </div>
                        <?php wcSaveButton( 'admin', 'Save admin panel' ); ?>
                    </div>

                    <!-- Profiles (1.0.61) -->
                    <div class="tab-pane fade" id="wc_tab_profiles" role="tabpanel">
                        <div class="mw-700px">
                            <h4 class="fw-bold mb-1">Name next to the profile picture</h4>
                            <div class="text-muted fs-7 mb-4">What the top-right corner shows beside each user's picture.</div>
                            <div class="row align-items-center mb-2">
                                <label class="col-6 col-form-label fw-semibold" for="wc_display_default">Show</label>
                                <div class="col-6"><select class="form-select form-select-solid" id="wc_display_default"></select></div>
                            </div>
                            <div class="wc-switch-row">
                                <div><div class="fw-semibold">Let users choose their own</div><div class="text-muted fs-7">Each user can pick another field on My Profile; the choice above is their default.</div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_display_user_choice" /></div>
                            </div>

                            <h4 class="fw-bold mb-1 mt-10">Profile pictures</h4>
                            <div class="text-muted fs-7 mb-2">Users can always pick one of the cartoon avatars or their initials.</div>
                            <div class="wc-switch-row">
                                <div><div class="fw-semibold">Allow own picture upload</div><div class="text-muted fs-7">PNG, JPG or WEBP up to 2 MB. Turning it off keeps pictures already uploaded.</div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_avatar_upload" /></div>
                            </div>

                            <h4 class="fw-bold mb-1 mt-10">Email change</h4>
                            <div class="text-muted fs-7 mb-4">How a user confirms a new email address on My Profile (the new address must prove it belongs to them).</div>
                            <div class="btn-group w-100 mb-4" role="group">
                                <input type="radio" class="btn-check" name="wc_email_method" id="wc_email_method_link" value="link" />
                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="wc_email_method_link"><i class="fa-solid fa-link me-2"></i>Link in the email</label>
                                <input type="radio" class="btn-check" name="wc_email_method" id="wc_email_method_code" value="code" />
                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="wc_email_method_code"><i class="fa-solid fa-keyboard me-2"></i>6-digit code on My Profile</label>
                            </div>
                            <div class="row align-items-center">
                                <label class="col-6 col-form-label fw-semibold" for="wc_email_code_minutes">Code valid for (minutes)</label>
                                <div class="col-6"><input type="number" class="form-control form-control-solid" id="wc_email_code_minutes" min="5" max="120" /></div>
                            </div>
                            <div class="form-text">The link is valid for 24 hours.</div>
                            <?php wcSaveButton( 'profiles', 'Save profiles' ); ?>
                        </div>
                    </div>

                    <!-- Sign-in options -->
                    <div class="tab-pane fade" id="wc_tab_signin" role="tabpanel">
                        <div class="mw-700px">
                            <div class="wc-switch-row">
                                <div><div class="fw-semibold">Forgot Password</div><div class="text-muted fs-7">Users can ask for a password reset link by email.</div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_allow_forgot_password" /></div>
                            </div>
                            <div class="wc-switch-row">
                                <div><div class="fw-semibold">Self registration</div><div class="text-muted fs-7">Shows "Sign up" on the login page.</div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_is_registration_open" /></div>
                            </div>
                            <div class="wc-switch-row">
                                <div><div class="fw-semibold">Email sign-in details to new users</div><div class="text-muted fs-7">When an account is created on the Users page.</div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_allow_email_credentials" /></div>
                            </div>
                            <div class="wc-switch-row">
                                <div><div class="fw-semibold">Account activation required</div><div class="text-muted fs-7">Self-registered accounts must open an emailed link first.</div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_activation_required" /></div>
                            </div>
                            <div class="row align-items-center mt-4">
                                <label class="col-7 col-form-label fw-semibold" for="wc_activation_minutes">Activation link valid for (minutes)</label>
                                <div class="col-5"><input type="number" class="form-control form-control-solid" id="wc_activation_minutes" min="5" max="10080" /></div>
                            </div>
                            <?php wcSaveButton( 'signin', 'Save sign-in options' ); ?>
                        </div>
                    </div>

                    <!-- Maintenance -->
                    <div class="tab-pane fade" id="wc_tab_maintenance" role="tabpanel">
                        <div class="mw-700px">
                            <h4 class="fw-bold mb-1">Maintenance mode</h4>
                            <div class="text-muted fs-7 mb-3">While it is on, only roles that can open this page (Administrators) can sign in and use the panel. Everyone else sees the message below. TVs, OPS servers and apps keep working.</div>
                            <div class="wc-switch-row">
                                <div><div class="fw-semibold">Maintenance mode</div><div class="text-muted fs-7" id="wc_maintenance_state"></div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_maintenance_mode" /></div>
                            </div>
                            <div class="mt-3 mb-10">
                                <label class="form-label" for="wc_maintenance_message">Message</label>
                                <textarea class="form-control form-control-solid" id="wc_maintenance_message" rows="2" maxlength="300"></textarea>
                            </div>

                            <h4 class="fw-bold mb-1">Logs &amp; errors</h4>
                            <div class="text-muted fs-7 mb-3">How long logs are kept, and error display.</div>
                            <div id="wc_retention"></div>
                            <div class="wc-switch-row mt-3">
                                <div><div class="fw-semibold">Show PHP errors</div><div class="text-muted fs-7">For troubleshooting only: errors become visible to every visitor. Keep it off.</div></div>
                                <div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_display_php_errors" /></div>
                            </div>
                            <?php wcSaveButton( 'maintenance', 'Save maintenance' ); ?>
                        </div>
                    </div>

                    <!-- Social sign-in (future scope) -->
                    <div class="tab-pane fade" id="wc_tab_social" role="tabpanel">
                        <h4 class="fw-bold mb-1">Social sign-in <span class="badge badge-light-info ms-2 fs-8 align-middle">Coming soon</span></h4>
                        <div class="text-muted fs-7 mb-5">Keys are stored now; the login page does not offer these buttons yet. Saved secrets are never shown again.</div>
                        <div class="row g-5" id="wc_social"></div>
                        <?php wcSaveButton( 'social', 'Save social sign-in' ); ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Website Configuration-->

<input type="file" class="d-none" id="wc_file" accept=".png,.jpg,.jpeg,.webp,.gif,.ico,image/png,image/jpeg,image/webp,image/gif,image/x-icon" />

<script src="plugins/website_configuration/js/website_configuration.js?v=<?= @filemtime( $wc_js ) ?>"></script>
