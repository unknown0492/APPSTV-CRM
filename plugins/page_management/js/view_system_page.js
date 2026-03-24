var pgDeleteCM;
var table;
var csm;
$( document ).ready( function(){
	pgDeleteCM = new ConfirmModal( "Confirm", "Are you sure you want to delete this page group ? The pages present in this group will be moved to the \"Other Pages\" group ", "Yes", "No" );
	csm = new ConfirmModal( "Caution !", "Are you sure you want to delete this page ? This operation cannot be reverted !", "Yes", "No" );

	table = $( '#view-system-page-table' ).DataTable({
		retrieve : true
	});
	
	$( '#functionality_id' ).selectpicker();
	
});

var _for = "";
var _thees = "";
var _page_id = "";

function deletePage( page_id, thees ){
	showConfirmModal( "Confirm", "Are you sure you want to delete this page ? Deleting the page will also delete the corresponding functionality. We recommend you to use the Edit Page, in case you want to edit the page/functionality information ! ", "Yes", "No" );
	_for = "delete_page";
	_thees = thees;
	_page_id = page_id;
}

function deletePageGroup( thees ){
	pgDeleteCM.showConfirm();
	pgDeleteCM.positiveFunction = function(){
		
		//console.log( thees.id );
		var fsa = new FormSubmitAnimator( thees.id );
		fsa.showLoading();
		
		var page_group_id = $( '#selected_page_group_id' ).val();
		// alert( page_group_id );
		
		$.ajax({
			url  : 'webservice.php',
			type : 'POST',
			data :  { 'what_do_you_want': 'delete_page_group', 'page_group_id' : page_group_id },
			success : function( returned_data ){
				console.log( returned_data );
				fsa.hideLoading();
				
				var jSon = parseJSONWithError( returned_data, "Server side script error. Please contact the Administrator !" );
				if( jSon == false ) return;
				jSon = jSon[ 0 ];
				
				if( jSon[ 'type' ] == "error" ){
					showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 0 );
					
					return;
				}
				if( jSon[ 'type' ] == "success" ){
					showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 0 );
					
					$( '#selected_page_group_id' ).val( '0' );
					
					setTimeout( function(){
						refreshPage();
					}, 2000 );
					
					return;
				}
				
			}
		});
		
	};
	
	
}

function editPage( page_id, thees ){
	post( 'adminpanel.php?what_do_you_want=edit_system_page', { 'page_id' : page_id, 'page_group_id' : $( '#selected_page_group_id' ).val() } );
}

function yesClicked( forr ){
	if( _for == "delete_page" ){
		$( _thees ).attr( 'disabled', "disabled" );
		
		$.ajax({
			url  : 'webservice.php',
			type : 'POST',
			data :  { 'what_do_you_want': 'delete_page', 'page_id' : _page_id },
			success : function( returned_data ){
				console.log( returned_data );
				var jSon = $.parseJSON( returned_data );
				
				$( _thees ).removeAttr( 'disabled' );
				
				$.each( jSon, function(){
					if( this[ 'type' ] == "error" ){
						showModal( "error", this[ 'info' ] );
						return;
					}
					if( this[ 'type' ] == "success" ){
						showModal( "success", this[ 'info' ] );
						$( _thees ).attr( 'disabled', "disabled" );
						$( _thees ).parent().parent().remove();
						return;
					}
				});
			}
		});
	}
	else if( _for == "delete_page_group" ){
		$( _thees ).attr( 'disabled', "disabled" );
		
		$.ajax({
			url  : 'webservice.php',
			type : 'POST',
			data :  { 'what_do_you_want': 'delete_page_group', 'page_group_id' : _page_group_id },
			success : function( returned_data ){
				console.log( returned_data );
				var jSon = $.parseJSON( returned_data );
				
				$( _thees ).removeAttr( 'disabled' );
				
				$.each( jSon, function(){
					if( this[ 'type' ] == "error" ){
						showModal( "error", this[ 'info' ] );
						return;
					}
					if( this[ 'type' ] == "success" ){
						showModal( "success", this[ 'info' ] );
						setTimeout( function(){
							refreshPage();
						}, 2000 );
						return;
					}
				});
			}
		});
	}
}


function showTableLoading(){
	$( '.table-loading' ).removeClass( 'hidden' );
	$( '#view-system-page-table' ).addClass( 'hidden' );
}

