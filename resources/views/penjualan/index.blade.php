@extends('layouts.master')

@section('title')
    Sales List
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Sales List</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-tabs">
            <li class="active"><a href="{{ route('penjualan.index') }}">Sales List</a></li>
            <li><a href="{{ route('penjualan.daily_sales') }}">Daily Sales</a></li>
            <li><a href="{{ route('penjualan.daily_room_sales') }}">Daily Room Sales</a></li>
        </ul>
    </div>
</div>
<br>

@if($isFirstOfMonth && auth()->user()->level == 1)
<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-info alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-info"></i> Monthly Sales Report Available!</h4>
            <p>The monthly sales report for {{ date('F Y', strtotime($startDate)) }} is now available for download.</p>
            <a href="{{ route('penjualan.monthly_report', ['startDate' => $startDate, 'endDate' => $endDate]) }}" class="btn btn-primary btn-lg" target="_blank">
                <i class="fa fa-download"></i> Download Monthly Report PDF
            </a>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body table-responsive">
                <table class="table table-stiped table-bordered table-penjualan table-hover">
                    <thead>
                        <th width="5%">#</th>
                        <th>Date</th>
                        <th>Products</th>
                        <th>Category</th>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Receipt ID</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Discount</th>
                        <th>Total Pay</th>
                        <th>Cashier</th>
                        <th width="15%"><i class="fa fa-cog"></i></th>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- visit "codeastro" for more projects! -->
@includeIf('penjualan.detail')
@endsection

@push('scripts')
<script>
    let table, table1;

    $(function () {
        table = $('.table-penjualan').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('penjualan.data') }}',
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'tanggal'},
                {data: 'products'},
                {data: 'category'},
                {data: 'room_details'},
                {data: 'phone_number'},
                {data: 'receipt_number'},
                {data: 'total_item'},
                {data: 'total_harga'},
                {data: 'diskon'},
                {data: 'bayar'},
                {data: 'kasir'},
                {data: 'aksi', searchable: false, sortable: false},
            ]
        });

        table1 = $('.table-detail').DataTable({
            processing: true,
            bSort: false,
            dom: 'Brt',
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'subtotal'},
            ]
        })
    });

    function showDetail(url) {
        $('#modal-detail').modal('show');

        table1.ajax.url(url);
        table1.ajax.reload();
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
</script>
@endpush