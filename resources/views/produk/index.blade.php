@extends('layouts.master')

@section('title')
    Product List
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Product List</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            @if(auth()->user()->level == 1)
            <div class="box-header with-border">
                <div class="btn-group">
                    <button onclick="addForm('{{ route('produk.store') }}')" class="btn btn-success  btn-flat"><i class="fa fa-plus-circle"></i> Add New Product</button>
                    <button onclick="deleteSelected('{{ route('produk.delete_selected') }}')" class="btn btn-danger  btn-flat"><i class="fa fa-trash"></i> Delete</button>
                    {{-- <button onclick="cetakBarcode('{{ route('produk.cetak_barcode') }}')" class="btn btn-warning  btn-flat"><i class="fa fa-barcode"></i> Print Barcode</button> --}}
                </div>

               

                <form id="barcodeForm" action="{{ route('produk.cetak_barcode') }}" method="POST" target="_blank" style="margin-bottom: 15px;">
                    @csrf
                    <input type="hidden" name="id_produk[]" id="selectedProducts">
                    <button type="button" class="btn btn-primary" onclick="submitBarcodeForm()">
                        <i class="fa fa-barcode"></i> Print Barcode
                    </button>
                </form>
                
                
            </div>
            @else
            <div class="box-header with-border">
                <div class="btn-group">
                    <button onclick="addForm('{{ route('produk.add_product') }}')" class="btn btn-success btn-flat"><i class="fa fa-plus-circle"></i> Add New Product</button>
                </div>
            </div>
            @endif
            <div class="box-body table-responsive">
                <form action="" method="post" class="form-produk">
                    @csrf
                    <table class="table table-stiped table-bordered table-hover">
                        <thead>
                            @if(auth()->user()->level == 1)
                            <th width="5%">
                                <input type="checkbox" name="select_all" id="select_all">
                            </th>
                            @endif
                            <th width="5%">#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Discount</th>
                            <th>Stock</th>
                            <th width="15%"><i class="fa fa-cog"></i></th>
                        </thead>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

@includeIf('produk.form')

