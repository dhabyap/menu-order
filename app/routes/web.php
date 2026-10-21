<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn () => response()->file(public_path('index.html')));
Route::get('/admin/login', fn () => auth()->check() ? redirect('/admin') : view('login'))->name('admin.login');
Route::post('/admin/login', function (\Illuminate\Http\Request $request) { $credentials = $request->validate(['email' => ['required','email'], 'password' => ['required']]); if (auth()->attempt($credentials, $request->boolean('remember'))) { $request->session()->regenerate(); return redirect()->intended('/admin'); } return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email'); })->middleware('throttle:5,1')->name('admin.login.post');
Route::post('/admin/logout', function (\Illuminate\Http\Request $request) { auth()->logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/admin/login'); })->name('admin.logout');
Route::middleware('auth')->prefix('admin')->group(function () {
    $validateProductOptions = static function (array $data): bool {
        $groups = json_decode($data['options_json'] ?? '[]', true);
        if (!is_array($groups) || !array_is_list($groups)) {
            return false;
        }
        foreach ($groups as $group) {
            if (!is_array($group) || !is_string($group['name'] ?? null) || trim($group['name']) === '' || !is_array($group['options'] ?? null) || !array_is_list($group['options']) || $group['options'] === []) {
                return false;
            }
            foreach ($group['options'] as $option) {
                if (!is_array($option) || !is_string($option['label'] ?? null) || trim($option['label']) === '' || filter_var($option['price'] ?? null, FILTER_VALIDATE_INT) === false || (int) $option['price'] < 0) {
                    return false;
                }
            }
        }
        return true;
    };
    Route::get('/', function () {
        $products = \Illuminate\Support\Facades\DB::table('products')->leftJoin('categories','categories.id','=','products.category_id')->select('products.*','categories.name as category')->orderBy('products.sort_order')->get();
        $orders = \Illuminate\Support\Facades\DB::table('orders')->latest()->get();
        $settings = \Illuminate\Support\Facades\DB::table('store_settings')->pluck('value', 'key');
        return view('admin', [
            'products' => $products,
            'orders' => $orders,
            'available' => $products->where('available', 1)->count(),
            'unavailable' => $products->where('available', 0)->count(),
            'revenue' => $orders->where('status', '!=', 'cancelled')->sum('total'),
            'settings' => $settings,
        ]);
    });
    Route::post('/settings', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'whatsapp' => ['nullable', 'regex:/^62[1-9][0-9]{7,12}$/'],
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'store_name' => ['required', 'string', 'max:120'],
            'store_tagline' => ['nullable', 'string', 'max:120'],
            'hero_heading' => ['required', 'string', 'max:160'],
            'hero_description' => ['required', 'string', 'max:500'],
            'marketing_badges' => ['nullable', 'string', 'max:1000'],
        ]);
        foreach ([
            'whatsapp' => $data['whatsapp'] ?? '',
            'delivery_fee' => (string) $data['delivery_fee'],
            'store_name' => $data['store_name'],
            'store_tagline' => $data['store_tagline'] ?? '',
            'hero_heading' => $data['hero_heading'],
            'hero_description' => $data['hero_description'],
            'marketing_badges' => $data['marketing_badges'] ?? '',
        ] as $key => $value) {
            \Illuminate\Support\Facades\DB::table('store_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        return back()->with('success', 'Pengaturan toko tersimpan.');
    })->name('admin.settings.update');
    Route::post('/products', function (\Illuminate\Http\Request $request) use ($validateProductOptions) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'options_json' => ['nullable', 'json', 'max:10000'],
            'labels' => ['nullable', 'string', 'max:200'],
        ]);
        if (!$validateProductOptions($data)) {
            return back()->withErrors(['options_json' => 'Format opsi racikan tidak valid.'])->withInput();
        }
        $slug = \Illuminate\Support\Str::slug($data['category']);
        \Illuminate\Support\Facades\DB::table('categories')->updateOrInsert(
            ['slug' => $slug],
            ['name' => $data['category'], 'active' => true, 'updated_at' => now(), 'created_at' => now()]
        );
        $categoryId = \Illuminate\Support\Facades\DB::table('categories')->where('slug', $slug)->value('id');
        \Illuminate\Support\Facades\DB::table('products')->insert([
            'category_id' => $categoryId,
            'prototype_id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'labels' => json_encode(array_values(array_filter(array_map('trim', explode(',', $data['labels'] ?? ''))))),
            'options' => ($data['options_json'] ?? '[]') ?: '[]',
            'available' => true,
            'sort_order' => \Illuminate\Support\Facades\DB::table('products')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Menu baru berhasil ditambahkan.');
    })->name('admin.products.create');
    Route::put('/products/{id}', function (\Illuminate\Http\Request $request, int $id) use ($validateProductOptions) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'options_json' => ['nullable', 'json', 'max:10000'],
            'labels' => ['nullable', 'string', 'max:200'],
        ]);
        if (!$validateProductOptions($data)) {
            return back()->withErrors(['options_json' => 'Format opsi racikan tidak valid.'])->withInput();
        }
        $slug = \Illuminate\Support\Str::slug($data['category']);
        \Illuminate\Support\Facades\DB::table('categories')->updateOrInsert(
            ['slug' => $slug],
            ['name' => $data['category'], 'active' => true, 'updated_at' => now(), 'created_at' => now()]
        );
        \Illuminate\Support\Facades\DB::table('products')->where('id', $id)->update([
            'category_id' => \Illuminate\Support\Facades\DB::table('categories')->where('slug', $slug)->value('id'),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'labels' => json_encode(array_values(array_filter(array_map('trim', explode(',', $data['labels'] ?? ''))))),
            'options' => ($data['options_json'] ?? '[]') ?: '[]',
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Perubahan menu berhasil disimpan.');
    })->name('admin.products.update');
    Route::patch('/products/{id}', function ($id) { \Illuminate\Support\Facades\DB::table('products')->where('id',$id)->update(['available'=>!\Illuminate\Support\Facades\DB::table('products')->where('id',$id)->value('available'),'updated_at'=>now()]); return back(); })->name('admin.products.toggle');
    Route::delete('/products/{id}', function ($id) { \Illuminate\Support\Facades\DB::table('products')->where('id',$id)->delete(); return back(); })->name('admin.products.delete');
});
