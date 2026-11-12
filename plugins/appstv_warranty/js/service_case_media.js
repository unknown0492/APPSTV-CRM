"use strict";

/* =============================================
   service_case_media.js (v1.0.22)
   Plugin: appstv_warranty - photos and videos of a service case or draft

   SCMedia: one gallery + uploader in a container (the wizard's Photos & videos step, the case window's tab).
     var g = new SCMedia( $container, { target: function () { return { case_id: 12 } }, editable: true, onChange: fn } );
     g.setMedia( list );           // [ { id, kind, state, name, size, has_thumb, error, ... } ] from the server
   Adding: pick several files; photos can be rotated / cropped first (Cropper.js), then each photo is reduced to
   1600 px JPEG in the browser; "Upload" sends every file in 1.5 MB pieces (4 pieces at a time, 2 files at a time,
   3 retries per piece) with a % bar per file. Videos up to 500 MB go as they are and are converted on the server.
   SCMViewer: full-screen viewer of the photos / videos of a gallery (previous / next, download).
   ============================================= */

/**
 * @description Gallery + uploader of one case or draft
 * @param {jQuery} $box - Container (filled by the gallery)
 * @param {object} options - target: function returning { case_id } or { draft_id } (may return a Promise);
 *                           editable: bool; onChange: function( media ) after an upload / removal
 * @returns {void}
 */
function SCMedia( $box, options ) {
    this.$box = $box;                                          // Container
    this.options = $.extend( { editable: true, onChange: function () {} }, options );
    this.media = [];                                           // Photos / videos on the server
    this.queue = [];                                           // Files picked, not uploaded yet: { id, file, kind, url, rotate, edited, state, progress, error }
    this.running = 0;                                          // Files uploading now
    this.seq = 0;                                              // Local ids of queued files
    this.build();
}

SCMedia.PHOTO_PX = 1600;                                       // Long side of a sent photo
SCMedia.JPEG_QUALITY = 0.82;                                   // JPEG quality of a sent photo
SCMedia.MAX_VIDEO = 500 * 1024 * 1024;                         // Largest video
SCMedia.PARALLEL_PARTS = 4;                                    // Pieces of one file in flight
SCMedia.PARALLEL_FILES = 2;                                    // Files uploading at once
SCMedia.RETRIES = 3;                                           // Extra tries of one piece

/**
 * @description URL of a stored photo / video / thumbnail (the sign-in cookie goes with it)
 * @param {number} id - Media id
 * @param {string} variant - file | thumb
 * @param {boolean} download - Ask the browser to save it
 * @returns {string}
 */
SCMedia.url = function ( id, variant, download ) {
    return getWebservice() + '?what_do_you_want=appstv_case_media_file&media_id=' + id + '&variant=' + ( variant || 'file' ) + ( download ? '&download=1' : '' );
};

/**
 * @description Readable size
 * @param {number} bytes - Size
 * @returns {string}
 */
SCMedia.size = function ( bytes ) {
    if ( bytes >= 1048576 ) { return ( bytes / 1048576 ).toFixed( bytes >= 10485760 ? 0 : 1 ) + ' MB'; }
    return Math.max( 1, Math.round( bytes / 1024 ) ) + ' KB';
};

/**
 * @description Draws the frame: add button, queue, gallery
 * @returns {void}
 */
