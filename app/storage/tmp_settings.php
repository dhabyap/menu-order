<?php
foreach (DB::table('store_settings')->orderBy('key')->get() as $row) {
    echo $row->key . ' = ' . $row->value . PHP_EOL;
}
