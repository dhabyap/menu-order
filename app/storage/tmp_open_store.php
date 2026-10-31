<?php
DB::table('store_settings')->where('key', 'open_time')->update(['value' => '00:00', 'updated_at' => now()]);
DB::table('store_settings')->where('key', 'close_time')->update(['value' => '23:59', 'updated_at' => now()]);
echo "hours temporarily set to 00:00-23:59\n";