SCMedia.prototype.build = function () {
    var self = this;
    var html = '';
    if ( this.options.editable ) {
        html += '<div class="scm-add">'
             + '<label class="btn btn-light-primary scm-add-btn"><i class="fa-solid fa-camera me-2"></i>Add photos / videos'
             + '<input type="file" class="d-none scm-input" accept="image/*,video/*" multiple /></label>'
             + '<div class="text-muted fs-7">Photos are reduced to 1600 px; videos up to 500 MB are converted to under 25 MB.</div></div>'
             + '<div class="scm-queue d-none"><div class="scm-queue-head"><div class="fw-semibold">Ready to upload <span class="scm-queue-n"></span></div>'
             + '<div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-light scm-clear">Remove all</button>'
             + '<button type="button" class="btn btn-sm btn-primary scm-start"><i class="fa-solid fa-cloud-arrow-up me-1"></i>Upload</button></div></div>'
             + '<div class="scm-grid scm-queue-grid"></div></div>';
    }
    html += '<div class="scm-grid scm-gallery"></div><div class="scm-empty text-muted fs-7 d-none">No photos or videos yet.</div>';
    this.$box.html( html );

    this.$box.on( 'change', '.scm-input', function () {
        self.add( this.files );
        $( this ).val( '' );
    } );
    this.$box.on( 'click', '.scm-start', function () { self.start(); } );
    this.$box.on( 'click', '.scm-clear', function () {
        self.queue = self.queue.filter( function ( q ) { return q.state === 'uploading'; } );
        self.drawQueue();
    } );
    this.$box.on( 'click', '[data-q-remove]', function () { self.removeQueued( +$( this ).attr( 'data-q-remove' ) ); } );
    this.$box.on( 'click', '[data-q-rotate]', function () { self.rotateQueued( +$( this ).attr( 'data-q-rotate' ) ); } );
    this.$box.on( 'click', '[data-q-crop]', function () { self.cropQueued( +$( this ).attr( 'data-q-crop' ) ); } );
    this.$box.on( 'click', '[data-q-retry]', function () { var q = self.find( +$( this ).attr( 'data-q-retry' ) ); if ( q ) { q.state = 'ready'; q.error = ''; self.start(); } } );
    this.$box.on( 'click', '[data-m-view]', function () { SCMViewer.open( self.media, +$( this ).attr( 'data-m-view' ) ); } );
    this.$box.on( 'click', '[data-m-delete]', function ( e ) { e.stopPropagation(); self.remove( +$( this ).attr( 'data-m-delete' ) ); } );
};

/**
 * @description Shows the stored photos / videos
 * @param {Array} list - Media from the server
 * @returns {void}
 */
SCMedia.prototype.setMedia = function ( list ) {
    this.media = list || [];
    this.drawGallery();
};

/**
 * @description Number of photos / videos (stored + waiting)
 * @returns {number}
 */
SCMedia.prototype.count = function () { return this.media.length; };

/**
 * @description True while files wait or upload
 * @returns {boolean}
 */
SCMedia.prototype.busy = function () { return this.queue.length > 0; };

/**
 * @description Draws the stored photos / videos
 * @returns {void}
 */
SCMedia.prototype.drawGallery = function () {
    var self = this;
    var html = $.map( this.media, function ( m ) {
        var thumb = m.has_thumb ? '<img src="' + SCMedia.url( m.id, 'thumb' ) + '" alt="" loading="lazy"/>'
                                : '<div class="scm-ph"><i class="fa-solid ' + ( m.kind === 'video' ? 'fa-film' : 'fa-image' ) + '"></i></div>';
        var badge = '';
        if ( m.kind === 'video' && ( m.state === 'queued' || m.state === 'processing' ) ) { badge = '<span class="scm-badge scm-badge-wait"><span class="spinner-border spinner-border-sm me-1"></span>Converting…</span>'; }
        if ( m.state === 'failed' ) { badge = '<span class="scm-badge scm-badge-fail" title="' + SC.esc( m.error ) + '">Not converted</span>'; }
        return '<div class="scm-tile" data-m-view="' + m.id + '" role="button" tabindex="0" title="' + SC.esc( m.name ) + '">' + thumb
             + ( m.kind === 'video' ? '<span class="scm-play"><i class="fa-solid fa-play"></i></span>' : '' ) + badge
             + ( self.options.editable ? '<button type="button" class="scm-del" data-m-delete="' + m.id + '" aria-label="Remove" title="Remove"><i class="fa-solid fa-trash-can"></i></button>' : '' )
             + '</div>';
    } ).join( '' );
    this.$box.find( '.scm-gallery' ).html( html );
    this.$box.find( '.scm-empty' ).toggleClass( 'd-none', this.media.length > 0 || this.queue.length > 0 );
};

