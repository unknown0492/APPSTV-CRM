                    <!--begin::Footer-->
                    <div class="footer py-4 d-flex flex-lg-column" id="kt_footer">
                        <!--begin::Container-->
                        <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
                            <!--begin::Copyright-->
                            <!-- Copyright line and links: Administrator > Website Configuration (1.0.58) -->
                            <div class="site-copyright text-gray-900 order-2 order-md-1">
                                <?php include INC_PATH . "/copyright-string.php"; ?>
                            </div>
                            <!--end::Copyright-->
                            <!--begin::Menu-->
                            <?php $site_footer_links = siteFooterLinks(); if( count( $site_footer_links ) > 0 ){ ?>
                            <ul class="site-footer-links menu menu-gray-600 menu-hover-primary fw-semibold order-1">
                                <?php foreach( $site_footer_links as $site_footer_link ){ ?>
                                <li class="menu-item">
                                    <a href="<?= htmlspecialchars( $site_footer_link[ 'url' ] ) ?>" target="_blank" rel="noopener" class="menu-link px-2"><?= htmlspecialchars( $site_footer_link[ 'label' ] ) ?></a>
                                </li>
                                <?php } ?>
                            </ul>
                            <?php } ?>
                            <?php $site_footer_css = siteFooterCss(); if( $site_footer_css !== '' ){ ?><style><?= $site_footer_css ?></style><?php } ?>
                            <!--end::Menu-->
                        </div>
                        <!--end::Container-->
                    </div>
                    <!--end::Footer-->