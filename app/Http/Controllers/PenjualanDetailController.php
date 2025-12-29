<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Produk;
use App\Models\Setting;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;

class PenjualanDetailController extends Controller
{
    public function index()
    {
        $produk = Produk::orderBy('nama_produk')->get();
        $member = Member::orderBy('nama')->get();
        $diskon = Setting::first()->diskon ?? 0;

        // Check whether there are any transactions in progress
        if ($id_penjualan = session('id_penjualan')) {
            $penjualan = Penjualan::find($id_penjualan);
            $memberSelected = $penjualan->member ?? new Member();
            
            // Calculate average discount from items if transaction discount is 0 or null
            $transactionDiskon = $penjualan->diskon ?? 0;
            if ($transactionDiskon == 0) {
                $details = PenjualanDetail::where('id_penjualan', $id_penjualan)->get();
                if ($details->count() > 0) {
                    $totalDiskon = 0;
                    $itemCount = 0;
                    foreach ($details as $detail) {
                        if ($detail->diskon > 0) {
                            $totalDiskon += $detail->diskon;
                            $itemCount++;
                        }
                    }
                    if ($itemCount > 0) {
                        $transactionDiskon = round($totalDiskon / $itemCount, 2);
                    }
                }
            }

            return view('penjualan_detail.index', compact('produk', 'member', 'diskon', 'id_penjualan', 'penjualan', 'memberSelected', 'transactionDiskon'));
        } else {
            if (auth()->user()->level == 1) {
                // tsansaction/checkout blade
                return redirect()->route('transaksi.baru');
            } else {
                return redirect()->route('home');
            }
        }
    }

    public function data($id)
    {
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', $id)
            ->get();

        $data = array();
        $total = 0;
        $total_item = 0;
        $total_diskon = 0;
        $item_count = 0;

        foreach ($detail as $item) {
            $row = array();
            $row['kode_produk'] = '<span class="label label-success">'. $item->produk['kode_produk'] .'</span';
            $row['nama_produk'] = $item->produk['nama_produk'];
            $row['harga_jual']  = '₦ '. format_uang($item->harga_jual);
            $row['jumlah']      = '<input type="number" class="form-control input-sm quantity" data-id="'. $item->id_penjualan_detail .'" value="'. $item->jumlah .'">';
            $row['diskon']      = '<input type="number" class="form-control input-sm discount-input" data-id="'. $item->id_penjualan_detail .'" value="'. $item->diskon .'" min="0" max="100" step="0.01">';
            $row['subtotal']    = '₦ '. format_uang($item->subtotal);
            $row['aksi']        = '<div class="btn-group">
                                    <button onclick="deleteData(`'. route('transaksi.destroy', $item->id_penjualan_detail) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                                </div>';
            $data[] = $row;

            $total += $item->harga_jual * $item->jumlah - (($item->diskon * $item->jumlah) / 100 * $item->harga_jual);;
            $total_item += $item->jumlah;
            if ($item->diskon > 0) {
                $total_diskon += $item->diskon;
                $item_count++;
            }
        }
        
        $avg_diskon = ($item_count > 0) ? round($total_diskon / $item_count, 2) : 0;
        
        $data[] = [
            'kode_produk' => '
                <div class="total hide">'. $total .'</div>
                <div class="total_item hide">'. $total_item .'</div>
                <div class="avg_diskon hide">'. $avg_diskon .'</div>',
            'nama_produk' => '',
            'harga_jual'  => '',
            'jumlah'      => '',
            'diskon'      => '',
            'subtotal'    => '',
            'aksi'        => '',
        ];

        return datatables()
            ->of($data)
            ->addIndexColumn()
            ->rawColumns(['aksi', 'kode_produk', 'jumlah', 'diskon'])
            ->make(true);
    }

    public function store(Request $request)
    {
        // $produk = Produk::where('id_produk', $request->id_produk)->first();

  // Try to find product by ID first, then by kode_produk (barcode)
  $produk = Produk::where('id_produk', $request->id_produk)
  ->orWhere('kode_produk', $request->kode_produk)
  ->first();
  
        if (! $produk) {
            return response()->json('Data failed to save', 400);
        }

        // Check if product is out of stock
        if ($produk->stok <= 0) {
            return response()->json('Product is SOLD OUT. Please contact admin to restock.', 400);
        }

        $detail = new PenjualanDetail();
        $detail->id_penjualan = $request->id_penjualan;
        $detail->id_produk = $produk->id_produk;
        $detail->harga_jual = $produk->harga_jual;
        $detail->jumlah = 1;
        $detail->diskon = $produk->diskon;
        $detail->subtotal = $produk->harga_jual - ($produk->diskon / 100 * $produk->harga_jual);;
        $detail->save();

        return response()->json('Data saved successfully', 200);
    }
    // visit "codeastro" for more projects!
    public function update(Request $request, $id)
    {
        $detail = PenjualanDetail::find($id);
        
        // Update quantity if provided
        if ($request->has('jumlah')) {
            $detail->jumlah = $request->jumlah;
        }
        
        // Update discount if provided
        if ($request->has('diskon')) {
            $diskon = floatval($request->diskon);
            // Validate discount range
            if ($diskon < 0) $diskon = 0;
            if ($diskon > 100) $diskon = 100;
            $detail->diskon = $diskon;
        }
        
        // Recalculate subtotal with current values
        $detail->subtotal = $detail->harga_jual * $detail->jumlah - (($detail->diskon * $detail->jumlah) / 100 * $detail->harga_jual);
        $detail->update();
        
        return response()->json(['message' => 'Data updated successfully'], 200);
    }

    public function destroy($id)
    {
        $detail = PenjualanDetail::find($id);
        $detail->delete();

        return response(null, 204);
    }

    public function loadForm($diskon = 0, $total = 0, $diterima = 0)
    {
        $bayar   = $total - ($diskon / 100 * $total);
        $kembali = ($diterima != 0) ? $diterima - $bayar : 0;
        $data    = [
            'totalrp' => format_uang($total),
            'bayar' => $bayar,
            'bayarrp' => format_uang($bayar),
            'terbilang' => ucwords(terbilang($bayar). ' Naira'),
            'kembalirp' => format_uang($kembali),
            'kembali_terbilang' => ucwords(terbilang($kembali). ' Naira'),
        ];

        return response()->json($data);
    }
}
// visit "codeastro" for more projects!