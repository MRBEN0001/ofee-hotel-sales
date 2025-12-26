<?php

namespace App\Http\Controllers;

use PDF;
use App\Models\Produk;
use App\Models\Setting;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;

class PenjualanController extends Controller
{
    public function index()
    {
        // Check if today is the 1st of the month
        $isFirstOfMonth = true; // For testing - always show the link
        
        // Calculate previous month's date range
        $previousMonth = date('m', strtotime('first day of last month'));
        $previousYear = date('Y', strtotime('first day of last month'));
        $startDate = date('Y-m-01', strtotime("$previousYear-$previousMonth-01"));
        $endDate = date('Y-m-t', strtotime("$previousYear-$previousMonth-01"));
        
        return view('penjualan.index', compact('isFirstOfMonth', 'startDate', 'endDate'));
    }

    public function data()
    {
        // Only show completed transactions (where items were actually sold and payment was made)
        $penjualan = Penjualan::with(['detail.produk', 'user'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->orderBy('id_penjualan', 'desc')
            ->get();

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('total_harga', function ($penjualan) {
                return '₦ '. format_uang($penjualan->total_harga);
            })
            ->addColumn('bayar', function ($penjualan) {
                return '₦ '. format_uang($penjualan->bayar);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('products', function ($penjualan) {
                $products = $penjualan->detail->map(function($detail) {
                    return $detail->produk->nama_produk ?? 'N/A';
                })->unique()->take(3)->implode(', ');
                
                if ($penjualan->detail->count() > 3) {
                    $products .= '... (+' . ($penjualan->detail->count() - 3) . ' more)';
                }
                
                return $products ?: '-';
            })
            ->addColumn('room_details', function ($penjualan) {
                return $penjualan->room_unique_details ?? '-';
            })
            ->addColumn('phone_number', function ($penjualan) {
                return $penjualan->phone_number ?? '-';
            })
            ->addColumn('receipt_number', function ($penjualan) {
                return $penjualan->receipt_number ?? '-';
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . '%';
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                $buttons = '<div class="btn-group">';
                $buttons .= '<button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-primary btn-flat"><i class="fa fa-eye"></i></button>';
                
                // Only show delete button for admin
                if (auth()->user()->level == 1) {
                    $buttons .= '<button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>';
                }
                
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
    // visit "codeastro" for more projects!
    public function create()
    {
        $penjualan = new Penjualan();
        $penjualan->id_member = null;
        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->id_user = auth()->id();
        $penjualan->save();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        return redirect()->route('transaksi.index');
    }

    public function store(Request $request)
    {
        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        $penjualan->id_member = $request->id_member;
        $penjualan->room_unique_details = $request->room_unique_details ?? null;
        $penjualan->phone_number = $request->phone_number ?? null;
        
        // Generate unique receipt number if not already set
        if (empty($penjualan->receipt_number)) {
            do {
                $receipt_number = 'RCP-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8)) . '-' . date('Ymd');
            } while (Penjualan::where('receipt_number', $receipt_number)->exists());
            
            $penjualan->receipt_number = $receipt_number;
        }
        
        $penjualan->total_item = $request->total_item;
        $penjualan->total_harga = $request->total;
        $penjualan->diskon = $request->diskon;
        $penjualan->bayar = $request->bayar;
        $penjualan->diterima = $request->diterima;
        $penjualan->update();

        $detail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $item->diskon = $request->diskon;
            $item->update();

            $produk = Produk::find($item->id_produk);
            $produk->stok -= $item->jumlah;
            $produk->update();
        }

        return redirect()->route('transaksi.selesai');
    }

    public function show($id)
    {
        $detail = PenjualanDetail::with('produk')->where('id_penjualan', $id)->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">'. $detail->produk->kode_produk .'</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('harga_jual', function ($detail) {
                return '₦ '. format_uang($detail->harga_jual);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah);
            })
            ->addColumn('subtotal', function ($detail) {
                return '₦ '. format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }
    // visit "codeastro" for more projects!
    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        $detail    = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok += $item->jumlah;
                $produk->update();
            }

            $item->delete();
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function selesai()
    {
        $setting = Setting::first();

        return view('penjualan.selesai', compact('setting'));
    }

    public function notaKecil()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();
        
        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail'));
    }

    public function notaBesar()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        $pdf->setPaper(0,0,609,440, 'potrait');
        return $pdf->stream('Transaction-'. date('Y-m-d-his') .'.pdf');
    }

    public function monthlyReportPDF(Request $request)
    {
        // Increase execution time for PDF generation
        set_time_limit(300); // 5 minutes
        
        // Get dates from request or use previous month
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        
        // If dates not provided, use previous month
        if (!$startDate || !$endDate) {
            $previousMonth = date('m', strtotime('first day of last month'));
            $previousYear = date('Y', strtotime('first day of last month'));
            $startDate = date('Y-m-01', strtotime("$previousYear-$previousMonth-01"));
            $endDate = date('Y-m-t', strtotime("$previousYear-$previousMonth-01"));
        }

        // Optimize query - use select to only get needed columns
        $transactions = Penjualan::select('id_penjualan', 'created_at', 'receipt_number', 'room_unique_details', 'total_item', 'total_harga', 'diskon', 'bayar', 'id_user')
            ->with(['detail:id_penjualan_detail,id_penjualan,id_produk,jumlah,subtotal', 'detail.produk:id_produk,nama_produk', 'user:id,name'])
            ->where('total_item', '>', 0)
            ->where('bayar', '>', 0)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate totals efficiently
        $grandTotal = $transactions->sum('bayar');
        $totalTransactions = $transactions->count();

        // Get all details efficiently using a single query
        $transactionIds = $transactions->pluck('id_penjualan')->toArray();
        $allDetails = PenjualanDetail::select('id_penjualan_detail', 'id_penjualan', 'id_produk', 'jumlah', 'subtotal')
            ->with('produk:id_produk,nama_produk')
            ->whereIn('id_penjualan', $transactionIds)
            ->get();

        $totalItems = $allDetails->sum('jumlah');

        // Group products and calculate totals efficiently
        $productSummary = [];
        foreach ($allDetails as $detail) {
            $productName = $detail->produk->nama_produk ?? 'N/A';
            if (!isset($productSummary[$productName])) {
                $productSummary[$productName] = [
                    'quantity' => 0,
                    'total' => 0
                ];
            }
            $productSummary[$productName]['quantity'] += $detail->jumlah;
            $productSummary[$productName]['total'] += $detail->subtotal;
        }

        // Sort products by name
        ksort($productSummary);

        $monthName = date('F Y', strtotime($startDate));
        
        // Get setting for hotel name
        $setting = Setting::first();
        
        // Generate PDF
        $pdf = PDF::loadView('penjualan.monthly_report', compact(
            'transactions', 
            'allDetails', 
            'productSummary', 
            'grandTotal', 
            'totalTransactions', 
            'totalItems',
            'startDate',
            'endDate',
            'monthName',
            'setting'
        ));
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'Monthly-Sales-Report-' . date('F-Y', strtotime($startDate)) . '.pdf';
        return $pdf->download($filename);
    }
}
// visit "codeastro" for more projects!