<!-- Add Stock Modal -->
<div class="modal fade" id="modal-add-stock" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Add Stock</h4>
            </div>
            <div class="modal-body">
                <form id="form-add-stock">
                    @csrf
                    <input type="hidden" id="add-stock-product-id" name="product_id">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" id="add-stock-product-name" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="text" id="add-stock-current-stock" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="stock_to_add">Stock to Add <span class="text-danger">*</span></label>
                        <input type="number" name="stock_to_add" id="stock_to_add" class="form-control" min="1" required autofocus>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-flat" onclick="submitAddStock()">Add Stock</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table;

    $(function () {
        @php
            $isAdmin = auth()->user()->level == 1;
            $columns = [];
            if ($isAdmin) {
                $columns[] = ['data' => 'select_all', 'searchable' => false, 'sortable' => false];
            }
            $columns = array_merge($columns, [
                ['data' => 'DT_RowIndex', 'searchable' => false, 'sortable' => false],
                ['data' => 'kode_produk'],
                ['data' => 'nama_produk'],
                ['data' => 'nama_kategori'],
                ['data' => 'merk'],
                ['data' => 'harga_beli'],
                ['data' => 'harga_jual'],
                ['data' => 'diskon'],
                ['data' => 'stok'],
                ['data' => 'aksi', 'searchable' => false, 'sortable' => false],
            ]);
        @endphp
        
        table = $('.table').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('produk.data') }}',
            },
            columns: @json($columns),
            language: {
                search: "Search all columns:"
            }
        });

        $('#modal-form').validator().on('submit', function (e) {
            if (! e.preventDefault()) {
                $.post($('#modal-form form').attr('action'), $('#modal-form form').serialize())
                    .done((response) => {
                        $('#modal-form').modal('hide');
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        alert('Unable to save data');
                        return;
                    });
            }
        });

        $('[name=select_all]').on('click', function () {
            $(':checkbox').prop('checked', this.checked);
        });
    });

    function addForm(url) {
        $('#modal-form').modal('show');
        $('#modal-form .modal-title').text('Add Product');

        $('#modal-form form')[0].reset();
        $('#modal-form form').attr('action', url);
        $('#modal-form [name=_method]').val('post');
        $('#modal-form [name=nama_produk]').focus();
    }

    function editForm(url) {
        $('#modal-form').modal('show');
        $('#modal-form .modal-title').text('Edit Product');

        $('#modal-form form')[0].reset();
        $('#modal-form form').attr('action', url);
        $('#modal-form [name=_method]').val('put');
        $('#modal-form [name=nama_produk]').focus();

        $.get(url)
            .done((response) => {
                $('#modal-form [name=nama_produk]').val(response.nama_produk);
                $('#modal-form [name=id_kategori]').val(response.id_kategori);
                $('#modal-form [name=merk]').val(response.merk);
                $('#modal-form [name=harga_beli]').val(response.harga_beli);
                $('#modal-form [name=harga_jual]').val(response.harga_jual);
                $('#modal-form [name=diskon]').val(response.diskon);
                $('#modal-form [name=stok]').val(response.stok);
            })
            .fail((errors) => {
                alert('Unable to display data');
                return;
            });
    }

    function deleteData(url) {
        if (confirm('Are you sure you want to delete selected data?')) {
            $.post(url, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'delete'
                })
                .done((response) => {
                    table.ajax.reload();
                })
                .fail((errors) => {
                    alert('Unable to delete data');
                    return;
                });
        }
    }

    function deleteSelected(url) {
        if ($('input:checked').length > 1) {
            if (confirm('Yakin ingin menghapus data terpilih?')) {
                $.post(url, $('.form-produk').serialize())
                    .done((response) => {
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        alert('Unable to delete data');
                        return;
                    });
            }
        } else {
            alert('Select the data to delete');
            return;
        }
    }

    function cetakBarcode(url) {
        if ($('input:checked').length < 1) {
            alert('Select the data to print');
            return;
        } else if ($('input:checked').length < 3) {
            alert('Select at least 3 data to print');
            return;
        } else {
            $('.form-produk')
                .attr('target', '_blank')
                .attr('action', url)
                .submit();
        }


    }



    
    function submitBarcodeForm() {
    const selected = $('input[name="id_produk[]"]:checked')
        .map(function () { return this.value; })
        .get();

    if (selected.length === 0) {
        alert('Please select at least one product to print.');
        return;
    }

    // Dynamically build hidden inputs
    const form = $('#barcodeForm');
    form.find('input[name="id_produk[]"]').remove(); // clear previous ones

    selected.forEach(id => {
        form.append(`<input type="hidden" name="id_produk[]" value="${id}">`);
    });

    form.submit();
}

    function showAddStockForm(id, name, currentStock) {
        $('#add-stock-product-id').val(id);
        $('#add-stock-product-name').val(name);
        $('#add-stock-current-stock').val(currentStock);
        $('#stock_to_add').val('');
        $('#modal-add-stock').modal('show');
        $('#stock_to_add').focus();
    }

    function submitAddStock() {
        let productId = $('#add-stock-product-id').val();
        let stockToAdd = $('#stock_to_add').val();

        if (!stockToAdd || stockToAdd < 1) {
            alert('Please enter a valid stock amount (minimum 1)');
            return;
        }

        $.post(`{{ url('/produk') }}/${productId}/add-stock`, {
            '_token': $('[name=csrf-token]').attr('content'),
            'stock_to_add': stockToAdd
        })
        .done((response) => {
            $('#modal-add-stock').modal('hide');
            table.ajax.reload();
            alert('Stock added successfully! New stock: ' + response.new_stock);
        })
        .fail((errors) => {
            let errorMessage = 'Unable to add stock';
            if (errors.responseJSON && errors.responseJSON.message) {
                errorMessage = errors.responseJSON.message;
            }
            alert(errorMessage);
        });
    }

</script>
@endpush