function hideTableLoading(){
	$( '.table-loading' ).addClass( 'hidden' );
	$( '#view-system-page-table' ).removeClass( 'hidden' );
}


var edit_page_app = angular.module( 'edit_page_app', [] );
edit_page_app.controller( 'edit_page_ctrl', function( $scope, $compile ){
	
	$scope.selected_page_group_id = "0";
	angular.element( document ).ready( function(){
		$scope.changePageGroup( 'selected_page_group_id' );
    });
	
	$scope.changePageGroup = function( id ){
		var selected_page_group_id = $( '#selected_page_group_id' ).val();
		// alert( selected_page_group_id );
		// post( 'adminpanel.php?what_do_you_want=view_privileges_page', { 'selected_privilege_group_id' : selected_privilege_group_id } );
		
		// Show Loading Icon
		showTableLoading();
		
		// Empty the DataTable
		table.destroy();
		$( '#view-system-page-table > tbody' ).html( "" );
		
		$.ajax({
			url : 'webservice.php',
			type : 'POST',
			data : { what_do_you_want : 'get_pages_from_selected_group', page_group_id : selected_page_group_id },
			success : function( returned_data ){
				console.log( returned_data );
				
				// Hide Loading Icon
				hideTableLoading();
				
				var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ) return;
	    		
	    		jSon = jSon[ 0 ];
	    		if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
	    			table = $( '#view-system-page-table' ).DataTable( {
					    retrieve: true,
					});
	    			return;
	    		}
				if( jSon[ 'type' ] == "success" ){
					
					var json = $.parseJSON( jSon[ 'info' ] );
					var count = 1;
					var data = "";
					$.each( json, function(){
						// console.log( this );
						var visible = (this[ 'visible' ]=="1")?'<span class="label label-success">Visible</span>':'<span class="label label-danger">Not Visible</span>';
						data += "<tr data-item-id=\""+ this[ 'page_id' ] +"\">" +
							"<td>" + (count++) + "</td>" + 
							"<td data-page-id=\""+ this[ 'page_id' ] +"\">" + this[ 'page_name' ] + "</td>" + 
							"<td>" + this[ 'page_sequence' ] + "</td>" + 
							"<td><span class=\""+this[ 'icon' ]+"\"> </span></td>" + 
							"<td>" + visible + "</td>" + 
							"<td>" + this[ 'functionality_name' ] + "</td>" + 
							"<td style=\"text-align: center; width: 100px;\">" + 
								"<a data-toggle=\"modal\" href=\"#edit-page-modal\" style=\"margin: 2px;\" class=\"btn green\" ng-click=\"editPage( '"+ this[ 'page_id' ] +"', this );\" title=\"Edit Page\" alt=\"Edit Page\"><i class=\"fa fa-pencil fa-lg\"> </i></a><a style=\"margin: 2px;\" class=\"btn red\" ng-click=\"deletePage( '"+ this[ 'page_id' ] +"', this );\" title=\"Delete Page\" alt=\"Delete Page\"><i class=\"fa fa-trash-o fa-lg\"> </i></a>" + 
							"</td>"
						"</tr>";
						var $data = $( data ).appendTo( '#view-system-page-table tbody' );
						$compile($data)($scope);
						//$( '#view-privilege-table tbody' ).append( data );
						
						data = "";
					});
					table = $( '#view-system-page-table' ).DataTable( {
					    retrieve: true,
					});
					
					return;
				}
			}
		});
		
	}
	
	$scope.editPage = function( page_id, thees ){
		// console.log( "page id : " + page_id );
		// console.log( thees );
		
		hideMe( "content", "class" );
		showMe( "loading_page_info", "class" );
		
		$.ajax({
			url : 'webservice.php',
			type : 'POST',
			data : { what_do_you_want : 'get_page_info', 'page_id' : page_id },
			success : function( returned_data ){
				console.log( returned_data );
				
				hideMe( "loading_page_info", "class" );
				showMe( "content", "class" );
				
				var jSon = parseJSONWithError( returned_data, "Server side error occurred !" );
				if( jSon == false ){
					// Hide Modal
	    			$( '#edit-page-modal' ).modal( 'hide' );
					return;
				}
				jSon = jSon[ 0 ];
				if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
	    			
	    			// Hide Modal
	    			$( '#edit-page-modal' ).modal( 'hide' );
	    			
	    			return;
	    		}
				
				if( jSon[ 'type' ] == "success" ){
					
					var json = $.parseJSON( jSon[ 'info' ] );
					json = json[ 0 ];
					
					console.log( json[ 'page_name' ] );
					
					var page_id				= json[ 'page_id' ];
					var page_name			= json[ 'page_name' ];
					var page_sequence		= json[ 'page_sequence' ];
					var icon				= json[ 'icon' ];
					var visible				= json[ 'visible' ];
					var page_title			= json[ 'page_title' ];
					var functionality_id	= json[ 'functionality_id' ];
					var page_group_id		= json[ 'page_group_id' ];
					var description			= json[ 'description' ];
					var tags				= json[ 'tags' ];
					var image				= json[ 'image' ];
					var content				= json[ 'content' ];
					var plugin_id			= json[ 'plugin_id' ];
					var is_page				= json[ 'is_page' ];
					
					$scope.page_name = page_name;
					$( '#page_name' ).val( page_name );
					
					$scope.page_id = page_id;
					$( '#page_id' ).val( page_id );
					
					$scope.page_sequence = page_sequence;
					$( '#page_sequence' ).val( page_sequence );
					
					$scope.page_icon = icon;
					$( '#page_icon' ).val( icon );
					
					visible = (visible=="1")?true:false;
					var is_visible = $( '#is_visible' ).bootstrapSwitch( 'state', visible );
				
					$scope.page_title = page_title;
					$( '#page_title' ).val( page_title );
					
					$scope.functionality_id = functionality_id;
					$( '#functionality_id' ).val( functionality_id );
					$( '#functionality_id' ).selectpicker( 'refresh' );
					
					$scope.page_group_id = page_group_id;
					$( '#page_group_id' ).val( page_group_id );
					
					$scope.description = description;
					$( '#description' ).val( description );
					
					$scope.tags = tags;
					$( '#tags' ).val( tags );
					
					$scope.image = image;
					$( '#image' ).val( image );
					
					$scope.content = content;
					$( '#content' ).val( content );
					
					$scope.plugin_id = plugin_id;
					$( '#plugin_id' ).val( plugin_id );
					
					$scope.$apply(function(){});
					
					
				}
				
			}
		});
		
	}
	
	$scope.updatePage = function( form, $event ){
		$event.preventDefault();
		
		console.log( form );
		
		if( form.$invalid ){
			showNotification( "error", "bottomRight", "Some of the form fields are invalid !", 5000, 0 );
			return;
		}
		
		var fsa = new FormSubmitAnimator( "submit_" + form.$name );
		fsa.showLoading();
		
		var is_visible = ( $( '#is_visible' ).bootstrapSwitch( 'state' ) )?"1":"0";
		
		$.ajax({
			url : 'webservice.php',
			type : 'POST',
			data : $( '#' + form.$name ).serialize() + "&is_visible="+is_visible+"&what_do_you_want=update_page",
			success : function( returned_data ){
				console.log( returned_data );
				
				fsa.hideLoading();
				
				var jSon = parseJSONWithError( returned_data, "Server side error occurred !" );
				if( jSon == false ){ return; }
				jSon = jSon[ 0 ];
				
				if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
	    			return;
	    		}
				
				if( jSon[ 'type' ] == "success" ){
					showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
					
					// Hide Modal
	    			$( '#edit-page-modal' ).modal( 'hide' );
	    			
	    			$scope.changePageGroup( 'selected_page_group_id' );
	    			
					return;
				}
			}
		});
		
	}
	
	$scope.deletePage = function( page_id, thees ){
		csm.showConfirm();
		
		csm.positiveFunction = function(){
			
			$.ajax({
				url : 'webservice.php',
				type : 'POST',
				data : "page_id="+page_id+"&what_do_you_want=delete_page",
				success : function( returned_data ){
					console.log( returned_data );
					
					var jSon = parseJSONWithError( returned_data, "Server side error occurred !" );
					if( jSon == false ){ return; }
					jSon = jSon[ 0 ];
					
					if( jSon[ 'type' ] == "error" ){
		    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
		    			return;
		    		}
					
					if( jSon[ 'type' ] == "success" ){
						showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
						
						table.row( $( '#view-system-page-table tr[data-item-id="'+ page_id +'"]' ) ).remove();
						$( '#view-system-page-table tr[data-item-id="'+ page_id +'"]' ).remove();
						
						
						return;
					}
					
				}
			});
		}
	}
	
} );