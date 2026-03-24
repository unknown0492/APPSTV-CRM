<?php 
    $page = getPageInformation( $page_name );
    $breadcrumbs = getBreadcrumbHierarchy( $page_name );
?>
                        <!--begin::Toolbar-->
                        <div class="toolbar" id="kt_toolbar">
                            <!--begin::Container-->
                            <div id="kt_toolbar_container" class="container-fluid d-flex ">
                                <!--begin::Page title-->
                                    <!--begin::Title-->
                                    <h1 class="d-flex justify-content-start text-gray-900 fw-bold my-1 fs-3">
                                        <?=($url_params==false)?"Administrator Panel":(($page==NULL)?"Administrator Panel":$page->page_title) ?>
                                    </h1>
                                    <!--end::Title-->
                                    <!--begin::Separator-->
                                    <span class="h-20px border-gray-200 border-start mx-4"></span>
                                    <!--end::Separator-->
                                    <!--begin::Breadcrumb-->
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                                        <!--begin::Item-->
                                        <li class="breadcrumb-item text-muted">
                                            <a href="<?=WEBSITE_ADMINPANEL_URL ?>" class="text-muted text-hover-primary">Dashboard</a>
                                        </li>
                                        <!--end::Item-->
                                        
                                        <?php 
                                        if( $breadcrumbs != NULL ){
                                            for( $i = count( $breadcrumbs ) - 1 ; $i >= 0 ; $i-- ){
                                                if( $i != 0 ){
                                        ?>
                                        <!--begin::Item-->
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-300 w-5px h-2px"></span>
                                        </li>
                                        <!--end::Item-->
                                        <!--begin::Item-->
                                        <li class="breadcrumb-item text-muted"><?=$breadcrumbs[ $i ][ 'page_title' ] ?></li>
                                        <!--end::Item-->
                                        <!--begin::Item-->
                                        <li class="breadcrumb-item">
                                            <span class="bullet bg-gray-300 w-5px h-2px"></span>
                                        </li>
                                        <!--end::Item-->
                                        <?php
                                                }
                                                else{
                                        ?>
                                        <!--begin::Item-->
                                        <li class="breadcrumb-item text-gray-900"><?=$breadcrumbs[ $i ][ 'page_title' ] ?></li>
                                        <!--end::Item-->
                                        <?php
                                                }
                                            }
                                        }
                                        ?>
                                        
                                        
                                        
                                    </ul>
                                    <!--end::Breadcrumb-->
                                
                                <!--end::Page title-->
                            </div>
                            <!--end::Container-->
                        </div>
                        <!--end::Toolbar-->