/**
 * @description Adds picked files to the queue (photos and videos only)
 * @param {FileList} files - Picked files
 * @returns {void}
 */
SCMedia.prototype.add = function ( files ) {
    var self = this;
    var refused = [];
    $.each( files, function ( i, file ) {
        var kind = /^video\//.test( file.type ) ? 'video' : ( /^image\//.test( file.type ) || /\.(heic|heif)$/i.test( file.name ) ? 'photo' : '' );
        if ( !kind ) { refused.push( file.name + ' (not a photo or video)' ); return; }
        if ( kind === 'video' && file.size > SCMedia.MAX_VIDEO ) { refused.push( file.name + ' (larger than 500 MB)' ); return; }
        self.queue.push( { id: ++self.seq, file: file, kind: kind, url: kind === 'photo' ? URL.createObjectURL( file ) : '', rotate: 0, edited: null,
                           state: 'ready', progress: 0, error: '' } );
    } );
    if ( refused.length ) { Swal.fire( { icon: 'warning', title: 'Not added', html: refused.map( SC.esc ).join( '<br/>' ), buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } ); }
    this.drawQueue();
};

/**
 * @description A queued file by its local id
 * @param {number} id - Local id
 * @returns {object|undefined}
 */
SCMedia.prototype.find = function ( id ) { return this.queue.filter( function ( q ) { return q.id === id; } )[ 0 ]; };

/**
 * @description Draws the queue (preview, rotate / crop / remove, % while uploading)
 * @returns {void}
 */
SCMedia.prototype.drawQueue = function () {
    var waiting = this.queue.filter( function ( q ) { return q.state === 'ready' || q.state === 'failed'; } ).length;
    this.$box.find( '.scm-queue' ).toggleClass( 'd-none', this.queue.length === 0 );
    this.$box.find( '.scm-queue-n' ).text( '(' + this.queue.length + ')' );
    this.$box.find( '.scm-start' ).prop( 'disabled', waiting === 0 ).html( '<i class="fa-solid fa-cloud-arrow-up me-1"></i>Upload' + ( waiting ? ' ' + waiting + ' file' + ( waiting > 1 ? 's' : '' ) : '' ) );
    this.$box.find( '.scm-queue-grid' ).html( $.map( this.queue, function ( q ) {
        var preview = q.kind === 'photo'
            ? '<img src="' + ( q.edited ? q.edited.url : q.url ) + '" alt="" style="transform: rotate(' + ( q.edited ? 0 : q.rotate ) + 'deg)"/>'
            : '<div class="scm-ph"><i class="fa-solid fa-film"></i><div class="fs-8 mt-1">' + SC.esc( SCMedia.size( q.file.size ) ) + '</div></div>';
        var actions = '';
        if ( q.state === 'ready' ) {
            actions = '<div class="scm-tools">'
                    + ( q.kind === 'photo' ? '<button type="button" data-q-rotate="' + q.id + '" title="Rotate" aria-label="Rotate"><i class="fa-solid fa-rotate-right"></i></button>'
                                           + '<button type="button" data-q-crop="' + q.id + '" title="Crop / rotate" aria-label="Crop"><i class="fa-solid fa-crop-simple"></i></button>' : '' )
                    + '<button type="button" data-q-remove="' + q.id + '" title="Remove" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button></div>';
        }
        var status = '';
        if ( q.state === 'uploading' ) { status = '<div class="scm-progress"><div style="width:' + q.progress + '%"></div></div><span class="scm-badge">' + q.progress + '%</span>'; }
        if ( q.state === 'preparing' ) { status = '<span class="scm-badge"><span class="spinner-border spinner-border-sm me-1"></span>Preparing…</span>'; }
        if ( q.state === 'failed' ) {
            status = '<span class="scm-badge scm-badge-fail" title="' + SC.esc( q.error ) + '">Failed</span>'
                   + '<div class="scm-tools"><button type="button" data-q-retry="' + q.id + '" title="Try again" aria-label="Try again"><i class="fa-solid fa-rotate"></i></button>'
                   + '<button type="button" data-q-remove="' + q.id + '" title="Remove" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button></div>';
        }
        return '<div class="scm-tile is-queued" data-q-id="' + q.id + '" title="' + SC.esc( q.file.name + ( q.error ? ': ' + q.error : '' ) ) + '">' + preview + status + actions + '</div>';
    } ).join( '' ) );
    this.$box.find( '.scm-empty' ).toggleClass( 'd-none', this.media.length > 0 || this.queue.length > 0 );
};

