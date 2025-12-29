<?php

namespace App\Http\Controllers;

use PDF;
use App\Models\Produk;
use App\Models\Kategori;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kategori = Kategori::all()->pluck('nama_kategori', 'id_kategori');

        return view('produk.index', compact('kategori'));
    }

    public function data()
    {
        $produk = Produk::leftJoin('kategori', 'kategori.id_kategori', 'produk.id_kategori')
            ->select('produk.*', 'nama_kategori')
            // ->orderBy('kode_produk', 'asc')
            ->get();

        $datatable = datatables()
            ->of($produk)
            ->addIndexColumn();
        
        // Only add select_all and aksi columns for admin
        if (auth()->user()->level == 1) {
            $datatable->addColumn('select_all', function ($produk) {
                return '
                    <input type="checkbox" name="id_produk[]" value="'. $produk->id_produk .'">
                ';
            });
            
            $datatable->addColumn('aksi', function ($produk) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="editForm(`'. route('produk.update', $produk->id_produk) .'`)" class="btn btn-xs btn-primary btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="deleteData(`'. route('produk.destroy', $produk->id_produk) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                    <button type="button" onclick="showAddStockForm(`'. $produk->id_produk .'`, `'. $produk->nama_produk .'`, `'. $produk->stok .'`)" class="btn btn-xs btn-success btn-flat"><i class="fa fa-plus"></i></button>
                </div>
                ';
            });
        } else {
            // Add action column for non-admin users (add stock button)
            $datatable->addColumn('aksi', function ($produk) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="showAddStockForm(`'. $produk->id_produk .'`, `'. $produk->nama_produk .'`, `'. $produk->stok .'`)" class="btn btn-xs btn-success btn-flat"><i class="fa fa-plus"></i></button>
                </div>
                ';
            });
        }
        
        return $datatable
            ->addColumn('kode_produk', function ($produk) {
                return '<span class="label label-success">'. $produk->kode_produk .'</span>';
            })
            ->addColumn('harga_beli', function ($produk) {
                return number_format($produk->harga_beli, 0, '.', ',');
            })
            ->addColumn('harga_jual', function ($produk) {
                return number_format($produk->harga_jual, 0, '.', ',');
            })
            ->addColumn('stok', function ($produk) {
                $stok = format_uang($produk->stok);
                if ($produk->stok == 0) {
                    return '<span class="label label-danger">SOLD OUT</span>';
                }
                return $stok;
            })
            ->rawColumns(auth()->user()->level == 1 ? ['aksi', 'kode_produk', 'select_all', 'stok'] : ['aksi', 'kode_produk', 'stok'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $produk = Produk::latest()->first() ?? new Produk();
        $request['kode_produk'] = 'P'. tambah_nol_didepan((int)$produk->id_produk +1, 6);

        $produk = Produk::create($request->all());

        return response()->json('Data saved successfully', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $produk = Produk::find($id);

        return response()->json($produk);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
        $produk->update($request->all());

        return response()->json('Data saved successfully', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $produk = Produk::find($id);
        $produk->delete();

        return response(null, 204);
    }

    public function deleteSelected(Request $request)
    {
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $produk->delete();
        }

        return response(null, 204);
    }
    // visit "codeastro" for more projects!
    public function cetakBarcode(Request $request)
    {
        $dataproduk = array();
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $dataproduk[] = $produk;
        }

        $no  = 1;
        $pdf = PDF::loadView('produk.barcode', compact('dataproduk', 'no'));
        $pdf->setPaper('a4', 'potrait');
        return $pdf->stream('product.pdf');
    }

    public function viewAllBarcode()
{
    $dataproduk = Produk::all(); // get all products
    $no = 1;

    return view('produk.all_barcode', compact('dataproduk', 'no'));
}

    public function addStock(Request $request, $id)
    {
        $request->validate([
            'stock_to_add' => 'required|numeric|min:1'
        ]);

        $produk = Produk::findOrFail($id);
        $produk->stok += $request->stock_to_add;
        $produk->update();

        return response()->json([
            'message' => 'Stock added successfully',
            'new_stock' => $produk->stok
        ], 200);
    }

}
