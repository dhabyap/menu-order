<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductViewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string', 'max:80'],
        ]);

        $updated = DB::table('products')
            ->where('prototype_id', $validated['product_id'])
            ->increment('view_count');

        return response()->json(['tracked' => $updated > 0], $updated > 0 ? 202 : 404);
    }
}