/**
 * @description Updates the % of one uploading file without redrawing the queue
 * @param {object} q - Queued file
 * @returns {void}
 */
SCMedia.prototype.drawProgress = function ( q ) {
    var $tile = this.$box.find( '[data-q-id="' + q.id + '"]' );
    $tile.find( '.scm-progress > div' ).css( 'width', q.progress + '%' );
    $tile.find( '.scm-badge' ).text( q.progress + '%' );
};

/**
 * @description Removes a queued file
 * @param {number} id - Local id
 * @returns {void}
 */
SCMedia.prototype.removeQueued = function ( id ) {
    var q = this.find( id );
    if ( q && q.url ) { URL.revokeObjectURL( q.url ); }
    this.queue = this.queue.filter( function ( x ) { return x.id !== id; } );
    this.drawQueue();
};

/**
 * @description Turns a queued photo by 90 degrees (a cropped photo is turned in the editor instead)
 * @param {number} id - Local id
 * @returns {void}
 */
SCMedia.prototype.rotateQueued = function ( id ) {
    var q = this.find( id );
    if ( !q ) { return; }
    if ( q.edited ) { this.cropQueued( id ); return; }
    q.rotate = ( q.rotate + 90 ) % 360;
    this.drawQueue();
};

/**
 * @description Opens the crop / rotate editor of a queued photo
 * @param {number} id - Local id
 * @returns {void}
 */
