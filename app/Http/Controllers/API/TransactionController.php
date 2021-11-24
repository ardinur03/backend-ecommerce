<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RecursiveIterator;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items.product'])->find($id);
            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Transaction berhasil di ambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Transaction tidak ditemukan',
                    404
                );
            }
        }

        $transaction = Transaction::with(['items.product'])->where('user_id', Auth::user()->id);
        if ($status) {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Transaksi berhasil di ambil'
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items'         => 'required|array',
            'items.*.id'    => 'exists:products.id',
            'total_price'   => 'required',
            'shipping_price' => 'required',
            'status'        => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPER',
        ]);

        $transaction = Transaction::create([
            'user_id'        => Auth::user()->id,
            'address'        => $request->address,
            'total_price'    => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status'         => $request->status,
        ]);

        foreach ($request->items as $product) {
            TransactionItem::create([
                'user_id'        => Auth::user()->id,
                'product_id'     => $product['id'],
                'transaction_id' => $transaction->id,
                'quantity'       => $product['quantity'],
            ]);
        }

        return ResponseFormatter::success(
            $transaction->load('items.product'),
            'Transaksi berhasil dibuat'
        );
    }
}
