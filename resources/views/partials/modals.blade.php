{{--}}
    Vista parcial con el html para la ventana modal,
    y el código javascript para realizar las operaciones
    básicas: Create, Update and Delete
{{--}}
<div class="modal fade draggable" role="dialog" aria-hidden="true" id="modal_crud" data-bs-keyboard="true" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header d-block">
                <div class="row g-0">
                    <div class="col">
                        <h5 class="modal-title my-0" id="modal-title"></h5>
                        <small class="text-muted my-0" id="modal-subtitle"><i></i></small>
                    </div>
                    <div class="col-auto text-end">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer wrapper" data-route="">
                <button type="button" class="btn btn-secondary pull-left" data-bs-dismiss="modal">Salir<kbd class="d-none d-md-inline ms-2">ESC</kbd></button>
                <button type="button" class="btn btn-primary" id="item-action">Acción</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Funcion  que quita las clases que empiezan con el filtro indicado
    $.fn.removeClassStartingWith = function (filter) {
        $(this).removeClass(function (index, className) {
            return (className.match(new RegExp("\\S*" + filter + "\\S*", 'g')) || []).join(' ')
        });
        return this;
    };
    var table_id = '';

    // When button with class: "item-edit" is clicked then display form in modal
    // If button has "data-id" attribute use it's value to retrieve corresponding record, else show empty form
    $(document).on('click', '.item-edit', function(){
        var element = $(this);
        var id = element.data('id');
        var path = element.closest('.wrapper').data('route');
        var route = (id ? ( path + '/' + id + '/edit') : path + '/create' );
        table_id = element.closest('table').attr('id') || element.closest('.wrapper').data('table');
        var data_ajax = element.data();
        $("#item-action").attr('disabled', false);
        $.ajax({
            url: route,
            type: 'GET',
            data: data_ajax,
            success: function(data) {
                $('#modal_crud .modal-dialog').addClass('modal-lg');
                $('#modal_crud #modal-title, #item-action').html(id ? 'Actualizar' : 'Registrar');
                $('#modal_crud #modal-subtitle').html('');
                if( element.data('reload') == true) {
                    $('#modal_crud #modal-title, #item-action').attr('data-reload', element.data('reload'))
                }
                $('#modal_crud .modal-body').html(data);
                $('#item-action').val('save').removeData('data-id').removeClassStartingWith('btn-').addClass('btn-primary').attr("hidden", false);
                $('#modal_crud').modal('show');
                $('#modal_crud form select').select2( {
                        dropdownParent: "#modal_crud",
                        theme: "bootstrap-5",
                        language: "es",
                        placeholder: "",
                        dropdownAutoWidth: true,
                        width: $('#modal_crud form select').data( 'width' ) ? $('#modal_crud form select').data( 'width' ) : $('#modal_crud form select').hasClass( 'w-100' ) ? '100%' : 'style',
                        minimumResultsForSearch: 4,
                    });
                $('#modal_crud input[maxlength],textarea[maxlength]').maxlength({
                    alwaysShow: true,
                    warningClass: "badge bg-success text-light",
                    limitReachedClass: "badge bg-danger text-light"
                });
            }
        });
    });

    // When button with id: "item-action" is clicked perform a set of actions based on its value
    $(document).on('click', '#item-action', function(){
        var element = $(this);
        element.attr('disabled', true)
        switch ( $(this).val() ) {
            case 'save':
                // When value is "save" then send formData to controller using ajax
                // If it fails display errors in alert, else reload datatable
                var route = $('#item-form').attr('action');
                var method = $('#item-form #id').val() ? 'PUT' : 'POST';
                var form = $('#item-form')[0];
                var formData = new FormData(form);
                $('#item-form #id').val() ? formData.append('_method', 'PUT') : '';
                $.ajax({
                    url: route,
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: formData,
                    success: function(data){
                        element.attr('disabled', false)
                        $('#modal_crud').modal('hide');
                        if(element.data('reload') == true) {
                            window.location.reload(true);
                        }
                        if(table_id) {
                            $('#' + table_id).DataTable().ajax.reload();
                        }
                    },
                    error: function(data){
                        element.attr('disabled', false)
                        var response = JSON.parse(data.responseText);
                        $('#item-alert').html('');
                        $.each( response.errors, function( key, field ) {
                            $.each( field, function( key, error ) {
                                $('#item-alert').append('<li>'+ error + '</li>');
                            })
                        });
                        $('#item-alert').removeAttr('hidden');
                    }
                });
            break;
            case 'delete':
                // When value is "delete" then
                // Reformat modal button with id: "item-action" to show save confirmation,
                // Delete item based on id and reload datatable
                var id = $('#item-action').attr('data-id');
                var path = $(this).closest('.wrapper').data('route');
                $.ajax({
                    url: path + '/' + id,
                    type: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        'id' : id,
                    },
                    success: function(data){
                        element.attr('disabled', false)
                        $('#item-action').removeAttr('data-id').removeClass('btn-danger').addClass('btn-primary').removeData();
                        if (data) {
                            $('#modal_crud #modal-title').html('Error');
                            $('#modal_crud #modal-subtitle').html('');
                            $('#modal_crud .modal-body').html(data);
                            $('#item-action').attr("hidden", true);
                        } else {
                            $('#modal_crud').modal('hide');
                            if(element.data('reload') == true) {
                                window.location.reload(true);
                            }
                            if(table_id) {
                                $('#' + table_id).DataTable().ajax.reload();
                            }
                        }
                    },
                    error: function(data){
                        element.attr('disabled', false);
                        var response = JSON.parse(data.responseText);
                        $('#item-alert').html('');
                        $.each( response.errors, function( key, value ) {
                            $('#item-alert').append('<li>'+ value + '</li>');
                        });
                        $('#item-alert').removeAttr('hidden');
                    }
                });
            break;
            case 'assign':
                //
            break;
            case 'close':
                //
            break;
        }
    });

    // When button with class: "item-delete" is clicked then 
    // Replace modal, reformat modal button with id: "item-action" to show delete confirmation and
    // Send id to delete button in modal
    $(document).on('click', '.item-delete', function(){
        var id = $(this).attr('data-id');
        var action = $(this).data('action') ?? "Eliminar";
        var btn = $(this).data('btn') ?? "btn-danger";
        var path = $(this).closest('.wrapper').data('route');
        table_id = $(this).closest('table').attr('id');
        $('#modal_crud .modal-dialog').removeClass('modal-lg');
        $('#modal_crud #modal-title, #item-action').html(action);
        $('#modal_crud #modal-subtitle').html('');
        if( $(this).data('reload') == true) {
            $('#modal_crud #modal-title, #item-action').attr('data-reload', $(this).data('reload'))
        }
        $('#modal_crud .modal-body').html('<div class="alert alert-warning" id="item-alert" hidden></div><div>Realmente desea ' + action + ' este registro?</di>');
        $('#item-action').val('delete').attr('data-id', id).removeClassStartingWith('btn-').addClass(btn).attr("hidden", false).attr('disabled', false);
        $('#modal_crud .modal-footer').data('route', path);
        $('#modal_crud').modal('show');
    });

    // Make modals draggable
    $('.modal.draggable>.modal-dialog').draggable({
        cursor: 'move',
        handle: '.modal-header'
    });
    $('.modal.draggable>.modal-dialog>.modal-content>.modal-header').css('cursor', 'move');
</script>