SCMedia.prototype.cropQueued = function ( id ) {
    var self = this;
    var q = this.find( id );
    if ( !q ) { return; }
    SCMedia.decode( q.file ).then( function ( canvas ) {
        SCMEditor.open( canvas, q.rotate, function ( blob ) {
            if ( q.edited ) { URL.revokeObjectURL( q.edited.url ); }
            q.edited = { blob: blob, url: URL.createObjectURL( blob ) };
            q.rotate = 0;
            self.drawQueue();
        } );
    } ).catch( function () {
        Swal.fire( { icon: 'info', title: 'Cannot edit this photo here', text: 'This photo format cannot be shown in the browser (for example HEIC). It is sent as it is.', buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
    } );
};

/**
 * @description Reads a photo upright (camera orientation applied) into a canvas
 * @param {File|Blob} file - Photo
 * @returns {Promise<HTMLCanvasElement>}
 */
SCMedia.decode = function ( file ) {
    var draw = function ( source, w, h ) {
        var canvas = document.createElement( 'canvas' );
        canvas.width = w; canvas.height = h;
        canvas.getContext( '2d' ).drawImage( source, 0, 0 );
        return canvas;
    };
    if ( window.createImageBitmap ) {
        return createImageBitmap( file, { imageOrientation: 'from-image' } ).then( function ( bmp ) {
            var c = draw( bmp, bmp.width, bmp.height );
            if ( bmp.close ) { bmp.close(); }
            return c;
        } );
    }
    return new Promise( function ( resolve, reject ) {
        var img = new Image();
        var url = URL.createObjectURL( file );
        img.onload = function () { URL.revokeObjectURL( url ); resolve( draw( img, img.naturalWidth, img.naturalHeight ) ); };
        img.onerror = function () { URL.revokeObjectURL( url ); reject( new Error( 'decode' ) ); };
        img.src = url;
    } );
};

/**
 * @description The photo as it will be sent: turned, at most 1600 px, JPEG (the original when it cannot be read)
 * @param {object} q - Queued photo
 * @returns {Promise<Blob>}
 */
SCMedia.preparePhoto = function ( q ) {
    var source = q.edited ? q.edited.blob : q.file;
    return SCMedia.decode( source ).then( function ( canvas ) {
        var turn = q.edited ? 0 : q.rotate;
        var scale = Math.min( 1, SCMedia.PHOTO_PX / Math.max( canvas.width, canvas.height ) );
        var w = Math.round( canvas.width * scale ), h = Math.round( canvas.height * scale );
        var out = document.createElement( 'canvas' );
        var side = ( turn === 90 || turn === 270 );
        out.width = side ? h : w; out.height = side ? w : h;
        var ctx = out.getContext( '2d' );
        ctx.fillStyle = '#fff';
        ctx.fillRect( 0, 0, out.width, out.height );
        ctx.translate( out.width / 2, out.height / 2 );
        ctx.rotate( turn * Math.PI / 180 );
        ctx.drawImage( canvas, -w / 2, -h / 2, w, h );
        return new Promise( function ( resolve ) { out.toBlob( function ( blob ) { resolve( blob || source ); }, 'image/jpeg', SCMedia.JPEG_QUALITY ); } );
    } ).catch( function () { return source; } );   // Sent as it is; the server says when it cannot read it
};

/**
 * @description Uploads every waiting file (2 at a time)
 * @returns {void}
 */
SCMedia.prototype.start = function () {
    var self = this;
    var waiting = this.queue.filter( function ( q ) { return q.state === 'ready'; } );
    if ( !waiting.length ) { return; }
    Promise.resolve( this.options.target() ).then( function ( target ) {
        if ( !target || !( target.case_id || target.draft_id ) ) { throw 'Save the case first.'; }
        var next = function () {
            while ( self.running < SCMedia.PARALLEL_FILES ) {
                var q = self.queue.filter( function ( x ) { return x.state === 'ready'; } )[ 0 ];
                if ( !q ) { return; }
                self.running++;
                self.uploadOne( q, target ).then( function () { self.running--; next(); } );
            }
        };
        next();
    } ).catch( function ( message ) { SC.toastError( message ); } );
};

/**
 * @description Uploads one file: start, pieces, finish
 * @param {object} q - Queued file
 * @param {object} target - { case_id } or { draft_id }
 * @returns {Promise} Always resolves (failures are shown on the tile)
 */
SCMedia.prototype.uploadOne = function ( q, target ) {
    var self = this;
    q.state = q.kind === 'photo' ? 'preparing' : 'uploading';
    q.progress = 0;
    this.drawQueue();
    var blob, info;
    return ( q.kind === 'photo' ? SCMedia.preparePhoto( q ) : Promise.resolve( q.file ) ).then( function ( b ) {
        blob = b;
        q.state = 'uploading';
        self.drawQueue();
        var name = q.kind === 'photo' && blob.type === 'image/jpeg' ? q.file.name.replace( /\.[^.]+$/, '' ) + '.jpg' : q.file.name;
        return SC.post( $.extend( { what_do_you_want: 'appstv_case_media_upload', step: 'start', kind: q.kind, name: name, size: blob.size, type: blob.type || q.file.type }, target ) );
    } ).then( function ( started ) {
        info = started.data;
        return self.sendParts( q, blob, info );
    } ).then( function () {
        return SC.post( { what_do_you_want: 'appstv_case_media_upload', step: 'finish', media_id: info.media_id, key: info.key } );
    } ).then( function ( finished ) {
        if ( q.url ) { URL.revokeObjectURL( q.url ); }
        if ( q.edited ) { URL.revokeObjectURL( q.edited.url ); }
        self.queue = self.queue.filter( function ( x ) { return x.id !== q.id; } );
        self.media = self.media.concat( finished.data.media );
        self.drawQueue();
        self.drawGallery();
        self.options.onChange( self.media );
    } ).catch( function ( message ) {
        q.state = 'failed';
        q.error = String( message );
        self.drawQueue();
    } );
};

/**
 * @description Sends the pieces of a file, 4 at a time, each retried up to 3 times
 * @param {object} q - Queued file (progress updated)
 * @param {Blob} blob - Contents
 * @param {object} info - { media_id, key, chunk_size, chunks }
 * @returns {Promise}
 */
SCMedia.prototype.sendParts = function ( q, blob, info ) {
    var self = this;
    var nextIndex = 0, done = 0, failed = null;
    var sendOne = function ( index, tries ) {
        var fd = new FormData();
        fd.append( 'what_do_you_want', 'appstv_case_media_upload' );
        fd.append( 'step', 'part' );
        fd.append( 'media_id', info.media_id );
        fd.append( 'key', info.key );
        fd.append( 'index', index );
        fd.append( 'chunk', blob.slice( index * info.chunk_size, Math.min( blob.size, ( index + 1 ) * info.chunk_size ) ), 'part' );
        return new Promise( function ( resolve, reject ) {
            $.ajax( { url: getWebservice(), type: 'POST', data: fd, processData: false, contentType: false, timeout: 120000,
                success: function ( raw ) {
                    var j = parseJSONWithError( raw, '' );
                    if ( j && j[ 0 ] && j[ 0 ].type === 'success' ) { resolve(); return; }
                    reject( j && j[ 0 ] && j[ 0 ].info && j[ 0 ].info.message ? j[ 0 ].info.message : 'Server error' );
                },
                error: function () { reject( 'Connection lost' ); } } );
        } ).catch( function ( message ) {
            if ( tries >= SCMedia.RETRIES ) { throw message; }
            return new Promise( function ( r ) { setTimeout( r, 1500 * ( tries + 1 ) ); } ).then( function () { return sendOne( index, tries + 1 ); } );
        } );
    };
    return new Promise( function ( resolve, reject ) {
        var pump = function () {
            if ( failed !== null ) { return; }
            if ( done === info.chunks ) { resolve(); return; }
            while ( nextIndex < info.chunks && nextIndex - done < SCMedia.PARALLEL_PARTS ) {
                ( function ( index ) {
                    sendOne( index, 0 ).then( function () {
                        done++;
                        q.progress = Math.round( done / info.chunks * 100 );
                        self.drawProgress( q );
                        pump();
                    } ).catch( function ( message ) { failed = message; reject( message ); } );
                } )( nextIndex++ );
            }
        };
        pump();
    } );
};

/**
 * @description Removes a stored photo / video after confirmation
 * @param {number} id - Media id
 * @returns {void}
 */
SCMedia.prototype.remove = function ( id ) {
    var self = this;
    var m = this.media.filter( function ( x ) { return x.id === id; } )[ 0 ];
    if ( !m ) { return; }
    showConfirmSweetAlert( 'Remove this ' + ( m.kind === 'video' ? 'video' : 'photo' ) + '?', SC.esc( m.name ), 'warning', 'Remove', 'btn btn-danger', 'Cancel', 'btn btn-light', function () {
        SC.post( { what_do_you_want: 'appstv_case_media_delete', media_id: id } ).then( function ( info ) {
            self.media = self.media.filter( function ( x ) { return x.id !== id; } );
            self.drawGallery();
            self.options.onChange( self.media );
            showSimpleToast( 'success', info.message );
        } ).catch( SC.toastError );
    } );
};


/* =============================================
   SCMEditor - crop / rotate one photo (Cropper.js), opened above the current window
   ============================================= */
var SCMEditor = {

    cropper : null,                                            // Cropper instance
    onDone : null,                                             // Called with the edited JPEG blob

    /**
     * @description Opens the editor
     * @param {HTMLCanvasElement} canvas - Upright photo
     * @param {number} rotate - Turn already chosen (degrees)
     * @param {function} onDone - Called with the edited Blob
     * @returns {void}
     */
    open : function ( canvas, rotate, onDone ) {
        SCMEditor.ensure();
        SCMEditor.onDone = onDone;
        var $img = $( '#scm_editor_img' );
        $img.attr( 'src', canvas.toDataURL( 'image/jpeg', 0.92 ) );
        var modal = bootstrap.Modal.getOrCreateInstance( document.getElementById( 'scm_editor' ) );
        $( '#scm_editor' ).one( 'shown.bs.modal', function () {
            if ( SCMEditor.cropper ) { SCMEditor.cropper.destroy(); }
            SCMEditor.cropper = new Cropper( $img[ 0 ], { viewMode: 1, autoCropArea: 1, background: false, checkOrientation: false,
                ready: function () { if ( rotate ) { SCMEditor.cropper.rotateTo( rotate ); } } } );
        } );
        modal.show();
    },

    /**
     * @description Adds the editor's markup to the page once
     * @returns {void}
     */
    ensure : function () {
        if ( $( '#scm_editor' ).length ) { return; }
        $( 'body' ).append( '<div class="modal fade" id="scm_editor" tabindex="-1" data-bs-backdrop="static" aria-label="Crop or rotate the photo">'
            + '<div class="modal-dialog modal-lg modal-fullscreen-md-down modal-dialog-centered"><div class="modal-content">'
            + '<div class="modal-header py-3"><h4 class="modal-title">Crop / rotate</h4>'
            + '<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fs-2"></i></button></div>'
            + '<div class="modal-body"><div class="scm-editor-stage"><img id="scm_editor_img" alt=""/></div></div>'
            + '<div class="modal-footer py-3 justify-content-between">'
            + '<div class="d-flex gap-2"><button type="button" class="btn btn-light btn-icon" data-scm-edit="left" title="Turn left" aria-label="Turn left"><i class="fa-solid fa-rotate-left"></i></button>'
            + '<button type="button" class="btn btn-light btn-icon" data-scm-edit="right" title="Turn right" aria-label="Turn right"><i class="fa-solid fa-rotate-right"></i></button>'
            + '<button type="button" class="btn btn-light" data-scm-edit="reset">Reset</button></div>'
            + '<button type="button" class="btn btn-primary" data-scm-edit="done">Done</button></div></div></div></div>' );
        $( '#scm_editor' ).on( 'click', '[data-scm-edit]', function () {
            var c = SCMEditor.cropper;
            if ( !c ) { return; }
            var what = $( this ).attr( 'data-scm-edit' );
            if ( what === 'left' ) { c.rotate( -90 ); }
            if ( what === 'right' ) { c.rotate( 90 ); }
            if ( what === 'reset' ) { c.reset(); }
            if ( what === 'done' ) {
                c.getCroppedCanvas( { maxWidth: SCMedia.PHOTO_PX, maxHeight: SCMedia.PHOTO_PX, fillColor: '#fff', imageSmoothingQuality: 'high' } ).toBlob( function ( blob ) {
                    bootstrap.Modal.getOrCreateInstance( document.getElementById( 'scm_editor' ) ).hide();
                    if ( blob && SCMEditor.onDone ) { SCMEditor.onDone( blob ); }
                }, 'image/jpeg', SCMedia.JPEG_QUALITY );
            }
        } );
        $( '#scm_editor' ).on( 'hidden.bs.modal', function () {
            if ( SCMEditor.cropper ) { SCMEditor.cropper.destroy(); SCMEditor.cropper = null; }
            $( '#scm_editor_img' ).attr( 'src', '' );
            if ( $( '.modal.show' ).length ) { $( 'body' ).addClass( 'modal-open' ); }   // The window below stays scrollable
        } );
    }
};


/* =============================================
   SCMViewer - full-screen viewer of a gallery's photos / videos
   ============================================= */
var SCMViewer = {

    list : [],                                                 // Media shown
    index : 0,                                                 // Position shown

    /**
     * @description Opens the viewer on one item
     * @param {Array} list - Media of the gallery
     * @param {number} id - Media id to show first
     * @returns {void}
     */
    open : function ( list, id ) {
        SCMViewer.ensure();
        SCMViewer.list = list;
        SCMViewer.index = Math.max( 0, list.map( function ( m ) { return m.id; } ).indexOf( id ) );
        SCMViewer.show();
        bootstrap.Modal.getOrCreateInstance( document.getElementById( 'scm_viewer' ) ).show();
    },

    /**
     * @description Shows the current item
     * @returns {void}
     */
    show : function () {
        var m = SCMViewer.list[ SCMViewer.index ];
        if ( !m ) { return; }
        var stage = m.kind === 'video'
            ? '<video src="' + SCMedia.url( m.id, 'file' ) + '" controls playsinline preload="metadata"' + ( m.has_thumb ? ' poster="' + SCMedia.url( m.id, 'thumb' ) + '"' : '' ) + '></video>'
            : '<img src="' + SCMedia.url( m.id, 'file' ) + '" alt="' + SC.esc( m.name ) + '"/>';
        $( '#scm_viewer_stage' ).html( stage );
        var note = '';
        if ( m.kind === 'video' && ( m.state === 'queued' || m.state === 'processing' ) ) { note = ' · converting, the original is shown'; }
        if ( m.state === 'failed' ) { note = ' · not converted (' + m.error + '), the original is shown'; }
        $( '#scm_viewer_title' ).text( m.name );
        $( '#scm_viewer_meta' ).text( ( SCMViewer.index + 1 ) + ' of ' + SCMViewer.list.length + ' · ' + SCMedia.size( m.size || m.source_size ) + ' · '
                                      + ( m.uploaded_by || '' ) + ' · ' + SC.showStamp( m.uploaded_on ) + note );
        $( '#scm_viewer_download' ).attr( 'href', SCMedia.url( m.id, 'file', true ) );
        $( '#scm_viewer [data-scm-nav]' ).toggleClass( 'd-none', SCMViewer.list.length < 2 );
    },

    /**
     * @description Moves to the previous / next item
     * @param {number} step - -1 or 1
     * @returns {void}
     */
    move : function ( step ) {
        var n = SCMViewer.list.length;
        SCMViewer.index = ( SCMViewer.index + step + n ) % n;
        SCMViewer.show();
    },

    /**
     * @description Adds the viewer's markup once
     * @returns {void}
     */
    ensure : function () {
        if ( $( '#scm_viewer' ).length ) { return; }
        $( 'body' ).append( '<div class="modal fade" id="scm_viewer" tabindex="-1" aria-labelledby="scm_viewer_title">'
            + '<div class="modal-dialog modal-fullscreen"><div class="modal-content scm-viewer">'
            + '<div class="modal-header py-3 border-0"><div class="min-w-0"><h4 class="modal-title text-white text-truncate" id="scm_viewer_title"></h4><div class="fs-8 scm-viewer-meta" id="scm_viewer_meta"></div></div>'
            + '<div class="d-flex gap-2"><a class="btn btn-sm btn-icon btn-dark" id="scm_viewer_download" title="Download" aria-label="Download"><i class="fa-solid fa-download"></i></a>'
            + '<button type="button" class="btn btn-sm btn-icon btn-dark" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fs-2"></i></button></div></div>'
            + '<div class="modal-body p-0 position-relative"><div class="scm-viewer-stage" id="scm_viewer_stage"></div>'
            + '<button type="button" class="scm-nav scm-nav-prev" data-scm-nav="-1" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>'
            + '<button type="button" class="scm-nav scm-nav-next" data-scm-nav="1" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button></div></div></div></div>' );
        $( '#scm_viewer' ).on( 'click', '[data-scm-nav]', function () { SCMViewer.move( +$( this ).attr( 'data-scm-nav' ) ); } );
        $( '#scm_viewer' ).on( 'keydown', function ( e ) { if ( e.key === 'ArrowLeft' ) { SCMViewer.move( -1 ); } if ( e.key === 'ArrowRight' ) { SCMViewer.move( 1 ); } } );
        $( '#scm_viewer' ).on( 'hidden.bs.modal', function () {
            $( '#scm_viewer_stage' ).empty();                    // Stops a playing video
            if ( $( '.modal.show' ).length ) { $( 'body' ).addClass( 'modal-open' ); }
        } );
    }
};
